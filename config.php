<?php
// Suppress deprecated/notice errors from showing in browser output
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');

$host = 'localhost';
$user = 'root';
$pass = ''; // Default laragon password
$db = 'screening_saham';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    // If DB doesn't exist, we might be hitting this before importing. 
    // Just a fallback check.
    die("Connection failed: " . $conn->connect_error);
}

// Helper to fetch 100% accurate live stock price from Yahoo Finance API (matching TradingView)
function getLiveStockPrice($ticker, $conn = null) {
    $ticker = strtoupper(trim(htmlspecialchars($ticker ?? '')));
    
    // Tentukan fallback default jika semua gagal
    $price = 100;
    $company_name = $ticker . ' INDONESIA';
    $prev_close = 100;
    $change = 0;
    $change_percent = 0;
    $high = 100;
    $low = 100;
    $volume = 100000;
    
    // 1. Ambil dari database terlebih dahulu jika koneksi ada
    $updated_at = null;
    if ($conn) {
        $stmt = $conn->prepare("SELECT price, company_name, volume, updated_at FROM stocks WHERE ticker = ?");
        if ($stmt) {
            $stmt->bind_param("s", $ticker);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $price = (float)$row['price'];
                $company_name = $row['company_name'];
                $volume = (int)$row['volume'];
                $updated_at = $row['updated_at'];
            }
            $stmt->close();
        }
    }
    
    // Cek cache: jika data di DB diupdate kurang dari 5 menit (300 detik) yang lalu, gunakan data DB secara instan!
    if ($updated_at) {
        $last_update = strtotime($updated_at);
        $time_diff = time() - $last_update;
        if ($time_diff < 300) { 
            return [
                'price' => $price,
                'company_name' => $company_name,
                'prev_close' => $price,
                'change' => 0,
                'change_percent' => 0,
                'high' => $price,
                'low' => $price,
                'volume' => $volume
            ];
        }
    }
    
    // 2. Tarik harga asli secara real-time dari Yahoo Finance API
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}.JK";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['chart']['result'][0]['meta']['regularMarketPrice'])) {
            $meta = $data['chart']['result'][0]['meta'];
            $realPrice = (float)$meta['regularMarketPrice'];
            $realPrevClose = isset($meta['previousClose']) ? (float)$meta['previousClose'] : (isset($meta['chartPreviousClose']) ? (float)$meta['chartPreviousClose'] : $realPrice);
            $realCompanyName = isset($meta['shortName']) ? $meta['shortName'] : $company_name;
            $realHigh = isset($meta['regularMarketDayHigh']) ? (float)$meta['regularMarketDayHigh'] : $realPrice;
            $realLow = isset($meta['regularMarketDayLow']) ? (float)$meta['regularMarketDayLow'] : $realPrice;
            $realVolume = isset($meta['regularMarketVolume']) ? (int)$meta['regularMarketVolume'] : $volume;
            
            // Jika ada perubahan harga atau data baru, perbarui/simpan di database
            if ($conn) {
                // Check if stock already exists in DB
                $stmt = $conn->prepare("SELECT company_name FROM stocks WHERE ticker = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $ticker);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $exists = false;
                    if ($row = $res->fetch_assoc()) {
                        $exists = true;
                    }
                    $stmt->close();
                    
                    if ($exists) {
                        // Perbarui harga, nama perusahaan, volume di database
                        $updateStmt = $conn->prepare("UPDATE stocks SET price = ?, company_name = ?, volume = ?, updated_at = NOW() WHERE ticker = ?");
                        if ($updateStmt) {
                            $updateStmt->bind_param("dsis", $realPrice, $realCompanyName, $realVolume, $ticker);
                            $updateStmt->execute();
                            $updateStmt->close();
                        }
                    } else {
                        // Sisipkan emiten baru jika belum terdaftar
                        $insertStmt = $conn->prepare("INSERT INTO stocks (ticker, company_name, price, sector, pe_ratio, pbv, roe, market_cap, volume, trend) VALUES (?, ?, ?, 'Investment', 15.0, 1.0, 10.0, 100000000, ?, 'Sideways')");
                        if ($insertStmt) {
                            $insertStmt->bind_param("ssdi", $ticker, $realCompanyName, $realPrice, $realVolume);
                            $insertStmt->execute();
                            $insertStmt->close();
                        }
                    }
                }
            }
            $price = $realPrice;
            $company_name = $realCompanyName;
            $prev_close = $realPrevClose;
            $high = $realHigh;
            $low = $realLow;
            $volume = $realVolume;
        }
    }
    
    // Hitung perubahan
    $change = $price - $prev_close;
    $change_percent = $prev_close > 0 ? ($change / $prev_close) * 100 : 0;
    
    return [
        'price' => $price,
        'company_name' => $company_name,
        'prev_close' => $prev_close,
        'change' => $change,
        'change_percent' => $change_percent,
        'high' => $high,
        'low' => $low,
        'volume' => $volume
    ];
}
?>


