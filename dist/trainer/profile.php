<?php
session_start();
require_once '../classes/Profile.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../login.php");
    exit;
}

$profile = new Profile($_SESSION['user']['id']);
$user = $profile->getUser();

// ✅ Update profile info
if (isset($_POST['update_profile'])) {
    $result = $profile->updateProfile($_POST);
    $_SESSION['success_message'] = $result ? "Profile updated successfully!" : "Failed to update profile.";
    header("Location: profile.php");
    exit;
}

// ✅ Change password
if (isset($_POST['change_password'])) {
    $result = $profile->changePassword($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password']);
    if ($result === true) {
        $_SESSION['success_message'] = "Password changed successfully!";
        header("Location: profile.php");
        exit;
    } else {
        $error_message = $result;
    }
}

// ✅ Update picture
if (isset($_POST['update_picture'])) {
    $result = $profile->updatePicture($_FILES['profile_picture']);
    if ($result === true) {
        $_SESSION['success_message'] = "Profile picture updated!";
        header("Location: profile.php");
        exit;
    } else {
        $error_message = $result;
    }
}

// ✅ Trainer stats (if trainer)
if ($user['role'] === 'trainer') {
    $stats = $profile->getTrainerStats();
}
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
    <link rel="stylesheet" href="../assets/css/profile.css" />
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
    </style>    
</head>
<body class="profile-page">
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
                        <li><a href="book.php" class="active">Book Client</a></li>
                        <li><a href="clients.php">My Clients</a></li>
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
                    </ul>
            </div>

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
                <?php if ($user['profile_picture']): ?>
                    <img src="../upload/profile/"<?php echo htmlspecialchars($user['profile_picture'] ?? ''); ?>" 
                        alt="Profile Picture" class="profile-picture">
                <?php else: ?>
                    <div class="profile-picture-placeholder">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <button class="change-picture-btn" onclick="openPictureModal()">📷</button>
            </div>

            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <p class="member-role" style="font-weight: 600; margin-top: 5px;">
                    <?php echo isset($user['role']) ? ucfirst($user['role']) : 'Member'; ?>
                </p>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="number"><?php echo $stats['total_bookings']; ?></div>
                        <div class="label">Total Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?php echo $stats['total_clients']; ?></div>
                        <div class="label">Clients</div>
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
                            <label>Full Name</label>
                            <p><?php echo htmlspecialchars($user['name'] ?? ''); ?></p>
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
                            <label>Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
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
                            <label for="emergency_phone">Emergency Contact Phone</label>
                            <input 
                                id="emergency_phone"
                                name="emergency_phone"
                                type="tel"
                                class="form-control"
                                placeholder="09XXXXXXXXX"
                                pattern="[0-9]{11}"
                                maxlength="11"
                                title="Enter a valid 11-digit number (e.g., 09123456789)"
                                required
                                value="<?= htmlspecialchars($user['emergency_phone'] ?? '') ?>">
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

        document.getElementById('pictureModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePictureModal();
            }
        });

        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 50) {
                header.style.background = 'linear-gradient(135deg, rgba(0, 51, 102, 0.98) 0%, rgba(0, 29, 61, 0.98) 100%)';
            } else {
                header.style.background = 'linear-gradient(135deg, #003366 0%, #001d3d 100%)';
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