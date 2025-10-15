<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../database/db.php';
if (!isset($conn)) {
    $conn = getDBConnection();
}

// Check if admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'management') {
    header("Location: ../../member_login.php");
    exit();
}

// Fetch members (from users table)
$members_sql = "SELECT id, username, email, phone, 'member' AS role FROM users ORDER BY id DESC";
$members_result = $conn->query($members_sql);

// Fetch trainers (from trainers table)
$trainers_sql = "SELECT id, name AS name, email, phone, specialty, 'trainer' AS role FROM trainers ORDER BY id DESC";
$trainers_result = $conn->query($trainers_sql);
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
            padding: 8px 16px; border-radius: 8px;
            background: #f1f5f9; color: #334155; cursor: pointer;
            font-weight: 600; transition: 0.3s;
        }
        .tab.active { background: #0f172a; color: #fff; }
        table {
            width: 100%; border-collapse: collapse; background: #fff;
            border-radius: 12px; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        th, td { padding: 14px 16px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 0.95rem; }
        th { background: #f1f5f9; font-weight: 600; color: #1e293b; }
        tr:hover { background: #1e293b; }
        .badge { padding: 5px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; }
        .badge.member { background: #dbeafe; color: #1e40af; }
        .badge.trainer { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
<header>
    <nav>
        <div class="logo">ForgeFit</div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="bookings.php">Bookings</a></li>
            <li><a href="users.php" class="active">Users</a></li>
            <li><a href="payments.php">Payments</a></li>
            <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
        </ul>
    </nav>
</header>

<main>
    <div class="dashboard-hero">
        <h1 class="dashboard-title">👥 Users Management</h1>
        <p style="color:#64748b;">View and manage all registered members and trainers.</p>
    </div>

    <div class="tabs">
        <div class="tab active" data-filter="members">Members</div>
        <div class="tab" data-filter="trainers">Trainers</div>
    </div>

    <!-- Members Table -->
    <div class="table-container" id="members-table">
            <table class="modern-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Membership Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($members_result && $members_result->num_rows > 0): ?>
                    <?php while ($member = $members_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['username']); ?></td>
                            <td><?php echo htmlspecialchars($member['name'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo htmlspecialchars($member['contact'] ?? '—'); ?></td>
                            <td>
                                <?php
                                    $membership_sql = "SELECT status FROM memberships WHERE user_id = ? ORDER BY end_date DESC LIMIT 1";
                                    $stmt = $conn->prepare($membership_sql);
                                    $stmt->bind_param("i", $member['id']);
                                    $stmt->execute();
                                    $membership_result = $stmt->get_result();
                                    if ($membership_result->num_rows > 0) {
                                        $membership = $membership_result->fetch_assoc();
                                        echo ucfirst($membership['status']);
                                    } else {
                                        echo "No membership";
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;">No members found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Trainers Table -->
    <div class="table-container" id="trainers-table" style="display:none;">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Specialty</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($trainers_result && $trainers_result->num_rows > 0): ?>
                    <?php while ($trainer = $trainers_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trainer['name']); ?></td>
                            <td><?php echo htmlspecialchars($trainer['email']); ?></td>
                            <td><?php echo htmlspecialchars($trainer['contact'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($trainer['specialty'] ?? '—'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;">No trainers found.</td></tr>
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
</script>
</body>
</html>
