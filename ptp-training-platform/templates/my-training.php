<?php
/**
 * Template: My Training (Parent Dashboard) v29.5.1
 * Logo + Mobile Optimized
 */
defined('ABSPATH') || exit;
$user = wp_get_current_user();
$logo_url = PTP_Images::logo();

// Get recent messages
global $wpdb;
$recent_messages = $wpdb->get_results($wpdb->prepare("
    SELECT m.*, c.trainer_id, t.display_name as trainer_name, t.photo_url as trainer_photo
    FROM {$wpdb->prefix}ptp_messages m
    JOIN {$wpdb->prefix}ptp_conversations c ON m.conversation_id = c.id
    JOIN {$wpdb->prefix}ptp_trainers t ON c.trainer_id = t.id
    WHERE c.parent_id = %d AND m.sender_id != %d
    ORDER BY m.created_at DESC
    LIMIT 3
", $parent->id, $user->ID));
?>

<style>
html, body { overflow-x: hidden !important; max-width: 100vw; }
*{box-sizing:border-box;margin:0;padding:0}
.ptp-my-training { min-height: 100vh; background: #F8F9FA; overflow-x: hidden; font-family:'Inter',-apple-system,sans-serif; }
.ptp-mt-header { background: linear-gradient(135deg, #0E0F11 0%, #1a1a1a 100%); padding: 30px 16px 100px; position: relative; }
@media (min-width: 600px) { .ptp-mt-header { padding: 40px 20px 100px; } }
.ptp-mt-header::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 60px; background: #F8F9FA; border-radius: 40px 40px 0 0; }
.ptp-mt-header-inner { max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; align-items: flex-start; gap: 16px; }
@media (min-width: 768px) { .ptp-mt-header-inner { flex-direction: row; align-items: center; justify-content: space-between; gap: 20px; } }
.ptp-mt-logo { height: 40px; max-width: 160px; width: auto; object-fit: contain; margin-bottom: 16px; }
@media (min-width: 768px) { .ptp-mt-logo { display: none; } }
.ptp-mt-greeting h1 { font-size: 24px; font-weight: 700; color: #fff; margin: 0 0 8px; }
@media (min-width: 600px) { .ptp-mt-greeting h1 { font-size: 32px; } }
.ptp-mt-greeting p { color: rgba(255,255,255,0.6); font-size: 14px; margin: 0; }
@media (min-width: 600px) { .ptp-mt-greeting p { font-size: 15px; } }
.ptp-mt-actions { display: flex; gap: 8px; width: 100%; }
@media (min-width: 768px) { .ptp-mt-actions { width: auto; gap: 12px; } }
.ptp-btn-book { flex: 1; background: linear-gradient(135deg, #FCB900 0%, #F59E0B 100%); color: #0E0F11; padding: 12px 20px; border-radius: 12px; font-weight: 700; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; }
@media (min-width: 600px) { .ptp-btn-book { flex: none; padding: 14px 28px; font-size: 15px; } }
.ptp-btn-book:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(252,185,0,0.3); color: #0E0F11; }
.ptp-btn-outline-white { flex: 1; background: transparent; border: 2px solid rgba(255,255,255,0.3); color: #fff; padding: 10px 20px; border-radius: 12px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.2s; text-align: center; }
@media (min-width: 600px) { .ptp-btn-outline-white { flex: none; padding: 12px 24px; } }
.ptp-btn-outline-white:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.5); color: #fff; }
.ptp-mt-container { max-width: 1200px; margin: -60px auto 0; padding: 0 16px 60px; position: relative; z-index: 1; }
@media (min-width: 600px) { .ptp-mt-container { padding: 0 20px 60px; } }
.ptp-mt-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 24px; }
@media (min-width: 600px) { .ptp-mt-stats { gap: 16px; margin-bottom: 32px; } }
@media (min-width: 900px) { .ptp-mt-stats { grid-template-columns: repeat(4, 1fr); } }
.ptp-mt-stat { background: #fff; border-radius: 16px; padding: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
@media (min-width: 600px) { .ptp-mt-stat { padding: 24px; } }
.ptp-mt-stat-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px; }
@media (min-width: 600px) { .ptp-mt-stat-icon { width: 48px; height: 48px; font-size: 24px; margin-bottom: 16px; } }
.ptp-mt-stat-icon.yellow { background: #FEF3C7; }
.ptp-mt-stat-icon.blue { background: #DBEAFE; }
.ptp-mt-stat-icon.green { background: #D1FAE5; }
.ptp-mt-stat-icon.purple { background: #EDE9FE; }
.ptp-mt-stat-value { font-size: 24px; font-weight: 700; color: #111827; margin: 0; }
@media (min-width: 600px) { .ptp-mt-stat-value { font-size: 32px; } }
.ptp-mt-stat-label { font-size: 12px; color: #6B7280; margin-top: 4px; }
@media (min-width: 600px) { .ptp-mt-stat-label { font-size: 14px; } }
.ptp-mt-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
@media (min-width: 900px) { .ptp-mt-grid { grid-template-columns: 2fr 1fr; gap: 24px; } }
.ptp-mt-card { background: #fff; border-radius: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; }
.ptp-mt-card-header { padding: 16px 20px; border-bottom: 1px solid #F3F4F6; display: flex; align-items: center; justify-content: space-between; }
@media (min-width: 600px) { .ptp-mt-card-header { padding: 20px 24px; } }
.ptp-mt-card-title { font-size: 16px; font-weight: 600; color: #111827; margin: 0; }
@media (min-width: 600px) { .ptp-mt-card-title { font-size: 18px; } }
.ptp-mt-card-body { padding: 16px 20px; }
@media (min-width: 600px) { .ptp-mt-card-body { padding: 24px; } }
.ptp-session-item { display: flex; gap: 12px; padding: 14px 0; border-bottom: 1px solid #F3F4F6; flex-wrap: wrap; }
@media (min-width: 500px) { .ptp-session-item { gap: 16px; padding: 16px 0; flex-wrap: nowrap; } }
.ptp-session-item:last-child { border-bottom: none; padding-bottom: 0; }
.ptp-session-item:first-child { padding-top: 0; }
.ptp-session-date-box { width: 50px; height: 50px; background: linear-gradient(135deg, #FCB900 0%, #F59E0B 100%); border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0; }
@media (min-width: 500px) { .ptp-session-date-box { width: 60px; height: 60px; } }
.ptp-session-date-day { font-size: 20px; font-weight: 700; color: #0E0F11; line-height: 1; }
@media (min-width: 500px) { .ptp-session-date-day { font-size: 24px; } }
.ptp-session-date-month { font-size: 10px; font-weight: 600; color: #0E0F11; text-transform: uppercase; }
@media (min-width: 500px) { .ptp-session-date-month { font-size: 12px; } }
.ptp-session-details { flex: 1; min-width: 0; }
.ptp-session-trainer { font-weight: 600; font-size: 15px; color: #111827; margin: 0 0 4px; }
@media (min-width: 500px) { .ptp-session-trainer { font-size: 16px; } }
.ptp-session-meta { font-size: 13px; color: #6B7280; display: flex; flex-wrap: wrap; gap: 6px; }
@media (min-width: 500px) { .ptp-session-meta { font-size: 14px; gap: 8px; } }
.ptp-session-meta span { display: inline-flex; align-items: center; gap: 4px; }
.ptp-session-status { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-top: 8px; width: 100%; text-align: center; }
@media (min-width: 500px) { .ptp-session-status { font-size: 12px; margin-top: 0; width: auto; } }
.ptp-session-status.confirmed { background: #D1FAE5; color: #065F46; }
.ptp-session-status.pending { background: #FEF3C7; color: #92400E; }
.ptp-session-status.completed { background: #E5E7EB; color: #374151; }
.ptp-empty-box { text-align: center; padding: 30px 20px; }
@media (min-width: 600px) { .ptp-empty-box { padding: 40px 20px; } }
.ptp-empty-icon { width: 60px; height: 60px; background: #F3F4F6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 28px; }
@media (min-width: 600px) { .ptp-empty-icon { width: 80px; height: 80px; font-size: 36px; margin-bottom: 20px; } }
.ptp-empty-title { font-family: 'Oswald', sans-serif; font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 8px; }
@media (min-width: 600px) { .ptp-empty-title { font-size: 20px; } }
.ptp-empty-text { font-size: 13px; color: #6B7280; margin: 0 0 16px; }
@media (min-width: 600px) { .ptp-empty-text { font-size: 14px; margin-bottom: 20px; } }
.ptp-player-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
@media (min-width: 600px) { .ptp-player-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; } }
.ptp-player-card { background: #F9FAFB; border-radius: 16px; padding: 16px; text-align: center; transition: all 0.2s; }
@media (min-width: 600px) { .ptp-player-card { padding: 20px; } }
.ptp-player-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.ptp-player-avatar { width: 50px; height: 50px; background: linear-gradient(135deg, #FCB900 0%, #F59E0B 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 22px; }
@media (min-width: 600px) { .ptp-player-avatar { width: 64px; height: 64px; font-size: 28px; margin-bottom: 12px; } }
.ptp-player-name { font-weight: 600; font-size: 14px; color: #111827; margin: 0 0 4px; }
@media (min-width: 600px) { .ptp-player-name { font-size: 15px; } }
.ptp-player-info { font-size: 12px; color: #6B7280; }
@media (min-width: 600px) { .ptp-player-info { font-size: 13px; } }
.ptp-msg-item { display: flex; gap: 10px; padding: 12px 0; border-bottom: 1px solid #F3F4F6; }
@media (min-width: 600px) { .ptp-msg-item { gap: 12px; padding: 14px 0; } }
.ptp-msg-item:last-child { border-bottom: none; }
.ptp-msg-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
@media (min-width: 600px) { .ptp-msg-avatar { width: 44px; height: 44px; } }
.ptp-msg-content { flex: 1; min-width: 0; }
.ptp-msg-name { font-weight: 600; font-size: 13px; color: #111827; margin: 0 0 4px; }
@media (min-width: 600px) { .ptp-msg-name { font-size: 14px; } }
.ptp-msg-preview { font-size: 12px; color: #6B7280; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
@media (min-width: 600px) { .ptp-msg-preview { font-size: 13px; } }
.ptp-msg-time { font-size: 11px; color: #9CA3AF; flex-shrink: 0; }
@media (min-width: 600px) { .ptp-msg-time { font-size: 12px; } }
.ptp-link { color: #FCB900; font-weight: 600; font-size: 13px; text-decoration: none; }
@media (min-width: 600px) { .ptp-link { font-size: 14px; } }
.ptp-link:hover { text-decoration: underline; }
.ptp-btn-add { background: #0E0F11; color: #fff; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.ptp-btn-add:hover { background: #1a1a1a; }
</style>

<div class="ptp-my-training">
    <!-- Header -->
    <div class="ptp-mt-header">
        <div class="ptp-mt-header-inner">
            <a href="<?php echo esc_url(home_url('/training/')); ?>">
                <img src="<?php echo esc_url($logo_url); ?>" class="ptp-mt-logo" alt="PTP Soccer">
            </a>
            <div class="ptp-mt-greeting">
                <h1>Welcome back, <?php echo esc_html($user->first_name ?: 'Parent'); ?>!</h1>
                <p>Manage your training sessions and players</p>
            </div>
            <div class="ptp-mt-actions">
                <a href="<?php echo home_url('/find-trainers/'); ?>" class="ptp-btn-book">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Book Session
                </a>
                <a href="<?php echo home_url('/account/'); ?>" class="ptp-btn-outline-white">Settings</a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="ptp-mt-container">
        <!-- Stats -->
        <div class="ptp-mt-stats">
            <div class="ptp-mt-stat">
                <div class="ptp-mt-stat-icon yellow"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                <div class="ptp-mt-stat-value"><?php echo count($upcoming); ?></div>
                <div class="ptp-mt-stat-label">Upcoming Sessions</div>
            </div>
            <div class="ptp-mt-stat">
                <div class="ptp-mt-stat-icon blue"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <div class="ptp-mt-stat-value"><?php echo $parent->total_sessions ?? 0; ?></div>
                <div class="ptp-mt-stat-label">Total Sessions</div>
            </div>
            <div class="ptp-mt-stat">
                <div class="ptp-mt-stat-icon green"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></div>
                <div class="ptp-mt-stat-value"><?php echo count($players); ?></div>
                <div class="ptp-mt-stat-label">Players</div>
            </div>
            <div class="ptp-mt-stat">
                <div class="ptp-mt-stat-icon purple"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#8B5CF6" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
                <div class="ptp-mt-stat-value"><?php echo count($recent_messages); ?></div>
                <div class="ptp-mt-stat-label">New Messages</div>
            </div>
        </div>
        
        <!-- Grid -->
        <div class="ptp-mt-grid">
            <!-- Main Column -->
            <div>
                <!-- Upcoming Sessions -->
                <div class="ptp-mt-card" style="margin-bottom: 24px;">
                    <div class="ptp-mt-card-header">
                        <h3 class="ptp-mt-card-title">Upcoming Sessions</h3>
                        <a href="<?php echo home_url('/find-trainers/'); ?>" class="ptp-link">Book New →</a>
                    </div>
                    <div class="ptp-mt-card-body">
                        <?php if (empty($upcoming)): ?>
                            <div class="ptp-empty-box">
                                <div class="ptp-empty-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                                <h3 class="ptp-empty-title">No Upcoming Sessions</h3>
                                <p class="ptp-empty-text">Book a training session to get started!</p>
                                <a href="<?php echo home_url('/find-trainers/'); ?>" class="ptp-btn-book" style="display: inline-flex;">Find a Trainer</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcoming as $session): ?>
                                <div class="ptp-session-item">
                                    <div class="ptp-session-date-box">
                                        <span class="ptp-session-date-day"><?php echo date('j', strtotime($session->session_date)); ?></span>
                                        <span class="ptp-session-date-month"><?php echo date('M', strtotime($session->session_date)); ?></span>
                                    </div>
                                    <div class="ptp-session-details">
                                        <h4 class="ptp-session-trainer"><?php echo esc_html($session->trainer_name); ?></h4>
                                        <div class="ptp-session-meta">
                                            <span><?php echo date('g:i A', strtotime($session->start_time)); ?></span>
                                            <span><?php echo esc_html($session->player_name); ?></span>
                                            <?php if ($session->location): ?>
                                                <span><?php echo esc_html($session->location); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="ptp-session-status <?php echo $session->status; ?>"><?php echo ucfirst($session->status); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- My Players -->
                <div class="ptp-mt-card">
                    <div class="ptp-mt-card-header">
                        <h3 class="ptp-mt-card-title">My Players</h3>
                        <button class="ptp-btn-add" data-modal="add-player-modal">+ Add Player</button>
                    </div>
                    <div class="ptp-mt-card-body">
                        <?php if (empty($players)): ?>
                            <div class="ptp-empty-box">
                                <div class="ptp-empty-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5"><circle cx="12" cy="12" r="10"/></svg></div>
                                <h3 class="ptp-empty-title">No Players Yet</h3>
                                <p class="ptp-empty-text">Add your child to start booking sessions</p>
                                <button class="ptp-btn-add" data-modal="add-player-modal">+ Add Player</button>
                            </div>
                        <?php else: ?>
                            <div class="ptp-player-grid">
                                <?php foreach ($players as $player): ?>
                                    <div class="ptp-player-card">
                                        <div class="ptp-player-avatar"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></div>
                                        <div class="ptp-player-name"><?php echo esc_html($player->name); ?></div>
                                        <div class="ptp-player-info"><?php echo $player->age; ?> yrs • <?php echo ucfirst($player->skill_level); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div>
                <!-- Messages -->
                <div class="ptp-mt-card" style="margin-bottom: 24px;">
                    <div class="ptp-mt-card-header">
                        <h3 class="ptp-mt-card-title">Messages</h3>
                        <a href="<?php echo home_url('/messages/'); ?>" class="ptp-link">View All →</a>
                    </div>
                    <div class="ptp-mt-card-body">
                        <?php if (empty($recent_messages)): ?>
                            <p style="color: #6B7280; font-size: 14px; margin: 0; text-align: center; padding: 20px 0;">No messages yet</p>
                        <?php else: ?>
                            <?php foreach ($recent_messages as $msg): 
                                $photo = $msg->trainer_photo ?: 'https://ui-avatars.com/api/?name=' . urlencode($msg->trainer_name) . '&size=88&background=FCB900&color=0A0A0A&bold=true';
                            ?>
                                <div class="ptp-msg-item">
                                    <img src="<?php echo esc_url($photo); ?>" alt="" class="ptp-msg-avatar">
                                    <div class="ptp-msg-content">
                                        <div class="ptp-msg-name"><?php echo esc_html($msg->trainer_name); ?></div>
                                        <p class="ptp-msg-preview"><?php echo esc_html(substr($msg->message, 0, 50)); ?>...</p>
                                    </div>
                                    <span class="ptp-msg-time"><?php echo human_time_diff(strtotime($msg->created_at)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Summer Camps Promo -->
                <div class="ptp-mt-card" style="background: linear-gradient(135deg, #0E0F11 0%, #1a1a1a 100%); border: none;">
                    <div style="padding: 24px;">
                        <div style="display: inline-block; background: #FCB900; color: #0E0F11; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; margin-bottom: 12px;">SUMMER 2026</div>
                        <h3 style="font-size: 18px; font-weight: 700; color: #fff; margin: 0 0 8px;">Week-Long Camps</h3>
                        <p style="font-size: 13px; color: rgba(255,255,255,0.7); margin: 0 0 16px; line-height: 1.5;">Full day training with elite coaches. PA, NJ, DE, MD & NY locations.</p>
                        <a href="<?php echo home_url('/ptp-shop-page/'); ?>" style="display: block; background: #FCB900; color: #0E0F11; padding: 12px; border-radius: 10px; text-align: center; font-weight: 700; font-size: 14px; text-decoration: none;">View Camps</a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="ptp-mt-card">
                    <div class="ptp-mt-card-header">
                        <h3 class="ptp-mt-card-title">Quick Links</h3>
                    </div>
                    <div class="ptp-mt-card-body" style="padding: 16px 24px;">
                        <a href="<?php echo home_url('/find-trainers/'); ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #F3F4F6; color: #111827; text-decoration: none; font-weight: 500;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            Find Trainers
                        </a>
                        <a href="<?php echo home_url('/ptp-shop-page/'); ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #F3F4F6; color: #111827; text-decoration: none; font-weight: 500;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            Summer Camps
                        </a>
                        <a href="<?php echo home_url('/messages/'); ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #F3F4F6; color: #111827; text-decoration: none; font-weight: 500;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            Messages
                        </a>
                        <a href="<?php echo home_url('/account/'); ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 0; color: #111827; text-decoration: none; font-weight: 500;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                            Account Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Player Modal -->
<div class="ptp-modal-overlay" id="add-player-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="ptp-modal" style="background: #fff; border-radius: 20px; max-width: 480px; width: 90%; max-height: 90vh; overflow: auto;">
        <div style="padding: 24px; border-bottom: 1px solid #F3F4F6; display: flex; align-items: center; justify-content: space-between;">
            <h3 style="font-family: 'Oswald', sans-serif; font-size: 20px; font-weight: 600; margin: 0;">Add Player</h3>
            <button type="button" class="ptp-modal-close" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6B7280;">&times;</button>
        </div>
        <form class="ptp-ajax-form" data-reload="true" data-close-modal="true">
            <input type="hidden" name="action" value="ptp_add_player">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ptp_nonce'); ?>">
            <div style="padding: 24px;">
                <div class="ptp-form-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">Player Name *</label>
                    <input type="text" name="name" required style="width: 100%; padding: 14px 16px; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 15px; box-sizing: border-box;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div class="ptp-form-group">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">Age *</label>
                        <input type="number" name="age" min="4" max="18" required style="width: 100%; padding: 14px 16px; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 15px; box-sizing: border-box;">
                    </div>
                    <div class="ptp-form-group">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">Skill Level</label>
                        <select name="skill_level" style="width: 100%; padding: 14px 16px; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 15px; box-sizing: border-box;">
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                            <option value="elite">Elite</option>
                        </select>
                    </div>
                </div>
                <div class="ptp-form-group">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">Goals (optional)</label>
                    <textarea name="goals" rows="3" placeholder="What do you want to improve?" style="width: 100%; padding: 14px 16px; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 15px; box-sizing: border-box; resize: vertical;"></textarea>
                </div>
            </div>
            <div style="padding: 20px 24px; border-top: 1px solid #F3F4F6; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="ptp-modal-close" style="background: #F3F4F6; color: #374151; padding: 12px 24px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" style="background: linear-gradient(135deg, #FCB900 0%, #F59E0B 100%); color: #0E0F11; padding: 12px 24px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Add Player</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal handling
document.querySelectorAll('[data-modal]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var modal = document.getElementById(this.getAttribute('data-modal'));
        if (modal) modal.style.display = 'flex';
    });
});
document.querySelectorAll('.ptp-modal-close').forEach(function(btn) {
    btn.addEventListener('click', function() {
        this.closest('.ptp-modal-overlay').style.display = 'none';
    });
});
document.querySelectorAll('.ptp-modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
