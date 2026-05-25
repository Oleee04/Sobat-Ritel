<?php
session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require 'config.php';

$brokerSearched = isset($_GET['broker']) && trim($_GET['broker']) !== '';
$selectedBroker = $brokerSearched ? strtoupper(trim(htmlspecialchars($_GET['broker']))) : null;
$ticker         = isset($_GET['ticker']) ? strtoupper(trim(htmlspecialchars($_GET['ticker']))) : null;

if ($selectedBroker) $_SESSION['active_broker'] = $selectedBroker;
if ($ticker)         $_SESSION['active_ticker']  = $ticker;

$brokerNames = [
    'YP' => 'Mirae Asset Sekuritas Indonesia',
    'PD' => 'Indo Premier Sekuritas (IPOT)',
    'XC' => 'Ajaib Sekuritas Asia',
    'XL' => 'Stockbit Sekuritas Digital',
    'CC' => 'Mandiri Sekuritas',
    'NI' => 'BNI Sekuritas',
    'SQ' => 'BCA Sekuritas',
    'OD' => 'BRI Danareksa Sekuritas',
    'DH' => 'Sinarmas Sekuritas',
    'AZ' => 'Sucor Sekuritas',
    'GR' => 'Panin Sekuritas',
    'KK' => 'Phillip Sekuritas Indonesia',
    'CP' => 'Valbury Sekuritas Indonesia',
    'XA' => 'NH Korindo Sekuritas',
    'EP' => 'MNC Sekuritas',
    'LG' => 'Trimegah Sekuritas Indonesia',
    'LS' => 'Reliance Sekuritas',
    'SH' => 'Artha Sekuritas',
    'AT' => 'Phintraco Sekuritas',
    'HP' => 'Henan Putihrai Sekuritas',
    'IU' => 'Indo Capital Sekuritas',
    'YU' => 'CGS-CIMB Sekuritas',
    'DR' => 'RHB Sekuritas Indonesia',
    'MU' => 'Minna Padi Investama Sekuritas',
    'MG' => 'Semesta Indovest Sekuritas',
    'BK' => 'J.P. Morgan Sekuritas',
    'AK' => 'UBS Sekuritas Indonesia',
    'ZP' => 'Maybank Sekuritas Indonesia',
    'CS' => 'Credit Suisse Sekuritas',
    'KZ' => 'CLSA Sekuritas Indonesia',
    'IF' => 'Ciptadana Sekuritas'
];

function formatMoneyCompact($val) {
    $abs  = abs($val);
    $sign = $val < 0 ? '-' : '';
    if ($abs >= 1_000_000_000_000) return $sign . 'Rp ' . number_format($abs / 1_000_000_000_000, 2, ',', '.') . 'T';
    if ($abs >= 1_000_000_000)     return $sign . 'Rp ' . number_format($abs / 1_000_000_000,     1, ',', '.') . 'B';
    if ($abs >= 1_000_000)         return $sign . 'Rp ' . number_format($abs / 1_000_000,         1, ',', '.') . 'M';
    return $sign . 'Rp ' . number_format($abs, 0, ',', '.');
}

$accumulatedStocks = [];
$distributedStocks = [];

if ($brokerSearched) {
    $idxPool = [
        ['ticker'=>'BBCA','name'=>'Bank Central Asia'],
        ['ticker'=>'BBRI','name'=>'Bank Rakyat Indonesia'],
        ['ticker'=>'BMRI','name'=>'Bank Mandiri'],
        ['ticker'=>'TLKM','name'=>'Telkom Indonesia'],
        ['ticker'=>'ASII','name'=>'Astra International'],
        ['ticker'=>'GOTO','name'=>'GoTo Gojek Tokopedia'],
        ['ticker'=>'BUMI','name'=>'Bumi Resources'],
        ['ticker'=>'UNVR','name'=>'Unilever Indonesia'],
        ['ticker'=>'ICBP','name'=>'Indofood CBP'],
        ['ticker'=>'BRIS','name'=>'Bank Syariah Indonesia'],
        ['ticker'=>'ADRO','name'=>'Adaro Energy'],
        ['ticker'=>'INDF','name'=>'Indofood Sukses Makmur'],
        ['ticker'=>'KLBF','name'=>'Kalbe Farma'],
        ['ticker'=>'PGAS','name'=>'Perusahaan Gas Negara'],
        ['ticker'=>'SMGR','name'=>'Semen Indonesia'],
        ['ticker'=>'ANTM','name'=>'Aneka Tambang'],
        ['ticker'=>'MDKA','name'=>'Merdeka Copper Gold'],
        ['ticker'=>'ITMG','name'=>'Indo Tambangraya Megah'],
        ['ticker'=>'INCO','name'=>'Vale Indonesia'],
        ['ticker'=>'SIDO','name'=>'Industri Jamu Sido Muncul'],
    ];

    // Fetch real prices from database to make dummy data realistic
    $realPrices = [];
    $stmt = $conn->prepare("SELECT ticker, price FROM stocks");
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $realPrices[$row['ticker']] = (float)$row['price'];
        }
        $stmt->close();
    }

    srand(crc32($selectedBroker . date('Ymd')));

    $stockFlows = [];
    foreach ($idxPool as $s) {
        // Use real price from DB if available, otherwise random
        $basePrice = isset($realPrices[$s['ticker']]) && $realPrices[$s['ticker']] > 0 
            ? $realPrices[$s['ticker']] 
            : (rand(200, 12000) * 10);
            
        $buyAvg   = round($basePrice * (1 + rand(-30,30)/1000));
        $sellAvg  = round($basePrice * (1 + rand(-30,30)/1000));
        
        $buyLot   = rand(5000, 1_500_000);
        $sellLot  = rand(5000, 1_500_000);
        
        // Value = Lot * 100 * Average Price
        $buyVal   = $buyLot * 100 * $buyAvg;
        $sellVal  = $sellLot * 100 * $sellAvg;
        $netVal   = $buyVal - $sellVal;
        
        $stockFlows[] = [
            'ticker'   => $s['ticker'],
            'name'     => $s['name'],
            'price'    => $basePrice,
            'buy_val'  => $buyVal,
            'sell_val' => $sellVal,
            'net_val'  => $netVal,
            'buy_lot'  => $buyLot,
            'sell_lot' => $sellLot,
            'buy_avg'  => $buyAvg,
            'sell_avg' => $sellAvg,
        ];
    }

    $acc  = array_filter($stockFlows, fn($x) => $x['net_val'] > 0);
    $dist = array_filter($stockFlows, fn($x) => $x['net_val'] < 0);
    usort($acc,  fn($a,$b) => $b['net_val'] <=> $a['net_val']);
    usort($dist, fn($a,$b) => $a['net_val'] <=> $b['net_val']);
    $accumulatedStocks = array_values(array_slice($acc,  0, 14));
    $distributedStocks = array_values(array_slice($dist, 0, 14));
}

$deepDive = ($brokerSearched && $ticker);
$stockPrice = 0; $stockCompanyName = ''; $dailyPoints = [];
$totalNetValue = 0; $totalBuyValue = 0; $totalBuyVolume = 0;
$totalSellValue = 0; $totalSellVolume = 0; $avgPrice = 0;

if ($deepDive) {
    $stockData       = getLiveStockPrice($ticker, $conn);
    $stockPrice      = $stockData['price'];
    $stockCompanyName = $stockData['company_name'];

    srand(crc32($ticker . $selectedBroker . date('Ymd')));
    $cumulativeNet = 0;
    for ($i = 9; $i >= 0; $i--) {
        $dateStr = date('d M', strtotime("-$i days"));
        $action  = (rand(0, 100) > 45) ? 'BUY' : 'SELL';
        $volume  = rand(10000, 180000);
        $price   = round($stockPrice * (1 + (rand(-50, 50) / 1000)));
        $value   = $volume * 100 * $price; // volume is Lot, so value = Lot * 100 * price
        $netFlow = ($action === 'BUY') ? $value : -$value;
        $cumulativeNet += $netFlow;
        $totalNetValue += $netFlow;
        if ($action === 'BUY') { $totalBuyVolume += $volume; $totalBuyValue += $value; }
        else                   { $totalSellVolume += $volume; $totalSellValue += $value; }
        $dailyPoints[] = [
            'date'           => $dateStr,
            'action'         => $action,
            'volume'         => $volume,
            'price'          => $price,
            'buy_vol'        => ($action === 'BUY')  ? $volume : 0,
            'sell_vol'       => ($action === 'SELL') ? $volume : 0,
            'net_flow'       => $netFlow,
            'cumulative_net' => $cumulativeNet,
        ];
    }
    $avgPrice = $totalBuyVolume > 0
        ? $totalBuyValue / $totalBuyVolume
        : ($totalSellVolume > 0 ? $totalSellValue / $totalSellVolume : 0);
}

$selectedBrokerName = $brokerNames[$selectedBroker] ?? ($selectedBroker . ' Sekuritas');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broker Flow Intelligence – Sobat Ritel</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ---------- MODERN PROFESSIONAL THEME (LIGHT MODE) ---------- */
        :root {
            --bg-page: #f7f9fc;
            --bg-card: #ffffff;
            --bg-sidebar: #ffffff;
            --border-light: #eef2f9;
            --border-card: #e9edf4;
            --text-primary: #1e2a3e;
            --text-secondary: #334155;
            --text-muted: #5b6e8c;
            --accent-green: #0f9d58;
            --accent-red: #e53e3e;
            --accent-blue: #2b6ef0;
            --accent-amber: #f39c12;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.02), 0 1px 2px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.03), 0 2px 4px rgba(0, 0, 0, 0.02);
            --radius-lg: 20px;
            --radius-md: 14px;
            --radius-sm: 10px;
            --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --transition: all 0.2s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Global layout inherited from style.css */

        /* ---------- HERO SECTION (Initial) ---------- */
        .hero-section {
            min-height: 70vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafd 100%);
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin: 1rem 0 2rem;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(43, 110, 240, 0.08);
            border: 1px solid rgba(43, 110, 240, 0.15);
            border-radius: 60px;
            padding: 0.4rem 1.2rem;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: var(--accent-blue);
            font-family: var(--font-mono);
            margin-bottom: 1.8rem;
        }
        .hero-badge .dot {
            width: 6px;
            height: 6px;
            background: var(--accent-green);
            border-radius: 50%;
            display: inline-block;
            animation: pulse 1.8s infinite;
        }
        @keyframes pulse {
            0% { opacity: 0.4; transform: scale(0.8); }
            70% { opacity: 1; transform: scale(1.2); }
            100% { opacity: 0.4; transform: scale(0.8); }
        }

        .hero-title {
            font-size: clamp(2.8rem, 8vw, 4.8rem);
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #1e2a3e 0%, #2b6ef0 80%);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
        }
        .hero-title span {
            background: linear-gradient(135deg, #0f9d58, #2b6ef0);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }
        .hero-sub {
            color: var(--text-secondary);
            max-width: 560px;
            margin: 0 auto 2rem;
            font-size: 0.95rem;
        }

        /* Search Box */
        .search-container {
            width: 100%;
            max-width: 560px;
            margin: 0 auto 2rem;
        }
        .search-wrapper {
            background: white;
            border: 1px solid var(--border-card);
            border-radius: 60px;
            padding: 0.2rem;
            display: flex;
            align-items: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        .search-wrapper:focus-within {
            border-color: var(--accent-blue);
            box-shadow: 0 6px 16px rgba(43, 110, 240, 0.08);
        }
        .search-prefix {
            padding: 0 0.8rem 0 1.2rem;
            font-family: var(--font-mono);
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--accent-blue);
        }
        .search-input {
            flex: 1;
            background: none;
            border: none;
            padding: 0.85rem 0;
            font-family: var(--font-mono);
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            outline: none;
            color: var(--text-primary);
        }
        .search-input::placeholder {
            text-transform: none;
            font-weight: 400;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        .search-btn {
            background: var(--accent-blue);
            border: none;
            color: white;
            font-weight: 700;
            font-size: 0.8rem;
            padding: 0.7rem 1.6rem;
            border-radius: 60px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            transition: var(--transition);
        }
        .search-btn:hover {
            background: #1a5ad9;
            transform: translateY(-1px);
        }

        /* Quick links */
        .quick-chips {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.6rem;
            margin-bottom: 1.5rem;
        }
        .quick-chip {
            font-family: var(--font-mono);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.45rem 1rem;
            border-radius: 40px;
            background: white;
            border: 1px solid var(--border-card);
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
        }
        .quick-chip:hover {
            border-color: var(--accent-blue);
            color: var(--accent-blue);
            transform: translateY(-2px);
        }

        /* ---------- BROKER HEADER (Mode B) ---------- */
        .broker-header {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-card);
            padding: 1.2rem 1.8rem;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }
        .broker-avatar {
            width: 54px;
            height: 54px;
            background: rgba(43, 110, 240, 0.08);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-mono);
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--accent-blue);
            border: 1px solid rgba(43, 110, 240, 0.2);
        }
        .broker-info h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }
        .broker-info p {
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        .broker-meta {
            margin-left: auto;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .live-pill {
            background: rgba(15, 157, 88, 0.1);
            border: 1px solid rgba(15, 157, 88, 0.2);
            border-radius: 40px;
            padding: 0.3rem 0.9rem;
            font-size: 0.7rem;
            font-weight: 700;
            font-family: var(--font-mono);
            color: var(--accent-green);
        }
        .change-link {
            background: #f8fafc;
            border: 1px solid var(--border-light);
            border-radius: 40px;
            padding: 0.3rem 0.9rem;
            font-size: 0.7rem;
            text-decoration: none;
            color: var(--text-secondary);
            transition: var(--transition);
        }
        .change-link:hover {
            border-color: var(--accent-blue);
        }

        /* Filter bar */
        .filter-bar {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
            margin-bottom: 1.6rem;
        }
        .filter-select {
            background: white;
            border: 1px solid var(--border-card);
            border-radius: 40px;
            padding: 0.45rem 1.8rem 0.45rem 1rem;
            font-family: var(--font-mono);
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-primary);
            cursor: pointer;
            outline: none;
        }
        .date-chip {
            margin-left: auto;
            background: white;
            border: 1px solid var(--border-card);
            border-radius: 40px;
            padding: 0.45rem 1rem;
            font-size: 0.7rem;
            font-family: var(--font-mono);
            color: var(--text-muted);
        }

        /* Split grid tables */
        .split-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.6rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 900px) {
            .split-grid {
                grid-template-columns: 1fr;
            }
        }
        .data-panel {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-card);
            overflow: hidden;
        }
        .panel-header {
            padding: 1rem 1.2rem;
            border-bottom: 1px solid var(--border-card);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fefefe;
        }
        .panel-title {
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .panel-title.acc { color: var(--accent-green); }
        .panel-title.dist { color: var(--accent-red); }
        .panel-count {
            font-size: 0.7rem;
            font-family: var(--font-mono);
            background: #f1f5f9;
            padding: 0.2rem 0.7rem;
            border-radius: 40px;
        }

        .activity-table {
            width: 100%;
            border-collapse: collapse;
        }
        .activity-table th {
            text-align: left;
            padding: 0.7rem 1rem;
            font-size: 0.65rem;
            font-weight: 700;
            font-family: var(--font-mono);
            letter-spacing: 0.06em;
            color: var(--text-muted);
            background: #fafcff;
            border-bottom: 1px solid var(--border-light);
        }
        .activity-table th:not(:first-child) {
            text-align: right;
        }
        .activity-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-light);
            font-size: 0.8rem;
            transition: background 0.1s;
        }
        .activity-table tr:hover td {
            background: rgba(43, 110, 240, 0.02);
        }
        .ticker-cell {
            font-weight: 700;
            font-family: var(--font-mono);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .val-positive {
            color: var(--accent-green);
            font-weight: 700;
        }
        .val-negative {
            color: var(--accent-red);
            font-weight: 700;
        }
        .lot-number, .avg-number {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            text-align: right;
        }
        .inline-bar {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .bar-bg {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 24px;
            border-radius: 4px;
            opacity: 0.12;
        }

        /* Deep Dive Components */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 1px solid var(--border-card);
            border-radius: 40px;
            padding: 0.45rem 1rem;
            font-size: 0.7rem;
            font-family: var(--font-mono);
            text-decoration: none;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }
        .surv-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-card);
            padding: 1.8rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .ticker-block {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .ticker-icon {
            width: 52px;
            height: 52px;
            background: #f1f5f9;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-family: var(--font-mono);
            color: var(--accent-blue);
        }
        .ticker-info h2 {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 0.2rem;
        }
        .stats-group {
            display: flex;
            gap: 1.2rem;
            background: #f8fafc;
            padding: 0.8rem 1.4rem;
            border-radius: 20px;
        }
        .stats-item {
            text-align: center;
        }
        .stats-label {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
        }
        .stats-value {
            font-weight: 800;
            font-size: 1.2rem;
            font-family: var(--font-mono);
        }
        .chart-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 800px) {
            .chart-row {
                grid-template-columns: 1fr;
            }
        }
        .chart-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-md);
            padding: 1.2rem;
        }
        .ledger-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-card);
            overflow-x: auto;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ledger-table th {
            text-align: left;
            padding: 0.8rem 1.2rem;
            font-size: 0.65rem;
            font-weight: 700;
            font-family: var(--font-mono);
            background: #fafcff;
            border-bottom: 1px solid var(--border-light);
        }
        .ledger-table td {
            padding: 0.8rem 1.2rem;
            border-bottom: 1px solid var(--border-light);
            font-size: 0.75rem;
        }
        .badge-action {
            display: inline-block;
            padding: 0.2rem 0.7rem;
            border-radius: 40px;
            font-size: 0.65rem;
            font-weight: 700;
            font-family: var(--font-mono);
        }
        .badge-buy {
            background: rgba(15, 157, 88, 0.1);
            color: var(--accent-green);
        }
        .badge-sell {
            background: rgba(229, 62, 62, 0.1);
            color: var(--accent-red);
        }

        /* misc */
        .text-mono {
            font-family: var(--font-mono);
        }
        .refresh-timer {
            position: fixed;
            bottom: 1.2rem;
            right: 1.2rem;
            background: white;
            border-radius: 60px;
            padding: 0.4rem 1rem;
            font-size: 0.65rem;
            font-family: var(--font-mono);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            z-index: 99;
        }
        @media (max-width: 640px) {
            .dashboard-wrapper {
                padding: 1rem;
            }
            .stats-group {
                flex-wrap: wrap;
                width: 100%;
            }
        }
    </style>
</head>
<body class="dashboard-body">

    <!-- sidebar (unchanged structure) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="assets/nyangkuters_logo.png" alt="Sobat Ritel" style="width:32px;height:32px;border-radius:8px;object-fit:cover;">
            <h2>Sobat Ritel</h2>
            <button class="sidebar-close-btn" id="sidebar-close-btn"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="scanner.php"><i class="fa-solid fa-binoculars"></i> Market Scanner</a></li>
            <li><a href="screener.php"><i class="fa-solid fa-filter"></i> Screener</a></li>
            <li><a href="technical.php"><i class="fa-solid fa-chart-line"></i> Technical</a></li>
            <li class="active"><a href="broker.php"><i class="fa-solid fa-user-tie"></i> Broker Stalker</a></li>
            <li><a href="bandarmology.php"><i class="fa-solid fa-user-shield"></i> Bandarmology</a></li>
            <li><a href="calculators.php"><i class="fa-solid fa-calculator"></i> Calculators</a></li>
            <li><a href="news.php"><i class="fa-solid fa-newspaper"></i> News</a></li>
            
            <li><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
            <li><a href="logout.php" style="color: #f87171; font-weight: 600;"><i class="fa-solid fa-power-off"></i> Logout</a></li>
        </ul>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                <div class="user-details">
                    <span class="name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <span class="role">Professional</span>
                </div>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="hamburger-btn" id="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
            <div class="search-bar">
                <form action="broker.php" method="GET">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="broker" placeholder="Cari kode broker… (CC, YP, BK)"
                           value="<?php echo htmlspecialchars($selectedBroker ?? ''); ?>"
                           onkeyup="this.value=this.value.toUpperCase();">
                </form>
            </div>
            <div class="topbar-actions">
                <button class="icon-btn"><i class="fa-regular fa-bell"></i><span class="badge">3</span></button>
                <div class="date-display"><i class="fa-regular fa-calendar"></i> <?php echo date('d M Y'); ?></div>
            </div>
        </header>

        <div class="dashboard-wrapper">

        <?php if (!$brokerSearched): ?>
            <!-- ========= MODE A – HERO ========= -->
            <div class="hero-section">
                <div class="hero-badge">
                    <span class="dot"></span>
                    IDX Trade Flow Monitor
                </div>
                <h1 class="hero-title">Broker <span>Flow Intelligence</span></h1>
                <p class="hero-sub">Lacak akumulasi dan distribusi saham oleh broker institusi & ritel secara real-time.</p>

                <div class="search-container">
                    <form action="broker.php" method="GET">
                        <div class="search-wrapper">
                            <span class="search-prefix">IDX://</span>
                            <input type="text" name="broker" class="search-input"
                                   placeholder="Kode broker, contoh: CC · YP · BK"
                                   required autofocus
                                   onkeyup="this.value=this.value.toUpperCase();" maxlength="4">
                            <button type="submit" class="search-btn">
                                <i class="fa-solid fa-chart-simple"></i> Lacak
                            </button>
                        </div>
                    </form>
                </div>

                <div class="quick-chips">
                    <?php foreach(['YP','MG','PD','CC','BK','AK','ZP','OD','KK','XL'] as $qb): ?>
                        <a href="broker.php?broker=<?php echo urlencode($qb); ?>" class="quick-chip"><?php echo $qb; ?></a>
                    <?php endforeach; ?>
                </div>
                <p class="text-mono" style="font-size:0.65rem; color:var(--text-muted);">YP: Mirae | CC: Mandiri Sekuritas | BK: J.P. Morgan | ZP: Maybank</p>
            </div>

        <?php elseif ($brokerSearched && !$deepDive): ?>
            <!-- ========= MODE B – BROKER ACTIVITY ========= -->
            <div class="broker-header">
                <div class="broker-avatar"><?php echo htmlspecialchars($selectedBroker); ?></div>
                <div class="broker-info">
                    <h3><?php echo htmlspecialchars($selectedBroker); ?> — <?php echo htmlspecialchars($selectedBrokerName); ?></h3>
                    <p>Bursa Efek Indonesia · Aktivitas perdagangan terkini</p>
                </div>
                <div class="broker-meta">
                    <div class="live-pill">
                        <i class="fa-solid fa-circle" style="font-size:0.45rem; color:var(--accent-green); margin-right:4px;"></i> LIVE STREAM
                    </div>
                    <a href="broker.php" class="change-link"><i class="fa-solid fa-rotate-left"></i> Ganti Broker</a>
                </div>
            </div>

            <div class="filter-bar">
                <select class="filter-select" id="filterInvestor">
                    <option value="all">SEMUA INVESTOR</option>
                    <option value="asing">ASING</option>
                    <option value="domestik">DOMESTIK</option>
                </select>
                <select class="filter-select" id="filterMarket">
                    <option value="all">SEMUA MARKET</option>
                    <option value="regular">REGULAR</option>
                    <option value="negotiated">NEGOTIATED</option>
                </select>
                <select class="filter-select" id="filterView">
                    <option value="net">NET FLOW</option>
                    <option value="buy">GROSS BUY</option>
                    <option value="sell">GROSS SELL</option>
                </select>
                <div class="date-chip">
                    <i class="fa-regular fa-calendar"></i> <?php echo date('d M Y'); ?>
                </div>
            </div>

            <div class="split-grid">
                <!-- ACCUMULATION PANEL -->
                <div class="data-panel">
                    <div class="panel-header">
                        <span class="panel-title acc"><i class="fa-solid fa-chart-line"></i> Akumulasi Net</span>
                        <span class="panel-count"><?php echo count($accumulatedStocks); ?> Saham</span>
                    </div>
                    <table class="activity-table" id="buyTable">
                        <thead>
                            <tr><th style="width:28px;">#</th><th>SAHAM</th><th>NILAI (RP)</th><th>LOT</th><th>HARGA RATA2</th></tr>
                        </thead>
                        <tbody id="buyTableBody">
                        <?php
                        $investorTypes = ['asing', 'domestik'];
                        $marketTypes   = ['regular', 'negotiated'];
                        foreach ($accumulatedStocks as $i => $s):
                            $maxBuy  = $accumulatedStocks[0]['buy_val'];
                            $pct     = $maxBuy > 0 ? ($s['buy_val'] / $maxBuy * 100) : 0;
                            $v       = $s['buy_val'];
                            $valStr  = $v >= 1e9 ? number_format($v/1e9,1,',','.').'B' : number_format($v/1e6,1,',','.').'M';
                            $l       = $s['buy_lot'];
                            $lotStr  = $l >= 1e6 ? number_format($l/1e6,1,',','.').'M' : ($l >= 1000 ? number_format($l/1000,1,',','.').'K' : number_format($l,0,',','.'));
                            $inv  = $investorTypes[crc32($s['ticker'].'inv'.$selectedBroker) % 2];
                            $mkt  = $marketTypes[crc32($s['ticker'].'mkt'.$selectedBroker) % 2];
                            $netVal  = $s['buy_val'] - round($s['buy_val'] * 0.3);
                            $netStr  = ($netVal >= 0 ? '+' : '') . (abs($netVal) >= 1e9 ? number_format(abs($netVal)/1e9,1,',','.').'B' : number_format(abs($netVal)/1e6,1,',','.').'M');
                        ?>
                            <tr onclick="location.href='broker.php?broker=<?php echo urlencode($selectedBroker); ?>&ticker=<?php echo urlencode($s['ticker']); ?>';"
                                data-investor="<?php echo $inv; ?>"
                                data-market="<?php echo $mkt; ?>"
                                data-val-net="<?php echo $netStr; ?>"
                                data-val-buy="<?php echo $valStr; ?>"
                                data-val-sell="<?php echo $valStr; ?>">
                                <td class="text-mono" style="font-size:0.7rem; color:var(--text-muted);"><?php echo $i+1; ?></td>
                                <td>
                                    <div class="inline-bar">
                                        <div class="bar-bg" style="width:<?php echo $pct; ?>%; background:var(--accent-green);"></div>
                                        <span class="ticker-cell"><?php echo htmlspecialchars($s['ticker']); ?></span>
                                    </div>
                                </td>
                                <td class="val-positive val-cell"><?php echo $valStr; ?></td>
                                <td class="lot-number"><?php echo $lotStr; ?></td>
                                <td class="avg-number"><?php echo number_format($s['buy_avg'],0,',','.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- DISTRIBUTION PANEL -->
                <div class="data-panel">
                    <div class="panel-header">
                        <span class="panel-title dist"><i class="fa-solid fa-chart-line"></i> Distribusi Net</span>
                        <span class="panel-count"><?php echo count($distributedStocks); ?> Saham</span>
                    </div>
                    <table class="activity-table" id="sellTable">
                        <thead><tr><th style="width:28px;">#</th><th>SAHAM</th><th>NILAI (RP)</th><th>LOT</th><th>HARGA RATA2</th></tr></thead>
                        <tbody id="sellTableBody">
                        <?php foreach ($distributedStocks as $i => $s):
                            $maxSell = $distributedStocks[0]['sell_val'];
                            $pct     = $maxSell > 0 ? ($s['sell_val'] / $maxSell * 100) : 0;
                            $v       = $s['sell_val'];
                            $valStr  = $v >= 1e9 ? number_format($v/1e9,1,',','.').'B' : number_format($v/1e6,1,',','.').'M';
                            $l       = $s['sell_lot'];
                            $lotStr  = $l >= 1e6 ? number_format($l/1e6,1,',','.').'M' : ($l >= 1000 ? number_format($l/1000,1,',','.').'K' : number_format($l,0,',','.'));
                            $inv     = $investorTypes[crc32($s['ticker'].'inv'.$selectedBroker) % 2];
                            $mkt     = $marketTypes[crc32($s['ticker'].'mkt'.$selectedBroker) % 2];
                            $netVal  = -(round($s['sell_val'] * 0.7));
                            $netStr  = '-' . (abs($netVal) >= 1e9 ? number_format(abs($netVal)/1e9,1,',','.').'B' : number_format(abs($netVal)/1e6,1,',','.').'M');
                        ?>
                            <tr onclick="location.href='broker.php?broker=<?php echo urlencode($selectedBroker); ?>&ticker=<?php echo urlencode($s['ticker']); ?>';"
                                data-investor="<?php echo $inv; ?>"
                                data-market="<?php echo $mkt; ?>"
                                data-val-net="<?php echo $netStr; ?>"
                                data-val-buy="<?php echo $valStr; ?>"
                                data-val-sell="<?php echo $valStr; ?>">
                                <td class="text-mono" style="font-size:0.7rem; color:var(--text-muted);"><?php echo $i+1; ?></td>
                                <td>
                                    <div class="inline-bar">
                                        <div class="bar-bg" style="width:<?php echo $pct; ?>%; background:var(--accent-red);"></div>
                                        <span class="ticker-cell"><?php echo htmlspecialchars($s['ticker']); ?></span>
                                    </div>
                                </td>
                                <td class="val-negative val-cell"><?php echo $valStr; ?></td>
                                <td class="lot-number"><?php echo $lotStr; ?></td>
                                <td class="avg-number"><?php echo number_format($s['sell_avg'],0,',','.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
            (function(){
                const selInv = document.getElementById('filterInvestor');
                const selMkt = document.getElementById('filterMarket');
                const selView = document.getElementById('filterView');
                const buyBody = document.getElementById('buyTableBody');
                const sellBody = document.getElementById('sellTableBody');

                const viewLabels = {
                    net:  { buy: 'NET B.VAL',  sell: 'NET S.VAL' },
                    buy:  { buy: 'GROSS B.VAL', sell: 'GROSS S.VAL' },
                    sell: { buy: 'GROSS B.VAL', sell: 'GROSS S.VAL' }
                };
                function applyFilters(){
                    const inv = selInv.value;
                    const mkt = selMkt.value;
                    const view = selView.value;

                    [buyBody, sellBody].forEach(tbody => {
                        let rank = 1;
                        Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
                            const rowInv = tr.dataset.investor;
                            const rowMkt = tr.dataset.market;
                            const visible = (inv === 'all' || rowInv === inv) && (mkt === 'all' || rowMkt === mkt);
                            tr.style.display = visible ? '' : 'none';
                            if(visible){
                                const rankCell = tr.querySelector('td:first-child');
                                if(rankCell) rankCell.textContent = rank++;
                                const valCell = tr.querySelector('.val-cell');
                                if(valCell){
                                    if(view === 'net'){
                                        valCell.textContent = tr.dataset.valNet;
                                        valCell.style.color = tr.dataset.valNet.startsWith('-') ? 'var(--accent-red)' : 'var(--accent-green)';
                                    } else if(view === 'buy'){
                                        valCell.textContent = tr.dataset.valBuy;
                                        valCell.style.color = '';
                                    } else {
                                        valCell.textContent = tr.dataset.valSell;
                                        valCell.style.color = '';
                                    }
                                }
                            }
                        });
                    });
                    const ths = document.querySelectorAll('.activity-table thead th:nth-child(3)');
                    const labels = viewLabels[view];
                    if(ths.length >= 2){
                        ths[0].textContent = labels.buy;
                        ths[1].textContent = labels.sell;
                    }
                }
                selInv.addEventListener('change', applyFilters);
                selMkt.addEventListener('change', applyFilters);
                selView.addEventListener('change', applyFilters);
                applyFilters();
            })();
            </script>

        <?php elseif ($brokerSearched && $deepDive): ?>
            <!-- ========= MODE C – DEEP DIVE ========= -->
            <a href="broker.php?broker=<?php echo urlencode($selectedBroker); ?>" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke ringkasan <?php echo htmlspecialchars($selectedBroker); ?>
            </a>

            <div class="surv-card">
                <div class="ticker-block">
                    <div class="ticker-icon"><?php echo substr($ticker,0,2); ?></div>
                    <div class="ticker-info">
                        <h2><?php echo htmlspecialchars($ticker); ?> <span style="font-size:0.8rem; font-weight:400;">— <?php echo htmlspecialchars($stockCompanyName); ?></span></h2>
                        <p>Tracking broker <strong><?php echo htmlspecialchars($selectedBroker); ?></strong> · <?php echo htmlspecialchars($selectedBrokerName); ?></p>
                    </div>
                </div>
                <div class="stats-group">
                    <div class="stats-item"><div class="stats-label">Last Price</div><div class="stats-value">Rp <?php echo number_format($stockPrice,0,',','.'); ?></div></div>
                    <div class="stats-item"><div class="stats-label">Net 10D</div><div class="stats-value" style="color:<?php echo $totalNetValue>=0?'var(--accent-green)':'var(--accent-red)'; ?>"><?php echo formatMoneyCompact($totalNetValue); ?></div></div>
                    <div class="stats-item"><div class="stats-label">Avg Buy</div><div class="stats-value">Rp <?php echo number_format($avgPrice,0,',','.'); ?></div></div>
                    <div class="stats-item"><div class="stats-label">Signal</div><div class="stats-value" style="color:<?php echo $totalNetValue>=0?'var(--accent-green)':'var(--accent-red)'; ?>"><?php echo $totalNetValue>=0?'ACCUMULATE':'DISTRIBUTE'; ?></div></div>
                </div>
            </div>

            <div class="chart-row">
                <div class="chart-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                        <span class="text-mono" style="font-weight:700;"><i class="fa-solid fa-chart-column"></i> Buy vs Sell Volume</span>
                        <span><span style="color:var(--accent-green);">■</span> Buy &nbsp; <span style="color:var(--accent-red);">■</span> Sell</span>
                    </div>
                    <div style="height:240px;"><canvas id="volumeChart"></canvas></div>
                </div>
                <div class="chart-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                        <span class="text-mono" style="font-weight:700;"><i class="fa-solid fa-chart-line"></i> Cumulative Net Flow</span>
                        <div class="live-pill" style="font-size:0.55rem;">LIVE STREAM</div>
                    </div>
                    <div style="height:240px;"><canvas id="flowChart"></canvas></div>
                </div>
            </div>

            <div class="ledger-card">
                <div style="padding:1rem 1.2rem; border-bottom:1px solid var(--border-card);">
                    <span class="text-mono" style="font-weight:700;"><i class="fa-regular fa-calendar"></i> Daily Execution Log (10 Hari)</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="ledger-table">
                        <thead><tr><th>Tanggal</th><th>Buy (Lot)</th><th>Sell (Lot)</th><th>Aksi</th><th>Harga Rata2</th></tr></thead>
                        <tbody>
                        <?php foreach($dailyPoints as $p): ?>
                        <tr>
                            <td><?php echo $p['date']; ?></td>
                            <td><?php echo $p['buy_vol']>0 ? number_format($p['buy_vol']) : '—'; ?></td>
                            <td><?php echo $p['sell_vol']>0 ? number_format($p['sell_vol']) : '—'; ?></td>
                            <td><span class="badge-action <?php echo strtolower($p['action']) === 'buy' ? 'badge-buy' : 'badge-sell'; ?>"><?php echo $p['action']; ?></span></td>
                            <td>Rp <?php echo number_format($p['price'],0,',','.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
            Chart.defaults.font.family = "'JetBrains Mono', monospace";
            Chart.defaults.color = '#5b6e8c';
            const dates    = <?php echo json_encode(array_column($dailyPoints,'date')); ?>;
            const buyVols  = <?php echo json_encode(array_column($dailyPoints,'buy_vol')); ?>;
            const sellVols = <?php echo json_encode(array_column($dailyPoints,'sell_vol')); ?>;
            const cumFlows = <?php echo json_encode(array_column($dailyPoints,'cumulative_net')); ?>;

            new Chart(document.getElementById('volumeChart'), {
                type: 'bar',
                data: { labels: dates, datasets: [
                    { label: 'BUY', data: buyVols, backgroundColor: 'rgba(15,157,88,0.5)', borderColor: '#0f9d58', borderWidth: 1, borderRadius: 6 },
                    { label: 'SELL', data: sellVols, backgroundColor: 'rgba(229,62,62,0.5)', borderColor: '#e53e3e', borderWidth: 1, borderRadius: 6 }
                ]},
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { grid: { color: '#eef2f9' }, ticks: { callback: v => v/1000 + 'K' } }, x: { grid: { display: false } } } }
            });
            const ctx = document.getElementById('flowChart').getContext('2d');
            const grad = ctx.createLinearGradient(0,0,0,230);
            grad.addColorStop(0, cumFlows[cumFlows.length-1]>=0 ? 'rgba(15,157,88,0.2)' : 'rgba(229,62,62,0.2)');
            grad.addColorStop(1, 'rgba(0,0,0,0)');
            new Chart(ctx, {
                type: 'line', data: { labels: dates, datasets: [{ label: 'Net Flow', data: cumFlows, borderColor: cumFlows[cumFlows.length-1]>=0 ? '#0f9d58' : '#e53e3e', borderWidth: 2.5, pointRadius: 3, fill: true, backgroundColor: grad, tension: 0.3 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => 'Rp ' + (ctx.parsed.y/1e6).toFixed(1) + 'M' } } }, scales: { y: { ticks: { callback: v => 'Rp '+(v/1e6).toFixed(0)+'M' } } } }
            });
            </script>
        <?php endif; ?>

        </div>
    </main>

    <script src="assets/js/main.js"></script>
    <script>
    // Sidebar toggle
    const sidebar = document.querySelector('.sidebar');
    document.getElementById('hamburger-btn')?.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.getElementById('sidebar-close-btn')?.addEventListener('click', () => sidebar.classList.remove('open'));

    <?php if ($brokerSearched && !$deepDive): ?>
    let countdown = 30;
    const timerDiv = document.createElement('div');
    timerDiv.className = 'refresh-timer';
    timerDiv.innerHTML = '<i class="fa-regular fa-clock"></i> Refresh dalam <span id="cdSec">30</span> detik';
    document.body.appendChild(timerDiv);
    const cdSpan = document.getElementById('cdSec');
    function tickRefresh() {
        countdown--;
        if(cdSpan) cdSpan.innerText = countdown;
        if(countdown <= 0) location.reload();
        else setTimeout(tickRefresh, 1000);
    }
    tickRefresh();
    <?php endif; ?>
    </script>
</body>
</html>


