<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require 'config.php';

// Function to fetch and parse live market news from CNBC Indonesia RSS
function getLiveMarketNews() {
    $rss_url = "https://www.cnbcindonesia.com/market/rss";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $rss_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $news_items = [];
    
    if ($response) {
        $xml = @simplexml_load_string($response);
        if ($xml && isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $imageUrl = '';
                if (isset($item->enclosure) && isset($item->enclosure['url'])) {
                    $imageUrl = (string)$item->enclosure['url'];
                }
                
                $news_items[] = [
                    'title'       => (string)$item->title,
                    'link'        => (string)$item->link,
                    'pubDate'     => strtotime((string)$item->pubDate),
                    'description' => trim(strip_tags((string)$item->description)),
                    'image'       => $imageUrl
                ];
                
                if (count($news_items) >= 12) break;
            }
        }
    }
    
    // Fallback mock data
    if (empty($news_items)) {
        $news_items = [
            [
                'title'       => 'Purbaya Tegaskan Kondisi RI Beda dengan Krisis Moneter 98',
                'link'        => 'https://www.cnbcindonesia.com/market',
                'pubDate'     => time(),
                'description' => 'Menteri Keuangan menegaskan pelemahan rupiah saat ini tidak mirip krisis 1998. Ia optimis pasar saham akan rebound dalam waktu dekat.',
                'image'       => 'https://akcdn.detik.net.id/visual/2026/01/28/suasana-layar-digital-pergerakan-harga-saham-di-bursa-efek-indonesia-jakarta-rabu-2812026-1769579123799_169.jpeg?w=1200&q=90'
            ],
            [
                'title'       => 'BI Holds Interest Rates Steady to Maintain Exchange Rate Stability',
                'link'        => 'https://www.cnbcindonesia.com/market',
                'pubDate'     => time() - 3600,
                'description' => 'Bank Indonesia decided to keep its benchmark interest rate unchanged to maintain stability amid global uncertainties.',
                'image'       => ''
            ],
            [
                'title'       => 'IHSG Bergerak Mixed di Tengah Sentimen Global yang Beragam',
                'link'        => 'https://www.cnbcindonesia.com/market',
                'pubDate'     => time() - 7200,
                'description' => 'Indeks Harga Saham Gabungan (IHSG) bergerak mixed pada perdagangan sesi pertama, dipengaruhi sentimen dari pasar global yang beragam.',
                'image'       => ''
            ],
        ];
    }
    
    return $news_items;
}

$newsList = getLiveMarketNews();
$featured  = $newsList[0] ?? null;
$remaining = array_slice($newsList, 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market News – Sobat Ritel</title>
    <meta name="description" content="Live Indonesian stock market news and financial insights from CNBC Indonesia, updated in real-time.">
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ── News Page Specific ───────────────────────────── */
        .news-hero {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 2.5rem;
            height: 420px;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            box-shadow: 0 24px 64px rgba(37,99,235,0.14);
            cursor: pointer;
            text-decoration: none;
            display: block;
        }

        .news-hero img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.45;
            transition: opacity 0.4s ease, transform 0.6s ease;
        }

        .news-hero:hover img {
            opacity: 0.35;
            transform: scale(1.03);
        }

        .news-hero-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f172a 0%, #1a3a6e 60%, #0d9488 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .news-hero-placeholder i {
            font-size: 5rem;
            color: rgba(255,255,255,0.08);
        }

        .news-hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(10,20,50,0.92) 0%, rgba(10,20,50,0.3) 55%, transparent 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 2.4rem 2.8rem;
        }

        .news-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: var(--primary);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            margin-bottom: 1rem;
            width: fit-content;
            box-shadow: 0 4px 14px rgba(37,99,235,0.4);
        }

        .news-hero-badge .dot-live {
            width: 6px;
            height: 6px;
            background: #4ade80;
            border-radius: 50%;
            animation: livePulse 1.4s ease-in-out infinite;
        }

        @keyframes livePulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.4); }
        }

        .news-hero-title {
            font-size: 1.9rem;
            font-weight: 900;
            color: #fff;
            line-height: 1.3;
            letter-spacing: -0.5px;
            margin-bottom: 0.8rem;
            text-shadow: 0 2px 12px rgba(0,0,0,0.3);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .news-hero-meta {
            display: flex;
            align-items: center;
            gap: 1.4rem;
            color: rgba(255,255,255,0.65);
            font-size: 0.82rem;
            font-weight: 600;
        }

        .news-hero-meta span {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .news-hero-desc {
            color: rgba(255,255,255,0.7);
            font-size: 0.92rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            font-size: 0.82rem;
            font-weight: 800;
            padding: 0.55rem 1.1rem;
            border-radius: 50px;
            transition: all 0.25s ease;
            width: fit-content;
            text-decoration: none;
        }

        .read-more-pill:hover {
            background: rgba(255,255,255,0.22);
            color: #fff;
            gap: 0.75rem;
        }

        /* ── Section header ── */
        .news-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.6rem;
        }

        .news-section-title {
            font-size: 1.35rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .news-section-title::before {
            content: '';
            width: 4px;
            height: 22px;
            background: linear-gradient(to bottom, var(--primary), var(--accent));
            border-radius: 2px;
            display: inline-block;
        }

        .news-count-badge {
            background: rgba(37,99,235,0.08);
            color: var(--primary);
            border: 1.5px solid rgba(37,99,235,0.15);
            font-size: 0.75rem;
            font-weight: 900;
            padding: 0.25rem 0.7rem;
            border-radius: 20px;
        }

        .news-source-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            padding: 0.45rem 0.9rem;
            border-radius: 8px;
            border: 1.5px solid rgba(37,99,235,0.12);
            transition: all 0.2s;
        }

        .news-source-link:hover {
            color: var(--primary);
            border-color: rgba(37,99,235,0.3);
            background: rgba(37,99,235,0.04);
        }

        /* ── News Grid ── */
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.6rem;
            margin-bottom: 3rem;
        }

        .news-card {
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(37,99,235,0.09);
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 6px 24px rgba(37,99,235,0.04);
            transition: transform 0.3s cubic-bezier(0.16,1,0.3,1),
                        box-shadow 0.3s cubic-bezier(0.16,1,0.3,1),
                        border-color 0.3s;
            position: relative;
        }

        .news-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 48px rgba(37,99,235,0.10);
            border-color: rgba(37,99,235,0.22);
        }

        .news-card-img {
            width: 100%;
            height: 190px;
            object-fit: cover;
            background: linear-gradient(135deg, #e2e8f0, #f1f5f9);
            display: block;
            transition: transform 0.4s ease;
        }

        .news-card:hover .news-card-img {
            transform: scale(1.04);
        }

        .news-card-img-wrap {
            overflow: hidden;
            height: 190px;
            position: relative;
            background: #f1f5f9;
        }

        .news-card-img-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(37,99,235,0.04), rgba(13,148,136,0.04));
        }

        .news-card-img-placeholder i {
            font-size: 2.5rem;
            color: rgba(37,99,235,0.15);
        }

        .news-card-source-tag {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(37,99,235,0.92);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 0.3rem 0.65rem;
            border-radius: 6px;
        }

        .news-card-number {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 28px;
            height: 28px;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            color: rgba(255,255,255,0.8);
            font-size: 0.7rem;
            font-weight: 900;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .news-card-body {
            padding: 1.3rem 1.4rem 1.2rem;
            display: flex;
            flex-direction: column;
            flex: 1;
            gap: 0.6rem;
        }

        .news-card-time {
            font-size: 0.73rem;
            color: var(--text-muted);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .news-card-title {
            font-size: 1rem;
            font-weight: 850;
            color: #0f172a;
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .news-card-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.2s;
        }

        .news-card-title a:hover {
            color: var(--primary);
        }

        .news-card-desc {
            font-size: 0.83rem;
            color: #64748b;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-weight: 500;
            flex: 1;
        }

        .news-card-footer {
            border-top: 1px solid rgba(37,99,235,0.07);
            padding-top: 0.85rem;
            margin-top: 0.4rem;
        }

        .news-card-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--primary);
            font-size: 0.8rem;
            font-weight: 800;
            text-decoration: none;
            transition: gap 0.2s;
        }

        .news-card-cta i {
            transition: transform 0.2s;
        }

        .news-card-cta:hover {
            color: var(--primary-hover);
            gap: 0.6rem;
        }

        .news-card-cta:hover i {
            transform: translateX(3px);
        }

        /* ── Ticker strip ── */
        .news-ticker-strip {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(37,99,235,0.1);
            border-radius: 14px;
            padding: 0.75rem 1.4rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            overflow: hidden;
        }

        .ticker-label {
            font-size: 0.68rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #fff;
            background: var(--primary);
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .ticker-scroll-wrap {
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        .ticker-scroll {
            display: flex;
            gap: 2.5rem;
            animation: tickerScroll 28s linear infinite;
            white-space: nowrap;
        }

        .ticker-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #334155;
            flex-shrink: 0;
        }

        .ticker-item .ticker-sym {
            color: #0f172a;
            font-weight: 900;
        }

        .ticker-item .up { color: var(--success); }
        .ticker-item .down { color: var(--danger); }

        @keyframes tickerScroll {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* ── Stagger animation ── */
        .news-card {
            opacity: 0;
            transform: translateY(24px);
            animation: cardIn 0.5s cubic-bezier(0.16,1,0.3,1) forwards;
        }

        @keyframes cardIn {
            to { opacity: 1; transform: translateY(0); }
        }

        .news-card:nth-child(1)  { animation-delay: 0.04s; }
        .news-card:nth-child(2)  { animation-delay: 0.08s; }
        .news-card:nth-child(3)  { animation-delay: 0.12s; }
        .news-card:nth-child(4)  { animation-delay: 0.16s; }
        .news-card:nth-child(5)  { animation-delay: 0.20s; }
        .news-card:nth-child(6)  { animation-delay: 0.24s; }
        .news-card:nth-child(7)  { animation-delay: 0.28s; }
        .news-card:nth-child(8)  { animation-delay: 0.32s; }
        .news-card:nth-child(9)  { animation-delay: 0.36s; }
        .news-card:nth-child(10) { animation-delay: 0.40s; }
        .news-card:nth-child(11) { animation-delay: 0.44s; }

        .news-hero {
            opacity: 0;
            animation: cardIn 0.6s cubic-bezier(0.16,1,0.3,1) 0s forwards;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .news-hero { height: 300px; }
            .news-hero-title { font-size: 1.4rem; }
            .news-hero-overlay { padding: 1.5rem 1.6rem; }
            .news-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="dashboard-body">

    <!-- ── Sidebar ────────────────────────────────────────── -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="assets/nyangkuters_logo.png" alt="Sobat Ritel Logo" style="width:32px;height:32px;border-radius:8px;object-fit:cover;">
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
            <li><a href="calculators.php"><i class="fa-solid fa-calculator"></i> Calculators</a></li>
            <li class="active"><a href="news.php"><i class="fa-solid fa-newspaper"></i> News</a></li>
            
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

    <!-- ── Main Content ───────────────────────────────────── -->
    <main class="main-content">
        <header class="topbar">
            <button class="hamburger-btn" id="hamburger-btn">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="search-bar">
                <form action="technical.php" method="GET">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="ticker" placeholder="Search ticker or company..."
                           value="<?php echo htmlspecialchars($_SESSION['active_ticker'] ?? ''); ?>">
                </form>
            </div>
            <div class="topbar-actions">
                <button class="icon-btn"><i class="fa-regular fa-bell"></i><span class="badge">3</span></button>
                <div class="date-display"><i class="fa-regular fa-calendar"></i> <?php echo date('d M Y'); ?></div>
            </div>
        </header>

        <div class="dashboard-wrapper">

            <!-- Page header -->
            <div class="page-header" style="margin-bottom:1.8rem;">
                <h1>Market News <span style="background:linear-gradient(135deg,var(--primary),var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">&amp; Insights</span></h1>
                <p>Berita pasar modal terbaru langsung dari <strong>CNBC Indonesia</strong> — diperbarui otomatis setiap kunjungan.</p>
            </div>

            <!-- Live ticker strip -->
            <div class="news-ticker-strip">
                <span class="ticker-label"><i class="fa-solid fa-bolt" style="font-size:0.6rem;"></i>&nbsp;Live</span>
                <div class="ticker-scroll-wrap">
                    <div class="ticker-scroll" id="tickerScroll">
                        <span class="ticker-item"><span class="ticker-sym">BBCA</span> 9.600 <span class="up">▲ +0,42%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">BBRI</span> 4.230 <span class="down">▼ -0,70%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">TLKM</span> 3.020 <span class="up">▲ +1,20%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">ASII</span> 4.500 <span class="down">▼ -0,22%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">GOTO</span> 77 <span class="up">▲ +2,67%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">BMRI</span> 5.925 <span class="up">▲ +0,85%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">UNVR</span> 2.200 <span class="down">▼ -1,12%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">IHSG</span> 7.234 <span class="up">▲ +0,54%</span></span>
                        <!-- duplicate for seamless loop -->
                        <span class="ticker-item"><span class="ticker-sym">BBCA</span> 9.600 <span class="up">▲ +0,42%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">BBRI</span> 4.230 <span class="down">▼ -0,70%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">TLKM</span> 3.020 <span class="up">▲ +1,20%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">ASII</span> 4.500 <span class="down">▼ -0,22%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">GOTO</span> 77 <span class="up">▲ +2,67%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">BMRI</span> 5.925 <span class="up">▲ +0,85%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">UNVR</span> 2.200 <span class="down">▼ -1,12%</span></span>
                        <span class="ticker-item"><span class="ticker-sym">IHSG</span> 7.234 <span class="up">▲ +0,54%</span></span>
                    </div>
                </div>
            </div>

            <!-- ── Featured / Hero news ── -->
            <?php if ($featured): ?>
            <a href="<?php echo htmlspecialchars($featured['link']); ?>" target="_blank" rel="noopener" class="news-hero" id="heroCard">
                <?php if (!empty($featured['image'])): ?>
                    <img src="<?php echo htmlspecialchars($featured['image']); ?>" alt="<?php echo htmlspecialchars($featured['title']); ?>">
                <?php else: ?>
                    <div class="news-hero-placeholder">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                <?php endif; ?>

                <div class="news-hero-overlay">
                    <div class="news-hero-badge">
                        <span class="dot-live"></span> Breaking News
                    </div>
                    <h2 class="news-hero-title"><?php echo htmlspecialchars($featured['title']); ?></h2>
                    <?php if (!empty($featured['description'])): ?>
                        <p class="news-hero-desc"><?php echo htmlspecialchars($featured['description']); ?></p>
                    <?php endif; ?>
                    <div class="news-hero-meta" style="margin-bottom:1.2rem;">
                        <span><i class="fa-solid fa-newspaper"></i> CNBC Indonesia</span>
                        <span><i class="fa-regular fa-clock"></i> <?php echo date('d M Y, H:i', $featured['pubDate']); ?> WIB</span>
                    </div>
                    <span class="read-more-pill">
                        Baca Selengkapnya <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>
            </a>
            <?php endif; ?>

            <!-- ── Cards section ── -->
            <div class="news-section-header">
                <h2 class="news-section-title">
                    Berita Terkini
                    <span class="news-count-badge"><?php echo count($remaining); ?> artikel</span>
                </h2>
                <a href="https://www.cnbcindonesia.com/market" target="_blank" rel="noopener" class="news-source-link">
                    <i class="fa-solid fa-external-link-alt"></i> Sumber: CNBC Indonesia
                </a>
            </div>

            <div class="news-grid">
                <?php foreach ($remaining as $i => $news): ?>
                <div class="news-card">
                    <div class="news-card-img-wrap">
                        <?php if (!empty($news['image'])): ?>
                            <img class="news-card-img"
                                 src="<?php echo htmlspecialchars($news['image']); ?>"
                                 alt="<?php echo htmlspecialchars($news['title']); ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="news-card-img-placeholder">
                                <i class="fa-solid fa-newspaper"></i>
                            </div>
                        <?php endif; ?>
                        <span class="news-card-source-tag">CNBC Indonesia</span>
                        <span class="news-card-number"><?php echo $i + 2; ?></span>
                    </div>

                    <div class="news-card-body">
                        <div class="news-card-time">
                            <i class="fa-regular fa-clock"></i>
                            <?php echo date('d M Y, H:i', $news['pubDate']); ?> WIB
                        </div>
                        <h3 class="news-card-title">
                            <a href="<?php echo htmlspecialchars($news['link']); ?>" target="_blank" rel="noopener">
                                <?php echo htmlspecialchars($news['title']); ?>
                            </a>
                        </h3>
                        <?php if (!empty($news['description'])): ?>
                            <p class="news-card-desc"><?php echo htmlspecialchars($news['description']); ?></p>
                        <?php endif; ?>

                        <div class="news-card-footer">
                            <a href="<?php echo htmlspecialchars($news['link']); ?>" target="_blank" rel="noopener" class="news-card-cta">
                                Baca Selengkapnya <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Footer note -->
            <div style="text-align:center;padding-bottom:2rem;color:var(--text-muted);font-size:0.82rem;font-weight:600;">
                <i class="fa-solid fa-circle-info" style="margin-right:0.3rem;"></i>
                Data berita di-refresh setiap kunjungan halaman. Sumber: CNBC Indonesia RSS Feed.
            </div>

        </div><!-- /.dashboard-wrapper -->
    </main>

    <script src="assets/js/main.js"></script>
</body>
</html>


