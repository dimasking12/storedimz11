<?php
require_once 'config_web.php';
if (!isWebAdmin()) {
    redirect('login');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Admin Hub - DIMZSTORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/style.css?v=999">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <style>
        /* Admin Tabs */
        .admin-tabs { display: flex; gap: 5px; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); overflow-x: auto; padding-bottom: 5px; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
        .admin-tabs::-webkit-scrollbar { display: none; }
        .admin-tab { padding: 10px 18px; cursor: pointer; color: var(--text-secondary); font-weight: 700; font-size: 0.75rem; border-bottom: 2px solid transparent; transition: 0.3s; white-space: nowrap; border-radius: 8px 8px 0 0; }
        .admin-tab.active { color: var(--accent); border-bottom-color: var(--accent); background: var(--accent-glow); }
        .admin-section { display: none; }
        .admin-section.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Upload Progress */
        .progress-wrapper { width: 100%; height: 8px; background: var(--bg-input); border-radius: 10px; margin: 15px 0; overflow: hidden; display: none; }
        .progress-bar { height: 100%; width: 0%; background: var(--accent); transition: width 0.2s; }
        .upload-status { font-size: 0.75rem; color: var(--text-secondary); text-align: center; margin-bottom: 10px; }

        /* Mobile fixes */
        @media (max-width: 1024px) {
            .grid-form { grid-template-columns: 1fr !important; gap: 1rem !important; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon" style="background: var(--warning); color: black;"><i class="fas fa-user-shield"></i></div>
                <h1 class="brand-name">ADMIN<span>HUB</span></h1>
            </div>
            <nav class="sidebar-nav">
                <div class="menu-header">Quick Access</div>
                <ul>
                    <li><a href="dashboard" class="nav-link"><i class="fas fa-home"></i> <span>Dashboard News</span></a></li>
                    <li><a href="store" class="nav-link"><i class="fas fa-shopping-cart"></i> <span>License Store</span></a></li>
                </ul>
                <div class="menu-header">Control Center</div>
                <ul>
                    <li><a id="nav-revenue" href="#" class="nav-link active" onclick="switchAdminTab('revenue')"><i class="fas fa-chart-line"></i> <span>Revenue Stats</span></a></li>
                    <li><a id="nav-products" href="#" class="nav-link" onclick="switchAdminTab('products')"><i class="fas fa-box"></i> <span>Products</span></a></li>
                    <li><a id="nav-rentals" href="#" class="nav-link" onclick="switchAdminTab('rentals')"><i class="fas fa-key"></i> <span>Rentals Management</span></a></li>
                    <li><a id="nav-rental_tx" href="#" class="nav-link" onclick="switchAdminTab('rental_tx')"><i class="fas fa-receipt"></i> <span>Rental Orders</span></a></li>
                    <li><a id="nav-licenses" href="#" class="nav-link" onclick="switchAdminTab('licenses')"><i class="fas fa-key"></i> <span>Purchased Keys</span></a></li>
                    <li><a id="nav-transactions" href="#" class="nav-link" onclick="switchAdminTab('transactions')"><i class="fas fa-receipt"></i> <span>All Transactions</span></a></li>
                    <li><a id="nav-news" href="#" class="nav-link" onclick="switchAdminTab('news')"><i class="fas fa-bullhorn"></i> <span>News & Updates</span></a></li>
                    <li><a id="nav-vouchers" href="#" class="nav-link" onclick="switchAdminTab('vouchers')"><i class="fas fa-ticket-alt"></i> <span>Vouchers</span></a></li>
                    <li><a id="nav-users" href="#" class="nav-link" onclick="switchAdminTab('users')"><i class="fas fa-users"></i> <span>Registered Users</span></a></li>
                    <li><a id="nav-notifications" href="#" class="nav-link" onclick="switchAdminTab('notifications')"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
                    <li><a id="nav-points" href="#" class="nav-link" onclick="switchAdminTab('points')"><i class="fas fa-coins"></i> <span>Point Settings</span></a></li>
                </ul>
                <div class="menu-header">Account</div>
                <ul>
                    <li><a href="profile" class="nav-link"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
                    <li><a href="logout" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Sign Out</span></a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <!-- Mobile Header -->
            <header class="mobile-header">
                <div class="sidebar-brand" style="padding: 0;">
                    <div class="brand-icon" style="width:32px; height:32px; background:var(--warning); color:black;"><i class="fas fa-user-shield"></i></div>
                    <h1 class="brand-name" style="font-size:1rem;">ADMIN<span>HUB</span></h1>
                </div>
                <button class="btn btn-ghost" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            </header>

            <header class="topbar">
                <h2 class="page-title" id="tab-title">Manage Products</h2>
                <div class="topbar-user">
                    <div class="user-avatar" style="color:var(--warning);"><i class="fas fa-shield-alt"></i></div>
                </div>
            </header>

            <div class="page-content">
                <div class="admin-tabs">
                    <div class="admin-tab active" onclick="switchAdminTab('revenue')">REVENUE</div>
                    <div class="admin-tab" onclick="switchAdminTab('products')">PRODUCTS</div>
                    <div class="admin-tab" onclick="switchAdminTab('rentals')">RENTALS</div>
                    <div class="admin-tab" onclick="switchAdminTab('rental_tx')">RNT ORDERS</div>
                    <div class="admin-tab" onclick="switchAdminTab('transactions')">TX KEYS</div>
                    <div class="admin-tab" onclick="switchAdminTab('news')">NEWS</div>
                    <div class="admin-tab" onclick="switchAdminTab('vouchers')">VOUCHERS</div>
                    <div class="admin-tab" onclick="switchAdminTab('users')">USERS</div>
                    <div class="admin-tab" onclick="switchAdminTab('notifications')"><i class="fas fa-bell"></i> NOTIF</div>
                    <div class="admin-tab" onclick="switchAdminTab('points')"><i class="fas fa-coins"></i> POINTS</div>
                </div>

                <!-- SECTION: REVENUE -->
                <div id="section-revenue" class="admin-section active">
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div style="display:flex; flex-direction:column; gap:12px;">
                             <h3 class="card-title" style="margin:0;"><i class="fas fa-filter"></i> Filter Range</h3>
                             <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                                 <input type="date" id="rev-start-date" class="form-control" style="flex:1; min-width:130px; padding:8px 12px; font-size:0.85rem;">
                                 <span style="color:var(--text-muted);">s/d</span>
                                 <input type="date" id="rev-end-date" class="form-control" style="flex:1; min-width:130px; padding:8px 12px; font-size:0.85rem;">
                                 <button class="btn btn-primary" onclick="loadRevenueStats()" style="width:100%; padding:10px 15px;"><i class="fas fa-sync"></i> Apply</button>
                             </div>
                        </div>
                    </div>

                    <!-- Stats: scrollable on mobile -->
                    <div class="responsive-table-wrapper" style="margin-bottom:1.5rem;">
                        <div class="stats-grid" style="display:flex; gap:1.5rem; min-width:650px;">
                            <div class="stat-card" style="flex:1;">
                                <span class="stat-label">Total Keuntungan</span>
                                <div class="stat-value" id="rev-total-revenue">Rp 0</div>
                                <i class="fas fa-money-bill-wave stat-icon" style="color:var(--accent);"></i>
                            </div>
                            <div class="stat-card" style="flex:1;">
                                <span class="stat-label">Total Penjualan</span>
                                <div class="stat-value" id="rev-total-sales">0</div>
                                <i class="fas fa-shopping-bag stat-icon" style="color:var(--warning);"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Charts: scrollable on mobile -->
                    <div class="responsive-table-wrapper">
                        <div style="display:flex; gap:1.5rem; min-width:850px;">
                            <div class="card" style="flex:2; margin-bottom:0;">
                                <div class="card-header"><h3 class="card-title">Daily Revenue Graph</h3></div>
                                <div style="height: 280px; position: relative;">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                            <div class="card" style="flex:1; margin-bottom:0;">
                                <div class="card-header"><h3 class="card-title">Game Breakdown</h3></div>
                                <div style="height: 280px; position: relative;">
                                    <canvas id="gameChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION: PRODUCTS -->
                <div id="section-products" class="admin-section">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Add Package</h3></div>
                        <form id="add-product-form">
                            <input type="hidden" name="action" value="add_product">
                            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin-bottom: 1.2rem;">
                                <div class="form-group"><label class="form-label">GAME</label><select name="game_type" class="form-control"><option value="ff">Free Fire</option><option value="ffmax">FF Max</option></select></div>
                                <div class="form-group"><label class="form-label">DELIVERY TYPE</label><select name="delivery_type" class="form-control"><option value="auto">Automatic (System)</option><option value="manual">Manual (Admin)</option></select></div>
                            </div>
                            <div class="form-group"><label class="form-label">NAME</label><input type="text" name="name" class="form-control" required></div>
                            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.2rem; margin-bottom: 1.5rem;">
                                <div class="form-group"><label class="form-label">DAYS</label><input type="number" name="duration" class="form-control" required></div>
                                <div class="form-group"><label class="form-label">PRICE</label><input type="number" name="price" class="form-control" required></div>
                                <div class="form-group"><label class="form-label">POINTS</label><input type="number" name="points_reward" class="form-control" required></div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">CREATE PRODUCT</button>
                        </form>
                    </div>
                    <div class="card" style="margin-top:2rem; padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table><thead><tr><th>Game</th><th>Name</th><th>Delivery</th><th>Price</th><th>Action</th></tr></thead>
                            <tbody id="admin-product-container"></tbody></table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: LICENSES -->
                <div id="section-licenses" class="admin-section">
                    <div class="card" style="padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table><thead><tr><th>User</th><th>Game</th><th>License Key</th><th>Status</th><th>Bought At</th><th>Expires At</th></tr></thead>
                            <tbody id="admin-license-container"></tbody></table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: TRANSACTIONS -->
                <div id="section-transactions" class="admin-section">
                    <div style="margin-bottom: 12px; display: flex; gap: 10px;">
                        <div style="position:relative; flex:1;">
                            <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                            <input type="text" id="tx-search-input" placeholder="Cari Order ID Member..." class="form-control" style="padding-left:35px; border-radius:10px;" onkeyup="filterTxTable()">
                        </div>
                    </div>
                    <div class="card" style="padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table><thead><tr><th>User</th><th>Order ID</th><th>Game</th><th>Status</th><th>License</th><th>⏰ Key Expires</th><th>Action</th></tr></thead>
                            <tbody id="admin-transaction-container"></tbody></table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: RENTAL_TX -->
                <div id="section-rental_tx" class="admin-section">
                    <div style="margin-bottom: 12px; display: flex; gap: 10px;">
                        <div style="position:relative; flex:1;">
                            <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                            <input type="text" id="rentaltx-search-input" placeholder="Cari Order ID Rental..." class="form-control" style="padding-left:35px; border-radius:10px;" onkeyup="filterRentalTxTable()">
                        </div>
                    </div>
                    <div class="card" style="padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table><thead><tr><th>User</th><th>Order ID</th><th>Account</th><th>Dur</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody id="admin-rentaltx-container"></tbody></table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: RENTALS -->
                <div id="section-rentals" class="admin-section">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Add Rental Account</h3></div>
                        <form id="add-rental-form">
                            <input type="hidden" name="action" value="add_rental">
                            <div class="form-group"><label class="form-label">TITLE</label><input type="text" name="title" class="form-control" placeholder="Akun Sultan Full Skin..." required></div>
                            <div class="form-group"><label class="form-label">DESCRIPTION</label><textarea name="description" class="form-control" rows="3" placeholder="Detail spesifikasi akun..."></textarea></div>
                            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.2rem; margin-bottom: 1.2rem;">
                                <div class="form-group"><label class="form-label">PRICE (Rp)</label><input type="number" name="price" class="form-control" required></div>
                                <div class="form-group"><label class="form-label">DURATION</label><input type="text" name="duration" class="form-control" placeholder="12 Jam / 1 Hari" required></div>
                                <div class="form-group"><label class="form-label">PHOTO</label><input type="file" name="image" class="form-control" accept="image/*"></div>
                            </div>
                            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 1.2rem; margin-bottom: 1.2rem;">
                                <div class="form-group"><label class="form-label">ACCOUNT EMAIL</label><input type="text" name="email" class="form-control" placeholder="example@gmail.com" required></div>
                                <div class="form-group"><label class="form-label">8x BACKUP CODES</label><input type="text" name="backup_codes" class="form-control" placeholder="12345678, 87654321..." required></div>
                            </div>
                            
                            <!-- Progress UI for Rental Upload -->
                            <div id="rental-progress-container" style="display:none; margin-bottom: 1.5rem;">
                                <div class="upload-status" id="rental-status-text">Uploading... 0%</div>
                                <div class="progress-wrapper" style="display:block;"><div class="progress-bar" id="rental-progress-bar"></div></div>
                                <button type="button" class="btn btn-ghost" onclick="cancelRentalUpload()" style="width:100%; color:var(--danger); border-color:var(--danger); font-size:0.75rem;">
                                    <i class="fas fa-times"></i> BATALKAN UPLOAD
                                </button>
                            </div>

                            <button type="submit" id="rental-submit-btn" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-plus"></i> ADD RENTAL</button>
                        </form>
                    </div>
                    <div class="card" style="margin-top:2rem; padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table>
                                <thead><tr><th>Info</th><th>Title</th><th>Price</th><th>Dur</th><th>Stat</th><th>Action</th></tr></thead>
                                <tbody id="admin-rentals-table"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: NEWS -->
                <div id="section-news" class="admin-section">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Post News</h3></div>
                        <form id="post-news-form">
                            <input type="hidden" name="action" value="add_news">
                            <div class="form-group"><label class="form-label">TITLE</label><input type="text" name="title" class="form-control" placeholder="Update v1.0.2..." required></div>
                            <div class="form-group"><label class="form-label">CONTENT</label><textarea name="content" class="form-control" rows="3" placeholder="Apa yang baru?" required></textarea></div>
                            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.2rem; margin-bottom: 1.2rem;">
                                <div class="form-group"><label class="form-label">TYPE</label><select name="type" class="form-control"><option value="info">Info</option><option value="update">Update</option><option value="warning">Warning</option></select></div>
                                <div class="form-group"><label class="form-label">PHOTO / BANNER</label><input type="file" name="image" id="news-img-input" class="form-control" accept="image/*" style="font-size:0.75rem;"></div>
                                <div class="form-group"><label class="form-label">ATTACHMENT (PDF/ZIP)</label><input type="file" name="attachment" id="news-file-input" class="form-control" style="font-size:0.75rem;"></div>
                            </div>
                            
                            <!-- Progress UI -->
                            <div id="upload-progress-container" style="display:none; margin-bottom: 1.5rem;">
                                <div class="upload-status" id="upload-status-text">Uploading... 0%</div>
                                <div class="progress-wrapper" style="display:block;"><div class="progress-bar" id="upload-progress-bar"></div></div>
                                <button type="button" class="btn btn-ghost" onclick="cancelUpload()" style="width:100%; color:var(--danger); border-color:var(--danger); font-size:0.75rem;">
                                    <i class="fas fa-times"></i> BATALKAN UPLOAD
                                </button>
                            </div>

                            <button type="submit" id="news-submit-btn" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-paper-plane"></i> POST NEWS</button>
                        </form>
                    </div>
                    <div class="card" style="margin-top:2rem; padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table><thead><tr><th>Poster</th><th>Title</th><th>Type</th><th>Files</th><th>Action</th></tr></thead>
                            <tbody id="admin-news-container"></tbody></table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: VOUCHERS -->
                <div id="section-vouchers" class="admin-section">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Create Voucher</h3></div>
                        <form id="add-voucher-form">
                            <input type="hidden" name="action" value="add_voucher">
                            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.2rem; margin-bottom: 1.2rem;">
                                <div class="form-group"><label class="form-label">VOUCHER CODE</label><input type="text" name="code" class="form-control" placeholder="SUMMER50" style="text-transform:uppercase" required></div>
                                <div class="form-group"><label class="form-label">DISCOUNT (%)</label><input type="number" name="discount_percent" class="form-control" placeholder="10" min="1" max="100" required></div>
                                <div class="form-group"><label class="form-label">MAX USES</label><input type="number" name="max_uses" class="form-control" placeholder="100" min="1" required></div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-plus"></i> CREATE VOUCHER</button>
                        </form>
                    </div>
                    <div class="card" style="margin-top:2rem; padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table><thead><tr><th>Code</th><th>Discount</th><th>Uses</th><th>Created</th><th>Action</th></tr></thead>
                            <tbody id="admin-voucher-container"></tbody></table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: USERS -->
                <div id="section-users" class="admin-section">
                    <div class="card" style="padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table><thead><tr><th>Username</th><th>WA</th><th>Role</th><th>Created</th><th>Action</th></tr></thead>
                            <tbody id="admin-user-container"></tbody></table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: NOTIFICATIONS -->
                <div id="section-notifications" class="admin-section">
                    <!-- Status Banner -->
                    <div id="notif-status-banner" style="display:none; border-radius:12px; padding:16px 20px; margin-bottom:1.5rem; display:flex; align-items:center; gap:12px;"></div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-bell" style="color:var(--accent);"></i> Konfigurasi Notifikasi Telegram</h3>
                        </div>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin-bottom:1.5rem; line-height:1.6;">
                            Setiap order sukses akan otomatis mengirim notifikasi ke chat Telegram kamu.
                            Masukkan <strong>Bot Token</strong> dan <strong>Chat ID Admin</strong> di bawah ini.
                            Klik <em>Test</em> untuk memverifikasi sebelum menyimpan.
                        </p>

                        <form id="notif-settings-form">
                            <div class="form-group" style="margin-bottom:1.2rem;">
                                <label class="form-label"><i class="fas fa-robot"></i> BOT TOKEN</label>
                                <div style="position:relative;">
                                    <input type="text" id="notif-bot-token" name="notif_bot_token" class="form-control"
                                           placeholder="Contoh: 8068557641:AAFtzz8RQznufSuMaZq7NTA7oDwXcNi2huc"
                                           style="padding-right: 46px; font-family: monospace; font-size:0.82rem;">
                                    <button type="button" onclick="toggleTokenVisibility()" id="toggle-token-btn"
                                            style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--text-secondary); cursor:pointer; font-size:1rem;">
                                        <i class="fas fa-eye" id="token-eye-icon"></i>
                                    </button>
                                </div>
                                <small style="color:var(--text-muted); font-size:0.75rem;">Dapatkan dari @BotFather di Telegram</small>
                            </div>

                            <div class="form-group" style="margin-bottom:1.5rem;">
                                <label class="form-label"><i class="fas fa-id-badge"></i> ADMIN CHAT ID</label>
                                <input type="text" id="notif-chat-id" name="notif_chat_id" class="form-control"
                                       placeholder="Contoh: 6201552432"
                                       style="font-family: monospace; font-size:0.95rem;">
                                <small style="color:var(--text-muted); font-size:0.75rem;">Dapatkan Chat ID kamu dari @userinfobot di Telegram</small>
                            </div>

                            </div>

                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                <button type="button" id="test-notif-btn" onclick="testNotification()"
                                        class="btn btn-ghost"
                                        style="flex:1; justify-content:center; min-width:140px; border-color:var(--accent); color:var(--accent);">
                                    <i class="fas fa-paper-plane"></i> Test Kirim
                                </button>
                                <button type="submit" class="btn btn-primary" style="flex:2; justify-content:center; min-width:180px;">
                                    <i class="fas fa-save"></i> Simpan Pengaturan
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Info Box (Only for Notifications) -->
                    <div id="notif-example-box" class="card" style="margin-top:1.5rem; border-color: rgba(0,212,170,0.2); background: rgba(0,212,170,0.04); display:none;">
                        <div class="card-header"><h3 class="card-title" style="font-size:0.85rem; color:var(--accent);"><i class="fas fa-info-circle"></i> Contoh Notifikasi yang Diterima</h3></div>
                        <pre style="color:var(--text-secondary); font-size:0.78rem; line-height:1.8; margin:0; white-space:pre-wrap;">🎉 <b>ORDER SUKSES!</b>
━━━━━━━━━━━━━━━━━
👤 <b>User:</b> budi123
🎮 <b>Game:</b> FF
⏱️ <b>Durasi:</b> 7 hari
💰 <b>Nominal:</b> Rp 80.000
🏷️ <b>Tipe:</b> 🆕 New Key
📦 <b>Delivery:</b> ✅ Otomatis
🔑 <b>Licence:</b> AB12
📋 <b>Order ID:</b> WS20260421235959123
🕐 <b>Waktu:</b> 21/04/2026 23:59:59
━━━━━━━━━━━━━━━━━</pre>
                    </div>
                </div>

                <!-- SECTION: POINTS -->
                <div id="section-points" class="admin-section">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-coins" style="color:var(--accent);"></i> Pengaturan Poin Extend</h3>
                        </div>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin-bottom:1.5rem; line-height:1.6;">
                            Atur jumlah poin reward yang didapat user saat melakukan perpanjangan (Extend) berdasarkan durasi hari.
                        </p>
                        <form id="point-settings-form" onsubmit="savePointSettings(event)">
                            <div id="point-rules-editor" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px;">
                                <!-- Will be populated by JS -->
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-save"></i> SIMPAN PENGATURAN POIN</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); document.getElementById('sidebarOverlay').classList.toggle('active'); }

        function switchAdminTab(tab) {
            document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));

            document.getElementById('section-' + tab).classList.add('active');
            
            // Highlight the sidebar nav link
            const sideLink = document.getElementById('nav-' + tab);
            if (sideLink) sideLink.classList.add('active');

            // Find and highlight the corresponding horizontal tab
            const tabMap = ['revenue', 'products', 'rentals', 'rental_tx', 'transactions', 'news', 'vouchers', 'users', 'notifications', 'points'];
            const idx = tabMap.indexOf(tab);
            if (idx !== -1) document.querySelectorAll('.admin-tab')[idx].classList.add('active');

            document.getElementById('tab-title').innerText = 'Manage ' + tab.toUpperCase();
            
            // Toggle Info Box Visibility
            const infoBox = document.getElementById('notif-example-box');
            if (infoBox) infoBox.style.display = (tab === 'notifications') ? 'block' : 'none';

            if (tab === 'revenue') loadRevenueStats();
            if (tab === 'products') loadAdminProducts();
            if (tab === 'rentals') loadAdminRentals();
            if (tab === 'rental_tx') loadAdminRentalTx();
            if (tab === 'licenses') loadAdminLicenses();
            if (tab === 'transactions') loadAdminTransactions();
            if (tab === 'news') loadAdminNews();
            if (tab === 'vouchers') loadAdminVouchers();
            if (tab === 'users') loadAdminUsers();
            if (tab === 'notifications') loadNotifSettings();
            if (tab === 'points') loadPointSettings();

            if (window.innerWidth <= 768) toggleSidebar();
}

async function loadPointSettings() {
    const resp = await fetch('api/admin.php?action=get_point_settings');
    const rules = await resp.json();
    const container = document.getElementById('point-rules-editor');
    
    // Define standard days to show if they don't exist in rules
    const standardDays = ['1', '2', '3', '4', '5', '6', '7', '8', '10', '15', '20', '30'];
    
    container.innerHTML = standardDays.map(day => `
        <div class="form-group" style="background:var(--bg-card); border:1px solid var(--border); padding:12px; border-radius:12px;">
            <label class="form-label" style="font-size:0.7rem; color:var(--accent);">${day} HARI</label>
            <div style="display:flex; align-items:center; gap:8px;">
                <input type="number" data-day="${day}" class="form-control point-rule-input" 
                       value="${rules[day] || 0}" min="0" 
                       style="font-family:monospace; font-weight:700;">
                <span style="font-size:0.7rem; color:var(--text-muted);">Poin</span>
            </div>
        </div>
    `).join('');
}

async function savePointSettings(e) {
    e.preventDefault();
    const rules = {};
    document.querySelectorAll('.point-rule-input').forEach(input => {
        rules[input.dataset.day] = parseInt(input.value) || 0;
    });

    Swal.fire({ title: 'Saving...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    
    const fd = new FormData();
    fd.append('action', 'save_point_settings');
    fd.append('rules', JSON.stringify(rules));
    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

    const resp = await fetch('api/admin.php', { method: 'POST', body: fd });
    const data = await resp.json();
    
    if (data.success) {
        Swal.fire('Success!', 'Point settings saved.', 'success');
    } else {
        Swal.fire('Error', data.message || 'Failed to save', 'error');
    }
}

        async function loadAdminRentalTx() {
            const resp = await fetch('api/store.php?action=get_all_rental_history');
            window.adminRentalTxData = await resp.json();
            renderRentalTxTable(window.adminRentalTxData);
        }

        function renderRentalTxTable(data) {
            document.getElementById('admin-rentaltx-container').innerHTML = data.map(h => `<tr><td>${h.username}</td><td>#${h.order_id}</td><td>${h.title}</td><td>${h.duration}</td><td style="color:var(--accent);">Rp ${parseInt(h.amount).toLocaleString()}</td><td><span class="badge badge-${h.status}">${h.status}</span></td></tr>`).join('');
        }

        function filterRentalTxTable() {
            const query = document.getElementById('rentaltx-search-input').value.toLowerCase();
            const filtered = window.adminRentalTxData.filter(h => h.order_id.toLowerCase().includes(query) || h.username.toLowerCase().includes(query));
            renderRentalTxTable(filtered);
        }

        async function loadAdminTransactions() {
            const resp = await fetch('api/store.php?action=get_all_history');
            window.adminTxData = await resp.json();
            renderTxTable(window.adminTxData);
        }

        function filterTxTable() {
            const query = document.getElementById('tx-search-input').value.toLowerCase();
            const filtered = window.adminTxData.filter(h => h.order_id.toLowerCase().includes(query) || h.username.toLowerCase().includes(query));
            renderTxTable(filtered);
        }

        function renderTxTable(history) {
            document.getElementById('admin-transaction-container').innerHTML = history.map(h => {
                let actionBtn = (h.tx_type === 'license' && h.status === 'completed' && h.needs_fulfillment == 1) ? 
                    `<button class="btn btn-primary" style="padding:5px 10px; font-size:0.7rem;" onclick="fulfillOrder('${h.order_id}')">FULFILL</button>` : '';
                
                let expiryInfo = h.tx_type === 'rental' ? `<span style="font-size:0.75rem; color:var(--text-muted);">${h.duration} Rental</span>` : '-';
                
                if (h.tx_type === 'license' && h.status === 'completed' && h.licence && h.licence !== 'PENDING_ADMIN') {
                   const createdMs  = new Date(h.created_at).getTime();
                   const expiryDate = new Date(createdMs + (h.duration * 24 * 60 * 60 * 1000));
                   const now        = new Date();
                   const isExpired  = expiryDate < now;
                   
                   const expiryStr = expiryDate.toLocaleString('id-ID', {
                       day: '2-digit', month: 'short', year: 'numeric',
                       hour: '2-digit', minute: '2-digit'
                   });
                   
                   expiryInfo = `<div style="font-size:0.75rem; color:${isExpired ? 'var(--danger)' : 'white'}; font-weight:600;">${expiryStr}</div>`;
                   expiryInfo += isExpired 
                       ? `<span class="badge badge-expired" style="font-size:0.6rem; padding:2px 5px;">EXPIRED</span>`
                       : `<span class="badge badge-success" style="font-size:0.6rem; padding:2px 5px;">ACTIVE</span>`;
                }

                const typeBadge = h.tx_type === 'rental' ? 
                    `<span class="badge" style="background:var(--accent); color:#000; font-size:0.6rem; margin-right:5px;">RENTAL</span>` : 
                    `<span class="badge" style="background:#6366f1; color:#fff; font-size:0.6rem; margin-right:5px;">LICENSE</span>`;

                return `<tr>
                    <td style="font-weight:700;">${h.username}</td>
                    <td style="font-size:0.7rem;">#${h.order_id}</td>
                    <td>
                        ${typeBadge}
                        <span class="badge ${h.game_type === 'ff' ? 'badge-success' : (h.game_type === 'RENTAL' ? 'badge-expired' : 'badge-pending')}" style="font-size:0.65rem;">
                            ${h.game_type.toUpperCase()}
                        </span>
                    </td>
                    <td><span class="badge badge-${h.status}">${h.status}</span></td>
                    <td title="${h.licence}" style="font-size:0.7rem; max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        ${h.licence || '-'}
                    </td>
                    <td>${expiryInfo}</td>
                    <td>${actionBtn}</td>
                </tr>`;
            }).join('');
        }

        async function fulfillOrder(orderId) {
            const { value: key } = await Swal.fire({ title: 'Enter License Key', input: 'text', background: '#1a1a26', color: '#fff', showCancelButton: true });
            if (key) {
                const fd = new FormData(); 
                fd.append('action', 'fulfill_order'); 
                fd.append('order_id', orderId); 
                fd.append('licence', key);
                fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
                const resp = await fetch('api/admin.php', { method: 'POST', body: fd });
                const data = await resp.json();
                if (data.success) { Swal.fire('Success!', 'Key delivered.', 'success'); loadAdminTransactions(); }
            }
        }

        async function loadAdminProducts() {
            const resp = await fetch('api/store.php?action=get_products');
            const products = await resp.json();
            document.getElementById('admin-product-container').innerHTML = products.map(p => `<tr><td>${p.game_type.toUpperCase()}</td><td>${p.name}</td><td><span class="badge ${p.delivery_type==='auto'?'badge-success':'badge-pending'}">${p.delivery_type}</span></td><td style="color:var(--accent);">Rp ${new Intl.NumberFormat('id-ID').format(p.price)}</td><td><button class="btn btn-ghost" onclick="deleteItem('delete_product', ${p.id}, loadAdminProducts)" style="color:var(--danger);"><i class="fas fa-trash"></i></button></td></tr>`).join('');
        }

        async function loadAdminLicenses() {
            const resp = await fetch('api/store.php?action=get_active_licenses');
            const data = await resp.json();
            document.getElementById('admin-license-container').innerHTML = data.map(l => {
                const createdMs  = new Date(l.created_at).getTime();
                const expiryMs   = createdMs + (l.duration * 24 * 60 * 60 * 1000);
                const expiryDate = new Date(expiryMs);
                const now        = new Date();
                const isExpired  = expiryDate < now;

                const statusBadge = isExpired
                    ? `<span class="badge badge-expired" style="font-size:0.6rem;">EXPIRED</span>`
                    : `<span class="badge badge-success" style="font-size:0.6rem;">ACTIVE</span>`;

                return `<tr>
                    <td style="font-weight:700;">${l.username}</td>
                    <td><span class="badge ${l.game_type === 'ff' ? 'badge-success' : 'badge-pending'}" style="font-size:0.65rem;">${(l.game_type || 'FF').toUpperCase()}</span></td>
                    <td><code style="color:var(--accent); font-size:0.75rem;">${l.licence}</code></td>
                    <td>${statusBadge}</td>
                    <td style="font-size:0.75rem;">${new Date(l.created_at).toLocaleDateString('id-ID')}</td>
                    <td style="font-size:0.75rem; color:${isExpired ? 'var(--danger)' : 'var(--warning)'}; font-weight:600;">
                        ${expiryDate.toLocaleDateString('id-ID')}
                    </td>
                </tr>`;
            }).join('');
        }

        async function loadAdminRentals() {
            const resp = await fetch('api/store.php?action=get_rentals');
            const data = await resp.json();
            const table = document.getElementById('admin-rentals-table');
            table.innerHTML = data.map(r => {
                const img = r.image_url ? `<img src="${r.image_url}" style="width:40px; height:40px; border-radius:6px; object-fit:cover;">` : '<i class="fas fa-user-circle fa-2x"></i>';
                return `<tr>
                    <td>${img}</td>
                    <td>${r.title}</td>
                    <td>Rp ${parseInt(r.price).toLocaleString()}</td>
                    <td>${r.duration}</td>
                    <td><span class="badge ${r.status === 'available' ? 'type-update' : 'type-warning'}">${r.status.toUpperCase()}</span></td>
                    <td>
                        <button class="btn btn-ghost" onclick="deleteItem('delete_rental', ${r.id}, loadAdminRentals)" style="color:var(--danger); padding:5px 10px;"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
            }).join('');
        }

        async function loadAdminNews() {
            const resp = await fetch('api/store.php?action=get_news');
            const news = await resp.json();
            document.getElementById('admin-news-container').innerHTML = news.map(n => {
                const img = n.image_url ? `<img src="${n.image_url}" style="width:40px; height:40px; border-radius:6px; object-fit:cover;">` : '<div style="width:40px; height:40px; background:var(--bg-lighter); border-radius:6px; display:flex; align-items:center; justify-content:center; color:var(--text-muted);"><i class="fas fa-image"></i></div>';
                const files = [];
                if (n.image_url) files.push('<i class="fas fa-image" title="Has Image"></i>');
                if (n.file_url) files.push('<a href="download.php?id=' + n.id + '" style="color:var(--accent);"><i class="fas fa-paperclip" title="' + (n.file_name || 'Download File') + '"></i></a>');
                
                return `<tr>
                    <td>${img}</td>
                    <td><div style="font-weight:600; font-size:0.85rem;">${n.title}</div><div style="font-size:0.7rem; color:var(--text-muted);">${new Date(n.created_at).toLocaleDateString()}</div></td>
                    <td><span class="badge type-${n.type}">${n.type.toUpperCase()}</span></td>
                    <td><div style="display:flex; gap:8px;">${files.join('') || '-'}</div></td>
                    <td><button class="btn btn-ghost" onclick="deleteItem('delete_news', ${n.id}, loadAdminNews)" style="color:var(--danger);"><i class="fas fa-trash"></i></button></td>
                </tr>`;
            }).join('');
        }

        async function loadAdminUsers() {
            const resp = await fetch('api/store.php?action=get_users');
            const users = await resp.json();
            document.getElementById('admin-user-container').innerHTML = users.map(u => `<tr><td>${u.username}</td><td>${u.wa_number}</td><td><span class="badge badge-success">${u.role}</span></td><td>${new Date(u.created_at).toLocaleDateString()}</td><td><div style="display:flex; gap:5px;"><button class="btn btn-ghost" onclick="changePassword(${u.id}, '${u.username}')" style="color:var(--accent); padding:5px;"><i class="fas fa-key"></i></button><button class="btn btn-ghost" onclick="deleteItem('delete_user', ${u.id}, loadAdminUsers)" style="color:var(--danger);" ${u.role === 'admin' ? 'disabled' : ''}><i class="fas fa-user-minus"></i></button></div></td></tr>`).join('');
        }

        async function changePassword(userId, username) {
            const { value: password } = await Swal.fire({
                title: 'Ubah Sandi: ' + username,
                input: 'password',
                inputLabel: 'Masukkan sandi baru',
                inputPlaceholder: 'Minimal 5 karakter',
                showCancelButton: true,
                background:'#1a1a26', color:'#fff',
                inputValidator: (value) => { if (!value || value.length < 5) return 'Sandi terlalu pendek!'; }
            });

            if (password) {
                const fd = new FormData();
                fd.append('action', 'update_user_password');
                fd.append('user_id', userId);
                fd.append('new_password', password);
                fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
                const resp = await fetch('api/admin.php', { method: 'POST', body: fd });
                const data = await resp.json();
                if (data.success) Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Sandi '+username+' diperbarui.', background:'#1a1a26', color:'#fff' });
                else Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message, background:'#1a1a26', color:'#fff' });
            }
        }

        async function handleAdminForm(e, callback) {
            e.preventDefault(); 
            const formData = new FormData(e.target);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            const resp = await fetch('api/admin.php', { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.success) { Swal.fire({ icon: 'success', title: 'Success!', background:'#1a1a26', color:'#fff' }); e.target.reset(); callback(); }
            else { Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message, background:'#1a1a26', color:'#fff' }); }
        }

        let currentUploadXHR = null;

        async function handleNewsPost(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            const submitBtn = document.getElementById('news-submit-btn');
            const progressContainer = document.getElementById('upload-progress-container');
            const progressBar = document.getElementById('upload-progress-bar');
            const statusText = document.getElementById('upload-status-text');

            // Reset & Show UI
            submitBtn.style.display = 'none';
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            statusText.innerText = 'Menyiapkan berkas... 0%';

            // Use XHR for progress event
            currentUploadXHR = new XMLHttpRequest();
            
            currentUploadXHR.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    statusText.innerText = `Mengunggah... ${percent}% (${(e.loaded/1024/1024).toFixed(2)} MB / ${(e.total/1024/1024).toFixed(2)} MB)`;
                }
            });

            currentUploadXHR.onload = function() {
                resetNewsUI();
                if (currentUploadXHR.status === 200) {
                    try {
                        const data = JSON.parse(currentUploadXHR.responseText);
                        if (data.success) {
                            Swal.fire({ icon: 'success', title: 'Berhasil Terposting!', background:'#1a1a26', color:'#fff' });
                            form.reset();
                            loadAdminNews();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message, background:'#1a1a26', color:'#fff' });
                        }
                    } catch (e) {
                        console.error('Raw Response:', currentUploadXHR.responseText);
                        Swal.fire({ 
                            icon: 'warning', 
                            title: 'Respon Server Tidak Valid', 
                            text: 'File mungkin terlalu besar untuk limit server (PHP Limit). Coba file yang lebih kecil atau hubungi penyedia hosting.', 
                            background:'#1a1a26', color:'#fff' 
                        });
                    }
                } else if (currentUploadXHR.status === 413) {
                    Swal.fire({ icon: 'error', title: 'File Terlalu Besar!', text: 'Server menolak upload karena ukuran file melebihi batas (Payload Too Large).', background:'#1a1a26', color:'#fff' });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error Server!', text: 'Status: ' + currentUploadXHR.status, background:'#1a1a26', color:'#fff' });
                }
            };

            currentUploadXHR.onerror = function() {
                Swal.fire({ icon: 'error', title: 'Error Jaringan!', text: 'Gagal menghubungi server.', background:'#1a1a26', color:'#fff' });
                resetNewsUI();
            };

            currentUploadXHR.onabort = function() {
                Swal.fire({ icon: 'info', title: 'Dibatalkan', text: 'Proses upload telah dihentikan.', background:'#1a1a26', color:'#fff' });
                resetNewsUI();
            };

            currentUploadXHR.open('POST', 'api/admin.php');
            currentUploadXHR.send(formData);
        }

        function cancelUpload() {
            if (currentUploadXHR) currentUploadXHR.abort();
        }

        function resetNewsUI() {
            document.getElementById('news-submit-btn').style.display = 'flex';
            document.getElementById('upload-progress-container').style.display = 'none';
        }

        let currentRentalXHR = null;

        async function handleRentalPost(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            const submitBtn = document.getElementById('rental-submit-btn');
            const progressContainer = document.getElementById('rental-progress-container');
            const progressBar = document.getElementById('rental-progress-bar');
            const statusText = document.getElementById('rental-status-text');

            submitBtn.style.display = 'none';
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            statusText.innerText = 'Menyiapkan berkas... 0%';

            currentRentalXHR = new XMLHttpRequest();
            
            currentRentalXHR.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    statusText.innerText = `Mengunggah... ${percent}% (${(e.loaded/1024/1024).toFixed(2)} MB / ${(e.total/1024/1024).toFixed(2)} MB)`;
                }
            });

            currentRentalXHR.onload = function() {
                resetRentalUI();
                if (currentRentalXHR.status === 200) {
                    try {
                        const data = JSON.parse(currentRentalXHR.responseText);
                        if (data.success) {
                            Swal.fire({ icon: 'success', title: 'Berhasil Ditambahkan!', background:'#1a1a26', color:'#fff' });
                            form.reset();
                            loadAdminRentals();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message, background:'#1a1a26', color:'#fff' });
                        }
                    } catch (e) {
                        Swal.fire({ icon: 'warning', title: 'Respon Server Tidak Valid', text: 'File mungkin terlalu besar untuk limit server.', background:'#1a1a26', color:'#fff' });
                    }
                } else if (currentRentalXHR.status === 413) {
                    Swal.fire({ icon: 'error', title: 'File Terlalu Besar!', text: 'Server menolak upload karena ukuran file melebihi batas.', background:'#1a1a26', color:'#fff' });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error Server!', text: 'Status: ' + currentRentalXHR.status, background:'#1a1a26', color:'#fff' });
                }
            };

            currentRentalXHR.onerror = function() {
                Swal.fire({ icon: 'error', title: 'Error Jaringan!', text: 'Gagal menghubungi server.', background:'#1a1a26', color:'#fff' });
                resetRentalUI();
            };

            currentRentalXHR.onabort = function() {
                Swal.fire({ icon: 'info', title: 'Dibatalkan', text: 'Proses upload telah dihentikan.', background:'#1a1a26', color:'#fff' });
                resetRentalUI();
            };

            currentRentalXHR.open('POST', 'api/admin.php');
            currentRentalXHR.send(formData);
        }

        function cancelRentalUpload() {
            if (currentRentalXHR) currentRentalXHR.abort();
        }

        function resetRentalUI() {
            document.getElementById('rental-submit-btn').style.display = 'flex';
            document.getElementById('rental-progress-container').style.display = 'none';
        }

        document.getElementById('add-product-form').addEventListener('submit', (e) => handleAdminForm(e, loadAdminProducts));
        document.getElementById('add-rental-form').addEventListener('submit', handleRentalPost);
        document.getElementById('post-news-form').addEventListener('submit', handleNewsPost);
        document.getElementById('add-voucher-form').addEventListener('submit', (e) => handleAdminForm(e, loadAdminVouchers));

        async function loadAdminVouchers() {
            const resp = await fetch('api/admin.php?action=get_vouchers');
            const data = await resp.json();
            document.getElementById('admin-voucher-container').innerHTML = data.map(v => {
                const isFull = parseInt(v.current_uses) >= parseInt(v.max_uses);
                const badgeClass = isFull ? 'badge-expired' : 'badge-success';
                return `<tr>
                    <td><code style="color:var(--accent); font-weight:700;">${v.code}</code></td>
                    <td><span class="badge" style="background:#6366f1;">${v.discount_percent}% OFF</span></td>
                    <td><span class="badge ${badgeClass}">${v.current_uses} / ${v.max_uses}</span></td>
                    <td style="font-size:0.75rem; color:var(--text-muted);">${new Date(v.created_at).toLocaleDateString('id-ID')}</td>
                    <td><button class="btn btn-ghost" onclick="deleteItem('delete_voucher', ${v.id}, loadAdminVouchers)" style="color:var(--danger);"><i class="fas fa-trash"></i></button></td>
                </tr>`;
            }).join('');
        }

        async function deleteItem(action, id, callback) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff4757',
                cancelButtonColor: '#2b2b36',
                confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#1a1a26',
                color: '#fff'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const fd = new FormData(); 
                    fd.append('action', action); 
                    fd.append('id', id);
                    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
                    await fetch('api/admin.php', { method: 'POST', body: fd }); 
                    callback();
                    Swal.fire({
                        title: 'Terhapus!',
                        text: 'Data berhasil dihapus.',
                        icon: 'success',
                        background: '#1a1a26',
                        color: '#fff',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        }

        // ===== NOTIFICATIONS TAB =====
        async function loadNotifSettings() {
            const resp = await fetch('api/admin.php?action=get_notif_settings');
            const data = await resp.json();
            if (!data.success) return;

            document.getElementById('notif-bot-token').value = data.notif_bot_token || '';
            document.getElementById('notif-chat-id').value   = data.notif_chat_id   || '';

            const banner = document.getElementById('notif-status-banner');
            if (data.notif_bot_token && data.notif_chat_id) {
                banner.style.display = 'flex';
                banner.style.background = 'rgba(0,212,170,0.08)';
                banner.style.border = '1px solid rgba(0,212,170,0.3)';
                banner.innerHTML = '<i class="fas fa-check-circle" style="color:var(--accent); font-size:1.3rem;"></i>'
                    + '<div><div style="color:var(--accent); font-weight:700; font-size:0.9rem;">Notifikasi Aktif ✅</div>'
                    + '<div style="color:var(--text-secondary); font-size:0.8rem;">Chat ID: <code>' + data.notif_chat_id + '</code> sudah dikonfigurasi.</div></div>';
            } else {
                banner.style.display = 'flex';
                banner.style.background = 'rgba(255,71,87,0.08)';
                banner.style.border = '1px solid rgba(255,71,87,0.3)';
                banner.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:var(--danger); font-size:1.3rem;"></i>'
                    + '<div><div style="color:var(--danger); font-weight:700; font-size:0.9rem;">Notifikasi Belum Dikonfigurasi ⚠️</div>'
                    + '<div style="color:var(--text-secondary); font-size:0.8rem;">Isi Token & Chat ID lalu simpan agar notifikasi aktif.</div></div>';
            }
        }

        document.getElementById('notif-settings-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'save_notif_settings');
            fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            const resp = await fetch('api/admin.php', { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Tersimpan!', text: data.message, background: '#1a1a26', color: '#fff' });
                loadNotifSettings();
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message, background: '#1a1a26', color: '#fff' });
            }
        });

        // ===== REVENUE & DASHBOARD =====
        let revChart = null;
        let gameChart = null;

        function setDefaultDates() {
            const end = new Date();
            const start = new Date();
            start.setDate(start.getDate() - 30);
            
            document.getElementById('rev-start-date').value = start.toISOString().split('T')[0];
            document.getElementById('rev-end-date').value = end.toISOString().split('T')[0];
        }

        async function loadRevenueStats() {
            const start = document.getElementById('rev-start-date').value;
            const end = document.getElementById('rev-end-date').value;
            
            const resp = await fetch(`api/admin.php?action=get_revenue_stats&start_date=${start}&end_date=${end}`);
            const data = await resp.json();
            
            if (!data.success) return;

            // Summary
            document.getElementById('rev-total-revenue').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(data.summary.total_revenue);
            document.getElementById('rev-total-sales').innerText = data.summary.total_sales;

            // Daily Revenue Chart
            const ctx = document.getElementById('revenueChart').getContext('2d');
            if (revChart) revChart.destroy();
            
            revChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.daily.map(d => d.date),
                    datasets: [{
                        label: 'Revenue',
                        data: data.daily.map(d => d.daily_revenue),
                        borderColor: '#00d4aa',
                        backgroundColor: 'rgba(0, 212, 170, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#00d4aa'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { font: { size: 10 }, color:'#888' }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { font: { size: 10 }, color:'#888' }
                        }
                    }
                }
            });

            // Game Pie Chart
            const gCtx = document.getElementById('gameChart').getContext('2d');
            if (gameChart) gameChart.destroy();
            
            gameChart = new Chart(gCtx, {
                type: 'doughnut',
                data: {
                    labels: data.games.map(g => g.game_type.toUpperCase()),
                    datasets: [{
                        data: data.games.map(g => g.revenue),
                        backgroundColor: ['#00d4aa', '#ffaa00', '#6366f1', '#ff4757'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#fff', font: { size: 11 } } }
                    },
                    cutout: '70%'
                }
            });
        }

        setDefaultDates();

        async function testNotification() {
            const token  = document.getElementById('notif-bot-token').value.trim();
            const chatId = document.getElementById('notif-chat-id').value.trim();
            if (!token || !chatId) {
                Swal.fire({ icon: 'warning', title: 'Isi Dulu!', text: 'Masukkan Bot Token dan Chat ID sebelum test.', background: '#1a1a26', color: '#fff' });
                return;
            }
            const btn = document.getElementById('test-notif-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';

            const fd = new FormData();
            fd.append('action', 'test_notif');
            fd.append('notif_bot_token', token);
            fd.append('notif_chat_id', chatId);
            fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            const resp = await fetch('api/admin.php', { method: 'POST', body: fd });
            const data = await resp.json();

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Test Kirim';

            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Berhasil! 🎉', text: data.message, background: '#1a1a26', color: '#fff' });
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message, background: '#1a1a26', color: '#fff' });
            }
        }

        function toggleTokenVisibility() {
            const input = document.getElementById('notif-bot-token');
            const icon  = document.getElementById('token-eye-icon');
            if (input.type === 'text') {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            } else {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            }
        }
        // ===== END NOTIFICATIONS TAB =====

        loadAdminProducts();
    </script>
</body>
</html>
