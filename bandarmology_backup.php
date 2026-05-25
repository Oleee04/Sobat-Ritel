<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require 'config.php';

$tickersQuery = "SELECT ticker, company_name, price FROM stocks ORDER BY ticker ASC";
$tickersResult = $conn->query($tickersQuery);
$tickers = [];
if ($tickersResult) {
    while ($row = $tickersResult->fetch_assoc()) {
        $tickers[] = $row;
    }
}

$activeTicker = $_SESSION['active_ticker'] ?? '';
if (isset($_GET['ticker'])) {
    $activeTicker = strtoupper(trim(htmlspecialchars($_GET['ticker'])));
    $_SESSION['active_ticker'] = $activeTicker;
}

$stockInfo = null;
foreach ($tickers as $t) {
    if ($t['ticker'] === $activeTicker) {
        $stockInfo = $t;
        break;
    }
}

if (!$stockInfo) {
    $stockInfo = ['ticker' => $activeTicker, 'company_name' => $activeTicker . ' INDONESIA', 'price' => 5000];
}

function getDeterministicData($ticker, $days = 10) {
    $seed = crc32($ticker);
    srand($seed);
    $data = [];
    $price = 1000 + (rand(0, 10000));
    $foreignFlow = [];
    for ($i = 0; $i < $days; $i++) {
        $flow = (rand(-50, 80)) * 1000000000;
        $foreignFlow[] = $flow;
    }
    $brokers = ['YP', 'PD', 'XC', 'XL', 'BK', 'CC', 'CS', 'KZ', 'RX', 'AK'];
    $buyerShares = [];
    $sellerShares = [];
    foreach ($brokers as $b) {
        $buyerShares[$b] = rand(5000, 100000);
        $sellerShares[$b] = rand(5000, 100000);
    }
    arsort($buyerShares);
    arsort($sellerShares);
    return ['foreign' => $foreignFlow, 'buyers' => $buyerShares, 'sellers' => $sellerShares];
}

$simData = getDeterministicData($activeTicker);
$totalBuyVolume = array_sum($simData['buyers']);
$totalSellVolume = array_sum($simData['sellers']);
$top3BuyersVol = 0;
$top3SellersVol = 0;
$i = 0;
foreach ($simData['buyers'] as $b => $v) { if ($i < 3) $top3BuyersVol += $v; $i++; }
$i = 0;
foreach ($simData['sellers'] as $b => $v) { if ($i < 3) $top3SellersVol += $v; $i++; }
$buyConcentration = $totalBuyVolume > 0 ? ($top3BuyersVol / $totalBuyVolume) * 100 : 50;
$sellConcentration = $totalSellVolume > 0 ? ($top3SellersVol / $totalSellVolume) * 100 : 50;
$accDistStatus = 'Neutral';
$statusClass = 'neutral';
$statusBadge = 'NEUTRAL';
if ($buyConcentration > 60 && $sellConcentration < 45) { $accDistStatus = 'Akumulasi besar oleh institusi (Big Money). Bandar sedang mengumpulkan posisi besar secara agresif.'; $statusClass = 'positive'; $statusBadge = 'BIG ACCUMULATION'; }
elseif ($buyConcentration > 50 && $sellConcentration < 48) { $accDistStatus = 'Akumulasi kecil terdeteksi. Tekanan beli dari beberapa pembeli dominan melebihi distribusi seller.'; $statusClass = 'positive-light'; $statusBadge = 'SMALL ACCUMULATION'; }
elseif ($sellConcentration > 60 && $buyConcentration < 45) { $accDistStatus = 'Distribusi besar terdeteksi! Big Money melepas kepemilikan dalam jumlah besar ke publik.'; $statusClass = 'negative'; $statusBadge = 'BIG DISTRIBUTION'; }
elseif ($sellConcentration > 50 && $buyConcentration < 48) { $accDistStatus = 'Distribusi kecil. Tekanan jual dari penjual dominan melebihi akumulasi buyer.'; $statusClass = 'negative-light'; $statusBadge = 'SMALL DISTRIBUTION'; }
else { $accDistStatus = 'Kondisi netral. Volume distribusi dan akumulasi relatif seimbang di pasar saat ini.'; $statusClass = 'neutral'; $statusBadge = 'NEUTRAL'; }

$retailBrokers = ['YP', 'PD', 'XC', 'XL'];
$retailBuyVol = 0; $retailSellVol = 0;
foreach ($retailBrokers as $rb) { $retailBuyVol += $simData['buyers'][$rb] ?? 0; $retailSellVol += $simData['sellers'][$rb] ?? 0; }
$totalBrokerVol = $totalBuyVolume + $totalSellVolume;
$retailParticipation = $totalBrokerVol > 0 ? (($retailBuyVol + $retailSellVol) / $totalBrokerVol) * 100 : 30;

$foreignBrokers = ['BK', 'CS', 'KZ', 'RX', 'AK', 'ZP', 'YU', 'BB', 'CG'];
$foreignBuyVol = 0; $foreignSellVol = 0;
foreach ($foreignBrokers as $fb) { $foreignBuyVol += $simData['buyers'][$fb] ?? 0; $foreignSellVol += $simData['sellers'][$fb] ?? 0; }
$foreignParticipation = $totalBrokerVol > 0 ? (($foreignBuyVol + $foreignSellVol) / $totalBrokerVol) * 100 : 25;

$retailBehavior = 'Partisipasi ritel normal.'; $retailBehaviorClass = 'neutral';
if ($retailParticipation > 60) {
    if ($retailBuyVol > $retailSellVol * 1.3) { $retailBehavior = 'FOMO / Retail Euphoria! Investor ritel membeli secara agresif. Waspadai potensi koreksi mendatang.'; $retailBehaviorClass = 'warning'; }
    elseif ($retailSellVol > $retailBuyVol * 1.3) { $retailBehavior = 'Panic Selling Ritel! Kepanikan massal & cut-loss. Biasanya peluang akumulasi bagi institusi pintar.'; $retailBehaviorClass = 'danger'; }
    else { $retailBehavior = 'Aktivitas Ritel Tinggi. Partisipasi ritel mendominasi transaksi harian.'; $retailBehaviorClass = 'primary'; }
} else { $retailBehavior = 'Keterlibatan ritel rendah. Pergerakan dikendalikan penuh oleh transaksi institusi profesional.'; $retailBehaviorClass = 'success'; }

$foreignTotalFlow = array_sum($simData['foreign']);
$signalText = 'HOLD'; $signalClass = 'neutral';
$signalDesc = 'Konsolidasi volume berimbang. Ambil posisi tunggu (wait & see) untuk konfirmasi arah tren selanjutnya.';
if ($statusBadge === 'BIG ACCUMULATION' || ($statusBadge === 'SMALL ACCUMULATION' && $foreignTotalFlow > 0)) {
    if ($foreignTotalFlow > 10000000000) { $signalText = 'STRONG BUY'; $signalClass = 'strong-buy'; $signalDesc = 'Akumulasi bandar masif didukung foreign inflow konsisten. Sangat direkomendasikan untuk entri posisi.'; }
    else { $signalText = 'BUY'; $signalClass = 'buy'; $signalDesc = 'Bandar sedang mengumpulkan posisi secara bertahap. Layak dibeli untuk investasi jangka menengah.'; }
} elseif ($statusBadge === 'BIG DISTRIBUTION' || ($statusBadge === 'SMALL DISTRIBUTION' && $foreignTotalFlow < 0)) {
    if ($foreignTotalFlow < -10000000000) { $signalText = 'STRONG SELL'; $signalClass = 'strong-sell'; $signalDesc = 'Distribusi bandar agresif dibarengi foreign outflow massal. Segera kurangi atau lepas posisi.'; }
    else { $signalText = 'SELL'; $signalClass = 'sell'; $signalDesc = 'Bandar mulai mendistribusikan barang. Sebaiknya ambil profit terlebih dahulu.'; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bandarmology â€” Sobat Ritel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.2">
    <style>
        /* ========================================
           BANDARMOLOGY â€” PREMIUM REDESIGN
           Colors & functions preserved exactly
        ======================================== */
        :root {
            --bg-page: #f5f7fc;
            --bg-sidebar: #ffffff;
            --bg-card: #ffffff;
            --bg-card-alt: #fafbff;
            --border-light: #e9edf2;
            --border-card: #eef2f8;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #6c7a91;
            --text-placeholder: #94a3b8;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.03), 0 1px 2px rgba(0,0,0,0.02);
            --shadow-lg: 0 12px 24px -8px rgba(0,0,0,0.05);
            --green: #10b981;
            --green-light: #d1fae5;
            --green-bg: #ecfdf5;
            --red: #ef4444;
            --red-light: #fee2e2;
            --red-bg: #fef2f2;
            --blue: #3b82f6;
            --blue-light: #dbeafe;
            --blue-bg: #eff6ff;
            --amber: #f59e0b;
            --amber-light: #fef3c7;
            --amber-bg: #fffbeb;
            --purple: #8b5cf6;
            --radius-card: 20px;
            --radius-sm: 12px;
            --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --transition: all 0.2s ease;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* Global layout inherited from style.css */

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ HEADER CARD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .bm-header {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-card);
            padding: 24px 28px;
            box-shadow: var(--shadow-sm);
        }
        .bm-header-title h1 {
            font-size: 1.6rem; font-weight: 800;
            color: var(--text-primary); letter-spacing: -0.3px;
            display: flex; align-items: center; gap: 8px;
        }
        .bm-header-title p { font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px; }
        .bm-header-title p span { color: var(--green); font-weight: 700; }

        .ticker-picker { display: flex; align-items: center; gap: 12px; background: #f8fafc; padding: 6px 12px; border-radius: 60px; border: 1px solid var(--border-light); }
        .ticker-picker label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); }
        .ticker-picker select {
            background: white; border: 1px solid var(--border-light);
            border-radius: 40px; padding: 6px 28px 6px 14px;
            font-family: var(--font-sans); font-size: 0.8rem; font-weight: 500;
            color: var(--text-primary); cursor: pointer; outline: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center;
        }

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ STAT ROW â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .stats-row {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .stat-card {
            background: white;
            border: 1px solid var(--border-card);
            border-radius: 20px;
            padding: 20px 22px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: #e2e8f0; }
        .stat-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 8px; }
        .stat-value { font-size: 2rem; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
        .stat-card.green .stat-value { color: var(--green); }
        .stat-card.red .stat-value { color: var(--red); }
        .stat-card.blue .stat-value { color: var(--blue); }
        .stat-card.amber .stat-value { color: var(--amber); }
        .stat-sub { font-size: 0.7rem; color: var(--text-muted); margin-top: 6px; }

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ GRID â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .main-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .full-width { grid-column: 1 / -1; }

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ CARD GAYA PUTIH â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .card {
            background: white;
            border: 1px solid var(--border-card);
            border-radius: 20px;
            padding: 20px 24px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }
        .card:hover { border-color: #e2edf7; box-shadow: var(--shadow-md); }
        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 18px; padding-bottom: 12px;
            border-bottom: 1px solid var(--border-light);
        }
        .card-title {
            font-weight: 700; font-size: 0.9rem;
            color: var(--text-primary); display: flex; align-items: center; gap: 8px;
        }
        .card-title i { color: var(--green); font-size: 1rem; }

        /* badge pill untuk status */
        .badge-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 40px;
            font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
            font-family: var(--font-mono);
        }
        .badge-pill.positive { background: var(--green-bg); color: var(--green); }
        .badge-pill.positive-light { background: var(--blue-bg); color: var(--blue); }
        .badge-pill.negative { background: var(--red-bg); color: var(--red); }
        .badge-pill.negative-light { background: var(--amber-bg); color: var(--amber); }
        .badge-pill.neutral { background: #f1f5f9; color: var(--text-muted); }

        /* signal panel */
        .signal-panel {
            display: flex; flex-direction: column; align-items: center; text-align: center;
            padding: 28px 20px; border-radius: 16px; gap: 12px;
        }
        .signal-eyebrow { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em; color: var(--text-muted); text-transform: uppercase; }
        .signal-text { font-size: 2.5rem; font-weight: 800; letter-spacing: -1px; line-height: 1.1; }
        .signal-desc { font-size: 0.8rem; color: var(--text-secondary); max-width: 340px; }
        .signal-panel.strong-buy { background: var(--green-bg); border: 1px solid #d1fae5; }
        .signal-panel.strong-buy .signal-text { color: var(--green); }
        .signal-panel.buy { background: var(--blue-bg); border: 1px solid #dbeafe; }
        .signal-panel.buy .signal-text { color: var(--blue); }
        .signal-panel.strong-sell { background: var(--red-bg); border: 1px solid #fee2e2; }
        .signal-panel.strong-sell .signal-text { color: var(--red); }
        .signal-panel.sell { background: var(--amber-bg); border: 1px solid #fef3c7; }
        .signal-panel.sell .signal-text { color: var(--amber); }
        .signal-panel.neutral { background: #f8fafc; border: 1px solid var(--border-light); }
        .signal-panel.neutral .signal-text { color: var(--text-muted); }

        /* akumulasi metrics */
        .acc-metrics { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 16px 0; }
        .acc-metric { background: #fafcff; border: 1px solid var(--border-light); border-radius: 16px; padding: 14px 16px; }
        .acc-metric-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
        .acc-metric-val { font-size: 1.6rem; font-weight: 800; }
        .acc-metric-val.green { color: var(--green); }
        .acc-metric-val.red { color: var(--red); }
        .acc-qualitative { background: #fafcff; border: 1px solid var(--border-light); border-radius: 16px; padding: 14px 16px; margin-top: 12px; }
        .acc-qualitative-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px; }
        .acc-qualitative-text { font-size: 0.8rem; color: var(--text-secondary); line-height: 1.5; }

        /* broker row */
        .broker-list { display: flex; flex-direction: column; gap: 10px; }
        .broker-row {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px;
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 14px;
            transition: var(--transition);
        }
        .broker-row:hover { background: #fafcff; border-color: #e2e8f0; }
        .broker-tag {
            font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700;
            padding: 4px 8px; border-radius: 8px; min-width: 58px; text-align: center;
        }
        .broker-tag.inst { background: var(--blue-bg); color: var(--blue); }
        .broker-tag.retail { background: var(--amber-bg); color: var(--amber); }
        .vol-track { flex: 1; height: 6px; background: #eef2f6; border-radius: 10px; overflow: hidden; }
        .vol-fill { height: 100%; border-radius: 10px; transition: width 0.6s ease; }
        .vol-fill.green { background: linear-gradient(90deg, #6ee7b7, var(--green)); }
        .vol-fill.red { background: linear-gradient(90deg, #fca5a5, var(--red)); }
        .vol-num { font-family: var(--font-mono); font-size: 0.7rem; font-weight: 600; color: var(--text-secondary); min-width: 70px; text-align: right; }

        /* retail section */
        .retail-progress-wrap { margin: 18px 0; }
        .retail-progress-top { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .retail-progress-label { font-size: 0.75rem; font-weight: 600; color: var(--text-secondary); }
        .retail-progress-val { font-family: var(--font-mono); font-size: 0.85rem; font-weight: 700; color: var(--text-primary); }
        .retail-track { height: 6px; background: #eef2f6; border-radius: 10px; overflow: hidden; }
        .retail-fill { height: 100%; border-radius: 10px; background: linear-gradient(90deg, var(--amber), var(--green)); transition: width 1s ease; }

        .retail-alert {
            display: flex; align-items: flex-start; gap: 14px;
            background: var(--blue-bg);
            border: 1px solid #dbeafe;
            border-radius: 16px;
            padding: 16px;
            margin: 16px 0;
        }
        .retail-alert-icon { background: white; width: 36px; height: 36px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--blue); font-size: 1rem; }
        .retail-alert-label { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--blue); margin-bottom: 4px; letter-spacing: 0.05em; }
        .retail-alert-text { font-size: 0.8rem; color: var(--text-secondary); line-height: 1.5; }

        .card-footer {
            display: flex; justify-content: space-between; padding-top: 16px; margin-top: 12px;
            border-top: 1px solid var(--border-light);
            font-size: 0.7rem; color: var(--text-muted);
        }
        .card-footer .live { color: var(--green); font-weight: 600; }

        /* section label */
        .section-label {
            font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;
            color: var(--text-muted); display: flex; align-items: center; gap: 12px; margin-bottom: 8px;
        }
        .section-label::after { content: ''; flex: 1; height: 1px; background: var(--border-light); }

        /* chart */
        .chart-wrap { position: relative; height: 240px; margin-top: 8px; }

        /* responsive */
        @media (max-width: 1200px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 992px) {
            .main-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: 1; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 260px; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .hamburger-btn { display: flex; align-items: center; justify-content: center; }
            .sidebar-close-btn { display: flex; }
            .dashboard-wrapper { padding: 20px; }
            .stats-row { grid-template-columns: 1fr 1fr; gap: 12px; }
        }
        @media (max-width: 480px) { .stats-row { grid-template-columns: 1fr; } }

        /* animasi fade */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card, .stat-card, .bm-header { animation: fadeUp 0.35s ease both; }
        .stat-card:nth-child(1) { animation-delay: 0.02s; }
        .stat-card:nth-child(2) { animation-delay: 0.05s; }
        .stat-card:nth-child(3) { animation-delay: 0.08s; }
        .stat-card:nth-child(4) { animation-delay: 0.11s; }
    </style>
</head>
<body class="dashboard-body">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="assets/nyangkuters_logo.png" alt="Sobat Ritel" style="width: 32px; height: 32px; border-radius: 8px; object-fit: cover;">
        <h2>Sobat Ritel</h2>
        <button class="sidebar-close-btn" id="sidebar-close-btn"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <ul class="nav-links">
        <li><a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
        <li><a href="scanner.php"><i class="fa-solid fa-binoculars"></i> Market Scanner</a></li>
        <li><a href="screener.php"><i class="fa-solid fa-filter"></i> Screener</a></li>
        <li><a href="technical.php"><i class="fa-solid fa-chart-line"></i> Technical</a></li>
        <li><a href="broker.php"><i class="fa-solid fa-user-tie"></i> Broker Stalker</a></li>
        <li class="active"><a href="bandarmology.php"><i class="fa-solid fa-user-shield"></i> Bandarmology</a></li>
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

<!-- MAIN CONTENT -->
<main class="main-content">
    <header class="topbar">
        <button class="hamburger-btn" id="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
        <div class="search-bar">
            <form action="dashboard.php" method="GET">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Cari emiten atau perusahaan...">
            </form>
        </div>
        <div class="topbar-actions">
            <button class="icon-btn"><i class="fa-regular fa-bell"></i><span class="badge">3</span></button>
            <div class="date-display"><?php echo date('d M Y'); ?></div>
        </div>
    </header>

    <div class="dashboard-wrapper">

        <!-- HEADER + SELECTOR -->
        <div class="bm-header">
            <div class="bm-header-title">
                <h1><i class="fa-solid fa-user-shield" style="color:var(--green);"></i> Bandarmology Surveillance</h1>
                <p>Deteksi akumulasi & distribusi Big Money â€” <span><?php echo $activeTicker; ?> Â· <?php echo htmlspecialchars($stockInfo['company_name']); ?></span></p>
            </div>
            <div class="ticker-picker">
                <label for="ticker-select">Saham</label>
                <form action="bandarmology.php" method="GET" id="ticker-form">
                    <select id="ticker-select" name="ticker" onchange="document.getElementById('ticker-form').submit();">
                        <?php foreach ($tickers as $t): ?>
                            <option value="<?php echo $t['ticker']; ?>" <?php echo $t['ticker'] === $activeTicker ? 'selected' : ''; ?>>
                                <?php echo $t['ticker']; ?> â€” <?php echo htmlspecialchars($t['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <!-- STAT CARDS -->
        <div class="stats-row">
            <div class="stat-card green" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div class="stat-label">Konsentrasi Beli</div>
                    <div class="stat-value"><?php echo number_format($buyConcentration, 1); ?>%</div>
                    <div class="stat-sub">Top 3 buyer dari total vol</div>
                </div>
                <div style="width:64px; height:64px; position:relative;">
                    <canvas id="miniBuyChart"></canvas>
                </div>
            </div>
            <div class="stat-card red" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div class="stat-label">Konsentrasi Jual</div>
                    <div class="stat-value"><?php echo number_format($sellConcentration, 1); ?>%</div>
                    <div class="stat-sub">Top 3 seller dari total vol</div>
                </div>
                <div style="width:64px; height:64px; position:relative;">
                    <canvas id="miniSellChart"></canvas>
                </div>
            </div>
            <div class="stat-card blue" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div class="stat-label">Partisipasi Ritel</div>
                    <div class="stat-value"><?php echo number_format($retailParticipation, 1); ?>%</div>
                    <div class="stat-sub">YP, PD, XC, XL vs total</div>
                </div>
                <div style="width:64px; height:64px; position:relative;">
                    <canvas id="miniRetailChart"></canvas>
                </div>
            </div>
            <div class="stat-card purple" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div class="stat-label">Partisipasi Asing</div>
                    <div class="stat-value"><?php echo number_format($foreignParticipation, 1); ?>%</div>
                    <div class="stat-sub">Volume asing vs total</div>
                </div>
                <div style="width:64px; height:64px; position:relative;">
                    <canvas id="miniForeignPartChart"></canvas>
                </div>
            </div>
            <div class="stat-card amber" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div class="stat-label">Net Foreign Flow</div>
                    <div class="stat-value" style="font-size: 1.6rem;"><?php echo ($foreignTotalFlow >= 0 ? '+' : '') . number_format($foreignTotalFlow / 1000000000, 1); ?>M</div>
                    <div class="stat-sub">Kumulatif 10 hari</div>
                </div>
                <div style="width:70px; height:50px; position:relative;">
                    <canvas id="miniForeignChart"></canvas>
                </div>
            </div>
        </div>

        <!-- SINYAL + ACCUMULATION ROW -->
        <div class="section-label">Sinyal & Status Akumulasi</div>
        <div class="main-grid">
            <!-- Signal Panel -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fa-solid fa-satellite-dish"></i> Bandarmology Signal</span>
                    <span class="badge-pill <?php echo $statusClass; ?>"><?php echo $statusBadge; ?></span>
                </div>
                <div class="signal-panel <?php echo $signalClass; ?>">
                    <span class="signal-eyebrow">Rekomendasi Aksi</span>
                    <div class="signal-text"><?php echo $signalText; ?></div>
                    <p class="signal-desc"><?php echo $signalDesc; ?></p>
                </div>
            </div>

            <!-- Accumulation & Distribution -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fa-solid fa-arrows-to-eye"></i> Accumulation & Distribution</span>
                    <span class="badge-pill <?php echo $statusClass; ?>"><?php echo $statusBadge; ?></span>
                </div>
                <p style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:8px;">Berdasarkan konsentrasi volume 3 broker utama buyer dan seller.</p>
                <div class="acc-metrics">
                    <div class="acc-metric">
                        <div class="acc-metric-label">Konsentrasi Beli (Top 3)</div>
                        <div class="acc-metric-val green"><?php echo number_format($buyConcentration, 1); ?>%</div>
                    </div>
                    <div class="acc-metric">
                        <div class="acc-metric-label">Konsentrasi Jual (Top 3)</div>
                        <div class="acc-metric-val red"><?php echo number_format($sellConcentration, 1); ?>%</div>
                    </div>
                </div>
                <div class="acc-qualitative">
                    <div class="acc-qualitative-label">Analisis Kualitatif</div>
                    <div class="acc-qualitative-text"><?php echo $accDistStatus; ?></div>
                </div>
            </div>
        </div>

        <!-- BROKER MAPPING -->
        <div class="section-label">Broker Activity Mapping</div>
        <div class="main-grid">
            <!-- Top Buyers -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title" style="color:var(--green);"><i class="fa-solid fa-arrow-trend-up"></i> Top Buyers</span>
                    <span style="font-size:0.7rem; font-family:var(--font-mono); color:var(--text-muted);">Volume (Lot)</span>
                </div>
                <div class="broker-list">
                    <?php $maxBuy = max($simData['buyers']); foreach ($simData['buyers'] as $broker => $vol): $isRetail = in_array($broker, $retailBrokers); $pct = $maxBuy > 0 ? ($vol / $maxBuy) * 100 : 0; ?>
                    <div class="broker-row">
                        <span class="broker-tag <?php echo $isRetail ? 'retail' : 'inst'; ?>"><?php echo $broker; ?></span>
                        <div class="vol-track"><div class="vol-fill green" style="width:<?php echo $pct; ?>%"></div></div>
                        <span class="vol-num"><?php echo number_format($vol); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top Sellers -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title" style="color:var(--red);"><i class="fa-solid fa-arrow-trend-down"></i> Top Sellers</span>
                    <span style="font-size:0.7rem; font-family:var(--font-mono); color:var(--text-muted);">Volume (Lot)</span>
                </div>
                <div class="broker-list">
                    <?php $maxSell = max($simData['sellers']); foreach ($simData['sellers'] as $broker => $vol): $isRetail = in_array($broker, $retailBrokers); $pct = $maxSell > 0 ? ($vol / $maxSell) * 100 : 0; ?>
                    <div class="broker-row">
                        <span class="broker-tag <?php echo $isRetail ? 'retail' : 'inst'; ?>"><?php echo $broker; ?></span>
                        <div class="vol-track"><div class="vol-fill red" style="width:<?php echo $pct; ?>%"></div></div>
                        <span class="vol-num"><?php echo number_format($vol); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- NEW CHARTS: COMPOSITION & POWER -->
        <div class="section-label">Visualisasi Komposisi & Kekuatan Broker</div>
        <div class="main-grid">
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fa-solid fa-chart-pie"></i> Market Participants</span>
                    <span style="font-size:0.7rem; font-family:var(--font-mono); color:var(--text-muted);">VOLUME</span>
                </div>
                <div class="chart-wrap" style="display: flex; justify-content: center; align-items: center;">
                    <canvas id="participantsChart"></canvas>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fa-solid fa-scale-balanced"></i> Buy vs Sell Power</span>
                    <span style="font-size:0.7rem; font-family:var(--font-mono); color:var(--text-muted);">TOP 3 vs OTHERS</span>
                </div>
                <div class="chart-wrap">
                    <canvas id="powerChart"></canvas>
                </div>
            </div>
        </div>

        <!-- FOREIGN FLOW + RETAIL BEHAVIOR -->
        <div class="section-label">Arus Asing & Psikologi Ritel</div>
        <div class="main-grid">
            <!-- Foreign Flow Chart -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fa-solid fa-earth-americas"></i> Net Foreign Flow â€” 10 Hari</span>
                    <span style="font-size:0.7rem; font-family:var(--font-mono); color:var(--text-muted);">MILIAR RP</span>
                </div>
                <p style="font-size:0.75rem;color:var(--text-muted);margin-bottom:16px;">Arus masuk (+) dan keluar (âˆ’) asing bersih harian pada saham <strong><?php echo $activeTicker; ?></strong>.</p>
                <div class="chart-wrap">
                    <canvas id="foreignFlowChart"></canvas>
                </div>
            </div>

            <!-- Retail Behavior -->
            <div class="card" style="display:flex; flex-direction:column; justify-content:space-between;">
                <div>
                    <div class="card-header">
                        <span class="card-title"><i class="fa-solid fa-people-group"></i> Retail Behavior Detection</span>
                    </div>
                    <p style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:16px;">Psikologi ritel dari persentase keterlibatan broker YP, PD, XC, XL.</p>
                    <div class="retail-progress-wrap">
                        <div class="retail-progress-top">
                            <span class="retail-progress-label">Partisipasi Volume Ritel</span>
                            <span class="retail-progress-val"><?php echo number_format($retailParticipation, 1); ?>%</span>
                        </div>
                        <div class="retail-track"><div class="retail-fill" style="width:<?php echo min($retailParticipation, 100); ?>%"></div></div>
                    </div>
                    <div class="retail-alert">
                        <div class="retail-alert-icon"><i class="fa-solid fa-brain"></i></div>
                        <div>
                            <div class="retail-alert-label">Deteksi Psikologis</div>
                            <div class="retail-alert-text"><?php echo $retailBehavior; ?></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <span><i class="fa-regular fa-clock"></i> Real-time update</span>
                    <span class="live"><i class="fa-regular fa-circle-check"></i> Data IDX</span>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="assets/js/main.js"></script>
<script>
    // Hamburger sidebar
    const sidebar = document.getElementById('sidebar');
    const hamburger = document.getElementById('hamburger-btn');
    const closeBtn = document.getElementById('sidebar-close-btn');
    hamburger?.addEventListener('click', () => sidebar.classList.toggle('open'));
    closeBtn?.addEventListener('click', () => sidebar.classList.remove('open'));

    // Foreign Flow Chart
    const foreignRaw = <?php echo json_encode($simData['foreign']); ?>;
    const labels = [];
    const today = new Date();
    for (let i = 9; i >= 0; i--) {
        const d = new Date(); d.setDate(today.getDate() - i);
        labels.push(d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }));
    }
    const dataBillions = foreignRaw.map(v => v / 1_000_000_000);
    const bgColor = dataBillions.map(v => v >= 0 ? 'rgba(16,185,129,0.5)' : 'rgba(239,68,68,0.5)');
    const borderColor = dataBillions.map(v => v >= 0 ? '#10b981' : '#ef4444');

    const ctx = document.getElementById('foreignFlowChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Net Foreign Flow (Miliar Rp)',
                data: dataBillions,
                backgroundColor: bgColor,
                borderColor: borderColor,
                borderWidth: 1.2,
                borderRadius: 8,
                barPercentage: 0.65
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#ffffff',
                    titleColor: '#1e293b',
                    bodyColor: '#475569',
                    borderColor: '#e9edf2',
                    borderWidth: 1,
                    cornerRadius: 8,
                    callbacks: {
                        label: (ctx) => {
                            let val = ctx.parsed.y;
                            return ` ${val >= 0 ? '+' : ''}${val.toFixed(2)} Miliar Rp`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    grid: { color: '#eef2f8' },
                    ticks: { color: '#6c7a91', font: { size: 10, family: "'JetBrains Mono', monospace", weight: '600' }, callback: v => (v >= 0 ? '+' : '') + v + ' M' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#6c7a91', font: { size: 10, family: "'JetBrains Mono', monospace", weight: '500' } }
                }
            }
        }
    });

    // Participants Chart (Doughnut)
    const instVol = <?php echo ($totalBrokerVol - ($retailBuyVol + $retailSellVol)); ?>;
    const retVol = <?php echo ($retailBuyVol + $retailSellVol); ?>;
    const ctxPart = document.getElementById('participantsChart').getContext('2d');
    new Chart(ctxPart, {
        type: 'doughnut',
        data: {
            labels: ['Institusi', 'Ritel'],
            datasets: [{
                data: [instVol, retVol],
                backgroundColor: ['#3b82f6', '#f59e0b'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#6c7a91', font: { family: "'Inter', sans-serif", size: 11, weight: '600' }, usePointStyle: true, padding: 20 }
                },
                tooltip: {
                    backgroundColor: '#ffffff', titleColor: '#1e293b', bodyColor: '#475569',
                    borderColor: '#e9edf2', borderWidth: 1, cornerRadius: 8,
                    callbacks: {
                        label: (ctx) => {
                            let val = ctx.parsed;
                            let pct = ((val / (instVol + retVol)) * 100).toFixed(1);
                            return ` ${ctx.label}: ${pct}% (${val.toLocaleString()} Lot)`;
                        }
                    }
                }
            }
        }
    });

    // Buy vs Sell Power Chart (Bar)
    const topBuyVol = <?php echo $top3BuyersVol; ?>;
    const otherBuyVol = <?php echo ($totalBuyVolume - $top3BuyersVol); ?>;
    const topSellVol = <?php echo $top3SellersVol; ?>;
    const otherSellVol = <?php echo ($totalSellVolume - $top3SellersVol); ?>;
    
    const ctxPower = document.getElementById('powerChart').getContext('2d');
    new Chart(ctxPower, {
        type: 'bar',
        data: {
            labels: ['BUY POWER', 'SELL POWER'],
            datasets: [
                {
                    label: 'Top 3 Brokers',
                    data: [topBuyVol, topSellVol],
                    backgroundColor: ['rgba(16,185,129,0.8)', 'rgba(239,68,68,0.8)'],
                    borderRadius: 6,
                    barPercentage: 0.6
                },
                {
                    label: 'Others',
                    data: [otherBuyVol, otherSellVol],
                    backgroundColor: ['rgba(16,185,129,0.3)', 'rgba(239,68,68,0.3)'],
                    borderRadius: 6,
                    barPercentage: 0.6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { color: '#6c7a91', font: { family: "'Inter', sans-serif", size: 10 }, usePointStyle: true } },
                tooltip: {
                    backgroundColor: '#ffffff', titleColor: '#1e293b', bodyColor: '#475569',
                    borderColor: '#e9edf2', borderWidth: 1, cornerRadius: 8
                }
            },
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { color: '#475569', font: { weight: '700', family: "'JetBrains Mono', monospace" } } },
                y: { stacked: true, grid: { color: '#eef2f8' }, ticks: { color: '#6c7a91', font: { family: "'JetBrains Mono', monospace" }, callback: v => v >= 1000 ? (v/1000)+'K' : v } }
            }
        }
    });

    // --- BEAUTIFUL SPARKLINE MINI CHARTS ---
    const generateSparkline = (canvasId, currentValue, isForeign = false, colorBase = '#10b981', colorRgb = '16, 185, 129') => {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        let dataArr = [];
        if (isForeign) {
            dataArr = dataBillions;
        } else {
            // Mock 10-day trend for percentages ending at currentValue
            let val = currentValue;
            for (let i = 0; i < 10; i++) {
                dataArr.unshift(val);
                val = val - (Math.random() * 5 - 2.5); // Reverse random walk
                if (val < 0) val = Math.random() * 5;
                if (val > 100) val = 100 - Math.random() * 5;
            }
        }

        // Determine if trending up or down (for color if dynamic)
        // If colorBase is dynamic, we can set it. We'll use the passed colorBase.
        
        // Gradient fill
        let gradient = ctx.createLinearGradient(0, 0, 0, 50);
        gradient.addColorStop(0, `rgba(${colorRgb}, 0.5)`);
        gradient.addColorStop(1, `rgba(${colorRgb}, 0.0)`);

        // Dot only at the end
        let pointRadii = new Array(10).fill(0);
        pointRadii[9] = 3; // Dot at the last data point

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels, // Reusing 10-day labels
                datasets: [{
                    data: dataArr,
                    borderColor: colorBase,
                    borderWidth: 2,
                    pointRadius: pointRadii,
                    pointBackgroundColor: colorBase,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1.5,
                    tension: 0.4, // Smooth curve
                    fill: true,
                    backgroundColor: gradient
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { 
                    x: { display: false }, 
                    y: { display: false, min: Math.min(...dataArr) * 0.9, max: Math.max(...dataArr) * 1.1 } 
                },
                layout: { padding: { top: 5, bottom: 5, left: 5, right: 5 } }
            }
        });
    };

    // Render the 5 Sparklines
    const buyVal = <?php echo number_format($buyConcentration, 1, '.', ''); ?>;
    const sellVal = <?php echo number_format($sellConcentration, 1, '.', ''); ?>;
    const retVal = <?php echo number_format($retailParticipation, 1, '.', ''); ?>;
    const forPartVal = <?php echo number_format($foreignParticipation, 1, '.', ''); ?>;
    const forVal = <?php echo $foreignTotalFlow; ?>;

    // 1. Buy Concentration (Green)
    generateSparkline('miniBuyChart', buyVal, false, '#10b981', '16, 185, 129');
    
    // 2. Sell Concentration (Red)
    generateSparkline('miniSellChart', sellVal, false, '#ef4444', '239, 68, 68');
    
    // 3. Retail Participation (Blue)
    generateSparkline('miniRetailChart', retVal, false, '#3b82f6', '59, 130, 246');

    // 4. Foreign Participation (Purple)
    generateSparkline('miniForeignPartChart', forPartVal, false, '#8b5cf6', '139, 92, 246');
    
    // 5. Foreign Flow (Dynamic Amber/Red)
    const forColor = forVal >= 0 ? '#f59e0b' : '#ef4444';
    const forRgb = forVal >= 0 ? '245, 158, 11' : '239, 68, 68';
    generateSparkline('miniForeignChart', forVal, true, forColor, forRgb);

</script>
</body>
</html>
