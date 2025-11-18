<?php
require_once __DIR__ . '/config.php';
redirect_if_not_logged_in();
redirect_if_not_admin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'add_stock':
                $symbol = strtoupper(sanitize_string($_POST['symbol'] ?? ''));
                $name = sanitize_string($_POST['name'] ?? '');
                $price = (float)($_POST['price'] ?? 0);

                if ($symbol === '' || $name === '' || $price <= 0) {
                    throw new RuntimeException('All stock fields are required and price must be positive.');
                }

                $stmt = $mysqli->prepare('INSERT INTO stocks (symbol, name, price) VALUES (?, ?, ?)');
                $stmt->bind_param('ssd', $symbol, $name, $price);
                if (!$stmt->execute()) {
                    throw new RuntimeException('Failed to add stock. Ensure the symbol is unique.');
                }
                $stmt->close();
                $message = 'Stock added successfully.';
                break;

            case 'delete_stock':
                $stockId = (int)($_POST['stock_id'] ?? 0);
                if ($stockId <= 0) {
                    throw new RuntimeException('Invalid stock selection.');
                }
                $stmt = $mysqli->prepare('DELETE FROM stocks WHERE id = ?');
                $stmt->bind_param('i', $stockId);
                $stmt->execute();
                $stmt->close();
                $message = 'Stock deleted.';
                break;

            case 'update_price':
                $stockId = (int)($_POST['stock_id'] ?? 0);
                $price = (float)($_POST['price'] ?? 0);
                if ($stockId <= 0 || $price <= 0) {
                    throw new RuntimeException('Provide a valid stock and price.');
                }
                $stmt = $mysqli->prepare('UPDATE stocks SET price = ?, last_updated = NOW() WHERE id = ?');
                $stmt->bind_param('di', $price, $stockId);
                $stmt->execute();
                $stmt->close();
                $message = 'Stock price updated.';
                break;

            case 'toggle_user':
                $userId = (int)($_POST['user_id'] ?? 0);
                $status = (int)($_POST['status'] ?? 1);
                if ($userId <= 0) {
                    throw new RuntimeException('Invalid user selection.');
                }
                $stmt = $mysqli->prepare('UPDATE users SET is_active = ? WHERE id = ? AND role = "user"');
                $stmt->bind_param('ii', $status, $userId);
                $stmt->execute();
                $stmt->close();
                $message = 'User status updated.';
                break;

            case 'refresh_prices':
                require_once __DIR__ . '/auto_update.php';
                perform_auto_update($mysqli);
                $message = 'Prices refreshed automatically.';
                break;

            default:
                throw new RuntimeException('Unsupported action.');
        }
    } catch (Throwable $th) {
        $error = $th->getMessage();
    }
}

$stocks = $mysqli->query('SELECT id, symbol, name, price, last_updated FROM stocks ORDER BY symbol')->fetch_all(MYSQLI_ASSOC);
$users = $mysqli->query('SELECT id, username, email, balance, is_active, created_at FROM users WHERE role = "user" ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Overview Invest</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header class="top-bar">
        <h1>Admin Dashboard</h1>
        <div class="user-info">
            <span>Logged in as <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
            <a class="btn outline" href="logout.php">Log out</a>
        </div>
    </header>
    <main class="dashboard">
        <?php if ($message !== ''): ?>
            <div class="alert success"><?php echo $message; ?></div>
        <?php elseif ($error !== ''): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <section class="card">
            <h2>Market Management</h2>
            <form method="post" class="inline-form">
                <input type="hidden" name="action" value="refresh_prices">
                <button type="submit" class="btn">Run Automatic Update</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Symbol</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stocks)): ?>
                        <tr><td colspan="5">No stocks listed yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stocks as $stock): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stock['symbol']); ?></td>
                                <td><?php echo htmlspecialchars($stock['name']); ?></td>
                                <td>$<?php echo number_format((float)$stock['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($stock['last_updated']); ?></td>
                                <td class="table-actions">
                                    <form method="post">
                                        <input type="hidden" name="action" value="delete_stock">
                                        <input type="hidden" name="stock_id" value="<?php echo $stock['id']; ?>">
                                        <button type="submit" class="btn danger" onclick="return confirm('Delete this stock?');">Delete</button>
                                    </form>
                                    <form method="post" class="update-price-form">
                                        <input type="hidden" name="action" value="update_price">
                                        <input type="hidden" name="stock_id" value="<?php echo $stock['id']; ?>">
                                        <input type="number" name="price" min="0.01" step="0.01" placeholder="New price" required>
                                        <button type="submit" class="btn secondary">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2>Add Stock</h2>
            <form method="post" class="grid-form">
                <input type="hidden" name="action" value="add_stock">
                <label>
                    Symbol
                    <input type="text" name="symbol" maxlength="10" required>
                </label>
                <label>
                    Name
                    <input type="text" name="name" required>
                </label>
                <label>
                    Price
                    <input type="number" name="price" min="0.01" step="0.01" required>
                </label>
                <button type="submit" class="btn">Add Stock</button>
            </form>
        </section>

        <section class="card">
            <h2>User Management</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6">No users registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>$<?php echo number_format((float)$user['balance'], 2); ?></td>
                                <td><?php echo (int)$user['is_active'] === 1 ? 'Active' : 'Inactive'; ?></td>
                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="action" value="toggle_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo (int)$user['is_active'] === 1 ? 0 : 1; ?>">
                                        <button type="submit" class="btn secondary">
                                            <?php echo (int)$user['is_active'] === 1 ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>

