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
    die("Error: Database connection failed.");
}

// Check if user is logged in and is a trainer
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../../login.php");
    exit();
}

if ($_SESSION['user']['role'] !== 'trainer') {
    die("Error: Access denied. Trainers only.");
}

$trainer_id = $_SESSION['user']['id'];
$trainer_name = $_SESSION['user']['username'];

// Fetch all members (users with role 'member')
$members_sql = "SELECT id, username, email, phone FROM users WHERE role = 'member' ORDER BY username ASC";
$members_result = $conn->query($members_sql);

// Handle booking form submission
if (isset($_POST['submit'])) {
    $client_id = (int)$_POST['client_id'];
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Fetch client details
    $client_sql = "SELECT username, email FROM users WHERE id = ? AND role = 'member'";
    $client_stmt = $conn->prepare($client_sql);
    $client_stmt->bind_param("i", $client_id);
    $client_stmt->execute();
    $client_result = $client_stmt->get_result();
    
    if ($client_result->num_rows === 0) {
        $error_message = "Error: Invalid client selected.";
    } else {
        $client = $client_result->fetch_assoc();
        $member_name = $client['username'];
        $member_email = $client['email'];
        
        // ✅ CHECK FOR CONFLICTS - See if trainer is already booked at this time
        $conflict_sql = "SELECT id FROM bookings 
                         WHERE trainer_id = ? 
                         AND booking_date = ? 
                         AND booking_time = ? 
                         AND status != 'cancelled'";
        
        $conflict_stmt = $conn->prepare($conflict_sql);
        $conflict_stmt->bind_param("iss", $trainer_id, $booking_date, $booking_time);
        $conflict_stmt->execute();
        $conflict_result = $conflict_stmt->get_result();
        
        if ($conflict_result->num_rows > 0) {
            // Trainer is already booked at this time
            $error_message = "⚠️ You are already booked on " . 
                            date('F j, Y', strtotime($booking_date)) . " at " . 
                            date('g:i A', strtotime($booking_time)) . ". Please choose a different time.";
        } else {
            // Check if client is already booked at this time
            $client_conflict_sql = "SELECT id FROM bookings 
                                   WHERE user_id = ? 
                                   AND booking_date = ? 
                                   AND booking_time = ? 
                                   AND status != 'cancelled'";
            
            $client_conflict_stmt = $conn->prepare($client_conflict_sql);
            $client_conflict_stmt->bind_param("iss", $client_id, $booking_date, $booking_time);
            $client_conflict_stmt->execute();
            $client_conflict_result = $client_conflict_stmt->get_result();
            
            if ($client_conflict_result->num_rows > 0) {
                $error_message = "⚠️ " . htmlspecialchars($member_name) . " is already booked on " . 
                                date('F j, Y', strtotime($booking_date)) . " at " . 
                                date('g:i A', strtotime($booking_time)) . ". Please choose a different time.";
            } else {
                // No conflict - proceed with booking
                $insert_sql = "INSERT INTO bookings 
                               (user_id, trainer_id, booking_date, booking_time, member_name, member_email, status, notes, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, 'booked', ?, NOW())";
                
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iisssss", 
                    $client_id, 
                    $trainer_id, 
                    $booking_date, 
                    $booking_time, 
                    $member_name, 
                    $member_email, 
                    $notes
                );
                
                if ($insert_stmt->execute()) {
                    $booking_id = $insert_stmt->insert_id;
                    $_SESSION['success_message'] = "✅ Session booked successfully! Training with " . 
                                                  htmlspecialchars($member_name) . 
                                                  " scheduled for " . date('M d, Y', strtotime($booking_date)) . 
                                                  " at " . date('g:i A', strtotime($booking_time)) . ".";
                    header("Location: trainer_dashboard.php");
                    exit();
                } else {
                    $error_message = "Booking failed: " . $insert_stmt->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Book Client Session - ForgeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/home.css?v=4" />
    <link rel="stylesheet" href="../assets/css/booking.css?v=4" />
    <style>
        .client-search {
            margin-bottom: 15px;
        }
        
        .client-search input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .client-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
        }
        
        .client-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .client-item:hover {
            background: #f8f9fa;
        }
        
        .client-item.selected {
            background: #e3f2fd;
            border-left: 4px solid #0077b6;
        }
        
        .client-item strong {
            display: block;
            color: #001d3d;
            margin-bottom: 4px;
        }
        
        .client-item small {
            color: #666;
            font-size: 13px;
        }
        
        .selected-client {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #0077b6;
        }
        
        .selected-client h4 {
            margin: 0 0 5px 0;
            color: #001d3d;
        }
        
        .selected-client p {
            margin: 0;
            color: #666;
            font-size: 14px;
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
                <div class="logo-two">Trainer</div>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="book.php" class="active">Book Client</a></li>
                <li><a href="clients.php">My Clients</a></li>
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
        <div class="booking-hero">
            <h1>Book Client Session</h1>
            <p>Schedule a training session with your client</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="booking-form">
            <div class="form-group">
                <label>Select Client</label>
                <div class="client-search">
                    <input type="text" id="searchClient" placeholder="🔍 Search by name or email..." onkeyup="filterClients()">
                </div>
                
                <div id="selectedClientDisplay" class="selected-client" style="display: none;">
                    <h4 id="selectedClientName"></h4>
                    <p id="selectedClientEmail"></p>
                </div>
                
                <div class="client-list" id="clientList">
                    <?php if ($members_result->num_rows > 0): ?>
                        <?php while ($member = $members_result->fetch_assoc()): ?>
                            <div class="client-item" 
                                 data-id="<?php echo $member['id']; ?>"
                                 data-name="<?php echo htmlspecialchars($member['username']); ?>"
                                 data-email="<?php echo htmlspecialchars($member['email'] ?? ''); ?>"
                                 onclick="selectClient(this)">
                                <strong><?php echo htmlspecialchars($member['username']); ?></strong>
                                <small>
                                    <?php echo htmlspecialchars($member['email'] ?? 'No email'); ?>
                                    <?php if (!empty($member['phone'])): ?>
                                        • <?php echo htmlspecialchars($member['phone']); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="client-item" style="text-align: center; color: #999;">
                            No members found
                        </div>
                    <?php endif; ?>
                </div>
                
                <input type="hidden" id="client_id" name="client_id" required>
            </div>

            <div class="form-group">
                <label for="booking_date">Session Date</label>
                <input type="date" id="booking_date" name="booking_date" 
                       min="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label for="booking_time">Session Time</label>
                <input type="time" id="booking_time" name="booking_time" required>
            </div>

            <div class="form-group">
                <label for="notes">Session Notes (Optional)</label>
                <textarea id="notes" name="notes" 
                          placeholder="Training focus, client goals, special requirements..."></textarea>
            </div>

            <button type="submit" name="submit" class="submit-btn" id="submitBtn" disabled>
                Confirm Booking
            </button>
        </form>
    </main>

    <footer>
        <div class="footer-bottom">
            <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
        </div>
    </footer>

    <script>
        let selectedClientId = null;

        function selectClient(element) {
            // Remove previous selection
            document.querySelectorAll('.client-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Add selection to clicked item
            element.classList.add('selected');
            
            // Get client data
            const clientId = element.getAttribute('data-id');
            const clientName = element.getAttribute('data-name');
            const clientEmail = element.getAttribute('data-email');
            
            // Update hidden input
            document.getElementById('client_id').value = clientId;
            selectedClientId = clientId;
            
            // Show selected client display
            document.getElementById('selectedClientName').textContent = clientName;
            document.getElementById('selectedClientEmail').textContent = clientEmail;
            document.getElementById('selectedClientDisplay').style.display = 'block';
            
            // Enable submit button
            document.getElementById('submitBtn').disabled = false;
            
            // Scroll to form
            document.getElementById('booking_date').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function filterClients() {
            const searchTerm = document.getElementById('searchClient').value.toLowerCase();
            const clientItems = document.querySelectorAll('.client-item');
            
            clientItems.forEach(item => {
                const name = item.getAttribute('data-name')?.toLowerCase() || '';
                const email = item.getAttribute('data-email')?.toLowerCase() || '';
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 50) {
                header.style.background = 'linear-gradient(135deg, rgba(15, 23, 42, 0.98) 0%, rgba(30, 41, 59, 0.98) 100%)';
            } else {
                header.style.background = 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)';
            }
        });

        // Form validation
        document.querySelector('.booking-form').addEventListener('submit', function(e) {
            if (!selectedClientId) {
                e.preventDefault();
                alert('Please select a client first.');
            }
        });
    </script>
</body>
</html>