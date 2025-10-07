<?php
session_start();
if (isset($_POST['submit'])) {
} else {

?>
  <!doctype html>
  <html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

  <head>
    <!-- Debug: Add error reporting -->
<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<!-- Current file path: " . __FILE__ . " -->";
?>
    
<title>Profile</title>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="description" content="." />
<meta name="keywords" content="." />
<meta name="author" content="Sniper 2025" />
    
    <!-- Fixed paths - since you're in member/ and assets are in dist/ -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <!-- Added Montserrat link, assuming the custom CSS uses it -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
    <!-- This is where your custom gym styles should be loaded -->
    <link rel="stylesheet" href="../assets/css/member_dashboard.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/home.css" id="main-style-link" />

  </head>

  <body class="dark-mode"> <!-- Added 'dark-mode' class to showcase the dark theme capabilities -->
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
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
      <div class="loader-track h-[5px] w-full inline-block absolute overflow-hidden top-0">
        <div class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]"></div>
      </div>
    </div>
    <!-- [ Pre-loader ] End -->

    <!-- [ Main Content ] start -->
    <div class="pc-container">
      <div class="pc-content">

            <!-- Start Dashboard Content Area -->
            <div class="dashboard-container">
                
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <h1 class="dashboard-title">Welcome Back, Member!</h1>
                    <div class="breadcrumb">
                        <a href="#">Home</a>
                        <span class="breadcrumb-separator">/</span>
                        <span>Dashboard</span>
                    </div>
                </div>

                <!-- Earnings/Metrics Grid -->
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

            </div>
            <!-- End Dashboard Content Area -->

      
        <!-- [ Main Content ] end -->
      </div>
    </div>
    <!-- [ Main Content ] end -->
    <?php include '../includes/footer.php'; ?>

    <!-- Required Js - Fixed paths -->
    <script src="../assets/js/plugins/simplebar.min.js"></script>
    <script src="../assets/js/plugins/popper.min.js"></script>
    <script src="../assets/js/icon/custom-icon.js"></script>
    <script src="../assets/js/plugins/feather.min.js"></script>
    <script src="../assets/js/component.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/script.js"></script>
    <div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]">
    </div>

    <script>
        // Ensure the custom scripts match the theme
      layout_change('false');
      layout_theme_sidebar_change('dark'); // Keeping sidebar dark to match gym theme
      change_box_container('false');
      layout_caption_change('true');
      layout_rtl_change('false');
      preset_change('preset-1');
      main_layout_change('vertical');
    </script>
  </body>
  <!-- [Body] end -->

  </html>
<?php } ?>