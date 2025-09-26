<?php
/* session_start();

// Mock users (for testing only)
$users = [
  ["id" => 1, "username" => "member1", "password" => "1234", "role" => "member"]
];

$error = "";

// Handle login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    foreach ($users as $u) {
        if ($u["username"] === $username && $u["password"] === $password && $u["role"] === "member") {
            $_SESSION["user"] = $u;
            // Secure the session
            session_regenerate_id(true);
            header("Location: member_dashboard.php");
            exit;
        }
    }
    $error = "Invalid login.";
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Member Login</title>
</head>
<body>
  <h2>Member Login</h2>

  <?php if ($error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="post">
    <input name="username" placeholder="Username" required><br>
    <input name="password" type="password" placeholder="Password" required><br>
    <button type="submit">Login</button>
  </form>

<a href="../index.php">
  <button type="button">Exit</button>
</a>
</body>
</html> 
*/

session_start();

// Mock users
$users = [
  ["id" => 1, "username" => "member1", "password" => "1234", "role" => "member"],
  ["id" => 2, "username" => "coach1", "password" => "5678", "role" => "coach"],
  ["id" => 3, "username" => "admin1", "password" => "9999", "role" => "admin"]
];

$error = "";

// Handle login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    foreach ($users as $u) {
        if ($u["username"] === $username && $u["password"] === $password) {
            $_SESSION["user"] = $u;
            session_regenerate_id(true);

            // Redirect based on role
            if ($u["role"] === "member") {
                header("Location: ./dist/member/member_dashboard.php");
            } elseif ($u["role"] === "coach") {
                header("Location: ./dist/coach/dashboard.php");
            } elseif ($u["role"] === "admin") {
                header("Location: ./dist/admin/dashboard.php");
            }
            exit;
        }
    }
    $error = "Invalid login.";
}
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <head>
    <title>Login</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="." />
    <meta name="keywords" content="." />
    <meta name="author" content="Sniper 2025" />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./dist/assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/feather.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/material.css" />
    <link rel="stylesheet" href="./dist/assets/css/style.css" id="main-style-link" />
  </head>
  <body>
    <!-- [ Pre-loader ] start -->
    <div class="fixed bg-transparent">
      <span><img src="./dist/assets/images/bg.jpeg" alt="logo" /></span>
    </div>
    <!-- [ Pre-loader ] End -->

    <div class="auth-main relative">
      <div class="auth-wrapper v1 flex items-center w-full h-full min-h-screen justify-center">
        <form method="post" class="text-center bg-transparent p-6 rounded shadow-lg">
          <h2 class="mb-4">Login</h2>

          <?php if ($error): ?>
            <p style="color:red"><?= htmlspecialchars($error) ?></p>
          <?php endif; ?>

          <input type="text" name="username" placeholder="Username" required class="form-control mb-2"><br>
          <input type="password" name="password" placeholder="Password" required class="form-control mb-2"><br>

          <button type="submit" class="btn btn-primary shadow-2xl">Login</button>
          <a href="index.php">
            <button type="button" class="btn btn-danger shadow-2xl">Exit</button>
          </a>
        </form>
      </div>
    </div>

    <!-- Required Js -->
    <script src="./dist/assets/js/plugins/simplebar.min.js"></script>
    <script src="./dist/assets/js/plugins/popper.min.js"></script>
    <script src="./dist/assets/js/icon/custom-icon.js"></script>
    <script src="./dist/assets/js/plugins/feather.min.js"></script>
    <script src="./dist/assets/js/component.js"></script>
    <script src="./dist/assets/js/theme.js"></script>
    <script src="./dist/assets/js/script.js"></script>

    <div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]"></div>
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
</html>
