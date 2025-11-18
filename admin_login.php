<?php
session_start();
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($password === '') {
        $error = 'Please enter the admin password.';
    } else {

        // Fetch the single admin record (plaintext password)
        $adminQuery = $mysqli->query("SELECT id, password FROM admins ORDER BY id ASC LIMIT 1");
        $admin = $adminQuery->fetch_assoc();

        // Plain-text password comparison
        if (!$admin || $password !== $admin['password']) {
            $error = 'Invalid admin credentials.';
        } else {
            // Set admin session
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['role'] = 'admin';

            header('Location: admin_dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Overview Invest</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <h1>Admin Portal</h1>
        <p>Manage platform settings and users.</p>
        <?php if ($error !== ''): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post">
            <label>
                Password
                <input type="password" name="password" required>
            </label>
            <button type="submit" class="btn">Sign In</button>
        </form>
        <p class="meta"><a href="index.php">Back to Overview Invest</a></p>
    </div>
</body>
</html>
