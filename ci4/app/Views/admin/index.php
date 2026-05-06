<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= esc($title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/style.css?v=999">
    <meta name="csrf-token" content="<?= esc($csrf_token) ?>">
    <style>
        .admin-tabs { display: flex; gap: 5px; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); overflow-x: auto; padding-bottom: 5px; scrollbar-width: none; }
        .admin-tabs::-webkit-scrollbar { display: none; }
        .admin-tab { padding: 10px 18px; cursor: pointer; color: var(--text-secondary); font-weight: 700; font-size: 0.75rem; border-bottom: 2px solid transparent; transition: 0.3s; white-space: nowrap; border-radius: 8px 8px 0 0; }
        .admin-tab.active { color: var(--accent); border-bottom-color: var(--accent); background: var(--accent-glow); }
        .admin-section { display: none; }
        .admin-section.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 1024px) { .grid-form { grid-template-columns: 1fr !important; gap: 1rem !important; } }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="app-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon" style="background: var(--warning); color: black;"><i class="fas fa-user-shield"></i></div>
                <h1 class="brand-name">ADMIN<span>HUB</span></h1>
            </div>
            <nav class="sidebar-nav">
                <div class="menu-header">Quick Access</div>
                <ul>
                    <li><a href="<?= base_url('ci4/public/dashboard') ?>" class="nav-link"><i class="fas fa-home"></i> <span>Dashboard News</span></a></li>
                    <li><a href="<?= base_url('ci4/public/store') ?>" class="nav-link"><i class="fas fa-shopping-cart"></i> <span>License Store</span></a></li>
                </ul>
                <div class="menu-header">Control Center</div>
                <ul>
                    <li><a id="nav-revenue" href="#" class="nav-link active" onclick="switchAdminTab('revenue')"><i class="fas fa-chart-line"></i> <span>Revenue Stats</span></a></li>
                    <li><a id="nav-products" href="#" class="nav-link" onclick="switchAdminTab('products')"><i class="fas fa-box"></i> <span>Products</span></a></li>
                    <li><a id="nav-rentals" href="#" class="nav-link" onclick="switchAdminTab('rentals')"><i class="fas fa-key"></i> <span>Rentals</span></a></li>
                    <li><a id="nav-transactions" href="#" class="nav-link" onclick="switchAdminTab('transactions')"><i class="fas fa-receipt"></i> <span>Transactions</span></a></li>
                    <li><a id="nav-news" href="#" class="nav-link" onclick="switchAdminTab('news')"><i class="fas fa-bullhorn"></i> <span>News</span></a></li>
                    <li><a id="nav-vouchers" href="#" class="nav-link" onclick="switchAdminTab('vouchers')"><i class="fas fa-ticket-alt"></i> <span>Vouchers</span></a></li>
                    <li><a id="nav-users" href="#" class="nav-link" onclick="switchAdminTab('users')"><i class="fas fa-users"></i> <span>Users</span></a></li>
                    <li><a id="nav-notifications" href="#" class="nav-link" onclick="switchAdminTab('notifications')"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
                    <li><a id="nav-points" href="#" class="nav-link" onclick="switchAdminTab('points')"><i class="fas fa-coins"></i> <span>Points</span></a></li>
                </ul>
                <div class="menu-header">Account</div>
                <ul>
                    <li><a href="<?= base_url('ci4/public/auth/profile') ?>" class="nav-link"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
                    <li><a href="<?= base_url('ci4/public/auth/logout') ?>" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Sign Out</span></a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="mobile-header">
                <div class="sidebar-brand" style="padding: 0;">
                    <div class="brand-icon" style="width:32px; height:32px; background:var(--warning); color:black;"><i class="fas fa-user-shield"></i></div>
                    <h1 class="brand-name" style="font-size:1rem;">ADMIN<span>HUB</span></h1>
                </div>
                <button class="btn btn-ghost" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            </header>

            <header class="topbar">
                <h2 class="page-title" id="tab-title">Revenue Stats</h2>
                <div class="topbar-user">
                    <div class="user-avatar" style="color:var(--warning);"><i class="fas fa-shield-alt"></i></div>
                </div>
            </header>

            <div class="page-content">
                <div class="admin-tabs">
                    <div class="admin-tab active" onclick="switchAdminTab('revenue')">REVENUE</div>
                    <div class="admin-tab" onclick="switchAdminTab('products')">PRODUCTS</div>
                    <div class="admin-tab" onclick="switchAdminTab('rentals')">RENTALS</div>
                    <div class="admin-tab" onclick="switchAdminTab('transactions')">TRANSACTIONS</div>
                    <div class="admin-tab" onclick="switchAdminTab('news')">NEWS</div>
                    <div class="admin-tab" onclick="switchAdminTab('vouchers')">VOUCHERS</div>
                    <div class="admin-tab" onclick="switchAdminTab('users')">USERS</div>
                    <div class="admin-tab" onclick="switchAdminTab('notifications')">NOTIF</div>
                    <div class="admin-tab" onclick="switchAdminTab('points')">POINTS</div>
                </div>

                <!-- REVENUE SECTION -->
                <div id="section-revenue" class="admin-section active">
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <h3 class="card-title"><i class="fas fa-filter"></i> Filter Range</h3>
                        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-top:15px;">
                            <input type="date" id="rev-start-date" class="form-control" style="flex:1; min-width:130px;">
                            <span style="color:var(--text-muted);">to</span>
                            <input type="date" id="rev-end-date" class="form-control" style="flex:1; min-width:130px;">
                            <button class="btn btn-primary" onclick="loadRevenueStats()" style="width:100%;"><i class="fas fa-sync"></i> Apply</button>
                        </div>
                    </div>
                    <div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
                        <div class="stat-card">
                            <span class="stat-label">Total Revenue</span>
                            <div class="stat-value" id="rev-total-revenue">Rp 0</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-label">Total Sales</span>
                            <div class="stat-value" id="rev-total-sales">0</div>
                        </div>
                    </div>
                    <div class="card">
                        <h3 class="card-title">Daily Revenue</h3>
                        <div style="height: 280px;"><canvas id="revenueChart"></canvas></div>
                    </div>
                </div>

                <!-- PRODUCTS SECTION -->
                <div id="section-products" class="admin-section">
                    <div class="card">
                        <h3 class="card-title">Add Product</h3>
                        <form id="add-product-form">
                            <?= csrf_field() ?>
                            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group"><label class="form-label">GAME</label><select name="game_type" class="form-control"><option value="ff">Free Fire</option><option value="ffmax">FF Max</option></select></div>
                                <div class="form-group"><label class="form-label">DELIVERY</label><select name="delivery_type" class="form-control"><option value="auto">Automatic</option><option value="manual">Manual</option></select></div>
                            </div>
                            <div class="form-group"><label class="form-label">NAME</label><input type="text" name="name" class="form-control" required></div>
                            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group"><label class="form-label">DAYS</label><input type="number" name="duration" class="form-control" required></div>
                                <div class="form-group"><label class="form-label">PRICE</label><input type="number" name="price" class="form-control" required></div>
                                <div class="form-group"><label class="form-label">POINTS</label><input type="number" name="points_reward" class="form-control" required></div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">CREATE PRODUCT</button>
                        </form>
                    </div>
                    <div class="card" style="margin-top:1.5rem; padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table><thead><tr><th>Game</th><th>Name</th><th>Delivery</th><th>Price</th><th>Action</th></tr></thead>
                            <tbody id="admin-product-container"></tbody></table>
                        </div>
                    </div>
                </div>

                <!-- RENTALS SECTION -->
                <div id="section-rentals" class="admin-section">
                    <div class="card">
                        <h3 class="card-title">Add Rental Account</h3>
                        <form id="add-rental-form">
                            <?= csrf_field() ?>
                            <div class="form-group"><label class="form-label">TITLE</label><input type="text" name="title" class="form-control" required></div>
                            <div class="form-group"><label class="form-label">DESCRIPTION</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group"><label class="form-label">PRICE (Rp)</label><input type="number" name="price" class="form-control" required></div>
                                <div class="form-group"><label class="form-label">DURATION</label><input type="text" name="duration" class="form-control" placeholder="12 Jam / 1 Hari" required></div>
                                <div class="form-group"><label class="form-label">PHOTO</label><input type="file" name="image" class="form-control" accept="image/*"></div>
                            </div>
                            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group"><label class="form-label">EMAIL</label><input type="text" name="email" class="form-control" required></div>
                                <div class="form-group"><label class="form-label">BACKUP CODES</label><input type="text" name="backup_codes" class="form-control" required></div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-plus"></i> ADD RENTAL</button>
                        </form>
                    </div>
                    <div class="card" style="margin-top:1.5rem; padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table><thead><tr><th>Title</th><th>Price</th><th>Duration</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody id="admin-rentals-table"></tbody></table>
                        </div>
                    </div>
                </div>

                <!-- TRANSACTIONS SECTION -->
                <div id="section-transactions" class="admin-section">
                    <div style="margin-bottom: 12px;">
                        <input type="text" id="tx-search-input" placeholder="Search Order ID..." class="form-control" onkeyup="filterTxTable()">
                    </div>
                    <div class="card" style="padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table><thead><tr><th>User</th><th>Order ID</th><th>Game</th><th>Status</th><th>License</th><th>Action</th></tr></thead>
                            <tbody id="admin-transaction-container"></tbody></table>
                        </div>
                    </div>
                </div>

                <!-- NEWS SECTION -->
                <div id="section-news" class="admin-section">
                    <div class="card">
                        <h3 class="card-title">Post News</h3>
                        <form id="post-news-form">
                            <?= csrf_field() ?>
                            <div class="form-group"><label class="form-label">TITLE</label><input type="text" name="title" class="form-control" required></div>
                            <div class="form-group"><label class="form-label">CONTENT</label><textarea name="content" class="form-control" rows="3" required></textarea></div>
                            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group"><label class="form-label">TYPE</label><select name="type" class="form-control"><option value="info">Info</option><option value="update">Update</option><option value="warning">Warning</option></select></div>
                                <div class="form-group"><label class="form-label">IMAGE</label><input type="file" name="image" class="form-control" accept="image/*"></div>
                                <div class="form-group"><label class="form-label">ATTACHMENT</label><input type="file" name="attachment" class="form-control"></div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-paper-plane"></i> POST NEWS</button>
                        </form>
                    </div>
                    <div class="card" style="margin-top:1.5rem; padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table><thead><tr><th>Title</th><th>Type</th><th>Created</th><th>Action</th></tr></thead>
                            <tbody id="admin-news-container"></tbody></table>
                        </div>
                    </div>
                </div>

                <!-- VOUCHERS SECTION -->
                <div id="section-vouchers" class="admin-section">
                    <div class="card">
                        <h3 class="card-title">Create Voucher</h3>
                        <form id="add-voucher-form">
                            <?= csrf_field() ?>
                            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group"><label class="form-label">CODE</label><input type="text" name="code" class="form-control" style="text-transform:uppercase" required></div>
                                <div class="form-group"><label class="form-label">DISCOUNT (%)</label><input type="number" name="discount_percent" class="form-control" min="1" max="100" required></div>
                                <div class="form-group"><label class="form-label">MAX USES</label><input type="number" name="max_uses" class="form-control" min="1" required></div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-plus"></i> CREATE VOUCHER</button>
                        </form>
                    </div>
                    <div class="card" style="margin-top:1.5rem; padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table><thead><tr><th>Code</th><th>Discount</th><th>Uses</th><th>Created</th><th>Action</th></tr></thead>
                            <tbody id="admin-voucher-container"></tbody></table>
                        </div>
                    </div>
                </div>

                <!-- USERS SECTION -->
                <div id="section-users" class="admin-section">
                    <div class="card" style="padding:0; overflow:hidden;">
                        <div class="responsive-table-wrapper">
                            <table><thead><tr><th>Username</th><th>WA</th><th>Role</th><th>Points</th><th>Created</th><th>Action</th></tr></thead>
                            <tbody id="admin-user-container"></tbody></table>
                        </div>
                    </div>
                </div>

                <!-- NOTIFICATIONS SECTION -->
                <div id="section-notifications" class="admin-section">
                    <div class="card">
                        <h3 class="card-title"><i class="fas fa-bell" style="color:var(--accent);"></i> Telegram Notification Settings</h3>
                        <form id="notif-settings-form">
                            <?= csrf_field() ?>
                            <div class="form-group" style="margin-bottom:1rem;">
                                <label class="form-label">BOT TOKEN</label>
                                <input type="text" id="notif-bot-token" name="notif_bot_token" class="form-control" placeholder="Bot Token from @BotFather">
                            </div>
                            <div class="form-group" style="margin-bottom:1rem;">
                                <label class="form-label">CHAT ID</label>
                                <input type="text" id="notif-chat-id" name="notif_chat_id" class="form-control" placeholder="Your Chat ID">
                            </div>
                            <div style="display:flex; gap:12px;">
                                <button type="button" onclick="testNotification()" class="btn btn-ghost" style="flex:1; border-color:var(--accent); color:var(--accent);"><i class="fas fa-paper-plane"></i> Test</button>
                                <button type="submit" class="btn btn-primary" style="flex:2;"><i class="fas fa-save"></i> Save Settings</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- POINTS SECTION -->
                <div id="section-points" class="admin-section">
                    <div class="card">
                        <h3 class="card-title"><i class="fas fa-coins" style="color:var(--accent);"></i> Point Settings</h3>
                        <form id="point-settings-form">
                            <?= csrf_field() ?>
                            <div id="point-rules-editor" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px;"></div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-save"></i> SAVE POINT SETTINGS</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const BASE_URL = '/ci4/public';
        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, background: '#1a1a26', color: '#fff' });
        let revenueChart = null;
        window.adminTxData = [];

        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); document.getElementById('sidebarOverlay').classList.toggle('active'); }

        function switchAdminTab(tab) {
            document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));

            document.getElementById('section-' + tab).classList.add('active');
            const sideLink = document.getElementById('nav-' + tab);
            if (sideLink) sideLink.classList.add('active');

            const tabMap = ['revenue', 'products', 'rentals', 'transactions', 'news', 'vouchers', 'users', 'notifications', 'points'];
            const idx = tabMap.indexOf(tab);
            if (idx !== -1) document.querySelectorAll('.admin-tab')[idx].classList.add('active');

            document.getElementById('tab-title').innerText = 'Manage ' + tab.toUpperCase();
            
            if (tab === 'revenue') loadRevenueStats();
            if (tab === 'products') loadAdminProducts();
            if (tab === 'rentals') loadAdminRentals();
            if (tab === 'transactions') loadAdminTransactions();
            if (tab === 'news') loadAdminNews();
            if (tab === 'vouchers') loadAdminVouchers();
            if (tab === 'users') loadAdminUsers();
            if (tab === 'notifications') loadNotifSettings();
            if (tab === 'points') loadPointSettings();

            if (window.innerWidth <= 768) toggleSidebar();
        }

        // Revenue
        async function loadRevenueStats() {
            const start = document.getElementById('rev-start-date').value || '';
            const end = document.getElementById('rev-end-date').value || '';
            const resp = await fetch(`${BASE_URL}/admin/revenue-stats?start_date=${start}&end_date=${end}`);
            const data = await resp.json();
            
            document.getElementById('rev-total-revenue').innerText = 'Rp ' + (data.summary?.total_revenue || 0).toLocaleString('id-ID');
            document.getElementById('rev-total-sales').innerText = data.summary?.total_sales || 0;
            
            const ctx = document.getElementById('revenueChart').getContext('2d');
            if (revenueChart) revenueChart.destroy();
            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.daily?.map(d => d.date) || [],
                    datasets: [{ label: 'Revenue', data: data.daily?.map(d => d.daily_revenue) || [], borderColor: '#00d4aa', tension: 0.3, fill: true, backgroundColor: 'rgba(0,212,170,0.1)' }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
        }

        // Products
        async function loadAdminProducts() {
            const resp = await fetch(`${BASE_URL}/admin/products`);
            const products = await resp.json();
            document.getElementById('admin-product-container').innerHTML = products.map(p => `
                <tr>
                    <td><span class="badge badge-${p.game_type === 'ff' ? 'success' : 'pending'}">${p.game_type.toUpperCase()}</span></td>
                    <td>${p.name}</td>
                    <td>${p.delivery_type}</td>
                    <td>Rp ${parseInt(p.price).toLocaleString('id-ID')}</td>
                    <td><button class="btn btn-ghost" style="color:var(--danger); padding:5px 10px; font-size:0.7rem;" onclick="deleteProduct(${p.id})"><i class="fas fa-trash"></i></button></td>
                </tr>
            `).join('');
        }

        document.getElementById('add-product-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const resp = await fetch(`${BASE_URL}/admin/add-product`, { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) { Toast.fire({ icon: 'success', title: data.message }); e.target.reset(); loadAdminProducts(); }
            else Toast.fire({ icon: 'error', title: data.message });
        });

        async function deleteProduct(id) {
            if (!confirm('Delete this product?')) return;
            const fd = new FormData();
            fd.append('id', id);
            fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            await fetch(`${BASE_URL}/admin/delete-product`, { method: 'POST', body: fd });
            loadAdminProducts();
        }

        // Rentals
        async function loadAdminRentals() {
            const resp = await fetch(`${BASE_URL}/admin/rentals`);
            const rentals = await resp.json();
            document.getElementById('admin-rentals-table').innerHTML = rentals.map(r => `
                <tr>
                    <td>${r.title}</td>
                    <td>Rp ${parseInt(r.price).toLocaleString('id-ID')}</td>
                    <td>${r.duration}</td>
                    <td><span class="badge badge-${r.status === 'available' ? 'success' : 'pending'}">${r.status}</span></td>
                    <td><button class="btn btn-ghost" style="color:var(--danger); padding:5px 10px; font-size:0.7rem;" onclick="deleteRental(${r.id})"><i class="fas fa-trash"></i></button></td>
                </tr>
            `).join('');
        }

        document.getElementById('add-rental-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const resp = await fetch(`${BASE_URL}/admin/add-rental`, { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) { Toast.fire({ icon: 'success', title: 'Rental added!' }); e.target.reset(); loadAdminRentals(); }
            else Toast.fire({ icon: 'error', title: data.message });
        });

        async function deleteRental(id) {
            if (!confirm('Delete this rental?')) return;
            const fd = new FormData();
            fd.append('id', id);
            fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            await fetch(`${BASE_URL}/admin/delete-rental`, { method: 'POST', body: fd });
            loadAdminRentals();
        }

        // Transactions
        async function loadAdminTransactions() {
            const resp = await fetch(`${BASE_URL}/admin/transactions`);
            window.adminTxData = await resp.json();
            renderTxTable(window.adminTxData);
        }

        function renderTxTable(data) {
            document.getElementById('admin-transaction-container').innerHTML = data.map(h => {
                let actionBtn = (h.status === 'completed' && h.needs_fulfillment == 1) ? 
                    `<button class="btn btn-primary" style="padding:5px 10px; font-size:0.7rem;" onclick="fulfillOrder('${h.order_id}')">FULFILL</button>` : '-';
                return `<tr>
                    <td>${h.username}</td>
                    <td style="font-size:0.7rem;">#${h.order_id}</td>
                    <td><span class="badge badge-${h.game_type === 'ff' ? 'success' : 'pending'}">${h.game_type.toUpperCase()}</span></td>
                    <td><span class="badge badge-${h.status}">${h.status}</span></td>
                    <td style="font-size:0.7rem;">${h.licence || '-'}</td>
                    <td>${actionBtn}</td>
                </tr>`;
            }).join('');
        }

        function filterTxTable() {
            const query = document.getElementById('tx-search-input').value.toLowerCase();
            renderTxTable(window.adminTxData.filter(h => h.order_id.toLowerCase().includes(query) || h.username.toLowerCase().includes(query)));
        }

        async function fulfillOrder(orderId) {
            const { value: licence } = await Swal.fire({ title: 'Enter License Key', input: 'text', inputPlaceholder: 'License key...', showCancelButton: true, background: '#1a1a26', color: '#fff' });
            if (!licence) return;
            const fd = new FormData();
            fd.append('order_id', orderId);
            fd.append('licence', licence);
            fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            await fetch(`${BASE_URL}/admin/fulfill-order`, { method: 'POST', body: fd });
            Toast.fire({ icon: 'success', title: 'Order fulfilled!' });
            loadAdminTransactions();
        }

        // News
        async function loadAdminNews() {
            const resp = await fetch(`${BASE_URL}/admin/news`);
            const news = await resp.json();
            document.getElementById('admin-news-container').innerHTML = news.map(n => `
                <tr>
                    <td>${n.title}</td>
                    <td><span class="badge badge-${n.type}">${n.type}</span></td>
                    <td style="font-size:0.7rem;">${n.created_at}</td>
                    <td><button class="btn btn-ghost" style="color:var(--danger); padding:5px 10px; font-size:0.7rem;" onclick="deleteNews(${n.id})"><i class="fas fa-trash"></i></button></td>
                </tr>
            `).join('');
        }

        document.getElementById('post-news-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const resp = await fetch(`${BASE_URL}/admin/add-news`, { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) { Toast.fire({ icon: 'success', title: data.message }); e.target.reset(); loadAdminNews(); }
            else Toast.fire({ icon: 'error', title: data.message });
        });

        async function deleteNews(id) {
            if (!confirm('Delete this news?')) return;
            const fd = new FormData();
            fd.append('id', id);
            fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            await fetch(`${BASE_URL}/admin/delete-news`, { method: 'POST', body: fd });
            loadAdminNews();
        }

        // Vouchers
        async function loadAdminVouchers() {
            const resp = await fetch(`${BASE_URL}/admin/vouchers`);
            const vouchers = await resp.json();
            document.getElementById('admin-voucher-container').innerHTML = vouchers.map(v => `
                <tr>
                    <td style="font-family:monospace; font-weight:700;">${v.code}</td>
                    <td>${v.discount_percent}%</td>
                    <td>${v.used_count || 0}/${v.max_uses}</td>
                    <td style="font-size:0.7rem;">${v.created_at}</td>
                    <td><button class="btn btn-ghost" style="color:var(--danger); padding:5px 10px; font-size:0.7rem;" onclick="deleteVoucher(${v.id})"><i class="fas fa-trash"></i></button></td>
                </tr>
            `).join('');
        }

        document.getElementById('add-voucher-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const resp = await fetch(`${BASE_URL}/admin/add-voucher`, { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) { Toast.fire({ icon: 'success', title: 'Voucher created!' }); e.target.reset(); loadAdminVouchers(); }
            else Toast.fire({ icon: 'error', title: data.message });
        });

        async function deleteVoucher(id) {
            if (!confirm('Delete this voucher?')) return;
            const fd = new FormData();
            fd.append('id', id);
            fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            await fetch(`${BASE_URL}/admin/delete-voucher`, { method: 'POST', body: fd });
            loadAdminVouchers();
        }

        // Users
        async function loadAdminUsers() {
            const resp = await fetch(`${BASE_URL}/admin/users`);
            const users = await resp.json();
            document.getElementById('admin-user-container').innerHTML = users.map(u => `
                <tr>
                    <td style="font-weight:700;">${u.username}</td>
                    <td>${u.wa_number}</td>
                    <td><span class="badge badge-${u.role === 'admin' ? 'warning' : 'success'}">${u.role}</span></td>
                    <td>${u.points || 0}</td>
                    <td style="font-size:0.7rem;">${u.created_at}</td>
                    <td>
                        <button class="btn btn-ghost" style="padding:5px 10px; font-size:0.7rem;" onclick="resetPassword(${u.id})"><i class="fas fa-key"></i></button>
                        <button class="btn btn-ghost" style="color:var(--danger); padding:5px 10px; font-size:0.7rem;" onclick="deleteUser(${u.id})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        }

        async function resetPassword(id) {
            const { value: pwd } = await Swal.fire({ title: 'New Password', input: 'text', showCancelButton: true, background: '#1a1a26', color: '#fff' });
            if (!pwd) return;
            const fd = new FormData();
            fd.append('user_id', id);
            fd.append('new_password', pwd);
            fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            const resp = await fetch(`${BASE_URL}/admin/update-user-password`, { method: 'POST', body: fd });
            const data = await resp.json();
            Toast.fire({ icon: data.success ? 'success' : 'error', title: data.message });
        }

        async function deleteUser(id) {
            if (!confirm('Delete this user?')) return;
            const fd = new FormData();
            fd.append('id', id);
            fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            await fetch(`${BASE_URL}/admin/delete-user`, { method: 'POST', body: fd });
            loadAdminUsers();
        }

        // Notifications
        async function loadNotifSettings() {
            const resp = await fetch(`${BASE_URL}/admin/notif-settings`);
            const data = await resp.json();
            document.getElementById('notif-bot-token').value = data.notif_bot_token || '';
            document.getElementById('notif-chat-id').value = data.notif_chat_id || '';
        }

        document.getElementById('notif-settings-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const resp = await fetch(`${BASE_URL}/admin/save-notif-settings`, { method: 'POST', body: fd });
            const data = await resp.json();
            Toast.fire({ icon: data.success ? 'success' : 'error', title: data.message });
        });

        async function testNotification() {
            const fd = new FormData();
            fd.append('notif_bot_token', document.getElementById('notif-bot-token').value);
            fd.append('notif_chat_id', document.getElementById('notif-chat-id').value);
            fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            const resp = await fetch(`${BASE_URL}/admin/test-notif`, { method: 'POST', body: fd });
            const data = await resp.json();
            Toast.fire({ icon: data.success ? 'success' : 'error', title: data.message });
        }

        // Points
        async function loadPointSettings() {
            const resp = await fetch(`${BASE_URL}/admin/point-settings`);
            const rules = await resp.json();
            const days = ['1', '2', '3', '4', '5', '6', '7', '8', '10', '15', '20', '30'];
            document.getElementById('point-rules-editor').innerHTML = days.map(day => `
                <div class="form-group" style="background:var(--bg-card); border:1px solid var(--border); padding:12px; border-radius:12px;">
                    <label class="form-label" style="font-size:0.7rem; color:var(--accent);">${day} DAYS</label>
                    <input type="number" data-day="${day}" class="form-control point-rule-input" value="${rules[day] || 0}" min="0">
                </div>
            `).join('');
        }

        document.getElementById('point-settings-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const rules = {};
            document.querySelectorAll('.point-rule-input').forEach(input => { rules[input.dataset.day] = parseInt(input.value) || 0; });
            const fd = new FormData();
            fd.append('rules', JSON.stringify(rules));
            fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            const resp = await fetch(`${BASE_URL}/admin/save-point-settings`, { method: 'POST', body: fd });
            const data = await resp.json();
            Toast.fire({ icon: data.success ? 'success' : 'error', title: data.success ? 'Settings saved!' : 'Failed to save' });
        });

        // Init
        loadRevenueStats();
    </script>
</body>
</html>
