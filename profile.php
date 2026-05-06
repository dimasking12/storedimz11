<?php
require_once 'config_web.php';
if (!isset($_SESSION['user_id'])) redirect('login.php');
$user = getWebUser($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - DIMZSTORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/style.css?v=999">
    <style>
        .profile-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
        }
        .profile-header {
            background: linear-gradient(135deg, var(--accent), #1a1a26);
            padding: 40px 20px;
            text-align: center;
            position: relative;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            border: 5px solid rgba(255, 255, 255, 0.2);
            font-size: 3rem;
            color: var(--accent);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .profile-info {
            padding: 30px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border);
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-value {
            color: white;
            font-weight: 700;
            font-size: 1rem;
        }
        .badge-role {
            background: rgba(0, 212, 170, 0.1);
            color: var(--accent);
            padding: 5px 12px;
            border-radius: 100px;
            font-size: 0.75rem;
            border: 1px solid var(--accent);
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    <div class="app-wrapper">
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
                    <li><a href="rental.php" class="nav-link"><i class="fas fa-key"></i> <span>Rental Akun</span></a></li>
                    <li><a href="history.php" class="nav-link"><i class="fas fa-history"></i> <span>Purchase History</span></a></li>
                    <li><a href="history_rental.php" class="nav-link"><i class="fas fa-clock-rotate-left"></i> <span>Riwayat Rental</span></a></li>
                    <li><a href="redeem.php" class="nav-link"><i class="fas fa-gift"></i> <span>Tukar Balance</span></a></li>
                </ul>
                <div class="menu-header">Account Details</div>
                <ul>
                    <li><a href="profile.php" class="nav-link active"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
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
                <div class="sidebar-brand" style="padding: 0;"><div class="brand-icon" style="width:32px; height:32px;"><i class="fas fa-bolt"></i></div><h1 class="brand-name" style="font-size:1rem;">DIMZ<span>STORE</span></h1></div>
                <button class="btn btn-ghost" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            </header>

            <header class="topbar">
                <h2 class="page-title">My Account</h2>
                <div class="topbar-user">
                    <div class="user-info text-right">
                        <span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span>
                        <span class="user-role"><?php echo strtoupper($user['role']); ?></span>
                    </div>
                </div>
            </header>

            <div class="page-content">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2 style="margin-top:15px; color:white; font-weight:800;"><?php echo htmlspecialchars($user['username']); ?></h2>
                        <span class="badge-role"><?php echo strtoupper($user['role']); ?> MEMBER</span>
                    </div>
                    <div class="profile-info">
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-user-tag"></i> Username</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fab fa-whatsapp"></i> No. WhatsApp</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['wa_number']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fab fa-telegram"></i> Telegram</span>
                            <span class="info-value">@<?php echo htmlspecialchars($user['telegram_user'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-shield-alt"></i> Akun Role</span>
                            <span class="info-value" style="color:var(--accent);"><?php echo strtoupper($user['role']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-calendar-check"></i> Join Date</span>
                            <span class="info-value"><?php echo date('d F Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
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
