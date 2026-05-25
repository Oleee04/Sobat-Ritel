<?php
require 'config.php';

$res = $conn->query("SELECT id, ticker FROM stocks");
$stocks = [];
while ($row = $res->fetch_assoc()) {
    $stocks[] = $row;
}
echo "Found " . count($stocks) . " stocks to update.\n";

$concurrency = 50;
$multi_handle = curl_multi_init();
$active_handles = [];
$index = 0;
$total = count($stocks);
$completed = 0;
$updatedCount = 0;

function addRequest($idx) {
    global $stocks, $multi_handle, $active_handles;
    $ticker = strtoupper(trim($stocks[$idx]['ticker']));
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}.JK";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    curl_multi_add_handle($multi_handle, $ch);
    $active_handles[(int)$ch] = $idx;
}

// Fill initial queue
for (; $index < min($concurrency, $total); $index++) {
    addRequest($index);
}

do {
    $mrc = curl_multi_exec($multi_handle, $active);
} while ($mrc == CURLM_CALL_MULTI_PERFORM);

while ($active && $mrc == CURLM_OK) {
    if (curl_multi_select($multi_handle) != -1) {
        do {
            $mrc = curl_multi_exec($multi_handle, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
    }
    
    while ($info = curl_multi_info_read($multi_handle)) {
        $ch = $info['handle'];
        $idx = $active_handles[(int)$ch];
        $stock = $stocks[$idx];
        $id = $stock['id'];
        
        $response = curl_multi_getcontent($ch);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['chart']['result'][0]['meta']['regularMarketPrice'])) {
                $price = (float)$data['chart']['result'][0]['meta']['regularMarketPrice'];
                $vol = isset($data['chart']['result'][0]['meta']['regularMarketVolume']) ? (int)$data['chart']['result'][0]['meta']['regularMarketVolume'] : 0;
                
                $stmt = $conn->prepare("UPDATE stocks SET price = ?, volume = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bind_param("dii", $price, $vol, $id);
                $stmt->execute();
                $updatedCount++;
            }
        }
        
        curl_multi_remove_handle($multi_handle, $ch);
        curl_close($ch);
        unset($active_handles[(int)$ch]);
        $completed++;
        
        if ($completed % 100 == 0) {
            echo "Processed $completed / $total\n";
        }
        
        if ($index < $total) {
            addRequest($index++);
            do {
                $mrc = curl_multi_exec($multi_handle, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
    }
}
curl_multi_close($multi_handle);
echo "Successfully updated $updatedCount / $total stocks.\n";
?>


