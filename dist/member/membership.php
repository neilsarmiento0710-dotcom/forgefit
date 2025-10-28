<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../database/db.php';
require_once '../classes/Membership.php';
require_once '../classes/MembershipPlan.php';
require_once '../classes/Payment.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['username'];

$membership = new Membership();
$membershipPlan = new MembershipPlan();
$payment = new Payment();

// Step 1: Sync inactive memberships
$membership->syncInactiveMemberships($user_id);

// Step 2: Get current active membership
$current_membership = $membership->getUserActiveMembership($user_id);

// Step 3: Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_membership'])) {
    try {
        $plan_type = $_POST['plan_type'];
        $payment_method = $_POST['payment_method'];

        $payment_result = $payment->processPayment($user_id, $plan_type, $payment_method, $_FILES['payment_proof'] ?? null);

        // ✅ Auto-create or update membership when payment is paid
        if (!empty($payment_result['status']) && $payment_result['status'] === 'paid') {
            $membership->createOrUpdateMembership($user_id, $plan_type);
        }

        if ($payment_result['success']) {
            $_SESSION['success_message'] = $payment_result['message'];
            header("Location: membership.php");
            exit();
        } else {
            $error_message = $payment_result['message'];
        }
    } catch (Exception $e) {
        error_log("Payment Error: " . $e->getMessage());
        $error_message = "Unexpected error during payment: " . $e->getMessage();
    }
}

// Step 4: Fetch payment history
$payment_history = $payment->getUserPaymentHistory($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership & Payment - ForgeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/css/home.css" />
    <link rel="stylesheet" href="../assets/css/membership.css" />
    <link rel="stylesheet" href="../assets/css/sidebar.css" />
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
                    <li><a href="trainers.php">Trainers</a></li>
                    <li><a href="classes.php">Bookings</a></li>
                    <li><a href="membership.php">Membership</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
                </ul>
        </div>

    <main>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                ✓ <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
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
    <?php else: ?>
        <!-- Only show pricing grid if NO active membership -->
        <div class="pricing-grid">
            <?php foreach ($membershipPlan->getActivePlans() as $plan): ?>
                <div class="pricing-card <?= !empty($plan['is_featured']) ? 'featured' : '' ?>">
                    <h3><?= htmlspecialchars($plan['plan_name']); ?></h3>
                    <div class="price">₱<?= number_format($plan['price'], 2); ?><span>/mo</span></div>
                    <ul class="pricing-features">
                        <?php foreach (explode('|', $plan['features']) as $feature): ?>
                            <li>✓ <?= htmlspecialchars($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button 
                        class="select-plan-btn" 
                        onclick="openPaymentModal('<?= $plan['plan_type']; ?>', <?= $plan['price']; ?>, '<?= htmlspecialchars($plan['plan_name']); ?>')">
                        Select Plan
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

        <?php if (!empty($payment_history)): ?>
            <div class="payment-history">
                <h3>💳 Payment History</h3>
                <?php foreach ($payment_history as $payment_item): ?>
                    <div class="payment-item">
                        <div>
                            <strong><?= ucfirst($payment_item['plan_type']); ?> Plan</strong><br>
                            <small><?= date('M d, Y', strtotime($payment_item['payment_date'] ?? $payment_item['created_at'])); ?></small>
                        </div>
                        <div><strong>₱<?= number_format($payment_item['amount'], 2); ?></strong></div>
                        <div>
                            <span class="status-badge <?= $payment_item['status']; ?>">
                                <?= ucfirst(str_replace('_', ' ', $payment_item['status'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- PAYMENT MODAL -->
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
                    <select name="payment_method" id="payment_method" required onchange="togglePaymentProof(this.value)">
                        <option value="">Select Payment Method</option>
                        <option value="gcash">GCash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash (Pay at Counter)</option>
                    </select>
                </div>

                <div id="paymentInstructions" class="payment-instructions" style="display: none;">
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

                <div class="form-group" id="proofUploadGroup" style="display: none;">
                    <label>Upload Payment Proof (Screenshot/Receipt):</label>
                    <div class="file-upload" onclick="document.getElementById('payment_proof').click()">
                        <p>📎 Click to upload file</p>
                        <small style="color: #64748b;">Accepted: JPG, PNG, PDF (Max 5MB)</small>
                        <input type="file" id="payment_proof" name="payment_proof" accept="image/*,.pdf" onchange="displayFileName(this)">
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
        const paymentModal = document.getElementById('paymentModal');
        const proofUploadGroup = document.getElementById('proofUploadGroup');
        const paymentInstructions = document.getElementById('paymentInstructions');
        const paymentProofInput = document.getElementById('payment_proof');

        function openPaymentModal(planType, amount, planName) {
            document.getElementById('plan_type').value = planType;
            document.getElementById('plan_name').value = planName;
            document.getElementById('plan_amount').value = '₱' + amount.toLocaleString();
            document.getElementById('payment_method').value = '';
            document.getElementById('file-name').textContent = '';
            paymentProofInput.value = '';
            proofUploadGroup.style.display = 'none';
            paymentInstructions.style.display = 'none';
            paymentModal.classList.add('active');
        }

        function closePaymentModal() {
            paymentModal.classList.remove('active');
        }

        function togglePaymentProof(method) {
            if (method === 'gcash' || method === 'bank_transfer') {
                proofUploadGroup.style.display = 'block';
                paymentInstructions.style.display = 'block';
                paymentProofInput.setAttribute('required', 'required');
            } else {
                proofUploadGroup.style.display = 'none';
                paymentInstructions.style.display = 'none';
                paymentProofInput.removeAttribute('required');
                document.getElementById('file-name').textContent = '';
                paymentProofInput.value = '';
            }
        }

        function displayFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('file-name').textContent = '✓ ' + input.files[0].name;
            } else {
                document.getElementById('file-name').textContent = '';
            }
        }

        paymentModal.addEventListener('click', e => {
            if (e.target === paymentModal) closePaymentModal();
        });

        window.addEventListener('scroll', () => {
            const header = document.querySelector('header');
            header.style.background = window.scrollY > 50
                ? 'linear-gradient(135deg, rgba(15,23,42,0.98) 0%, rgba(30,41,59,0.98) 100%)'
                : 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)';
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
