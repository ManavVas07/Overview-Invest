<?php
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize_string($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error = 'Please provide both username/email and password.';
    } else {
        $stmt = $mysqli->prepare('SELECT id, username, email, password_hash, role, is_active FROM users WHERE (username = ? OR email = ?) AND role = "user" LIMIT 1');
        $stmt->bind_param('ss', $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || (int)$user['is_active'] !== 1 || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid credentials or inactive account.';
        } else {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
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
    <title>User Login | Overview Invest</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <h1>Welcome Back</h1>
        <p>Sign in to continue trading.</p>
        <?php if ($error !== ''): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post">
            <label>
                Username or Email
                <input type="text" name="identifier" required>
            </label>
            <label>
                Password
                <input type="password" name="password" required>
            </label>
            <button type="submit" class="btn">Sign In</button>
        </form>
        <p class="meta">Need an account? <a href="register.php">Sign up</a></p>
        <p class="meta"><a href="admin_login.php">Admin login</a></p>
    </div>
</body>
</html>

