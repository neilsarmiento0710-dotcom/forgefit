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

// Create membership_plans table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS membership_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_type VARCHAR(50) NOT NULL UNIQUE,
    plan_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL DEFAULT 30,
    features TEXT,
    is_featured TINYINT(1) DEFAULT 0,
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

// Check if table is empty and insert default plans
$check_sql = "SELECT COUNT(*) as count FROM membership_plans";
$check_result = $conn->query($check_sql);
$count = $check_result->fetch_assoc()['count'];

if ($count == 0) {
    $default_plans = [
        ['basic', 'Basic Plan', 600, 30, 'Gym Access|Cardio Equipment|Locker Room|Free WiFi', 0, 1],
        ['premium', 'Premium Plan', 1000, 30, 'Everything in Basic|Group Classes|Sauna Access|Nutrition Guidance|Guest Passes (2/month)', 1, 2],
        ['elite', 'Elite Plan', 1250, 30, 'Everything in Premium|Personal Training (4 sessions)|Priority Booking|Massage Therapy|Unlimited Guest Passes|Exclusive Events', 0, 3]
    ];
    
    foreach ($default_plans as $plan) {
        $insert_sql = "INSERT INTO membership_plans (plan_type, plan_name, price, duration_days, features, is_featured, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ssdisii", $plan[0], $plan[1], $plan[2], $plan[3], $plan[4], $plan[5], $plan[6]);
        $stmt->execute();
    }
}

// Handle Add/Update Plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    $plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
    $plan_type = $_POST['plan_type'];
    $plan_name = $_POST['plan_name'];
    $price = floatval($_POST['price']);
    $duration_days = intval($_POST['duration_days']);
    $features = $_POST['features'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $display_order = intval($_POST['display_order']);
    $status = $_POST['status'];
    
    if ($plan_id > 0) {
        // Update existing plan
        $update_sql = "UPDATE membership_plans SET plan_type = ?, plan_name = ?, price = ?, duration_days = ?, features = ?, is_featured = ?, display_order = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssdisiisi", $plan_type, $plan_name, $price, $duration_days, $features, $is_featured, $display_order, $status, $plan_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Plan updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update plan.";
        }
    } else {
        // Add new plan
        $insert_sql = "INSERT INTO membership_plans (plan_type, plan_name, price, duration_days, features, is_featured, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ssdisiis", $plan_type, $plan_name, $price, $duration_days, $features, $is_featured, $display_order, $status);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Plan added successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to add plan.";
        }
    }
    
    header("Location: member_rates.php");
    exit();
}

// Handle Delete Plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_plan'])) {
    $plan_id = intval($_POST['plan_id']);
    
    $delete_sql = "DELETE FROM membership_plans WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $plan_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Plan deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete plan.";
    }
    $stmt->close();
    header("Location: member_rates.php");
    exit();
}

// Fetch all membership plans
$sql = "SELECT * FROM membership_plans ORDER BY display_order ASC, id ASC";
$result = $conn->query($sql);
?>

<!doctype html>
<html lang="en">
<head>
    <title>Membership Rates - ForgeFit Admin</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="ForgeFit Admin Membership Rates" />
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
        .status-active {
            background-color: #22c55e;
            color: #fff;
        }
        .status-inactive {
            background-color: #64748b;
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
        .add-btn {
            background-color: #22c55e;
            color: white;
            padding: 10px 20px;
            font-size: 1rem;
            margin: 20px 0;
        }
        .add-btn:hover {
            background-color: #16a34a;
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
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            overflow-y: auto;
        }
        .modal-content {
            background-color: #1e293b;
            margin: 3% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
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
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
            border: 1px solid #334155;
            font-size: 0.95rem;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
        }
        .form-control:hover {
            background-color: #0f172a !important;
            border-color: #475569;
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            font-family: 'Montserrat', sans-serif;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
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
        .featured-badge {
            background-color: #facc15;
            color: #000;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 5px;
        }
        .features-list {
            text-align: left;
            font-size: 0.85rem;
            line-height: 1.6;
        }
        select.form-control option {
            background-color: #0f172a;
            color: #e2e8f0;
        }
        .help-text {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-top: 5px;
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
            <li><a href="member_rates.php" class="active">Membership Rates</a></li>
            <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
        </ul>
        <div class="mobile-menu">
            <span></span><span></span><span></span>
        </div>
    </nav>
</header>

<main>
    <div class="dashboard-hero">
        <h1 class="dashboard-title">💎 Membership Plans Management</h1>
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

    <button class="action-btn add-btn" onclick="openAddModal()">+ Add New Plan</button>

    <div class="earnings-grid">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Plan Type</th>
                    <th>Plan Name</th>
                    <th>Price</th>
                    <th>Duration</th>
                    <th>Features</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['display_order']; ?></td>
                            <td>
                                <?php echo ucfirst($row['plan_type']); ?>
                                <?php if ($row['is_featured']): ?>
                                    <span class="featured-badge">FEATURED</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['plan_name']); ?></td>
                            <td>₱<?php echo number_format($row['price'], 2); ?></td>
                            <td><?php echo $row['duration_days']; ?> days</td>
                            <td>
                                <div class="features-list">
                                    <?php 
                                    $features = explode('|', $row['features']);
                                    echo count($features) . ' features';
                                    ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="action-btn edit-btn" onclick='openEditModal(<?php echo json_encode($row); ?>)'>
                                    Edit
                                </button>
                                <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['plan_name']); ?>')">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="color:#94a3b8;">No membership plans found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Add/Edit Plan Modal -->
<div id="planModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closePlanModal()">&times;</span>
        <h2 class="modal-header" id="modalTitle">Add New Plan</h2>
        <form method="POST" action="">
            <input type="hidden" name="plan_id" id="plan_id">
            
            <div class="form-group">
                <label for="plan_type">Plan Type (Unique ID):</label>
                <input type="text" name="plan_type" id="plan_type" class="form-control" required>
                <p class="help-text">Use lowercase, no spaces (e.g., basic, premium, elite)</p>
            </div>
            
            <div class="form-group">
                <label for="plan_name">Plan Name:</label>
                <input type="text" name="plan_name" id="plan_name" class="form-control" required>
                <p class="help-text">Display name for the plan (e.g., Basic Plan, Premium Plan)</p>
            </div>
            
            <div class="form-group">
                <label for="price">Price (₱):</label>
                <input type="number" name="price" id="price" class="form-control" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="duration_days">Duration (Days):</label>
                <input type="number" name="duration_days" id="duration_days" class="form-control" value="30" required>
            </div>
            
            <div class="form-group">
                <label for="features">Features (One per line):</label>
                <textarea name="features" id="features" class="form-control" required></textarea>
                <p class="help-text">Enter features separated by pipe symbol (|) or new lines</p>
            </div>
            
            <div class="form-group">
                <label for="display_order">Display Order:</label>
                <input type="number" name="display_order" id="display_order" class="form-control" value="1" required>
                <p class="help-text">Lower numbers appear first</p>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" name="is_featured" id="is_featured" value="1">
                <label for="is_featured" style="margin: 0;">Mark as Featured Plan</label>
            </div>
            
            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" id="status" class="form-control" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="action-btn" onclick="closePlanModal()" style="background-color: #64748b;">Cancel</button>
                <button type="submit" name="save_plan" class="action-btn add-btn">Save Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h2 class="modal-header">Confirm Deletion</h2>
        <p id="deletePlanName"></p>
        <p style="color: #f87171;">Are you sure you want to delete this plan? This action cannot be undone.</p>
        <form method="POST" action="">
            <input type="hidden" name="plan_id" id="delete_plan_id">
            <div class="modal-footer">
                <button type="button" class="action-btn" onclick="closeDeleteModal()" style="background-color: #64748b;">Cancel</button>
                <button type="submit" name="delete_plan" class="action-btn delete-btn">Delete Plan</button>
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
        document.getElementById('modalTitle').textContent = 'Add New Plan';
        document.getElementById('plan_id').value = '';
        document.getElementById('plan_type').value = '';
        document.getElementById('plan_name').value = '';
        document.getElementById('price').value = '';
        document.getElementById('duration_days').value = '30';
        document.getElementById('features').value = '';
        document.getElementById('display_order').value = '1';
        document.getElementById('is_featured').checked = false;
        document.getElementById('status').value = 'active';
        document.getElementById('plan_type').readOnly = false;
        document.getElementById('planModal').style.display = 'block';
    }

    function openEditModal(plan) {
        document.getElementById('modalTitle').textContent = 'Edit Plan';
        document.getElementById('plan_id').value = plan.id;
        document.getElementById('plan_type').value = plan.plan_type;
        document.getElementById('plan_name').value = plan.plan_name;
        document.getElementById('price').value = plan.price;
        document.getElementById('duration_days').value = plan.duration_days;
        document.getElementById('features').value = plan.features.replace(/\|/g, '\n');
        document.getElementById('display_order').value = plan.display_order;
        document.getElementById('is_featured').checked = plan.is_featured == 1;
        document.getElementById('status').value = plan.status;
        document.getElementById('plan_type').readOnly = true;
        document.getElementById('planModal').style.display = 'block';
    }

    function closePlanModal() {
        document.getElementById('planModal').style.display = 'none';
    }

    function confirmDelete(id, name) {
        document.getElementById('delete_plan_id').value = id;
        document.getElementById('deletePlanName').textContent = 'Delete "' + name + '"?';
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const planModal = document.getElementById('planModal');
        const deleteModal = document.getElementById('deleteModal');
        
        if (event.target == planModal) {
            closePlanModal();
        }
        if (event.target == deleteModal) {
            closeDeleteModal();
        }
    }

    // Convert textarea features to pipe-separated on form submit
    document.querySelector('form').addEventListener('submit', function(e) {
        const featuresField = document.getElementById('features');
        const features = featuresField.value.split('\n').map(f => f.trim()).filter(f => f).join('|');
        featuresField.value = features;
    });
</script>
</body>
</html>