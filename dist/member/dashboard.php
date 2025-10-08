<?php
session_start();
if (isset($_POST['submit'])) {
} else {
?>
<!doctype html>
<html lang="en">
<head>
    <?php 
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ?>
    
    <title>Member Dashboard - ForgeFit</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="ForgeFit Member Dashboard" />
    <meta name="author" content="Sniper 2025" />
    
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/css/member_dashboard.css" />
    <link rel="stylesheet" href="../assets/css/home.css"/>
    
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            width: 100%;
        }
        
        header {
            width: 100%;
        }
        
        footer {
            margin-top: auto;
        }
    </style>
</head>

<body class="dark-mode">
    <header>
        <nav>
            <div class="logo">ForgeFit</div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="classes.php">Classes</a></li>
                <li><a href="membership.php">Membership</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
            <div class="mobile-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main>
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Welcome Back, Member!</h1>
            <div class="breadcrumb">
                <a href="#">Home</a>
                <span class="breadcrumb-separator">/</span>
                <span>Dashboard</span>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="earnings-grid">
            <!-- Card 1: Daily Goal Progress -->
            <div class="earnings-card">
                <div class="earnings-header">DAILY WORKOUT GOAL</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount">75%</span>
                    </div>
                    <div class="earnings-percentage">2.5 hrs logged</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: 75%;"></div>
                </div>
            </div>

            <!-- Card 2: Monthly Attendance -->
            <div class="earnings-card">
                <div class="earnings-header">MONTHLY ATTENDANCE</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount">12</span>
                    </div>
                    <div class="earnings-percentage">Visits this month</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: 60%;"></div>
                </div>
            </div>
            
            <!-- Card 3: Weight Goal -->
            <div class="earnings-card">
                <div class="earnings-header">WEIGHT LOSS GOAL</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount">8 kg</span>
                    </div>
                    <div class="earnings-percentage text-cyan-500">4.5 kg achieved</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: 56%;"></div>
                </div>
            </div>
        </div>

        <!-- Recent Activities Section -->
        <div class="activities-card">
            <div class="activities-header">Recent Activities</div>
            <div class="activity-list">
                <!-- Activity Item 1 -->
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="ph-duotone ph-running text-xl"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Cardio Session Completed</div>
                        <div class="activity-description">45 minutes on the treadmill. Great job!</div>
                    </div>
                    <div class="activity-meta">
                        <span class="activity-timestamp">
                            <span class="status-dot complete"></span> 
                            Just now
                        </span>
                        <div class="activity-actions">
                            <button class="action-btn success">View Log</button>
                        </div>
                    </div>
                </div>

                <!-- Activity Item 2 -->
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="ph-duotone ph-book-open-text text-xl"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">New Meal Plan Available</div>
                        <div class="activity-description">Your personalized high-protein plan is ready.</div>
                    </div>
                    <div class="activity-meta">
                        <span class="activity-timestamp">
                            <span class="status-dot active"></span> 
                            1 hour ago
                        </span>
                        <div class="activity-actions">
                            <button class="action-btn primary">Download</button>
                        </div>
                    </div>
                </div>
                
                <!-- Activity Item 3 -->
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="ph-duotone ph-calendar-check text-xl"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Personal Training Booked</div>
                        <div class="activity-description">Session with Coach Alex on Friday at 5 PM.</div>
                    </div>
                    <div class="activity-meta">
                        <span class="activity-timestamp">
                            <span class="status-dot pending"></span> 
                            Yesterday
                        </span>
                        <div class="activity-actions">
                            <button class="action-btn secondary">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="../assets/js/plugins/feather.min.js"></script>
    <script src="../assets/js/icon/custom-icon.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenu = document.querySelector('.mobile-menu');
            const navLinks = document.querySelector('.nav-links');
            
            if (mobileMenu) {
                mobileMenu.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                });
            }
        });
    </script>
</body>
<footer>
    <div class="footer-bottom">
            <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
    </div>
</footer>
</html>
<?php } ?>