<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
$db_path = '../database/db.php';
if (!file_exists($db_path)) {
    die("Error: Database connection file not found.");
}

require_once '../database/db.php';
require_once '../classes/Booking.php';

// Check if user is logged in as management
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'management') {
    header("Location: ../../login.php");
    exit();
}

$bookingModel = new Booking();
$db = Database::getInstance();

// Handle Add Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_booking'])) {
    $user_id = intval($_POST['user_id']);
    $trainer_id = intval($_POST['trainer_id']);
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $notes = $_POST['notes'] ?? '';
    
    // Get member details
    $stmt = $db->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
    
    if ($member) {
        // Check for conflicts
        if ($bookingModel->hasConflict($trainer_id, $booking_date, $booking_time)) {
            $_SESSION['error_message'] = "This trainer already has a booking at this time.";
        } else {
            $data = [
                'user_id' => $user_id,
                'trainer_id' => $trainer_id,
                'booking_date' => $booking_date,
                'booking_time' => $booking_time,
                'member_name' => $member['username'],
                'member_email' => $member['email'],
                'status' => 'confirmed',
                'notes' => $notes
            ];
            
            if ($bookingModel->createBooking($data)) {
                $_SESSION['success_message'] = "Booking added successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to add booking.";
            }
        }
    } else {
        $_SESSION['error_message'] = "Member not found.";
    }
    
    header("Location: bookings.php");
    exit();
}

// Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['status'];
    
    if ($bookingModel->updateStatus($booking_id, $new_status)) {
        $_SESSION['success_message'] = "Booking status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update booking status.";
    }
    header("Location: bookings.php");
    exit();
}

// Handle booking deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    if ($bookingModel->deleteBooking($booking_id)) {
        $_SESSION['success_message'] = "Booking deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete booking.";
    }
    header("Location: bookings.php");
    exit();
}

// Get members and trainers for dropdown
$members = $db->query("SELECT id, username, email FROM users WHERE role = 'member' ORDER BY username")->fetch_all(MYSQLI_ASSOC);
$trainers = $db->query("SELECT id, username, specialty FROM users WHERE role = 'trainer' ORDER BY username")->fetch_all(MYSQLI_ASSOC);

// Pagination setup
$records_per_page = 10;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Get all bookings and calculate pagination
$all_bookings = $bookingModel->getAllBookings();
$total_records = count($all_bookings);
$total_pages = ceil($total_records / $records_per_page);

// Get bookings for current page
$bookings = array_slice($all_bookings, $offset, $records_per_page);
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
    <link rel="stylesheet" href="../assets/css/member_dashboard.css"/> 
    <link rel="stylesheet" href="../assets/css/bookings_a.css" id="main-style-link"/>
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
            <li><a href="bookings.php" class="active">Bookings</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="payments.php">Payments</a></li>
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
                <li><a href="bookings.php" class="active">Bookings</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="payments.php">Payments</a></li>
                <li><a href="member_rates.php">Membership Rates</a></li>
                <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
            </ul>
    </div>

<main>
    <div class="dashboard-hero">
        <h1 class="dashboard-title">Bookings Management</h1>
    </div>
    <div class="tabs">
        <button class="action-btn edit-btn" onclick="openAddModal()" style="font-size: 0.9rem;">
            Add New Booking
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
                    <th>Trainer</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($bookings)): ?>
                    <?php foreach ($bookings as $row): ?>
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
                    <?php endforeach; ?>
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

<!-- Add Booking Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddModal()">&times;</span>
        <h2 class="modal-header">Add New Booking</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="user_id">Select Member:</label>
                <select name="user_id" id="user_id" class="status-select" required style="width: 100%; padding: 10px;">
                    <option value="">-- Choose Member --</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?php echo $member['id']; ?>">
                            <?php echo htmlspecialchars($member['username']); ?> (<?php echo htmlspecialchars($member['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="trainer_id">Select Trainer:</label>
                <select name="trainer_id" id="trainer_id" class="status-select" required style="width: 100%; padding: 10px;">
                    <option value="">-- Choose Trainer --</option>
                    <?php foreach ($trainers as $trainer): ?>
                        <option value="<?php echo $trainer['id']; ?>">
                            <?php echo htmlspecialchars($trainer['username']); ?> - <?php echo htmlspecialchars($trainer['specialty']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="booking_date">Booking Date:</label>
                <input type="date" name="booking_date" id="booking_date" class="status-select" required 
                       min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 10px;">
            </div>
            
            <div class="form-group">
                <label for="booking_time">Booking Time:</label>
                <input type="time" name="booking_time" id="booking_time" class="status-select" required 
                       style="width: 100%; padding: 10px;">
            </div>
            
            <div class="form-group">
                <label for="notes">Notes (Optional):</label>
                <textarea name="notes" id="notes" class="status-select" rows="3" 
                          placeholder="Add any additional notes..." 
                          style="width: 100%; padding: 10px; resize: vertical; font-family: inherit;"></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="action-btn" onclick="closeAddModal()" style="background-color: #64748b; color: white;">Cancel</button>
                <button type="submit" name="add_booking" class="action-btn edit-btn">Add Booking</button>
            </div>
        </form>
    </div>
</div>

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

    function openAddModal() {
        document.getElementById('addModal').style.display = 'block';
    }

    function closeAddModal() {
        document.getElementById('addModal').style.display = 'none';
    }

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
        const addModal = document.getElementById('addModal');
        const editModal = document.getElementById('editModal');
        const deleteModal = document.getElementById('deleteModal');
        
        if (event.target == addModal) {
            closeAddModal();
        }
        if (event.target == editModal) {
            closeEditModal();
        }
        if (event.target == deleteModal) {
            closeDeleteModal();
        }
    }
</script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenu = document.querySelector('.mobile-menu');
            const navLinks = document.querySelector('.nav-links');
            
            if (mobileMenu) {
                mobileMenu.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                });
            }

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
    <script>
// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Sidebar functionality
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const sidebar = document.getElementById('sidebar');
const sidebarClose = document.getElementById('sidebarClose');

// Open sidebar
mobileMenuBtn.addEventListener('click', () => {
    sidebar.classList.add('active');
    mobileMenuBtn.classList.add('open');
});

// Close sidebar with close button
sidebarClose.addEventListener('click', () => {
    sidebar.classList.remove('active');
    mobileMenuBtn.classList.remove('open');
});

// Close sidebar when clicking on a link
const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
sidebarLinks.forEach(link => {
    link.addEventListener('click', () => {
        sidebar.classList.remove('active');
        mobileMenuBtn.classList.remove('open');
    });
});

// Close sidebar when clicking outside
document.addEventListener('click', (e) => {
    if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
        sidebar.classList.remove('active');
        mobileMenuBtn.classList.remove('open');
    }
});
</script>
</body>
</html>