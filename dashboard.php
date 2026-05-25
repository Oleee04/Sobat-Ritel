<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require 'config.php';

// Fetch summary metrics
$totalStocksQuery = "SELECT COUNT(*) as total FROM stocks";
$totalStocks = $conn->query($totalStocksQuery)->fetch_assoc()['total'];

$uptrendQuery = "SELECT COUNT(*) as total FROM stocks WHERE trend = 'Uptrend'";
$uptrendStocks = $conn->query($uptrendQuery)->fetch_assoc()['total'];

// Filter handling
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    if ($search !== '') {
        $_SESSION['active_ticker'] = strtoupper(htmlspecialchars($search));
    }
} else {
    $search = $_SESSION['active_ticker'] ?? '';
}
$sector = isset($_GET['sector']) ? $conn->real_escape_string($_GET['sector']) : '';

$where_clauses = [];
if ($search !== '') {
    $where_clauses[] = "(ticker LIKE '%$search%' OR company_name LIKE '%$search%')";
}
if ($sector !== '') {
    $where_clauses[] = "sector = '$sector'";
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

// Fetch Stocks
$stocksQuery = "SELECT * FROM stocks $where_sql ORDER BY market_cap DESC";
$stocksResult = $conn->query($stocksQuery);

// Fetch Sectors for filter
$sectorsQuery = "SELECT DISTINCT sector FROM stocks";
$sectorsResult = $conn->query($sectorsQuery);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sobat Ritel</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <li class="active"><a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="scanner.php"><i class="fa-solid fa-binoculars"></i> Market Scanner</a></li>
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
                    <input type="text" name="search" placeholder="Search ticker or company..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if($sector): ?>
                        <input type="hidden" name="sector" value="<?php echo htmlspecialchars($sector); ?>">
                    <?php endif; ?>
                </form>
            </div>
            <div class="topbar-actions">
                <button class="icon-btn"><i class="fa-regular fa-bell"></i><span class="badge">3</span></button>
                <div class="date-display"><i class="fa-regular fa-calendar"></i> <?php echo date('d M Y'); ?></div>
            </div>
        </header>

        <div class="dashboard-wrapper">
            <div class="page-header">
                <h1>Market Overview</h1>
                <p>Real-time analytics and fundamental screening for Indonesian Stocks.</p>
            </div>

            <!-- Stats Widgets -->
            <div class="stats-grid">
                <div class="stat-card glass-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-list"></i></div>
                    <div class="stat-details">
                        <h3>Total Stocks</h3>
                        <p class="number"><?php echo $totalStocks ?? 0; ?></p>
                        <span class="trend positive"><i class="fa-solid fa-arrow-trend-up"></i> Active</span>
                    </div>
                </div>
                <div class="stat-card glass-card">
                    <div class="stat-icon green"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    <div class="stat-details">
                        <h3>Uptrend Stocks</h3>
                        <p class="number"><?php echo $uptrendStocks ?? 0; ?></p>
                        <span class="trend positive">Bullish</span>
                    </div>
                </div>
                <div class="stat-card glass-card">
                    <div class="stat-icon purple"><i class="fa-solid fa-money-bill-wave"></i></div>
                    <div class="stat-details">
                        <h3>Avg P/E Ratio</h3>
                        <p class="number">15.4x</p>
                        <span class="trend neutral">Market Avg</span>
                    </div>
                </div>
                <div class="stat-card glass-card" style="display: flex; flex-direction: column; justify-content: center; align-items: stretch; padding: 0.6rem 1rem; min-height: 108px; overflow: hidden;">
                    <!-- TradingView Widget BEGIN -->
                    <div class="tradingview-widget-container" style="width: 100%;">
                        <div class="tradingview-widget-container__widget"></div>
                        <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-single-quote.js" async>
                        {
                          "symbol": "IDX:COMPOSITE",
                          "width": "100%",
                          "isTransparent": true,
                          "colorTheme": "light",
                          "locale": "id"
                        }
                        </script>
                    </div>
                    <!-- TradingView Widget END -->
                </div>
            </div>

            <!-- Charts Section (Top) -->
            <div class="charts-grid">
                <div class="glass-card chart-container">
                    <h3>Sector Distribution</h3>
                    <canvas id="sectorChart"></canvas>
                </div>
                <div class="glass-card chart-container">
                    <h3>Market Cap Overview (Top 5)</h3>
                    <canvas id="marketCapChart"></canvas>
                </div>
            </div>

            <!-- Live TradingView Screener Widget Section (Bottom) -->
            <div class="table-section glass-card" style="padding: 1.5rem; overflow: hidden; border-radius: var(--border-radius);">
                <div class="section-header" style="border-bottom: none; padding-bottom: 1.2rem; padding-left: 0; padding-right: 0;">
                    <h2>Indonesian Stock Market <span style="color:var(--text-muted);font-size:0.9rem;font-weight:600;">(Live TradingView Quotes)</span></h2>
                </div>
                <!-- Widget tinggi diperbesar agar semua saham langsung terlihat tanpa scroll internal -->
                <div class="tradingview-widget-container" style="width: 100%; height: 1400px; border-radius: var(--border-radius); overflow: hidden;">
                    <div class="tradingview-widget-container__widget" style="width: 100%; height: 100%;"></div>
                    <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-screener.js" async>
                    {
                      "width": "100%",
                      "height": "100%",
                      "defaultColumn": "overview",
                      "defaultScreen": "most_capitalized",
                      "market": "indonesia",
                      "showToolbar": true,
                      "colorTheme": "light",
                      "locale": "id",
                      "isTransparent": false,
                      "largeChartUrl": ""
                    }
                    </script>
                </div>
            </div>
        </div>
    </main>
    <script src="assets/js/main.js"></script>
</body>
</html>


