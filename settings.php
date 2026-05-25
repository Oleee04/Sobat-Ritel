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
    <title>Settings - Sobat Ritel</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                <!-- No search bar for settings -->
            </div>
            <div class="topbar-actions">
                <button class="icon-btn"><i class="fa-regular fa-bell"></i><span class="badge">3</span></button>
                <div class="date-display"><i class="fa-regular fa-calendar"></i> <?php echo date('d M Y'); ?></div>
            </div>
        </header>

        <div class="dashboard-wrapper">
            <div class="page-header">
                <h1>Settings</h1>
                <p>Manage your account preferences.</p>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; max-width: 1100px;">
                <!-- Profile Settings Card -->
                <div class="glass-card" style="padding: 2.2rem; border-radius: 24px; border: 1px solid rgba(37,99,235,0.12); box-shadow: 0 15px 40px rgba(37,99,235,0.03);">
                    <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 850; letter-spacing: -0.3px;"><i class="fa-solid fa-user" style="color: var(--primary); margin-right: 0.5rem;"></i> Profile Settings</h3>
                    <form style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.85rem; font-weight: 750; text-transform: uppercase; letter-spacing: 0.5px;">Full Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" style="width: 100%; padding: 0.8rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: rgba(37,99,235,0.02); color: var(--text-main); font-weight: 600;" readonly>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.85rem; font-weight: 750; text-transform: uppercase; letter-spacing: 0.5px;">Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" style="width: 100%; padding: 0.8rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: rgba(37,99,235,0.02); color: var(--text-main); font-weight: 600;" readonly>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.85rem; font-weight: 750; text-transform: uppercase; letter-spacing: 0.5px;">Theme Selection</label>
                            <select class="custom-select" style="width: 100%; padding: 0.8rem 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #ffffff; color: var(--text-main); font-weight: 600; outline: none;">
                                <option>White & Electric-Blue (Default)</option>
                                <option>Dark Tech Glassmorphism</option>
                            </select>
                        </div>
                        <div>
                            <button type="button" class="btn" style="background: var(--primary); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 800; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-1px)';" onmouseout="this.style.transform='none';">Save Changes</button>
                        </div>
                    </form>
                </div>

                <!-- Account Security Card -->
                <div class="glass-card" style="padding: 2.2rem; border-radius: 24px; border: 1px solid rgba(37,99,235,0.12); box-shadow: 0 15px 40px rgba(37,99,235,0.03); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <h3 style="margin-bottom: 1rem; font-size: 1.25rem; font-weight: 850; letter-spacing: -0.3px;"><i class="fa-solid fa-shield-halved" style="color: var(--primary); margin-right: 0.5rem;"></i> Account Security</h3>
                        <p style="color: var(--text-muted); font-size: 0.92rem; line-height: 1.5; margin-bottom: 1.5rem;">
                            Jaga keamanan akun **Sobat Ritel** Anda dengan memperbarui kata sandi secara berkala. Pastikan Anda menggunakan kata sandi unik dengan kombinasi huruf, angka, dan karakter khusus.
                        </p>
                        <div style="background: rgba(37,99,235,0.04); border: 1px solid rgba(37,99,235,0.08); padding: 1.2rem; border-radius: 16px; margin-bottom: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.8rem; color: var(--primary); font-weight: 700; font-size: 0.88rem; margin-bottom: 0.4rem;">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>Security Recommendations</span>
                            </div>
                            <ul style="margin: 0; padding-left: 1.2rem; font-size: 0.82rem; color: var(--text-muted); line-height: 1.6; font-weight: 600;">
                                <li>Panjang minimal 6 karakter.</li>
                                <li>Gunakan kombinasi huruf besar, kecil, dan angka.</li>
                                <li>Jangan gunakan kata sandi yang sama untuk platform lain.</li>
                            </ul>
                        </div>
                    </div>
                    <div>
                        <a href="change_password.php" class="btn" style="display: inline-block; text-decoration: none; text-align: center; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; border: none; padding: 0.85rem 1.8rem; border-radius: 12px; cursor: pointer; font-weight: 800; font-size: 0.92rem; box-shadow: 0 4px 14px rgba(37,99,235,0.25); transition: all 0.25s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(37,99,235,0.35)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 14px rgba(37,99,235,0.25)';">
                            <i class="fa-solid fa-key" style="margin-right: 0.5rem;"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="assets/js/main.js"></script>
</body>
</html>


