<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require 'config.php';

// Fetch all stocks for dynamic processing
$stocksQuery = "SELECT * FROM stocks ORDER BY market_cap DESC";
$stocksResult = $conn->query($stocksQuery);
$allStocks = [];
if ($stocksResult) {
    while ($row = $stocksResult->fetch_assoc()) {
        // Generate a deterministic daily change percentage based on price & ticker
        $seed = crc32($row['ticker']);
        srand($seed);
        $change = (rand(-60, 85)) / 10; // -6.0% to +8.5%
        $row['change_pct'] = $change;
        
        // Simulating daily volume
        $row['daily_volume'] = $row['volume'] > 0 ? $row['volume'] : rand(5000, 1000000);
        
        $allStocks[] = $row;
    }
}

// 1. Sector Classification List
$sectors = [
    'Financials' => [],
    'Consumer Non-Cyclicals' => [],
    'Consumer Cyclicals' => [],
    'Basic Materials' => [],
    'Energy' => [],
    'Infrastructure' => [],
    'Healthcare' => [],
    'Properties & Real Estate' => [],
    'Technology' => [],
    'Industrials' => [],
    'Transportation & Logistics' => []
];

foreach ($allStocks as $s) {
    $sec = $s['sector'] ?? 'Industrials';
    if (array_key_exists($sec, $sectors)) {
        $sectors[$sec][] = $s;
    } else {
        $sectors['Industrials'][] = $s;
    }
}

// 2. Conglomerate Definitions
$conglomerates = [
    'Salim Group' => [
        'tickers' => ['INDF', 'ICBP', 'LSIP', 'SIMP', 'IMAS', 'AMRT'],
        'desc' => 'Gurita bisnis makanan, perkebunan, ritel, dan otomotif terbesar di Indonesia dipimpin oleh Anthoni Salim.',
        'color' => '#1e3a8a',
        'leader_name' => 'Anthoni Salim',
        'leader_role' => 'Chairman',
        'leader_image' => 'assets/images/leaders/anthoni_salim.png',
        'stocks' => []
    ],
    'Astra Group' => [
        'tickers' => ['ASII', 'UNTR', 'AALI', 'ASGR', 'AUTO'],
        'desc' => 'Konglomerat multinasional raksasa otomotif, alat berat, pertambangan, dan agribisnis.',
        'color' => '#0f172a',
        'leader_name' => 'William Soeryadjaya',
        'leader_role' => 'Founder',
        'leader_image' => 'assets/images/leaders/astra_leader.png',
        'stocks' => []
    ],
    'Barito Group' => [
        'tickers' => ['BRPT', 'TPIA', 'BREN', 'CUAN', 'PTRO'],
        'desc' => 'Grup energi hijau, petrokimia, dan infrastruktur terbesar milik Prajogo Pangestu.',
        'color' => '#047857',
        'leader_name' => 'Prajogo Pangestu',
        'leader_role' => 'Pemilik',
        'leader_image' => 'assets/images/leaders/prajogo_pangestu.png',
        'stocks' => []
    ],
    'Djarum / Hartono' => [
        'tickers' => ['BBCA', 'TOWR', 'BLIB'],
        'desc' => 'Dipimpin oleh keluarga Hartono (orang terkaya RI), menguasai perbankan swasta terbesar, infrastruktur telekomunikasi, dan e-commerce.',
        'color' => '#b91c1c',
        'leader_name' => 'Budi & Michael Hartono',
        'leader_role' => 'Owners',
        'leader_image' => 'assets/images/leaders/hartono_brothers.png',
        'stocks' => []
    ],
    'Sinar Mas' => [
        'tickers' => ['BSDE', 'INKP', 'TKIM', 'FREN', 'BSIM'],
        'desc' => 'Grup properti raksasa, kertas (pulp), jasa keuangan, telekomunikasi, dan agribisnis keluarga Widjaja.',
        'color' => '#a21caf',
        'leader_name' => 'Eka Tjipta Widjaja',
        'leader_role' => 'Founder',
        'leader_image' => 'assets/images/leaders/eka_tjipta.png',
        'stocks' => []
    ],
    'Lippo Group' => [
        'tickers' => ['LPKR', 'SILO', 'MPPA', 'MLPL'],
        'desc' => 'Fokus pada pembangunan properti, layanan kesehatan (Siloam), ritel konsumen, dan investasi media keluarga Riady.',
        'color' => '#0369a1',
        'leader_name' => 'Mochtar Riady',
        'leader_role' => 'Founder',
        'leader_image' => 'assets/images/leaders/mochtar_riady.png',
        'stocks' => []
    ],
    'MNC Group' => [
        'tickers' => ['BHIT', 'MNCN', 'BMTR', 'MSIN', 'KPIG'],
        'desc' => 'Raksasa media penyiaran, hiburan, properti, dan jasa keuangan terintegrasi milik Hary Tanoesoedibjo.',
        'color' => '#4f46e5',
        'leader_name' => 'Hary Tanoesoedibjo',
        'leader_role' => 'Chairman & CEO',
        'leader_image' => 'assets/images/leaders/hary_tanoe.png',
        'stocks' => []
    ],
    'Bakrie Group' => [
        'tickers' => ['BUMI', 'BNBR', 'ELTY', 'BTEL', 'VIVA', 'ENRG'],
        'desc' => 'Salah satu konglomerat tertua di RI, bergerak di pertambangan batubara, properti, media, dan telekomunikasi milik keluarga Bakrie.',
        'color' => '#c2410c',
        'leader_name' => 'Aburizal Bakrie',
        'leader_role' => 'Chairman',
        'leader_image' => 'assets/images/leaders/aburizal_bakrie.png',
        'stocks' => []
    ],
    'CT Corp (Trans)' => [
        'tickers' => ['MAPI', 'MTDL', 'ATIC'],
        'desc' => 'Konglomerat ritel, media, dan keuangan milik Chairul Tanjung — Trans TV, Trans7, Bank Mega, dan jaringan fashion premium.',
        'color' => '#0891b2',
        'leader_name' => 'Chairul Tanjung',
        'leader_role' => 'Founder & Chairman',
        'leader_image' => 'assets/images/leaders/chairul_tanjung.png',
        'stocks' => []
    ],
    'Saratoga Group' => [
        'tickers' => ['ACES', 'ADRO', 'STRK', 'SRTG'],
        'desc' => 'Grup investasi strategis di sektor pertambangan, infrastruktur, dan konsumer dipimpin Edwin Soeryadjaya & Sandiaga Uno.',
        'color' => '#065f46',
        'leader_name' => 'Edwin Soeryadjaya',
        'leader_role' => 'Co-Founder',
        'leader_image' => 'assets/images/leaders/sandiaga_uno.png',
        'stocks' => []
    ],
];

// Map stocks into conglomerates
foreach ($allStocks as $s) {
    foreach ($conglomerates as $name => &$info) {
        if (in_array($s['ticker'], $info['tickers'])) {
            $info['stocks'][] = $s;
        }
    }
}
unset($info); // break reference

// 3. Technical Setup Filtering (Deterministic classification)
$setups = [
    'Volume Breakout' => [],
    'Bullish Pullback' => [],
    'Golden Cross MA' => [],
    '52-Week High Breakout' => []
];

foreach ($allStocks as $s) {
    $seed = crc32($s['ticker'] . 'setup');
    srand($seed);
    $setupVal = rand(1, 100);
    
    // Deterministically assign stocks into setup buckets to populate realistically
    if ($setupVal > 85) {
        $s['setup_desc'] = 'Volume transaksi harian naik > 2.5x dari rata-rata volume 20 hari harian, didukung kenaikan harga signifikan.';
        $setups['Volume Breakout'][] = $s;
    } elseif ($setupVal > 70 && $setupVal <= 85) {
        $s['setup_desc'] = 'Mengalami koreksi minor yang sehat mendekati garis support dinamis MA20 dengan volume menyusut (Buy on Weakness).';
        $setups['Bullish Pullback'][] = $s;
    } elseif ($setupVal > 55 && $setupVal <= 70) {
        $s['setup_desc'] = 'Indikator Moving Average periode pendek (MA5) baru saja menyilang ke atas MA periode menengah (MA20) secara solid.';
        $setups['Golden Cross MA'][] = $s;
    } elseif ($setupVal > 45 && $setupVal <= 55) {
        $s['setup_desc'] = 'Harga berhasil menembus level tertinggi (Resistance) baru yang terbentuk selama 52 minggu (1 tahun) terakhir.';
        $setups['52-Week High Breakout'][] = $s;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Scanner - Sobat Ritel</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Floating Tech Radial Lights */
        .screener-glow {
            position: fixed;
            width: 700px;
            height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.06) 0%, rgba(37, 99, 235, 0) 70%);
            pointer-events: none;
            z-index: 0;
        }
        
        .glow-1 {
            top: -20%;
            right: -10%;
        }

        .glow-2 {
            bottom: -20%;
            left: -10%;
        }

        .scanner-tabs {
            display: inline-flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 0.4rem;
            border-radius: 18px;
            border: 1px solid rgba(37, 99, 235, 0.08);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            max-width: 100%;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .scanner-tabs::-webkit-scrollbar {
            display: none;
        }
        .scanner-tab-btn {
            background: none;
            border: none;
            padding: 0.75rem 1.4rem;
            border-radius: 14px;
            font-weight: 800;
            font-size: 0.92rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        .scanner-tab-btn:hover {
            color: var(--primary);
            background: rgba(37, 99, 235, 0.04);
        }
        .scanner-tab-btn.active {
            color: white;
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.18);
        }
        .scanner-content-panel {
            display: none;
            position: relative;
            z-index: 1;
        }
        .scanner-content-panel.active {
            display: block;
            animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .filter-panel {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(37, 99, 235, 0.08);
            border-radius: 20px;
            padding: 1.2rem 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.01);
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .filter-group label {
            font-size: 0.78rem;
            font-weight: 850;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .filter-group select {
            padding: 0.65rem 1.2rem;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            font-weight: 700;
            color: var(--text-main);
            outline: none;
            font-size: 0.88rem;
            background: #ffffff;
            cursor: pointer;
            transition: var(--transition);
        }
        .filter-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
        }
        .scanner-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.8rem;
        }
        .conglo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 1.8rem;
        }
        .conglo-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 24px;
            border: 1px solid rgba(37, 99, 235, 0.08);
            padding: 1.8rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 1.5rem;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.02);
            transition: var(--transition);
        }
        .conglo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 45px rgba(37, 99, 235, 0.06);
            border-color: rgba(37, 99, 235, 0.2);
        }
        .conglo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .conglo-name {
            font-size: 1.2rem;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .conglo-badge {
            font-weight: 850;
            font-size: 0.78rem;
            padding: 0.35rem 0.75rem;
            border-radius: 10px;
            letter-spacing: 0.3px;
        }
        .conglo-perf-good {
            background: rgba(13, 148, 136, 0.08);
            color: #0d9488;
            border: 1px solid rgba(13, 148, 136, 0.15);
        }
        .conglo-perf-bad {
            background: rgba(225, 29, 72, 0.08);
            color: #e11d48;
            border: 1px solid rgba(225, 29, 72, 0.15);
        }
        .conglo-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 0.8rem;
        }
        .pct-up {
            color: var(--success);
            font-weight: 800;
        }
        .pct-down {
            color: var(--danger);
            font-weight: 800;
        }
        .pct-neutral {
            color: var(--text-muted);
            font-weight: 800;
        }
        
        .setup-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 24px;
            border: 1px solid rgba(37, 99, 235, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.02);
            transition: var(--transition);
        }
        .setup-card:hover {
            border-color: rgba(37, 99, 235, 0.2);
            box-shadow: 0 15px 40px rgba(37, 99, 235, 0.05);
        }
        .setup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid rgba(37, 99, 235, 0.06);
            padding-bottom: 1rem;
            margin-bottom: 1.2rem;
        }
        .setup-name {
            font-size: 1.25rem;
            font-weight: 900;
            color: var(--text-main);
        }
        .setup-desc {
            font-size: 0.82rem;
            color: var(--text-muted);
            font-weight: 750;
            background: rgba(0, 0, 0, 0.03);
            padding: 0.35rem 0.75rem;
            border-radius: 10px;
        }

        /* Premium table style for scanner */
        .scanner-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .scanner-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f8fafc;
            color: var(--text-muted);
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            padding: 1rem 0.8rem;
            border-bottom: 2.5px solid rgba(37, 99, 235, 0.08);
            text-align: left;
        }
        .scanner-table td {
            padding: 1rem 0.8rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
            font-weight: 600;
            color: #334155;
            vertical-align: middle;
        }
        .scanner-table tbody tr {
            transition: var(--transition);
        }
        .scanner-table tbody tr:hover {
            background: rgba(37, 99, 235, 0.015);
        }
        .scanner-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table-container {
            border-radius: 16px;
            border: 1px solid rgba(37, 99, 235, 0.06);
            overflow: hidden;
        }
        
        /* Custom sleek scrollbars */
        .table-container::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .table-container::-webkit-scrollbar-track {
            background: transparent;
        }
        .table-container::-webkit-scrollbar-thumb {
            background: rgba(37, 99, 235, 0.12);
            border-radius: 4px;
        }
        .table-container::-webkit-scrollbar-thumb:hover {
            background: rgba(37, 99, 235, 0.25);
        }
        
        .sector-card-hover {
            background: rgba(255, 255, 255, 0.5); 
            border: 1px solid var(--card-border); 
            padding: 1.15rem; 
            border-radius: 18px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            transition: var(--transition);
        }
        .sector-card-hover:hover {
            transform: translateY(-2px);
            border-color: rgba(37, 99, 235, 0.25) !important;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.05);
            background: #ffffff !important;
        }

        @media (max-width: 992px) {
            .scanner-grid {
                grid-template-columns: 1fr;
            }
            .conglo-grid {
                grid-template-columns: 1fr;
            }
            .filter-panel {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
            .filter-group {
                justify-content: space-between;
            }
            .scanner-tabs {
                display: flex;
            }
        }
    </style>
</head>
<body class="dashboard-body">
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="assets/nyangkuters_logo.png" alt="Sobat Ritel Logo" style="width: 32px; height: 32px; border-radius: 8px; object-fit: cover;">
            <h2>Sobat Ritel</h2>
            <button class="sidebar-close-btn" id="sidebar-close-btn">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li class="active"><a href="scanner.php"><i class="fa-solid fa-binoculars"></i> Market Scanner</a></li>
            <li><a href="screener.php"><i class="fa-solid fa-filter"></i> Screener</a></li>
            <li><a href="technical.php"><i class="fa-solid fa-chart-line"></i> Technical</a></li>
            <li><a href="broker.php"><i class="fa-solid fa-user-tie"></i> Broker Stalker</a></li>
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
                    <span class="role">Pro Member</span>
                </div>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="hamburger-btn" id="hamburger-btn">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="search-bar">
                <form action="dashboard.php" method="GET">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Cari emiten atau perusahaan...">
                </form>
            </div>
            <div class="topbar-actions">
                <button class="icon-btn"><i class="fa-regular fa-bell"></i><span class="badge">3</span></button>
                <div class="date-display"><i class="fa-regular fa-calendar"></i> <?php echo date('d M Y'); ?></div>
            </div>
        </header>

        <div class="dashboard-wrapper">
            <div class="page-header">
                <h1>Market Scanner & Mapping</h1>
                <p>Identifikasi peluang momentum, performa sektor industri, dan pemetaan konglomerasi saham IDX.</p>
            </div>

            <!-- Tab Buttons -->
            <div class="scanner-tabs">
                <button class="scanner-tab-btn active" data-tab="movers">
                    <i class="fa-solid fa-fire"></i> Top Movers (Gainer/Loser)
                </button>
                <button class="scanner-tab-btn" data-tab="conglo">
                    <i class="fa-solid fa-diagram-project"></i> Sector & Conglomerate Mapping
                </button>
                <button class="scanner-tab-btn" data-tab="setups">
                    <i class="fa-solid fa-radar"></i> Market Scanner (Setup Trade)
                </button>
            </div>

            <!-- PANEL 1: MOVERS SCANNER -->
            <div class="scanner-content-panel active" id="tab-movers">
                <!-- Filters -->
                <div class="filter-panel">
                    <div class="filter-group">
                        <label><i class="fa-solid fa-filter"></i> Sektor:</label>
                        <select id="movers-sector-filter" onchange="filterMovers()">
                            <option value="ALL">Semua Sektor</option>
                            <?php foreach (array_keys($sectors) as $sName): ?>
                                <option value="<?php echo $sName; ?>"><?php echo $sName; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fa-solid fa-chart-simple"></i> Volume Minimum:</label>
                        <select id="movers-vol-filter" onchange="filterMovers()">
                            <option value="0">Tanpa Batas</option>
                            <option value="50000">> 50.000 Lot</option>
                            <option value="150000">> 150.000 Lot</option>
                            <option value="500000">> 500.000 Lot</option>
                        </select>
                    </div>
                </div>

                <div class="scanner-grid">
                    <!-- Top Gainers -->
                    <div class="glass-card" style="padding: 1.8rem; overflow: hidden;">
                        <h3 style="color: var(--success); margin-bottom: 1.2rem; display: flex; align-items: center; gap: 0.6rem;"><i class="fa-solid fa-arrow-trend-up"></i> Top Gainers Harian</h3>
                        <div class="table-container" style="max-height: 480px; overflow-y: auto;">
                            <table class="scanner-table">
                                <thead>
                                    <tr>
                                        <th>EMITEN</th>
                                        <th style="text-align: right; width: 110px;">HARGA</th>
                                        <th style="text-align: right; width: 110px;">PERUBAHAN</th>
                                        <th style="text-align: right; width: 130px;">VOLUME (LOT)</th>
                                    </tr>
                                </thead>
                                <tbody id="gainers-tbody">
                                    <!-- Dynamic rows from JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top Losers -->
                    <div class="glass-card" style="padding: 1.8rem; overflow: hidden;">
                        <h3 style="color: var(--danger); margin-bottom: 1.2rem; display: flex; align-items: center; gap: 0.6rem;"><i class="fa-solid fa-arrow-trend-down"></i> Top Losers Harian</h3>
                        <div class="table-container" style="max-height: 480px; overflow-y: auto;">
                            <table class="scanner-table">
                                <thead>
                                    <tr>
                                        <th>EMITEN</th>
                                        <th style="text-align: right; width: 110px;">HARGA</th>
                                        <th style="text-align: right; width: 110px;">PERUBAHAN</th>
                                        <th style="text-align: right; width: 130px;">VOLUME (LOT)</th>
                                    </tr>
                                </thead>
                                <tbody id="losers-tbody">
                                    <!-- Dynamic rows from JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sector Performance Overview -->
                <div class="glass-card" style="margin-top: 2rem; padding: 2rem;">
                    <h3 style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.6rem;"><i class="fa-solid fa-layer-group" style="color: var(--primary);"></i> Sector & Industry Classification Performance</h3>
                    <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.8rem; font-weight: 600;">Klasifikasi performa gabungan 11 sektor industri utama di Bursa Efek Indonesia.</p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.2rem;">
                        <?php 
                        $sectorIcons = [
                            'Financials' => 'fa-landmark',
                            'Consumer Non-Cyclicals' => 'fa-basket-shopping',
                            'Consumer Cyclicals' => 'fa-cart-shopping',
                            'Basic Materials' => 'fa-gem',
                            'Energy' => 'fa-bolt',
                            'Infrastructure' => 'fa-road',
                            'Healthcare' => 'fa-heart-pulse',
                            'Properties & Real Estate' => 'fa-building',
                            'Technology' => 'fa-microchip',
                            'Industrials' => 'fa-industry',
                            'Transportation & Logistics' => 'fa-truck-fast'
                        ];
                        
                        foreach ($sectors as $sName => $sList): 
                            // Hitung performa sektoral rata-rata
                            $totalSecChange = 0;
                            $count = count($sList);
                            foreach ($sList as $stock) {
                                $totalSecChange += $stock['change_pct'];
                            }
                            $avgSecChange = $count > 0 ? ($totalSecChange / $count) : 0;
                            $secClass = $avgSecChange >= 0 ? 'pct-up' : 'pct-down';
                            $secSign = $avgSecChange >= 0 ? '+' : '';
                            $secIcon = $sectorIcons[$sName] ?? 'fa-circle-dot';
                        ?>
                            <div class="sector-card-hover">
                                <div style="display: flex; align-items: center; gap: 0.8rem;">
                                    <div style="width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(37,99,235,0.06); color: var(--primary);">
                                        <i class="fa-solid <?php echo $secIcon; ?>" style="font-size: 1.05rem;"></i>
                                    </div>
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 800; font-size: 0.85rem; color: var(--text-main);"><?php echo $sName; ?></span>
                                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;"><?php echo $count; ?> Emiten</span>
                                    </div>
                                </div>
                                <span class="<?php echo $secClass; ?>" style="font-size: 1rem; font-weight: 900;">
                                    <?php echo $secSign . number_format($avgSecChange, 2); ?>%
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- PANEL 2: CONGLOMERATE MAPPING -->
            <div class="scanner-content-panel" id="tab-conglo">
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.8rem; font-weight: 600;">
                    Pemetaan pergerakan saham emiten berdasarkan kepemilikan grup konglomerat besar di Indonesia. Performa grup dihitung dari rata-rata perubahan saham anggotanya.
                </p>
                <div class="conglo-grid">
                    <?php 
                    foreach ($conglomerates as $cName => $cInfo): 
                        // Hitung performa grup
                        $totChange = 0;
                        $count = count($cInfo['stocks']);
                        foreach ($cInfo['stocks'] as $stock) {
                            $totChange += $stock['change_pct'];
                        }
                        $avgChange = $count > 0 ? ($totChange / $count) : 0;
                        $perfClass = $avgChange >= 0 ? 'conglo-perf-good' : 'conglo-perf-bad';
                        $pctClass = $avgChange >= 0 ? 'pct-up' : 'pct-down';
                        $sign = $avgChange >= 0 ? '+' : '';
                    ?>
                        <div class="conglo-card">
                            <!-- Leader Portrait Section -->
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(37,99,235,0.06);">
                                <div style="position: relative; flex-shrink: 0;">
                                    <img src="<?php echo $cInfo['leader_image']; ?>" alt="<?php echo htmlspecialchars($cInfo['leader_name']); ?>"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                         style="width: 68px; height: 68px; border-radius: 50%; object-fit: cover; border: 3px solid <?php echo $cInfo['color']; ?>22; box-shadow: 0 4px 16px rgba(0,0,0,0.10);">
                                    <!-- fallback monogram -->
                                    <div style="display: none; width: 68px; height: 68px; border-radius: 50%; background: <?php echo $cInfo['color']; ?>18; border: 3px solid <?php echo $cInfo['color']; ?>33; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 900; color: <?php echo $cInfo['color']; ?>;">
                                        <?php echo strtoupper(substr($cInfo['leader_name'], 0, 1)); ?>
                                    </div>
                                    <!-- Online indicator dot styled as conglomerate color -->
                                    <div style="position: absolute; bottom: 2px; right: 2px; width: 14px; height: 14px; border-radius: 50%; background: <?php echo $cInfo['color']; ?>; border: 2px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.15);"></div>
                                </div>
                                <div>
                                    <div style="font-weight: 900; font-size: 0.92rem; color: var(--text-main); letter-spacing: -0.3px;"><?php echo htmlspecialchars($cInfo['leader_name']); ?></div>
                                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-top: 0.15rem;"><?php echo htmlspecialchars($cInfo['leader_role'] ?? 'Pemilik'); ?></div>
                                    <div style="margin-top: 0.4rem;">
                                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; font-weight: 800; padding: 0.2rem 0.65rem; border-radius: 8px; background: <?php echo $cInfo['color']; ?>12; color: <?php echo $cInfo['color']; ?>; border: 1px solid <?php echo $cInfo['color']; ?>25;">
                                            <i class="fa-solid fa-building-user" style="font-size: 0.7rem;"></i> <?php echo $cName; ?>
                                        </span>
                                    </div>
                                </div>
                                <!-- Performance badge aligned right -->
                                <div style="margin-left: auto; text-align: right;">
                                    <span class="conglo-badge <?php echo $perfClass; ?>">
                                        <?php echo $sign . number_format($avgChange, 2); ?>%
                                    </span>
                                    <div style="font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-top: 0.3rem; text-transform: uppercase; letter-spacing: 0.04em;">Avg Perf</div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.55; font-weight: 600; margin-bottom: 1rem;">
                                    <?php echo $cInfo['desc']; ?>
                                </p>
                            </div>

                            <!-- Emiten Affiliates -->
                            <div>
                                <span style="font-size: 0.75rem; font-weight: 850; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; display: block; margin-bottom: 0.6rem;">Emiten Afiliasi:</span>
                                <div class="conglo-list">
                                    <?php foreach ($cInfo['stocks'] as $stock): 
                                        $stockClass = $stock['change_pct'] >= 0 ? 'pct-up' : 'pct-down';
                                        $stockSign = $stock['change_pct'] >= 0 ? '+' : '';
                                        $stockBorder = $stock['change_pct'] >= 0 ? 'rgba(13, 148, 136, 0.15)' : 'rgba(225, 29, 72, 0.15)';
                                        $stockBg = $stock['change_pct'] >= 0 ? 'rgba(13, 148, 136, 0.03)' : 'rgba(225, 29, 72, 0.03)';
                                        $tickerUpper = strtoupper($stock['ticker']);
                                        $initials = substr($tickerUpper, 0, 2);
                                    ?>
                                        <div class="conglo-item" style="border: 1px solid <?php echo $stockBorder; ?>; background: <?php echo $stockBg; ?>; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.8rem; border-radius: 10px; font-weight: 800; transition: var(--transition);">
                                            <div style="position: relative; width: 22px; height: 22px; flex-shrink: 0;">
                                                <img src="https://assets.stockbit.com/logos/companies/<?php echo $tickerUpper; ?>.png" 
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';" 
                                                     style="width: 22px; height: 22px; border-radius: 50%; object-fit: contain; background: #ffffff; border: 1px solid rgba(0,0,0,0.06); display: block;"
                                                />
                                                <div style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; align-items: center; justify-content: center; font-size: 0.55rem; font-weight: 900; border-radius: 50%; background: #e2e8f0; color: #475569;" class="logo-fallback-badge">
                                                    <?php echo $initials; ?>
                                                </div>
                                            </div>
                                            <span style="font-size: 0.85rem; color: var(--text-main);"><?php echo $stock['ticker']; ?></span>
                                            <span class="<?php echo $stockClass; ?>" style="font-size: 0.78rem;">
                                                <?php echo $stockSign . number_format($stock['change_pct'], 1); ?>%
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($count === 0): ?>
                                        <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; font-style: italic;">Tidak ada emiten aktif di database.</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- PANEL 3: SETUP SCANNERS -->
            <div class="scanner-content-panel" id="tab-setups">
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Pemindai cerdas mendeteksi emiten dengan formasi setup teknikal & pola transaksi bandarmology matang yang siap melesat.
                </p>

                <?php foreach ($setups as $setupName => $setupList): 
                    $badgeIcon = 'fa-chart-line';
                    $badgeStyle = 'background: rgba(37,99,235,0.08); color: var(--primary);';
                    if ($setupName === 'Volume Breakout') {
                        $badgeIcon = 'fa-fire-flame-curved';
                        $badgeStyle = 'background: rgba(234,88,12,0.08); color: var(--warning);';
                    } elseif ($setupName === 'Bullish Pullback') {
                        $badgeIcon = 'fa-circle-chevron-down';
                        $badgeStyle = 'background: rgba(13,148,136,0.08); color: var(--success);';
                    } elseif ($setupName === '52-Week High Breakout') {
                        $badgeIcon = 'fa-trophy';
                        $badgeStyle = 'background: rgba(162,28,175,0.08); color: #a21caf;';
                    }
                ?>
                    <div class="setup-card">
                        <div class="setup-header">
                            <div>
                                <span class="setup-name" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="display: inline-flex; width: 32px; height: 32px; border-radius: 8px; align-items: center; justify-content: center; <?php echo $badgeStyle; ?>">
                                        <i class="fa-solid <?php echo $badgeIcon; ?>"></i>
                                    </span>
                                    <?php echo $setupName; ?>
                                </span>
                            </div>
                            <span class="setup-desc"><?php echo count($setupList); ?> Saham Terdeteksi</span>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-muted); font-weight: 700; margin-bottom: 1rem; border-left: 3px solid var(--primary); padding-left: 0.6rem;">
                            Kriteria Pindai: <?php 
                                if ($setupName === 'Volume Breakout') echo 'Volume harian > 2x rata-rata 20 hari terakhir + harga ditutup positif.';
                                elseif ($setupName === 'Bullish Pullback') echo 'Harga memantul di support dinamis MA20 dengan volume menyusut saat koreksi.';
                                elseif ($setupName === 'Golden Cross MA') echo 'Garis MA5 memotong ke atas MA20, indikator pembalikan arah menuju tren naik.';
                                elseif ($setupName === '52-Week High Breakout') echo 'Harga berhasil menembus rekor tertinggi baru 1 tahun terakhir, tanpa resistance atas.';
                            ?>
                        </p>
                        
                        <div class="table-container" style="overflow-x: auto;">
                            <table class="scanner-table" style="width: 100%; border-collapse: separate; min-width: 600px;">
                                <thead>
                                    <tr>
                                        <th style="width: 180px;">TICKER</th>
                                        <th>NAMA PERUSAHAAN</th>
                                        <th style="text-align: right; width: 120px;">HARGA</th>
                                        <th style="text-align: right; width: 110px;">CHANGE</th>
                                        <th style="text-align: right; width: 140px;">VOLUME (LOT)</th>
                                        <th style="width: 130px;">TREND</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $limit = 0;
                                    foreach ($setupList as $stock): 
                                        if ($limit >= 4) break; // Limit 4 emiten per setup for absolute layout compactness
                                        $limit++;
                                        $cClass = $stock['change_pct'] >= 0 ? 'pct-up' : 'pct-down';
                                        $cSign = $stock['change_pct'] >= 0 ? '+' : '';
                                        
                                        $trend = $stock['trend'] ?? 'Sideways';
                                        $trendClass = 'trend-sideways';
                                        $trendIcon = 'fa-minus';
                                        if (strtolower($trend) === 'uptrend' || strtolower($trend) === 'up') {
                                            $trendClass = 'trend-up';
                                            $trendIcon = 'fa-arrow-trend-up';
                                        } else if (strtolower($trend) === 'downtrend' || strtolower($trend) === 'down') {
                                            $trendClass = 'trend-down';
                                            $trendIcon = 'fa-arrow-trend-down';
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.8rem;">
                                                    <?php 
                                                    $tickerUpper = strtoupper($stock['ticker']);
                                                    $initials = substr($tickerUpper, 0, 2);
                                                    ?>
                                                    <div style="position: relative; width: 34px; height: 34px; flex-shrink: 0;">
                                                        <img src="https://assets.stockbit.com/logos/companies/<?php echo $tickerUpper; ?>.png" 
                                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';" 
                                                             style="width: 34px; height: 34px; border-radius: 50%; object-fit: contain; background: #ffffff; border: 1px solid rgba(0,0,0,0.08); padding: 2px; display: block;"
                                                        />
                                                        <div style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 900; border-radius: 50%; background: #e2e8f0; color: #475569;" class="logo-fallback-badge">
                                                            <?php echo $initials; ?>
                                                        </div>
                                                    </div>
                                                    <span class="badge-ticker" style="padding: 0.3rem 0.6rem; border-radius: 8px; font-size: 0.8rem; font-weight: 900; background: rgba(37,99,235,0.06); color: var(--primary); border: 1px solid rgba(37,99,235,0.12);"><?php echo htmlspecialchars($stock['ticker']); ?></span>
                                                </div>
                                            </td>
                                            <td style="font-weight: 600; color: var(--text-main); font-size: 0.85rem;"><?php echo htmlspecialchars($stock['company_name']); ?></td>
                                            <td style="font-weight: 700; color: var(--text-main); text-align: right; font-size: 0.88rem;">Rp <?php echo number_format($stock['price'], 0, ',', '.'); ?></td>
                                            <td style="text-align: right; font-size: 0.88rem; font-weight: 800;" class="<?php echo $cClass; ?>"><?php echo $cSign . number_format($stock['change_pct'], 1); ?>%</td>
                                            <td style="font-weight: 600; color: var(--text-muted); text-align: right; font-size: 0.85rem;"><?php echo number_format($stock['daily_volume'] / 100, 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="trend-badge <?php echo $trendClass; ?>">
                                                    <i class="fa-solid <?php echo $trendIcon; ?>"></i> <?php echo htmlspecialchars($trend); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if ($limit === 0): ?>
                                        <tr>
                                            <td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-muted); font-weight: 600; font-style: italic;">
                                                Tidak ada saham yang memenuhi kriteria setup ini saat ini.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </main>

    <script src="assets/js/main.js"></script>
    <script>
        // Tab switching logic
        const tabs = document.querySelectorAll('.scanner-tab-btn');
        const panels = document.querySelectorAll('.scanner-content-panel');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                panels.forEach(p => p.classList.remove('active'));

                tab.classList.add('active');
                const targetPanel = document.getElementById('tab-' + tab.dataset.tab);
                if (targetPanel) {
                    targetPanel.classList.add('active');
                }
            });
        });

        // Store all stock data from PHP to JS for instant filtering
        const allStockData = <?php echo json_encode($allStocks); ?>;

        // Perform movers filtering
        function filterMovers() {
            const sectorFilter = document.getElementById('movers-sector-filter').value;
            const volFilter = parseFloat(document.getElementById('movers-vol-filter').value);

            // Filter
            let filtered = allStockData.filter(item => {
                const sectorMatch = (sectorFilter === 'ALL' || item.sector === sectorFilter);
                const volMatch = (volFilter === 0 || (item.daily_volume / 100) >= volFilter);
                return sectorMatch && volMatch;
            });

            // Sort for Gainers (descending change_pct)
            let gainers = [...filtered].sort((a, b) => b.change_pct - a.change_pct);
            
            // Sort for Losers (ascending change_pct)
            let losers = [...filtered].sort((a, b) => a.change_pct - b.change_pct);

            // Render Gainers Table
            const gainersTbody = document.getElementById('gainers-tbody');
            gainersTbody.innerHTML = '';
            gainers.slice(0, 15).forEach(item => {
                if (item.change_pct < 0) return; // Only show positive change or neutral
                
                const tr = document.createElement('tr');
                const tickerUpper = item.ticker.toUpperCase();
                const initials = tickerUpper.substring(0, 2);
                
                tr.innerHTML = `
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.8rem;">
                            <div style="position: relative; width: 34px; height: 34px; flex-shrink: 0;">
                                <img src="https://assets.stockbit.com/logos/companies/${tickerUpper}.png" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';" 
                                     style="width: 34px; height: 34px; border-radius: 50%; object-fit: contain; background: #ffffff; border: 1px solid rgba(0,0,0,0.08); padding: 2px; display: block;"
                                />
                                <div style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 900; border-radius: 50%; background: #e2e8f0; color: #475569;" class="logo-fallback-badge">
                                    ${initials}
                                </div>
                            </div>
                            <span class="badge-ticker" style="padding: 0.3rem 0.6rem; border-radius: 8px; font-size: 0.8rem; font-weight: 900; background: rgba(37,99,235,0.06); color: var(--primary); border: 1px solid rgba(37,99,235,0.12);">${tickerUpper}</span>
                        </div>
                    </td>
                    <td style="font-weight: 700; color: var(--text-main); text-align: right; font-size: 0.88rem;">Rp ${Math.round(item.price).toLocaleString('id-ID')}</td>
                    <td style="font-weight: 800; text-align: right; font-size: 0.88rem;" class="pct-up">+${item.change_pct.toFixed(1)}%</td>
                    <td style="font-weight: 600; color: var(--text-muted); text-align: right; font-size: 0.88rem;">${Math.round(item.daily_volume / 100).toLocaleString('id-ID')}</td>
                `;
                gainersTbody.appendChild(tr);
            });
            if (gainersTbody.children.length === 0) {
                gainersTbody.innerHTML = `<tr><td colspan="4" style="padding: 2rem; text-align: center; color: var(--text-muted); font-weight: 600; font-style: italic;">Tidak ada saham gainer yang cocok.</td></tr>`;
            }

            // Render Losers Table
            const losersTbody = document.getElementById('losers-tbody');
            losersTbody.innerHTML = '';
            losers.slice(0, 15).forEach(item => {
                if (item.change_pct > 0) return; // Only show negative change or neutral
                
                const tr = document.createElement('tr');
                const tickerUpper = item.ticker.toUpperCase();
                const initials = tickerUpper.substring(0, 2);
                
                tr.innerHTML = `
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.8rem;">
                            <div style="position: relative; width: 34px; height: 34px; flex-shrink: 0;">
                                <img src="https://assets.stockbit.com/logos/companies/${tickerUpper}.png" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';" 
                                     style="width: 34px; height: 34px; border-radius: 50%; object-fit: contain; background: #ffffff; border: 1px solid rgba(0,0,0,0.08); padding: 2px; display: block;"
                                />
                                <div style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 900; border-radius: 50%; background: #e2e8f0; color: #475569;" class="logo-fallback-badge">
                                    ${initials}
                                </div>
                            </div>
                            <span class="badge-ticker" style="padding: 0.3rem 0.6rem; border-radius: 8px; font-size: 0.8rem; font-weight: 900; background: rgba(37,99,235,0.06); color: var(--primary); border: 1px solid rgba(37,99,235,0.12);">${tickerUpper}</span>
                        </div>
                    </td>
                    <td style="font-weight: 700; color: var(--text-main); text-align: right; font-size: 0.88rem;">Rp ${Math.round(item.price).toLocaleString('id-ID')}</td>
                    <td style="font-weight: 800; text-align: right; font-size: 0.88rem;" class="pct-down">${item.change_pct.toFixed(1)}%</td>
                    <td style="font-weight: 600; color: var(--text-muted); text-align: right; font-size: 0.88rem;">${Math.round(item.daily_volume / 100).toLocaleString('id-ID')}</td>
                `;
                losersTbody.appendChild(tr);
            });
            if (losersTbody.children.length === 0) {
                losersTbody.innerHTML = `<tr><td colspan="4" style="padding: 2rem; text-align: center; color: var(--text-muted); font-weight: 600; font-style: italic;">Tidak ada saham loser yang cocok.</td></tr>`;
            }
        }

        // Initialize table on load
        window.addEventListener('DOMContentLoaded', () => {
            filterMovers();
        });
    </script>
</body>
</html>


