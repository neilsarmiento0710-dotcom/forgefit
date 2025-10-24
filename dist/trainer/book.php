<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../database/db.php';
require_once '../classes/TrainerBooking.php';

// Check trainer login
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'trainer') {
    header("Location: ../../login.php");
    exit();
}

$trainer_id = $_SESSION['user']['id'];
$trainer_name = $_SESSION['user']['username'];

$trainerBooking = new TrainerBooking($trainer_id, $trainer_name);

// Handle booking form
if (isset($_POST['submit'])) {
    $client_id = (int)$_POST['client_id'];
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $notes = $_POST['notes'] ?? '';

    $result = $trainerBooking->createBooking($client_id, $booking_date, $booking_time, $notes);

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error_message = $result['message'];
    }
}

$members_result = $trainerBooking->getAllMembers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Client Session - ForgeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/home.css?v=4" />
    <link rel="stylesheet" href="../assets/css/booking.css" />
    <link rel="stylesheet" href="../assets/css/booking_t.css" />
</head>
<body>
<header>
    <nav>
        <div style="display: flex; align-items: center; gap: 15px;">
            <div class="logo">ForgeFit</div>
            <div class="logo-two">Trainer</div>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="book.php" class="active">Book Client</a></li>
            <li><a href="clients.php">My Clients</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
        </ul>
        <div class="mobile-menu"><span></span><span></span><span></span></div>
    </nav>
</header>

<main>
    <div class="booking-hero">
        <h1>Book Client Session</h1>
        <p>Schedule a training session with your client</p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="error-message"><?= htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" class="booking-form">
        <div class="form-group">
            <label>Select Client</label>
            <div class="client-search">
                <input type="text" id="searchClient" placeholder="🔍 Search by name or email..." onkeyup="filterClients()">
            </div>
            <div id="selectedClientDisplay" class="selected-client" style="display:none;">
                <h4 id="selectedClientName"></h4>
                <p id="selectedClientEmail"></p>
            </div>
            <div class="client-list" id="clientList">
                <?php if ($members_result->num_rows > 0): ?>
                    <?php while ($member = $members_result->fetch_assoc()): ?>
                        <div class="client-item"
                             data-id="<?= $member['id']; ?>"
                             data-name="<?= htmlspecialchars($member['username']); ?>"
                             data-email="<?= htmlspecialchars($member['email'] ?? ''); ?>"
                             onclick="selectClient(this)">
                            <strong><?= htmlspecialchars($member['username']); ?></strong>
                            <small>
                                <?= htmlspecialchars($member['email'] ?? 'No email'); ?>
                                <?php if (!empty($member['phone'])): ?>
                                    • <?= htmlspecialchars($member['phone']); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="client-item" style="text-align:center; color:#999;">No members found</div>
                <?php endif; ?>
            </div>
            <input type="hidden" id="client_id" name="client_id" required>
        </div>

        <div class="form-group">
            <label for="booking_date">Session Date</label>
            <input type="date" id="booking_date" name="booking_date" min="<?= date('Y-m-d'); ?>" required>
        </div>

        <div class="form-group">
            <label for="booking_time">Session Time</label>
            <input type="time" id="booking_time" name="booking_time" required>
        </div>

        <div class="form-group">
            <label for="notes">Session Notes (Optional)</label>
            <textarea id="notes" name="notes" placeholder="Training focus, client goals, special requirements..."></textarea>
        </div>

        <button type="submit" name="submit" class="submit-btn" id="submitBtn" disabled>Confirm Booking</button>
    </form>
</main>

<footer>
    <div class="footer-bottom"><p>&copy; 2025 ForgeFit Gym. All rights reserved.</p></div>
</footer>

<script>
    let selectedClientId = null;
    function selectClient(element) {
        document.querySelectorAll('.client-item').forEach(i => i.classList.remove('selected'));
        element.classList.add('selected');
        const clientId = element.dataset.id;
        const clientName = element.dataset.name;
        const clientEmail = element.dataset.email;
        document.getElementById('client_id').value = clientId;
        selectedClientId = clientId;
        document.getElementById('selectedClientName').textContent = clientName;
        document.getElementById('selectedClientEmail').textContent = clientEmail;
        document.getElementById('selectedClientDisplay').style.display = 'block';
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('booking_date').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    function filterClients() {
        const term = document.getElementById('searchClient').value.toLowerCase();
        document.querySelectorAll('.client-item').forEach(item => {
            const name = item.dataset.name.toLowerCase();
            const email = item.dataset.email.toLowerCase();
            item.style.display = name.includes(term) || email.includes(term) ? 'block' : 'none';
        });
    }
    window.addEventListener('scroll', () => {
        const header = document.querySelector('header');
        header.style.background = window.scrollY > 50
            ? 'linear-gradient(135deg, rgba(15, 23, 42, 0.98) 0%, rgba(30, 41, 59, 0.98) 100%)'
            : 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)';
    });
    document.querySelector('.booking-form').addEventListener('submit', e => {
        if (!selectedClientId) {
            e.preventDefault();
            alert('Please select a client first.');
        }
    });
</script>
</body>
</html>
