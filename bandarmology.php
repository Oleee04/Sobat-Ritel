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
    <title>Bandarmology – Sobat Ritel</title>
    <meta name="description" content="Analisis akumulasi dan distribusi Big Money dengan teknologi Bandarmology untuk saham IDX.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800;14..32,900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.3">
    <style>
        :root {
            --bm-accent-green: #10b981;
            --bm-accent-red: #ef4444;
            --bm-accent-blue: #3b82f6;
            --bm-accent-amber: #f59e0b;
            --bm-accent-purple: #8b5cf6;
            --bm-bg: #f0f4fb;
            --bm-card: #ffffff;
            --bm-border: rgba(37,99,235,0.10);
            --bm-border-strong: #dde4f0;
            --bm-text: #0f172a;
            --bm-text-2: #334155;
            --bm-text-3: #64748b;
            --bm-text-4: #94a3b8;
            --bm-shadow: 0 4px 20px rgba(15,23,42,0.06), 0 1px 4px rgba(15,23,42,0.04);
            --bm-shadow-hover: 0 12px 40px rgba(37,99,235,0.10), 0 2px 8px rgba(15,23,42,0.06);
            --bm-radius: 20px;
            --bm-radius-sm: 14px;
            --bm-radius-xs: 10px;
            --bm-transition: all 0.22s cubic-bezier(.4,0,.2,1);
            --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── BM HERO HEADER ── */
        .bm-hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #0a1628 0%, #0f2044 40%, #0d1f3c 70%, #162036 100%);
            border-radius: var(--bm-radius);
            padding: 30px 34px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 8px 40px rgba(10,22,40,0.28), 0 2px 8px rgba(10,22,40,0.18);
        }
        .bm-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 80% at 85% 50%, rgba(16,185,129,0.08) 0%, transparent 65%),
                radial-gradient(ellipse 40% 60% at 10% 30%, rgba(59,130,246,0.07) 0%, transparent 60%);
            pointer-events: none;
        }
        .bm-hero-grid-bg {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }
        .bm-hero-left { position: relative; z-index: 1; }
        .bm-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(16,185,129,0.12);
            border: 1px solid rgba(16,185,129,0.28);
            border-radius: 40px;
            padding: 5px 14px 5px 8px;
            margin-bottom: 14px;
        }
        .bm-hero-badge-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 0 rgba(16,185,129,0.6);
            animation: pulse-live 1.8s ease-out infinite;
            flex-shrink: 0;
        }
        @keyframes pulse-live {
            0%   { box-shadow: 0 0 0 0 rgba(16,185,129,0.7); }
            70%  { box-shadow: 0 0 0 7px rgba(16,185,129,0); }
            100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
        }
        .bm-hero-badge span {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: #6ee7b7;
        }
        .bm-hero-title {
            font-size: 1.75rem;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: -0.7px;
            display: flex;
            align-items: center;
            gap: 14px;
            line-height: 1.15;
        }
        .bm-hero-icon {
            width: 46px; height: 46px;
            border-radius: 14px;
            background: rgba(16,185,129,0.15);
            border: 1px solid rgba(16,185,129,0.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: #10b981; flex-shrink: 0;
        }
        .bm-hero-subtitle {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.45);
            margin-top: 8px;
            font-weight: 500;
        }
        .bm-hero-subtitle strong { color: rgba(255,255,255,0.75); font-weight: 700; }
        .bm-hero-right { position: relative; z-index: 1; }
        .ticker-selector-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 50px;
            padding: 7px 7px 7px 18px;
            backdrop-filter: blur(12px);
        }
        .ticker-selector-wrap label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: rgba(255,255,255,0.4);
            white-space: nowrap;
        }
        .ticker-selector-wrap select {
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 40px;
            padding: 8px 32px 8px 16px;
            font-family: var(--font-sans);
            font-size: 0.83rem;
            font-weight: 700;
            color: #ffffff;
            cursor: pointer;
            outline: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 11px center;
            -webkit-appearance: none;
            appearance: none;
            transition: var(--bm-transition);
        }
        .ticker-selector-wrap select:hover { background-color: rgba(255,255,255,0.16); }
        .ticker-selector-wrap select option { background: #0f2044; color: #f1f5f9; }

        /* ── SECTION LABEL ── */
        .bm-section-label {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 28px 0 14px;
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--bm-text-3);
        }
        .bm-section-label-bar {
            width: 28px; height: 3px;
            border-radius: 10px;
            background: linear-gradient(90deg, var(--bm-accent-blue), var(--bm-accent-purple));
            flex-shrink: 0;
        }
        .bm-section-label-line {
            flex: 1; height: 1px;
            background: linear-gradient(90deg, var(--bm-border-strong), transparent);
        }

        /* ── STAT CARDS ROW ── */
        .bm-stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
            margin-bottom: 4px;
        }
        .bm-stat {
            background: var(--bm-card);
            border: 1.5px solid var(--bm-border-strong);
            border-radius: var(--bm-radius);
            padding: 20px 18px 16px;
            box-shadow: var(--bm-shadow);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 4px;
            transition: var(--bm-transition);
        }
        .bm-stat::after {
            content: '';
            position: absolute;
            left: 0; top: 15%; bottom: 15%;
            width: 3.5px;
            border-radius: 0 4px 4px 0;
        }
        .bm-stat.s-green::after { background: var(--bm-accent-green); }
        .bm-stat.s-red::after   { background: var(--bm-accent-red); }
        .bm-stat.s-blue::after  { background: var(--bm-accent-blue); }
        .bm-stat.s-purple::after{ background: var(--bm-accent-purple); }
        .bm-stat.s-amber::after { background: var(--bm-accent-amber); }
        .bm-stat:hover {
            transform: translateY(-3px);
            box-shadow: var(--bm-shadow-hover);
            border-color: #c7d8f3;
        }
        /* stat card header row: icon + label */
        .bm-stat-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-left: 2px;
            margin-bottom: 10px;
        }
        .bm-stat-icon-wrap {
            width: 28px; height: 28px;
            border-radius: var(--bm-radius-xs);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem;
            flex-shrink: 0;
        }
        .bm-stat.s-green .bm-stat-icon-wrap { background: #ecfdf5; color: var(--bm-accent-green); }
        .bm-stat.s-red   .bm-stat-icon-wrap { background: #fef2f2; color: var(--bm-accent-red); }
        .bm-stat.s-blue  .bm-stat-icon-wrap { background: #eff6ff; color: var(--bm-accent-blue); }
        .bm-stat.s-purple .bm-stat-icon-wrap { background: #f5f3ff; color: var(--bm-accent-purple); }
        .bm-stat.s-amber  .bm-stat-icon-wrap { background: #fffbeb; color: var(--bm-accent-amber); }
        .bm-stat-label {
            font-size: 0.67rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--bm-text-3);
            flex: 1;
        }
        /* full-width sparkline chart area */
        .bm-sparkline-wrap {
            width: 100%;
            height: 72px;
            position: relative;
            margin-bottom: 10px;
        }
        /* bottom row: sub label + trend badge */
        .bm-stat-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 8px;
            border-top: 1px solid var(--bm-border-strong);
        }
        .bm-stat-sub {
            font-size: 0.64rem;
            color: var(--bm-text-4);
            font-weight: 500;
        }
        .bm-stat-trend {
            font-size: 0.65rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 20px;
        }
        .bm-stat.s-green .bm-stat-trend { color: var(--bm-accent-green); background: #ecfdf5; border: 1px solid #a7f3d0; }
        .bm-stat.s-red   .bm-stat-trend { color: var(--bm-accent-red);   background: #fef2f2; border: 1px solid #fecaca; }
        .bm-stat.s-blue  .bm-stat-trend { color: var(--bm-accent-blue);  background: #eff6ff; border: 1px solid #bfdbfe; }
        .bm-stat.s-purple .bm-stat-trend { color: var(--bm-accent-purple); background: #f5f3ff; border: 1px solid #ddd6fe; }
        .bm-stat.s-amber  .bm-stat-trend { color: var(--bm-accent-amber); background: #fffbeb; border: 1px solid #fde68a; }

        /* ── TWO-COL GRID ── */
        .bm-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .bm-full { grid-column: 1 / -1; }

        /* ── CARD ── */
        .bm-card {
            background: var(--bm-card);
            border: 1.5px solid var(--bm-border-strong);
            border-radius: var(--bm-radius);
            box-shadow: var(--bm-shadow);
            overflow: hidden;
            transition: var(--bm-transition);
        }
        .bm-card:hover {
            border-color: #c7d8f3;
            box-shadow: var(--bm-shadow-hover);
        }
        .bm-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 22px 14px;
            border-bottom: 1.5px solid var(--bm-border-strong);
        }
        .bm-card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--bm-text);
        }
        .bm-card-icon {
            width: 34px; height: 34px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.88rem;
            flex-shrink: 0;
        }
        .bm-card-icon.green  { background: #ecfdf5; color: var(--bm-accent-green); }
        .bm-card-icon.red    { background: #fef2f2; color: var(--bm-accent-red); }
        .bm-card-icon.blue   { background: #eff6ff; color: var(--bm-accent-blue); }
        .bm-card-icon.amber  { background: #fffbeb; color: var(--bm-accent-amber); }
        .bm-card-icon.purple { background: #f5f3ff; color: var(--bm-accent-purple); }
        .bm-card-icon.slate  { background: #f1f5f9; color: var(--bm-text-3); }
        .bm-card-body { padding: 20px 22px; }

        /* ── BADGE PILL ── */
        .bm-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 40px;
            font-size: 0.62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-family: var(--font-mono);
        }
        .bm-pill::before {
            content: '';
            width: 5px; height: 5px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .bm-pill.positive      { background: #ecfdf5; color: var(--bm-accent-green); border: 1px solid #a7f3d0; }
        .bm-pill.positive::before { background: var(--bm-accent-green); }
        .bm-pill.positive-light { background: #eff6ff; color: var(--bm-accent-blue); border: 1px solid #bfdbfe; }
        .bm-pill.positive-light::before { background: var(--bm-accent-blue); }
        .bm-pill.negative      { background: #fef2f2; color: var(--bm-accent-red); border: 1px solid #fecaca; }
        .bm-pill.negative::before { background: var(--bm-accent-red); }
        .bm-pill.negative-light { background: #fffbeb; color: var(--bm-accent-amber); border: 1px solid #fde68a; }
        .bm-pill.negative-light::before { background: var(--bm-accent-amber); }
        .bm-pill.neutral       { background: #f1f5f9; color: var(--bm-text-3); border: 1px solid #e2e8f0; }
        .bm-pill.neutral::before { background: var(--bm-text-3); }

        /* ── SIGNAL PANEL ── */
        .signal-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 28px 20px;
            border-radius: var(--bm-radius-sm);
            position: relative;
            overflow: hidden;
            gap: 8px;
        }
        .signal-display::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 50% 0%, rgba(255,255,255,0.65), transparent 60%);
            pointer-events: none;
        }
        .signal-eyebrow {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--bm-text-3);
            position: relative; z-index: 1;
        }
        .signal-main {
            font-size: 3rem;
            font-weight: 900;
            letter-spacing: -2px;
            line-height: 1;
            position: relative; z-index: 1;
        }
        .signal-sub {
            font-size: 0.8rem;
            color: var(--bm-text-2);
            max-width: 300px;
            line-height: 1.6;
            position: relative; z-index: 1;
        }
        .signal-display.strong-buy { background: linear-gradient(160deg,#ecfdf5,#d1fae5); border: 1.5px solid #a7f3d0; }
        .signal-display.strong-buy .signal-main { color: var(--bm-accent-green); }
        .signal-display.buy        { background: linear-gradient(160deg,#eff6ff,#dbeafe); border: 1.5px solid #bfdbfe; }
        .signal-display.buy        .signal-main { color: var(--bm-accent-blue); }
        .signal-display.strong-sell{ background: linear-gradient(160deg,#fef2f2,#fee2e2); border: 1.5px solid #fecaca; }
        .signal-display.strong-sell .signal-main { color: var(--bm-accent-red); }
        .signal-display.sell       { background: linear-gradient(160deg,#fffbeb,#fef3c7); border: 1.5px solid #fde68a; }
        .signal-display.sell       .signal-main { color: var(--bm-accent-amber); }
        .signal-display.neutral    { background: linear-gradient(160deg,#f8fafc,#f1f5f9); border: 1.5px solid #e2e8f0; }
        .signal-display.neutral    .signal-main { color: var(--bm-text-3); }

        /* ── ACC METRICS ── */
        .acc-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 14px;
        }
        .acc-metric-box {
            background: #f8fafc;
            border: 1.5px solid var(--bm-border-strong);
            border-radius: var(--bm-radius-sm);
            padding: 14px 16px;
            transition: var(--bm-transition);
        }
        .acc-metric-box:hover { background: #fff; box-shadow: var(--bm-shadow); }
        .acc-metric-box-label {
            font-size: 0.64rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--bm-text-3);
            margin-bottom: 6px;
        }
        .acc-metric-box-val {
            font-size: 1.85rem;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -1px;
        }
        .acc-metric-box-val.green { color: var(--bm-accent-green); }
        .acc-metric-box-val.red   { color: var(--bm-accent-red); }
        .acc-qualitative {
            background: #f8fafc;
            border: 1.5px solid var(--bm-border-strong);
            border-radius: var(--bm-radius-sm);
            padding: 14px 16px;
        }
        .acc-qualitative-label {
            font-size: 0.64rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--bm-text-3);
            margin-bottom: 6px;
        }
        .acc-qualitative-text {
            font-size: 0.82rem;
            color: var(--bm-text-2);
            line-height: 1.65;
        }

        /* ── BROKER LIST ── */
        .broker-list { display: flex; flex-direction: column; gap: 7px; }
        .broker-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: #f8fafc;
            border: 1.5px solid var(--bm-border-strong);
            border-radius: var(--bm-radius-xs);
            transition: var(--bm-transition);
        }
        .broker-row:hover {
            background: #fff;
            border-color: #c7d8f3;
            transform: translateX(3px);
            box-shadow: var(--bm-shadow);
        }
        .broker-rank {
            font-family: var(--font-mono);
            font-size: 0.6rem;
            font-weight: 700;
            color: var(--bm-text-4);
            min-width: 18px;
        }
        .broker-tag {
            font-family: var(--font-mono);
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 7px;
            min-width: 48px;
            text-align: center;
        }
        .broker-tag.inst   { background: #eff6ff; color: var(--bm-accent-blue); border: 1px solid #bfdbfe; }
        .broker-tag.retail { background: #fffbeb; color: var(--bm-accent-amber); border: 1px solid #fde68a; }
        .vol-track {
            flex: 1; height: 6px;
            background: #e9eef5;
            border-radius: 10px;
            overflow: hidden;
        }
        .vol-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.9s cubic-bezier(.4,0,.2,1);
        }
        .vol-fill.green { background: linear-gradient(90deg, #a7f3d0, var(--bm-accent-green)); }
        .vol-fill.red   { background: linear-gradient(90deg, #fca5a5, var(--bm-accent-red)); }
        .vol-num {
            font-family: var(--font-mono);
            font-size: 0.68rem;
            font-weight: 600;
            color: var(--bm-text-2);
            min-width: 68px;
            text-align: right;
        }

        /* ── RETAIL BEHAVIOR ── */
        .retail-progress-wrap { margin: 16px 0; }
        .retail-progress-top {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 10px;
        }
        .retail-progress-label { font-size: 0.78rem; font-weight: 600; color: var(--bm-text-2); }
        .retail-progress-val {
            font-family: var(--font-mono);
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--bm-text);
        }
        .retail-track {
            height: 8px;
            background: #e9eef5;
            border-radius: 10px;
            overflow: hidden;
        }
        .retail-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, var(--bm-accent-amber), var(--bm-accent-green));
            transition: width 1.2s cubic-bezier(.4,0,.2,1);
        }
        .retail-insight {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: #eff6ff;
            border: 1.5px solid #bfdbfe;
            border-radius: var(--bm-radius-sm);
            padding: 14px 16px;
            margin: 14px 0;
        }
        .retail-insight-icon {
            width: 38px; height: 38px;
            flex-shrink: 0;
            background: #fff;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: var(--bm-accent-blue);
            font-size: 1.05rem;
            box-shadow: 0 2px 8px rgba(59,130,246,0.12);
        }
        .retail-insight-tag {
            font-size: 0.62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--bm-accent-blue);
            margin-bottom: 4px;
        }
        .retail-insight-text {
            font-size: 0.8rem;
            color: var(--bm-text-2);
            line-height: 1.6;
        }
        .bm-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 22px;
            background: #f8fafc;
            border-top: 1.5px solid var(--bm-border-strong);
            font-size: 0.68rem;
            color: var(--bm-text-4);
        }
        .bm-card-footer .live {
            color: var(--bm-accent-green);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* ── CHART WRAPPER ── */
        .bm-chart-wrap {
            position: relative;
            height: 230px;
            margin-top: 8px;
        }

        /* ── ANIMATIONS ── */
        @keyframes bmFadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .bm-hero   { animation: bmFadeUp 0.35s ease both; }
        .bm-stat   { animation: bmFadeUp 0.4s ease both; }
        .bm-card   { animation: bmFadeUp 0.45s ease both; }
        .bm-stat:nth-child(1) { animation-delay: 0.05s; }
        .bm-stat:nth-child(2) { animation-delay: 0.10s; }
        .bm-stat:nth-child(3) { animation-delay: 0.15s; }
        .bm-stat:nth-child(4) { animation-delay: 0.20s; }
        .bm-stat:nth-child(5) { animation-delay: 0.25s; }

        /* ── RESPONSIVE ── */
        @media (max-width: 1400px) { .bm-stats-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 1200px) { .bm-stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 992px)  { .bm-grid-2 { grid-template-columns: 1fr; } .bm-full { grid-column: 1; } }
        @media (max-width: 768px)  {
            .sidebar { transform: translateX(-100%); width: 260px; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .hamburger-btn { display: flex; align-items: center; justify-content: center; }
            .sidebar-close-btn { display: flex; }
            .dashboard-wrapper { padding: 16px; }
            .bm-stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .bm-hero { padding: 22px 20px; }
            .bm-hero-title { font-size: 1.4rem; }
        }
        @media (max-width: 480px) { .bm-stats-grid { grid-template-columns: 1fr; } }
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

        <!-- ── HERO HEADER ── -->
        <div class="bm-hero">
            <div class="bm-hero-grid-bg"></div>
            <div class="bm-hero-left">
                <div class="bm-hero-badge">
                    <span class="bm-hero-badge-dot"></span>
                    <span>Live Surveillance · IDX Market</span>
                </div>
                <h1 class="bm-hero-title">
                    <span class="bm-hero-icon"><i class="fa-solid fa-user-shield"></i></span>
                    Bandarmology Surveillance
                </h1>
                <p class="bm-hero-subtitle">
                    Deteksi akumulasi &amp; distribusi Big Money &mdash;
                    <strong><?php echo $activeTicker; ?> &middot; <?php echo htmlspecialchars($stockInfo['company_name']); ?></strong>
                </p>
            </div>
            <div class="bm-hero-right">
                <div class="ticker-selector-wrap">
                    <label for="ticker-select">Pilih Saham</label>
                    <form action="bandarmology.php" method="GET" id="ticker-form">
                        <select id="ticker-select" name="ticker" onchange="document.getElementById('ticker-form').submit();">
                            <?php foreach ($tickers as $t): ?>
                                <option value="<?php echo $t['ticker']; ?>" <?php echo $t['ticker'] === $activeTicker ? 'selected' : ''; ?>>
                                    <?php echo $t['ticker']; ?> – <?php echo htmlspecialchars($t['company_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── STAT CARDS ── -->
        <div class="bm-stats-grid">

            <!-- Konsentrasi Beli -->
            <div class="bm-stat s-green">
                <div class="bm-stat-header">
                    <div class="bm-stat-icon-wrap"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    <span class="bm-stat-label">Konsentrasi Beli</span>
                </div>
                <div class="bm-sparkline-wrap"><canvas id="miniBuyChart"></canvas></div>
                <div class="bm-stat-bottom">
                    <span class="bm-stat-sub">Top 3 buyer dari total vol</span>
                    <span class="bm-stat-trend"><i class="fa-solid fa-caret-up"></i> Buy Side</span>
                </div>
            </div>

            <!-- Konsentrasi Jual -->
            <div class="bm-stat s-red">
                <div class="bm-stat-header">
                    <div class="bm-stat-icon-wrap"><i class="fa-solid fa-arrow-trend-down"></i></div>
                    <span class="bm-stat-label">Konsentrasi Jual</span>
                </div>
                <div class="bm-sparkline-wrap"><canvas id="miniSellChart"></canvas></div>
                <div class="bm-stat-bottom">
                    <span class="bm-stat-sub">Top 3 seller dari total vol</span>
                    <span class="bm-stat-trend"><i class="fa-solid fa-caret-down"></i> Sell Side</span>
                </div>
            </div>

            <!-- Partisipasi Ritel -->
            <div class="bm-stat s-blue">
                <div class="bm-stat-header">
                    <div class="bm-stat-icon-wrap"><i class="fa-solid fa-people-group"></i></div>
                    <span class="bm-stat-label">Partisipasi Ritel</span>
                </div>
                <div class="bm-sparkline-wrap"><canvas id="miniRetailChart"></canvas></div>
                <div class="bm-stat-bottom">
                    <span class="bm-stat-sub">YP, PD, XC, XL vs total</span>
                    <span class="bm-stat-trend"><i class="fa-solid fa-users"></i> Retail</span>
                </div>
            </div>

            <!-- Partisipasi Asing -->
            <div class="bm-stat s-purple">
                <div class="bm-stat-header">
                    <div class="bm-stat-icon-wrap"><i class="fa-solid fa-earth-americas"></i></div>
                    <span class="bm-stat-label">Partisipasi Asing</span>
                </div>
                <div class="bm-sparkline-wrap"><canvas id="miniForeignPartChart"></canvas></div>
                <div class="bm-stat-bottom">
                    <span class="bm-stat-sub">Volume asing vs total</span>
                    <span class="bm-stat-trend"><i class="fa-solid fa-globe"></i> Foreign</span>
                </div>
            </div>

            <!-- Net Foreign Flow -->
            <div class="bm-stat s-amber">
                <div class="bm-stat-header">
                    <div class="bm-stat-icon-wrap"><i class="fa-solid fa-money-bill-transfer"></i></div>
                    <span class="bm-stat-label">Net Foreign Flow</span>
                </div>
                <div class="bm-sparkline-wrap"><canvas id="miniForeignChart"></canvas></div>
                <div class="bm-stat-bottom">
                    <span class="bm-stat-sub">Kumulatif 10 hari</span>
                    <span class="bm-stat-trend">
                        <?php if($foreignTotalFlow >= 0): ?>
                            <i class="fa-solid fa-caret-up"></i> Inflow
                        <?php else: ?>
                            <i class="fa-solid fa-caret-down"></i> Outflow
                        <?php endif; ?>
                    </span>
                </div>
            </div>

        </div><!-- end bm-stats-grid -->

        <!-- ── SINYAL & AKUMULASI ── -->
        <div class="bm-section-label">
            <span class="bm-section-label-bar"></span>
            Sinyal &amp; Status Akumulasi
            <span class="bm-section-label-line"></span>
        </div>
        <div class="bm-grid-2">

            <!-- Signal Card -->
            <div class="bm-card">
                <div class="bm-card-header">
                    <span class="bm-card-title">
                        <span class="bm-card-icon slate"><i class="fa-solid fa-satellite-dish"></i></span>
                        Bandarmology Signal
                    </span>
                    <span class="bm-pill <?php echo $statusClass; ?>"><?php echo $statusBadge; ?></span>
                </div>
                <div class="bm-card-body">
                    <div class="signal-display <?php echo $signalClass; ?>">
                        <span class="signal-eyebrow">Rekomendasi Aksi</span>
                        <div class="signal-main"><?php echo $signalText; ?></div>
                        <p class="signal-sub"><?php echo $signalDesc; ?></p>
                    </div>
                </div>
            </div>

            <!-- Accumulation & Distribution -->
            <div class="bm-card">
                <div class="bm-card-header">
                    <span class="bm-card-title">
                        <span class="bm-card-icon blue"><i class="fa-solid fa-arrows-to-eye"></i></span>
                        Accumulation &amp; Distribution
                    </span>
                    <span class="bm-pill <?php echo $statusClass; ?>"><?php echo $statusBadge; ?></span>
                </div>
                <div class="bm-card-body">
                    <p style="font-size:0.8rem; color:var(--bm-text-2); margin-bottom:14px;">
                        Berdasarkan konsentrasi volume 3 broker utama buyer dan seller.
                    </p>
                    <div class="acc-grid">
                        <div class="acc-metric-box">
                            <div class="acc-metric-box-label">Konsentrasi Beli (Top 3)</div>
                            <div class="acc-metric-box-val green"><?php echo number_format($buyConcentration, 1); ?>%</div>
                        </div>
                        <div class="acc-metric-box">
                            <div class="acc-metric-box-label">Konsentrasi Jual (Top 3)</div>
                            <div class="acc-metric-box-val red"><?php echo number_format($sellConcentration, 1); ?>%</div>
                        </div>
                    </div>
                    <div class="acc-qualitative">
                        <div class="acc-qualitative-label">Analisis Kualitatif</div>
                        <div class="acc-qualitative-text"><?php echo $accDistStatus; ?></div>
                    </div>
                </div>
            </div>

        </div><!-- end bm-grid-2 -->

        <!-- ── BROKER MAPPING ── -->
        <div class="bm-section-label">
            <span class="bm-section-label-bar"></span>
            Broker Activity Mapping
            <span class="bm-section-label-line"></span>
        </div>
        <div class="bm-grid-2">

            <!-- Top Buyers -->
            <div class="bm-card">
                <div class="bm-card-header">
                    <span class="bm-card-title">
                        <span class="bm-card-icon green"><i class="fa-solid fa-arrow-trend-up"></i></span>
                        Top Buyers
                    </span>
                    <span style="font-size:0.68rem; font-family:var(--font-mono); color:var(--bm-text-4); font-weight:700; text-transform:uppercase;">Volume (Lot)</span>
                </div>
                <div class="bm-card-body">
                    <div class="broker-list">
                        <?php
                        $maxBuy = max($simData['buyers']);
                        $rank = 1;
                        foreach ($simData['buyers'] as $broker => $vol):
                            $isRetail = in_array($broker, $retailBrokers);
                            $pct = $maxBuy > 0 ? ($vol / $maxBuy) * 100 : 0;
                        ?>
                        <div class="broker-row">
                            <span class="broker-rank">#<?php echo $rank++; ?></span>
                            <span class="broker-tag <?php echo $isRetail ? 'retail' : 'inst'; ?>"><?php echo $broker; ?></span>
                            <div class="vol-track"><div class="vol-fill green" style="width:<?php echo $pct; ?>%"></div></div>
                            <span class="vol-num"><?php echo number_format($vol); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Top Sellers -->
            <div class="bm-card">
                <div class="bm-card-header">
                    <span class="bm-card-title">
                        <span class="bm-card-icon red"><i class="fa-solid fa-arrow-trend-down"></i></span>
                        Top Sellers
                    </span>
                    <span style="font-size:0.68rem; font-family:var(--font-mono); color:var(--bm-text-4); font-weight:700; text-transform:uppercase;">Volume (Lot)</span>
                </div>
                <div class="bm-card-body">
                    <div class="broker-list">
                        <?php
                        $maxSell = max($simData['sellers']);
                        $rankS = 1;
                        foreach ($simData['sellers'] as $broker => $vol):
                            $isRetail = in_array($broker, $retailBrokers);
                            $pct = $maxSell > 0 ? ($vol / $maxSell) * 100 : 0;
                        ?>
                        <div class="broker-row">
                            <span class="broker-rank">#<?php echo $rankS++; ?></span>
                            <span class="broker-tag <?php echo $isRetail ? 'retail' : 'inst'; ?>"><?php echo $broker; ?></span>
                            <div class="vol-track"><div class="vol-fill red" style="width:<?php echo $pct; ?>%"></div></div>
                            <span class="vol-num"><?php echo number_format($vol); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div><!-- end broker grid -->

        <!-- ── CHARTS ROW ── -->
        <div class="bm-section-label">
            <span class="bm-section-label-bar"></span>
            Visualisasi Komposisi &amp; Kekuatan Broker
            <span class="bm-section-label-line"></span>
        </div>
        <div class="bm-grid-2">

            <div class="bm-card">
                <div class="bm-card-header">
                    <span class="bm-card-title">
                        <span class="bm-card-icon purple"><i class="fa-solid fa-chart-pie"></i></span>
                        Market Participants
                    </span>
                    <span style="font-size:0.68rem; font-family:var(--font-mono); color:var(--bm-text-4); font-weight:700; text-transform:uppercase;">Volume</span>
                </div>
                <div class="bm-card-body">
                    <div class="bm-chart-wrap" style="display:flex; justify-content:center; align-items:center;">
                        <canvas id="participantsChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bm-card">
                <div class="bm-card-header">
                    <span class="bm-card-title">
                        <span class="bm-card-icon amber"><i class="fa-solid fa-scale-balanced"></i></span>
                        Buy vs Sell Power
                    </span>
                    <span style="font-size:0.68rem; font-family:var(--font-mono); color:var(--bm-text-4); font-weight:700; text-transform:uppercase;">Top 3 vs Others</span>
                </div>
                <div class="bm-card-body">
                    <div class="bm-chart-wrap">
                        <canvas id="powerChart"></canvas>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── FOREIGN FLOW & RETAIL ── -->
        <div class="bm-section-label">
            <span class="bm-section-label-bar"></span>
            Arus Asing &amp; Psikologi Ritel
            <span class="bm-section-label-line"></span>
        </div>
        <div class="bm-grid-2">

            <!-- Foreign Flow Chart -->
            <div class="bm-card">
                <div class="bm-card-header">
                    <span class="bm-card-title">
                        <span class="bm-card-icon blue"><i class="fa-solid fa-earth-americas"></i></span>
                        Net Foreign Flow &ndash; 10 Hari
                    </span>
                    <span style="font-size:0.68rem; font-family:var(--font-mono); color:var(--bm-text-4); font-weight:700; text-transform:uppercase;">Miliar Rp</span>
                </div>
                <div class="bm-card-body">
                    <p style="font-size:0.75rem; color:var(--bm-text-4); margin-bottom:14px;">
                        Arus masuk (+) dan keluar (−) asing bersih harian pada saham <strong style="color:var(--bm-text-2);"><?php echo $activeTicker; ?></strong>.
                    </p>
                    <div class="bm-chart-wrap">
                        <canvas id="foreignFlowChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Retail Behavior -->
            <div class="bm-card" style="display:flex; flex-direction:column;">
                <div class="bm-card-header">
                    <span class="bm-card-title">
                        <span class="bm-card-icon amber"><i class="fa-solid fa-people-group"></i></span>
                        Retail Behavior Detection
                    </span>
                </div>
                <div class="bm-card-body" style="flex:1;">
                    <p style="font-size:0.8rem; color:var(--bm-text-2); margin-bottom:14px;">
                        Psikologi ritel dari persentase keterlibatan broker YP, PD, XC, XL.
                    </p>
                    <div class="retail-progress-wrap">
                        <div class="retail-progress-top">
                            <span class="retail-progress-label">Partisipasi Volume Ritel</span>
                            <span class="retail-progress-val"><?php echo number_format($retailParticipation, 1); ?>%</span>
                        </div>
                        <div class="retail-track">
                            <div class="retail-fill" style="width:<?php echo min($retailParticipation, 100); ?>%"></div>
                        </div>
                    </div>
                    <div class="retail-insight">
                        <div class="retail-insight-icon"><i class="fa-solid fa-brain"></i></div>
                        <div>
                            <div class="retail-insight-tag">Deteksi Psikologis</div>
                            <div class="retail-insight-text"><?php echo $retailBehavior; ?></div>
                        </div>
                    </div>
                </div>
                <div class="bm-card-footer">
                    <span><i class="fa-regular fa-clock"></i> Real-time update</span>
                    <span class="live"><i class="fa-regular fa-circle-check"></i> Data IDX</span>
                </div>
            </div>

        </div><!-- end foreign+retail grid -->

    </div><!-- end dashboard-wrapper -->
</main>

<script src="assets/js/main.js"></script>
<script>
    // Sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const hamburger = document.getElementById('hamburger-btn');
    const closeBtn = document.getElementById('sidebar-close-btn');
    hamburger?.addEventListener('click', () => sidebar.classList.toggle('open'));
    closeBtn?.addEventListener('click', () => sidebar.classList.remove('open'));

    // ── Date labels (10 days)
    const labels = [];
    const today = new Date();
    for (let i = 9; i >= 0; i--) {
        const d = new Date(); d.setDate(today.getDate() - i);
        labels.push(d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }));
    }

    // ── Foreign Flow raw data
    const foreignRaw = <?php echo json_encode($simData['foreign']); ?>;
    const dataBillions = foreignRaw.map(v => v / 1_000_000_000);
    const bgColor     = dataBillions.map(v => v >= 0 ? 'rgba(16,185,129,0.55)' : 'rgba(239,68,68,0.55)');
    const borderColor = dataBillions.map(v => v >= 0 ? '#10b981' : '#ef4444');

    // ── Foreign Flow Bar Chart
    const ctxFF = document.getElementById('foreignFlowChart').getContext('2d');
    new Chart(ctxFF, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Net Foreign Flow (Miliar Rp)',
                data: dataBillions,
                backgroundColor: bgColor,
                borderColor: borderColor,
                borderWidth: 1.5,
                borderRadius: 7,
                barPercentage: 0.62
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#ffffff', titleColor: '#1e293b',
                    bodyColor: '#475569', borderColor: '#e9edf2', borderWidth: 1,
                    cornerRadius: 10, padding: 10,
                    callbacks: {
                        label: ctx => ` ${ctx.parsed.y >= 0 ? '+' : ''}${ctx.parsed.y.toFixed(2)} Miliar Rp`
                    }
                }
            },
            scales: {
                y: {
                    grid: { color: '#eef2f8' },
                    ticks: {
                        color: '#6c7a91',
                        font: { size: 10, family: "'JetBrains Mono', monospace", weight: '600' },
                        callback: v => (v >= 0 ? '+' : '') + v + ' M'
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#6c7a91', font: { size: 10, family: "'JetBrains Mono', monospace" } }
                }
            }
        }
    });

    // ── Participants Doughnut
    const instVol = <?php echo ($totalBrokerVol - ($retailBuyVol + $retailSellVol)); ?>;
    const retVol  = <?php echo ($retailBuyVol + $retailSellVol); ?>;
    const ctxPart = document.getElementById('participantsChart').getContext('2d');
    new Chart(ctxPart, {
        type: 'doughnut',
        data: {
            labels: ['Institusi', 'Ritel'],
            datasets: [{
                data: [instVol, retVol],
                backgroundColor: ['#3b82f6', '#f59e0b'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#64748b',
                        font: { family: "'Inter', sans-serif", size: 11, weight: '600' },
                        usePointStyle: true, padding: 22
                    }
                },
                tooltip: {
                    backgroundColor: '#ffffff', titleColor: '#1e293b',
                    bodyColor: '#475569', borderColor: '#e9edf2', borderWidth: 1,
                    cornerRadius: 10, padding: 10,
                    callbacks: {
                        label: ctx => {
                            const pct = ((ctx.parsed / (instVol + retVol)) * 100).toFixed(1);
                            return ` ${ctx.label}: ${pct}% (${ctx.parsed.toLocaleString()} Lot)`;
                        }
                    }
                }
            }
        }
    });

    // ── Buy vs Sell Power (Stacked Bar)
    const topBuyVol   = <?php echo $top3BuyersVol; ?>;
    const otherBuyVol = <?php echo ($totalBuyVolume - $top3BuyersVol); ?>;
    const topSellVol  = <?php echo $top3SellersVol; ?>;
    const otherSellVol= <?php echo ($totalSellVolume - $top3SellersVol); ?>;
    const ctxPower = document.getElementById('powerChart').getContext('2d');
    new Chart(ctxPower, {
        type: 'bar',
        data: {
            labels: ['BUY POWER', 'SELL POWER'],
            datasets: [
                {
                    label: 'Top 3 Brokers',
                    data: [topBuyVol, topSellVol],
                    backgroundColor: ['rgba(16,185,129,0.82)', 'rgba(239,68,68,0.82)'],
                    borderRadius: { topLeft: 7, topRight: 7 },
                    barPercentage: 0.58
                },
                {
                    label: 'Others',
                    data: [otherBuyVol, otherSellVol],
                    backgroundColor: ['rgba(16,185,129,0.25)', 'rgba(239,68,68,0.25)'],
                    borderRadius: 0,
                    barPercentage: 0.58
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#64748b',
                        font: { family: "'Inter', sans-serif", size: 10 },
                        usePointStyle: true, padding: 14
                    }
                },
                tooltip: {
                    backgroundColor: '#ffffff', titleColor: '#1e293b',
                    bodyColor: '#475569', borderColor: '#e9edf2', borderWidth: 1,
                    cornerRadius: 10, padding: 10
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { color: '#334155', font: { weight: '700', family: "'JetBrains Mono', monospace", size: 10 } }
                },
                y: {
                    stacked: true,
                    grid: { color: '#eef2f8' },
                    ticks: {
                        color: '#6c7a91',
                        font: { family: "'JetBrains Mono', monospace", size: 10 },
                        callback: v => v >= 1000 ? (v/1000).toFixed(0)+'K' : v
                    }
                }
            }
        }
    });

    // ── Sparkline helper
    const renderSparkline = (canvasId, currentValue, isForeign, colorBase, colorRgb) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        let dataArr = [];
        if (isForeign) {
            dataArr = dataBillions;
        } else {
            let val = currentValue;
            for (let i = 0; i < 10; i++) {
                dataArr.unshift(val);
                val = val - (Math.random() * 5 - 2.5);
                if (val < 0)   val = Math.random() * 5;
                if (val > 100) val = 100 - Math.random() * 5;
            }
        }

        let grad = ctx.createLinearGradient(0, 0, 0, 72);
        grad.addColorStop(0, `rgba(${colorRgb}, 0.5)`);
        grad.addColorStop(1, `rgba(${colorRgb}, 0.0)`);

        let radii      = new Array(10).fill(0);
        let hoverRadii = new Array(10).fill(4);
        radii[9] = 4;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data: dataArr,
                    borderColor: colorBase,
                    borderWidth: 2.2,
                    pointRadius: radii,
                    pointHoverRadius: hoverRadii,
                    pointBackgroundColor: colorBase,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                    tension: 0.4,
                    fill: true,
                    backgroundColor: grad
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: '#0f172a',
                        titleColor: 'rgba(255,255,255,0.5)',
                        bodyColor: '#ffffff',
                        borderColor: 'rgba(255,255,255,0.08)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        padding: 8,
                        displayColors: false,
                        callbacks: {
                            title: (items) => items[0].label,
                            label: (item) => {
                                const v = item.parsed.y;
                                if (isForeign) return (v >= 0 ? '+' : '') + v.toFixed(2) + ' M';
                                return v.toFixed(1) + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: { display: false },
                    y: { display: false, min: Math.min(...dataArr) * 0.85, max: Math.max(...dataArr) * 1.15 }
                },
                layout: { padding: { top: 5, bottom: 5, left: 5, right: 5 } }
            }
        });
    };

    const buyVal    = <?php echo number_format($buyConcentration, 1, '.', ''); ?>;
    const sellVal   = <?php echo number_format($sellConcentration, 1, '.', ''); ?>;
    const retVal    = <?php echo number_format($retailParticipation, 1, '.', ''); ?>;
    const forPartVal= <?php echo number_format($foreignParticipation, 1, '.', ''); ?>;
    const forVal    = <?php echo $foreignTotalFlow; ?>;

    renderSparkline('miniBuyChart',        buyVal,     false, '#10b981', '16,185,129');
    renderSparkline('miniSellChart',       sellVal,    false, '#ef4444', '239,68,68');
    renderSparkline('miniRetailChart',     retVal,     false, '#3b82f6', '59,130,246');
    renderSparkline('miniForeignPartChart',forPartVal, false, '#8b5cf6', '139,92,246');
    const fColor = forVal >= 0 ? '#f59e0b' : '#ef4444';
    const fRgb   = forVal >= 0 ? '245,158,11' : '239,68,68';
    renderSparkline('miniForeignChart', forVal, true, fColor, fRgb);
</script>
</body>
</html>
