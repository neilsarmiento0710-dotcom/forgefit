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

// Ensure DB connection
if (!isset($conn) || $conn === null) {
    $conn = getDBConnection();
}

// Check if admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'management') {
    header("Location: ../../member_login.php");
    exit();
}

// === Approve Payment ===
if (isset($_POST['approve_id'])) {
    $payment_id = intval($_POST['approve_id']);
    
    // Update payment status to 'paid'
    $update_sql = "UPDATE payments SET status = 'paid' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $payment_id);

    if ($stmt->execute()) {
        // Update the corresponding membership status to 'active'
        $update_membership_sql = "UPDATE memberships m 
                                  JOIN payments p ON m.id = p.membership_id 
                                  SET m.status = 'active' 
                                  WHERE p.id = ?";
        $update_membership_stmt = $conn->prepare($update_membership_sql);
        $update_membership_stmt->bind_param("i", $payment_id);
        $update_membership_stmt->execute();
        
        $_SESSION['success_message'] = "✅ Payment #{$payment_id} approved and membership activated!";
    } else {
        $_SESSION['error_message'] = "❌ Failed to approve payment.";
    }
    header("Location: payments.php");
    exit();
}

// === Fetch Payments ===
$sql = "SELECT p.id, u.username, u.email, p.amount, p.payment_method, p.status, p.payment_proof, p.created_at
        FROM payments p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<!doctype html>
<html lang="en">
<head>
    <title>Admin - Payments | ForgeFit</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/css/home.css?v=4"/>
    <link rel="stylesheet" href="../assets/css/member_dashboard.css" id="main-style-link"/>
    <style>
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .payments-table th, .payments-table td {
            padding: 14px 16px;
            text-align: left;
        }
        .payments-table th {
            background: #0f172a;
            color: white;
        }
        .payments-table tr:nth-child(even) {
            background: #f8fafc;
        }
        .approve-btn {
            background: linear-gradient(135deg, #16a34a, #22c55e);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .approve-btn:hover {
            background: linear-gradient(135deg, #15803d, #16a34a);
        }
        .status-paid {
            color: #16a34a;
            font-weight: 700;
        }
        .status-pending {
            color: #f59e0b;
            font-weight: 700;
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
    </style>
</head>

<body>
<header>
    <nav>
        <div class="logo">ForgeFit</div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="bookings.php">Bookings</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="payments.php" class="active">Payments</a></li>
            <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
        </ul>
        <div class="mobile-menu">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>
</header>

<main>
    <div class="dashboard-hero">
        <h1 class="dashboard-title">💳 Payments Management</h1>
        <div class="breadcrumb">
            <a href="dashboard.php">Home</a> / <span>Payments</span>
        </div>
    </div>

    <div class="dashboard-content">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message-box success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php elseif (isset($_SESSION['error_message'])): ?>
            <div class="message-box error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

        <table class="payments-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Member</th>
                    <th>Email</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Proof of Payment</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                            <td><?php echo ucfirst($row['payment_method']); ?></td>
                            <td>
                                <?php if (!empty($row['payment_proof'])): ?>
                                    <a href="../uploads/payments/<?php echo htmlspecialchars($row['payment_proof']); ?>" 
                                    target="_blank" 
                                    class="view-proof-btn">View Proof</a>
                                <?php else: ?><span class="no-proof">No proof uploaded</span><?php endif; ?>
                            </td>
                            <td class="<?php echo ($row['status'] == 'paid') ? 'status-paid' : 'status-pending'; ?>">
                                <?php echo strtoupper($row['status']); ?>
                            </td>
                            <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                            <td>
                                <?php if ($row['status'] != 'paid'): ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="approve_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="approve-btn">Approve</button>
                                    </form>
                                <?php else: ?>
                                    ✅
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center; padding:20px;">No payment records found.</td></tr>
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
    // Mobile menu toggle
    document.querySelector('.mobile-menu')?.addEventListener('click', () => {
        document.querySelector('.nav-links').classList.toggle('active');
    });
</script>
</body>
</html>
