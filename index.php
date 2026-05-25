<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
$default_mode = 'login';
if (isset($_SESSION['auth_mode'])) {
    $default_mode = $_SESSION['auth_mode'];
    unset($_SESSION['auth_mode']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Login | Sobat Ritel — IDX Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Poppins:wght@0400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg-dark: #0b1120;
            --card-bg: rgba(18, 25, 45, 0.85);
            --card-border: rgba(255, 255, 255, 0.08);
            --primary-glow: #38bdf8;
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --text-light: #f1f5f9;
            --text-muted: #94a3b8;
            --input-bg: rgba(30, 41, 59, 0.6);
            --error-bg: rgba(153, 27, 27, 0.2);
            --error-border: #f87171;
            --success: #10b981;
        }

        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background: radial-gradient(circle at 10% 20%, #0f172a, #020617);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            width: 100%; height: 100%;
            top: 0; left: 0;
            background:
                radial-gradient(circle at 20% 30%, rgba(56,189,248,0.12) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(139,92,246,0.12) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .login-wrapper {
            max-width: 860px;
            width: 100%;
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            border-radius: 2rem;
            border: 1px solid var(--card-border);
            box-shadow: 0 25px 45px -12px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.02);
            display: flex;
            flex-wrap: wrap;
            overflow: hidden;
            z-index: 10000;
            opacity: 0;
            transform: translateY(30px) scale(0.96);
            transition: opacity 1.2s cubic-bezier(0.16, 1, 0.3, 1) 0.1s, transform 1.2s cubic-bezier(0.16, 1, 0.3, 1) 0.1s;
            pointer-events: none; /* Block clicks when invisible */
        }
        .login-wrapper.show {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto; /* Allow clicks when visible */
        }

        /* ── ULTIMATE INTRO OVERLAY ── */
        #intro-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at center, #090d1f 0%, #020617 100%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 3rem 2rem;
            overflow: hidden;
            transition: all 1s ease;
        }

        #intro-overlay.intro-ended {
            pointer-events: none;
        }

        #intro-overlay.intro-ended .landing-middle-wrap {
            opacity: 0;
            transform: translateY(-40px) scale(0.96);
            pointer-events: none;
        }
        #intro-overlay.intro-ended .landing-header {
            opacity: 0;
            transform: translateY(-20px);
            pointer-events: none;
        }
        #intro-overlay.intro-ended .landing-footer {
            opacity: 0;
            transform: translateY(20px);
            pointer-events: none;
        }

        /* DYNAMIC SPECTRUM GLOW ORB */
        .intro-orb {
            position: absolute;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.14) 0%, rgba(139, 92, 246, 0.04) 50%, transparent 70%);
            z-index: 1;
            filter: blur(40px);
            animation: orbPulse 4s ease-in-out infinite alternate;
            pointer-events: none;
        }
        @keyframes orbPulse {
            0% { transform: scale(0.9) translate(0, 0); opacity: 0.6; }
            100% { transform: scale(1.15) translate(20px, -20px); opacity: 1; }
        }

        /* 3D SCROLLING GRID FLOOR */
        .intro-grid-floor {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50%;
            background-image: 
                linear-gradient(to top, #020617 15%, rgba(2, 6, 23, 0) 100%),
                linear-gradient(0deg, rgba(56, 189, 248, 0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56, 189, 248, 0.08) 1px, transparent 1px);
            background-size: 100% 100%, 60px 60px, 60px 60px;
            transform: perspective(450px) rotateX(68deg) translateY(0);
            transform-origin: top;
            animation: gridScroll 3s linear infinite;
            z-index: 1;
            opacity: 0.75;
            pointer-events: none;
        }

        @keyframes gridScroll {
            from { background-position: 0 0, 0 0, 0 0; }
            to { background-position: 0 0, 0 120px, 0 0; }
        }

        /* HIGH-SPEED SPARKS PARALLAX SYSTEM */
        .spark-container {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
            z-index: 3;
            overflow: hidden;
        }
        .spark {
            position: absolute;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.9), #ffffff);
            border-radius: 100px;
            opacity: 0;
            animation: zipSpark 1s linear infinite;
        }
        @keyframes zipSpark {
            0% { transform: translateX(110vw); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateX(-20vw); opacity: 0; }
        }

        /* SPEED LINES (Running Illusion) */
        .intro-speedlines {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: repeating-linear-gradient(
                90deg,
                transparent,
                transparent 80px,
                rgba(255, 255, 255, 0.03) 80px,
                rgba(255, 255, 255, 0.03) 84px
            );
            z-index: 2;
            opacity: 0;
            animation: speedMove 0.25s linear infinite, fadeInSpeed 1s forwards;
            animation-delay: 0.4s;
            pointer-events: none;
        }

        @keyframes speedMove {
            from { transform: translateX(0); }
            to { transform: translateX(-164px); }
        }
        @keyframes fadeInSpeed {
            to { opacity: 1; }
        }

        /* CANDLESTICK TRACK WITH SEAMLESS LOOPING */
        .candle-track {
            position: absolute;
            bottom: 22%;
            left: 0;
            display: flex;
            align-items: center;
            gap: 80px;
            padding-right: 80px;
            z-index: 2;
            opacity: 0;
            animation: fadeInSpeed 1.5s forwards;
            animation-delay: 0.3s;
            pointer-events: none;
            will-change: transform;
        }

        /* NEON GLASSMORPHISM CANDLESTICK STYLING */
        .candle-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 250px;
            transform: translateY(var(--y-offset, 0px)) skewX(var(--skew-angle, 0deg));
            will-change: transform;
        }

        .candle {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 100%;
            animation: candleBob 3s ease-in-out infinite alternate;
        }

        @keyframes candleBob {
            0% { transform: translateY(-12px); }
            100% { transform: translateY(12px); }
        }

        .candle-wick {
            width: 2px;
            position: absolute;
            z-index: 1;
            border-radius: 1px;
            opacity: 0.7;
        }

        /* Flame/Glow spark at the wick tip */
        .candle-wick::after {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 10px currentColor, 0 0 20px currentColor;
            animation: flameFlicker 0.25s ease-in-out infinite alternate;
        }

        @keyframes flameFlicker {
            0% { transform: translate(-50%, -50%) scale(0.85); opacity: 0.8; }
            100% { transform: translate(-50%, -50%) scale(1.2); opacity: 1; }
        }

        .candle-body {
            width: 24px;
            position: relative;
            z-index: 2;
            border-radius: 4px;
            box-shadow: 0 0 20px currentColor, inset 0 0 8px rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            backdrop-filter: blur(2px);
        }

        .candle.green {
            color: #10b981;
            filter: drop-shadow(0 0 10px rgba(16, 185, 129, 0.5));
        }
        .candle.green .candle-body {
            background: linear-gradient(180deg, #34d399 0%, #047857 100%);
            border: 1px solid rgba(52, 211, 153, 0.4);
        }
        .candle.green .candle-wick {
            background: linear-gradient(180deg, #6ee7b7, #047857);
        }

        .candle.red {
            color: #ef4444;
            filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0.5));
        }
        .candle.red .candle-body {
            background: linear-gradient(180deg, #f87171 0%, #b91c1c 100%);
            border: 1px solid rgba(248, 113, 113, 0.4);
        }
        .candle.red .candle-wick {
            background: linear-gradient(180deg, #fca5a5, #b91c1c);
        }

        /* LANDING HEADER NAV */
        .landing-header {
            width: 100%;
            display: flex;
            justify-content: flex-end;
            gap: 1.2rem;
            z-index: 10;
            padding: 0 2.5rem;
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-nav {
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 8px 24px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            outline: none;
            letter-spacing: 0.3px;
        }

        .btn-nav.outline {
            border: 1.5px solid #38bdf8;
            color: #38bdf8;
            background: transparent;
        }

        .btn-nav.outline:hover {
            background: rgba(56, 189, 248, 0.1);
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.25);
            transform: translateY(-1px);
        }

        .btn-nav.solid {
            border: 1.5px solid #38bdf8;
            color: #0b1120;
            background: #38bdf8;
        }

        .btn-nav.solid:hover {
            background: #0284c7;
            border-color: #0284c7;
            color: white;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.45);
            transform: translateY(-1px);
        }

        /* MIDDLE WRAP */
        .landing-middle-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            position: relative;
        }

        /* BULL CONTAINER */
        .bull-container {
            position: relative;
            z-index: 10;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: -15px;
            pointer-events: none;
        }

        /* STATIC SLEEK BULL LOGO */
        .intro-core-logo {
            width: 240px;
            height: 240px;
            object-fit: contain;
            filter: drop-shadow(0 15px 35px rgba(56, 189, 248, 0.45)) drop-shadow(0 5px 12px rgba(0,0,0,0.85));
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .intro-core-logo:hover {
            transform: scale(1.04);
        }

        /* LANDING CENTER CONTENT */
        .landing-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            will-change: transform, opacity;
        }

        .brand-name {
            font-family: 'Poppins', sans-serif;
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff 30%, #38bdf8 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 1px;
            filter: drop-shadow(0 0 15px rgba(56, 189, 248, 0.25));
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        /* TYPING EFFECT SUBTITLE */
        /* PREMIUM FADE-IN-OUT TEXT TRANSITION */
        .typing-container {
            font-family: 'Poppins', 'Inter', sans-serif;
            font-size: 1.6rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 2.2rem;
            letter-spacing: 0.5px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        .fade-text {
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 0.8s ease, transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            color: var(--primary-glow);
        }

        .fade-text.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* LANDING MAIN CALL-TO-ACTION BUTTON */
        .btn-action {
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: #38bdf8;
            background: transparent;
            border: 1.5px solid #38bdf8;
            padding: 12px 38px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.1);
            letter-spacing: 0.5px;
            outline: none;
        }

        .btn-action:hover {
            background: rgba(56, 189, 248, 0.1);
            box-shadow: 0 0 25px rgba(56, 189, 248, 0.3);
            transform: translateY(-2px) scale(1.02);
        }

        /* LANDING FOOTER STYLE */
        .landing-footer {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
            padding: 0 2.5rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            font-weight: 600;
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .footer-left {
            opacity: 0.75;
        }

        .footer-right {
            opacity: 0.75;
            color: #38bdf8;
            text-transform: uppercase;
        }

        /* SLEEK CLOSE BUTTON ON LOGIN PANEL */
        .btn-close-login {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: transparent;
            border: none;
            color: #475569;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }

        .btn-close-login:hover {
            color: #38bdf8;
            background: rgba(255, 255, 255, 0.05);
            transform: rotate(90deg);
        }


        /* ── LOGO KECIL (Form) ── */
        .logo-animate {
            width: 72px;
            height: 72px;
            object-fit: contain;
            filter: drop-shadow(0 0 12px rgba(56, 189, 248, 0.45));
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .logo-animate:hover {
            transform: scale(1.05);
        }

        /* ─ ─ LEFT PANEL ─"�� */
        .login-panel {
            flex: 1.2;
            padding: 1.2rem 1.8rem;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 0.8rem;
        }

        .welcome-text {
            font-size: 0.88rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 0.3rem;
            letter-spacing: -0.2px;
        }

        /* ─ ─ ALERT ──*/
        .alert {
            padding: 0.6rem 1rem;
            border-radius: 0.8rem;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.85rem;
            font-weight: 500;
            backdrop-filter: blur(8px);
            background: var(--error-bg);
            border-left: 3px solid var(--error-border);
            color: #fecaca;
        }
        .alert i { font-size: 1.1rem; }

        /* ─ ─ FORM ─"�� */
        .form-group { margin-bottom: 0.7rem; }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.2rem;
            color: var(--text-muted);
        }

        .input-group {
            position: relative;
            width: 100%;
        }

        .input-group i:first-child {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1rem;
            transition: color 0.2s;
            pointer-events: none;
            z-index: 10;
        }

        .input-group input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 0.65rem 3rem 0.65rem 2.5rem;
            border-radius: 1.2rem;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-light);
            font-family: 'Inter', monospace;
            transition: all 0.25s ease;
            outline: none;
            display: block;
        }

        /* Prevent ugly white browser autofill styling */
        .input-group input:-webkit-autofill,
        .input-group input:-webkit-autofill:hover, 
        .input-group input:-webkit-autofill:focus, 
        .input-group input:-webkit-autofill:active {
            -webkit-background-clip: text;
            -webkit-text-fill-color: var(--text-light) !important;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px rgba(30, 41, 59, 0.9) !important;
        }

        .input-group input:focus {
            border-color: var(--primary);
            background: rgba(30,41,59,0.9);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.3);
        }

        .input-group input::placeholder { color: #475569; font-weight: 400; }

        .toggle-pass {
            position: absolute;
            right: 1.8rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.2s;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        /* PREMIUM SEGMENTED AUTH TABS */
        .auth-tabs {
            display: flex;
            background: rgba(2, 6, 23, 0.4);
            border-radius: 1.2rem;
            padding: 4px;
            margin-bottom: 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .auth-tab-btn {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 9px 0;
            border-radius: 0.9rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            outline: none;
        }

        .auth-tab-btn.active {
            background: rgba(56, 189, 248, 0.15);
            color: var(--primary-glow);
            border: 1px solid rgba(56, 189, 248, 0.2);
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.15);
        }

        .auth-tab-btn:hover:not(.active) {
            color: white;
            background: rgba(255, 255, 255, 0.03);
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(95deg, var(--primary), var(--primary-dark));
            border: none;
            padding: 0.7rem;
            border-radius: 1.5rem;
            font-weight: 700;
            font-size: 0.95rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.2rem;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.3px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(56, 189, 248, 0.35);
            background: linear-gradient(95deg, var(--primary-dark), #0284c7);
        }

        .footer-note {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.7rem;
            color: #475569;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 0.6rem;
        }

        /* ─" RIGHT PANEL ──*/
        .hero-panel {
            flex: 1;
            background: linear-gradient(125deg, rgba(2, 15, 30, 0.6), rgba(0, 0, 0, 0.3));
            backdrop-filter: blur(12px);
            padding: 1.2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            border-left: 1px solid rgba(255, 255, 255, 0.05);
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 2rem;
            padding: 0.9rem;
            width: 100%;
            max-width: 280px;
            backdrop-filter: blur(16px);
            border: 1px solid rgba(56, 189, 248, 0.2);
            margin-bottom: 0.8rem;
            transition: all 0.3s;
        }

        .stats-card:hover {
            border-color: rgba(56, 189, 248, 0.6);
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.15);
        }

        .market-ticker {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #cbd5e1;
            margin-bottom: 0.5rem;
        }

        .index-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary-glow);
        }

        .trend-up { color: #4ade80; font-size: 0.7rem; }

        .animated-chart {
            margin-top: 0.6rem;
            display: flex;
            align-items: flex-end;
            gap: 6px;
            justify-content: center;
            height: 60px;
        }

        .chart-bar {
            width: 8px;
            background: linear-gradient(to top, #3b82f6, #38bdf8);
            border-radius: 4px;
            animation: barBounce 1.4s infinite alternate ease;
        }
        .bar1 { height:32px; animation-delay:0s; }
        .bar2 { height:50px; animation-delay:0.2s; }
        .bar3 { height:38px; animation-delay:0.5s; }
        .bar4 { height:56px; animation-delay:0.1s; }
        .bar5 { height:26px; animation-delay:0.3s; }

        @keyframes barBounce {
            0%   { transform: scaleY(0.9); opacity: 0.7; }
            100% { transform: scaleY(1.2); opacity: 1; background: linear-gradient(to top, #a855f7, #38bdf8); }
        }

        .hero-panel h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem;
            font-weight: 700;
            background: linear-gradient(135deg, #e2e8f0, #94a3b8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.3rem;
        }

        .hero-panel p {
            color: #94a3b8;
            font-size: 0.8rem;
            max-width: 260px;
        }

        @media (max-width: 820px) {
            .login-wrapper { flex-direction: column; }
            .login-panel   { padding: 2rem 1.5rem; }
            .hero-panel    { padding: 2rem; border-left: none; border-top: 1px solid rgba(255,255,255,0.05); }
        }
    </style>
</head>
<body>
<div id="intro-overlay">
    <div class="intro-orb"></div>
    <div class="intro-grid-floor"></div>
    <div class="intro-speedlines"></div>
    
    <!-- HIGH-SPEED SPARKS PARALLAX SYSTEM -->
    <div class="spark-container">
        <?php
        for($i = 0; $i < 15; $i++) {
            $top = rand(5, 95);
            $speed = rand(4, 10) / 10; // 0.4s to 1.0s
            $delay = rand(0, 15) / 10; // 0s to 1.5s
            $width = rand(60, 200);
            $opacity = rand(3, 8) / 10;
            echo '<div class="spark" style="top: '.$top.'%; width: '.$width.'px; animation-duration: '.$speed.'s; animation-delay: '.$delay.'s; opacity: '.$opacity.';"></div>';
        }
        ?>
    </div>

    <!-- CANDLESTICK TRACK WITH SEAMLESS LOOPING -->
    <div class="candle-track">
        <?php 
        $candles = [];
        for($i = 0; $i < 25; $i++) {
            $type = rand(0, 1) ? 'green' : 'red';
            $height = rand(35, 120);
            $wickHeight = $height + rand(15, 60);
            $offset = rand(-45, 45);
            $bobSpeed = rand(22, 45) / 10; // 2.2s to 4.5s
            $bobDelay = rand(-40, 0) / 10; // -4.0s to 0s
            $candles[] = [
                'type' => $type,
                'height' => $height,
                'wickHeight' => $wickHeight,
                'offset' => $offset,
                'bobSpeed' => $bobSpeed,
                'bobDelay' => $bobDelay
            ];
        }
        // Output TWICE for 100% perfect seamless scroll loop
        for($loop = 0; $loop < 2; $loop++) {
            foreach($candles as $c) {
                echo '<div class="candle-container" style="--y-offset: '.$c['offset'].'px;">';
                echo '<div class="candle '.$c['type'].'" style="animation-duration: '.$c['bobSpeed'].'s; animation-delay: '.$c['bobDelay'].'s;">';
                echo '<div class="candle-wick" style="height: '.$c['wickHeight'].'px"></div>';
                echo '<div class="candle-body" style="height: '.$c['height'].'px"></div>';
                echo '</div>';
                echo '</div>';
            }
        }
        ?>
    </div>

    <!-- HEADER NAVIGATION -->
    <div class="landing-header">
        <button class="btn-nav outline" onclick="showAuthCard('login')">Sign In</button>
        <button class="btn-nav solid" onclick="showAuthCard('register')">Sign Up</button>
    </div>

    <!-- MIDDLE WRAPPER -->
    <div class="landing-middle-wrap">
        <!-- RUNNING BULL WRAPPER -->
        <div class="bull-container">
            <img src="assets/nyangkuters_logo-removebg-preview.png" alt="Sobat Ritel" class="intro-core-logo">
        </div>

        <!-- MAIN CENTRAL BRANDING -->
        <div class="landing-center">
            <h1 class="brand-name">Sobat Ritel</h1>
            <div class="typing-container">
                <span id="typing-text" class="fade-text"></span>
            </div>
            <button class="btn-action" onclick="showAuthCard('login')">Access Dashboard</button>
        </div>
    </div>

    <!-- FOOTER INFO -->
    <div class="landing-footer">
        <div class="footer-left">Sobat Ritel © 2026. All rights reserved.</div>
        <div class="footer-right"># ARA KAN NDAR</div>
    </div>
</div>

<div class="login-wrapper">

    <!-- ══ LEFT: FORM,LOGIN ══ -->
    <div class="login-panel">
        <button type="button" class="btn-close-login" onclick="hideLoginCard()"><i class="fa-solid fa-xmark"></i></button>
        <div class="logo-area">
            <img src="assets/nyangkuters_logo-removebg-preview.png" alt="Sobat Ritel" class="logo-animate">
            <div class="welcome-text">Siap mendeteksi pergerakan Bandar hari ini!!</div>
        </div>

        <!-- Segmented Tab Selector -->
        <div class="auth-tabs">
            <button type="button" class="auth-tab-btn active" id="tab-login" onclick="switchAuthMode('login')">Sign In</button>
            <button type="button" class="auth-tab-btn" id="tab-register" onclick="switchAuthMode('register')">Sign Up</button>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- SIGN IN FORM -->
        <form action="auth.php" method="POST" id="login-form">
            <div class="form-group">
                <label><i class="fa-regular fa-user" style="margin-right:6px;"></i> USERNAME</label>
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="username" id="username"
                           placeholder="admin / user" required autocomplete="username">
                </div>
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-key"></i> PASSWORD</label>
                <div class="input-group" style="position: relative; width: 100%;">
                    <i class="fa-solid fa-lock" style="position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: #64748b; z-index: 10; pointer-events: none;"></i>
                    <input type="password" name="password" id="password"
                           placeholder="••••••••" required autocomplete="current-password"
                           style="width: 100%; background: var(--input-bg); border: 1px solid rgba(255,255,255,0.1); padding: 0.65rem 3rem 0.65rem 2.5rem; border-radius: 1.2rem; font-size: 0.95rem; font-weight: 500; color: var(--text-light); font-family: 'Inter', monospace; outline: none; display: block;">
                    <button type="button" class="toggle-pass" style="position: absolute; right: 1.5rem !important; top: 50% !important; transform: translateY(-50%) !important; z-index: 10; background: none; border: none; padding: 0; color: #64748b; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center;"
                            onclick="togglePasswordVisibility('password', 'toggleEyeIcon')" aria-label="Toggle visibility">
                        <i class="fa-regular fa-eye" id="toggleEyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <span>ACCESS DASHBOARD</span>
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
            </button>
        </form>

        <!-- SIGN UP FORM -->
        <form action="register.php" method="POST" id="register-form" style="display: none;">
            <div class="form-group">
                <label><i class="fa-solid fa-signature" style="margin-right:6px;"></i> NAMA LENGKAP</label>
                <div class="input-group">
                    <i class="fa-solid fa-user-tag"></i>
                    <input type="text" name="full_name" id="reg-fullname"
                           placeholder="Nama Lengkap Anda" required autocomplete="name">
                </div>
            </div>
            <div class="form-group">
                <label><i class="fa-regular fa-user" style="margin-right:6px;"></i> USERNAME</label>
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="username" id="reg-username"
                           placeholder="Buat username baru" required autocomplete="username">
                </div>
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-key"></i> PASSWORD</label>
                <div class="input-group" style="position: relative; width: 100%;">
                    <i class="fa-solid fa-lock" style="position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: #64748b; z-index: 10; pointer-events: none;"></i>
                    <input type="password" name="password" id="reg-password"
                           placeholder="Minimal 6 karakter" required autocomplete="new-password"
                           style="width: 100%; background: var(--input-bg); border: 1px solid rgba(255,255,255,0.1); padding: 0.65rem 3rem 0.65rem 2.5rem; border-radius: 1.2rem; font-size: 0.95rem; font-weight: 500; color: var(--text-light); font-family: 'Inter', monospace; outline: none; display: block;">
                    <button type="button" class="toggle-pass" style="position: absolute; right: 1.5rem !important; top: 50% !important; transform: translateY(-50%) !important; z-index: 10; background: none; border: none; padding: 0; color: #64748b; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center;"
                            onclick="togglePasswordVisibility('reg-password', 'regToggleEyeIcon')" aria-label="Toggle visibility">
                        <i class="fa-regular fa-eye" id="regToggleEyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login" style="background: linear-gradient(95deg, #10b981, #047857);">
                <span>CREATE ACCOUNT</span>
                <i class="fa-solid fa-user-plus"></i>
            </button>
        </form>

        <div class="footer-note">
            <i class="fa-solid fa-shield-halved"></i> Platform Intelijen Pasar Modal & Deteksi Bandarmologi
        </div>
    </div>

    <!-- ══ RIGHT: HERO PANEL ══ -->
    <div class="hero-panel">
        <div class="stats-card">
            <div class="market-ticker">
                <span>IDX Composite</span>
                <span class="trend-up"><i class="fa-solid fa-chart-line"></i> +1.24%</span>
            </div>
            <div class="index-value">7,285.42</div>
            <div class="animated-chart">
                <div class="chart-bar bar1"></div>
                <div class="chart-bar bar2"></div>
                <div class="chart-bar bar3"></div>
                <div class="chart-bar bar4"></div>
                <div class="chart-bar bar5"></div>
            </div>
        </div>
        <h2>Senjata Rahasia Sobat Ritel</h2>
        <p>Pantau akumulasi Big Money, lacak pergerakan bandar secara real-time, dan temukan momentum emas di Bursa Efek Indonesia.</p>
    </div>

</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Initialize active auth mode based on PHP session state
        switchAuthMode('<?php echo $default_mode; ?>');

        // Elegant cross-fade text transitions
       const words = [
            "Ragumu Rugimu, Yakinmu Nyangkutmu",
            "Buy Sekarang, Nangis Belakangan",
            "Cuan Itu Opsional, Nyangkut Itu Tradisi",
            "Porto Merah = Investor Jangka Panjang",
            "Scalping 5 Menit, Hold 5 Semester",
            "Sell Pas Naik, Nangis Pas Auto ARA",
            "Katanya Koreksi Sehat, Kok ICU",
            "Belum Loss Kalo Belum Dijual",
            "Analisa Ribet, Ujungnya Ikut Telegram",
            "Nunggu Balik Modal Sampai Anak Lulus",
            "Bandar Tepuk Tangan Lihat Ritel Averaging",
            "Masuk Karena Yakin, Keluar Karena Tagihan",
            "Saham Turun Dibilang Diskon Biar Kuat",
            "Trading Plan: Bismillah & Feeling",
            "Cuan Tipis Gapapa, Story Harus Ada"
        ];
        let wordIndex = 0;
        const typingText = document.getElementById("typing-text");

        function updateFadeText() {
            if (!typingText) return;
            // Hide the text
            typingText.classList.remove("show");

            // Wait for fade-out to finish, then swap text and fade in
            setTimeout(() => {
                typingText.textContent = words[wordIndex];
                typingText.classList.add("show");
                // Move to next word
                wordIndex = (wordIndex + 1) % words.length;
            }, 800); // matches transition time
        }

        // Initialize immediately
        updateFadeText();
        // Cycle every 3.5 seconds
        setInterval(updateFadeText, 3500);

        // Horizontal cursor controlled candle track loop
        const track = document.querySelector('.candle-track');
        if (track) {
            let currentX = 0;
            let targetSpeed = -1.2; // Default smooth ambient drift to the left
            let currentSpeed = -1.2;
            let skewAngle = 0;
            let targetSkew = 0;
            let mouseX = window.innerWidth / 2;
            let mouseInWindow = false;

            let halfWidth = track.scrollWidth / 2;
            
            const updateDimensions = () => {
                halfWidth = track.scrollWidth / 2;
            };
            window.addEventListener('resize', updateDimensions);
            // Re-calculate after a brief delay to ensure layout is done
            setTimeout(updateDimensions, 500);

            window.addEventListener('mousemove', (e) => {
                mouseInWindow = true;
                mouseX = e.clientX;
            });

            document.addEventListener('mouseleave', () => {
                mouseInWindow = false;
            });

            function animateCandles() {
                if (mouseInWindow) {
                    const ratioCentered = (mouseX - window.innerWidth / 2) / (window.innerWidth / 2);
                    // ratioCentered goes from -1 (far left) to +1 (far right)
                    // Far left: scroll right (positive speed)
                    // Far right: scroll left (negative speed)
                    targetSpeed = ratioCentered * -8; // max speed 8
                } else {
                    targetSpeed = -1.2; // Return to default auto-scroll
                }

                // Interpolate speed (lerp)
                currentSpeed += (targetSpeed - currentSpeed) * 0.06;

                // Accumulate position
                currentX += currentSpeed;

                // Loop checking
                if (halfWidth > 0) {
                    if (currentX <= -halfWidth) {
                        currentX += halfWidth;
                    } else if (currentX >= 0) {
                        currentX -= halfWidth;
                    }
                }

                // Apply scroll translation
                track.style.transform = `translateX(${currentX}px)`;

                // Organic dynamic tilt (skew) based on movement speed
                targetSkew = Math.max(-10, Math.min(10, currentSpeed * 1.5));
                skewAngle += (targetSkew - skewAngle) * 0.1;
                track.style.setProperty('--skew-angle', `${skewAngle}deg`);

                requestAnimationFrame(animateCandles);
            }

            // Start animation
            requestAnimationFrame(animateCandles);
        }
    });

    function showLoginCard() {
        showAuthCard('login');
    }

    function showAuthCard(mode = 'login') {
        const intro = document.getElementById("intro-overlay");
        const wrapper = document.querySelector(".login-wrapper");
        if (intro) {
            intro.classList.add('intro-ended');
        }
        if (wrapper) {
            wrapper.classList.add("show");
        }
        switchAuthMode(mode);
    }

    function switchAuthMode(mode) {
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const tabLogin = document.getElementById('tab-login');
        const tabRegister = document.getElementById('tab-register');
        const welcomeText = document.querySelector('.logo-area .welcome-text');

        if (mode === 'login') {
            if (loginForm) loginForm.style.display = 'block';
            if (registerForm) registerForm.style.display = 'none';
            if (tabLogin) tabLogin.classList.add('active');
            if (tabRegister) tabRegister.classList.remove('active');
            if (welcomeText) welcomeText.textContent = 'Siap mendeteksi pergerakan Bandar hari ini!!';
        } else {
            if (loginForm) loginForm.style.display = 'none';
            if (registerForm) registerForm.style.display = 'block';
            if (tabLogin) tabLogin.classList.remove('active');
            if (tabRegister) tabRegister.classList.add('active');
            if (welcomeText) welcomeText.textContent = 'Daftar sekarang untuk mengintai pergerakan Bandar!';
        }
    }

    function hideLoginCard() {
        const intro = document.getElementById("intro-overlay");
        const wrapper = document.querySelector(".login-wrapper");
        if (intro) {
            intro.classList.remove('intro-ended');
        }
        if (wrapper) {
            wrapper.classList.remove("show");
        }
    }

    function togglePasswordVisibility(fieldId = 'password', eyeId = 'toggleEyeIcon') {
        const pwdField = document.getElementById(fieldId);
        const icon     = document.getElementById(eyeId);
        if (!pwdField || !icon) return;
        if (pwdField.type === 'password') {
            pwdField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            pwdField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
</body>
</html>
