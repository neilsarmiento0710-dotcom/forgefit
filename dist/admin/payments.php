<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../database/db.php';

// Include class files
require_once '../classes/Payment.php';
require_once '../classes/Membership.php';
require_once '../classes/User.php';
require_once '../classes/MembershipPlan.php';

// Ensure DB connection
if (!isset($conn) || $conn === null) {
    $conn = getDBConnection();
}

// Check if admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'management') {
    header("Location: ../../member_login.php");
    exit();
}

// Initialize objects
$payment = new Payment($conn);
$membership = new Membership($conn);
$user = new User($conn);

// NEW: Get management users for the dropdown
$managementUsers = $payment->getManagementUsers();

// === AUTO-SYNC: Ensure all "paid" payments have "active" memberships ===
$payment->syncPaymentStatuses();

// === Add New Payment ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $conn->begin_transaction();

    try {
        $user_id = intval($_POST['user_id']);
        $amount = floatval($_POST['amount']);
        $payment_method = $_POST['payment_method'];
        $status = $_POST['status'];
        $plan_type = $_POST['plan_type'] ?? null;
        $payment_proof = null;

        // Handle file upload
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/payments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $payment_proof = 'payment_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $payment_proof;

            if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_path)) {
                throw new Exception("Failed to upload payment proof");
            }
        }

        // Create payment
        $payment_data = [
            'user_id' => $user_id,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'status' => $status,
            'plan_type' => $plan_type,
            'payment_proof' => $payment_proof
        ];

        $payment_id = $payment->create($payment_data);

        if (!$payment_id) {
            throw new Exception("Failed to create payment");
        }

        // If payment is paid, activate or create membership
        if ($status === 'paid') {
            if ($plan_type) {
                $membership->createOrUpdateMembership($user_id, $plan_type);
            }
            $membership->activateByUserId($user_id);
            $user->updateStatus($user_id, 'active');
        }

        $conn->commit();
        $_SESSION['success_message'] = "Payment added successfully!";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Failed to add payment: " . $e->getMessage();
    }

    header("Location: payments.php");
    exit();
}

// === Update Payment ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $payment_id = intval($_POST['payment_id']);
    $amount = floatval($_POST['amount']);
    $payment_method = $_POST['payment_method'];
    $status = $_POST['status'];
    $plan_type = $_POST['plan_type'] ?? null;

    $conn->begin_transaction();

    try {
        $payment_info = $payment->getById($payment_id);
        if (!$payment_info) {
            throw new Exception("Payment not found");
        }

        $user_id = $payment_info['user_id'];
        $old_status = $payment_info['status'];

        $update_data = [
            'amount' => $amount,
            'payment_method' => $payment_method,
            'status' => $status,
            'plan_type' => $plan_type
        ];

        // NEW: Handle approved_by field
        if (isset($_POST['approved_by'])) {
            $update_data['approved_by'] = $_POST['approved_by'] === '' ? null : intval($_POST['approved_by']);
        }

        // NEW: Handle approved_at field
        if (isset($_POST['approved_at']) && !empty($_POST['approved_at'])) {
            $update_data['approved_at'] = date('Y-m-d H:i:s', strtotime($_POST['approved_at']));
        } else {
            $update_data['approved_at'] = null;
        }

        if (!$payment->update($payment_id, $update_data)) {
            throw new Exception("Failed to update payment");
        }

        // If changed to paid, ensure membership is created/updated
        if ($status === 'paid' && $old_status !== 'paid') {
            if ($plan_type) {
                $membership->createOrUpdateMembership($user_id, $plan_type);
            }
            $membership->activateByUserId($user_id);
            $user->updateStatus($user_id, 'active');
        }
        
        // If payment is already paid and plan type was changed, update membership plan
        if ($status === 'paid' && $old_status === 'paid' && $plan_type !== $payment_info['plan_type']) {
            $membership->createOrUpdateMembership($user_id, $plan_type);
        }

        // If status changed from paid to something else
        if ($status !== 'paid' && $old_status === 'paid') {
            $has_other_paid = $payment->hasOtherPaidPayments($user_id, $payment_id);
            if (!$has_other_paid) {
                $membership->deactivateByUserId($user_id);
                $user->updateStatus($user_id, 'inactive');
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

    if ($payment->delete($payment_id)) {
        $_SESSION['success_message'] = "Payment deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete payment.";
    }

    header("Location: payments.php");
    exit();
}

// === Approve Payment (Quick Action) ===
if (isset($_POST['approve_id'])) {
    $payment_id = intval($_POST['approve_id']);
    $admin_id = $_SESSION['user']['id'];

    $conn->begin_transaction();

    try {
        $payment_info = $payment->getById($payment_id);
        if (!$payment_info) {
            throw new Exception("Payment not found");
        }

        $user_id = $payment_info['user_id'];
        $plan_type = $payment_info['plan_type'] ?? null;

        if (!$payment->approve($payment_id, $admin_id)) {
            throw new Exception("Failed to approve payment");
        }

        // Ensure membership is created or updated
        if ($plan_type) {
            $membership->createOrUpdateMembership($user_id, $plan_type);
        }

        $membership->activateByUserId($user_id);
        $user->updateStatus($user_id, 'active');

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

$total_records = $payment->getTotalCount();
$total_pages = ceil($total_records / $records_per_page);
$payments_list = $payment->getAllWithDetails($records_per_page, $offset);
$members_list = $user->getMembersList();
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
    
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    <link rel="stylesheet" href="../assets/css/home.css"/> 
    <link rel="stylesheet" href="../assets/css/member_dashboard.css" id="main-style-link"/> 
    <link rel="stylesheet" href="../assets/css/payments_a.css"/> 
    <link rel="stylesheet" href="../assets/css/sidebar.css" />
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
            <div class="mobile-menu" id="mobileMenuBtn">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>
    
    <div class="sidebar" id="sidebar">
        <button class="sidebar-close" id="sidebarClose">×</button>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="bookings.php">Bookings</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="payments.php" class="active">Payments</a></li>
            <li><a href="member_rates.php">Membership Rates</a></li>
            <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
        </ul>
    </div>

<main>
    <div class="dashboard-hero">
        <h1 class="dashboard-title">Payments Management</h1>
    </div>

    <div style="text-align: right; margin-bottom: 1rem;">
        <button class="action-btn edit-btn" onclick="openAddPaymentModal()" style="font-size: 0.9rem;">
            Add New Payment
        </button>
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
                    <th>Type</th>
                    <th>Proof</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Approved By</th>
                    <th>Approved At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($payments_list && count($payments_list) > 0): ?>
                    <?php foreach ($payments_list as $row): ?>
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
                                <?php 
                                    echo !empty($row['plan_type']) 
                                        ? ucfirst($row['plan_type']) 
                                        : '<span class="no-proof">—</span>'; 
                                ?>
                            </td>
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
                                    echo !empty($row['approved_at']) && $row['approved_at'] !== '0000-00-00 00:00:00'
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
                                <button class="action-btn edit-btn" onclick='openEditModal(<?php 
                                    echo json_encode([
                                        'id' => $row['id'],
                                        'amount' => $row['amount'],
                                        'payment_method' => $row['payment_method'],
                                        'status' => $row['status'],
                                        'plan_type' => $row['plan_type'] ?? 'basic',
                                        'approved_by' => $row['approved_by'] ?? '',
                                        'approved_at' => $row['approved_at'] ?? ''
                                    ]); 
                                ?>)'>Edit</button>
                                <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11" style="color:#94a3b8;">No payment records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

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

    <!-- Add Payment Modal -->
    <div id="addPaymentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddPaymentModal()">&times;</span>
            <h2 class="modal-header">Add New Payment</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="add_user_id">Select Member:</label>
                    <select name="user_id" id="add_user_id" class="form-control" required>
                        <option value="">-- Select Member --</option>
                        <?php foreach ($members_list as $member): ?>
                            <option value="<?php echo $member['id']; ?>">
                                <?php echo htmlspecialchars($member['username']) . ' (' . htmlspecialchars($member['email']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add_amount">Amount (₱):</label>
                    <input type="number" name="amount" id="add_amount" class="form-control" step="0.01" required min="0">
                </div>
                
                <div class="form-group">
                    <label for="add_payment_method">Payment Method:</label>
                    <select name="payment_method" id="add_payment_method" class="form-control" required>
                        <option value="gcash">GCash</option>
                        <option value="paymaya">PayMaya</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add_status">Status:</label>
                    <select name="status" id="add_status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="add_plan_type">Plan Type:</label>
                    <select name="plan_type" id="add_plan_type" class="form-control" required>
                        <option value="">-- Select Plan --</option>
                        <option value="basic">Basic</option>
                        <option value="premium">Premium</option>
                        <option value="elite">Elite</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add_payment_proof">Payment Proof (Optional):</label>
                    <input type="file" name="payment_proof" id="add_payment_proof" class="form-control" accept="image/*">
                    <small style="color: #94a3b8;">Upload receipt or proof of payment (JPG, PNG, max 5MB)</small>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="action-btn" onclick="closeAddPaymentModal()" style="background-color: #64748b;">Cancel</button>
                    <button type="submit" name="add_payment" class="action-btn edit-btn">Add Payment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Payment Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
            <h2 style="margin-top:0;">✏️ Edit Payment</h2>
            <form method="POST" action="">
                <input type="hidden" name="payment_id" id="edit_payment_id">
                
                <div class="form-group">
                    <label>Amount (₱)</label>
                    <input type="number" name="amount" id="edit_amount" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" id="edit_payment_method" required>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="paymaya">PayMaya</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="credit_card">Credit Card</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Plan Type</label>
                    <select name="plan_type" id="edit_plan_type" required>
                        <option value="basic">Basic</option>
                        <option value="premium">Premium</option>
                        <option value="elite">Elite</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Approved By</label>
                    <select name="approved_by" id="edit_approved_by">
                        <option value="">Not Approved</option>
                        <?php foreach ($managementUsers as $manager): ?>
                            <option value="<?php echo $manager['id']; ?>">
                                <?php echo htmlspecialchars($manager['name'] ?? $manager['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Approved Date & Time</label>
                    <input type="datetime-local" name="approved_at" id="edit_approved_at">
                    <small style="color: #64748b;">Leave empty if not approved yet</small>
                </div>
                
                <button type="submit" name="update_payment" class="submit-btn">💾 Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
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

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <div class="image-modal-content">
            <span class="image-close" onclick="closeImageModal()">&times;</span>
            <img id="modalImage" class="modal-image" src="" alt="Payment Proof">
        </div>
    </div>
</main>

<footer>
    <div class="footer-bottom">
        <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
    </div>
</footer>

<script src="../assets/js/plugins/feather.min.js"></script>
<script>
    function openAddPaymentModal() {
        document.getElementById('add_user_id').value = '';
        document.getElementById('add_amount').value = '';
        document.getElementById('add_payment_method').value = 'gcash';
        document.getElementById('add_status').value = 'pending';
        document.getElementById('add_plan_type').value = '';
        document.getElementById('add_payment_proof').value = '';
        
        document.getElementById('addPaymentModal').style.display = 'block';
    }

    function closeAddPaymentModal() {
        document.getElementById('addPaymentModal').style.display = 'none';
    }

    function openEditModal(payment) {
        document.getElementById('edit_payment_id').value = payment.id;
        document.getElementById('edit_amount').value = payment.amount;
        document.getElementById('edit_payment_method').value = payment.payment_method;
        document.getElementById('edit_status').value = payment.status;
        document.getElementById('edit_plan_type').value = payment.plan_type || 'basic';
        
        // Set approved_by value
        document.getElementById('edit_approved_by').value = payment.approved_by || '';
        
        // Set approved_at value - convert MySQL datetime to HTML5 datetime-local format
        if (payment.approved_at && payment.approved_at !== '' && payment.approved_at !== '0000-00-00 00:00:00') {
            const approvedDate = payment.approved_at.replace(' ', 'T').substring(0, 16);
            document.getElementById('edit_approved_at').value = approvedDate;
        } else {
            document.getElementById('edit_approved_at').value = '';
        }
        
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
        const addModal = document.getElementById('addPaymentModal');
        
        if (event.target == editModal) {
            closeEditModal();
        }
        if (event.target == deleteModal) {
            closeDeleteModal();
        }
        if (event.target == imageModal) {
            closeImageModal();
        }
        if (event.target == addModal) {
            closeAddPaymentModal();
        }
    }

    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImageModal();
            closeEditModal();
            closeDeleteModal();
            closeAddPaymentModal();
        }
    });

    // Mobile menu and sidebar
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarClose = document.getElementById('sidebarClose');

        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.add('active');
            mobileMenuBtn.classList.add('open');
        });

        sidebarClose.addEventListener('click', () => {
            sidebar.classList.remove('active');
            mobileMenuBtn.classList.remove('open');
        });

        const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                sidebar.classList.remove('active');
                mobileMenuBtn.classList.remove('open');
            });
        });

        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                sidebar.classList.remove('active');
                mobileMenuBtn.classList.remove('open');
            }
        });

        // Header background change on scroll
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 50) {
                header.style.background = 'linear-gradient(135deg, rgba(15, 23, 42, 0.98) 0%, rgba(30, 41, 59, 0.98) 100%)';
            } else {
                header.style.background = 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)';
            }
        });
    });
</script>
</body>
</html>