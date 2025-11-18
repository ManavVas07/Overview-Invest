<?php
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    if (is_admin()) {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overview Invest</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header class="hero">
        <div class="hero-content">
            <h1>Overview Invest</h1>
            <p>Practice trading, build strategies, and track performance in our stock market simulation.</p>
            <div class="cta-group">
                <a class="btn" href="register.php">Create Account</a>
                <a class="btn secondary" href="login.php">Sign In</a>
                <a class="btn outline" href="admin_login.php">Admin Portal</a>
            </div>
        </div>
    </header>
    <main class="features">
        <section class="feature-card">
            <h2>Real-Time Price Simulation</h2>
            <p>Stock prices adjust automatically to mimic market movement. Stay on top of trends with live updates.</p>
        </section>
        <section class="feature-card">
            <h2>Practice Buying & Selling</h2>
            <p>Build a virtual portfolio, execute trades, and learn without financial risk.</p>
        </section>
        <section class="feature-card">
            <h2>Admin Controls</h2>
            <p>Admins manage listed stocks, update prices, and supervise user activity from an intuitive dashboard.</p>
        </section>
    </main>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Overview Invest. All rights reserved.</p>
    </footer>
</body>
</html>

