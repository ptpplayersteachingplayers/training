<?php
/**
 * Template: Player Progress Report - PTP Style v23.6
 * Parent view of training plans, assessments, and session notes
 */
defined('ABSPATH') || exit;
?>
<style>html, body { overflow-x: hidden !important; max-width: 100vw; }</style>
<?php

$player_id = isset($_GET['player']) ? intval($_GET['player']) : 0;
$parent = PTP_Parent::get_by_user_id(get_current_user_id());

if (!$parent) {
    echo '<div style="min-height:100vh;background:#F8F9FA;display:flex;align-items:center;justify-content:center;padding:40px 20px;">
        <div style="background:#fff;border-radius:24px;padding:40px;text-align:center;max-width:400px;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
            <div style="font-size:48px;margin-bottom:16px;">🔒</div>
            <h2 style="font-family:Oswald,sans-serif;font-size:24px;color:#111827;margin:0 0 12px;">Please Log In</h2>
            <p style="color:#6B7280;margin:0 0 24px;">Log in to view progress reports.</p>
            <a href="' . home_url('/login/') . '" style="background:linear-gradient(135deg,#FCB900,#F59E0B);color:#0E0F11;padding:14px 28px;border-radius:12px;font-weight:700;text-decoration:none;display:inline-block;">Log In</a>
        </div>
    </div>';
    return;
}

global $wpdb;
$player = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ptp_players WHERE id = %d AND parent_id = %d",
    $player_id, $parent->id
));

if (!$player) {
    $players = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ptp_players WHERE parent_id = %d AND is_active = 1",
        $parent->id
    ));
    
    if (empty($players)) {
        echo '<div style="min-height:100vh;background:#F8F9FA;display:flex;align-items:center;justify-content:center;padding:40px 20px;">
            <div style="background:#fff;border-radius:24px;padding:40px;text-align:center;max-width:400px;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
                <div style="font-size:48px;margin-bottom:16px;">⚽</div>
                <h2 style="font-family:Oswald,sans-serif;font-size:24px;color:#111827;margin:0 0 12px;">No Players Found</h2>
                <p style="color:#6B7280;margin:0 0 24px;">Add a player to track their progress.</p>
                <a href="' . home_url('/my-training/') . '" style="background:linear-gradient(135deg,#FCB900,#F59E0B);color:#0E0F11;padding:14px 28px;border-radius:12px;font-weight:700;text-decoration:none;display:inline-block;">Go to Dashboard</a>
            </div>
        </div>';
        return;
    }
    ?>
    <div style="min-height:100vh;background:#F8F9FA;padding:60px 20px;">
        <div style="background:#fff;border-radius:24px;max-width:500px;margin:0 auto;padding:40px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
            <div style="font-size:56px;margin-bottom:20px;">📊</div>
            <h1 style="font-family:'Oswald',sans-serif;font-size:28px;font-weight:700;color:#111827;margin:0 0 8px;">Select a Player</h1>
            <p style="font-size:15px;color:#6B7280;margin:0 0 32px;">Choose which player's progress you'd like to view</p>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach ($players as $p): ?>
                <a href="?player=<?php echo $p->id; ?>" style="display:flex;align-items:center;gap:16px;padding:16px 20px;background:#F9FAFB;border:2px solid #E5E7EB;border-radius:16px;text-decoration:none;transition:all 0.2s;">
                    <div style="width:50px;height:50px;background:linear-gradient(135deg,#FCB900,#F59E0B);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;">⚽</div>
                    <div style="text-align:left;">
                        <div style="font-weight:600;font-size:16px;color:#111827;"><?php echo esc_html($p->name ?? $p->first_name . ' ' . $p->last_name); ?></div>
                        <div style="font-size:13px;color:#6B7280;">Age <?php echo $p->age ?? 'N/A'; ?> • <?php echo ucfirst($p->skill_level ?? 'Beginner'); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Get data
$sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT b.*, t.display_name as trainer_name FROM {$wpdb->prefix}ptp_bookings b 
     JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id 
     WHERE b.player_id = %d AND b.status = 'completed' 
     ORDER BY b.session_date DESC LIMIT 10",
    $player_id
));

$total_hours = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(duration_minutes)/60, 0) FROM {$wpdb->prefix}ptp_bookings 
     WHERE player_id = %d AND status = 'completed'",
    $player_id
));

// Get active training plan
$active_plan = null;
$milestones = array();
if (class_exists('PTP_Training_Plans')) {
    $active_plan = $wpdb->get_row($wpdb->prepare(
        "SELECT p.*, t.display_name as trainer_name FROM {$wpdb->prefix}ptp_training_plans p
         JOIN {$wpdb->prefix}ptp_trainers t ON p.trainer_id = t.id
         WHERE p.player_id = %d AND p.status = 'active' ORDER BY p.created_at DESC LIMIT 1",
        $player_id
    ));
    
    if ($active_plan) {
        $milestones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_plan_milestones WHERE plan_id = %d ORDER BY sort_order",
            $active_plan->id
        ));
    }
}

// Get latest assessment
$latest_assessment = null;
$skill_ratings = array();
if (class_exists('PTP_Training_Plans')) {
    $latest_assessment = $wpdb->get_row($wpdb->prepare(
        "SELECT a.*, t.display_name as trainer_name FROM {$wpdb->prefix}ptp_skill_assessments a
         JOIN {$wpdb->prefix}ptp_trainers t ON a.trainer_id = t.id
         WHERE a.player_id = %d ORDER BY a.assessment_date DESC LIMIT 1",
        $player_id
    ));
    
    if ($latest_assessment) {
        $skill_ratings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_skill_ratings WHERE assessment_id = %d",
            $latest_assessment->id
        ));
    }
}

// Get session notes (visible to parent)
$session_notes = $wpdb->get_results($wpdb->prepare(
    "SELECT sn.*, t.display_name as trainer_name FROM {$wpdb->prefix}ptp_session_notes sn
     JOIN {$wpdb->prefix}ptp_trainers t ON sn.trainer_id = t.id
     WHERE sn.player_id = %d AND sn.is_visible_to_parent = 1
     ORDER BY sn.session_date DESC LIMIT 5",
    $player_id
));

// Get goals
$goals = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ptp_player_goals WHERE player_id = %d ORDER BY status ASC, target_date ASC",
    $player_id
));

$player_name = $player->name ?? $player->first_name . ' ' . $player->last_name;
$completed_milestones = array_filter($milestones, function($m) { return $m->is_completed; });
$milestone_progress = count($milestones) > 0 ? round(count($completed_milestones) / count($milestones) * 100) : 0;
?>

<div style="min-height:100vh;background:#F8F9FA;">
    <div style="background:linear-gradient(135deg,#0E0F11 0%,#1a1a1a 100%);padding:40px 20px 100px;">
        <div style="max-width:900px;margin:0 auto;">
            <a href="<?php echo home_url('/my-training/'); ?>" style="display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,0.7);text-decoration:none;font-size:14px;margin-bottom:20px;">← Back to Dashboard</a>
            <div style="display:flex;align-items:center;gap:24px;">
                <div style="width:80px;height:80px;background:linear-gradient(135deg,#FCB900,#F59E0B);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:36px;flex-shrink:0;">⚽</div>
                <div>
                    <h1 style="font-family:'Oswald',sans-serif;font-size:32px;font-weight:700;color:#fff;margin:0 0 8px;"><?php echo esc_html($player_name); ?></h1>
                    <p style="color:rgba(255,255,255,0.6);font-size:15px;margin:0;">Age <?php echo $player->age ?? 'N/A'; ?> • <?php echo ucfirst($player->skill_level ?? 'Player'); ?><?php echo $player->position ? ' • ' . esc_html($player->position) : ''; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div style="max-width:900px;margin:-60px auto 0;padding:0 20px 60px;position:relative;">
        <!-- Stats -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:24px;">
            <div style="background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.06);text-align:center;">
                <div style="font-family:'Oswald',sans-serif;font-size:36px;font-weight:700;color:#FCB900;margin:0;"><?php echo count($sessions); ?></div>
                <div style="font-size:14px;color:#6B7280;margin-top:4px;">Sessions</div>
            </div>
            <div style="background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.06);text-align:center;">
                <div style="font-family:'Oswald',sans-serif;font-size:36px;font-weight:700;color:#FCB900;margin:0;"><?php echo number_format($total_hours, 1); ?></div>
                <div style="font-size:14px;color:#6B7280;margin-top:4px;">Hours</div>
            </div>
            <div style="background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.06);text-align:center;">
                <div style="font-family:'Oswald',sans-serif;font-size:36px;font-weight:700;color:#FCB900;margin:0;"><?php echo $milestone_progress; ?>%</div>
                <div style="font-size:14px;color:#6B7280;margin-top:4px;">Plan Progress</div>
            </div>
        </div>
        
        <?php if ($active_plan): ?>
        <!-- Training Plan -->
        <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:24px;margin-bottom:24px;">
            <h3 style="font-family:'Oswald',sans-serif;font-size:20px;font-weight:600;color:#111827;margin:0 0 4px;display:flex;align-items:center;gap:10px;">📋 <?php echo esc_html($active_plan->title); ?></h3>
            <p style="font-size:13px;color:#6B7280;margin:0 0 20px;">By <?php echo esc_html($active_plan->trainer_name); ?> • <?php echo $active_plan->duration_weeks; ?> weeks</p>
            
            <?php if (!empty($milestones)): ?>
            <div style="margin-bottom:16px;">
                <div style="background:#F3F4F6;border-radius:8px;height:8px;overflow:hidden;">
                    <div style="background:linear-gradient(135deg,#FCB900,#F59E0B);height:100%;width:<?php echo $milestone_progress; ?>%;transition:width 0.3s;"></div>
                </div>
                <p style="font-size:12px;color:#6B7280;margin:8px 0 0;"><?php echo count($completed_milestones); ?> of <?php echo count($milestones); ?> milestones completed</p>
            </div>
            
            <div style="display:flex;flex-direction:column;gap:8px;">
                <?php foreach ($milestones as $m): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:12px;background:<?php echo $m->is_completed ? '#F0FDF4' : '#F9FAFB'; ?>;border-radius:10px;">
                    <span style="font-size:20px;"><?php echo $m->is_completed ? '✅' : '⬜'; ?></span>
                    <div style="flex:1;">
                        <div style="font-weight:600;font-size:14px;color:#111827;"><?php echo esc_html($m->title); ?></div>
                        <?php if ($m->target_week): ?>
                        <div style="font-size:12px;color:#6B7280;">Week <?php echo $m->target_week; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($latest_assessment && !empty($skill_ratings)): ?>
        <!-- Skill Assessment -->
        <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:24px;margin-bottom:24px;">
            <h3 style="font-family:'Oswald',sans-serif;font-size:20px;font-weight:600;color:#111827;margin:0 0 4px;display:flex;align-items:center;gap:10px;">📊 Skills Assessment</h3>
            <p style="font-size:13px;color:#6B7280;margin:0 0 20px;">By <?php echo esc_html($latest_assessment->trainer_name); ?> • <?php echo date('M j, Y', strtotime($latest_assessment->assessment_date)); ?></p>
            
            <?php
            $categories = array();
            foreach ($skill_ratings as $r) {
                if (!isset($categories[$r->skill_category])) {
                    $categories[$r->skill_category] = array();
                }
                $categories[$r->skill_category][] = $r;
            }
            
            $cat_names = array('technical' => 'Technical', 'tactical' => 'Tactical', 'physical' => 'Physical', 'mental' => 'Mental');
            $cat_icons = array('technical' => '⚽', 'tactical' => '🧠', 'physical' => '💪', 'mental' => '🎯');
            ?>
            
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                <?php foreach ($categories as $cat => $ratings): ?>
                <div style="background:#F9FAFB;border-radius:12px;padding:16px;">
                    <h4 style="font-size:14px;font-weight:600;color:#374151;margin:0 0 12px;display:flex;align-items:center;gap:6px;">
                        <?php echo $cat_icons[$cat] ?? '📌'; ?> <?php echo $cat_names[$cat] ?? ucfirst($cat); ?>
                    </h4>
                    <?php foreach ($ratings as $r): ?>
                    <div style="margin-bottom:8px;">
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                            <span style="color:#6B7280;"><?php echo ucfirst(str_replace('_', ' ', $r->skill_name)); ?></span>
                            <span style="font-weight:600;color:#111827;"><?php echo $r->rating; ?>/10</span>
                        </div>
                        <div style="background:#E5E7EB;border-radius:4px;height:6px;overflow:hidden;">
                            <div style="background:<?php echo $r->rating >= 7 ? '#10B981' : ($r->rating >= 5 ? '#F59E0B' : '#EF4444'); ?>;height:100%;width:<?php echo $r->rating * 10; ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($session_notes)): ?>
        <!-- Session Notes -->
        <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:24px;margin-bottom:24px;">
            <h3 style="font-family:'Oswald',sans-serif;font-size:20px;font-weight:600;color:#111827;margin:0 0 20px;display:flex;align-items:center;gap:10px;">📝 Trainer Notes</h3>
            
            <?php foreach ($session_notes as $note): ?>
            <div style="padding:16px 0;border-bottom:1px solid #F3F4F6;">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px;">
                    <span style="font-weight:600;font-size:14px;color:#111827;"><?php echo esc_html($note->trainer_name); ?></span>
                    <span style="font-size:12px;color:#9CA3AF;"><?php echo date('M j, Y', strtotime($note->session_date)); ?></span>
                </div>
                
                <?php if ($note->focus_worked_on): ?>
                <p style="font-size:14px;color:#374151;margin:0 0 8px;"><strong>Focus:</strong> <?php echo esc_html($note->focus_worked_on); ?></p>
                <?php endif; ?>
                
                <?php if ($note->achievements): ?>
                <p style="font-size:14px;color:#059669;margin:0 0 8px;">✅ <?php echo esc_html($note->achievements); ?></p>
                <?php endif; ?>
                
                <?php if ($note->areas_to_improve): ?>
                <p style="font-size:14px;color:#D97706;margin:0 0 8px;">🎯 <?php echo esc_html($note->areas_to_improve); ?></p>
                <?php endif; ?>
                
                <?php if ($note->homework): ?>
                <p style="font-size:14px;color:#6B7280;margin:0;"><strong>Homework:</strong> <?php echo esc_html($note->homework); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($goals)): ?>
        <!-- Goals -->
        <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:24px;margin-bottom:24px;">
            <h3 style="font-family:'Oswald',sans-serif;font-size:20px;font-weight:600;color:#111827;margin:0 0 20px;display:flex;align-items:center;gap:10px;">🎯 Goals</h3>
            
            <?php foreach ($goals as $goal): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:12px;background:<?php echo $goal->status === 'achieved' ? '#F0FDF4' : '#F9FAFB'; ?>;border-radius:10px;margin-bottom:8px;">
                <span style="font-size:20px;"><?php echo $goal->status === 'achieved' ? '🏆' : ($goal->status === 'active' ? '🎯' : '⏸️'); ?></span>
                <div style="flex:1;">
                    <div style="font-weight:600;font-size:14px;color:#111827;"><?php echo esc_html($goal->title); ?></div>
                    <?php if ($goal->target_date): ?>
                    <div style="font-size:12px;color:#6B7280;">Target: <?php echo date('M j, Y', strtotime($goal->target_date)); ?></div>
                    <?php endif; ?>
                </div>
                <span style="font-size:11px;font-weight:600;padding:4px 10px;border-radius:12px;background:<?php echo $goal->status === 'achieved' ? '#D1FAE5' : ($goal->status === 'active' ? '#FEF3C7' : '#F3F4F6'); ?>;color:<?php echo $goal->status === 'achieved' ? '#065F46' : ($goal->status === 'active' ? '#92400E' : '#6B7280'); ?>;"><?php echo ucfirst($goal->status); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Recent Sessions -->
        <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:24px;">
            <h3 style="font-family:'Oswald',sans-serif;font-size:20px;font-weight:600;color:#111827;margin:0 0 20px;display:flex;align-items:center;gap:10px;">📅 Recent Sessions</h3>
            
            <?php if (empty($sessions)): ?>
                <p style="color:#9CA3AF;text-align:center;padding:20px;">No completed sessions yet.</p>
            <?php else: ?>
                <?php foreach ($sessions as $s): ?>
                <div style="display:flex;gap:16px;padding:16px 0;border-bottom:1px solid #F3F4F6;">
                    <div style="width:50px;height:50px;background:#F3F4F6;border-radius:10px;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="font-family:'Oswald',sans-serif;font-size:18px;font-weight:700;color:#111827;line-height:1;"><?php echo date('j', strtotime($s->session_date)); ?></span>
                        <span style="font-size:10px;font-weight:600;color:#6B7280;text-transform:uppercase;"><?php echo date('M', strtotime($s->session_date)); ?></span>
                    </div>
                    <div>
                        <h4 style="font-weight:600;font-size:15px;color:#111827;margin:0 0 4px;"><?php echo esc_html($s->trainer_name); ?></h4>
                        <p style="font-size:13px;color:#6B7280;margin:0;"><?php echo $s->duration_minutes; ?> min • <?php echo esc_html($s->location ?: 'Location TBD'); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
