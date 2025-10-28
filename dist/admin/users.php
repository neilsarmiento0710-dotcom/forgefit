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
require_once '../classes/User.php';
require_once '../classes/Booking.php';
require_once '../classes/Payment.php';
require_once '../classes/Membership.php';

// Check if admin (management) is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'management') {
    header("Location: ../../login.php");
    exit();
}

$userModel = new User();
$bookingModel = new Booking();
$paymentModel = new Payment();
$membershipModel = new Membership();


if (isset($_POST['update_user'])) {
    $user_id = intval($_POST['user_id']);

    $data = [
        'username' => trim($_POST['username']),
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'address' => trim($_POST['address'] ?? ''),
        'role' => $_POST['role'],
        'status' => $_POST['status'],
        'specialty' => trim($_POST['specialty'] ?? '')
    ];

    if (!empty($_POST['password'])) {
        $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    if ($userModel->updateUser($user_id, $data)) {
        $_SESSION['success_message'] = "✅ User updated successfully!";
    } else {
        $_SESSION['error_message'] = "❌ Failed to update user.";
    }
    
    // IMPORTANT: This redirect must happen
    $redirect_params = [];
    if (isset($_GET['members_page'])) {
        $redirect_params[] = 'members_page=' . $_GET['members_page'];
    }
    if (isset($_GET['trainers_page'])) {
        $redirect_params[] = 'trainers_page=' . $_GET['trainers_page'];
    }
    if (isset($_GET['tab'])) {
        $redirect_params[] = 'tab=' . $_GET['tab'];
    }
    
    $redirect_url = 'users.php' . (!empty($redirect_params) ? '?' . implode('&', $redirect_params) : '');
    header("Location: " . $redirect_url);
    exit(); // MUST have exit() here
}

// === ADD NEW USER ===
if (isset($_POST['add_user'])) {
    $data = [
        'username' => trim($_POST['username']),
        'name' => trim($_POST['name']), // NEW: Add name field
        'email' => trim($_POST['email']),
        'password' => $_POST['password'],
        'phone' => trim($_POST['phone']),
        'role' => $_POST['role'],
        'address' => trim($_POST['address'] ?? ''),
        'specialty' => trim($_POST['specialty'] ?? '')
    ];
    
    if ($userModel->createUser($data)) {
        $_SESSION['success_message'] = "✅ New user added successfully!";
    } else {
        $_SESSION['error_message'] = "❌ Failed to add user. Email might already exist.";
    }
    header("Location: users.php");
    exit();
}

// === DELETE USER ===
if (isset($_POST['delete_user_id'])) {
    $user_id = intval($_POST['delete_user_id']);
    
    if ($userModel->deleteUser($user_id)) {
        $_SESSION['success_message'] = "✅ User deleted successfully!";
    } else {
        $_SESSION['error_message'] = "❌ Failed to delete user.";
    }
    header("Location: users.php");
    exit();
}

// Determine active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'members';

// Pagination setup
$records_per_page = 10;
$members_page = isset($_GET['members_page']) ? max(1, intval($_GET['members_page'])) : 1;
$trainers_page = isset($_GET['trainers_page']) ? max(1, intval($_GET['trainers_page'])) : 1;

// Calculate offsets
$members_offset = ($members_page - 1) * $records_per_page;
$trainers_offset = ($trainers_page - 1) * $records_per_page;

// Fetch members (paginated)
$all_members = $userModel->getUsersByRole('member');
$total_members = count($all_members);
$members = array_slice($all_members, $members_offset, $records_per_page);

// Fetch trainers (paginated)
$all_trainers = $userModel->getUsersByRole('trainer');
$total_trainers = count($all_trainers);
$trainers = array_slice($all_trainers, $trainers_offset, $records_per_page);

$total_members_pages = max(1, ceil($total_members / $records_per_page));
$total_trainers_pages = max(1, ceil($total_trainers / $records_per_page));

// Build pagination URLs
function buildPaginationUrl($page, $type) {
    $params = $_GET;
    if ($type === 'members') {
        $params['members_page'] = $page;
        $params['tab'] = 'members';
    } else {
        $params['trainers_page'] = $page;
        $params['tab'] = 'trainers';
    }
    return 'users.php?' . http_build_query($params);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Users - ForgeFit Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/home.css"/>
    <link rel="stylesheet" href="../assets/css/member_dashboard.css"/>
    <link rel="stylesheet" href="../assets/css/users_a.css"/>
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="bookings.php">Bookings</a></li>
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="bookings.php">Bookings</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="payments.php">Payments</a></li>
                <li><a href="member_rates.php">Membership Rates</a></li>
                <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
            </ul>
        </div>

<main>
    <div class="dashboard-hero">
        <h1 class="dashboard-title" style="text-align: center;">Users Management</h1>
    </div>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="message-box success"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php elseif (isset($_SESSION['error_message'])): ?>
        <div class="message-box error"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <div class="tabs">
        <a href="?tab=members&members_page=<?php echo $members_page; ?>" class="tab <?php echo $active_tab === 'members' ? 'active' : ''; ?>">Members (<?php echo $total_members; ?>)</a>
        <a href="?tab=trainers&trainers_page=<?php echo $trainers_page; ?>" class="tab <?php echo $active_tab === 'trainers' ? 'active' : ''; ?>">Trainers (<?php echo $total_trainers; ?>)</a>
        <button class="action-btn edit-btn" onclick="openAddModal()" style="font-size: 0.9rem;">
            Add New User
        </button>
    </div>
    <!-- Members Table -->
    <div class="table-container" id="members-table" style="display: <?php echo $active_tab === 'members' ? 'block' : 'none'; ?>;">
    <table class="modern-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Status</th>
                <th>Membership</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($members)): ?>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($member['id']); ?></td>
                        <td><?php echo htmlspecialchars($member['name'] ?? $member['username']); ?></td>
                        <td><?php echo htmlspecialchars($member['username']); ?></td>
                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                        <td><?php echo htmlspecialchars($member['phone'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($member['address'] ?? '—'); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo htmlspecialchars($member['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($member['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                                $membership = $membershipModel->getUserLatestMembership($member['id']);
                                if ($membership) {
                                    $status = $membership['status'];
                                    $end_date = $membership['end_date'];
                                    
                                    if ($status === 'active' && strtotime($end_date) < time()) {
                                        echo '<span style="color:#f59e0b;">Expired</span>';
                                    } else {
                                        $color = ($status === 'active') ? '#16a34a' : '#f59e0b';
                                        echo '<span style="color:' . $color . ';">' . ucfirst(htmlspecialchars($status)) . '</span>';
                                    }
                                } else {
                                    echo '<span style="color:#64748b;">None</span>';
                                }
                            ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                        <td>
                            <button class="action-btn edit-btn" onclick='openEditModal(<?php echo json_encode($member); ?>, "member")'>Edit</button>
                            <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars(addslashes($member['name'] ?? $member['username'])); ?>')">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="10" style="text-align:center;">No members found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
        <?php if ($total_members_pages > 1): ?>
            <div class="pagination">
                <?php if ($members_page > 1): ?>
                    <a href="<?php echo buildPaginationUrl($members_page - 1, 'members'); ?>">&laquo; Prev</a>
                <?php else: ?>
                    <span class="disabled">&laquo; Prev</span>
                <?php endif; ?>

                <?php 
                $start_page = max(1, $members_page - 2);
                $end_page = min($total_members_pages, $members_page + 2);
                
                if ($start_page > 1): ?>
                    <a href="<?php echo buildPaginationUrl(1, 'members'); ?>">1</a>
                    <?php if ($start_page > 2): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $members_page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo buildPaginationUrl($i, 'members'); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($end_page < $total_members_pages): ?>
                    <?php if ($end_page < $total_members_pages - 1): ?>
                        <span>...</span>
                    <?php endif; ?>
                    <a href="<?php echo buildPaginationUrl($total_members_pages, 'members'); ?>"><?php echo $total_members_pages; ?></a>
                <?php endif; ?>

                <?php if ($members_page < $total_members_pages): ?>
                    <a href="<?php echo buildPaginationUrl($members_page + 1, 'members'); ?>">Next &raquo;</a>
                <?php else: ?>
                    <span class="disabled">Next &raquo;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Trainers Table -->
    <div class="table-container" id="trainers-table" style="display: <?php echo $active_tab === 'trainers' ? 'block' : 'none'; ?>;">
        <table class="modern-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Specialty</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($trainers)): ?>
                <?php foreach ($trainers as $trainer): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($trainer['id']); ?></td>
                        <td><?php echo htmlspecialchars($trainer['name'] ?? $trainer['username']); ?></td>
                        <td><?php echo htmlspecialchars($trainer['username']); ?></td>
                        <td><?php echo htmlspecialchars($trainer['email']); ?></td>
                        <td><?php echo htmlspecialchars($trainer['phone'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($trainer['specialty'] ?? '—'); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo htmlspecialchars($trainer['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($trainer['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($trainer['created_at'])); ?></td>
                        <td>
                            <button class="action-btn edit-btn" onclick='openEditModal(<?php echo json_encode($trainer); ?>, "trainer")'>Edit</button>
                            <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $trainer['id']; ?>, '<?php echo htmlspecialchars(addslashes($trainer['name'] ?? $trainer['username'])); ?>')">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" style="text-align:center;">No trainers found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
        <?php if ($total_trainers_pages > 1): ?>
            <div class="pagination">
                <?php if ($trainers_page > 1): ?>
                    <a href="<?php echo buildPaginationUrl($trainers_page - 1, 'trainers'); ?>">&laquo; Prev</a>
                <?php else: ?>
                    <span class="disabled">&laquo; Prev</span>
                <?php endif; ?>

                <?php 
                $start_page = max(1, $trainers_page - 2);
                $end_page = min($total_trainers_pages, $trainers_page + 2);
                
                if ($start_page > 1): ?>
                    <a href="<?php echo buildPaginationUrl(1, 'trainers'); ?>">1</a>
                    <?php if ($start_page > 2): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $trainers_page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo buildPaginationUrl($i, 'trainers'); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($end_page < $total_trainers_pages): ?>
                    <?php if ($end_page < $total_trainers_pages - 1): ?>
                        <span>...</span>
                    <?php endif; ?>
                    <a href="<?php echo buildPaginationUrl($total_trainers_pages, 'trainers'); ?>"><?php echo $total_trainers_pages; ?></a>
                <?php endif; ?>

                <?php if ($trainers_page < $total_trainers_pages): ?>
                    <a href="<?php echo buildPaginationUrl($trainers_page + 1, 'trainers'); ?>">Next &raquo;</a>
                <?php else: ?>
                    <span class="disabled">Next &raquo;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Edit User Modal - UPDATED -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeEditModal()">&times;</span>
        <h2 style="margin-top:0;">✏️ Edit User</h2>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" id="edit_name" required minlength="2" maxlength="100"
                       placeholder="Enter full name">
            </div>
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" id="edit_username" required minlength="3" maxlength="20"
                       pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, and underscores allowed.">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" id="edit_phone" pattern="[0-9]{10,11}" maxlength="11"
                       title="Phone number must be 10-11 digits.">
            </div>
            
            <div class="form-group" id="edit_address_group">
                <label>Address</label>
                <textarea name="address" id="edit_address" placeholder="Enter full address"></textarea>
            </div>

            <div class="form-group" id="edit_specialty_group" style="display:none;">
                <label>Specialty (Trainers only)</label>
                <input type="text" name="specialty" id="edit_specialty" maxlength="100">
            </div>
            
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="edit_role" onchange="toggleEditRoleFields()">
                    <option value="member">Member</option>
                    <option value="trainer">Trainer</option>
                    <option value="management">Management</option>
                </select>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Change Password (optional)</label>
                <input type="password" name="password" id="edit_password" placeholder="Leave blank to keep current password" minlength="8">
            </div>

            <button type="submit" name="update_user" class="submit-btn">💾 Save Changes</button>
        </form>
    </div>
</div>

<!-- Add User Modal - UPDATED -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeAddModal()">&times;</span>
        <h2 style="margin-top:0;">➕ Add New User</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="add_role" required onchange="toggleAddRoleFields()">
                    <option value="member">Member</option>
                    <option value="trainer">Trainer</option>
                    <option value="management">Management</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required minlength="2" maxlength="100"
                       placeholder="Enter full name">
            </div>
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required minlength="3" maxlength="20"
                       pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, and underscores allowed.">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" pattern="[0-9]{10,11}" maxlength="11"
                       title="Phone number must be 10-11 digits.">
            </div>
            
            <div class="form-group" id="add_address_group">
                <label>Address</label>
                <textarea name="address"></textarea>
            </div>
            
            <div class="form-group" id="add_specialty_group" style="display:none;">
                <label>Specialty</label>
                <input type="text" name="specialty" maxlength="100">
            </div>
            
            <button type="submit" name="add_user" class="submit-btn">➕ Add User</button>
        </form>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" action="" style="display:none;">
    <input type="hidden" name="delete_user_id" id="delete_user_id">
</form>

<footer>
    <div class="footer-bottom">
        <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
    </div>
</footer>

<script>
    function openEditModal(user, type) {
        const modal = document.getElementById('editModal');
        modal.classList.add('active');

        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_name').value = user.name || user.username;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_phone').value = user.phone || '';
        document.getElementById('edit_address').value = user.address || '';
        document.getElementById('edit_specialty').value = user.specialty || '';
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_status').value = user.status;
        document.getElementById('edit_password').value = '';

        toggleEditRoleFields();
    }

    function toggleEditRoleFields() {
        const role = document.getElementById('edit_role').value;
        const specialtyGroup = document.getElementById('edit_specialty_group');
        const addressGroup = document.getElementById('edit_address_group');

        if (role === 'trainer') {
            specialtyGroup.style.display = 'block';
            addressGroup.style.display = 'none';
        } else {
            specialtyGroup.style.display = 'none';
            addressGroup.style.display = 'block';
        }
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }

    function openAddModal() {
        document.getElementById('addModal').classList.add('active');
        toggleAddRoleFields();
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.remove('active');
    }

    function toggleAddRoleFields() {
        const role = document.getElementById('add_role').value;
        const specialtyGroup = document.getElementById('add_specialty_group');
        const addressGroup = document.getElementById('add_address_group');
        
        if (role === 'trainer') {
            specialtyGroup.style.display = 'block';
            addressGroup.style.display = 'none';
        } else {
            specialtyGroup.style.display = 'none';
            addressGroup.style.display = 'block';
        }
    }

    function confirmDelete(userId, username) {
        if (confirm(`Are you sure you want to delete user "${username}"?\n\nThis will also delete all their bookings, payments, and memberships. This action cannot be undone!`)) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('deleteForm').submit();
        }
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const editModal = document.getElementById('editModal');
        const addModal = document.getElementById('addModal');
        
        if (event.target === editModal) {
            closeEditModal();
        }
        if (event.target === addModal) {
            closeAddModal();
        }
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeEditModal();
            closeAddModal();
        }
    });
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