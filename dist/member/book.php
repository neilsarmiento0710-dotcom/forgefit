<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once '../database/db.php';
require_once '../classes/Booking.php';

/**
 * BookingPageHandler - Handles booking page logic
 */
class BookingPageHandler {
    private $db;
    private $bookingModel;
    private $userId;
    private $userName;
    private $userEmail;
    private $trainerId;
    private $trainer;
    private $errorMessage;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->bookingModel = new Booking();
        
        $this->validateSession();
        $this->loadTrainer();
    }
    
    /**
     * Validate user session
     */
    private function validateSession() {
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
            header("Location: ../../login.php");
            exit();
        }
        
        $this->userId = $_SESSION['user']['id'];
        $this->userName = $_SESSION['user']['username'];
        $this->userEmail = $_SESSION['user']['email'] ?? '';
    }
    
    /**
     * Load trainer information from database
     */
    private function loadTrainer() {
        if (!isset($_GET['trainer_id']) || empty($_GET['trainer_id'])) {
            die("Error: No trainer selected.");
        }
        
        $this->trainerId = (int)$_GET['trainer_id'];
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? AND role = 'trainer'");
        $stmt->bind_param("i", $this->trainerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            die("Error: Trainer not found.");
        }
        
        $this->trainer = $result->fetch_assoc();
    }
    
    /**
     * Process booking form submission
     */
    public function processBooking() {
        if (!isset($_POST['submit'])) {
            return;
        }
        
        $bookingDate = $_POST['booking_date'];
        $bookingTime = $_POST['booking_time'];
        $memberName = $_POST['member_name'];
        $memberEmail = $_POST['member_email'];
        $notes = $_POST['notes'] ?? '';
        
        // VALIDATION 1: Check user's booking constraints (2 per day, 1-hour gap)
        $validationErrors = $this->bookingModel->validateBooking($this->userId, $bookingDate, $bookingTime);
        
        if (!empty($validationErrors)) {
            $this->errorMessage = "⚠️ " . implode('<br>⚠️ ', $validationErrors);
            return;
        }
        
        // VALIDATION 2: Check for trainer conflicts
        $conflict = $this->bookingModel->hasConflict($this->trainerId, $bookingDate, $bookingTime);
        if ($conflict) {
            $this->errorMessage = "⚠️ Sorry! " . htmlspecialchars($this->trainer['name']) . 
                " is booked from " . $conflict['start_time'] . 
                " to " . $conflict['end_time'] . 
                " on " . date('F j, Y', strtotime($bookingDate)) . 
                ". Please choose a different time.";
            return;
        }
        
        // Create booking
        $data = [
            'user_id' => $this->userId,
            'trainer_id' => $this->trainerId,
            'booking_date' => $bookingDate,
            'booking_time' => $bookingTime,
            'member_name' => $memberName,
            'member_email' => $memberEmail,
            'notes' => $notes,
            'status' => 'booked'
        ];
        
        $newBookingId = $this->bookingModel->createBooking($data);
        
        if ($newBookingId) {
            $_SESSION['success_message'] = "✅ Booking successful! Your session with " . 
                htmlspecialchars($this->trainer['username']) . " is scheduled for " . 
                date('M d, Y', strtotime($bookingDate)) . " at " . 
                date('g:i A', strtotime($bookingTime)) . ".";
            header("Location: dashboard.php");
            exit();
        } else {
            $this->errorMessage = "❌ Booking failed. Please try again.";
        }
    }
    
    // Getters for view
    public function getTrainer() {
        return $this->trainer;
    }
    
    public function getUserName() {
        return $this->userName;
    }
    
    public function getUserEmail() {
        return $this->userEmail;
    }
    
    public function getErrorMessage() {
        return $this->errorMessage;
    }
    
    public function hasError() {
        return !empty($this->errorMessage);
    }
    
    public function getMinDate() {
        return date('Y-m-d');
    }
}

// Initialize handler and process booking
$pageHandler = new BookingPageHandler();
$pageHandler->processBooking();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Book Training Session - ForgeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/home.css" />
    <link rel="stylesheet" href="../assets/css/booking.css" />
    <link rel="stylesheet" href="../assets/css/sidebar.css" />
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
        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 15px 20px;
            margin: 20px auto;
            max-width: 800px;
            border-radius: 8px;
            line-height: 1.6;
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
        <div class="booking-hero">
            <h1>Book Training Session</h1>
            <p>Schedule your personalized training experience</p>
        </div>

        <?php if ($pageHandler->hasError()): ?>
            <div class="error-message"><?php echo $pageHandler->getErrorMessage(); ?></div>
        <?php endif; ?>

        <div class="trainer-info">
            <h3><?php echo htmlspecialchars($pageHandler->getTrainer()['username']); ?></h3>
            <p><strong>Specialty:</strong> <?php echo htmlspecialchars($pageHandler->getTrainer()['specialty']); ?></p>
        </div>

        <form method="POST" action="" class="booking-form">
            <div class="form-group">
                <label for="member_name">Your Name</label>
                <input type="text" id="member_name" name="member_name"
                    value="<?php echo htmlspecialchars($pageHandler->getUserName()); ?>" required>
            </div>

            <div class="form-group">
                <label for="member_email">Your Email</label>
                <input type="email" id="member_email" name="member_email"
                    value="<?php echo htmlspecialchars($pageHandler->getUserEmail()); ?>" required>
            </div>

            <div class="form-group">
                <label for="booking_date">Preferred Date</label>
                <input type="date" id="booking_date" name="booking_date"
                    min="<?php echo $pageHandler->getMinDate(); ?>" required>
            </div>

            <div class="form-group">
                <label for="booking_time">Preferred Time</label>
                <input type="time" id="booking_time" name="booking_time" required>
            </div>

            <div class="form-group">
                <label for="notes">Additional Notes (Optional)</label>
                <textarea id="notes" name="notes" maxlength="100" placeholder="Any specific goals or requirements?"></textarea>
            </div>

            <button type="submit" name="submit" class="submit-btn">Confirm Booking</button>
        </form>
    </main>

    <footer>
        <div class="footer-bottom">
            <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
        </div>
    </footer>

    <script>
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 50) {
                header.style.background = 'linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(30, 41, 59, 0.98))';
            } else {
                header.style.background = 'linear-gradient(135deg, #0f172a, #1e293b)';
            }
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