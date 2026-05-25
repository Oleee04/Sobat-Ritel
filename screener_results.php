<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require 'config.php';

$whereClauses = [];
$params = [];
$types = "";

$fields = $_GET['rule_field'] ?? [];
$ops = $_GET['rule_operator'] ?? [];
$vals = $_GET['rule_value'] ?? [];

$allowedFields = ['price', 'pe_ratio', 'pbv', 'roe', 'market_cap', 'volume'];
$allowedOps = ['>', '<', '=', '>=', '<='];

$humanReadableRules = [];

if (is_array($fields)) {
    for ($i = 0; $i < count($fields); $i++) {
        $field = $fields[$i] ?? '';
        $op = $ops[$i] ?? '';
        $val = $vals[$i] ?? '';

        if (in_array($field, $allowedFields) && in_array($op, $allowedOps) && is_numeric($val)) {
            $whereClauses[] = "$field $op ?";
            $params[] = (float)$val;
            $types .= "d"; // double
            
            $fieldName = '';
            switch ($field) {
                case 'pe_ratio': $fieldName = 'P/E Ratio'; break;
                case 'pbv': $fieldName = 'PBV'; break;
                case 'roe': $fieldName = 'ROE'; break;
                case 'price': $fieldName = 'Price'; break;
                case 'market_cap': $fieldName = 'Market Cap'; break;
                case 'volume': $fieldName = 'Volume'; break;
            }
            
            $suffix = ($field === 'roe') ? '%' : '';
            $prefix = ($field === 'price') ? 'Rp ' : '';
            
            $humanReadableRules[] = "$fieldName $op $prefix" . number_format($val, 0, ',', '.') . $suffix;
        }
    }
}

$sql = "SELECT ticker, company_name, sector, price, pe_ratio, pbv, roe, market_cap, volume, trend FROM stocks";
if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
} else {
    // Default limit if no filter
    $sql .= " LIMIT 50"; 
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$stocks = [];
while ($row = $result->fetch_assoc()) {
    // Gunakan data harga dan nama emiten dari database lokal secara instan (menghilangkan bottleneck N+1 HTTP Request)
    $stocks[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screener Results - Sobat Ritel</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Modern Ice-Blue & White Glassmorphism Overrides */
        body.dashboard-body {
            background: #f4f7fa;
            color: #1e293b;
            font-family: 'Inter', sans-serif;
        }

        /* Floating Tech Radial Lights */
        .screener-glow {
            position: fixed;
            width: 700px;
            height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.08) 0%, rgba(37, 99, 235, 0) 70%);
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

        /* Topbar Light Mode Overrides */
        .topbar {
            background: rgba(255, 255, 255, 0.8) !important;
            border-bottom: 1px solid rgba(37, 99, 235, 0.1) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            z-index: 10;
        }

        .topbar .search-bar input {
            background: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #0f172a !important;
            font-weight: 600;
        }

        .topbar .search-bar i {
            color: #64748b !important;
        }

        .topbar .date-display {
            background: rgba(37, 99, 235, 0.06) !important;
            color: #2563eb !important;
            border: 1px solid rgba(37, 99, 235, 0.1) !important;
            font-weight: 700;
        }

        .topbar .icon-btn {
            background: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #475569 !important;
        }

        /* Sidebar Glass Syncing */
        .sidebar {
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 10px 0 30px rgba(0, 0, 0, 0.05);
            z-index: 11;
        }

        /* Results Display Card */
        .table-section {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(37, 99, 235, 0.12);
            border-radius: 32px;
            padding: 2.2rem;
            box-shadow: 0 15px 40px rgba(37, 99, 235, 0.02);
            position: relative;
            z-index: 1;
        }

        .data-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .data-table th {
            background: rgba(37, 99, 235, 0.04);
            color: #1e293b;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.1em;
            padding: 1.2rem 1.4rem;
            border-bottom: 2.5px solid rgba(37, 99, 235, 0.08);
        }

        .data-table td {
            padding: 1.15rem 1.4rem;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .data-table tr:hover td {
            background: rgba(37, 99, 235, 0.02) !important;
        }

        .badge-ticker {
            background: rgba(37, 99, 235, 0.08);
            color: #2563eb;
            border: 1.5px solid rgba(37, 99, 235, 0.15);
            padding: 0.35rem 0.7rem;
            border-radius: 8px;
            font-weight: 900;
            font-size: 0.82rem;
            letter-spacing: 0.5px;
        }

        .company-name {
            font-weight: 800;
            color: #0f172a;
        }

        /* Trend Badges */
        .trend-badge {
            font-size: 0.72rem;
            font-weight: 850;
            padding: 0.35rem 0.65rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .trend-up {
            background: rgba(16, 185, 129, 0.08);
            color: #0d9488;
            border: 1px solid rgba(16, 185, 129, 0.15);
        }

        .trend-down {
            background: rgba(239, 68, 68, 0.08);
            color: #e11d48;
            border: 1px solid rgba(239, 68, 68, 0.15);
        }

        .trend-sideways {
            background: rgba(100, 116, 139, 0.08);
            color: #64748b;
            border: 1px solid rgba(100, 116, 139, 0.15);
        }

        .btn-outline {
            background: #ffffff !important;
            border: 1.5px solid #2563eb !important;
            color: #2563eb !important;
            padding: 0.72rem 1.6rem !important;
            border-radius: 12px !important;
            cursor: pointer !important;
            font-weight: 800 !important;
            font-size: 0.88rem !important;
            transition: all 0.2s !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-outline:hover {
            background: rgba(37, 99, 235, 0.05) !important;
            transform: translateY(-1px) !important;
        }

        /* Monogram Fallback Logo Custom Styling */
        .ticker-col-wrapper {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .ticker-logo-badge {
            font-size: 0.8rem;
            font-weight: 900;
            border-radius: 50%;
            text-transform: uppercase;
            letter-spacing: -0.2px;
        }

        .logo-fallback { background: #e2e8f0; color: #475569; }
        .logo-bbca { background: #e0f2fe; color: #0369a1; }
        .logo-bbri { background: #dbeafe; color: #1d4ed8; }
        .logo-bmri { background: #fef9c3; color: #a16207; }
        .logo-tlkm { background: #fee2e2; color: #b91c1c; }
        .logo-goto { background: #dcfce7; color: #15803d; }
        .logo-bumi { background: #fae8ff; color: #a21caf; }
        .logo-bris { background: #ccfbf1; color: #0f766e; }
        .logo-bipi { background: #f3e8ff; color: #6b21a8; }
        .logo-wbsa { background: #ffedd5; color: #c2410c; }
        .logo-aspr { background: #e0e7ff; color: #4338ca; }
    </style>
</head>
<body class="dashboard-body">
    
    <!-- Light Tech Ambient Glows -->
    <div class="screener-glow glow-1"></div>
    <div class="screener-glow glow-2"></div>

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
            <li class="active"><a href="screener.php"><i class="fa-solid fa-filter"></i> Screener</a></li>
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
                <form action="technical.php" method="GET">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="ticker" placeholder="Search ticker or company..." value="<?php echo htmlspecialchars($_SESSION['active_ticker'] ?? ''); ?>">
                </form>
            </div>
            <div class="topbar-actions">
                <button class="icon-btn"><i class="fa-regular fa-bell"></i><span class="badge">3</span></button>
                <div class="date-display"><i class="fa-regular fa-calendar"></i> <?php echo date('d M Y'); ?></div>
            </div>
        </header>

        <div class="dashboard-wrapper" style="position: relative; z-index: 1; padding: 2rem;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h2 style="font-size: 1.8rem; font-weight: 900; color: #0f172a; margin: 0; letter-spacing: -0.8px;">Screener Results</h2>
                    <p style="color: #64748b; font-size: 0.9rem; margin-top: 0.25rem; font-weight: 600;">Found <?php echo count($stocks); ?> matching stocks</p>
                </div>
                <a href="screener.php" class="btn-outline" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 800;">
                    <i class="fa-solid fa-arrow-left"></i> Back to Screener
                </a>
            </div>

            <?php if (!empty($humanReadableRules)): ?>
            <div style="display: flex; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 1.8rem; align-items: center;">
                <span style="font-size: 0.8rem; font-weight: 850; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-right: 0.4rem;">Active Filters:</span>
                <?php foreach ($humanReadableRules as $hrRule): ?>
                    <span style="background: rgba(37, 99, 235, 0.05); color: #2563eb; border: 1.5px solid rgba(37, 99, 235, 0.12); padding: 0.4rem 0.85rem; border-radius: 12px; font-weight: 800; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-circle-check" style="font-size: 0.75rem;"></i> <?php echo htmlspecialchars($hrRule); ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="table-section">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ticker</th>
                                <th>Company</th>
                                <th>Sector</th>
                                <th>Price</th>
                                <th>P/E Ratio</th>
                                <th>PBV</th>
                                <th>ROE</th>
                                <th>Volume</th>
                                <th>Market Cap</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stocks)): ?>
                            <tr>
                                <td colspan="10" class="text-center empty-state" style="text-align: center; padding: 4rem;">
                                    <i class="fa-solid fa-folder-open" style="font-size: 3rem; color: #94a3b8; margin-bottom: 1.2rem;"></i>
                                    <p style="color: #64748b; font-weight: 800; font-size: 1.05rem;">No stocks match your screening criteria.</p>
                                    <p style="color: #94a3b8; font-size: 0.88rem; margin-top: 0.4rem;">Try adjusting your thresholds or adding different rules.</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($stocks as $stock): 
                                    $trend = $stock['trend'] ?? 'Sideways';
                                    $trendClass = 'trend-sideways';
                                    $trendIcon = 'fa-minus';
                                    if ($trend === 'Uptrend') {
                                        $trendClass = 'trend-up';
                                        $trendIcon = 'fa-arrow-trend-up';
                                    } else if ($trend === 'Downtrend') {
                                        $trendClass = 'trend-down';
                                        $trendIcon = 'fa-arrow-trend-down';
                                    }
                                    ?>
                                <tr>
                                    <td>
                                        <div class="ticker-col-wrapper">
                                            <?php 
                                            $tickerUpper = strtoupper($stock['ticker']);
                                            $presets = ['BBCA', 'BBRI', 'BMRI', 'TLKM', 'GOTO', 'BUMI', 'BRIS', 'BIPI', 'WBSA', 'ASPR'];
                                            $logoClass = 'logo-fallback';
                                            if (in_array($tickerUpper, $presets)) {
                                                $logoClass = 'logo-' . strtolower($tickerUpper);
                                            }
                                            $initials = substr($tickerUpper, 0, 2);
                                            ?>
                                            <div style="position: relative; width: 38px; height: 38px; flex-shrink: 0;">
                                                <img src="https://assets.stockbit.com/logos/companies/<?php echo $tickerUpper; ?>.png" 
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';" 
                                                     style="width: 38px; height: 38px; border-radius: 50%; object-fit: contain; background: #ffffff; border: 1px solid rgba(0,0,0,0.08); padding: 2px; display: block;"
                                                />
                                                <div class="ticker-logo-badge <?php echo $logoClass; ?>" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; align-items: center; justify-content: center;">
                                                    <?php echo $initials; ?>
                                                </div>
                                            </div>
                                            <span class="badge-ticker"><?php echo htmlspecialchars($stock['ticker']); ?></span>
                                        </div>
                                    </td>
                                    <td class="company-name"><?php echo htmlspecialchars($stock['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($stock['sector']); ?></td>
                                    <td class="stock-price font-weight-bold" data-ticker="<?php echo htmlspecialchars($stock['ticker']); ?>" data-price="<?php echo $stock['price']; ?>" style="color: #0f172a; font-weight: 800;">Rp <?php echo number_format($stock['price'], 0, ',', '.'); ?></td>
                                    <td class="stock-pe <?php echo $stock['pe_ratio'] < 10 ? 'text-success' : ($stock['pe_ratio'] > 20 ? 'text-danger' : ''); ?>" data-pe="<?php echo $stock['pe_ratio']; ?>"><?php echo number_format($stock['pe_ratio'], 2); ?></td>
                                    <td class="stock-pbv <?php echo $stock['pbv'] < 1 ? 'text-success' : ($stock['pbv'] > 3 ? 'text-danger' : ''); ?>" data-pbv="<?php echo $stock['pbv']; ?>"><?php echo number_format($stock['pbv'], 2); ?></td>
                                    <td class="stock-roe <?php echo $stock['roe'] > 15 ? 'text-success' : ''; ?>" style="color: #2563eb; font-weight: 700;"><?php echo number_format($stock['roe'], 2); ?>%</td>
                                    <td><?php echo number_format($stock['volume'], 0, ',', '.'); ?></td>
                                    <td class="stock-cap" data-cap="<?php echo $stock['market_cap']; ?>">Rp <?php echo number_format($stock['market_cap'] / 1000000000, 2, ',', '.'); ?> B</td>
                                    <td>
                                        <span class="trend-badge <?php echo $trendClass; ?>">
                                            <i class="fa-solid <?php echo $trendIcon; ?>"></i> <?php echo htmlspecialchars($trend); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Live Real-Time Stock Price Simulation in Screener Results
            setInterval(() => {
                const rows = document.querySelectorAll('.data-table tbody tr');
                rows.forEach(row => {
                    const priceCell = row.querySelector('.stock-price');
                    if (!priceCell) return;
                    
                    const ticker = priceCell.getAttribute('data-ticker');
                    const currentPrice = parseFloat(priceCell.getAttribute('data-price'));
                    if (!currentPrice) return;

                    // Small random price fluctuation (-0.5% to +0.5%)
                    const pctChange = (Math.random() * 1.0 - 0.5) / 100;
                    const changeVal = currentPrice * pctChange;
                    const newPrice = Math.round(currentPrice + changeVal);

                    // Update data-price
                    priceCell.setAttribute('data-price', newPrice);
                    priceCell.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(newPrice);

                    // Visual Flash Effect
                    if (pctChange > 0) {
                        priceCell.style.color = '#10b981'; // vibrant green
                        setTimeout(() => priceCell.style.color = '#0f172a', 600);
                    } else if (pctChange < 0) {
                        priceCell.style.color = '#ef4444'; // vibrant red
                        setTimeout(() => priceCell.style.color = '#0f172a', 600);
                    }

                    // Dynamically recalculate P/E, PBV, Market Cap
                    const peCell = row.querySelector('.stock-pe');
                    if (peCell) {
                        const originalPe = parseFloat(peCell.getAttribute('data-pe'));
                        if (originalPe) {
                            const newPe = originalPe * (newPrice / currentPrice);
                            peCell.setAttribute('data-pe', newPe);
                            peCell.textContent = newPe.toFixed(2);
                        }
                    }

                    const pbvCell = row.querySelector('.stock-pbv');
                    if (pbvCell) {
                        const originalPbv = parseFloat(pbvCell.getAttribute('data-pbv'));
                        if (originalPbv) {
                            const newPbv = originalPbv * (newPrice / currentPrice);
                            pbvCell.setAttribute('data-pbv', newPbv);
                            pbvCell.textContent = newPbv.toFixed(2);
                        }
                    }

                    const capCell = row.querySelector('.stock-cap');
                    if (capCell) {
                        const originalCap = parseFloat(capCell.getAttribute('data-cap'));
                        if (originalCap) {
                            const newCap = originalCap * (newPrice / currentPrice);
                            capCell.setAttribute('data-cap', newCap);
                            capCell.textContent = 'Rp ' + (newCap / 1000000000).toFixed(2) + ' B';
                        }
                    }
                });
            }, 3000);
        });
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>


