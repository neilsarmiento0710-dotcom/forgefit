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

// Fetch all bookings with user + trainer info
$sql = "
    SELECT 
        b.id, 
        b.booking_date, 
        b.booking_time, 
        b.status, 
        u.username AS member_name, 
        u.email AS member_email,
        t.name AS trainer_name,
        t.specialty
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN trainers t ON b.trainer_id = t.id
    ORDER BY b.booking_date DESC, b.booking_time DESC
";
$result = $conn->query($sql);
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
    </style>
</head>

<body>
<header>
    <nav>
        <div class="logo">ForgeFit</div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="bookings.php" class="active">Bookings</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="payments.php">Payments</a></li>
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
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="color:#94a3b8;">No bookings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<footer>
    <div class="footer-bottom">
        <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
    </div>
</footer>

<script src="../assets/js/plugins/feather.min.js"></script>
<script src="../assets/js/icon/custom-icon.js"></script>
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
</script>
</body>
</html>
