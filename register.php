<?php
require_once __DIR__ . '/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_string($_POST['username'] ?? '');
    $email = sanitize_string($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($username === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Username or email already in use.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $mysqli->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
            $insert->bind_param('sss', $username, $email, $passwordHash);
            if ($insert->execute()) {
                $success = 'Account created! You can now sign in.';
            } else {
                $error = 'Failed to create your account. Please try again.';
            }
            $insert->close();
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Overview Invest</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <h1>Create Account</h1>
        <p>Get started with Overview Invest.</p>
        <?php if ($error !== ''): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php elseif ($success !== ''): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="post">
            <label>
                Username
                <input type="text" name="username" required>
            </label>
            <label>
                Email
                <input type="email" name="email" required>
            </label>
            <label>
                Password
                <input type="password" name="password" required minlength="6">
            </label>
            <label>
                Confirm Password
                <input type="password" name="confirm_password" required minlength="6">
            </label>
            <button type="submit" class="btn">Create Account</button>
        </form>
        <p class="meta">Already registered? <a href="login.php">Sign in</a></p>
        <p class="meta"><a href="admin_login.php">Admin login</a></p>
    </div>
</body>
</html>

