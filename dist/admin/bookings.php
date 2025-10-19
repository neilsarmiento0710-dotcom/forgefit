<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
$db_path = '../database/db.php';
if (!file_exists($db_path)) {
    die("Error: Database connection file not found.");
}
include $db_path;

if (!isset($conn) || $conn === null) {
    $conn = getDBConnection();
}

// Check if user is logged in as management
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'management') {
    header("Location: ../../member_login.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['status'];
    
    $update_sql = "UPDATE bookings SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $booking_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Booking status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update booking status.";
    }
    $stmt->close();
    header("Location: bookings.php");
    exit();
}

// Handle booking deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    $delete_sql = "DELETE FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $booking_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Booking deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete booking.";
    }
    $stmt->close();
    header("Location: bookings.php");
    exit();
}

// Pagination setup
$records_per_page = 10;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Get total number of bookings
$count_sql = "SELECT COUNT(*) as total FROM bookings";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch bookings with pagination
$sql = "
    SELECT 
        b.id, 
        b.booking_date, 
        b.booking_time, 
        b.status, 
        u.username AS member_name,
        u.email AS member_email,
        trainer.username AS trainer_name,
        trainer.specialty
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN users trainer ON b.trainer_id = trainer.id
    ORDER BY b.booking_date DESC, b.booking_time DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!doctype html>
<html lang="en">
<head>
    <title>Bookings - ForgeFit Admin</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="ForgeFit Admin Bookings" />
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

    <style>
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            border-radius: 12px;
            overflow: hidden;
            background-color: #0f172a;
        }
        .modern-table th {
            background-color: #1e293b;
            color: #e2e8f0;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            padding: 14px;
        }
        .modern-table td {
            padding: 12px;
            border-bottom: 1px solid #1e293b;
            color: #cbd5e1;
            text-align: center;
        }
        .status-badge {
            padding: 6px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
        }
        .status-pending {
            background-color: #facc15;
            color: #000;
        }
        .status-confirmed {
            background-color: #3b82f6;
            color: #fff;
        }
        .status-completed {
            background-color: #22c55e;
            color: #fff;
        }
        .status-cancelled {
            background-color: #ef4444;
            color: #fff;
        }
        .dashboard-title {
            text-align: center;
            margin-top: 40px;
            color: #f8fafc;
        }
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            margin: 2px;
            transition: all 0.3s ease;
        }
        .edit-btn {
            background-color: #3b82f6;
            color: white;
        }
        .edit-btn:hover {
            background-color: #2563eb;
        }
        .delete-btn {
            background-color: #ef4444;
            color: white;
        }
        .delete-btn:hover {
            background-color: #dc2626;
        }
        .status-select {
            padding: 6px 10px;
            border-radius: 6px;
            background-color: #1e293b;
            color: #e2e8f0;
            border: 1px solid #334155;
            font-size: 0.85rem;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
        }
        .modal-content {
            background-color: #1e293b;
            margin: 10% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            color: #e2e8f0;
        }
        .modal-header {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #f8fafc;
        }
        .modal-footer {
            margin-top: 20px;
            text-align: right;
        }
        .close {
            color: #94a3b8;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #f8fafc;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 30px 0;
            padding: 20px 0;
            width: 100%;
        }
        .pagination a, .pagination span {
            padding: 10px 16px;
            background-color: #1e293b;
            color: #e2e8f0;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .pagination a:hover {
            background-color: #3b82f6;
        }
        .pagination .active {
            background-color: #3b82f6;
            font-weight: 600;
        }
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
        }
        .alert-success {
            background-color: #22c55e;
            color: white;
        }
        .alert-error {
            background-color: #ef4444;
            color: white;
        }
        .logo-two {
            font-size: 0.9rem;
            font-weight: 600;
            color: #90e0ef;
            background: rgba(144, 224, 239, 0.1);
            padding: 6px 16px;
            border-radius: 20px;
            border: 1px solid rgba(144, 224, 239, 0.3);
            margin-left: 15px;
        }

    </style>
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
            <li><a href="bookings.php" class="active">Bookings</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="payments.php">Payments</a></li>
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
        <h1 class="dashboard-title">Bookings Overview</h1>
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
                    <th>Trainer</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                            $statusClass = '';
                            switch (strtolower($row['status'])) {
                                case 'confirmed': $statusClass = 'status-confirmed'; break;
                                case 'completed': $statusClass = 'status-completed'; break;
                                case 'cancelled': $statusClass = 'status-cancelled'; break;
                                default: $statusClass = 'status-pending';
                            }
                        ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($row['member_name']); ?><br>
                                <small><?php echo htmlspecialchars($row['member_email']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['trainer_name']); ?><br>
                                <small><?php echo htmlspecialchars($row['specialty']); ?></small>
                            </td>
                            <td><?php echo date('F j, Y', strtotime($row['booking_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($row['booking_time'])); ?></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            <td>
                                <button class="action-btn edit-btn" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo $row['status']; ?>')">
                                    Edit
                                </button>
                                <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="color:#94a3b8;">No bookings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

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
</main>

<!-- Edit Status Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2 class="modal-header">Edit Booking Status</h2>
        <form method="POST" action="">
            <input type="hidden" name="booking_id" id="edit_booking_id">
            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" id="status" class="status-select" required>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="action-btn" onclick="closeEditModal()" style="background-color: #64748b;">Cancel</button>
                <button type="submit" name="update_status" class="action-btn edit-btn">Update Status</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h2 class="modal-header">Confirm Deletion</h2>
        <p>Are you sure you want to delete this booking? This action cannot be undone.</p>
        <form method="POST" action="">
            <input type="hidden" name="booking_id" id="delete_booking_id">
            <div class="modal-footer">
                <button type="button" class="action-btn" onclick="closeDeleteModal()" style="background-color: #64748b;">Cancel</button>
                <button type="submit" name="delete_booking" class="action-btn delete-btn">Delete Booking</button>
            </div>
        </form>
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

    function openEditModal(bookingId, currentStatus) {
        document.getElementById('edit_booking_id').value = bookingId;
        document.getElementById('status').value = currentStatus.toLowerCase();
        document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function confirmDelete(bookingId) {
        document.getElementById('delete_booking_id').value = bookingId;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const editModal = document.getElementById('editModal');
        const deleteModal = document.getElementById('deleteModal');
        if (event.target == editModal) {
            closeEditModal();
        }
        if (event.target == deleteModal) {
            closeDeleteModal();
        }
    }
</script>
</body>
</html>