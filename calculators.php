<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require 'config.php';

// Fetch all stocks for real-time populate feature
$tickersQuery = "SELECT ticker, company_name, price, dividend_per_share FROM stocks ORDER BY ticker ASC";
$tickersResult = $conn->query($tickersQuery);
$tickers = [];
if ($tickersResult) {
    while ($row = $tickersResult->fetch_assoc()) {
        $tickers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator Saham - Sobat Ritel</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .calc-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            margin-top: 1.5rem;
        }
        .calc-tabs {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .calc-tab-btn {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid var(--card-border);
            padding: 1.2rem;
            border-radius: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 700;
            color: var(--text-muted);
            transition: var(--transition);
            text-align: left;
            backdrop-filter: var(--glass-blur);
        }
        .calc-tab-btn i {
            font-size: 1.3rem;
            color: var(--primary);
            transition: var(--transition);
        }
        .calc-tab-btn:hover {
            background: rgba(255, 255, 255, 0.95);
            color: var(--primary);
            transform: translateX(5px);
            border-color: rgba(37, 99, 235, 0.3);
        }
        .calc-tab-btn.active {
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: white;
            border-color: transparent;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.2);
        }
        .calc-tab-btn.active i {
            color: white;
        }
        .calc-content {
            background: rgba(255, 255, 255, 0.85);
            border-radius: 24px;
            border: 1px solid var(--card-border);
            padding: 2rem;
            backdrop-filter: var(--glass-blur);
            min-height: 500px;
        }
        .calc-panel {
            display: none;
        }
        .calc-panel.active {
            display: block;
            animation: fadeIn 0.4s ease-out;
        }
        .calc-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2.5rem;
            margin-top: 1.5rem;
        }
        @media (max-width: 992px) {
            .calc-container {
                grid-template-columns: 1fr;
            }
            .calc-tabs {
                flex-direction: row;
                overflow-x: auto;
                padding-bottom: 0.5rem;
            }
            .calc-tab-btn {
                white-space: nowrap;
                padding: 0.8rem 1.2rem;
            }
            .calc-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .calc-form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }
        .calc-form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .calc-form-group label {
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .calc-input-wrapper {
            position: relative;
        }
        .calc-input-wrapper span {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 700;
            color: #64748b;
        }
        .calc-input-wrapper input, .calc-input-wrapper select {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.8rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        .calc-input-wrapper.no-prefix input {
            padding-left: 1rem;
        }
        .calc-input-wrapper input:focus, .calc-input-wrapper select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .calc-results {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 18px;
            padding: 1.8rem;
            border: 1px solid rgba(37, 99, 235, 0.08);
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            height: fit-content;
        }
        .calc-results h4 {
            font-size: 1.1rem;
            color: var(--text-main);
            border-bottom: 1.5px solid rgba(37, 99, 235, 0.1);
            padding-bottom: 0.8rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .calc-result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 0.8rem;
            border-bottom: 1px dashed #cbd5e1;
        }
        .calc-result-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .calc-result-item span.label {
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .calc-result-item span.value {
            font-weight: 800;
            color: var(--text-main);
            font-size: 1rem;
        }
        .calc-result-item.highlight {
            background: rgba(37, 99, 235, 0.05);
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid rgba(37, 99, 235, 0.1);
            margin-top: 0.5rem;
        }
        .calc-result-item.highlight span.value {
            color: var(--primary);
            font-size: 1.2rem;
        }
        .calc-btn {
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 12px;
            font-weight: 850;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.15);
            margin-top: 0.5rem;
        }
        .calc-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.25);
        }
        .alert-calc {
            background: rgba(234, 88, 12, 0.08);
            border: 1px solid rgba(234, 88, 12, 0.2);
            color: #c2410c;
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 600;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            line-height: 1.4;
        }
        .alert-calc i {
            font-size: 1.1rem;
            margin-top: 0.1rem;
        }
        
        /* ── STOCK SELECTOR CARD ── */
        .emiten-selector-card {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            backdrop-filter: var(--glass-blur);
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
            display: grid;
            grid-template-columns: 1.2fr 2fr;
            gap: 2rem;
            align-items: center;
            transition: var(--transition);
        }
        .emiten-selector-card:hover {
            border-color: rgba(37, 99, 235, 0.2);
            box-shadow: 0 8px 30px rgba(37, 99, 235, 0.05);
        }
        @media (max-width: 768px) {
            .emiten-selector-card {
                grid-template-columns: 1fr;
                gap: 1.2rem;
            }
        }
        .emiten-search-box {
            position: relative;
        }
        .emiten-search-box i.search-icon {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1rem;
            pointer-events: none;
        }
        .emiten-search-box input {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 3rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.95rem;
            transition: var(--transition);
            background: white;
            color: var(--text-main);
        }
        .emiten-search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .emiten-search-box i.clear-icon {
            position: absolute;
            right: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            display: none;
            transition: var(--transition);
        }
        .emiten-search-box i.clear-icon:hover {
            color: #64748b;
        }
        .search-suggestions-list {
            position: absolute;
            top: 110%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            max-height: 250px;
            overflow-y: auto;
            z-index: 100;
            display: none;
            padding: 0.5rem;
        }
        .suggestion-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
        }
        .suggestion-item:hover {
            background: rgba(37, 99, 235, 0.05);
        }
        .suggestion-item .ticker-badge {
            background: rgba(37, 99, 235, 0.08);
            color: var(--primary);
            font-weight: 900;
            font-size: 0.8rem;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .suggestion-item .company-name {
            font-weight: 700;
            color: var(--text-main);
            font-size: 0.85rem;
            margin-left: 0.75rem;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .suggestion-item .price-badge {
            font-weight: 800;
            color: #475569;
            font-size: 0.85rem;
        }
        .emiten-info-panel {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            background: rgba(255, 255, 255, 0.5);
            border: 1.5px dashed #cbd5e1;
            padding: 0.85rem 1.25rem;
            border-radius: 16px;
            min-height: 72px;
            transition: var(--transition);
        }
        .emiten-info-panel.selected {
            background: rgba(37, 99, 235, 0.02);
            border-color: rgba(37, 99, 235, 0.25);
            border-style: solid;
        }
        .emiten-info-empty {
            color: var(--text-muted);
            font-size: 0.88rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            width: 100%;
        }
        .emiten-info-details {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 1.5rem;
        }
        .emiten-meta {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            min-width: 0;
            flex: 1;
        }
        .emiten-meta .company {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .emiten-meta .ticker-price-row {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .emiten-meta .ticker-price-row .ticker {
            font-weight: 900;
            color: var(--text-main);
            font-size: 1.3rem;
            text-transform: uppercase;
            letter-spacing: -0.5px;
        }
        .emiten-meta .ticker-price-row .price {
            font-weight: 900;
            color: var(--primary);
            font-size: 1.25rem;
        }
        .emiten-stats {
            display: flex;
            gap: 1.5rem;
            flex-shrink: 0;
        }
        .emiten-stat-item {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.15rem;
        }
        .emiten-stat-item .label {
            font-size: 0.65rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .emiten-stat-item .val {
            font-weight: 850;
            color: var(--text-main);
            font-size: 1rem;
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
            <li><a href="scanner.php"><i class="fa-solid fa-binoculars"></i> Market Scanner</a></li>
            <li><a href="screener.php"><i class="fa-solid fa-filter"></i> Screener</a></li>
            <li><a href="technical.php"><i class="fa-solid fa-chart-line"></i> Technical</a></li>
            <li><a href="broker.php"><i class="fa-solid fa-user-tie"></i> Broker Stalker</a></li>
            <li><a href="bandarmology.php"><i class="fa-solid fa-user-shield"></i> Bandarmology</a></li>
            <li class="active"><a href="calculators.php"><i class="fa-solid fa-calculator"></i> Calculators</a></li>
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
                <h1>Financial Tools & Calculators</h1>
                <p>Kalkulator investasi profesional untuk membantu pengambilan keputusan transaksi Anda.</p>
            </div>

            <!-- Global Ticker Selector Card -->
            <div class="emiten-selector-card">
                <div class="emiten-search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="emiten-search-input" placeholder="Cari & Pilih Saham (e.g. BBCA, BBRI)..." autocomplete="off">
                    <i class="fa-solid fa-circle-xmark clear-icon" id="emiten-clear-btn"></i>
                    <div class="search-suggestions-list" id="emiten-suggestions"></div>
                </div>
                <div class="emiten-info-panel" id="emiten-info-panel">
                    <div class="emiten-info-empty" id="emiten-info-empty">
                        <i class="fa-solid fa-circle-info" style="color: var(--primary); font-size: 1.1rem;"></i>
                        <span>Pilih emiten untuk mengisi nilai kalkulator secara real-time</span>
                    </div>
                    <div class="emiten-info-details" id="emiten-info-details" style="display: none;">
                        <div class="emiten-meta">
                            <div class="ticker-price-row">
                                <span class="ticker" id="selected-emiten-ticker">-</span>
                                <span class="price" id="selected-emiten-price">Rp 0</span>
                            </div>
                            <div class="company" id="selected-emiten-company">-</div>
                        </div>
                        <div class="emiten-stats">
                            <div class="emiten-stat-item">
                                <span class="label">Dividen / Lbr</span>
                                <span class="val" id="selected-emiten-dividend">Rp 0</span>
                            </div>
                            <div class="emiten-stat-item" style="align-items: flex-end;">
                                <span class="label" style="margin-bottom: 0.2rem;">Sumber Data</span>
                                <span class="val" id="selected-emiten-source" style="font-size: 0.72rem; font-weight: 700; padding: 0.25rem 0.5rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.3rem; transition: var(--transition); background: #f1f5f9; color: var(--text-muted); border: 1px solid #e2e8f0;">
                                    <i class="fa-solid fa-database"></i> Database
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="calc-container">
                <!-- Sidebar Tabs -->
                <div class="calc-tabs">
                    <button class="calc-tab-btn active" data-tab="avg">
                        <i class="fa-solid fa-chart-bar"></i>
                        <span>Kalkulator Average Saham</span>
                    </button>
                    <button class="calc-tab-btn" data-tab="rights">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Kalkulator Rights Issue</span>
                    </button>
                    <button class="calc-tab-btn" data-tab="recovery">
                        <i class="fa-solid fa-arrows-down-to-line"></i>
                        <span>Kalkulator Recovery</span>
                    </button>
                    <button class="calc-tab-btn" data-tab="dividend">
                        <i class="fa-solid fa-money-bill-trend-up"></i>
                        <span>Kalkulator Dividen</span>
                    </button>
                    <button class="calc-tab-btn" data-tab="allocation">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Kalkulator Alokasi Dana</span>
                    </button>
                </div>

                <!-- Content Area -->
                <div class="calc-content">
                    
                    <!-- PANEL 1: AVERAGE SAHAM -->
                    <div class="calc-panel active" id="tab-avg">
                        <h2>Kalkulator Average Saham</h2>
                        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Hitung rata-rata harga beli saham Anda setelah melakukan beberapa kali transaksi (Average Up / Down).</p>
                        <div class="calc-grid">
                            <form class="calc-form" id="form-avg" oninput="calculateAverage()">
                                <div style="display: flex; flex-direction: column; gap: 1rem;">
                                    <h4 style="color: var(--primary); font-size: 0.95rem;"><i class="fa-solid fa-circle-1"></i> Pembelian Pertama</h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                        <div class="calc-form-group">
                                            <label>Harga Beli (Rp)</label>
                                            <div class="calc-input-wrapper">
                                                <span>Rp</span>
                                                <input type="number" id="avg-price-1" placeholder="e.g. 5000" min="0">
                                            </div>
                                        </div>
                                        <div class="calc-form-group">
                                            <label>Jumlah (Lot)</label>
                                            <div class="calc-input-wrapper no-prefix">
                                                <input type="number" id="avg-lot-1" placeholder="e.g. 10" min="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 1rem; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 1rem;">
                                    <h4 style="color: var(--primary); font-size: 0.95rem;"><i class="fa-solid fa-circle-2"></i> Pembelian Kedua</h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                        <div class="calc-form-group">
                                            <label>Harga Beli (Rp)</label>
                                            <div class="calc-input-wrapper">
                                                <span>Rp</span>
                                                <input type="number" id="avg-price-2" placeholder="e.g. 4500" min="0">
                                            </div>
                                        </div>
                                        <div class="calc-form-group">
                                            <label>Jumlah (Lot)</label>
                                            <div class="calc-input-wrapper no-prefix">
                                                <input type="number" id="avg-lot-2" placeholder="e.g. 20" min="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 1rem; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 1rem;">
                                    <h4 style="color: var(--primary); font-size: 0.95rem;"><i class="fa-solid fa-circle-3"></i> Pembelian Ketiga (Opsional)</h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                        <div class="calc-form-group">
                                            <label>Harga Beli (Rp)</label>
                                            <div class="calc-input-wrapper">
                                                <span>Rp</span>
                                                <input type="number" id="avg-price-3" placeholder="e.g. 4000" min="0">
                                            </div>
                                        </div>
                                        <div class="calc-form-group">
                                            <label>Jumlah (Lot)</label>
                                            <div class="calc-input-wrapper no-prefix">
                                                <input type="number" id="avg-lot-3" placeholder="e.g. 30" min="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div class="calc-results">
                                <h4><i class="fa-solid fa-square-poll-vertical"></i> Hasil Kalkulasi</h4>
                                <div class="calc-result-item">
                                    <span class="label">Total Lot</span>
                                    <span class="value" id="avg-res-totallot">0 Lot</span>
                                </div>
                                <div class="calc-result-item">
                                    <span class="label">Total Lembar Saham</span>
                                    <span class="value" id="avg-res-totalshares">0 Lembar</span>
                                </div>
                                <div class="calc-result-item">
                                    <span class="label">Total Nilai Investasi</span>
                                    <span class="value" id="avg-res-totalvalue">Rp 0</span>
                                </div>
                                <div class="calc-result-item highlight">
                                    <span class="label">Harga Rata-Rata</span>
                                    <span class="value" id="avg-res-average">Rp 0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PANEL 2: RIGHTS ISSUE -->
                    <div class="calc-panel" id="tab-rights">
                        <h2>Kalkulator Rights Issue</h2>
                        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Hitung harga teoritis saham setelah pelaksanaan Rights Issue (HMETD) dan kebutuhan dana penebusan.</p>
                        <div class="calc-grid">
                            <form class="calc-form" id="form-rights" oninput="calculateRights()">
                                <div class="calc-form-group">
                                    <label>Harga Saham Sebelum Ex-Date (Cum Price)</label>
                                    <div class="calc-input-wrapper">
                                        <span>Rp</span>
                                        <input type="number" id="rights-cum" placeholder="e.g. 3000" min="0">
                                    </div>
                                </div>
                                <div class="calc-form-group">
                                    <label>Harga Pelaksanaan / Tebus Rights</label>
                                    <div class="calc-input-wrapper">
                                        <span>Rp</span>
                                        <input type="number" id="rights-exercise" placeholder="e.g. 2000" min="0">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="calc-form-group">
                                        <label>Rasio Saham Lama</label>
                                        <div class="calc-input-wrapper no-prefix">
                                            <input type="number" id="rights-ratio-old" placeholder="e.g. 5" min="1">
                                        </div>
                                    </div>
                                    <div class="calc-form-group">
                                        <label>Rasio Saham Baru</label>
                                        <div class="calc-input-wrapper no-prefix">
                                            <input type="number" id="rights-ratio-new" placeholder="e.g. 2" min="1">
                                        </div>
                                    </div>
                                </div>
                                <div class="calc-form-group" style="border-top: 1px solid rgba(0,0,0,0.05); padding-top: 1rem;">
                                    <label>Kepemilikan Saham Lama (Opsional)</label>
                                    <div class="calc-input-wrapper no-prefix">
                                        <input type="number" id="rights-holdings" placeholder="e.g. 100 Lot" min="0">
                                    </div>
                                    <span style="font-size: 0.78rem; color: var(--text-muted);">Masukkan jumlah kepemilikan Anda saat ini (dalam satuan LOT) untuk mengetahui jumlah dana penebusan.</span>
                                </div>
                            </form>
                            <div class="calc-results">
                                <h4><i class="fa-solid fa-square-poll-vertical"></i> Hasil Perhitungan</h4>
                                <div class="calc-result-item">
                                    <span class="label">Rasio Rights</span>
                                    <span class="value" id="rights-res-ratio">-</span>
                                </div>
                                <div class="calc-result-item">
                                    <span class="label">Diskon Harga Tebus</span>
                                    <span class="value" id="rights-res-discount">0%</span>
                                </div>
                                <div class="calc-result-item highlight">
                                    <span class="label">Harga Teoritis (Ex-Price)</span>
                                    <span class="value" id="rights-res-theoretical">Rp 0</span>
                                </div>
                                <div style="border-top: 1px solid rgba(0,0,0,0.1); margin-top: 0.5rem; padding-top: 0.8rem;">
                                    <h5 style="font-size: 0.9rem; color: var(--text-main); margin-bottom: 0.8rem;"><i class="fa-solid fa-cash-register"></i> Penebusan Portofolio Anda</h5>
                                    <div class="calc-result-item">
                                        <span class="label">Jumlah Hak Rights</span>
                                        <span class="value" id="rights-res-qty">0 Lot (0 Lembar)</span>
                                    </div>
                                    <div class="calc-result-item">
                                        <span class="label">Dana Penebusan</span>
                                        <span class="value" id="rights-res-cost" style="color: var(--danger); font-weight: 800;">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PANEL 3: RECOVERY (AVERAGE DOWN TARGET) -->
                    <div class="calc-panel" id="tab-recovery">
                        <h2>Kalkulator Recovery (Average Down Target)</h2>
                        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Cari tahu berapa lot saham yang harus Anda beli di harga pasar saat ini agar harga rata-rata turun mencapai target harga impian Anda.</p>
                        <div class="calc-grid">
                            <form class="calc-form" id="form-recovery" oninput="calculateRecovery()">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="calc-form-group">
                                        <label>Harga Rata-Rata Saat Ini</label>
                                        <div class="calc-input-wrapper">
                                            <span>Rp</span>
                                            <input type="number" id="rec-price-old" placeholder="e.g. 8000" min="0">
                                        </div>
                                    </div>
                                    <div class="calc-form-group">
                                        <label>Jumlah Kepemilikan (Lot)</label>
                                        <div class="calc-input-wrapper no-prefix">
                                            <input type="number" id="rec-lot-old" placeholder="e.g. 50" min="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="calc-form-group">
                                    <label>Harga Pasar Sekarang (Beli Baru)</label>
                                    <div class="calc-input-wrapper">
                                        <span>Rp</span>
                                        <input type="number" id="rec-price-current" placeholder="e.g. 5000" min="0">
                                    </div>
                                </div>
                                <div class="calc-form-group">
                                    <label>Target Harga Rata-Rata Baru</label>
                                    <div class="calc-input-wrapper">
                                        <span>Rp</span>
                                        <input type="number" id="rec-price-target" placeholder="e.g. 6000" min="0">
                                    </div>
                                </div>
                            </form>
                            <div class="calc-results">
                                <h4><i class="fa-solid fa-square-poll-vertical"></i> Hasil Simulasi</h4>
                                <div id="rec-alert-container"></div>
                                <div class="calc-result-item">
                                    <span class="label">Sangkut / Kerugian Harga</span>
                                    <span class="value" id="rec-res-loss">-</span>
                                </div>
                                <div class="calc-result-item">
                                    <span class="label">Dana yang Terikat Sekarang</span>
                                    <span class="value" id="rec-res-currentval">Rp 0</span>
                                </div>
                                <div class="calc-result-item highlight">
                                    <span class="label">Lot Beli Baru yang Dibutuhkan</span>
                                    <span class="value" id="rec-res-neededlots">0 Lot</span>
                                </div>
                                <div class="calc-result-item">
                                    <span class="label">Tambahan Dana Baru</span>
                                    <span class="value" id="rec-res-newfund" style="color: var(--primary);">Rp 0</span>
                                </div>
                                <div class="calc-result-item" style="border-top: 1px solid rgba(0,0,0,0.1); margin-top: 0.5rem; padding-top: 0.8rem;">
                                    <span class="label">Total Dana Gabungan</span>
                                    <span class="value" id="rec-res-combinedfund" style="font-weight: 800;">Rp 0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PANEL 4: DIVIDEN -->
                    <div class="calc-panel" id="tab-dividend">
                        <h2>Kalkulator Dividen</h2>
                        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Hitung total penerimaan dividen kotor, estimasi potongan pajak, dividen bersih, dan persentase imbal hasil (*dividend yield*).</p>
                        <div class="calc-grid">
                            <form class="calc-form" id="form-dividend" oninput="calculateDividend()">
                                <div class="calc-form-group">
                                    <label>Harga Rata-Rata Beli Saham</label>
                                    <div class="calc-input-wrapper">
                                        <span>Rp</span>
                                        <input type="number" id="div-avg-price" placeholder="e.g. 4000" min="1">
                                    </div>
                                </div>
                                <div class="calc-form-group">
                                    <label>Jumlah Kepemilikan (Lot)</label>
                                    <div class="calc-input-wrapper no-prefix">
                                        <input type="number" id="div-lots" placeholder="e.g. 100" min="0">
                                    </div>
                                </div>
                                <div class="calc-form-group">
                                    <label>Dividen Per Lembar Saham (DPS)</label>
                                    <div class="calc-input-wrapper">
                                        <span>Rp</span>
                                        <input type="number" id="div-dps" placeholder="e.g. 200" min="0">
                                    </div>
                                    <span style="font-size: 0.78rem; color: var(--text-muted);">Nilai dividen per lembar saham yang diumumkan emiten (Dividend Per Share).</span>
                                </div>
                                <div class="calc-form-group">
                                    <label>Potongan Pajak Dividen WNI</label>
                                    <div class="calc-input-wrapper no-prefix">
                                        <select id="div-tax-option">
                                            <option value="10">Kena Pajak PPh Final (10%)</option>
                                            <option value="0">Bebas Pajak (Diinvestasikan Kembali)</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                            <div class="calc-results">
                                <h4><i class="fa-solid fa-square-poll-vertical"></i> Hasil Imbal Hasil</h4>
                                <div class="calc-result-item">
                                    <span class="label">Total Lembar Saham</span>
                                    <span class="value" id="div-res-shares">0 Lembar</span>
                                </div>
                                <div class="calc-result-item">
                                    <span class="label">Dividen Kotor (Gross)</span>
                                    <span class="value" id="div-res-gross">Rp 0</span>
                                </div>
                                <div class="calc-result-item">
                                    <span class="label">Potongan Pajak (PPh)</span>
                                    <span class="value" id="div-res-tax" style="color: var(--danger);">Rp 0</span>
                                </div>
                                <div class="calc-result-item highlight">
                                    <span class="label">Dividen Bersih (Net)</span>
                                    <span class="value" id="div-res-net">Rp 0</span>
                                </div>
                                <div class="calc-result-item" style="border-top: 1px solid rgba(0,0,0,0.1); margin-top: 0.5rem; padding-top: 0.8rem;">
                                    <span class="label">Dividend Yield (Harga Beli)</span>
                                    <span class="value" id="div-res-yield" style="color: var(--success); font-weight: 850;">0.00%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PANEL 5: ALOKASI DANA (MONEY MANAGEMENT) -->
                    <div class="calc-panel" id="tab-allocation">
                        <h2>Kalkulator Alokasi Dana (Money Management)</h2>
                        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Kelola risiko portofolio Anda secara ilmiah. Tentukan ukuran posisi (*position size*) terbaik berdasarkan toleransi risiko Anda.</p>
                        <div class="calc-grid">
                            <form class="calc-form" id="form-allocation" oninput="calculateAllocation()">
                                <div class="calc-form-group">
                                    <label>Total Modal Investasi (Equity)</label>
                                    <div class="calc-input-wrapper">
                                        <span>Rp</span>
                                        <input type="number" id="alloc-equity" placeholder="e.g. 50000000" min="0">
                                    </div>
                                </div>
                                <div class="calc-form-group">
                                    <label>Toleransi Risiko Maksimal per Transaksi</label>
                                    <div class="calc-input-wrapper no-prefix">
                                        <select id="alloc-risk-percent">
                                            <option value="0.5">0.5% dari Modal (Sangat Konservatif)</option>
                                            <option value="1" selected>1% dari Modal (Konservatif)</option>
                                            <option value="2">2% dari Modal (Moderat)</option>
                                            <option value="3">3% dari Modal (Agresif)</option>
                                            <option value="5">5% dari Modal (Sangat Agresif)</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 1rem;">
                                    <div class="calc-form-group">
                                        <label>Harga Beli / Entry</label>
                                        <div class="calc-input-wrapper">
                                            <span>Rp</span>
                                            <input type="number" id="alloc-entry" placeholder="e.g. 2000" min="1">
                                        </div>
                                    </div>
                                    <div class="calc-form-group">
                                        <label>Harga Stop Loss</label>
                                        <div class="calc-input-wrapper">
                                            <span>Rp</span>
                                            <input type="number" id="alloc-sl" placeholder="e.g. 1900" min="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="calc-form-group">
                                    <label>Target Harga Profit (Take Profit)</label>
                                    <div class="calc-input-wrapper">
                                        <span>Rp</span>
                                        <input type="number" id="alloc-tp" placeholder="e.g. 2300" min="0">
                                    </div>
                                </div>
                            </form>
                            <div class="calc-results">
                                <h4><i class="fa-solid fa-square-poll-vertical"></i> Manajemen Risiko</h4>
                                <div class="calc-result-item">
                                    <span class="label">Risiko Rupiah Maksimal</span>
                                    <span class="value" id="alloc-res-risk-rp" style="color: var(--danger);">Rp 0</span>
                                </div>
                                <div class="calc-result-item">
                                    <span class="label">Persentase Stop Loss</span>
                                    <span class="value" id="alloc-res-sl-percent">0%</span>
                                </div>
                                <div class="calc-result-item highlight">
                                    <span class="label">Ukuran Posisi Maksimal</span>
                                    <span class="value" id="alloc-res-max-lots">0 Lot</span>
                                </div>
                                <div class="calc-result-item">
                                    <span class="label">Dana Terpakai (Eksposur)</span>
                                    <span class="value" id="alloc-res-capital-used">Rp 0</span>
                                </div>
                                <div class="calc-result-item">
                                    <span class="label">Porsi Alokasi Modal</span>
                                    <span class="value" id="alloc-res-capital-percent">0.00%</span>
                                </div>
                                <div class="calc-result-item" style="border-top: 1px solid rgba(0,0,0,0.1); margin-top: 0.5rem; padding-top: 0.8rem;">
                                    <span class="label">Risk-to-Reward Ratio</span>
                                    <span class="value" id="alloc-res-rr" style="font-weight: 850;">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
    <script>
        // Tab switching logic
        const tabs = document.querySelectorAll('.calc-tab-btn');
        const panels = document.querySelectorAll('.calc-content .calc-panel');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // remove active class from all tabs & panels
                tabs.forEach(t => t.classList.remove('active'));
                panels.forEach(p => p.classList.remove('active'));

                // add active class to clicked tab and its panel
                tab.classList.add('active');
                const targetPanel = document.getElementById('tab-' + tab.dataset.tab);
                if (targetPanel) {
                    targetPanel.classList.add('active');
                }
            });
        });

        // Formatting utilities
        function formatRupiah(number) {
            if (isNaN(number) || number === null || number === Infinity || number === -Infinity) return 'Rp 0';
            return 'Rp ' + Math.round(number).toLocaleString('id-ID');
        }

        // 1. Average Saham Perhitungan
        function calculateAverage() {
            const p1 = parseFloat(document.getElementById('avg-price-1').value) || 0;
            const l1 = parseFloat(document.getElementById('avg-lot-1').value) || 0;
            const p2 = parseFloat(document.getElementById('avg-price-2').value) || 0;
            const l2 = parseFloat(document.getElementById('avg-lot-2').value) || 0;
            const p3 = parseFloat(document.getElementById('avg-price-3').value) || 0;
            const l3 = parseFloat(document.getElementById('avg-lot-3').value) || 0;

            const totalLots = l1 + l2 + l3;
            const totalShares = totalLots * 100;
            const totalValue = (p1 * l1 * 100) + (p2 * l2 * 100) + (p3 * l3 * 100);
            const averagePrice = totalLots > 0 ? (totalValue / totalShares) : 0;

            document.getElementById('avg-res-totallot').innerText = totalLots.toLocaleString('id-ID') + ' Lot';
            document.getElementById('avg-res-totalshares').innerText = totalShares.toLocaleString('id-ID') + ' Lembar';
            document.getElementById('avg-res-totalvalue').innerText = formatRupiah(totalValue);
            document.getElementById('avg-res-average').innerText = formatRupiah(averagePrice);
        }

        // 2. Rights Issue Perhitungan
        function calculateRights() {
            const cum = parseFloat(document.getElementById('rights-cum').value) || 0;
            const exercise = parseFloat(document.getElementById('rights-exercise').value) || 0;
            const oldRatio = parseFloat(document.getElementById('rights-ratio-old').value) || 0;
            const newRatio = parseFloat(document.getElementById('rights-ratio-new').value) || 0;
            const holdings = parseFloat(document.getElementById('rights-holdings').value) || 0;

            let ratioStr = '-';
            let discountPercent = '0%';
            let theoretical = 0;
            let rightsQty = 0;
            let exerciseCost = 0;

            if (oldRatio > 0 && newRatio > 0) {
                ratioStr = `${oldRatio} : ${newRatio}`;
                theoretical = ((cum * oldRatio) + (exercise * newRatio)) / (oldRatio + newRatio);
                
                if (cum > 0) {
                    const discount = ((cum - exercise) / cum) * 100;
                    discountPercent = discount.toFixed(2) + '%';
                }
            }

            if (holdings > 0 && oldRatio > 0) {
                // holdings is in LOT. 1 LOT = 100 shares.
                const totalOldShares = holdings * 100;
                const totalRightsShares = Math.floor((totalOldShares * newRatio) / oldRatio);
                rightsQty = totalRightsShares / 100;
                exerciseCost = totalRightsShares * exercise;
            }

            document.getElementById('rights-res-ratio').innerText = ratioStr;
            document.getElementById('rights-res-discount').innerText = discountPercent;
            document.getElementById('rights-res-theoretical').innerText = formatRupiah(theoretical);
            document.getElementById('rights-res-qty').innerText = rightsQty.toLocaleString('id-ID') + ' Lot (' + (rightsQty * 100).toLocaleString('id-ID') + ' Lembar)';
            document.getElementById('rights-res-cost').innerText = formatRupiah(exerciseCost);
        }

        // 3. Recovery Perhitungan
        function calculateRecovery() {
            const pOld = parseFloat(document.getElementById('rec-price-old').value) || 0;
            const lOld = parseFloat(document.getElementById('rec-lot-old').value) || 0;
            const pCurr = parseFloat(document.getElementById('rec-price-current').value) || 0;
            const pTarget = parseFloat(document.getElementById('rec-price-target').value) || 0;

            let lossStr = '-';
            let currentVal = lOld * 100 * pOld;
            let neededLots = 0;
            let newFund = 0;
            let combinedFund = 0;
            let alertBox = document.getElementById('rec-alert-container');
            alertBox.innerHTML = ''; // reset alerts

            if (pOld > 0 && pCurr > 0) {
                const pctLoss = ((pCurr - pOld) / pOld) * 100;
                lossStr = pctLoss.toFixed(2) + '%';
            }

            if (pOld > 0 && lOld > 0 && pCurr > 0 && pTarget > 0) {
                // Periksa batas logis target harga rata-rata
                // Target must be strictly between pCurr and pOld
                if (pOld > pCurr) { // Sangkut (Average Down)
                    if (pTarget >= pOld) {
                        alertBox.innerHTML = `
                            <div class="alert-calc">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Target harga rata-rata sudah terpenuhi atau lebih besar dari harga sangkut awal Anda. Tidak perlu membeli lagi.</span>
                            </div>`;
                    } else if (pTarget <= pCurr) {
                        alertBox.innerHTML = `
                            <div class="alert-calc">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Target harga rata-rata tidak boleh sama atau lebih rendah dari harga pasar sekarang (Rp ${pCurr.toLocaleString('id-ID')}).</span>
                            </div>`;
                    } else {
                        // Formula: L_new = L_old * (p_old - p_target) / (p_target - p_curr)
                        neededLots = (lOld * (pOld - pTarget)) / (pTarget - pCurr);
                        neededLots = Math.ceil(neededLots); // Selalu bulatkan ke atas untuk Lot
                        newFund = neededLots * 100 * pCurr;
                        combinedFund = currentVal + newFund;
                    }
                } else if (pOld < pCurr) { // Average Up Target
                    if (pTarget <= pOld) {
                        alertBox.innerHTML = `
                            <div class="alert-calc">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Target harga rata-rata baru harus lebih tinggi dari harga awal (Rp ${pOld.toLocaleString('id-ID')}) dalam kasus Average Up.</span>
                            </div>`;
                    } else if (pTarget >= pCurr) {
                        alertBox.innerHTML = `
                            <div class="alert-calc">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Target harga rata-rata baru tidak boleh melebihi harga pasar saat ini (Rp ${pCurr.toLocaleString('id-ID')}).</span>
                            </div>`;
                    } else {
                        // Formula: L_new = L_old * (p_target - p_old) / (p_curr - p_target)
                        neededLots = (lOld * (pTarget - pOld)) / (pCurr - pTarget);
                        neededLots = Math.ceil(neededLots);
                        newFund = neededLots * 100 * pCurr;
                        combinedFund = currentVal + newFund;
                    }
                }
            }

            document.getElementById('rec-res-loss').innerText = lossStr;
            document.getElementById('rec-res-currentval').innerText = formatRupiah(currentVal);
            document.getElementById('rec-res-neededlots').innerText = neededLots.toLocaleString('id-ID') + ' Lot';
            document.getElementById('rec-res-newfund').innerText = formatRupiah(newFund);
            document.getElementById('rec-res-combinedfund').innerText = formatRupiah(combinedFund);
        }

        // 4. Dividen Perhitungan
        function calculateDividend() {
            const avgPrice = parseFloat(document.getElementById('div-avg-price').value) || 0;
            const lots = parseFloat(document.getElementById('div-lots').value) || 0;
            const dps = parseFloat(document.getElementById('div-dps').value) || 0;
            const taxRate = parseFloat(document.getElementById('div-tax-option').value) || 0;

            const totalShares = lots * 100;
            const grossDividend = totalShares * dps;
            const taxAmount = (grossDividend * taxRate) / 100;
            const netDividend = grossDividend - taxAmount;

            let yieldPercent = 0;
            if (avgPrice > 0) {
                yieldPercent = (dps / avgPrice) * 100;
            }

            document.getElementById('div-res-shares').innerText = totalShares.toLocaleString('id-ID') + ' Lembar';
            document.getElementById('div-res-gross').innerText = formatRupiah(grossDividend);
            document.getElementById('div-res-tax').innerText = formatRupiah(taxAmount);
            document.getElementById('div-res-net').innerText = formatRupiah(netDividend);
            document.getElementById('div-res-yield').innerText = yieldPercent.toFixed(2) + '%';
        }

        // 5. Alokasi Dana (Money Management) Perhitungan
        function calculateAllocation() {
            const equity = parseFloat(document.getElementById('alloc-equity').value) || 0;
            const riskPct = parseFloat(document.getElementById('alloc-risk-percent').value) || 0;
            const entry = parseFloat(document.getElementById('alloc-entry').value) || 0;
            const sl = parseFloat(document.getElementById('alloc-sl').value) || 0;
            const tp = parseFloat(document.getElementById('alloc-tp').value) || 0;

            const maxRiskRp = (equity * riskPct) / 100;
            let slPercent = 0;
            let maxLots = 0;
            let capitalUsed = 0;
            let capitalPercent = 0;
            let rrRatio = '-';

            if (entry > 0) {
                if (sl < entry && sl > 0) {
                    const riskPerShare = entry - sl;
                    slPercent = (riskPerShare / entry) * 100;
                    
                    const maxShares = maxRiskRp / riskPerShare;
                    maxLots = Math.floor(maxShares / 100);
                    
                    capitalUsed = maxLots * 100 * entry;
                    if (equity > 0) {
                        capitalPercent = (capitalUsed / equity) * 100;
                    }
                }
                
                if (tp > entry && sl < entry && sl > 0) {
                    const reward = tp - entry;
                    const risk = entry - sl;
                    const rr = reward / risk;
                    rrRatio = `1 : ${rr.toFixed(2)}`;
                }
            }

            document.getElementById('alloc-res-risk-rp').innerText = formatRupiah(maxRiskRp);
            document.getElementById('alloc-res-sl-percent').innerText = slPercent.toFixed(2) + '%';
            document.getElementById('alloc-res-max-lots').innerText = maxLots.toLocaleString('id-ID') + ' Lot';
            document.getElementById('alloc-res-capital-used').innerText = formatRupiah(capitalUsed);
            document.getElementById('alloc-res-capital-percent').innerText = capitalPercent.toFixed(2) + '%';
            document.getElementById('alloc-res-rr').innerText = rrRatio;
        }

        // ── Emiten Selector Logic ──
        const stockList = <?php echo json_encode($tickers); ?>;
        let selectedStock = null;

        const searchInput = document.getElementById('emiten-search-input');
        const clearBtn = document.getElementById('emiten-clear-btn');
        const suggestionsContainer = document.getElementById('emiten-suggestions');
        const infoPanel = document.getElementById('emiten-info-panel');
        const infoEmpty = document.getElementById('emiten-info-empty');
        const infoDetails = document.getElementById('emiten-info-details');

        const displayTicker = document.getElementById('selected-emiten-ticker');
        const displayPrice = document.getElementById('selected-emiten-price');
        const displayCompany = document.getElementById('selected-emiten-company');
        const displayDividend = document.getElementById('selected-emiten-dividend');

        // Show/hide clear icon & render suggestions on typing
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim().toUpperCase();
            clearBtn.style.display = query.length > 0 ? 'block' : 'none';
            renderSuggestions(query);
        });

        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            clearBtn.style.display = 'none';
            suggestionsContainer.style.display = 'none';
            resetSelectedStock();
        });

        // Close suggestions dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });

        // Open suggestions on focus
        searchInput.addEventListener('focus', () => {
            const query = searchInput.value.trim().toUpperCase();
            renderSuggestions(query);
        });

        function renderSuggestions(query) {
            suggestionsContainer.innerHTML = '';
            
            // Filter stocks
            let filtered = stockList.filter(stock => 
                stock.ticker.toUpperCase().includes(query) || 
                stock.company_name.toUpperCase().includes(query)
            );

            // Smart Sort: Prioritize exact matches, then starts-with ticker, then starts-with company name
            filtered.sort((a, b) => {
                const aTicker = a.ticker.toUpperCase();
                const bTicker = b.ticker.toUpperCase();
                const aName = a.company_name.toUpperCase();
                const bName = b.company_name.toUpperCase();

                // 1. Exact ticker match
                if (aTicker === query && bTicker !== query) return -1;
                if (bTicker === query && aTicker !== query) return 1;

                // 2. Ticker starts with query
                if (aTicker.startsWith(query) && !bTicker.startsWith(query)) return -1;
                if (bTicker.startsWith(query) && !aTicker.startsWith(query)) return 1;

                // 3. Company name starts with query
                if (aName.startsWith(query) && !bName.startsWith(query)) return -1;
                if (bName.startsWith(query) && !aName.startsWith(query)) return 1;

                // 4. Default alphabetical ticker
                return aTicker.localeCompare(bTicker);
            });

            if (filtered.length === 0) {
                suggestionsContainer.innerHTML = '<div style="padding: 0.75rem; text-align: center; color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">Emiten tidak ditemukan</div>';
                suggestionsContainer.style.display = 'block';
                return;
            }

            filtered.forEach(stock => {
                const item = document.createElement('div');
                item.className = 'suggestion-item';
                item.innerHTML = `
                    <div style="display: flex; align-items: center; min-width: 0;">
                        <span class="ticker-badge">${escapeHtml(stock.ticker)}</span>
                        <span class="company-name">${escapeHtml(stock.company_name)}</span>
                    </div>
                    <span class="price-badge">${formatRupiah(stock.price)}</span>
                `;
                item.addEventListener('click', () => {
                    selectStock(stock);
                    suggestionsContainer.style.display = 'none';
                });
                suggestionsContainer.appendChild(item);
            });

            suggestionsContainer.style.display = 'block';
        }

        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function selectStock(stock) {
            selectedStock = { ...stock }; // Clone to avoid modifying original list
            searchInput.value = stock.ticker + ' - ' + stock.company_name;
            clearBtn.style.display = 'block';

            // Show details card
            infoEmpty.style.display = 'none';
            infoDetails.style.display = 'flex';
            infoPanel.classList.add('selected');

            displayTicker.innerText = stock.ticker;
            displayPrice.innerText = formatRupiah(stock.price);
            displayCompany.innerText = stock.company_name;
            displayDividend.innerText = formatRupiah(stock.dividend_per_share || 0);

            const sourceBadge = document.getElementById('selected-emiten-source');
            if (sourceBadge) {
                sourceBadge.innerHTML = '<i class="fa-solid fa-database"></i> Database';
                sourceBadge.style.color = 'var(--text-muted)';
                sourceBadge.style.background = '#f1f5f9';
                sourceBadge.style.border = '1px solid #e2e8f0';
            }

            // Populate active tab with database price first (instant feedback)
            populateActiveCalculator();

            // Now fetch real-time price from TradingView API proxy
            fetchRealTimePrice(stock.ticker);
        }

        function fetchRealTimePrice(ticker) {
            const sourceBadge = document.getElementById('selected-emiten-source');
            const displayPriceEl = document.getElementById('selected-emiten-price');

            if (sourceBadge) {
                sourceBadge.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Live Syncing...';
                sourceBadge.style.color = 'var(--primary)';
                sourceBadge.style.background = 'rgba(37, 99, 235, 0.05)';
                sourceBadge.style.border = '1px solid rgba(37, 99, 235, 0.15)';
            }

            fetch(`api_price.php?ticker=${encodeURIComponent(ticker)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && selectedStock && selectedStock.ticker === ticker) {
                        const realTimePrice = parseFloat(data.price);
                        
                        // Update in-memory stock price
                        selectedStock.price = realTimePrice;
                        
                        // Update display with standard format
                        displayPriceEl.innerText = formatRupiah(realTimePrice);

                        // Update status badge to show TradingView Live success
                        if (sourceBadge) {
                            sourceBadge.innerHTML = '<i class="fa-solid fa-bolt"></i> TradingView Live';
                            sourceBadge.style.color = '#10b981';
                            sourceBadge.style.background = '#ecfdf5';
                            sourceBadge.style.border = '1px solid #a7f3d0';
                        }

                        // Re-populate active calculator with the new real-time price
                        populateActiveCalculator();
                    } else if (sourceBadge) {
                        // Keep database value if not found on TV
                        sourceBadge.innerHTML = '<i class="fa-solid fa-database"></i> Database Price';
                        sourceBadge.style.color = 'var(--text-muted)';
                        sourceBadge.style.background = '#f1f5f9';
                        sourceBadge.style.border = '1px solid #e2e8f0';
                    }
                })
                .catch(err => {
                    console.error('Error fetching real-time price:', err);
                    if (sourceBadge) {
                        sourceBadge.innerHTML = '<i class="fa-solid fa-database"></i> Database Price';
                        sourceBadge.style.color = 'var(--text-muted)';
                        sourceBadge.style.background = '#f1f5f9';
                        sourceBadge.style.border = '1px solid #e2e8f0';
                    }
                });
        }

        function resetSelectedStock() {
            selectedStock = null;
            infoEmpty.style.display = 'flex';
            infoDetails.style.display = 'none';
            infoPanel.classList.remove('selected');
        }

        function populateActiveCalculator() {
            if (!selectedStock) return;

            // Get active tab from the buttons
            const activeTabBtn = document.querySelector('.calc-tab-btn.active');
            if (!activeTabBtn) return;
            const tabId = activeTabBtn.dataset.tab;
            
            const price = parseFloat(selectedStock.price) || 0;
            const dividend = parseFloat(selectedStock.dividend_per_share) || 0;

            if (tabId === 'avg') {
                const inputPrice = document.getElementById('avg-price-1');
                if (inputPrice) {
                    inputPrice.value = price;
                    calculateAverage();
                }
            } else if (tabId === 'rights') {
                const inputCum = document.getElementById('rights-cum');
                if (inputCum) {
                    inputCum.value = price;
                    calculateRights();
                }
            } else if (tabId === 'recovery') {
                const inputPriceCurrent = document.getElementById('rec-price-current');
                if (inputPriceCurrent) {
                    inputPriceCurrent.value = price;
                    calculateRecovery();
                }
            } else if (tabId === 'dividend') {
                const inputAvgPrice = document.getElementById('div-avg-price');
                const inputDps = document.getElementById('div-dps');
                if (inputAvgPrice) inputAvgPrice.value = price;
                if (inputDps) inputDps.value = dividend;
                calculateDividend();
            } else if (tabId === 'allocation') {
                const inputEntry = document.getElementById('alloc-entry');
                if (inputEntry) {
                    inputEntry.value = price;
                    calculateAllocation();
                }
            }

            // Highlight auto-filled fields with a soft green glow briefly
            highlightAutoFilledFields(tabId);
        }

        function highlightAutoFilledFields(tabId) {
            let fields = [];
            if (tabId === 'avg') fields = ['avg-price-1'];
            else if (tabId === 'rights') fields = ['rights-cum'];
            else if (tabId === 'recovery') fields = ['rec-price-current'];
            else if (tabId === 'dividend') fields = ['div-avg-price', 'div-dps'];
            else if (tabId === 'allocation') fields = ['alloc-entry'];

            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.style.borderColor = '#10b981';
                    el.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.15)';
                    setTimeout(() => {
                        el.style.borderColor = '';
                        el.style.boxShadow = '';
                    }, 1200);
                }
            });
        }

        // When switching tabs, if a stock is selected, populate it
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                setTimeout(() => {
                    populateActiveCalculator();
                }, 50);
            });
        });
    </script>
</body>
</html>


