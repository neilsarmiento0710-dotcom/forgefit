<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
$db_path = '../database/db.php';
if (!file_exists($db_path)) {
    die("Error: Database connection file not found.");
}
require_once $db_path;

// Ensure DB connection
if (!isset($conn) || $conn === null) {
    $conn = getDBConnection();
}

// Check if admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'management') {
    header("Location: ../../member_login.php");
    exit();
}

// === AUTO-SYNC: Ensure all "paid" payments have "active" memberships ===
// Note: Memberships are linked via user_id, not payment_id
$sync_sql = "UPDATE memberships m
             INNER JOIN payments p ON m.user_id = p.user_id
             SET m.status = 'active'
             WHERE p.status = 'paid' AND m.status != 'active'";
$conn->query($sync_sql);

// Also sync user status
$sync_user_sql = "UPDATE users u
                  INNER JOIN payments p ON u.id = p.user_id
                  SET u.status = 'active'
                  WHERE p.status = 'paid' AND u.status != 'active'";
$conn->query($sync_user_sql);

// === Update Payment ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $payment_id = intval($_POST['payment_id']);
    $amount = floatval($_POST['amount']);
    $payment_method = $_POST['payment_method'];
    $status = $_POST['status'];
    
    $conn->begin_transaction();
    
    try {
        // Get current payment info
        $get_info_sql = "SELECT user_id, status as old_status FROM payments WHERE id = ?";
        $stmt = $conn->prepare($get_info_sql);
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $payment_info = $result->fetch_assoc();
        
        if (!$payment_info) {
            throw new Exception("Payment not found");
        }
        
        $user_id = $payment_info['user_id'];
        $old_status = $payment_info['old_status'];
        
        // Update payment
        $update_sql = "UPDATE payments SET amount = ?, payment_method = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("dssi", $amount, $payment_method, $status, $payment_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update payment");
        }
        
        // If status changed to 'paid', activate membership and user
        if ($status === 'paid' && $old_status !== 'paid') {
            // Update all memberships for this user to active
            $update_membership_sql = "UPDATE memberships SET status = 'active' WHERE user_id = ?";
            $stmt = $conn->prepare($update_membership_sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $update_user_sql = "UPDATE users SET status = 'active' WHERE id = ?";
            $stmt = $conn->prepare($update_user_sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }
        
        // If status changed from 'paid' to something else, deactivate membership
        if ($status !== 'paid' && $old_status === 'paid') {
            // Check if user has any other paid payments
            $check_sql = "SELECT COUNT(*) as paid_count FROM payments WHERE user_id = ? AND status = 'paid' AND id != ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("ii", $user_id, $payment_id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            $paid_count = $check_result->fetch_assoc()['paid_count'];
            
            // Only deactivate if no other paid payments exist
            if ($paid_count == 0) {
                $update_membership_sql = "UPDATE memberships SET status = 'inactive' WHERE user_id = ?";
                $stmt = $conn->prepare($update_membership_sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                $update_user_sql = "UPDATE users SET status = 'inactive' WHERE id = ?";
                $stmt = $conn->prepare($update_user_sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        $_SESSION['success_message'] = "Payment updated successfully!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Failed to update payment: " . $e->getMessage();
    }
    
    header("Location: payments.php");
    exit();
}

// === Delete Payment ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payment'])) {
    $payment_id = intval($_POST['payment_id']);
    
    $delete_sql = "DELETE FROM payments WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $payment_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Payment deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete payment.";
    }
    $stmt->close();
    header("Location: payments.php");
    exit();
}

// === Approve Payment (Quick Action) ===
if (isset($_POST['approve_id'])) {
    $payment_id = intval($_POST['approve_id']);
    $admin_id = $_SESSION['user']['id']; // management user ID

    $conn->begin_transaction();

    try {
        // Get payment info
        $get_info_sql = "SELECT user_id FROM payments WHERE id = ?";
        $stmt = $conn->prepare($get_info_sql);
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $payment_info = $result->fetch_assoc();

        if (!$payment_info) {
            throw new Exception("Payment not found");
        }

        $user_id = $payment_info['user_id'];

        // ✅ Update payment: mark as paid + log approver and timestamp
        $update_payment_sql = "UPDATE payments 
                               SET status = 'paid', approved_by = ?, approved_at = NOW() 
                               WHERE id = ?";
        $stmt = $conn->prepare($update_payment_sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare update_payment_sql: " . $conn->error);
        }
        $stmt->bind_param("ii", $admin_id, $payment_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update payment: " . $stmt->error);
        }

        // ✅ Activate memberships and user
        $update_membership_sql = "UPDATE memberships SET status = 'active' WHERE user_id = ?";
        $stmt = $conn->prepare($update_membership_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $update_user_sql = "UPDATE users SET status = 'active' WHERE id = ?";
        $stmt = $conn->prepare($update_user_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $conn->commit();
        $_SESSION['success_message'] = "Payment approved successfully by " . htmlspecialchars($_SESSION['user']['username']) . "!";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Failed to approve payment: " . $e->getMessage();
    }

    header("Location: payments.php");
    exit();
}


// Pagination setup
$records_per_page = 10;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Get total number of payments
$count_sql = "SELECT COUNT(*) as total FROM payments";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// === Fetch Payments with Pagination ===
$sql = "SELECT 
            p.id, 
            u.username, 
            u.email, 
            p.amount, 
            p.payment_method, 
            p.status, 
            p.payment_proof, 
            p.created_at, 
            p.approved_by, 
            p.approved_at, 
            m.username AS approved_by_name
        FROM payments p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN users m ON p.approved_by = m.id
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!doctype html>
<html lang="en">
<head>
    <title>Payments - ForgeFit Admin</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="ForgeFit Admin Payments" />
    <meta name="author" content="Sniper 2025" />
    
    <!-- Fonts & Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    <link rel="stylesheet" href="../assets/css/home.css?v=4"/> 
    <link rel="stylesheet" href="../assets/css/member_dashboard.css" id="main-style-link"/> 
    <link rel="stylesheet" href="../assets/css/payments_a.css"/> 
</head>

<body>
<header>
    <nav>
         <div style="display: flex; align-items: center; gap: 15px;">
        <div class="logo">ForgeFit</div>
        <div class="logo-two">Admin</div>
    </div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="bookings.php">Bookings</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="payments.php" class="active">Payments</a></li>
            <li><a href="member_rates.php">Membership Rates</a></li>
            <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
        </ul>
        <div class="mobile-menu">
            <span></span><span></span><span></span>
        </div>
    </nav>
</header>

<main>
    <div class="dashboard-hero">
        <h1 class="dashboard-title">💳 Payments Management</h1>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="earnings-grid">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Member</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Proof</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Approved By</th>
                    <th>Approved At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                            $statusClass = '';
                            switch (strtolower($row['status'])) {
                                case 'paid': $statusClass = 'status-paid'; break;
                                case 'failed': $statusClass = 'status-failed'; break;
                                default: $statusClass = 'status-pending';
                            }
                        ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($row['username']); ?><br>
                                <small><?php echo htmlspecialchars($row['email']); ?></small>
                            </td>
                            <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                            <td><?php echo ucfirst($row['payment_method']); ?></td>
                            <td>
                                <?php if (!empty($row['payment_proof'])): ?>
                                    <a href="#" 
                                       class="view-proof-btn" 
                                       onclick="openImageModal('../uploads/payments/<?php echo htmlspecialchars($row['payment_proof']); ?>'); return false;">
                                       View Proof
                                    </a>
                                <?php else: ?>
                                    <span class="no-proof">No proof</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <?php 
                                    echo !empty($row['approved_by_name']) 
                                        ? htmlspecialchars($row['approved_by_name']) 
                                        : '<span class="no-proof">—</span>';
                                ?>
                            </td>
                            <td>
                                <?php 
                                    echo !empty($row['approved_at']) 
                                        ? date('M d, Y h:i A', strtotime($row['approved_at'])) 
                                        : '<span class="no-proof">—</span>';
                                ?>
                            </td>

                            <td>
                                <?php if ($row['status'] != 'paid'): ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="approve_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="action-btn approve-btn">Approve</button>
                                    </form>
                                <?php endif; ?>
                                <button class="action-btn edit-btn" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['username']); ?>', <?php echo $row['amount']; ?>, '<?php echo $row['payment_method']; ?>', '<?php echo $row['status']; ?>')">
                                    Edit
                                </button>
                                <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="color:#94a3b8;">No payment records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=1">First</a>
                    <a href="?page=<?php echo $current_page - 1; ?>">Previous</a>
                <?php else: ?>
                    <span class="disabled">First</span>
                    <span class="disabled">Previous</span>
                <?php endif; ?>

                <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <?php if ($i == $current_page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>">Next</a>
                    <a href="?page=<?php echo $total_pages; ?>">Last</a>
                <?php else: ?>
                    <span class="disabled">Next</span>
                    <span class="disabled">Last</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Edit Payment Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2 class="modal-header">Edit Payment</h2>
        <form method="POST" action="">
            <input type="hidden" name="payment_id" id="edit_payment_id">
            
            <div class="form-group">
                <label>Member:</label>
                <input type="text" id="edit_member" class="form-control" readonly>
            </div>
            
            <div class="form-group">
                <label for="edit_amount">Amount (₱):</label>
                <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="edit_payment_method">Payment Method:</label>
                <select name="payment_method" id="edit_payment_method" class="form-control" required>
                    <option value="gcash">GCash</option>
                    <option value="paymaya">PayMaya</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="edit_status">Status:</label>
                <select name="status" id="edit_status" class="form-control" required>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="action-btn" onclick="closeEditModal()" style="background-color: #64748b;">Cancel</button>
                <button type="submit" name="update_payment" class="action-btn edit-btn">Update Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h2 class="modal-header">Confirm Deletion</h2>
        <p>Are you sure you want to delete this payment record? This action cannot be undone.</p>
        <form method="POST" action="">
            <input type="hidden" name="payment_id" id="delete_payment_id">
            <div class="modal-footer">
                <button type="button" class="action-btn" onclick="closeDeleteModal()" style="background-color: #64748b;">Cancel</button>
                <button type="submit" name="delete_payment" class="action-btn delete-btn">Delete Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- Image Modal for Payment Proof -->
<div id="imageModal" class="image-modal">
    <div class="image-modal-content">
        <span class="image-close" onclick="closeImageModal()">&times;</span>
        <img id="modalImage" class="modal-image" src="" alt="Payment Proof">
    </div>
</div>

<footer>
    <div class="footer-bottom">
        <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
    </div>
</footer>

<script src="../assets/js/plugins/feather.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenu = document.querySelector('.mobile-menu');
        const navLinks = document.querySelector('.nav-links');
        if (mobileMenu) {
            mobileMenu.addEventListener('click', function() {
                navLinks.classList.toggle('active');
            });
        }
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 50) {
                header.style.background = 'linear-gradient(135deg, rgba(15, 23, 42, 0.98) 0%, rgba(30, 41, 59, 0.98) 100%)';
            } else {
                header.style.background = 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)';
            }
        });
    });

    function openEditModal(id, member, amount, method, status) {
        document.getElementById('edit_payment_id').value = id;
        document.getElementById('edit_member').value = member;
        document.getElementById('edit_amount').value = amount;
        document.getElementById('edit_payment_method').value = method;
        document.getElementById('edit_status').value = status;
        document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function confirmDelete(id) {
        document.getElementById('delete_payment_id').value = id;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    function openImageModal(imageSrc) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        modal.classList.add('active');
        modalImg.src = imageSrc;
    }

    function closeImageModal() {
        const modal = document.getElementById('imageModal');
        modal.classList.remove('active');
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const editModal = document.getElementById('editModal');
        const deleteModal = document.getElementById('deleteModal');
        const imageModal = document.getElementById('imageModal');
        
        if (event.target == editModal) {
            closeEditModal();
        }
        if (event.target == deleteModal) {
            closeDeleteModal();
        }
        if (event.target == imageModal) {
            closeImageModal();
        }
    }

    // Close image modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImageModal();
        }
    });
</script>
</body>
</html>