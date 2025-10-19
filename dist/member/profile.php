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

// Fetch complete user profile
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $emergency_contact = $_POST['emergency_contact'];
    $emergency_phone = $_POST['emergency_phone'];
    
    $update_sql = "UPDATE users SET 
                   username = ?, 
                   email = ?, 
                   phone = ?, 
                   address = ?, 
                   emergency_contact = ?, 
                   emergency_phone = ? 
                   WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssssi", $username, $email, $phone, $address, $emergency_contact, $emergency_phone, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
        $_SESSION['user']['username'] = $username;
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
    
    // Verify current password
    if (password_verify($current_password, $user['password_hash'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $password_sql = "UPDATE users SET password_hash = ? WHERE id = ?";
                $password_stmt = $conn->prepare($password_sql);
                $password_stmt->bind_param("si", $new_password_hash, $user_id);
                
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
        $upload_dir = '../admin/upload/profiles/'; // Added trailing slash
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['profile_picture']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old profile picture if exists
                if ($user['profile_picture'] && file_exists($upload_dir . $user['profile_picture'])) {
                    unlink($upload_dir . $user['profile_picture']);
                }
                
                $picture_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                $picture_stmt = $conn->prepare($picture_sql);
                $picture_stmt->bind_param("si", $new_filename, $user_id);
                
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

// Fetch user statistics
$stats_sql = "SELECT 
                (SELECT COUNT(*) 
                 FROM bookings 
                 WHERE user_id = ?) AS total_bookings,
                (SELECT COUNT(*) 
                 FROM memberships 
                 WHERE user_id = ? AND status = 'active') AS active_memberships";

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("ii", $user_id, $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Access the results
$total_bookings = $stats['total_bookings'];
$active_memberships = $stats['active_memberships'];


// Refresh user data after any update
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
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
    <!-- FIXED: Load home.css first, then profile.css will override -->
    <link rel="stylesheet" href="../assets/css/home.css" />
    <link rel="stylesheet" href="../assets/css/profile.css?v=2" />

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
<body class="profile-page">
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
            <div class="error-message">⚠ <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-picture-container">
                <?php if (!empty($user['profile_picture'])): ?>
                    <img src="../admin/upload/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                         alt="Profile Picture" class="profile-picture">
                <?php else: ?>
                    <div class="profile-picture-placeholder">
                        <?php echo strtoupper(substr($user['username'] ?? 'U', 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <button class="change-picture-btn" onclick="openPictureModal()">📷</button>
            </div>

            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['username'] ?? 'User'); ?></h2>
                <p><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                <p class="member-role" style="font-weight: 600; margin-top: 5px;">
                    <?php echo ucfirst($user['role'] ?? 'member'); ?> Member
                </p>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="number"><?php echo $stats['total_bookings'] ?? 0; ?></div>
                        <div class="label">Total Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?php echo $stats['active_memberships'] ?? 0; ?></div>
                        <div class="label">Active Plans</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-sections">
            <!-- PERSONAL INFO SECTION -->
            <div class="profile-section">
                <div class="section-header">
                    <h3>👤 Personal Information</h3>
                    <button class="edit-btn" onclick="toggleEdit('personal')">Edit</button>
                </div>

                <div id="personal-display">
                    <div class="info-display">
                        <div class="info-item">
                            <label>Username</label>
                            <p><?php echo htmlspecialchars($user['username'] ?? ''); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Phone</label>
                            <p><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Not set'; ?></p>
                        </div>
                        <div class="info-item">
                            <label>Address</label>
                            <p><?php echo $user['address'] ? htmlspecialchars($user['address']) : 'Not set'; ?></p>
                        </div>
                    </div>
                </div>

                <form method="POST" id="personal-edit" class="hidden">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
                        <button type="button" class="btn-secondary" onclick="toggleEdit('personal')">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- EMERGENCY CONTACT -->
            <div class="profile-section">
                <div class="section-header">
                    <h3>🚨 Emergency Contact</h3>
                    <button class="edit-btn" onclick="toggleEdit('emergency')">Edit</button>
                </div>

                <div id="emergency-display">
                    <div class="info-display">
                        <div class="info-item">
                            <label>Contact Name</label>
                            <p><?php echo $user['emergency_contact'] ? htmlspecialchars($user['emergency_contact']) : 'Not set'; ?></p>
                        </div>
                        <div class="info-item">
                            <label>Contact Phone</label>
                            <p><?php echo $user['emergency_phone'] ? htmlspecialchars($user['emergency_phone']) : 'Not set'; ?></p>
                        </div>
                    </div>
                </div>

                <form method="POST" id="emergency-edit" class="hidden">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    <input type="hidden" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Emergency Contact Name</label>
                            <input type="text" name="emergency_contact" value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact Phone</label>
                            <input type="tel" name="emergency_phone" value="<?php echo htmlspecialchars($user['emergency_phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
                        <button type="button" class="btn-secondary" onclick="toggleEdit('emergency')">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- SECURITY SETTINGS -->
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