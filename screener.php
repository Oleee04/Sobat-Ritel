<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require 'config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screener - Sobat Ritel</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Modern Ice-Blue & White Glassmorphism Overrides */
        body.dashboard-body {
            background: #f4f7fa;
            color: #1e293b;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
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
        
        .glow-1 { top: -20%; right: -10%; }
        .glow-2 { bottom: -20%; left: -10%; }

        /* Topbar & Sidebar sync */
        .topbar {
            background: rgba(255, 255, 255, 0.8) !important;
            border-bottom: 1px solid rgba(37, 99, 235, 0.1) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            z-index: 10;
        }

        .sidebar {
            z-index: 11;
        }

        /* SPA Containers */
        .view-section {
            display: none;
            animation: fadeIn 0.4s ease-out forwards;
        }

        .view-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Nav Tab Bar Superior (Stockbit Style) */
        .screener-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .tab-card {
            flex: 1;
            min-width: 150px;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(37, 99, 235, 0.08);
            border-radius: 16px;
            padding: 1.1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .tab-card:hover {
            transform: translateY(-3px);
            border-color: #2563eb;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.08);
        }

        .tab-card.active {
            background: linear-gradient(135deg, #ffffff 0%, rgba(37, 99, 235, 0.03) 100%);
            border-color: #2563eb;
            border-width: 1.5px;
        }

        .tab-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: bold;
        }

        .tab-icon.create { background: rgba(16, 185, 129, 0.08); color: #10b981; }
        .tab-icon.favorite { background: rgba(245, 158, 11, 0.08); color: #f59e0b; }
        .tab-icon.saved { background: rgba(20, 184, 166, 0.08); color: #14b8a6; }

        .tab-info h4 {
            margin: 0;
            font-size: 0.92rem;
            font-weight: 800;
            color: #0f172a;
        }

        .tab-info p {
            margin: 0.15rem 0 0 0;
            font-size: 0.72rem;
            color: #64748b;
            font-weight: 600;
        }

        /* Preset Screener Section */
        .section-title-bar {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1.5rem;
        }

        .section-title-bar i {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .section-title-bar h3 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 850;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        /* Preset 2-Column Grid Card */
        .preset-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.2rem;
        }

        @media (max-width: 768px) {
            .preset-grid {
                grid-template-columns: 1fr;
            }
        }

        .preset-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(37, 99, 235, 0.08);
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.015);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .preset-card:hover {
            transform: translateY(-4px);
            border-color: rgba(37, 99, 235, 0.2);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.08);
        }

        .preset-logo {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        }

        /* Preset Accents */
        .logo-guru { background: #f3e8ff; color: #8b5cf6; }
        .logo-popular { background: #ffedd5; color: #ea580c; }
        .logo-val { background: #dbeafe; color: #2563eb; }
        .logo-tech { background: #fce7f3; color: #db2777; }
        .logo-div { background: #fef9c3; color: #ca8a04; }
        .logo-fund { background: #e0f2fe; color: #0284c7; }

        .preset-details {
            flex: 1;
        }

        .preset-details h4 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 850;
            color: #0f172a;
            letter-spacing: -0.3px;
        }

        .preset-details p {
            margin: 0.25rem 0 0 0;
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 500;
            line-height: 1.4;
        }

        /* Create Screener Form Layout */
        .glass-card-builder {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(37, 99, 235, 0.12);
            border-radius: 30px;
            padding: 2.2rem;
            box-shadow: 0 15px 45px rgba(37, 99, 235, 0.03);
            position: relative;
            z-index: 1;
        }

        .builder-title-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            border-bottom: 1.5px solid rgba(37, 99, 235, 0.06);
            padding-bottom: 1rem;
        }

        .builder-title-bar h2 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.8px;
        }

        /* Minimalist Input (Stockbit Style) */
        .form-group-stockbit {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.8rem;
        }

        .form-group-stockbit label {
            font-size: 0.78rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .form-group-stockbit input.text-input-sb {
            border: none !important;
            border-bottom: 2px solid #cbd5e1 !important;
            border-radius: 0 !important;
            background: transparent !important;
            font-size: 1.15rem !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            padding: 0.5rem 0 !important;
            box-shadow: none !important;
            outline: none !important;
            transition: border-color 0.25s !important;
        }

        .form-group-stockbit input.text-input-sb:focus {
            border-bottom-color: #2563eb !important;
        }

        /* Stock Universe Select Card */
        .universe-selector {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 0.95rem 1.2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.2s;
        }

        .universe-selector:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
        }

        .universe-info-sb {
            display: flex;
            flex-direction: column;
        }

        .universe-info-sb span.label {
            font-size: 0.65rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
        }

        .universe-info-sb span.value {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            margin-top: 0.1rem;
        }

        /* Rule Row Strip */
        .rule-row-strip {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            padding: 0.85rem 1.2rem;
            border-radius: 16px;
            margin-bottom: 0.8rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.01);
            animation: slideInRule 0.25s ease-out;
        }

        @keyframes slideInRule {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .rule-name-tag {
            flex: 2;
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .rule-name-tag i {
            color: #3b82f6;
            font-size: 0.85rem;
        }

        .rule-op-select {
            width: 80px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            padding: 0.4rem 0.5rem;
            outline: none;
            font-size: 0.85rem;
            font-weight: 700;
            color: #334155;
            background: #f8fafc;
            text-align: center;
        }

        .rule-val-input {
            flex: 1.5;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            outline: none;
            font-size: 0.88rem;
            font-weight: 700;
            color: #0f172a;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.01);
        }

        .rule-val-input:focus {
            border-color: #2563eb;
        }

        .btn-delete-rule-sb {
            border: none;
            background: rgba(239, 68, 68, 0.05);
            color: #ef4444;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn-delete-rule-sb:hover {
            background: #ef4444;
            color: white;
        }

        /* Dotted Add Rule Button */
        .btn-add-rule-dotted {
            width: 100%;
            border: 2px dashed #cbd5e1;
            background: transparent;
            padding: 1.1rem;
            border-radius: 16px;
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            transition: all 0.25s;
            margin-bottom: 2rem;
        }

        .btn-add-rule-dotted:hover {
            border-color: #2563eb;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.02);
        }

        /* Screen Full Width Button */
        .btn-screen-emerald {
            width: 100%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 1.1rem;
            border-radius: 16px;
            font-size: 1.05rem;
            font-weight: 850;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.25);
            transition: all 0.25s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-screen-emerald:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.35);
        }

        /* Bottom Sheet Panel CSS */
        .bottom-sheet-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.35s ease;
        }

        .bottom-sheet-backdrop.open {
            opacity: 1;
            visibility: visible;
        }

        .bottom-sheet-panel {
            position: fixed;
            left: 50%;
            bottom: 0;
            transform: translate(-50%, 100%);
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 28px 28px 0 0;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.08);
            z-index: 1001;
            padding: 1.8rem;
            max-height: 85vh;
            overflow-y: auto;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .bottom-sheet-backdrop.open .bottom-sheet-panel {
            transform: translate(-50%, 0);
        }

        .bottom-sheet-drag-handle {
            width: 42px;
            height: 5px;
            background: #cbd5e1;
            border-radius: 3px;
            margin: -0.6rem auto 1.5rem auto;
            cursor: pointer;
        }

        .bottom-sheet-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .bottom-sheet-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .btn-close-sheet {
            border: none;
            background: #f1f5f9;
            color: #64748b;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-close-sheet:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        /* Bottom Sheet Step 1 Options */
        .sheet-option-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.25s;
        }

        .sheet-option-card:hover {
            border-color: #2563eb;
            background: rgba(37, 99, 235, 0.01);
            transform: translateY(-2px);
        }

        .sheet-option-card h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 850;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sheet-option-card h4 span.badge-pro {
            font-size: 0.65rem;
            background: rgba(37, 99, 235, 0.08);
            color: #2563eb;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            font-weight: 800;
        }

        .sheet-option-card p {
            margin: 0.35rem 0 0 0;
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.4;
            font-weight: 500;
        }

        /* Bottom Sheet Search Header (Step 2) */
        .sheet-search-wrapper {
            position: relative;
            margin-bottom: 1.4rem;
        }

        .sheet-search-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.95rem;
        }

        .sheet-search-wrapper input {
            width: 100%;
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: 14px;
            padding: 0.75rem 1rem 0.75rem 2.6rem;
            outline: none;
            font-size: 0.9rem;
            font-weight: 600;
            color: #0f172a;
            transition: all 0.2s;
        }

        .sheet-search-wrapper input:focus {
            border-color: #2563eb;
            background: #ffffff;
        }

        /* List Items Kategori Metrik */
        .sheet-category-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0.5rem;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: all 0.2s;
        }

        .sheet-category-item:hover {
            background: rgba(0,0,0,0.01);
            padding-left: 0.8rem;
            padding-right: 0.2rem;
        }

        .sheet-category-left {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .sheet-category-left i.cat-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .sheet-category-left span.cat-name {
            font-size: 0.92rem;
            font-weight: 800;
            color: #334155;
        }

        .sheet-category-item i.chevron-icon {
            color: #94a3b8;
            font-size: 0.85rem;
        }

        /* Saved Screener List view styles */
        .saved-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 18px;
            padding: 1.3rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.25s;
        }

        .saved-card:hover {
            border-color: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.015);
        }

        .saved-card-left {
            flex: 1;
            cursor: pointer;
        }

        .saved-card-left h4 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 850;
            color: #0f172a;
        }

        .saved-card-left p {
            margin: 0.25rem 0 0 0;
            font-size: 0.82rem;
            color: #64748b;
        }

        .saved-card-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-saved-action {
            border: none;
            padding: 0.55rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.88rem;
            transition: all 0.2s;
        }

        .btn-saved-action.load { background: rgba(37,99,235,0.08); color: #2563eb; }
        .btn-saved-action.load:hover { background: #2563eb; color: white; }
        .btn-saved-action.delete { background: rgba(239,68,68,0.08); color: #ef4444; }
        .btn-saved-action.delete:hover { background: #ef4444; color: white; }

        /* General Buttons layout */
        .header-buttons-sb {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .btn-outline-sb {
            border: 1.5px solid #2563eb;
            background: transparent;
            color: #2563eb;
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-outline-sb:hover {
            background: rgba(37,99,235,0.04);
            transform: translateY(-1px);
        }

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
            
            <!-- Superior Nav Tabs Bar -->
            <div class="screener-tabs">
                <div class="tab-card active" id="tab-dashboard" onclick="showSection('dashboard')">
                    <div class="tab-icon favorite"><i class="fa-regular fa-star"></i></div>
                    <div class="tab-info">
                        <h4>Presets &amp; Favorites</h4>
                        <p>Explore curated screener tools</p>
                    </div>
                </div>
                <div class="tab-card" id="tab-create" onclick="showSection('create')">
                    <div class="tab-icon create"><i class="fa-solid fa-plus"></i></div>
                    <div class="tab-info">
                        <h4>Create Screener</h4>
                        <p>Build your indicators rules</p>
                    </div>
                </div>
                <div class="tab-card" id="tab-saved" onclick="showSection('saved')">
                    <div class="tab-icon saved"><i class="fa-regular fa-bookmark"></i></div>
                    <div class="tab-info">
                        <h4>Saved Screener</h4>
                        <p>Your custom screens library</p>
                    </div>
                </div>
            </div>

            <!-- VIEW 1: PRESETS & FAVORITES -->
            <div id="section-dashboard" class="view-section active">
                <div class="section-title-bar">
                    <i class="fa-solid fa-sliders"></i>
                    <h3>Preset Screener</h3>
                </div>

                <div class="preset-grid">
                    <!-- Guru Screener Card -->
                    <div class="preset-card" onclick="loadPreset('guru')">
                        <div class="preset-logo logo-guru"><i class="fa-solid fa-graduation-cap"></i></div>
                        <div class="preset-details">
                            <h4>Guru Screener</h4>
                            <p>Cari Saham Ala Investor Terkenal (High ROE &amp; Low PBV)</p>
                        </div>
                    </div>

                    <!-- Popular Screener Card -->
                    <div class="preset-card" onclick="loadPreset('popular')">
                        <div class="preset-logo logo-popular"><i class="fa-solid fa-fire"></i></div>
                        <div class="preset-details">
                            <h4>Popular</h4>
                            <p>Saham Favorit Stockbitor dengan Likuiditas &amp; Volume Raksasa</p>
                        </div>
                    </div>

                    <!-- Valuation Screener Card -->
                    <div class="preset-card" onclick="loadPreset('valuation')">
                        <div class="preset-logo logo-val"><i class="fa-solid fa-gem"></i></div>
                        <div class="preset-details">
                            <h4>Valuation</h4>
                            <p>Saham Dengan Valuasi Murah di Bawah Rata-Rata Historis</p>
                        </div>
                    </div>

                    <!-- Technical Screener Card -->
                    <div class="preset-card" onclick="loadPreset('technical')">
                        <div class="preset-logo logo-tech"><i class="fa-solid fa-chart-line"></i></div>
                        <div class="preset-details">
                            <h4>Technical</h4>
                            <p>Ikuti Pergerakan Harga Dan Volume Secara Presisi</p>
                        </div>
                    </div>

                    <!-- Dividend Screener Card -->
                    <div class="preset-card" onclick="loadPreset('dividend')">
                        <div class="preset-logo logo-div"><i class="fa-solid fa-chart-pie"></i></div>
                        <div class="preset-details">
                            <h4>Dividend</h4>
                            <p>Saham Rajin Bagi Dividend Berkinerja Bisnis Cemerlang</p>
                        </div>
                    </div>

                    <!-- Fundamental Screener Card -->
                    <div class="preset-card" onclick="loadPreset('fundamental')">
                        <div class="preset-logo logo-fund"><i class="fa-solid fa-file-contract"></i></div>
                        <div class="preset-details">
                            <h4>Fundamental</h4>
                            <p>Saham Dengan Bisnis Yang Solid, Stabil, &amp; Bertumbuh Kuat</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 2: CREATE SCREENER FORM -->
            <div id="section-create" class="view-section">
                <div class="glass-card-builder">
                    <div class="builder-title-bar">
                        <h2>Create Screener</h2>
                        <div class="header-buttons-sb">
                            <button type="button" class="btn-outline-sb" onclick="saveScreener()"><i class="fa-regular fa-floppy-disk"></i> Save Screener</button>
                            <button type="button" class="btn-outline-sb" style="color: #ef4444; border-color: #ef4444;" onclick="resetForm()"><i class="fa-solid fa-arrows-rotate"></i> Reset</button>
                        </div>
                    </div>

                    <form action="screener_results.php" method="GET" id="screenerForm">
                        
                        <!-- Minimalist Tulis Nama Screener -->
                        <div class="form-group-stockbit">
                            <label>Nama Screener</label>
                            <input type="text" class="text-input-sb" name="screen_name" id="screen_name" placeholder="Tulis nama screener disini">
                        </div>

                        <!-- Description input -->
                        <div class="form-group-stockbit">
                            <label>Deskripsi Singkat</label>
                            <input type="text" class="text-input-sb" name="screen_desc" id="screen_desc" placeholder="Tulis deskripsi kriteria penyaringan...">
                        </div>

                        <!-- Stock Universe (Select Button) -->
                        <div class="form-group-stockbit" style="margin-bottom: 2.2rem;">
                            <label>Stock Universe</label>
                            <div class="universe-selector" onclick="alert('Pilihan indeks pasar segera hadir!')">
                                <div class="universe-info-sb">
                                    <span class="label">Index List</span>
                                    <span class="value">IHSG</span>
                                </div>
                                <i class="fa-solid fa-chevron-right" style="color: #94a3b8; font-size: 0.9rem;"></i>
                            </div>
                        </div>

                        <div class="section-title-bar" style="margin-bottom: 1.2rem;">
                            <i class="fa-solid fa-list-check" style="background: rgba(37,99,235,0.06); color: #2563eb;"></i>
                            <h3>Rules</h3>
                        </div>

                        <!-- Rules Rows Container -->
                        <div id="rules-container" style="margin-bottom: 1rem;">
                            <!-- Rules will be injected here dynamically -->
                        </div>

                        <!-- + Tambah Rules Dotted Button -->
                        <button type="button" class="btn-add-rule-dotted" onclick="openBottomSheet(1)">
                            <i class="fa-solid fa-plus" style="font-size: 0.85rem;"></i> Tambah Rules
                        </button>

                        <!-- Screen emerald submit button -->
                        <button type="submit" class="btn-screen-emerald">Screen</button>
                    </form>
                </div>
            </div>

            <!-- VIEW 3: SAVED SCREENER LIBRARY -->
            <div id="section-saved" class="view-section">
                <div class="section-title-bar">
                    <i class="fa-solid fa-folder-open" style="background: rgba(20, 184, 166, 0.1); color: #14b8a6;"></i>
                    <h3>Saved Screener</h3>
                </div>

                <div id="saved-screener-list">
                    <!-- Loaded dynamically via AJAX -->
                    <div style="text-align: center; padding: 4rem; color: #94a3b8;">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size: 2.2rem; margin-bottom: 1rem;"></i>
                        <p style="font-weight: 700; font-size: 1rem;">Memuat daftar screener Anda...</p>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- BOTTOM SHEET PANEL (Slide-up Bottom Sheet Modal) -->
    <div class="bottom-sheet-backdrop" id="bottomSheetBackdrop" onclick="closeBottomSheet()">
        <div class="bottom-sheet-panel" id="bottomSheetPanel" onclick="event.stopPropagation()">
            <div class="bottom-sheet-drag-handle" onclick="closeBottomSheet()"></div>
            
            <!-- STEP 1 CONTAINER (Tambah Rules) -->
            <div id="sheet-step-1" class="sheet-step-view active">
                <div class="bottom-sheet-header">
                    <h3>Tambah Rules</h3>
                    <button type="button" class="btn-close-sheet" onclick="closeBottomSheet()"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <!-- Basic Ratio Option Card -->
                <div class="sheet-option-card" onclick="setBottomSheetStep(2)">
                    <h4>Basic Ratio <i class="fa-solid fa-chevron-right" style="font-size: 0.8rem; color: #94a3b8;"></i></h4>
                    <p>Untuk menyaring perusahaan atas suatu metrik finansial dalam rentang tertentu</p>
                </div>


            </div>

            <!-- STEP 2 CONTAINER (Add Financial Metric Kategori) -->
            <div id="sheet-step-2" class="sheet-step-view">
                <div class="bottom-sheet-header">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <button type="button" class="btn-close-sheet" onclick="setBottomSheetStep(1)" style="width: 28px; height: 28px;"><i class="fa-solid fa-arrow-left" style="font-size: 0.78rem;"></i></button>
                        <h3 style="font-size: 1.15rem;">Add Financial Metric</h3>
                    </div>
                    <button type="button" class="btn-close-sheet" onclick="closeBottomSheet()"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <!-- Search Input Bar -->
                <div class="sheet-search-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="metricSearchInput" placeholder="Search Criteria" onkeyup="filterMetricCategories()">
                </div>

                <!-- Kategori Metrik Lists -->
                <div id="metric-categories-list">
                    <!-- Kategori Size (Market Cap) -->
                    <div class="sheet-category-item" onclick="addRuleFromMetric('market_cap', 'Market Cap')">
                        <div class="sheet-category-left">
                            <i class="cat-icon fa-solid fa-chart-column" style="background: rgba(37,99,235,0.06); color: #2563eb;"></i>
                            <span class="cat-name">Size (Market Cap)</span>
                        </div>
                        <i class="chevron-icon fa-solid fa-plus" style="color: #10b981;"></i>
                    </div>

                    <!-- Kategori Valuation (PE) -->
                    <div class="sheet-category-item" onclick="addRuleFromMetric('pe_ratio', 'P/E Ratio')">
                        <div class="sheet-category-left">
                            <i class="cat-icon fa-solid fa-gem" style="background: rgba(245,158,11,0.06); color: #f59e0b;"></i>
                            <span class="cat-name">Valuation (P/E Ratio)</span>
                        </div>
                        <i class="chevron-icon fa-solid fa-plus" style="color: #10b981;"></i>
                    </div>

                    <!-- Kategori Valuation (PBV) -->
                    <div class="sheet-category-item" onclick="addRuleFromMetric('pbv', 'PBV')">
                        <div class="sheet-category-left">
                            <i class="cat-icon fa-solid fa-scale-balanced" style="background: rgba(236,72,153,0.06); color: #ec4899;"></i>
                            <span class="cat-name">Valuation (PBV)</span>
                        </div>
                        <i class="chevron-icon fa-solid fa-plus" style="color: #10b981;"></i>
                    </div>

                    <!-- Kategori Profitability (ROE) -->
                    <div class="sheet-category-item" onclick="addRuleFromMetric('roe', 'ROE (%)')">
                        <div class="sheet-category-left">
                            <i class="cat-icon fa-solid fa-percent" style="background: rgba(16,185,129,0.06); color: #10b981;"></i>
                            <span class="cat-name">Profitability (ROE %)</span>
                        </div>
                        <i class="chevron-icon fa-solid fa-plus" style="color: #10b981;"></i>
                    </div>

                    <!-- Kategori Technical (Price) -->
                    <div class="sheet-category-item" onclick="addRuleFromMetric('price', 'Price')">
                        <div class="sheet-category-left">
                            <i class="cat-icon fa-solid fa-money-bill-trend-up" style="background: rgba(14,165,233,0.06); color: #0ea5e9;"></i>
                            <span class="cat-name">Technical (Price)</span>
                        </div>
                        <i class="chevron-icon fa-solid fa-plus" style="color: #10b981;"></i>
                    </div>

                    <!-- Kategori Technical (Volume) -->
                    <div class="sheet-category-item" onclick="addRuleFromMetric('volume', 'Volume')">
                        <div class="sheet-category-left">
                            <i class="cat-icon fa-solid fa-chart-line" style="background: rgba(139,92,246,0.06); color: #8b5cf6;"></i>
                            <span class="cat-name">Technical (Volume)</span>
                        </div>
                        <i class="chevron-icon fa-solid fa-plus" style="color: #10b981;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadScreeners();
            // Start by default with an empty rule row in form
            resetForm();
        });

        // Toggle SPA Sections
        function showSection(sectionId) {
            // Remove active classes
            document.querySelectorAll('.screener-tabs .tab-card').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.view-section').forEach(view => view.classList.remove('active'));

            // Set new active
            document.getElementById('tab-' + sectionId).classList.add('active');
            document.getElementById('section-' + sectionId).classList.add('active');

            if (sectionId === 'saved') {
                loadScreeners();
            }
        }

        // Reset Form
        function resetForm() {
            document.getElementById('screenerForm').reset();
            document.getElementById('rules-container').innerHTML = '';
            // add 1 default rule
            addRuleFromMetric('pe_ratio', 'P/E Ratio');
        }

        // Bottom Sheet Functions
        function openBottomSheet(step = 1) {
            setBottomSheetStep(step);
            document.getElementById('bottomSheetBackdrop').classList.add('open');
            document.body.style.overflow = 'hidden'; // lock background scrolling
        }

        function closeBottomSheet() {
            document.getElementById('bottomSheetBackdrop').classList.remove('open');
            document.body.style.overflow = ''; // unlock scrolling
            // Clear search criteria
            document.getElementById('metricSearchInput').value = '';
            filterMetricCategories();
        }

        function setBottomSheetStep(step) {
            document.querySelectorAll('.sheet-step-view').forEach(view => view.classList.remove('active'));
            document.getElementById('sheet-step-' + step).classList.add('active');
        }

        // Filter categories list inside Bottom Sheet search
        function filterMetricCategories() {
            const query = document.getElementById('metricSearchInput').value.toLowerCase().trim();
            const items = document.querySelectorAll('#metric-categories-list .sheet-category-item');
            
            items.forEach(item => {
                const name = item.querySelector('.cat-name').textContent.toLowerCase();
                if (name.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Add Rule From Metric Selected in Bottom Sheet
        function addRuleFromMetric(field, label) {
            const container = document.getElementById('rules-container');
            const rowId = 'rule_row_' + Date.now() + Math.random().toString(36).substr(2, 5);
            
            const ruleRow = document.createElement('div');
            ruleRow.className = 'rule-row-strip';
            ruleRow.id = rowId;
            
            ruleRow.innerHTML = `
                <div class="rule-name-tag">
                    <i class="fa-solid fa-square-check"></i>
                    <span>${label}</span>
                    <input type="hidden" name="rule_field[]" value="${field}">
                </div>
                <select name="rule_operator[]" class="rule-op-select">
                    <option value=">">&gt;</option>
                    <option value="<">&lt;</option>
                    <option value="=">=</option>
                    <option value=">=">&gt;=</option>
                    <option value="<=">&lt;=</option>
                </select>
                <input type="number" name="rule_value[]" step="any" class="rule-val-input" placeholder="Masukkan nilai" required>
                <button type="button" class="btn-delete-rule-sb" onclick="removeRuleRow('${rowId}')"><i class="fa-solid fa-trash-can"></i></button>
            `;
            
            container.appendChild(ruleRow);
            closeBottomSheet();
        }

        function removeRuleRow(rowId) {
            const row = document.getElementById(rowId);
            if (row) {
                if (document.querySelectorAll('.rule-row-strip').length > 1) {
                    row.remove();
                } else {
                    alert("Screener membutuhkan minimal satu kriteria rule!");
                }
            }
        }

        // Map Clicking Preset Cards directly to active rules & submit
        function loadPreset(presetType) {
            // Switch to form section first
            showSection('create');
            
            const nameField = document.getElementById('screen_name');
            const descField = document.getElementById('screen_desc');
            const container = document.getElementById('rules-container');
            
            container.innerHTML = ''; // reset rules

            if (presetType === 'guru') {
                nameField.value = 'Guru Screener';
                descField.value = 'Saham dengan performa fundamental prima, ROE tinggi dan PBV murah.';
                addRuleWithValues('roe', 'ROE (%)', '>', 15);
                addRuleWithValues('pbv', 'PBV', '<', 3);
            } else if (presetType === 'popular') {
                nameField.value = 'Popular Screener';
                descField.value = 'Saham-saham dengan likuiditas tinggi dan transaksi teraktif di bursa.';
                addRuleWithValues('volume', 'Volume', '>', 5000000);
            } else if (presetType === 'valuation') {
                nameField.value = 'Valuation Screener';
                descField.value = 'Saham dengan valuasi murah (PE Ratio & PBV di bawah rata-rata).';
                addRuleWithValues('pe_ratio', 'P/E Ratio', '<', 15);
                addRuleWithValues('pbv', 'PBV', '<', 1.5);
            } else if (presetType === 'technical') {
                nameField.value = 'Technical Trend Screener';
                descField.value = 'Saham yang bergerak aktif dengan volume transaksi yang kuat.';
                addRuleWithValues('price', 'Price', '>', 100);
                addRuleWithValues('volume', 'Volume', '>', 1000000);
            } else if (presetType === 'dividend') {
                nameField.value = 'High Dividend Proxy';
                descField.value = 'Saham yang rajin membagikan dividen dengan efisiensi modal yang solid.';
                addRuleWithValues('roe', 'ROE (%)', '>', 12);
                addRuleWithValues('pbv', 'PBV', '<', 2.5);
            } else if (presetType === 'fundamental') {
                nameField.value = 'Super Fundamental';
                descField.value = 'Saham bermarket cap besar dengan profitabilitas sehat jangka panjang.';
                addRuleWithValues('market_cap', 'Market Cap', '>', 10000000000);
                addRuleWithValues('roe', 'ROE (%)', '>', 10);
            }

            // Automatically submit preset search to results page
            setTimeout(() => {
                document.getElementById('screenerForm').submit();
            }, 300);
        }

        // Helper to insert rule with custom preloaded values
        function addRuleWithValues(field, label, operator, val) {
            const container = document.getElementById('rules-container');
            const rowId = 'rule_row_' + Date.now() + Math.random().toString(36).substr(2, 5);
            
            const ruleRow = document.createElement('div');
            ruleRow.className = 'rule-row-strip';
            ruleRow.id = rowId;
            
            ruleRow.innerHTML = `
                <div class="rule-name-tag">
                    <i class="fa-solid fa-square-check"></i>
                    <span>${label}</span>
                    <input type="hidden" name="rule_field[]" value="${field}">
                </div>
                <select name="rule_operator[]" class="rule-op-select">
                    <option value=">" ${operator === '>' ? 'selected' : ''}>&gt;</option>
                    <option value="<" ${operator === '<' ? 'selected' : ''}>&lt;</option>
                    <option value="=" ${operator === '=' ? 'selected' : ''}>=</option>
                    <option value=">=" ${operator === '>=' ? 'selected' : ''}>&gt;=</option>
                    <option value="<=" ${operator === '<=' ? 'selected' : ''}>&lt;=</option>
                </select>
                <input type="number" name="rule_value[]" step="any" class="rule-val-input" placeholder="Masukkan nilai" value="${val}" required>
                <button type="button" class="btn-delete-rule-sb" onclick="removeRuleRow('${rowId}')"><i class="fa-solid fa-trash-can"></i></button>
            `;
            
            container.appendChild(ruleRow);
        }

        // AJAX Functions: SAVE SCREENER TO DB
        function saveScreener() {
            const name = document.getElementById('screen_name').value;
            const desc = document.getElementById('screen_desc').value;
            
            // Collect rules
            const fields = document.getElementsByName('rule_field[]');
            const ops = document.getElementsByName('rule_operator[]');
            const vals = document.getElementsByName('rule_value[]');
            let rules = [];
            for (let i = 0; i < fields.length; i++) {
                if (fields[i].value && vals[i].value) {
                    rules.push({
                        field: fields[i].value,
                        op: ops[i].value,
                        val: vals[i].value
                    });
                }
            }

            if (!name) {
                alert("Silakan tulis nama screener Anda terlebih dahulu!");
                return;
            }

            if (rules.length === 0) {
                alert("Silakan tambahkan minimal satu rule kriteria!");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('screen_name', name);
            formData.append('screen_desc', desc);
            formData.append('rules', JSON.stringify(rules));

            fetch('api_screener.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Screener kustom Anda berhasil disimpan ke cloud library!');
                    loadScreeners();
                } else {
                    alert('Gagal menyimpan: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Gangguan jaringan atau server error.');
            });
        }

        // AJAX Functions: LOAD SAVED SCREENERS FROM DB
        function loadScreeners() {
            const formData = new FormData();
            formData.append('action', 'load');

            fetch('api_screener.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                const listContainer = document.getElementById('saved-screener-list');
                
                if (data.status === 'success') {
                    listContainer.innerHTML = '';
                    
                    if (data.data.length === 0) {
                        listContainer.innerHTML = `
                            <div style="text-align: center; padding: 4rem; color: #94a3b8;">
                                <i class="fa-regular fa-folder-open" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1.2rem;"></i>
                                <p style="font-weight: 800; font-size: 1rem; color: #64748b;">Belum ada screener kustom tersimpan.</p>
                                <p style="font-size: 0.82rem; margin-top: 0.4rem;">Silakan buat screener kustom Anda di tab "Create Screener" dan simpan!</p>
                            </div>
                        `;
                        return;
                    }

                    window.loadedSavedScreens = data.data;
                    data.data.forEach((screen, index) => {
                        const card = document.createElement('div');
                        card.className = 'saved-card';
                        
                        card.innerHTML = `
                            <div class="saved-card-left" onclick="loadSavedScreenerRulesByIndex(${index})">
                                <h4>${escapeHTML(screen.name)}</h4>
                                <p>${escapeHTML(screen.description) || 'Tidak ada deskripsi'}</p>
                            </div>
                            <div class="saved-card-actions">
                                <button type="button" class="btn-saved-action load" onclick="loadSavedScreenerRulesByIndex(${index})"><i class="fa-solid fa-folder-open"></i> Buka</button>
                                <button type="button" class="btn-saved-action delete" onclick="deleteScreener(${screen.id})"><i class="fa-solid fa-trash-can"></i> Hapus</button>
                            </div>
                        `;
                        listContainer.appendChild(card);
                    });
                }
            })
            .catch(err => {
                console.error(err);
            });
        }

        // Helper to escape HTML characters
        function escapeHTML(str) {
            if (!str) return '';
            return str.replace(/[&<>'"]/g, 
                tag => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    "'": '&#39;',
                    '"': '&quot;'
                }[tag] || tag)
            );
        }

        // Apply rules from DB to form by global index
        function loadSavedScreenerRulesByIndex(index) {
            if (window.loadedSavedScreens && window.loadedSavedScreens[index]) {
                loadSavedScreenerRules(window.loadedSavedScreens[index]);
            }
        }

        // Apply rules from DB to form & search
        function loadSavedScreenerRules(screen) {
            showSection('create');
            document.getElementById('screen_name').value = screen.name;
            document.getElementById('screen_desc').value = screen.description || '';
            
            const container = document.getElementById('rules-container');
            container.innerHTML = '';
            
            const rules = screen.rules;
            if (Array.isArray(rules) && rules.length > 0) {
                rules.forEach(rule => {
                    let label = 'Indicator';
                    switch(rule.field) {
                        case 'pe_ratio': label = 'P/E Ratio'; break;
                        case 'pbv': label = 'PBV'; break;
                        case 'roe': label = 'ROE (%)'; break;
                        case 'price': label = 'Price'; break;
                        case 'market_cap': label = 'Market Cap'; break;
                        case 'volume': label = 'Volume'; break;
                    }
                    addRuleWithValues(rule.field, label, rule.op, rule.val);
                });
            } else {
                resetForm();
            }
            
            // Auto submit
            setTimeout(() => {
                document.getElementById('screenerForm').submit();
            }, 300);
        }

        // AJAX Functions: DELETE SCREENER FROM DB
        function deleteScreener(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus screener kustom ini dari library?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('screen_id', id);

            fetch('api_screener.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Screener kustom berhasil dihapus dari library.');
                    loadScreeners();
                    resetForm();
                } else {
                    alert('Gagal menghapus: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
            });
        }
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>


