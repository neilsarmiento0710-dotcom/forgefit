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

// Verify connection exists
if (!isset($conn) || $conn === null) {
    $conn = getDBConnection();
}

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../../login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['username'];

// Sync memberships with payment status
$update_inactive_sql = "
    UPDATE memberships m
    JOIN payments p ON m.payment_id = p.id
    SET m.status = 'inactive'
    WHERE p.status = 'pending' AND m.status != 'inactive' AND m.user_id = ?";
$update_stmt = $conn->prepare($update_inactive_sql);
$update_stmt->bind_param("i", $user_id);
$update_stmt->execute();

// Fetch user's current membership
$membership_sql = "SELECT * FROM memberships WHERE user_id = ? AND status = 'active' ORDER BY end_date DESC LIMIT 1";
$membership_stmt = $conn->prepare($membership_sql);
$membership_stmt->bind_param("i", $user_id);
$membership_stmt->execute();
$membership_result = $membership_stmt->get_result();
$current_membership = $membership_result->fetch_assoc();

// Handle payment submission
if (isset($_POST['purchase_membership'])) {
    $plan_type = $_POST['plan_type'];
    $payment_method = $_POST['payment_method'];
    
    // Plan prices
    $plans = [
        'basic' => ['price' => 600, 'name' => 'Basic Plan', 'duration' => 30],
        'premium' => ['price' => 1000, 'name' => 'Premium Plan', 'duration' => 30],
        'elite' => ['price' => 1250, 'name' => 'Elite Plan', 'duration' => 30]
    ];
    
    if (!isset($plans[$plan_type])) {
        $error_message = "Invalid plan selected.";
    } else {
        $plan = $plans[$plan_type];
        $amount = $plan['price'];
        
        // Handle file upload (payment proof)
        $payment_proof = null;
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/payments/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $new_filename = 'payment_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_path)) {
                $payment_proof = $new_filename;
            }
        }
        
        // Calculate dates
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+' . $plan['duration'] . ' days'));
        
        // Insert payment record
        $payment_sql = "INSERT INTO payments (user_id, amount, payment_method, plan_type, payment_proof, status, created_at) 
                       VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        $payment_stmt = $conn->prepare($payment_sql);
        $payment_stmt->bind_param("idsss", $user_id, $amount, $payment_method, $plan_type, $payment_proof);
        
        if ($payment_stmt->execute()) {
            $payment_id = $payment_stmt->insert_id;
            
            // Create membership record (pending approval)
            $membership_insert_sql = "INSERT INTO memberships (user_id, plan_type, start_date, end_date, payment_id, status, created_at) 
                                     VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
            $membership_insert_stmt = $conn->prepare($membership_insert_sql);
            $membership_insert_stmt->bind_param("isssi", $user_id, $plan_type, $start_date, $end_date, $payment_id);
            
            if ($membership_insert_stmt->execute()) {
                $_SESSION['success_message'] = "Payment submitted successfully! Your membership will be activated once payment is verified.";
                header("Location: membership.php");
                exit();
            }
        } else {
            $error_message = "Payment submission failed. Please try again.";
        }
    }
}

// Fetch payment history
$payments_sql = "SELECT p.*, m.plan_type, m.start_date, m.end_date 
                 FROM payments p 
                 LEFT JOIN memberships m ON p.id = m.payment_id 
                 WHERE p.user_id = ? 
                 ORDER BY p.created_at DESC 
                 LIMIT 10";
$payments_stmt = $conn->prepare($payments_sql);
$payments_stmt->bind_param("i", $user_id);
$payments_stmt->execute();
$payments_result = $payments_stmt->get_result();

?>  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Membership & Payment - ForgeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/css/home.css?v=4" />
    <link rel="stylesheet" href="../assets/css/membership.css" />
    <style>
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
                <div class="logo-two">Member</div>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="trainers.php">Trainers</a></li>
                <li><a href="classes.php">Bookings</a></li>
                <li><a href="membership.php">Membership</a></li>
                <li><a href="profile.php">Profile</a></li>
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
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                ✓ <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="membership-hero">
            <h1>Membership Plans & Status</h1>
            <p>Choose the perfect plan for your fitness journey</p>
        </div>

        <?php if ($current_membership): ?>
            <div class="current-membership">
                <h3>🎉 Your Current Membership</h3>
                <div class="membership-details">
                    <div class="membership-detail">
                        <strong>Plan:</strong><br>
                        <?php echo ucfirst($current_membership['plan_type']); ?>
                    </div>
                    <div class="membership-detail">
                        <strong>Start Date:</strong><br>
                        <?php echo date('M d, Y', strtotime($current_membership['start_date'])); ?>
                    </div>
                    <div class="membership-detail">
                        <strong>End Date:</strong><br>
                        <?php echo date('M d, Y', strtotime($current_membership['end_date'])); ?>
                    </div>
                    <div class="membership-detail">
                        <strong>Status:</strong><br>
                        <?php echo ucfirst($current_membership['status']); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="pricing-grid">
            <!-- Basic Plan -->
            <div class="pricing-card">
                <h3>Basic</h3>
                <div class="price">₱600<span style="font-size: 1rem;">/mo</span></div>
                <ul class="pricing-features">
                    <li>✓ Gym Access</li>
                    <li>✓ Cardio Equipment</li>
                    <li>✓ Locker Room</li>
                    <li>✓ Free WiFi</li>
                </ul>
                <button class="select-plan-btn" onclick="openPaymentModal('basic', 600, 'Basic Plan')">
                    Select Plan
                </button>
            </div>

            <!-- Premium Plan -->
            <div class="pricing-card featured">
                <h3>Premium</h3>
                <div class="price">₱1000<span style="font-size: 1rem;">/mo</span></div>
                <ul class="pricing-features">
                    <li>✓ Everything in Basic</li>
                    <li>✓ Group Classes</li>
                    <li>✓ Sauna Access</li>
                    <li>✓ Nutrition Guidance</li>
                    <li>✓ Guest Passes (2/month)</li>
                </ul>
                <button class="select-plan-btn" onclick="openPaymentModal('premium', 1000, 'Premium Plan')">
                    Select Plan
                </button>
            </div>

            <!-- Elite Plan -->
            <div class="pricing-card">
                <h3>Elite</h3>
                <div class="price">₱1250<span style="font-size: 1rem;">/mo</span></div>
                <ul class="pricing-features">
                    <li>✓ Everything in Premium</li>
                    <li>✓ Personal Training (4 sessions)</li>
                    <li>✓ Priority Booking</li>
                    <li>✓ Massage Therapy</li>
                    <li>✓ Unlimited Guest Passes</li>
                    <li>✓ Exclusive Events</li>
                </ul>
                <button class="select-plan-btn" onclick="openPaymentModal('elite', 1250, 'Elite Plan')">
                    Select Plan
                </button>
            </div>
        </div>

        <!-- Payment History -->
        <?php if ($payments_result->num_rows > 0): ?>
            <div class="payment-history">
                <h3>💳 Payment History</h3>
                <?php while ($payment = $payments_result->fetch_assoc()): ?>
                    <div class="payment-item">
                        <div>
                            <strong><?php echo ucfirst($payment['plan_type']); ?> Plan</strong><br>
                            <small><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></small>
                        </div>
                        <div>
                            <strong>₱<?php echo number_format($payment['amount'], 2); ?></strong>
                        </div>
                        <div>
                            <span class="status-badge <?php echo $payment['status']; ?>">
                                <?php echo ucfirst($payment['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Complete Payment</h2>
                <button class="close-modal" onclick="closePaymentModal()">&times;</button>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="plan_type" id="plan_type">
                
                <div class="form-group">
                    <label>Selected Plan:</label>
                    <input type="text" id="plan_name" readonly style="background: #f8fafc;">
                </div>

                <div class="form-group">
                    <label>Amount:</label>
                    <input type="text" id="plan_amount" readonly style="background: #f8fafc;">
                </div>

                <div class="form-group">
                    <label>Payment Method:</label>
                    <select name="payment_method" required>
                        <option value="">Select Payment Method</option>
                        <option value="gcash">GCash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash (Pay at Counter)</option>
                    </select>
                </div>

                <div class="payment-instructions">
                    <h4>📱 GCash Payment</h4>
                    <div class="bank-details">
                        <p><strong>GCash Number:</strong> 0946 540 3747</p>
                        <p><strong>Name:</strong> ForgeFit Gym</p>
                    </div>

                    <h4 style="margin-top: 20px;">🏦 Bank Transfer</h4>
                    <div class="bank-details">
                        <p><strong>Bank:</strong> BDO</p>
                        <p><strong>Account Number:</strong> 1234-5678-9012</p>
                        <p><strong>Account Name:</strong> ForgeFit Gym</p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Upload Payment Proof (Screenshot/Receipt):</label>
                    <div class="file-upload" onclick="document.getElementById('payment_proof').click()">
                        <p>📎 Click to upload file</p>
                        <small style="color: #64748b;">Accepted: JPG, PNG, PDF (Max 5MB)</small>
                        <input type="file" id="payment_proof" name="payment_proof" accept="image/*,.pdf" required onchange="displayFileName(this)">
                    </div>
                    <p id="file-name" style="margin-top: 10px; color: #10b981; font-weight: 600;"></p>
                </div>

                <button type="submit" name="purchase_membership" class="select-plan-btn">
                    Submit Payment
                </button>
            </form>
        </div>
    </div>

    <footer>
        <div class="footer-bottom">
            <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function openPaymentModal(planType, amount, planName) {
            document.getElementById('plan_type').value = planType;
            document.getElementById('plan_name').value = planName;
            document.getElementById('plan_amount').value = '₱' + amount.toLocaleString();
            document.getElementById('paymentModal').classList.add('active');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
        }

        function displayFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('file-name').textContent = '✓ ' + input.files[0].name;
            }
        }

        // Close modal when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 50) {
                header.style.background = 'linear-gradient(135deg, rgba(15, 23, 42, 0.98) 0%, rgba(30, 41, 59, 0.98) 100%)';
            } else {
                header.style.background = 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)';
            }
        });
    </script>
</body>
</html>