<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
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

<div class="profile-card">
    <div class="profile-header">
        <div class="profile-avatar">
            <i class="fas fa-user"></i>
        </div>
        <h2 style="margin-top:15px; color:white; font-weight:800;"><?= esc($user['username']) ?></h2>
        <span class="badge-role"><?= strtoupper(esc($user['role'])) ?> MEMBER</span>
    </div>
    <div class="profile-info">
        <div class="info-item">
            <span class="info-label"><i class="fas fa-user-tag"></i> Username</span>
            <span class="info-value"><?= esc($user['username']) ?></span>
        </div>
        <div class="info-item">
            <span class="info-label"><i class="fab fa-whatsapp"></i> No. WhatsApp</span>
            <span class="info-value"><?= esc($user['wa_number']) ?></span>
        </div>
        <div class="info-item">
            <span class="info-label"><i class="fab fa-telegram"></i> Telegram</span>
            <span class="info-value">@<?= esc($user['telegram_user'] ?? 'N/A') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label"><i class="fas fa-coins"></i> Points</span>
            <span class="info-value" style="color: #ffd700;"><?= number_format($user['points'] ?? 0) ?></span>
        </div>
        <div class="info-item">
            <span class="info-label"><i class="fas fa-shield-alt"></i> Akun Role</span>
            <span class="info-value" style="color:var(--accent);"><?= strtoupper(esc($user['role'])) ?></span>
        </div>
        <div class="info-item">
            <span class="info-label"><i class="fas fa-calendar-check"></i> Join Date</span>
            <span class="info-value"><?= date('d F Y', strtotime($user['created_at'])) ?></span>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
