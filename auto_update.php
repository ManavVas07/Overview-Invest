<?php
require_once __DIR__ . '/config.php';

function perform_auto_update(mysqli $mysqli): array
{
    $stocksResult = $mysqli->query('SELECT id, price FROM stocks');
    $updates = [];
    $timestamp = date('Y-m-d H:i:s');

    while ($stock = $stocksResult->fetch_assoc()) {
        $currentPrice = (float)$stock['price'];
        $changePercent = mt_rand(-300, 300) / 1000; // -0.30 to +0.30 (±30%)
        $newPrice = max(0.50, round($currentPrice * (1 + $changePercent), 2));

        $stmt = $mysqli->prepare('UPDATE stocks SET price = ?, last_updated = NOW() WHERE id = ?');
        $stmt->bind_param('di', $newPrice, $stock['id']);
        $stmt->execute();
        $stmt->close();

        $updates[] = [
            'id' => (int)$stock['id'],
            'price' => number_format($newPrice, 2, '.', ''),
            'last_updated' => $timestamp
        ];
    }

    return [
        'success' => true,
        'updated' => $updates
    ];
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }

    header('Content-Type: application/json');

    try {
        echo json_encode(perform_auto_update($mysqli));
    } catch (Throwable $th) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $th->getMessage()]);
    }
    exit;
}

