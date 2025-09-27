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
    
    <title>Dashboard</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="." />
    <meta name="keywords" content="." />
    <meta name="author" content="Sniper 2025" />
    
    <!-- Fixed paths - since you're in member/ and assets are in dist/ -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
  </head>

  <body>
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
      <div class="loader-track h-[5px] w-full inline-block absolute overflow-hidden top-0">
        <div class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]"></div>
      </div>
    </div>
    <!-- [ Pre-loader ] End -->

    <!-- [ Sidebar Menu ] start -->
    <?php include '../includes/member-sidebar.php'; ?>
    <!-- [ Sidebar Menu ] end -->
    <!-- [ Header Topbar ] start -->
    <?php include '../includes/member-header.php'; ?>
    <!-- [ Header ] end -->

    <!-- [ Main Content ] start -->
    <div class="pc-container">
      <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">Dashboard</h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../member/member_dashboard.php">Member</a></li>
              <!-- <li class="breadcrumb-item"><a href="javascript: void(0)">Dashboard</a></li> -->
              <li class="breadcrumb-item" aria-current="page">Dashboard</li>
            </ul>
          </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
          <div class="col-span-12 xl:col-span-6 md:col-span-6">
            <div class="card">
              <div class="card-header !pb-0 !border-b-0">
                <h5>This is Daily Earnings</h5>
              </div>
              <div class="card-body">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                  <h3 class="font-light flex items-center mb-0">
                    <i class="feather icon-arrow-up text-success-500 text-[30px] mr-1.5"></i>$ 89.50
                  </h3>
                  <p class="mb-0">78%</p>
                </div>
                <div class="w-full bg-theme-bodybg rounded-lg h-1.5 mt-6 dark:bg-themedark-bodybg">
                  <div class="bg-theme-bg-1 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar"
                    style="width: 78%"></div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-span-12 xl:col-span-6 md:col-span-6">
            <div class="card">
              <div class="card-header !pb-0 !border-b-0">
                <h5>This is Monthly Earnings</h5>
              </div>
              <div class="card-body">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                  <h3 class="font-light flex items-center mb-0">
                    <i class="feather icon-arrow-up text-success-500 text-[30px] mr-1.5"></i>$ 1,847.65
                  </h3>
                  <p class="mb-0">84%</p>
                </div>
                <div class="w-full bg-theme-bodybg rounded-lg h-1.5 mt-6 dark:bg-themedark-bodybg">
                  <div class="bg-theme-bg-1 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar"
                    style="width: 84%"></div>
                </div>
              </div>
            </div>
          </div>
 
          <div class="col-span-12">
            <div class="card table-card">
              <div class="card-header">
                <h5>My Recent Activities</h5>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-hover">
                    <tbody>
                      <!-- activity 1 start here -->
                      <tr class="unread">
                        <td>
                          <img class="rounded-full max-w-10" style="width: 40px" src="../assets/images/user/avatar-1.jpg" alt="activity-user" />
                        </td>
                        <td>
                          <h6 class="mb-1">Profile Updated Successfully</h6>
                          <p class="m-0">You have successfully updated your profile information and preferences....</p>
                        </td>
                        <td>
                          <h6 class="text-muted">
                            <i class="fas fa-circle text-success text-[10px] ltr:mr-4 rtl:ml-4"></i>
                            25 SEP 14:30
                          </h6>
                        </td>
                        <td>
                          <a href="#!" class="badge bg-theme-bg-2 text-white text-[12px] mx-2">View</a>
                          <a href="#!" class="badge bg-theme-bg-1 text-white text-[12px]">Complete</a>
                        </td>
                      </tr>  <!-- activity 1 ends here -->

                      <!-- activity 2 start here -->
                      <tr class="unread">
                        <td>
                          <img class="rounded-full max-w-10" style="width: 40px" src="../assets/images/user/avatar-2.jpg" alt="activity-user" />
                        </td>
                        <td>
                          <h6 class="mb-1">Payment Processed</h6>
                          <p class="m-0">Your monthly subscription payment has been successfully processed....</p>
                        </td>
                        <td>
                          <h6 class="text-muted">
                            <i class="fas fa-circle text-danger text-[10px] ltr:mr-4 rtl:ml-4"></i>
                            23 SEP 09:15
                          </h6>
                        </td>
                        <td>
                          <a href="#!" class="badge bg-theme-bg-2 text-white text-[12px] mx-2">Receipt</a>
                          <a href="#!" class="badge bg-theme-bg-1 text-white text-[12px]">Paid</a>
                        </td>
                      </tr>  <!-- activity 2 ends here -->

                      <!-- activity 3 start here -->
                      <tr class="unread">
                        <td>
                          <img class="rounded-full max-w-10" style="width: 40px" src="../assets/images/user/avatar-3.jpg" alt="activity-user" />
                        </td>
                        <td>
                          <h6 class="mb-1">New Feature Access</h6>
                          <p class="m-0">You now have access to premium analytics dashboard and reporting tools....</p>
                        </td>
                        <td>
                          <h6 class="text-muted">
                            <i class="fas fa-circle text-success text-[10px] ltr:mr-4 rtl:ml-4"></i>
                            20 SEP 16:45
                          </h6>
                        </td>
                        <td>
                          <a href="#!" class="badge bg-theme-bg-2 text-white text-[12px] mx-2">Explore</a>
                          <a href="#!" class="badge bg-theme-bg-1 text-white text-[12px]">Active</a>
                        </td>
                      </tr>  <!-- activity 3 ends here -->
                      
                      <!-- activity 4 start here -->
                      <tr class="unread">
                        <td>
                          <img class="rounded-full max-w-10" style="width: 40px" src="../assets/images/user/avatar-1.jpg" alt="activity-user" />
                        </td>
                        <td>
                          <h6 class="mb-1">Account Verification Complete</h6>
                          <p class="m-0">Your account has been successfully verified and all features are now available....</p>
                        </td>
                        <td>
                          <h6 class="text-muted f-w-300">
                            <i class="fas fa-circle text-danger text-[10px] ltr:mr-4 rtl:ml-4"></i>
                            18 SEP 11:20
                          </h6>
                        </td>
                        <td>
                          <a href="#!" class="badge bg-theme-bg-2 text-white text-[12px] mx-2">Details</a>
                          <a href="#!" class="badge bg-theme-bg-1 text-white text-[12px]">Verified</a>
                        </td>
                      </tr> <!-- activity 4 ends here -->

                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
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
      layout_change('false');
      layout_theme_sidebar_change('dark');
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