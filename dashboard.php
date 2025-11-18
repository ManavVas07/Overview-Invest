<?php
require_once __DIR__ . '/config.php';
redirect_if_not_logged_in();

if (is_admin()) {
    header('Location: admin_dashboard.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $stockId = (int)($_POST['stock_id'] ?? 0);
    $quantity = max(0, (int)($_POST['quantity'] ?? 0));

    if (!in_array($action, ['buy', 'sell'], true) || $stockId <= 0 || $quantity <= 0) {
        $error = 'Please choose a valid stock and quantity.';
    } else {
        $mysqli->begin_transaction();
        try {
            $stockStmt = $mysqli->prepare('SELECT id, price FROM stocks WHERE id = ? LIMIT 1 FOR UPDATE');
            $stockStmt->bind_param('i', $stockId);
            $stockStmt->execute();
            $stockResult = $stockStmt->get_result();
            $stock = $stockResult->fetch_assoc();
            $stockStmt->close();

            if (!$stock) {
                throw new RuntimeException('Stock not found.');
            }

            $price = (float)$stock['price'];
            $cost = $price * $quantity;

            $userStmt = $mysqli->prepare('SELECT balance FROM users WHERE id = ? LIMIT 1 FOR UPDATE');
            $userStmt->bind_param('i', $_SESSION['user_id']);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $user = $userResult->fetch_assoc();
            $userStmt->close();

            if (!$user) {
                throw new RuntimeException('User record missing.');
            }

            if ($action === 'buy') {
                if ($user['balance'] < $cost) {
                    throw new RuntimeException('Insufficient balance for this purchase.');
                }

                $updateBalance = $mysqli->prepare('UPDATE users SET balance = balance - ? WHERE id = ?');
                $updateBalance->bind_param('di', $cost, $_SESSION['user_id']);
                $updateBalance->execute();
                $updateBalance->close();

                $portfolio = $mysqli->prepare('INSERT INTO portfolios (user_id, stock_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)');
                $portfolio->bind_param('iii', $_SESSION['user_id'], $stockId, $quantity);
                $portfolio->execute();
                $portfolio->close();
            } else { // sell
                $portfolio = $mysqli->prepare('SELECT quantity FROM portfolios WHERE user_id = ? AND stock_id = ? LIMIT 1 FOR UPDATE');
                $portfolio->bind_param('ii', $_SESSION['user_id'], $stockId);
                $portfolio->execute();
                $portfolioResult = $portfolio->get_result();
                $holding = $portfolioResult->fetch_assoc();
                $portfolio->close();

                if (!$holding || (int)$holding['quantity'] < $quantity) {
                    throw new RuntimeException('You do not own enough shares to sell.');
                }

                $updatePortfolio = $mysqli->prepare('UPDATE portfolios SET quantity = quantity - ? WHERE user_id = ? AND stock_id = ?');
                $updatePortfolio->bind_param('iii', $quantity, $_SESSION['user_id'], $stockId);
                $updatePortfolio->execute();
                $updatePortfolio->close();

                $removeEmpty = $mysqli->prepare('DELETE FROM portfolios WHERE user_id = ? AND stock_id = ? AND quantity <= 0');
                $removeEmpty->bind_param('ii', $_SESSION['user_id'], $stockId);
                $removeEmpty->execute();
                $removeEmpty->close();

                $updateBalance = $mysqli->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
                $updateBalance->bind_param('di', $cost, $_SESSION['user_id']);
                $updateBalance->execute();
                $updateBalance->close();
            }

            $log = $mysqli->prepare('INSERT INTO transactions (user_id, stock_id, transaction_type, quantity, price) VALUES (?, ?, ?, ?, ?)');
            $log->bind_param('iisid', $_SESSION['user_id'], $stockId, $action, $quantity, $price);
            $log->execute();
            $log->close();

            $mysqli->commit();
            $message = sprintf('Successfully %s %d share(s).', $action === 'buy' ? 'purchased' : 'sold', $quantity);
        } catch (Throwable $th) {
            $mysqli->rollback();
            $error = $th->getMessage();
        }
    }
}

$stocks = $mysqli->query('SELECT id, symbol, name, price, last_updated FROM stocks ORDER BY symbol')->fetch_all(MYSQLI_ASSOC);

$balanceStmt = $mysqli->prepare('SELECT balance FROM users WHERE id = ? LIMIT 1');
$balanceStmt->bind_param('i', $_SESSION['user_id']);
$balanceStmt->execute();
$balanceResult = $balanceStmt->get_result();
$userBalance = $balanceResult->fetch_assoc()['balance'] ?? 0.0;
$balanceStmt->close();

$portfolioStmt = $mysqli->prepare('SELECT s.symbol, s.name, p.quantity, s.price, (p.quantity * s.price) AS market_value FROM portfolios p INNER JOIN stocks s ON s.id = p.stock_id WHERE p.user_id = ? ORDER BY s.symbol');
$portfolioStmt->bind_param('i', $_SESSION['user_id']);
$portfolioStmt->execute();
$portfolio = $portfolioStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$portfolioStmt->close();

$transactionsStmt = $mysqli->prepare('SELECT t.transaction_type, t.quantity, t.price, s.symbol, t.created_at FROM transactions t INNER JOIN stocks s ON s.id = t.stock_id WHERE t.user_id = ? ORDER BY t.created_at DESC LIMIT 10');
$transactionsStmt->bind_param('i', $_SESSION['user_id']);
$transactionsStmt->execute();
$transactions = $transactionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$transactionsStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Overview Invest</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header class="top-bar">
        <h1>Overview Invest</h1>
        <div class="user-info">
            <span>Hi, <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
            <span class="balance">Balance: $<?php echo number_format((float)$userBalance, 2); ?></span>
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
            <h2>Market Overview</h2>
            <p class="hint">Prices refresh automatically. Last refreshed: <span id="last-refresh"><?php echo date('H:i:s'); ?></span></p>
            <table id="market-table">
                <thead>
                    <tr>
                        <th>Symbol</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stocks as $stock): ?>
                        <tr data-stock-id="<?php echo $stock['id']; ?>">
                            <td><?php echo htmlspecialchars($stock['symbol']); ?></td>
                            <td><?php echo htmlspecialchars($stock['name']); ?></td>
                            <td class="price">$<?php echo number_format((float)$stock['price'], 2); ?></td>
                            <td class="updated"><?php echo htmlspecialchars($stock['last_updated']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2>Trade Stocks</h2>
            <form method="post" class="trade-form">
                <label>
                    Stock
                    <select name="stock_id" required>
                        <option value="">Select stock</option>
                        <?php foreach ($stocks as $stock): ?>
                            <option value="<?php echo $stock['id']; ?>"><?php echo htmlspecialchars($stock['symbol'] . ' - ' . $stock['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Quantity
                    <input type="number" name="quantity" min="1" step="1" required>
                </label>
                <div class="trade-actions">
                    <button type="submit" name="action" value="buy" class="btn">Buy</button>
                    <button type="submit" name="action" value="sell" class="btn secondary">Sell</button>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Your Portfolio</h2>
            <?php if (empty($portfolio)): ?>
                <p>You have no holdings yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Name</th>
                            <th>Quantity</th>
                            <th>Market Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($portfolio as $holding): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($holding['symbol']); ?></td>
                                <td><?php echo htmlspecialchars($holding['name']); ?></td>
                                <td><?php echo (int)$holding['quantity']; ?></td>
                                <td>$<?php echo number_format((float)$holding['market_value'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Recent Transactions</h2>
            <?php if (empty($transactions)): ?>
                <p>No trades recorded yet.</p>
            <?php else: ?>
                <ul class="transaction-list">
                    <?php foreach ($transactions as $txn): ?>
                        <li>
                            <span class="symbol"><?php echo htmlspecialchars(strtoupper($txn['transaction_type'])); ?></span>
                            <span><?php echo (int)$txn['quantity']; ?> × <?php echo htmlspecialchars($txn['symbol']); ?></span>
                            <span>@ $<?php echo number_format((float)$txn['price'], 2); ?></span>
                            <span class="timestamp"><?php echo htmlspecialchars($txn['created_at']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </main>
    <script src="assets/app.js" defer></script>
</body>
</html>

