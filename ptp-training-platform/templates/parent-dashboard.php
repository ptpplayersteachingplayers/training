<?php
/**
 * Template: Parent Dashboard v29.5.2
 * Clean, professional mobile-first design with PTP branding
 */
defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

$logo_url = PTP_Images::logo();
$user = wp_get_current_user();
$parent = class_exists('PTP_Parent') ? PTP_Parent::get_by_user_id(get_current_user_id()) : null;
$players = $parent && class_exists('PTP_Player') ? PTP_Player::get_by_parent($parent->id) : array();
$upcoming = $parent && class_exists('PTP_Parent') ? PTP_Parent::get_upcoming_sessions($parent->id) : array();
$history = $parent && class_exists('PTP_Parent') ? PTP_Parent::get_past_sessions($parent->id) : array();
$conversations = class_exists('PTP_Messaging') ? PTP_Messaging::get_conversations_for_user(get_current_user_id()) : array();
$first_name = $user->first_name ?: explode(' ', $user->display_name)[0];
$unread_count = 0;
foreach ($conversations as $c) { $unread_count += $c->unread_count ?? 0; }
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
$total_sessions = count($history);
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { overflow-x: hidden !important; max-width: 100vw; }

:root {
    --ptp-yellow: #FCB900;
    --ptp-black: #0E0F11;
    --ptp-dark: #1A1B1E;
    --ptp-gray-900: #111827;
    --ptp-gray-700: #374151;
    --ptp-gray-500: #6B7280;
    --ptp-gray-400: #9CA3AF;
    --ptp-gray-200: #E5E7EB;
    --ptp-gray-100: #F3F4F6;
    --ptp-gray-50: #F9FAFB;
    --ptp-green: #10B981;
    --ptp-red: #EF4444;
}

.ptp-dash-wrap {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--ptp-gray-100);
    min-height: 100vh;
    color: var(--ptp-gray-900);
    -webkit-font-smoothing: antialiased;
    overflow-x: hidden;
}

/* Header */
.ptp-header {
    background: var(--ptp-black);
    padding: 0 16px;
    position: sticky;
    top: 0;
    z-index: 100;
}

.ptp-header-inner {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
}

.ptp-logo { height: 32px; max-width: 130px; width: auto; object-fit: contain; }

.ptp-header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ptp-header-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
    color: #fff;
    text-decoration: none;
    position: relative;
    transition: background 0.2s;
}

.ptp-header-btn:hover { background: rgba(255,255,255,0.15); }

.ptp-header-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 18px;
    height: 18px;
    background: var(--ptp-red);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ptp-user-menu {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 12px 6px 6px;
    background: rgba(255,255,255,0.1);
    border-radius: 24px;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
}

.ptp-user-menu:hover { background: rgba(255,255,255,0.15); }

.ptp-user-avatar {
    width: 32px;
    height: 32px;
    background: var(--ptp-yellow);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    color: var(--ptp-black);
}

.ptp-user-name {
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    display: none;
}

@media (min-width: 640px) {
    .ptp-user-name { display: block; }
}

/* Main Content */
.ptp-main {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 16px 100px;
}

@media (min-width: 768px) {
    .ptp-main { padding: 32px 24px 60px; }
}

/* Welcome Section */
.ptp-welcome {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 24px;
}

@media (min-width: 640px) {
    .ptp-welcome {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

.ptp-welcome-text h1 {
    font-size: 24px;
    font-weight: 800;
    color: var(--ptp-gray-900);
    margin-bottom: 4px;
}

@media (min-width: 640px) {
    .ptp-welcome-text h1 { font-size: 28px; }
}

.ptp-welcome-text p {
    color: var(--ptp-gray-500);
    font-size: 14px;
}

.ptp-welcome-actions { display: flex; gap: 12px; }

.ptp-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.ptp-btn-primary {
    background: var(--ptp-yellow);
    color: var(--ptp-black);
}

.ptp-btn-primary:hover {
    background: #E5A800;
    transform: translateY(-1px);
}

.ptp-btn-secondary {
    background: #fff;
    color: var(--ptp-gray-700);
    border: 1px solid var(--ptp-gray-200);
}

.ptp-btn-secondary:hover {
    border-color: var(--ptp-gray-400);
    background: var(--ptp-gray-50);
}

/* Stats Grid */
.ptp-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 24px;
}

@media (min-width: 768px) {
    .ptp-stats { grid-template-columns: repeat(4, 1fr); }
}

.ptp-stat {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    border: 1px solid var(--ptp-gray-200);
}

.ptp-stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
}

.ptp-stat-icon.yellow { background: #FEF3C7; color: #D97706; }
.ptp-stat-icon.green { background: #D1FAE5; color: #059669; }
.ptp-stat-icon.blue { background: #DBEAFE; color: #2563EB; }
.ptp-stat-icon.purple { background: #EDE9FE; color: #7C3AED; }

.ptp-stat-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--ptp-gray-900);
    line-height: 1;
    margin-bottom: 4px;
}

.ptp-stat-label {
    font-size: 13px;
    color: var(--ptp-gray-500);
    font-weight: 500;
}

/* Content Grid */
.ptp-grid {
    display: grid;
    gap: 24px;
}

@media (min-width: 900px) {
    .ptp-grid { grid-template-columns: 1fr 380px; }
}

/* Cards */
.ptp-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--ptp-gray-200);
    overflow: hidden;
}

.ptp-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--ptp-gray-100);
}

.ptp-card-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--ptp-gray-900);
}

.ptp-card-action {
    color: var(--ptp-yellow);
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
}

.ptp-card-action:hover { text-decoration: underline; }

.ptp-card-body { padding: 16px 20px; }

/* Session Items */
.ptp-session {
    display: flex;
    gap: 14px;
    padding: 16px 0;
    border-bottom: 1px solid var(--ptp-gray-100);
}

.ptp-session:last-child { border-bottom: none; padding-bottom: 0; }
.ptp-session:first-child { padding-top: 0; }

.ptp-session-date {
    width: 52px;
    height: 52px;
    background: var(--ptp-black);
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.ptp-session-day {
    font-size: 20px;
    font-weight: 800;
    color: var(--ptp-yellow);
    line-height: 1;
}

.ptp-session-month {
    font-size: 10px;
    font-weight: 600;
    color: rgba(255,255,255,0.6);
    text-transform: uppercase;
    margin-top: 2px;
}

.ptp-session-info { flex: 1; min-width: 0; }

.ptp-session-trainer {
    font-weight: 600;
    font-size: 15px;
    color: var(--ptp-gray-900);
    margin-bottom: 4px;
}

.ptp-session-details {
    font-size: 13px;
    color: var(--ptp-gray-500);
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.ptp-session-status {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    align-self: flex-start;
}

.ptp-session-status.confirmed { background: #D1FAE5; color: #065F46; }
.ptp-session-status.pending { background: #FEF3C7; color: #92400E; }

/* Player Items */
.ptp-player {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid var(--ptp-gray-100);
}

.ptp-player:last-child { border-bottom: none; padding-bottom: 0; }
.ptp-player:first-child { padding-top: 0; }

.ptp-player-avatar {
    width: 48px;
    height: 48px;
    background: var(--ptp-yellow);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    color: var(--ptp-black);
    flex-shrink: 0;
}

.ptp-player-info { flex: 1; min-width: 0; }

.ptp-player-name {
    font-weight: 600;
    font-size: 15px;
    color: var(--ptp-gray-900);
    margin-bottom: 2px;
}

.ptp-player-meta {
    font-size: 13px;
    color: var(--ptp-gray-500);
}

/* Message Items */
.ptp-message {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid var(--ptp-gray-100);
    text-decoration: none;
    transition: background 0.2s;
}

.ptp-message:last-child { border-bottom: none; }
.ptp-message:hover { background: var(--ptp-gray-50); }

.ptp-message-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.ptp-message-info { flex: 1; min-width: 0; }

.ptp-message-name {
    font-weight: 600;
    font-size: 14px;
    color: var(--ptp-gray-900);
    margin-bottom: 2px;
}

.ptp-message-preview {
    font-size: 13px;
    color: var(--ptp-gray-500);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ptp-message-badge {
    width: 20px;
    height: 20px;
    background: var(--ptp-red);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Promo Card */
.ptp-promo {
    background: linear-gradient(135deg, var(--ptp-black) 0%, var(--ptp-dark) 100%);
    border: none;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 16px;
}

.ptp-promo-badge {
    display: inline-block;
    background: var(--ptp-yellow);
    color: var(--ptp-black);
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 14px;
}

.ptp-promo-title {
    font-size: 20px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
}

.ptp-promo-text {
    font-size: 14px;
    color: rgba(255,255,255,0.7);
    line-height: 1.5;
    margin-bottom: 20px;
}

.ptp-promo-btn {
    display: block;
    background: var(--ptp-yellow);
    color: var(--ptp-black);
    padding: 14px;
    border-radius: 10px;
    text-align: center;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s;
}

.ptp-promo-btn:hover { background: #E5A800; }

/* Quick Links */
.ptp-quick-links { display: flex; flex-direction: column; gap: 8px; }

.ptp-quick-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: var(--ptp-gray-50);
    border-radius: 10px;
    text-decoration: none;
    color: var(--ptp-gray-700);
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s;
}

.ptp-quick-link:hover { background: var(--ptp-gray-100); }
.ptp-quick-link svg { color: var(--ptp-gray-400); }

/* Empty States */
.ptp-empty {
    text-align: center;
    padding: 32px 20px;
}

.ptp-empty-icon {
    width: 48px;
    height: 48px;
    background: var(--ptp-gray-100);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    color: var(--ptp-gray-400);
}

.ptp-empty-text {
    color: var(--ptp-gray-500);
    font-size: 14px;
    margin-bottom: 16px;
}

.ptp-empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--ptp-yellow);
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    background: none;
    border: none;
    cursor: pointer;
}

.ptp-empty-btn:hover { text-decoration: underline; }

/* Mobile Bottom Nav */
.ptp-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    border-top: 1px solid var(--ptp-gray-200);
    display: flex;
    padding: 8px 16px env(safe-area-inset-bottom, 8px);
    z-index: 100;
}

@media (min-width: 768px) { .ptp-bottom-nav { display: none; } }

.ptp-bottom-nav a {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 8px;
    color: var(--ptp-gray-400);
    text-decoration: none;
    font-size: 11px;
    font-weight: 500;
}

.ptp-bottom-nav a.active { color: var(--ptp-yellow); }
.ptp-bottom-nav a svg { width: 22px; height: 22px; }

/* Modal */
.ptp-modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
}

.ptp-modal.show { opacity: 1; visibility: visible; }

.ptp-modal-content {
    background: #fff;
    border-radius: 20px;
    width: 100%;
    max-width: 440px;
    max-height: 90vh;
    overflow-y: auto;
    transform: translateY(20px);
    transition: transform 0.3s;
}

.ptp-modal.show .ptp-modal-content { transform: translateY(0); }

.ptp-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--ptp-gray-100);
}

.ptp-modal-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--ptp-gray-900);
}

.ptp-modal-close {
    width: 32px;
    height: 32px;
    background: var(--ptp-gray-100);
    border: none;
    border-radius: 50%;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ptp-gray-500);
}

.ptp-modal-body { padding: 24px; }

.ptp-modal-footer {
    padding: 16px 24px 24px;
    display: flex;
    gap: 12px;
}

.ptp-modal-footer .ptp-btn { flex: 1; justify-content: center; }

/* Form Elements */
.ptp-form-group { margin-bottom: 20px; }

.ptp-form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--ptp-gray-700);
    margin-bottom: 8px;
}

.ptp-form-input,
.ptp-form-select {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid var(--ptp-gray-200);
    border-radius: 10px;
    font-size: 16px;
    font-family: inherit;
    transition: all 0.2s;
    background: #fff;
}

.ptp-form-input:focus,
.ptp-form-select:focus {
    outline: none;
    border-color: var(--ptp-yellow);
    box-shadow: 0 0 0 3px rgba(252, 185, 0, 0.1);
}
</style>

<div class="ptp-dash-wrap">

<!-- Header -->
<header class="ptp-header">
    <div class="ptp-header-inner">
        <a href="<?php echo home_url(); ?>">
            <img src="<?php echo esc_url($logo_url); ?>" alt="PTP" class="ptp-logo">
        </a>
        
        <div class="ptp-header-actions">
            <a href="<?php echo home_url('/messages/'); ?>" class="ptp-header-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <?php if ($unread_count > 0): ?>
                    <span class="ptp-header-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="<?php echo home_url('/account/'); ?>" class="ptp-user-menu">
                <div class="ptp-user-avatar"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                <span class="ptp-user-name"><?php echo esc_html($first_name); ?></span>
            </a>
        </div>
    </div>
</header>

<!-- Main Content -->
<main class="ptp-main">
    <!-- Welcome Section -->
    <div class="ptp-welcome">
        <div class="ptp-welcome-text">
            <h1>Welcome back, <?php echo esc_html($first_name); ?>!</h1>
            <p>Manage your training sessions and players</p>
        </div>
        <div class="ptp-welcome-actions">
            <a href="<?php echo home_url('/find-trainers/'); ?>" class="ptp-btn ptp-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                Book Session
            </a>
            <a href="<?php echo home_url('/account/'); ?>" class="ptp-btn ptp-btn-secondary">Settings</a>
        </div>
    </div>
    
    <!-- Stats -->
    <div class="ptp-stats">
        <div class="ptp-stat">
            <div class="ptp-stat-icon yellow">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                </svg>
            </div>
            <div class="ptp-stat-value"><?php echo count($upcoming); ?></div>
            <div class="ptp-stat-label">Upcoming Sessions</div>
        </div>
        <div class="ptp-stat">
            <div class="ptp-stat-icon green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div class="ptp-stat-value"><?php echo $total_sessions; ?></div>
            <div class="ptp-stat-label">Total Sessions</div>
        </div>
        <div class="ptp-stat">
            <div class="ptp-stat-icon blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                </svg>
            </div>
            <div class="ptp-stat-value"><?php echo count($players); ?></div>
            <div class="ptp-stat-label">Players</div>
        </div>
        <div class="ptp-stat">
            <div class="ptp-stat-icon purple">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <div class="ptp-stat-value"><?php echo $unread_count; ?></div>
            <div class="ptp-stat-label">New Messages</div>
        </div>
    </div>
    
    <!-- Content Grid -->
    <div class="ptp-grid">
        <!-- Left Column -->
        <div>
            <!-- Upcoming Sessions -->
            <div class="ptp-card" style="margin-bottom: 24px;">
                <div class="ptp-card-header">
                    <h2 class="ptp-card-title">Upcoming Sessions</h2>
                    <a href="<?php echo home_url('/find-trainers/'); ?>" class="ptp-card-action">
                        Book New
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </div>
                <div class="ptp-card-body">
                    <?php if (empty($upcoming)): ?>
                        <div class="ptp-empty">
                            <div class="ptp-empty-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                                </svg>
                            </div>
                            <p class="ptp-empty-text">No upcoming sessions scheduled</p>
                            <a href="<?php echo home_url('/find-trainers/'); ?>" class="ptp-empty-btn">
                                Find a Trainer
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                                </svg>
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($upcoming, 0, 5) as $session): ?>
                            <div class="ptp-session">
                                <div class="ptp-session-date">
                                    <div class="ptp-session-day"><?php echo date('j', strtotime($session->session_date)); ?></div>
                                    <div class="ptp-session-month"><?php echo date('M', strtotime($session->session_date)); ?></div>
                                </div>
                                <div class="ptp-session-info">
                                    <div class="ptp-session-trainer"><?php echo esc_html($session->trainer_name ?? 'Trainer'); ?></div>
                                    <div class="ptp-session-details">
                                        <span><?php echo date('g:i A', strtotime($session->start_time)); ?></span>
                                        <span><?php echo esc_html($session->player_name ?? 'Player'); ?></span>
                                        <?php if (!empty($session->location)): ?>
                                            <span><?php echo esc_html($session->location); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="ptp-session-status <?php echo $session->status === 'confirmed' ? 'confirmed' : 'pending'; ?>">
                                    <?php echo ucfirst($session->status ?? 'Confirmed'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- My Players -->
            <div class="ptp-card">
                <div class="ptp-card-header">
                    <h2 class="ptp-card-title">My Players</h2>
                    <button onclick="openAddPlayer()" class="ptp-btn ptp-btn-primary" style="padding: 8px 14px; font-size: 13px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Add Player
                    </button>
                </div>
                <div class="ptp-card-body">
                    <?php if (empty($players)): ?>
                        <div class="ptp-empty">
                            <div class="ptp-empty-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                </svg>
                            </div>
                            <p class="ptp-empty-text">Add your first player to get started</p>
                            <button onclick="openAddPlayer()" class="ptp-empty-btn">
                                Add Player
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($players as $player): 
                            $name = trim(($player->first_name ?? '') . ' ' . ($player->last_name ?? ''));
                            if (empty($name)) $name = $player->name ?? 'Player';
                            $initials = strtoupper(substr($name, 0, 1));
                        ?>
                            <div class="ptp-player">
                                <div class="ptp-player-avatar"><?php echo $initials; ?></div>
                                <div class="ptp-player-info">
                                    <div class="ptp-player-name"><?php echo esc_html($name); ?></div>
                                    <div class="ptp-player-meta">
                                        <?php if (!empty($player->age)): ?>Age <?php echo esc_html($player->age); ?><?php endif; ?>
                                        <?php if (!empty($player->skill_level)): ?> &middot; <?php echo esc_html(ucfirst($player->skill_level)); ?><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div>
            <!-- Messages -->
            <div class="ptp-card" style="margin-bottom: 16px;">
                <div class="ptp-card-header">
                    <h2 class="ptp-card-title">Messages</h2>
                    <a href="<?php echo home_url('/messages/'); ?>" class="ptp-card-action">
                        View All
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </div>
                <div class="ptp-card-body" style="padding: 8px 0;">
                    <?php if (empty($conversations)): ?>
                        <div class="ptp-empty" style="padding: 24px 16px;">
                            <div class="ptp-empty-icon" style="font-size: 40px; margin-bottom: 12px;">💬</div>
                            <p class="ptp-empty-text" style="margin-bottom: 12px;">No messages yet</p>
                            <p style="color: var(--ptp-gray-400); font-size: 13px; margin: 0 0 16px;">Book a session with a trainer to start chatting</p>
                            <a href="<?php echo home_url('/find-trainers/'); ?>" class="ptp-empty-btn" style="display: inline-flex; align-items: center; gap: 6px; background: var(--ptp-yellow); color: var(--ptp-black); padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                Find Trainers
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($conversations, 0, 4) as $convo): 
                            $avatar = !empty($convo->other_photo) ? $convo->other_photo : 'https://ui-avatars.com/api/?name=' . urlencode($convo->other_name) . '&size=80&background=FCB900&color=0A0A0A&bold=true';
                        ?>
                            <a href="<?php echo home_url('/messages/?conversation=' . $convo->id); ?>" class="ptp-message">
                                <img src="<?php echo esc_url($avatar); ?>" alt="" class="ptp-message-avatar">
                                <div class="ptp-message-info">
                                    <div class="ptp-message-name"><?php echo esc_html($convo->other_name); ?></div>
                                    <div class="ptp-message-preview"><?php echo $convo->last_message ? esc_html(substr($convo->last_message, 0, 40)) : 'Start chatting'; ?></div>
                                </div>
                                <?php if ($convo->unread_count > 0): ?>
                                    <span class="ptp-message-badge"><?php echo $convo->unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Summer Camps Promo -->
            <div class="ptp-promo">
                <div class="ptp-promo-badge">Summer 2026</div>
                <h3 class="ptp-promo-title">Week-Long Camps</h3>
                <p class="ptp-promo-text">Full day training with elite coaches. PA, NJ, DE, MD & NY locations.</p>
                <a href="<?php echo home_url('/ptp-shop-page/'); ?>" class="ptp-promo-btn">View Camps</a>
            </div>
            
            <!-- Quick Links -->
            <div class="ptp-card">
                <div class="ptp-card-header">
                    <h2 class="ptp-card-title">Quick Links</h2>
                </div>
                <div class="ptp-card-body">
                    <div class="ptp-quick-links">
                        <a href="<?php echo home_url('/find-trainers/'); ?>" class="ptp-quick-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            Find Trainers
                        </a>
                        <a href="<?php echo home_url('/ptp-shop-page/'); ?>" class="ptp-quick-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                            </svg>
                            Summer Camps
                        </a>
                        <a href="<?php echo home_url('/messages/'); ?>" class="ptp-quick-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            Messages
                        </a>
                        <a href="<?php echo home_url('/account/'); ?>" class="ptp-quick-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            Account Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Mobile Bottom Navigation -->
<nav class="ptp-bottom-nav">
    <a href="?tab=overview" class="<?php echo $active_tab === 'overview' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        Home
    </a>
    <a href="<?php echo home_url('/my-training/'); ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        Sessions
    </a>
    <a href="<?php echo home_url('/find-trainers/'); ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        Find
    </a>
    <a href="<?php echo home_url('/messages/'); ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        Messages
    </a>
</nav>

</div>

<!-- Add Player Modal -->
<div class="ptp-modal" id="add-player-modal">
    <div class="ptp-modal-content">
        <div class="ptp-modal-header">
            <h3 class="ptp-modal-title">Add Player</h3>
            <button class="ptp-modal-close" onclick="closeAddPlayer()">&times;</button>
        </div>
        <form id="add-player-form">
            <div class="ptp-modal-body">
                <div class="ptp-form-group">
                    <label class="ptp-form-label">Player Name</label>
                    <input type="text" name="name" class="ptp-form-input" required placeholder="Enter player's name">
                </div>
                <div class="ptp-form-group">
                    <label class="ptp-form-label">Age</label>
                    <input type="number" name="age" class="ptp-form-input" min="4" max="18" placeholder="Player's age">
                </div>
                <div class="ptp-form-group">
                    <label class="ptp-form-label">Skill Level</label>
                    <select name="skill_level" class="ptp-form-select">
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>
                <div class="ptp-form-group" style="margin-bottom: 0;">
                    <label class="ptp-form-label">Position</label>
                    <input type="text" name="position" class="ptp-form-input" placeholder="e.g., Striker, Midfielder">
                </div>
            </div>
            <div class="ptp-modal-footer">
                <button type="button" class="ptp-btn ptp-btn-secondary" onclick="closeAddPlayer()">Cancel</button>
                <button type="submit" class="ptp-btn ptp-btn-primary">Add Player</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddPlayer() {
    document.getElementById('add-player-modal').classList.add('show');
}

function closeAddPlayer() {
    document.getElementById('add-player-modal').classList.remove('show');
}

document.getElementById('add-player-modal').addEventListener('click', function(e) {
    if (e.target === this) closeAddPlayer();
});

document.getElementById('add-player-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'ptp_add_player');
    formData.append('nonce', typeof ptp_ajax !== 'undefined' ? ptp_ajax.nonce : '');
    
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Adding...';
    
    fetch(typeof ptp_ajax !== 'undefined' ? ptp_ajax.ajax_url : '/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.data?.message || 'Error adding player');
            btn.disabled = false;
            btn.textContent = 'Add Player';
        }
    })
    .catch(() => {
        alert('Error adding player');
        btn.disabled = false;
        btn.textContent = 'Add Player';
    });
});
</script>
