<?php
header('Content-Type: application/json');

$ticker = isset($_GET['ticker']) ? strtoupper(trim(htmlspecialchars($_GET['ticker']))) : '';

if (empty($ticker)) {
    echo json_encode(['status' => 'error', 'message' => 'Ticker parameter is required']);
    exit();
}

$url = "https://scanner.tradingview.com/indonesia/scan";

$payload = json_encode([
    'symbols' => [
        'tickers' => ["IDX:" . $ticker]
    ],
    'columns' => ["close"]
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(['status' => 'error', 'message' => $err]);
    exit();
}

if ($http_code !== 200) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch price from TradingView', 'http_code' => $http_code]);
    exit();
}

$data = json_decode($response, true);

if (isset($data['data'][0]['d'][0])) {
    $price = floatval($data['data'][0]['d'][0]);
    echo json_encode(['status' => 'success', 'ticker' => $ticker, 'price' => $price]);
} else {
    // Fallback: If not found on TradingView, we don't output error, but try to search without IDX prefix
    echo json_encode(['status' => 'error', 'message' => 'Symbol not found on TradingView']);
}
?>
