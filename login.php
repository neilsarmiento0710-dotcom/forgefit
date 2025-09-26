<?php
// super simple PHP login + register test (not secure, just for testing!)
// Hardcoded storage in a text file instead of a database.

session_start();

$users_file = __DIR__ . "/users.txt";
if (!file_exists($users_file)) {
    file_put_contents($users_file, "");
}

function load_users($file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $users = [];
    foreach ($lines as $line) {
        list($u, $p) = explode(":", $line, 2);
        $users[$u] = $p;
    }
    return $users;
}

function save_user($file, $username, $password) {
    file_put_contents($file, $username . ":" . $password . "\n", FILE_APPEND);
}

$users = load_users($users_file);
$error = '';

// Handle register
if (isset($_POST['register'])) {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');
    if ($u === '' || $p === '') {
        $error = "Enter username and password.";
    } elseif (isset($users[$u])) {
        $error = "Username already exists.";
    } else {
        save_user($users_file, $u, $p);
        $users = load_users($users_file);
        $error = "Registered successfully. Please login.";
    }
}

// Handle login
if (isset($_POST['login'])) {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    if (isset($users[$u]) && $users[$u] === $p) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $u;
        header("Location: ?");
        exit;
    } else {
        $error = "Invalid login.";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit;
}

// Logged in view
if (!empty($_SESSION['logged_in'])) {
    echo "<h2>Welcome, ".htmlspecialchars($_SESSION['username'])."!</h2>";
    echo "<p><a href='?logout=1'>Logout</a></p>";
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Simple Login & Register Test</title>
</head>
<body>
  <h2>Login</h2>
  <?php if (!empty($error)) echo "<p style='color:red'>$error</p>"; ?>
  <form method="post">
    <label>Username: <input type="text" name="username"></label><br>
    <label>Password: <input type="password" name="password"></label><br>
    <button type="submit" name="login">Login</button>
    <button type="submit" name="register">Register</button>
  </form>
</body>
</html>
