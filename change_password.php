<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validasi input
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Kata sandi baru harus minimal 6 karakter.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Konfirmasi kata sandi baru tidak cocok.';
    } else {
        // Ambil password lama dari database
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $dbPasswordHash = $row['password'];
                
                // Verifikasi password saat ini
                if (!password_verify($currentPassword, $dbPasswordHash)) {
                    $error = 'Kata sandi saat ini salah.';
                } elseif (password_verify($newPassword, $dbPasswordHash)) {
                    $error = 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini.';
                } else {
                    // Hash password baru dengan Bcrypt
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    // Update password di database
                    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($updateStmt) {
                        $updateStmt->bind_param("si", $newPasswordHash, $userId);
                        if ($updateStmt->execute()) {
                            $success = 'Kata sandi Anda berhasil diperbarui!';
                        } else {
                            $error = 'Gagal memperbarui kata sandi. Silakan coba lagi.';
                        }
                        $updateStmt->close();
                    }
                }
            } else {
                $error = 'Pengguna tidak ditemukan.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Sobat Ritel</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .password-strength-container {
            margin-top: 0.5rem;
            display: flex;
            gap: 4px;
        }
        .strength-bar {
            height: 4px;
            flex: 1;
            border-radius: 2px;
            background: #e2e8f0;
            transition: all 0.3s;
        }
        .strength-text {
            font-size: 0.75rem;
            font-weight: 700;
            margin-top: 0.3rem;
            display: block;
            text-align: right;
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
            <div class="search-bar"></div>
            <div class="topbar-actions">
                <button class="icon-btn"><i class="fa-regular fa-bell"></i><span class="badge">3</span></button>
                <div class="date-display"><i class="fa-regular fa-calendar"></i> <?php echo date('d M Y'); ?></div>
            </div>
        </header>

        <div class="dashboard-wrapper">
            <div class="page-header" style="margin-bottom: 2rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-muted); font-size: 0.88rem; font-weight: 700; margin-bottom: 0.5rem;">
                    <a href="settings.php" style="color: var(--text-muted); text-decoration: none;">Settings</a>
                    <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem;"></i>
                    <span style="color: var(--primary);">Change Password</span>
                </div>
                <h1 style="margin: 0; font-size: 2rem; font-weight: 850; letter-spacing: -0.5px;">Change Password</h1>
                <p style="margin: 0.2rem 0 0 0;">Secure your Sobat Ritel account with a strong password.</p>
            </div>

            <div class="glass-card" style="padding: 2.2rem; max-width: 550px; border-radius: 24px; border: 1px solid rgba(37,99,235,0.12); box-shadow: 0 15px 40px rgba(37,99,235,0.03);">
                
                <?php if (!empty($error)): ?>
                    <div style="background: rgba(239, 68, 68, 0.08); border: 1.5px solid rgba(239, 68, 68, 0.18); color: #ef4444; padding: 1rem; border-radius: 12px; font-weight: 700; font-size: 0.9rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                        <i class="fa-solid fa-circle-exclamation" style="font-size: 1.1rem;"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div style="background: rgba(16, 185, 129, 0.08); border: 1.5px solid rgba(16, 185, 129, 0.18); color: #0d9488; padding: 1rem; border-radius: 12px; font-weight: 700; font-size: 0.9rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                        <i class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <form action="change_password.php" method="POST" id="passwordForm" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: #475569; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Current Password</label>
                        <div style="position: relative;">
                            <input type="password" name="current_password" required style="width: 100%; padding: 0.8rem 1rem; border-radius: 12px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #0f172a; outline: none; font-weight: 600; font-size: 0.92rem; transition: all 0.25s;" onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 4px rgba(37,99,235,0.08)';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                            <button type="button" onclick="togglePassword(this)" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.05rem;"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: #475569; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">New Password</label>
                        <div style="position: relative;">
                            <input type="password" id="new_password" name="new_password" required style="width: 100%; padding: 0.8rem 1rem; border-radius: 12px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #0f172a; outline: none; font-weight: 600; font-size: 0.92rem; transition: all 0.25s;" onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 4px rgba(37,99,235,0.08)';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';" oninput="checkStrength(this.value)">
                            <button type="button" onclick="togglePassword(this)" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.05rem;"><i class="fa-solid fa-eye"></i></button>
                        </div>
                        <div class="password-strength-container">
                            <div class="strength-bar" id="bar1"></div>
                            <div class="strength-bar" id="bar2"></div>
                            <div class="strength-bar" id="bar3"></div>
                        </div>
                        <span class="strength-text" id="strengthText" style="color: #94a3b8;">Weak</span>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: #475569; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Confirm New Password</label>
                        <div style="position: relative;">
                            <input type="password" name="confirm_password" required style="width: 100%; padding: 0.8rem 1rem; border-radius: 12px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #0f172a; outline: none; font-weight: 600; font-size: 0.92rem; transition: all 0.25s;" onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 4px rgba(37,99,235,0.08)';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                            <button type="button" onclick="togglePassword(this)" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.05rem;"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <button type="submit" class="btn" style="flex: 1; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; border: none; padding: 0.85rem 1.8rem; border-radius: 12px; cursor: pointer; font-weight: 800; font-size: 0.92rem; box-shadow: 0 4px 14px rgba(37,99,235,0.25); transition: all 0.25s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(37,99,235,0.35)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 14px rgba(37,99,235,0.25)';">Update Password</button>
                        <a href="settings.php" class="btn-outline" style="text-align: center; text-decoration: none; padding: 0.8rem 1.6rem; border: 1.5px solid #2563eb; color: #2563eb; border-radius: 12px; font-weight: 800; font-size: 0.9rem; transition: all 0.2s;" onmouseover="this.style.background='rgba(37,99,235,0.05)';" onmouseout="this.style.background='none';">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
    <script>
        function togglePassword(btn) {
            const input = btn.previousElementSibling;
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkStrength(password) {
            const bar1 = document.getElementById('bar1');
            const bar2 = document.getElementById('bar2');
            const bar3 = document.getElementById('bar3');
            const text = document.getElementById('strengthText');

            let score = 0;
            if (password.length >= 6) score++;
            if (/[A-Z]/.test(password) && /[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            // Reset
            bar1.style.background = '#e2e8f0';
            bar2.style.background = '#e2e8f0';
            bar3.style.background = '#e2e8f0';

            if (password.length === 0) {
                text.textContent = 'Weak';
                text.style.color = '#94a3b8';
                return;
            }

            if (score === 1) {
                bar1.style.background = '#ef4444'; // Red
                text.textContent = 'Weak';
                text.style.color = '#ef4444';
            } else if (score === 2) {
                bar1.style.background = '#f59e0b'; // Amber
                bar2.style.background = '#f59e0b';
                text.textContent = 'Medium';
                text.style.color = '#f59e0b';
            } else if (score === 3) {
                bar1.style.background = '#10b981'; // Emerald Green
                bar2.style.background = '#10b981';
                bar3.style.background = '#10b981';
                text.textContent = 'Strong';
                text.style.color = '#10b981';
            }
        }
    </script>
</body>
</html>


