<?php
require 'config.php';

$csvFile = 'Dataset-Saham-IDX-master/List Emiten/all.csv';
if (!file_exists($csvFile)) {
    die("CSV file not found.");
}

$handle = fopen($csvFile, "r");
$header = fgetcsv($handle); // skip header

// Prepare statement
$sql = "INSERT IGNORE INTO stocks (ticker, company_name, sector, price, pe_ratio, pbv, roe, market_cap, volume, trend) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

$sectors = ['Finance', 'Infrastructures', 'Consumer Cyclicals', 'Technology', 'Consumer Non-Cyclicals', 'Basic Materials', 'Energy', 'Healthcare', 'Industrials', 'Properties & Real Estate', 'Transportation & Logistic'];
$trends = ['Uptrend', 'Downtrend', 'Sideways'];

$count = 0;
while (($row = fgetcsv($handle)) !== FALSE) {
    if (count($row) < 2) continue;
    $ticker = $row[0];
    $name = $row[1];
    
    // Check if already exists
    $checkSql = "SELECT id FROM stocks WHERE ticker = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $ticker);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        continue;
    }
    
    $sector = $sectors[array_rand($sectors)];
    $price = rand(50, 20000);
    $pe_ratio = rand(-50, 100) / 10;
    $pbv = rand(1, 50) / 10;
    $roe = rand(-20, 50) / 10;
    $market_cap = rand(1000000, 1000000000);
    $volume = rand(10000, 100000000);
    $trend = $trends[array_rand($trends)];
    
    $stmt->bind_param("sssidddiis", $ticker, $name, $sector, $price, $pe_ratio, $pbv, $roe, $market_cap, $volume, $trend);
    $stmt->execute();
    $count++;
}
fclose($handle);
echo "Imported $count new stocks.\n";
?>


