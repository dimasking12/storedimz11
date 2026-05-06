<?php
require_once 'config_web.php';
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}
$user = getWebUser($_SESSION['user_id']);
$balance = intval($user['points'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tukar Balance - DIMZSTORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/style.css?v=999">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <style>
        /* ===== REDEEM PAGE STYLES ===== */
        .balance-hero {
            background: linear-gradient(135deg, rgba(0,212,170,0.12) 0%, rgba(99,102,241,0.12) 100%);
            border: 1px solid rgba(0,212,170,0.25);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .balance-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0,212,170,0.06) 0%, transparent 60%);
            animation: pulse-glow 4s ease-in-out infinite;
        }
        @keyframes pulse-glow { 0%,100%{opacity:0.5;transform:scale(1);} 50%{opacity:1;transform:scale(1.05);} }
        .balance-label { font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px; }
        .balance-amount { font-size: 3.5rem; font-weight: 800; color: var(--accent); line-height: 1; margin-bottom: 6px; }
        .balance-unit { font-size: 0.85rem; color: var(--text-secondary); font-weight: 500; }

        /* Game Selector */
        .game-selector { display: flex; gap: 12px; margin-bottom: 2rem; }
        .game-btn {
            flex: 1; padding: 16px; border-radius: 16px; border: 2px solid var(--border);
            background: var(--bg-card); cursor: pointer; transition: all 0.25s ease;
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            color: var(--text-secondary); font-weight: 700; font-size: 0.85rem;
        }
        .game-btn i { font-size: 1.5rem; }
        .game-btn.active { border-color: var(--accent); background: rgba(0,212,170,0.08); color: var(--accent); }
        .game-btn:hover:not(.active) { border-color: rgba(0,212,170,0.4); transform: translateY(-2px); }
        .game-btn .game-emoji { font-size: 2rem; }

        /* Redeem Cards */
        .redeem-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .redeem-card {
            background: var(--bg-card); border: 2px solid var(--border); border-radius: 18px;
            padding: 24px 20px; cursor: pointer; transition: all 0.25s ease; text-align: center;
            position: relative; overflow: hidden;
        }
        .redeem-card::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(0,212,170,0.08), transparent);
            opacity: 0; transition: opacity 0.25s;
        }
        .redeem-card:hover { border-color: var(--accent); transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,212,170,0.15); }
        .redeem-card:hover::after { opacity: 1; }
        .redeem-card.selected { border-color: var(--accent); background: rgba(0,212,170,0.08); }
        .redeem-card.selected::after { opacity: 1; }
        .redeem-card.disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }

        .redeem-days { font-size: 2.5rem; font-weight: 800; color: white; line-height: 1; }
        .redeem-days-label { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
        .redeem-cost { display: flex; align-items: center; justify-content: center; gap: 6px; background: rgba(0,212,170,0.1); border-radius: 100px; padding: 6px 14px; margin: 0 auto; display: inline-flex; }
        .redeem-cost-num { font-weight: 800; color: var(--accent); font-size: 1rem; }
        .redeem-cost-unit { font-size: 0.7rem; color: var(--text-secondary); font-weight: 600; }
        .redeem-badge { position: absolute; top: 12px; right: 12px; background: var(--warning); color: black; font-size: 0.6rem; font-weight: 800; padding: 3px 8px; border-radius: 100px; }

        .redeem-info-box {
            background: rgba(255,170,0,0.06); border: 1px solid rgba(255,170,0,0.2);
            border-radius: 14px; padding: 16px 20px; margin-bottom: 2rem; display: flex; gap: 12px; align-items: flex-start;
        }
        .redeem-info-box i { color: var(--warning); margin-top: 2px; }

        .btn-redeem-submit {
            width: 100%; padding: 16px; border-radius: 14px; font-size: 1rem; font-weight: 800;
            background: linear-gradient(135deg, var(--accent), #00a884); border: none;
            color: white; cursor: pointer; transition: all 0.25s; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-redeem-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,212,170,0.4); }
        .btn-redeem-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        /* Result Modal */
        #result-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.92); z-index:9000; align-items:center; justify-content:center; padding:20px; }
        .result-box { background: var(--bg-card); border: 1px solid var(--border); border-radius: 24px; padding: 36px 28px; max-width: 400px; width: 100%; text-align: center; animation: popIn 0.4s cubic-bezier(.175,.885,.32,1.275); }
        @keyframes popIn { from{opacity:0;transform:scale(0.8);} to{opacity:1;transform:scale(1);} }
        .result-icon { font-size: 3.5rem; margin-bottom: 16px; display: block; }
        .result-key { background: #0d0d18; border: 1px solid var(--border); border-radius: 12px; padding: 16px; font-family: monospace; font-size: 1.4rem; font-weight: 800; color: var(--accent); letter-spacing: 3px; margin: 20px 0; word-break: break-all; }

        @media (max-width: 480px) {
            .balance-amount { font-size: 2.5rem; }
            .game-selector { flex-direction: row; }
            .redeem-grid { grid-template-columns: 1fr 1fr 1fr; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon"><i class="fas fa-bolt"></i></div>
                <h1 class="brand-name">DIMZ<span>STORE</span></h1>
            </div>
            <nav class="sidebar-nav">
                <div class="menu-header">Main Menu</div>
                <ul>
                    <li><a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> <span>Dashboard News</span></a></li>
                    <li><a href="store.php" class="nav-link"><i class="fas fa-shopping-cart"></i> <span>License Store</span></a></li>
                    <li><a href="history.php" class="nav-link"><i class="fas fa-history"></i> <span>Purchase History</span></a></li>
                    <li><a href="redeem.php" class="nav-link active"><i class="fas fa-gift"></i> <span>Tukar Balance</span></a></li>
                </ul>
                <div class="menu-header">Account Details</div>
                <ul>
                    <li><a href="profile.php" class="nav-link"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
                    <li><a href="javascript:showSupportOptions()" class="nav-link"><i class="fas fa-headset"></i> <span>Technical Support</span></a></li>
                    <?php if(isWebAdmin()): ?>
                    <li><a href="admin.php" class="nav-link" style="color: #ffaa00;"><i class="fas fa-user-shield"></i> <span>Admin Control</span></a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Sign Out</span></a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="mobile-header">
                <div class="sidebar-brand" style="padding:0;"><div class="brand-icon" style="width:32px;height:32px;"><i class="fas fa-bolt"></i></div><h1 class="brand-name" style="font-size:1rem;">DIMZ<span>STORE</span></h1></div>
                <button class="btn btn-ghost" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            </header>

            <header class="topbar">
                <h2 class="page-title"><i class="fas fa-gift" style="color:var(--accent);"></i> Tukar Balance</h2>
                <div class="topbar-user">
                    <div class="user-info"><span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span></div>
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                </div>
            </header>

            <div class="page-content">

                <!-- Balance Hero -->
                <div class="balance-hero">
                    <div class="balance-label"><i class="fas fa-coins"></i> Balance Kamu Saat Ini</div>
                    <div class="balance-amount" id="live-balance"><?php echo $balance; ?></div>
                    <div class="balance-unit">Balance Points</div>
                </div>

                <!-- Info Box -->
                <div class="redeem-info-box">
                    <i class="fas fa-info-circle fa-lg"></i>
                    <div style="font-size:0.83rem; color:var(--text-secondary); line-height:1.7;">
                        Tukarkan balance kamu dengan <b style="color:white;">licence key</b> aktif!
                        Balance didapat dari setiap pembelian. Key akan langsung aktif dan tercatat di database.
                        <br><b style="color:var(--warning);">1 hari = 12 balance &nbsp;|&nbsp; 3 hari = 36 balance &nbsp;|&nbsp; 7 hari = 84 balance</b>
                    </div>
                </div>

                <!-- Game Selector -->
                <div style="margin-bottom:1rem;"><label class="form-label"><i class="fas fa-gamepad"></i> PILIH GAME</label></div>
                <div class="game-selector">
                    <button class="game-btn active" id="btn-ff" onclick="selectGame('ff')">
                        <img src="img/ff.png" alt="Free Fire" style="width:60px; height:60px; border-radius:14px; object-fit:cover; box-shadow:0 4px 14px rgba(0,0,0,0.4);">
                        <span>FREE FIRE</span>
                    </button>
                    <button class="game-btn" id="btn-ffmax" onclick="selectGame('ffmax')">
                        <img src="img/ffmax.png" alt="FF Max" style="width:60px; height:60px; border-radius:14px; object-fit:cover; box-shadow:0 4px 14px rgba(0,0,0,0.4);">
                        <span>FF MAX</span>
                    </button>
                </div>

                <!-- Redeem Cards -->
                <div style="margin-bottom:1rem;"><label class="form-label"><i class="fas fa-calendar-alt"></i> PILIH DURASI</label></div>
                <div class="redeem-grid">
                    <div class="redeem-card <?php echo $balance < 12 ? 'disabled' : ''; ?>" id="card-1" onclick="selectDuration(1, 12)">
                        <div class="redeem-days">1</div>
                        <div class="redeem-days-label">Hari</div>
                        <div class="redeem-cost">
                            <span class="redeem-cost-num">12</span>
                            <span class="redeem-cost-unit">balance</span>
                        </div>
                        <?php if($balance >= 12): ?><span class="redeem-badge">✓ BISA</span><?php endif; ?>
                    </div>

                    <div class="redeem-card <?php echo $balance < 36 ? 'disabled' : ''; ?>" id="card-3" onclick="selectDuration(3, 36)">
                        <div class="redeem-days">3</div>
                        <div class="redeem-days-label">Hari</div>
                        <div class="redeem-cost">
                            <span class="redeem-cost-num">36</span>
                            <span class="redeem-cost-unit">balance</span>
                        </div>
                        <?php if($balance >= 36): ?><span class="redeem-badge">✓ BISA</span><?php endif; ?>
                    </div>

                    <div class="redeem-card <?php echo $balance < 84 ? 'disabled' : ''; ?>" id="card-7" onclick="selectDuration(7, 84)">
                        <div class="redeem-days">7</div>
                        <div class="redeem-days-label">Hari</div>
                        <div class="redeem-cost">
                            <span class="redeem-cost-num">84</span>
                            <span class="redeem-cost-unit">balance</span>
                        </div>
                        <?php if($balance >= 84): ?><span class="redeem-badge" style="background:var(--accent); color:black;">HEMAT</span><?php endif; ?>
                    </div>
                </div>

                <!-- Summary & Submit -->
                <div class="card" id="summary-box" style="display:none; margin-bottom:1.5rem; border-color:rgba(0,212,170,0.3);">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:4px;">RINGKASAN PENUKARAN</div>
                            <div style="font-weight:800; font-size:1rem;" id="summary-text">—</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:0.75rem; color:var(--text-muted);">Sisa Balance</div>
                            <div style="font-weight:800; color:var(--warning); font-size:1.1rem;" id="summary-remaining">—</div>
                        </div>
                    </div>
                </div>

                <button class="btn-redeem-submit" id="submit-btn" disabled onclick="doRedeem()">
                    <i class="fas fa-gift"></i>
                    <span>PILIH GAME & DURASI DULU</span>
                </button>
            </div>
        </main>
    </div>

    <!-- RESULT MODAL -->
    <div id="result-modal">
        <div class="result-box">
            <span class="result-icon">🎉</span>
            <div style="font-size:1.3rem; font-weight:800; margin-bottom:8px;">Penukaran Berhasil!</div>
            <div style="font-size:0.82rem; color:var(--text-muted); margin-bottom:4px;">Licence Key Aktif Kamu:</div>
            <div class="result-key" id="result-key-display">-</div>
            <div style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:20px;" id="result-detail">-</div>
            <button class="btn btn-primary" style="width:100%; justify-content:center; margin-bottom:10px;" onclick="copyKey()">
                <i class="fas fa-copy"></i> SALIN KEY
            </button>
            <button class="btn btn-ghost" style="width:100%; border:1px solid var(--border);" onclick="closeResult()">
                TUTUP
            </button>
        </div>
    </div>

    <script>
        let selectedGame = 'ff';
        let selectedDays = 0;
        let selectedCost = 0;
        let currentBalance = <?php echo $balance; ?>;

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        function selectGame(game) {
            selectedGame = game;
            document.getElementById('btn-ff').classList.toggle('active', game === 'ff');
            document.getElementById('btn-ffmax').classList.toggle('active', game === 'ffmax');
            updateSubmitBtn();
        }

        function selectDuration(days, cost) {
            if (currentBalance < cost) return;
            selectedDays = days;
            selectedCost = cost;

            // Update card selection
            ['card-1', 'card-3', 'card-7'].forEach(id => document.getElementById(id).classList.remove('selected'));
            const cardMap = {1:'card-1', 3:'card-3', 7:'card-7'};
            document.getElementById(cardMap[days]).classList.add('selected');

            // Update summary
            const remaining = currentBalance - cost;
            document.getElementById('summary-box').style.display = 'block';
            document.getElementById('summary-text').textContent = `${days} Hari — ${cost} balance`;
            document.getElementById('summary-remaining').textContent = `${remaining} balance`;

            updateSubmitBtn();
        }

        function updateSubmitBtn() {
            const btn = document.getElementById('submit-btn');
            const span = btn.querySelector('span');
            if (selectedDays > 0 && selectedGame) {
                btn.disabled = false;
                const gameLabel = selectedGame === 'ff' ? '🔥 FF' : '⚡ FF MAX';
                span.textContent = `TUKAR ${selectedDays} HARI (${gameLabel}) — ${selectedCost} Balance`;
            } else {
                btn.disabled = true;
                span.textContent = 'PILIH GAME & DURASI DULU';
            }
        }

        async function doRedeem() {
            if (!selectedGame || !selectedDays) return;

            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.querySelector('span').textContent = 'Memproses...';
            btn.querySelector('i').className = 'fas fa-spinner fa-spin';

            const fd = new FormData();
            fd.append('action', 'redeem_points');
            fd.append('game_type', selectedGame);
            fd.append('days', selectedDays);
            fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

            const resp = await fetch('api/store.php', { method: 'POST', body: fd });
            const data = await resp.json();

            btn.disabled = false;
            btn.querySelector('i').className = 'fas fa-gift';
            updateSubmitBtn();

            if (data.success) {
                // Update balance live
                currentBalance = data.new_balance;
                document.getElementById('live-balance').textContent = currentBalance;

                // Show result modal
                document.getElementById('result-key-display').textContent = data.licence;
                document.getElementById('result-detail').innerHTML =
                    `<b style="color:var(--accent);">${selectedGame.toUpperCase()}</b> · ${selectedDays} Hari · Sisa Balance: <b>${data.new_balance}</b>`;
                document.getElementById('result-modal').style.display = 'flex';

                // Reset selection
                selectedDays = 0; selectedCost = 0;
                ['card-1','card-3','card-7'].forEach(id => document.getElementById(id).classList.remove('selected'));
                document.getElementById('summary-box').style.display = 'none';
                updateSubmitBtn();
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message, background: '#1a1a26', color: '#fff' });
            }
        }

        function copyKey() {
            const key = document.getElementById('result-key-display').textContent;
            navigator.clipboard.writeText(key).then(() => {
                Swal.fire({ icon: 'success', title: 'Disalin!', text: key, timer: 1500, showConfirmButton: false, background: '#1a1a26', color: '#fff' });
            });
        }

        function closeResult() {
            document.getElementById('result-modal').style.display = 'none';
            location.reload(); // refresh balance cards
        }

        function showSupportOptions() {
            Swal.fire({
                title: 'Technical Support',
                text: 'Silahkan pilih layanan bantuan:',
                background: '#1a1a26', color: '#fff',
                showConfirmButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="fab fa-whatsapp"></i> WhatsApp',
                denyButtonText: '<i class="fab fa-telegram"></i> Telegram',
                confirmButtonColor: '#25D366',
                denyButtonColor: '#0088cc',
            }).then((result) => {
                if (result.isConfirmed) window.open('https://wa.me/6283850791311', '_blank');
                else if (result.isDenied) window.open('https://t.me/dimasvip1120', '_blank');
            });
        }
    </script>
</body>
</html>
