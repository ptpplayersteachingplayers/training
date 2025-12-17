<?php
/**
 * Template: Trainer Dashboard v29.5.1
 * Logo + Mobile Optimized, schedule saving, Google Calendar sync
 */
defined('ABSPATH') || exit;

if (!isset($trainer) || !$trainer) {
    echo '<div style="padding:40px;text-align:center;font-family:Inter,sans-serif"><p>Trainer profile not found.</p><a href="' . home_url('/apply/') . '">Apply as Trainer</a></div>';
    return;
}

$logo_url = PTP_Images::logo();

// Safe defaults
$earnings = isset($earnings) && is_array($earnings) ? $earnings : array('this_month' => 0, 'total_earnings' => 0, 'pending_payout' => 0);
$upcoming = isset($upcoming) && is_array($upcoming) ? $upcoming : array();
$pending_confirmations = isset($pending_confirmations) && is_array($pending_confirmations) ? $pending_confirmations : array();

// Get availability
$availability = array();
if (class_exists('PTP_Availability')) {
    $availability = PTP_Availability::get_weekly($trainer->id);
}
$avail_by_day = array();
foreach ($availability as $slot) {
    if (isset($slot->day_of_week)) {
        $avail_by_day[$slot->day_of_week] = $slot;
    }
}

// Calendar status
$calendar_status = array('google' => array('connected' => false));
if (class_exists('PTP_Calendar_Sync')) {
    $calendar_status = PTP_Calendar_Sync::get_connection_status(get_current_user_id());
}

$user = wp_get_current_user();
$days = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$day_names = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'schedule';
$photo = $trainer->photo_url ?: PTP_Images::avatar($trainer->display_name, 200);
$stripe_connected = !empty($trainer->stripe_account_id);
$trainer_slug = $trainer->slug ?: sanitize_title($trainer->display_name);
$profile_url = home_url('/trainer/' . $trainer_slug . '/');

// Conversations
$conversations = array();
$unread_count = 0;
if (class_exists('PTP_Messaging') && method_exists('PTP_Messaging', 'get_conversations_for_user')) {
    $conversations = PTP_Messaging::get_conversations_for_user(get_current_user_id());
    foreach ($conversations as $c) { $unread_count += $c->unread_count ?? 0; }
}
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
*{box-sizing:border-box;margin:0;padding:0}html,body{overflow-x:hidden!important;max-width:100vw}
.td{font-family:'Inter',-apple-system,sans-serif;background:#F3F4F6;min-height:100vh;color:#111;-webkit-font-smoothing:antialiased}
.td-header{background:#0E0F11;padding:20px;color:#fff}
.td-header-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:16px}
.td-logo{height:36px;max-width:140px;width:auto;object-fit:contain;margin-right:8px}
.td-avatar{width:56px;height:56px;border-radius:14px;object-fit:cover;border:2px solid #FCB900}
.td-name{font-size:20px;font-weight:700}
.td-role{font-size:14px;color:#9CA3AF}
.td-main{max-width:1200px;margin:0 auto;padding:24px 20px}
.td-tabs{display:flex;gap:8px;margin-bottom:24px;overflow-x:auto;padding-bottom:8px;-webkit-overflow-scrolling:touch}
.td-tab{padding:12px 20px;background:#fff;border:2px solid #E5E7EB;border-radius:12px;font-size:14px;font-weight:600;color:#6B7280;cursor:pointer;white-space:nowrap;text-decoration:none;transition:all 0.2s}
.td-tab:hover{border-color:#FCB900}
.td-tab.active{background:#FCB900;border-color:#FCB900;color:#0E0F11}
.td-card{background:#fff;border-radius:20px;padding:24px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);border:1px solid #E5E7EB}
.td-card-title{font-size:18px;font-weight:700;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.td-grid{display:grid;grid-template-columns:1fr 400px;gap:24px}
.td-avail-row{display:flex;align-items:center;gap:16px;padding:16px;background:#F9FAFB;border-radius:14px;margin-bottom:12px}
.td-avail-toggle{position:relative;width:48px;height:28px;flex-shrink:0}
.td-avail-toggle input{opacity:0;width:0;height:0;position:absolute}
.td-avail-toggle-track{position:absolute;inset:0;background:#D1D5DB;border-radius:14px;transition:background 0.2s;cursor:pointer}
.td-avail-toggle input:checked+.td-avail-toggle-track{background:#10B981}
.td-avail-toggle-track::after{content:'';position:absolute;width:22px;height:22px;background:#fff;border-radius:50%;top:3px;left:3px;transition:transform 0.2s;box-shadow:0 2px 4px rgba(0,0,0,0.2)}
.td-avail-toggle input:checked+.td-avail-toggle-track::after{transform:translateX(20px)}
.td-avail-day{width:80px;font-weight:700;font-size:15px;color:#111}
.td-avail-times{display:flex;align-items:center;gap:10px;flex:1}
.td-avail-times.disabled{opacity:0.4;pointer-events:none}
.td-time-input{padding:10px 14px;border:2px solid #E5E7EB;border-radius:10px;font-size:16px;font-family:inherit;width:120px}
@media(max-width:768px){
    .td-grid{grid-template-columns:1fr!important}
    .td-avail-row{flex-wrap:wrap}
    .td-avail-times{width:100%;margin-top:8px}
    .td-header-inner{flex-wrap:wrap}
}
.td-time-input:focus{outline:none;border-color:#FCB900}
.td-avail-status{width:14px;height:14px;border-radius:50%;background:#D1D5DB}
.td-avail-status.on{background:#10B981}
.td-btn{padding:14px 28px;background:#FCB900;color:#0E0F11;border:none;border-radius:12px;font-family:inherit;font-size:15px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all 0.2s}
.td-btn:hover{background:#e5a800}
.td-btn:disabled{opacity:0.5;cursor:not-allowed}
.td-btn-outline{background:transparent;border:2px solid #E5E7EB;color:#374151}
.td-btn-outline:hover{border-color:#FCB900;background:#FFFBEB}
.td-stat{background:#fff;border-radius:16px;padding:20px;border:1px solid #E5E7EB}
.td-stat-value{font-size:28px;font-weight:800;color:#0E0F11}
.td-stat-label{font-size:14px;color:#6B7280;margin-top:4px}
.td-session{display:flex;align-items:center;gap:16px;padding:16px;background:#F9FAFB;border-radius:14px;margin-bottom:12px}
.td-session-date{width:48px;height:56px;background:#0E0F11;border-radius:10px;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff}
.td-session-day{font-size:20px;font-weight:800;color:#FCB900}
.td-session-month{font-size:10px;text-transform:uppercase;color:#9CA3AF}
.td-alert{padding:16px;background:#FEF3C7;border:1px solid #FCD34D;border-radius:12px;margin-bottom:20px;display:flex;align-items:flex-start;gap:12px}
.td-alert-success{background:#D1FAE5;border-color:#6EE7B7}
.td-alert-error{background:#FEE2E2;border-color:#FCA5A5}
.td-sync-item{display:flex;align-items:center;gap:14px;padding:16px;background:#F9FAFB;border-radius:12px;margin-bottom:12px}
.td-sync-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center}
@media(max-width:1024px){.td-grid{grid-template-columns:1fr}}
@media(max-width:640px){
    .td-header-inner{flex-direction:column;text-align:center}
    .td-avail-row{flex-wrap:wrap}
    .td-avail-day{width:100%;margin-bottom:8px}
    .td-avail-times{width:100%}
    .td-time-input{flex:1}
}
</style>

<div class="td">
    <!-- Header -->
    <div class="td-header">
        <div class="td-header-inner">
            <a href="<?php echo esc_url(home_url('/training/')); ?>">
                <img src="<?php echo esc_url($logo_url); ?>" class="td-logo" alt="PTP Soccer">
            </a>
            <img src="<?php echo esc_url($photo); ?>" class="td-avatar" alt="">
            <div style="flex:1">
                <div class="td-name"><?php echo esc_html($trainer->display_name); ?></div>
                <div class="td-role">Trainer Dashboard</div>
            </div>
            <a href="<?php echo esc_url($profile_url); ?>" class="td-btn td-btn-outline" style="color:#fff;border-color:rgba(255,255,255,0.3)" target="_blank">View Profile →</a>
        </div>
    </div>

    <div class="td-main">
        <!-- Tabs -->
        <div class="td-tabs">
            <a href="?tab=schedule" class="td-tab <?php echo $active_tab === 'schedule' ? 'active' : ''; ?>">📅 Schedule</a>
            <a href="?tab=sessions" class="td-tab <?php echo $active_tab === 'sessions' ? 'active' : ''; ?>">⚽ Sessions</a>
            <a href="?tab=earnings" class="td-tab <?php echo $active_tab === 'earnings' ? 'active' : ''; ?>">💰 Earnings</a>
            <a href="<?php echo home_url('/messages/'); ?>" class="td-tab">💬 Messages <?php if($unread_count): ?><span style="background:#EF4444;color:#fff;padding:2px 8px;border-radius:10px;font-size:12px;margin-left:6px"><?php echo $unread_count; ?></span><?php endif; ?></a>
            <a href="<?php echo home_url('/trainer-onboarding/?edit=1'); ?>" class="td-tab">⚙️ Profile</a>
        </div>

        <?php if ($active_tab === 'schedule'): ?>
        <!-- SCHEDULE TAB -->
        <div class="td-grid">
            <div>
                <!-- Weekly Availability -->
                <div class="td-card">
                    <div class="td-card-title">
                        <span>📅</span> Weekly Availability
                        <span id="save-status" style="margin-left:auto;font-size:13px;font-weight:500;color:#6B7280"></span>
                    </div>
                    
                    <p style="color:#6B7280;font-size:14px;margin-bottom:20px">Set your available hours for each day. Parents will only be able to book during these times.</p>
                    
                    <div id="availability-grid">
                        <?php foreach ($days as $i => $day): 
                            $slot = $avail_by_day[$i] ?? null;
                            $is_active = $slot && $slot->is_active;
                            $start_time = $slot ? substr($slot->start_time, 0, 5) : '16:00';
                            $end_time = $slot ? substr($slot->end_time, 0, 5) : '20:00';
                        ?>
                        <div class="td-avail-row" data-day="<?php echo $i; ?>">
                            <label class="td-avail-toggle">
                                <input type="checkbox" class="avail-toggle" data-day="<?php echo $i; ?>" <?php checked($is_active); ?>>
                                <span class="td-avail-toggle-track"></span>
                            </label>
                            <div class="td-avail-day"><?php echo esc_html($day_names[$i]); ?></div>
                            <div class="td-avail-times <?php echo !$is_active ? 'disabled' : ''; ?>">
                                <input type="time" class="td-time-input avail-start" data-day="<?php echo $i; ?>" value="<?php echo esc_attr($start_time); ?>" <?php echo !$is_active ? 'disabled' : ''; ?>>
                                <span style="color:#9CA3AF">to</span>
                                <input type="time" class="td-time-input avail-end" data-day="<?php echo $i; ?>" value="<?php echo esc_attr($end_time); ?>" <?php echo !$is_active ? 'disabled' : ''; ?>>
                            </div>
                            <div class="td-avail-status <?php echo $is_active ? 'on' : ''; ?>"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap">
                        <button type="button" id="save-availability-btn" class="td-btn" style="display:none">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <!-- Calendar Sync -->
                <div class="td-card">
                    <div class="td-card-title"><span>🔗</span> Calendar Sync</div>
                    <p style="color:#6B7280;font-size:14px;margin-bottom:20px">Connect your calendar to automatically block times when you're busy.</p>
                    
                    <!-- Google Calendar -->
                    <div class="td-sync-item">
                        <div class="td-sync-icon" style="background:#E8F0FE">
                            <svg width="24" height="24" viewBox="0 0 24 24"><path d="M18 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2z" fill="#4285F4"/><path d="M12 8v8M8 12h8" stroke="#fff" stroke-width="2"/></svg>
                        </div>
                        <div style="flex:1">
                            <div style="font-weight:600;font-size:15px">Google Calendar</div>
                            <div style="font-size:13px;color:#6B7280">
                                <?php if ($calendar_status['google']['connected']): ?>
                                    ✓ Connected
                                <?php else: ?>
                                    Sync sessions automatically
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($calendar_status['google']['connected']): ?>
                            <button type="button" onclick="disconnectGoogle()" class="td-btn td-btn-outline" style="padding:10px 16px;font-size:13px">Disconnect</button>
                        <?php else: ?>
                            <button type="button" onclick="connectGoogle()" class="td-btn" style="padding:10px 16px;font-size:13px">Connect</button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Apple Calendar -->
                    <div class="td-sync-item">
                        <div class="td-sync-icon" style="background:#FFE5E5">
                            <svg width="24" height="24" viewBox="0 0 24 24"><rect width="24" height="24" rx="5" fill="#FF3B30"/><text x="12" y="17" text-anchor="middle" fill="#fff" font-size="10" font-weight="bold">31</text></svg>
                        </div>
                        <div style="flex:1">
                            <div style="font-weight:600;font-size:15px">Apple / Outlook</div>
                            <div style="font-size:13px;color:#6B7280">Subscribe via ICS feed</div>
                        </div>
                        <button type="button" onclick="copyICS()" class="td-btn td-btn-outline" style="padding:10px 16px;font-size:13px" id="copy-ics-btn">Copy Link</button>
                    </div>
                    <input type="hidden" id="ics-url" value="<?php echo esc_attr($calendar_status['ics_url'] ?? ''); ?>">
                </div>

                <!-- Quick Stats -->
                <div class="td-card">
                    <div class="td-card-title"><span>📊</span> This Month</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="td-stat">
                            <div class="td-stat-value">$<?php echo number_format($earnings['this_month'] ?? 0); ?></div>
                            <div class="td-stat-label">Earned</div>
                        </div>
                        <div class="td-stat">
                            <div class="td-stat-value"><?php echo count($upcoming); ?></div>
                            <div class="td-stat-label">Sessions</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($active_tab === 'sessions'): ?>
        <!-- SESSIONS TAB -->
        <div class="td-card">
            <div class="td-card-title"><span>⚽</span> Upcoming Sessions</div>
            <?php if (empty($upcoming)): ?>
            <div style="text-align:center;padding:40px 20px;color:#6B7280">
                <div style="font-size:48px;margin-bottom:16px">📅</div>
                <p style="font-size:16px;margin:0 0 8px">No upcoming sessions</p>
                <p style="font-size:14px;margin:0">Sessions will appear here once parents book with you</p>
            </div>
            <?php else: ?>
            <?php foreach ($upcoming as $session): 
                $session_date = strtotime($session->session_date ?? $session->date ?? 'now');
            ?>
            <div class="td-session">
                <div class="td-session-date">
                    <div class="td-session-day"><?php echo date('j', $session_date); ?></div>
                    <div class="td-session-month"><?php echo date('M', $session_date); ?></div>
                </div>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:16px;margin-bottom:4px"><?php echo esc_html($session->player_name ?? $session->parent_name ?? 'Player'); ?></div>
                    <div style="font-size:14px;color:#6B7280">
                        <?php echo date('g:i A', $session_date); ?> • <?php echo esc_html($session->location ?? 'TBD'); ?>
                    </div>
                </div>
                <span style="padding:8px 14px;background:<?php echo ($session->status ?? '') === 'confirmed' ? '#D1FAE5' : '#FEF3C7'; ?>;color:<?php echo ($session->status ?? '') === 'confirmed' ? '#065F46' : '#92400E'; ?>;border-radius:8px;font-size:13px;font-weight:600"><?php echo ucfirst($session->status ?? 'pending'); ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php elseif ($active_tab === 'earnings'): ?>
        <!-- EARNINGS TAB -->
        <div class="td-grid">
            <div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
                    <div class="td-stat">
                        <div class="td-stat-value">$<?php echo number_format($earnings['this_month'] ?? 0); ?></div>
                        <div class="td-stat-label">This Month</div>
                    </div>
                    <div class="td-stat">
                        <div class="td-stat-value">$<?php echo number_format($earnings['total_earnings'] ?? 0); ?></div>
                        <div class="td-stat-label">Total Earned</div>
                    </div>
                    <div class="td-stat">
                        <div class="td-stat-value">$<?php echo number_format($earnings['pending_payout'] ?? 0); ?></div>
                        <div class="td-stat-label">Pending</div>
                    </div>
                </div>

                <div class="td-card">
                    <div class="td-card-title"><span>🏦</span> Payout Settings</div>
                    <?php if ($stripe_connected): ?>
                    <div class="td-alert td-alert-success">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <div>
                            <strong style="color:#065F46">Bank Account Connected</strong>
                            <p style="margin:4px 0 0;font-size:14px;color:#065F46">Payouts are sent automatically after sessions complete.</p>
                        </div>
                    </div>
                    <button type="button" onclick="openStripeDashboard()" class="td-btn td-btn-outline" style="margin-top:12px">Manage in Stripe →</button>
                    <?php else: ?>
                    <div class="td-alert">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <div>
                            <strong style="color:#92400E">Connect Bank Account</strong>
                            <p style="margin:4px 0 0;font-size:14px;color:#92400E">Set up your payout method to receive earnings.</p>
                        </div>
                    </div>
                    <button type="button" onclick="connectStripe()" class="td-btn" style="margin-top:12px">Connect Bank Account</button>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <div class="td-card">
                    <div class="td-card-title"><span>📜</span> How Payouts Work</div>
                    <div style="font-size:14px;color:#4B5563;line-height:1.7">
                        <p><strong>Session Complete:</strong> After you complete a session, the payment moves to "pending".</p>
                        <p style="margin-top:12px"><strong>Processing:</strong> Payouts are processed within 2-3 business days.</p>
                        <p style="margin-top:12px"><strong>Your Share:</strong> You receive 80% of the session price. PTP retains 20% for platform costs.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    const nonce = '<?php echo wp_create_nonce('ptp_nonce'); ?>';
    let hasChanges = false;

    // Toggle availability day
    document.querySelectorAll('.avail-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const day = this.dataset.day;
            const row = this.closest('.td-avail-row');
            const timesDiv = row.querySelector('.td-avail-times');
            const startInput = row.querySelector('.avail-start');
            const endInput = row.querySelector('.avail-end');
            const status = row.querySelector('.td-avail-status');
            
            if (this.checked) {
                timesDiv.classList.remove('disabled');
                startInput.disabled = false;
                endInput.disabled = false;
                status.classList.add('on');
            } else {
                timesDiv.classList.add('disabled');
                startInput.disabled = true;
                endInput.disabled = true;
                status.classList.remove('on');
            }
            showSaveButton();
        });
    });

    // Time inputs change
    document.querySelectorAll('.avail-start, .avail-end').forEach(input => {
        input.addEventListener('change', showSaveButton);
    });

    function showSaveButton() {
        hasChanges = true;
        document.getElementById('save-availability-btn').style.display = 'inline-flex';
        document.getElementById('save-status').textContent = 'Unsaved changes';
        document.getElementById('save-status').style.color = '#D97706';
    }

    // SAVE AVAILABILITY - BULLETPROOF
    document.getElementById('save-availability-btn')?.addEventListener('click', async function() {
        const btn = this;
        const statusEl = document.getElementById('save-status');
        
        btn.disabled = true;
        btn.innerHTML = '<svg class="spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Saving...';
        statusEl.textContent = 'Saving...';
        statusEl.style.color = '#6B7280';

        let success = 0;
        let errors = [];

        // Save each day
        for (let day = 0; day <= 6; day++) {
            const row = document.querySelector(`.td-avail-row[data-day="${day}"]`);
            if (!row) continue;

            const toggle = row.querySelector('.avail-toggle');
            const startInput = row.querySelector('.avail-start');
            const endInput = row.querySelector('.avail-end');

            const enabled = toggle.checked ? '1' : '0';
            const start = startInput.value || '16:00';
            const end = endInput.value || '20:00';

            try {
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'ptp_save_trainer_schedule',
                        nonce: nonce,
                        day: day.toString(),
                        enabled: enabled,
                        start: start,
                        end: end
                    }),
                    credentials: 'same-origin'
                });

                const data = await response.json();
                if (data.success) {
                    success++;
                } else {
                    errors.push('Day ' + day + ': ' + (data.data?.message || 'Failed'));
                }
            } catch (e) {
                errors.push('Day ' + day + ': Network error');
            }
        }

        if (errors.length === 0) {
            btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Saved!';
            btn.style.background = '#10B981';
            statusEl.textContent = 'All changes saved';
            statusEl.style.color = '#059669';
            hasChanges = false;

            setTimeout(() => {
                btn.style.display = 'none';
                btn.style.background = '';
                btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Save Changes';
                btn.disabled = false;
            }, 2000);
        } else {
            btn.innerHTML = 'Save Changes';
            btn.disabled = false;
            statusEl.textContent = 'Some days failed to save';
            statusEl.style.color = '#DC2626';
            console.error('Save errors:', errors);
            alert('Error saving some days:\n' + errors.join('\n'));
        }
    });

    // Google Calendar
    window.connectGoogle = function() {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=ptp_connect_google_calendar&nonce=' + nonce,
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data.auth_url) {
                window.location.href = data.data.auth_url;
            } else {
                alert(data.data?.message || 'Could not connect to Google. Please try again.');
            }
        });
    };

    window.disconnectGoogle = function() {
        if (!confirm('Disconnect Google Calendar?')) return;
        fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=ptp_disconnect_google_calendar&nonce=' + nonce,
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    };

    // ICS URL
    window.copyICS = function() {
        const url = document.getElementById('ics-url').value;
        if (!url) {
            alert('ICS feed not available. Please contact support.');
            return;
        }
        navigator.clipboard.writeText(url).then(() => {
            const btn = document.getElementById('copy-ics-btn');
            btn.textContent = 'Copied!';
            setTimeout(() => btn.textContent = 'Copy Link', 2000);
        });
    };

    // Stripe
    window.connectStripe = function() {
        const btn = event.target;
        btn.disabled = true;
        btn.textContent = 'Connecting...';
        
        fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=ptp_create_stripe_connect_account&nonce=' + nonce,
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data.url) {
                window.location.href = data.data.url;
            } else {
                alert(data.data?.message || 'Could not connect to Stripe');
                btn.disabled = false;
                btn.textContent = 'Connect Bank Account';
            }
        });
    };

    window.openStripeDashboard = function() {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=ptp_get_stripe_dashboard_link&nonce=' + nonce,
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data.url) {
                window.open(data.data.url, '_blank');
            }
        });
    };

    // Add spin animation
    const style = document.createElement('style');
    style.textContent = '@keyframes spin{to{transform:rotate(360deg)}}.spin{animation:spin 1s linear infinite}';
    document.head.appendChild(style);
})();
</script>

<?php get_footer(); ?>
