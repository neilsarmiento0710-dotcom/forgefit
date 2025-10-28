<?php
session_start();
require_once '../database/db.php';
require_once '../classes/Trainer.php';
require_once '../classes/Membership.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

// Initialize models
$trainerModel = new Trainer($conn);
$membershipModel = new Membership($conn);

// Fetch data
$trainers = $trainerModel->getActiveTrainers();
$has_active_membership = $membershipModel->hasActiveMembership($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="ForgeFit Gym - Our Expert Trainers" />
    <meta name="keywords" content="gym, fitness, training, workout, health, trainers" />
    <meta name="author" content="Sniper 2025" />
    <title>ForgeFit - Our Trainers</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    <link rel="stylesheet" href="../assets/css/home.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/sidebar.css" />
    <style>
        main {
            margin-top: 100px;
            padding: 40px 20px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            min-height: calc(100vh - 300px);
        }
        .trainers-hero {
            background: linear-gradient(135deg, #003366 0%, #001d3d 100%);
            padding: 60px 40px;
            border-radius: 20px;
            color: white;
            margin-bottom: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 51, 102, 0.4);
        }
        .trainers-hero h1 {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .trainers-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        .trainer-list {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        .trainer-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }
        .trainer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        .trainer-card h3 {
            font-size: 1.8rem;
            color: #0f172a;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .trainer-card p {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 20px;
        }
        .trainer-card .cta-btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .trainer-card .cta-btn:hover {
            background: linear-gradient(135deg, #ee5a6f, #ff6b6b);
            transform: scale(1.05);
        }
        .no-trainers {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
            font-size: 1.2rem;
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
                <span class="success-icon">✓</span>
                <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <span class="error-icon">✕</span>
                <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="trainers-hero">
            <h1>BOOK A TRAINER</h1>
            <p>Choose the perfect trainer for you!</p>
        </div>

        <section>
            <div class="trainer-list">
                <?php if (!empty($trainers)): ?>
                    <?php foreach ($trainers as $trainer): ?>
                        <div class="trainer-card">
                            <div class="feature-icon">💪</div>
                            <h3><?php echo htmlspecialchars($trainer['name']); ?></h3>
                            <p><strong>Specialty:</strong> <?php echo htmlspecialchars($trainer['specialty'] ?? 'N/A'); ?></p>
                            <?php if ($has_active_membership): ?>
                                <a href="book.php?trainer_id=<?php echo $trainer['id']; ?>" class="cta-btn">Book Session</a>
                            <?php else: ?>
                                <a href="membership.php" class="cta-btn" style="background: linear-gradient(135deg, #94a3b8, #64748b);">
                                    Get Membership First
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-trainers">
                        <p>No trainers available at the moment. Please check back later!</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-bottom">
            <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
        </div>
    </footer>
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
