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

// Check if trainer is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../../_trainerlogin.php");
    exit();
}

// Get logged-in trainer ID
$trainer_id = $_SESSION['user']['id'];

// Fetch trainer profile
$trainer_sql = "SELECT * FROM trainers WHERE id = ?";
$trainer_stmt = $conn->prepare($trainer_sql);
$trainer_stmt->bind_param("i", $trainer_id);
$trainer_stmt->execute();
$trainer_result = $trainer_stmt->get_result();
$trainer = $trainer_result->fetch_assoc();

if (!$trainer) {
    die("Error: Trainer not found in database.");
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    // Note: The 'name' from the form corresponds to the 'name' column in the DB.
    $name = $_POST['name']; 
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $emergency_contact = $_POST['emergency_contact'];
    $emergency_phone = $_POST['emergency_phone'];

    $update_sql = "UPDATE trainers SET 
                       name = ?, 
                       email = ?, 
                       phone = ?, 
                       address = ?, 
                       emergency_contact = ?, 
                       emergency_phone = ?
                       WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssssi", $name, $email, $phone, $address, $emergency_contact, $emergency_phone, $trainer_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
        // Update session name if it changed
        $_SESSION['user']['name'] = $name;
        header("Location: profile.php");
        exit();
    } else {
        $error_message = "Failed to update profile.";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $trainer['password_hash'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $password_sql = "UPDATE trainers SET password_hash = ? WHERE id = ?";
                $password_stmt = $conn->prepare($password_sql);
                $password_stmt->bind_param("si", $new_password_hash, $trainer_id);
                
                if ($password_stmt->execute()) {
                    $_SESSION['success_message'] = "Password changed successfully!";
                    header("Location: profile.php");
                    exit();
                } else {
                    $error_message = "Failed to change password.";
                }
            } else {
                $error_message = "Password must be at least 6 characters long.";
            }
        } else {
            $error_message = "New passwords do not match.";
        }
    } else {
        $error_message = "Current password is incorrect.";
    }
}

// Handle profile picture upload
if (isset($_POST['update_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../admin/upload/profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['profile_picture']['type'];

        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = 'trainer_' . $trainer_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old picture
                if ($trainer['profile_picture'] && file_exists($upload_dir . $trainer['profile_picture'])) {
                    unlink($upload_dir . $trainer['profile_picture']);
                }

                $picture_sql = "UPDATE trainers SET profile_picture = ? WHERE id = ?";
                $picture_stmt = $conn->prepare($picture_sql);
                $picture_stmt->bind_param("si", $new_filename, $trainer_id);

                if ($picture_stmt->execute()) {
                    $_SESSION['success_message'] = "Profile picture updated successfully!";
                    header("Location: profile.php");
                    exit();
                }
            } else {
                $error_message = "Failed to upload profile picture.";
            }
        } else {
            $error_message = "Only JPG, JPEG, and PNG files are allowed.";
        }
    } else {
        $error_message = "Please select a file to upload.";
    }
}

// Fetch trainer statistics.
// FATAL ERROR SOURCE: The original error comes from this query. It assumes a 'trainer_id' column
// exists in the 'bookings', 'memberships', and 'payments' tables. If the error persists after
// applying the other fixes, you must verify these column names in your database schema.
$stats_sql = "SELECT 
                  (SELECT COUNT(*) FROM bookings WHERE trainer_id = ? AND status = 'booked') as total_bookings,
                  (SELECT COUNT(*) FROM memberships WHERE trainer_id = ? AND status = 'active') as active_memberships,
                  (SELECT COUNT(*) FROM payments WHERE trainer_id = ? AND status = 'approved') as total_payments";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("iii", $trainer_id, $trainer_id, $trainer_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Refresh trainer data in case of updates
$trainer_stmt->execute();
$trainer_result = $trainer_stmt->get_result();
$trainer = $trainer_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>My Profile - ForgeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/css/home.css" />
    <link rel="stylesheet" href="../assets/css/profile.css?v=2" />
</head>
<body class="profile-page">
    <header>
        <nav>
            <div class="logo">ForgeFit</div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="clients.php">My Clients</a></li>
                <li><a href="schedule.php">Schedule</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
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
                <div class="success-icon">✓</div>
                <span><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message">⚠ <?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-picture-container">
                <?php if ($trainer['profile_picture']): ?>
                    <img src="../admin/upload/profiles/<?php echo htmlspecialchars($trainer['profile_picture']); ?>" 
                         alt="Profile Picture" class="profile-picture">
                <?php else: ?>
                    <div class="profile-picture-placeholder">
                        <?php echo strtoupper(substr($trainer['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <button class="change-picture-btn" onclick="openPictureModal()">📷</button>
            </div>

            <div class="profile-info">
                <h2><?php echo htmlspecialchars($trainer['name']); ?></h2>
                <p><?php echo htmlspecialchars($trainer['email']); ?></p>
                <p class="member-role" style="font-weight: 600; margin-top: 5px;">
                    <?php echo isset($trainer['role']) ? ucfirst($trainer['role']) : 'Trainer'; ?>
                </p>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="number"><?php echo $stats['total_bookings']; ?></div>
                        <div class="label">Total Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?php echo $stats['active_memberships']; ?></div>
                        <div class="label">Active Members</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?php echo $stats['total_payments']; ?></div>
                        <div class="label">Payments</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-sections">
            <div class="profile-section">
                <div class="section-header">
                    <h3>👤 Personal Information</h3>
                    <button class="edit-btn" onclick="toggleEdit('personal')">Edit</button>
                </div>

                <div id="personal-display">
                    <div class="info-display">
                        <div class="info-item">
                            <label>Name</label>
                            <p><?php echo htmlspecialchars($trainer['name']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($trainer['email']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Phone</label>
                            <p><?php echo $trainer['phone'] ? htmlspecialchars($trainer['phone'] ?? '') : 'Not set'; ?></p>
                        </div>
                        <div class="info-item">
                            <label>Address</label>
                            <p><?php echo htmlspecialchars($trainer['address'] ?? '') ?: 'Not set'; ?></p>
                        </div>
                    </div>
                </div>

                <form method="POST" id="personal-edit" class="hidden">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($trainer['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($trainer['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($trainer['phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($trainer['address']); ?>">
                        </div>
                        <input type="hidden" name="emergency_contact" value="<?php echo htmlspecialchars($trainer['emergency_contact']); ?>">
                        <input type="hidden" name="emergency_phone" value="<?php echo htmlspecialchars($trainer['emergency_phone']); ?>">
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
                        <button type="button" class="btn-secondary" onclick="toggleEdit('personal')">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="profile-section">
                <div class="section-header">
                    <h3>🚨 Emergency Contact</h3>
                    <button class="edit-btn" onclick="toggleEdit('emergency')">Edit</button>
                </div>

                <div id="emergency-display">
                    <div class="info-display">
                        <div class="info-item">
                            <label>Contact Name</label>
                            <p><?php echo $trainer['emergency_contact'] ? htmlspecialchars($trainer['emergency_contact'] ?? '') : 'Not set'; ?></p>
                        </div>
                        <div class="info-item">
                            <label>Contact Phone</label>
                            <p><?php echo $trainer['emergency_phone'] ? htmlspecialchars($trainer['emergency_phone'] ?? '') : 'Not set'; ?></p>
                        </div>
                    </div>
                </div>

                <form method="POST" id="emergency-edit" class="hidden">
                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($trainer['name']); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($trainer['email']); ?>">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($trainer['phone']); ?>">
                    <input type="hidden" name="address" value="<?php echo htmlspecialchars($trainer['address']); ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Emergency Contact Name</label>
                            <input type="text" name="emergency_contact" value="<?php echo htmlspecialchars($trainer['emergency_contact']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact Phone</label>
                            <input type="tel" name="emergency_phone" value="<?php echo htmlspecialchars($trainer['emergency_phone']); ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
                        <button type="button" class="btn-secondary" onclick="toggleEdit('emergency')">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="profile-section">
                <div class="section-header">
                    <h3>🔒 Security Settings</h3>
                    <button class="edit-btn" onclick="toggleEdit('password')">Change Password</button>
                </div>

                <div id="password-display">
                    <p style="color: #0077b6;">Your password is secure and encrypted.</p>
                </div>

                <form method="POST" id="password-edit" class="hidden">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="change_password" class="btn-primary">Update Password</button>
                        <button type="button" class="btn-secondary" onclick="toggleEdit('password')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <div id="pictureModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Profile Picture</h3>
                <button class="close-modal" onclick="closePictureModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Select New Picture</label>
                    <input type="file" name="profile_picture" accept="image/*" required style="padding: 10px;">
                </div>
                <button type="submit" name="update_picture" class="btn-primary" style="width: 100%; margin-top: 20px;">
                    Upload Picture
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
        function toggleEdit(section) {
            const display = document.getElementById(section + '-display');
            const edit = document.getElementById(section + '-edit');
            
            if (display.classList.contains('hidden')) {
                display.classList.remove('hidden');
                edit.classList.add('hidden');
            } else {
                display.classList.add('hidden');
                edit.classList.remove('hidden');
            }
        }

        function openPictureModal() {
            document.getElementById('pictureModal').classList.add('active');
        }

        function closePictureModal() {
            document.getElementById('pictureModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('pictureModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePictureModal();
            }
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 50) {
                header.style.background = 'linear-gradient(135deg, rgba(0, 51, 102, 0.98) 0%, rgba(0, 29, 61, 0.98) 100%)';
            } else {
                header.style.background = 'linear-gradient(135deg, #003366 0%, #001d3d 100%)';
            }
        });
    </script>
</body>
</html>
