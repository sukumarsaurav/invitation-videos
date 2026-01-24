<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = '127.0.0.1'; // Force TCP
$db = $_ENV['DB_DATABASE'];
$user = $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_PASSWORD'];
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}

$stmt = $pdo->query("SELECT id, order_number, order_status, video_url, created_at, updated_at FROM orders ORDER BY id DESC LIMIT 5");
$orders = $stmt->fetchAll();

echo "Latest 5 Orders:\n";
foreach ($orders as $order) {
    echo "--------------------------------------------------\n";
    echo "Order ID: " . $order['id'] . "\n";
    echo "Order Number: " . $order['order_number'] . "\n";
    echo "Status: " . $order['order_status'] . "\n";
    echo "Video URL: " . ($order['video_url'] ? $order['video_url'] : "NULL") . "\n";
    echo "Created At: " . $order['created_at'] . "\n";
    echo "Updated At: " . $order['updated_at'] . "\n";
}
echo "--------------------------------------------------\n";
