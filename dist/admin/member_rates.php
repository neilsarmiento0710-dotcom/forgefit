<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../database/db.php';
require_once '../classes/MembershipPlan.php';

// Check if user is logged in as management
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'management') {
    header("Location: ../../member_login.php");
    exit();
}

$membershipPlanModel = new MembershipPlan();

// Create table and insert default plans if needed
$membershipPlanModel->createTable();

if ($membershipPlanModel->countPlans() == 0) {
    $membershipPlanModel->insertDefaultPlans();
}
// Handle Add/Update Plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    $plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
    
    $data = [
        'plan_type' => $_POST['plan_type'],
        'plan_name' => $_POST['plan_name'],
        'price' => floatval($_POST['price']),
        'duration_days' => intval($_POST['duration_days']),
        'features' => $_POST['features'],
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'display_order' => intval($_POST['display_order']),
        'status' => $_POST['status']
    ];
    
    if ($plan_id > 0) {
        // Update existing plan
        if ($membershipPlanModel->updatePlan($plan_id, $data)) {
            $_SESSION['success_message'] = "Plan updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update plan.";
        }
    } else {
        // Add new plan
        if ($membershipPlanModel->createPlan($data)) {
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
    
    if ($membershipPlanModel->deletePlan($plan_id)) {
        $_SESSION['success_message'] = "Plan deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete plan.";
    }
    
    header("Location: member_rates.php");
    exit();
}

// Fetch all membership plans
$plans = $membershipPlanModel->getAllPlans();
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
    <link rel="stylesheet" href="../assets/css/home.css"/> 
    <link rel="stylesheet" href="../assets/css/member_dashboard.css" id="main-style-link"/> 
    <link rel="stylesheet" href="../assets/css/member_rates.css"/> 
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
            <li><a href="payments.php">Payments</a></li>
            <li><a href="member_rates.php" class="active">Membership Rates</a></li>
            <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
        </ul>
        <div class="mobile-menu">
            <span></span><span></span><span></span>
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
        <h1 class="dashboard-title">Membership Plans Management</h1>
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

    <div style="text-align: right; margin: 20px 0;">
        <button class="action-btn edit-btn" onclick="openAddModal()" style="font-size: 1rem;">
            + Add New Plan
        </button>
    </div>

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
                <?php if (!empty($plans)): ?>
                    <?php foreach ($plans as $row): ?>
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
                    <?php endforeach; ?>
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