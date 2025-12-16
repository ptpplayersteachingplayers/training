<?php
/**
 * Template: Training Plans - PTP Style v23.4
 */
defined('ABSPATH') || exit;

$trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
if (!$trainer) {
    echo '<div style="min-height:100vh;background:#F8F9FA;display:flex;align-items:center;justify-content:center;padding:40px 20px;">
        <div style="background:#fff;border-radius:24px;padding:40px;text-align:center;max-width:400px;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
            <div style="font-size:48px;margin-bottom:16px;">🔒</div>
            <h2 style="font-family:Oswald,sans-serif;font-size:24px;color:#111827;margin:0 0 12px;">Trainer Access Required</h2>
            <p style="color:#6B7280;margin:0 0 24px;">This page is only available for trainers.</p>
            <a href="' . home_url('/trainer-dashboard/') . '" style="background:linear-gradient(135deg,#FCB900,#F59E0B);color:#0E0F11;padding:14px 28px;border-radius:12px;font-weight:700;text-decoration:none;display:inline-block;">Go to Dashboard</a>
        </div>
    </div>';
    return;
}

$players = class_exists('PTP_Training_Plans') ? PTP_Training_Plans::get_trainer_players($trainer->id) : array();
$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'players';
$player_id = isset($_GET['player']) ? intval($_GET['player']) : 0;
?>

<style>
html, body { overflow-x: hidden !important; max-width: 100vw; }
.ptp-tp-wrap { min-height: 100vh; background: #F8F9FA; overflow-x: hidden; }
.ptp-tp-header { background: linear-gradient(135deg, #0E0F11 0%, #1a1a1a 100%); padding: 30px 16px 100px; }
@media (min-width: 600px) { .ptp-tp-header { padding: 40px 20px 100px; } }
.ptp-tp-header-inner { max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; align-items: flex-start; gap: 16px; }
@media (min-width: 600px) { .ptp-tp-header-inner { flex-direction: row; align-items: center; justify-content: space-between; gap: 20px; } }
.ptp-tp-title { font-family: 'Oswald', sans-serif; font-size: 24px; font-weight: 700; color: #fff; margin: 0 0 6px; }
@media (min-width: 600px) { .ptp-tp-title { font-size: 32px; margin-bottom: 8px; } }
.ptp-tp-subtitle { color: rgba(255,255,255,0.6); font-size: 14px; margin: 0; }
@media (min-width: 600px) { .ptp-tp-subtitle { font-size: 15px; } }
.ptp-tp-btn { padding: 10px 16px; border-radius: 10px; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.2s; background: transparent; border: 2px solid rgba(255,255,255,0.3); color: #fff; display: inline-block; }
@media (min-width: 600px) { .ptp-tp-btn { padding: 12px 20px; font-size: 14px; } }
.ptp-tp-btn:hover { background: rgba(255,255,255,0.1); }
.ptp-tp-container { max-width: 1000px; margin: -60px auto 0; padding: 0 16px 60px; position: relative; }
@media (min-width: 600px) { .ptp-tp-container { padding: 0 20px 60px; } }
.ptp-tp-card { background: #fff; border-radius: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 20px; }
@media (min-width: 600px) { .ptp-tp-card { margin-bottom: 24px; } }
.ptp-tp-card-header { padding: 16px 20px; border-bottom: 1px solid #F3F4F6; display: flex; align-items: center; justify-content: space-between; }
@media (min-width: 600px) { .ptp-tp-card-header { padding: 20px 24px; } }
.ptp-tp-card-title { font-family: 'Oswald', sans-serif; font-size: 18px; font-weight: 600; color: #111827; margin: 0; }
@media (min-width: 600px) { .ptp-tp-card-title { font-size: 20px; } }
.ptp-tp-card-body { padding: 16px 20px; }
@media (min-width: 600px) { .ptp-tp-card-body { padding: 24px; } }
.ptp-tp-player { display: flex; flex-direction: column; gap: 12px; padding: 16px; border-bottom: 1px solid #F3F4F6; transition: all 0.2s; }
@media (min-width: 500px) { .ptp-tp-player { flex-direction: row; align-items: center; gap: 16px; padding: 20px; } }
.ptp-tp-player:last-child { border-bottom: none; }
.ptp-tp-player:hover { background: #F9FAFB; }
.ptp-tp-player-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #FCB900, #F59E0B); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
@media (min-width: 500px) { .ptp-tp-player-avatar { width: 56px; height: 56px; font-size: 28px; } }
.ptp-tp-player-info { flex: 1; min-width: 0; }
.ptp-tp-player-name { font-weight: 600; font-size: 15px; color: #111827; margin: 0 0 4px; }
@media (min-width: 500px) { .ptp-tp-player-name { font-size: 17px; } }
.ptp-tp-player-meta { font-size: 13px; color: #6B7280; }
@media (min-width: 500px) { .ptp-tp-player-meta { font-size: 14px; } }
.ptp-tp-player-actions { display: flex; gap: 8px; width: 100%; }
@media (min-width: 500px) { .ptp-tp-player-actions { width: auto; } }
.ptp-tp-action-btn { flex: 1; padding: 10px 14px; border-radius: 8px; font-weight: 600; font-size: 12px; text-decoration: none; transition: all 0.2s; text-align: center; }
@media (min-width: 500px) { .ptp-tp-action-btn { flex: none; padding: 10px 16px; font-size: 13px; } }
.ptp-tp-action-btn-primary { background: linear-gradient(135deg, #FCB900, #F59E0B); color: #0E0F11; }
.ptp-tp-action-btn-outline { background: #F3F4F6; color: #374151; }
.ptp-tp-empty { text-align: center; padding: 40px 20px; }
@media (min-width: 600px) { .ptp-tp-empty { padding: 60px 40px; } }
.ptp-tp-empty-icon { font-size: 48px; margin-bottom: 16px; }
@media (min-width: 600px) { .ptp-tp-empty-icon { font-size: 56px; margin-bottom: 20px; } }
.ptp-tp-empty-title { font-family: 'Oswald', sans-serif; font-size: 20px; font-weight: 600; color: #111827; margin: 0 0 10px; }
@media (min-width: 600px) { .ptp-tp-empty-title { font-size: 22px; margin-bottom: 12px; } }
.ptp-tp-empty-text { font-size: 14px; color: #6B7280; margin: 0; max-width: 400px; margin-left: auto; margin-right: auto; }
@media (min-width: 600px) { .ptp-tp-empty-text { font-size: 15px; } }
.ptp-tp-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
@media (min-width: 600px) { .ptp-tp-badge { font-size: 12px; } }
.ptp-tp-badge-active { background: #D1FAE5; color: #065F46; }
.ptp-tp-badge-new { background: #DBEAFE; color: #1E40AF; }
</style>

<div class="ptp-tp-wrap">
    <div class="ptp-tp-header">
        <div class="ptp-tp-header-inner">
            <div>
                <h1 class="ptp-tp-title">Training Plans & Progress</h1>
                <p class="ptp-tp-subtitle">Manage training plans and track player development</p>
            </div>
            <a href="<?php echo home_url('/trainer-dashboard/'); ?>" class="ptp-tp-btn">← Back to Dashboard</a>
        </div>
    </div>
    
    <div class="ptp-tp-container">
        <div class="ptp-tp-card">
            <div class="ptp-tp-card-header">
                <h3 class="ptp-tp-card-title">Your Players</h3>
            </div>
            
            <?php if (empty($players)): ?>
            <div class="ptp-tp-empty">
                <div class="ptp-tp-empty-icon">👥</div>
                <h3 class="ptp-tp-empty-title">No Players Yet</h3>
                <p class="ptp-tp-empty-text">Complete training sessions with players to start tracking their progress and creating custom training plans.</p>
            </div>
            <?php else: ?>
            <div>
                <?php foreach ($players as $player): ?>
                <div class="ptp-tp-player">
                    <div class="ptp-tp-player-avatar">⚽</div>
                    <div class="ptp-tp-player-info">
                        <div class="ptp-tp-player-name"><?php echo esc_html($player->name ?? $player->first_name . ' ' . $player->last_name); ?></div>
                        <div class="ptp-tp-player-meta">
                            Age <?php echo $player->age ?? 'N/A'; ?> • 
                            <?php echo ucfirst($player->skill_level ?? 'Beginner'); ?> • 
                            <?php echo $player->sessions_with_trainer ?? 0; ?> sessions
                        </div>
                    </div>
                    <div class="ptp-tp-player-actions">
                        <?php if (!empty($player->active_plan_id)): ?>
                        <span class="ptp-tp-badge ptp-tp-badge-active">Active Plan</span>
                        <?php endif; ?>
                        <a href="?player=<?php echo $player->id; ?>&view=assess" class="ptp-tp-action-btn ptp-tp-action-btn-outline">Assess</a>
                        <a href="?player=<?php echo $player->id; ?>&view=plan" class="ptp-tp-action-btn ptp-tp-action-btn-primary">
                            <?php echo !empty($player->active_plan_id) ? 'View Plan' : 'Create Plan'; ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Tips Card -->
        <div class="ptp-tp-card">
            <div class="ptp-tp-card-body">
                <h4 style="font-family: 'Oswald', sans-serif; font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 16px; display: flex; align-items: center; gap: 10px;">
                    💡 Tips for Effective Training Plans
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div style="padding: 16px; background: #F9FAFB; border-radius: 12px;">
                        <div style="font-weight: 600; font-size: 14px; color: #111827; margin-bottom: 8px;">Assess First</div>
                        <p style="font-size: 13px; color: #6B7280; margin: 0; line-height: 1.6;">Evaluate the player's current skill levels before creating a plan to identify areas for improvement.</p>
                    </div>
                    <div style="padding: 16px; background: #F9FAFB; border-radius: 12px;">
                        <div style="font-weight: 600; font-size: 14px; color: #111827; margin-bottom: 8px;">Set Goals</div>
                        <p style="font-size: 13px; color: #6B7280; margin: 0; line-height: 1.6;">Work with players and parents to set realistic, achievable goals for each training period.</p>
                    </div>
                    <div style="padding: 16px; background: #F9FAFB; border-radius: 12px;">
                        <div style="font-weight: 600; font-size: 14px; color: #111827; margin-bottom: 8px;">Track Progress</div>
                        <p style="font-size: 13px; color: #6B7280; margin: 0; line-height: 1.6;">Update skill assessments regularly to show improvement and keep players motivated.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
