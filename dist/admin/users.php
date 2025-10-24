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

// === DELETE USER ===
if (isset($_POST['delete_user_id'])) {
    $user_id = intval($_POST['delete_user_id']);
    
    // Delete related records first
    // Note: Add deleteByUserId methods to Booking, Payment, Membership models
    // For now, we'll use direct queries or you can add these methods
    
    if ($userModel->deleteUser($user_id)) {
        $_SESSION['success_message'] = "✅ User deleted successfully!";
    } else {
        $_SESSION['error_message'] = "❌ Failed to delete user.";
    }
    header("Location: users.php");
    exit();
}

// === UPDATE USER ===
if (isset($_POST['update_user'])) {
    $user_id = intval($_POST['user_id']);
    
    $data = [
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone']
    ];
    
    if ($userModel->updateUser($user_id, $data)) {
        $_SESSION['success_message'] = "✅ User updated successfully!";
    } else {
        $_SESSION['error_message'] = "❌ Failed to update user.";
    }
    header("Location: users.php");
    exit();
}

// === ADD NEW USER ===
if (isset($_POST['add_user'])) {
    $data = [
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'password' => $_POST['password'],
        'phone' => $_POST['phone'],
        'role' => $_POST['role']
    ];
    
    if ($userModel->createUser($data)) {
        $_SESSION['success_message'] = "✅ New user added successfully!";
    } else {
        $_SESSION['error_message'] = "❌ Failed to add user. Email might already exist.";
    }
    header("Location: users.php");
    exit();
}

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


?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Users - ForgeFit Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/home.css?v=4"/>
    <link rel="stylesheet" href="../assets/css/member_dashboard.css"/>
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
        .tabs { display: flex; gap: 10px; margin: 20px 0; }
        .tab {
            padding: 8px 16px;
            border-radius: 8px;
            background: #0f172a;
            color: #ffffff;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            border: 1px solid #0f172a;
        }
        .tab.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        tr:hover { background: #1e293b; }
        
        .action-btn {
            padding: 6px 12px;
            margin: 0 4px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: 0.3s;
        }
        .edit-btn {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        .edit-btn:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
        }
        .delete-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        .delete-btn:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }
        .add-user-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            margin: 20px 0;
        }
        .add-user-btn:hover {
            background: linear-gradient(135deg, #059669, #047857);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s ease;
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: #1e293b;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease;
            color: #e2e8f0;
        }
        .modal-close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            color: #94a3b8;
            cursor: pointer;
        }
        .modal-close:hover {
            color: #e2e8f0;
        }
        .form-group {
            margin: 15px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #cbd5e1;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #334155;
            border-radius: 8px;
            background: #0f172a;
            color: #e2e8f0;
            font-family: 'Montserrat', sans-serif;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .submit-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            width: 100%;
            margin-top: 10px;
        }
        .submit-btn:hover {
            background: linear-gradient(135deg, #059669, #047857);
        }
        .message-box {
            margin: 15px 0;
            padding: 12px 16px;
            border-radius: 10px;
            font-weight: 600;
        }
        .success {
            background: #dcfce7;
            color: #166534;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .status-active {
            background: #dcfce7;
            color: #166534;
        }
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
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
                <li><a href="bookings.php">Bookings</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="payments.php">Payments</a></li>
                <li><a href="member_rates.php">Membership Rates</a></li>
                <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
        </ul>
    </nav>
</header>

<main>
    <div class="dashboard-hero">
        <h1 class="dashboard-title">👥 Users Management</h1>
        <p style="color:#64748b;">Full admin access to manage all users, members, and trainers.</p>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="message-box success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php elseif (isset($_SESSION['error_message'])): ?>
        <div class="message-box error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <button class="add-user-btn" onclick="openAddUserModal()">➕ Add New User</button>

    <div class="tabs">
        <div class="tab active" data-filter="members">Members</div>
        <div class="tab" data-filter="trainers">Trainers</div>
    </div>

    <!-- Members Table -->
    <div class="table-container" id="members-table">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>ID</th>
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
                            <td><?php echo $member['id']; ?></td>
                            <td><?php echo htmlspecialchars($member['username']); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo htmlspecialchars($member['phone'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($member['address'] ?? '—'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $member['status']; ?>">
                                    <?php echo ucfirst($member['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                    $membership = $membershipModel->getUserLatestMembership($member['id']);
                                    if ($membership) {
                                        $status = $membership['status'];
                                        $end_date = $membership['end_date'];
                                        
                                        // Check if membership is expired
                                        if ($status === 'active' && strtotime($end_date) < time()) {
                                            echo '<span style="color:#f59e0b;">Expired</span>';
                                        } else {
                                            $color = ($status === 'active') ? '#16a34a' : '#f59e0b';
                                            echo '<span style="color:' . $color . ';">' . ucfirst($status) . '</span>';
                                        }
                                    } else {
                                        echo '<span style="color:#64748b;">None</span>';
                                    }
                                ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                            <td>
                                <button class="action-btn edit-btn" onclick='openEditModal(<?php echo json_encode($member); ?>, "member")'>Edit</button>
                                <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['username']); ?>')">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center;">No members found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if ($total_members_pages > 1): ?>
        <div class="pagination">
            <?php if ($members_page > 1): ?>
                <a href="?members_page=<?php echo $members_page - 1; ?>">&laquo; Prev</a>
            <?php else: ?>
                <span class="disabled">&laquo; Prev</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_members_pages; $i++): ?>
                <?php if ($i == $members_page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?members_page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($members_page < $total_members_pages): ?>
                <a href="?members_page=<?php echo $members_page + 1; ?>">Next &raquo;</a>
            <?php else: ?>
                <span class="disabled">Next &raquo;</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Trainers Table -->
    <div class="table-container" id="trainers-table" style="display:none;">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>ID</th>
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
                            <td><?php echo $trainer['id']; ?></td>
                            <td><?php echo htmlspecialchars($trainer['username']); ?></td>
                            <td><?php echo htmlspecialchars($trainer['email']); ?></td>
                            <td><?php echo htmlspecialchars($trainer['phone'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($trainer['specialty'] ?? '—'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $trainer['status']; ?>">
                                    <?php echo ucfirst($trainer['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($trainer['created_at'])); ?></td>
                            <td>
                                <button class="action-btn edit-btn" onclick='openEditModal(<?php echo json_encode($trainer); ?>, "trainer")'>Edit</button>
                                <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $trainer['id']; ?>, '<?php echo htmlspecialchars($trainer['username']); ?>')">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;">No trainers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if ($total_trainers_pages > 1): ?>
        <div class="pagination">
            <?php if ($trainers_page > 1): ?>
                <a href="?trainers_page=<?php echo $trainers_page - 1; ?>">&laquo; Prev</a>
            <?php else: ?>
                <span class="disabled">&laquo; Prev</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_trainers_pages; $i++): ?>
                <?php if ($i == $trainers_page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?trainers_page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($trainers_page < $total_trainers_pages): ?>
                <a href="?trainers_page=<?php echo $trainers_page + 1; ?>">Next &raquo;</a>
            <?php else: ?>
                <span class="disabled">Next &raquo;</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</main>

<!-- Edit User Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeEditModal()">&times;</span>
        <h2 style="margin-top:0;">✏️ Edit User</h2>
        <form method="POST" action="">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" id="edit_username" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" id="edit_phone">
            </div>
            
            <div class="form-group" id="address_group">
                <label>Address</label>
                <textarea name="address" id="edit_address"></textarea>
            </div>
            
            <div class="form-group" id="specialty_group" style="display:none;">
                <label>Specialty</label>
                <input type="text" name="specialty" id="edit_specialty">
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <button type="submit" name="update_user" class="submit-btn">Update User</button>
        </form>
    </div>
</div>

<!-- Add User Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeAddModal()">&times;</span>
        <h2 style="margin-top:0;">➕ Add New User</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="add_role" required onchange="toggleSpecialtyField()">
                    <option value="member">Member</option>
                    <option value="trainer">Trainer</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone">
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address"></textarea>
            </div>
            
            <div class="form-group" id="add_specialty_group" style="display:none;">
                <label>Specialty</label>
                <input type="text" name="specialty">
            </div>
            
            <button type="submit" name="add_user" class="submit-btn">Add User</button>
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
    // Switch between members and trainers tables
    const tabs = document.querySelectorAll('.tab');
    const membersTable = document.getElementById('members-table');
    const trainersTable = document.getElementById('trainers-table');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            if (tab.dataset.filter === 'trainers') {
                membersTable.style.display = 'none';
                trainersTable.style.display = 'block';
            } else {
                membersTable.style.display = 'block';
                trainersTable.style.display = 'none';
            }
        });
    });

    // Edit Modal Functions
    function openEditModal(user, type) {
        document.getElementById('editModal').classList.add('active');
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_phone').value = user.phone || '';
        document.getElementById('edit_status').value = user.status;
        
        if (type === 'trainer') {
            document.getElementById('address_group').style.display = 'none';
            document.getElementById('specialty_group').style.display = 'block';
            document.getElementById('edit_specialty').value = user.specialty || '';
            document.getElementById('edit_address').value = '';
        } else {
            document.getElementById('address_group').style.display = 'block';
            document.getElementById('specialty_group').style.display = 'none';
            document.getElementById('edit_address').value = user.address || '';
            document.getElementById('edit_specialty').value = '';
        }
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }

    // Add Modal Functions
    function openAddUserModal() {
        document.getElementById('addModal').classList.add('active');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.remove('active');
    }

    function toggleSpecialtyField() {
        const role = document.getElementById('add_role').value;
        const specialtyGroup = document.getElementById('add_specialty_group');
        
        if (role === 'trainer') {
            specialtyGroup.style.display = 'block';
        } else {
            specialtyGroup.style.display = 'none';
        }
    }

    // Delete Confirmation
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
</script>
</body>
</html>