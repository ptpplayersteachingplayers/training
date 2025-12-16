<?php
/**
 * PTP Schedule Calendar
 * Admin calendar for VA scheduling - shows both admin-created sessions and parent bookings
 */

defined('ABSPATH') || exit;

class PTP_Schedule_Calendar {
    
    private static $instance = null;
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function init() {
        self::instance();
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu'), 30);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_ptp_schedule_get_events', array($this, 'ajax_get_events'));
        add_action('wp_ajax_ptp_schedule_create_session', array($this, 'ajax_create_session'));
        add_action('wp_ajax_ptp_schedule_update_session', array($this, 'ajax_update_session'));
        add_action('wp_ajax_ptp_schedule_delete_session', array($this, 'ajax_delete_session'));
        add_action('wp_ajax_ptp_schedule_search_customers', array($this, 'ajax_search_customers'));
        add_action('wp_ajax_ptp_schedule_search_players', array($this, 'ajax_search_players'));
        add_action('wp_ajax_ptp_schedule_get_trainer_stats', array($this, 'ajax_get_trainer_stats'));
    }
    
    public function add_menu() {
        add_submenu_page(
            'ptp-training',
            'Schedule Calendar',
            '📅 Schedule Calendar',
            'edit_posts',
            'ptp-schedule-calendar',
            array($this, 'render_calendar')
        );
    }
    
    public function enqueue_assets($hook) {
        if (strpos($hook, 'ptp-schedule-calendar') === false) return;
        
        // FullCalendar
        wp_enqueue_style('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css', array(), '6.1.10');
        wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js', array(), '6.1.10', true);
        
        // Plugin assets
        wp_enqueue_style('ptp-schedule', PTP_PLUGIN_URL . 'assets/css/schedule.css', array(), PTP_VERSION);
        wp_enqueue_script('ptp-schedule', PTP_PLUGIN_URL . 'assets/js/schedule.js', array('jquery', 'fullcalendar'), PTP_VERSION, true);
        
        wp_localize_script('ptp-schedule', 'PTPSchedule', array(
            'ajax' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ptp_schedule_nonce'),
        ));
    }
    
    public function render_calendar() {
        global $wpdb;
        
        // Get active trainers
        $trainers = $wpdb->get_results(
            "SELECT id, user_id, display_name, photo_url, status 
             FROM {$wpdb->prefix}ptp_trainers 
             WHERE status = 'active' 
             ORDER BY display_name"
        );
        
        // Trainer colors
        $colors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#6366f1'];
        
        ?>
        <div class="wrap ptp-schedule-wrap">
            <div class="ptp-schedule-layout">
                <!-- Sidebar -->
                <div class="ptp-schedule-sidebar">
                    <!-- Trainers -->
                    <div class="ptp-sidebar-card">
                        <div class="ptp-sidebar-header">
                            <h3>Trainers</h3>
                            <div class="ptp-view-toggle">
                                <button type="button" data-view="list" class="active">☰</button>
                                <button type="button" data-view="grid">▦</button>
                            </div>
                        </div>
                        
                        <div class="ptp-trainer-actions">
                            <button type="button" id="selectAll" class="active">All</button>
                            <button type="button" id="selectNone">None</button>
                        </div>
                        
                        <div id="focusBanner" class="ptp-focus-banner">
                            <span id="focusName"></span>
                            <button type="button" id="exitFocus">&times;</button>
                        </div>
                        
                        <div id="trainerList" class="ptp-trainer-list">
                            <?php if (empty($trainers)): ?>
                            <div class="ptp-empty">No active trainers found.</div>
                            <?php else: ?>
                            <?php foreach ($trainers as $i => $t): 
                                $color = $colors[$i % 10];
                                $initials = strtoupper(substr($t->display_name, 0, 1));
                            ?>
                            <div class="ptp-trainer-row selected" 
                                 data-id="<?php echo $t->id; ?>" 
                                 data-name="<?php echo esc_attr($t->display_name); ?>"
                                 data-color="<?php echo $color; ?>">
                                <input type="checkbox" value="<?php echo $t->id; ?>" checked>
                                <?php if ($t->photo_url): ?>
                                <img src="<?php echo esc_url($t->photo_url); ?>" class="ptp-trainer-photo" alt="">
                                <?php else: ?>
                                <div class="ptp-trainer-initials" style="background:<?php echo $color; ?>"><?php echo $initials; ?></div>
                                <?php endif; ?>
                                <div class="ptp-trainer-info">
                                    <span class="ptp-trainer-name"><?php echo esc_html($t->display_name); ?></span>
                                    <span class="ptp-trainer-count">0 sessions</span>
                                </div>
                                <div class="ptp-trainer-dot" style="background:<?php echo $color; ?>"></div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <p class="ptp-tip">Double-click trainer for focus mode</p>
                    </div>
                    
                    <!-- Status Legend -->
                    <div class="ptp-sidebar-card">
                        <div class="ptp-sidebar-header">
                            <h3>Status</h3>
                        </div>
                        <div class="ptp-status-legend">
                            <div class="ptp-status-item"><span class="dot pending"></span>Pending</div>
                            <div class="ptp-status-item"><span class="dot confirmed"></span>Confirmed</div>
                            <div class="ptp-status-item"><span class="dot scheduled"></span>Scheduled</div>
                            <div class="ptp-status-item"><span class="dot completed"></span>Completed</div>
                            <div class="ptp-status-item"><span class="dot cancelled"></span>Cancelled</div>
                            <div class="ptp-status-item"><span class="dot no_show"></span>No Show</div>
                        </div>
                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #6b7280;">
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 2px; background: #dbeafe; border: 1px solid #3b82f6;"></span>
                                Parent Booking
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 2px; background: #fef3c7; border: 1px solid #f59e0b;"></span>
                                Admin Session
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="ptp-sidebar-card">
                        <div class="ptp-sidebar-header">
                            <h3>Quick Actions</h3>
                        </div>
                        <div class="ptp-quick-actions">
                            <button type="button" id="btnNewSession" class="ptp-btn primary">+ New Session</button>
                            <button type="button" id="btnToday" class="ptp-btn secondary">Today</button>
                        </div>
                    </div>
                </div>
                
                <!-- Calendar -->
                <div class="ptp-schedule-main">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
        
        <!-- Session Modal -->
        <div id="sessionModal" class="ptp-modal">
            <div class="ptp-modal-content">
                <div class="ptp-modal-header">
                    <h2 id="modalTitle">New Session</h2>
                    <span id="sessionSource" class="ptp-session-source"></span>
                    <button type="button" class="ptp-modal-close">&times;</button>
                </div>
                <form id="sessionForm">
                    <input type="hidden" id="sessionId" name="id">
                    <input type="hidden" id="sessionType" name="source" value="admin">
                    
                    <div class="ptp-form-section">
                        <div class="ptp-form-section-title">Session Details</div>
                        <div class="ptp-form-row">
                            <div class="ptp-field">
                                <label>Trainer <span class="req">*</span></label>
                                <select id="trainerId" name="trainer_id" required>
                                    <option value="">Select trainer...</option>
                                    <?php foreach ($trainers as $t): ?>
                                    <option value="<?php echo $t->id; ?>"><?php echo esc_html($t->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="ptp-field">
                                <label>Status</label>
                                <select id="sessionStatus" name="session_status">
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="scheduled" selected>Scheduled</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="no_show">No Show</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="ptp-field">
                            <label>Parent</label>
                            <div class="ptp-search-wrap">
                                <input type="text" id="customerSearch" placeholder="Search parents by name or email...">
                                <input type="hidden" id="customerId" name="customer_id">
                                <input type="hidden" id="parentId" name="parent_id">
                                <div id="customerResults" class="ptp-search-results"></div>
                            </div>
                            <div id="selectedCustomer" class="ptp-selected-item" style="display:none;">
                                <span id="customerName"></span>
                                <button type="button" id="clearCustomer">&times;</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ptp-form-section">
                        <div class="ptp-form-section-title">Player Info</div>
                        <div class="ptp-field">
                            <label>Select Player</label>
                            <select id="playerId" name="player_id">
                                <option value="">-- Select or enter manually --</option>
                            </select>
                        </div>
                        <div class="ptp-form-row">
                            <div class="ptp-field flex-2">
                                <label>Player Name <span class="req">*</span></label>
                                <input type="text" id="playerName" name="player_name" required>
                            </div>
                            <div class="ptp-field flex-1">
                                <label>Age</label>
                                <input type="number" id="playerAge" name="player_age" min="4" max="18">
                            </div>
                        </div>
                    </div>
                    
                    <div class="ptp-form-section">
                        <div class="ptp-form-section-title">Schedule</div>
                        <div class="ptp-form-row">
                            <div class="ptp-field">
                                <label>Date <span class="req">*</span></label>
                                <input type="date" id="sessionDate" name="session_date" required>
                            </div>
                            <div class="ptp-field">
                                <label>Start Time <span class="req">*</span></label>
                                <input type="time" id="startTime" name="start_time" required>
                            </div>
                            <div class="ptp-field flex-1">
                                <label>Duration</label>
                                <select id="duration" name="duration_minutes">
                                    <option value="30">30m</option>
                                    <option value="45">45m</option>
                                    <option value="60" selected>1hr</option>
                                    <option value="90">1.5hr</option>
                                    <option value="120">2hr</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="ptp-field">
                            <label>Location</label>
                            <input type="text" id="locationText" name="location_text" placeholder="Field or address">
                        </div>
                    </div>
                    
                    <div class="ptp-form-section">
                        <div class="ptp-form-section-title">Pricing</div>
                        <div class="ptp-form-row">
                            <div class="ptp-field">
                                <label>Type</label>
                                <select id="sessionTypeSelect" name="session_type">
                                    <option value="1on1">1-on-1</option>
                                    <option value="small_group">Small Group (2-4)</option>
                                    <option value="group">Group (5+)</option>
                                </select>
                            </div>
                            <div class="ptp-field flex-1">
                                <label>Price ($)</label>
                                <input type="number" id="price" name="price" step="0.01" min="0">
                            </div>
                            <div class="ptp-field">
                                <label>Payment</label>
                                <select id="paymentStatus" name="payment_status">
                                    <option value="unpaid">Unpaid</option>
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ptp-field">
                        <label>Internal Notes</label>
                        <textarea id="internalNotes" name="internal_notes" rows="2"></textarea>
                    </div>
                    
                    <div class="ptp-modal-footer">
                        <button type="button" id="deleteSession" class="ptp-btn danger">Delete</button>
                        <div class="ptp-modal-footer-right">
                            <button type="button" class="ptp-btn secondary ptp-modal-cancel">Cancel</button>
                            <button type="submit" class="ptp-btn primary">Save Session</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        // Load players when parent is selected
        document.getElementById('customerId').addEventListener('change', function() {
            loadPlayersForParent(this.value);
        });
        
        function loadPlayersForParent(parentId) {
            if (!parentId) {
                document.getElementById('playerId').innerHTML = '<option value="">-- Select or enter manually --</option>';
                return;
            }
            
            fetch(PTPSchedule.ajax + '?action=ptp_schedule_search_players&nonce=' + PTPSchedule.nonce + '&parent_id=' + parentId)
                .then(r => r.json())
                .then(data => {
                    let html = '<option value="">-- Select or enter manually --</option>';
                    if (data.success && data.data.length) {
                        data.data.forEach(p => {
                            html += `<option value="${p.id}" data-name="${p.name}" data-age="${p.age || ''}">${p.name}${p.age ? ' (Age ' + p.age + ')' : ''}</option>`;
                        });
                    }
                    document.getElementById('playerId').innerHTML = html;
                });
        }
        
        document.getElementById('playerId').addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt.value) {
                document.getElementById('playerName').value = opt.dataset.name || '';
                document.getElementById('playerAge').value = opt.dataset.age || '';
            }
        });
        </script>
        <?php
    }
    
    // ===================
    // AJAX HANDLERS
    // ===================
    
    public function ajax_get_events() {
        check_ajax_referer('ptp_schedule_nonce', 'nonce');
        
        global $wpdb;
        
        $start = sanitize_text_field($_GET['start'] ?? '');
        $end = sanitize_text_field($_GET['end'] ?? '');
        $trainer_ids = isset($_GET['trainers']) ? sanitize_text_field($_GET['trainers']) : '';
        $trainer_ids = $trainer_ids ? array_map('intval', explode(',', $trainer_ids)) : array();
        
        $events = array();
        
        // Get admin-created sessions from ptp_sessions
        $sql = "SELECT s.*, t.display_name as trainer_name, u.display_name as customer_name
                FROM {$wpdb->prefix}ptp_sessions s
                LEFT JOIN {$wpdb->prefix}ptp_trainers t ON s.trainer_id = t.id
                LEFT JOIN {$wpdb->users} u ON s.customer_id = u.ID
                WHERE s.session_date >= %s AND s.session_date <= %s";
        
        $params = array($start, $end);
        
        if (!empty($trainer_ids)) {
            $placeholders = implode(',', array_fill(0, count($trainer_ids), '%d'));
            $sql .= " AND s.trainer_id IN ($placeholders)";
            $params = array_merge($params, $trainer_ids);
        }
        
        $sql .= " ORDER BY s.session_date, s.start_time";
        
        $sessions = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        foreach ($sessions as $s) {
            $events[] = array(
                'id' => 'session_' . $s->id,
                'title' => ($s->player_name ?: 'Session') . ' - ' . ($s->trainer_name ?: 'Unassigned'),
                'start' => $s->session_date . 'T' . $s->start_time,
                'end' => $s->session_date . 'T' . $s->end_time,
                'className' => 'status-' . $s->session_status . ' source-admin',
                'extendedProps' => array(
                    'source' => 'admin',
                    'session_id' => $s->id,
                    'trainer_id' => $s->trainer_id,
                    'trainer_name' => $s->trainer_name,
                    'customer_id' => $s->customer_id,
                    'customer_name' => $s->customer_name,
                    'parent_id' => $s->parent_id,
                    'player_id' => $s->player_id,
                    'player_name' => $s->player_name,
                    'player_age' => $s->player_age,
                    'session_status' => $s->session_status,
                    'payment_status' => $s->payment_status,
                    'session_type' => $s->session_type,
                    'location_text' => $s->location_text,
                    'price' => $s->price,
                    'internal_notes' => $s->internal_notes,
                    'duration_minutes' => $s->duration_minutes,
                ),
            );
        }
        
        // Get parent-booked sessions from ptp_bookings
        $sql2 = "SELECT b.*, t.display_name as trainer_name, 
                        p.name as player_name, p.age as player_age,
                        pa.user_id as customer_id,
                        u.display_name as customer_name
                 FROM {$wpdb->prefix}ptp_bookings b
                 LEFT JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
                 LEFT JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
                 LEFT JOIN {$wpdb->prefix}ptp_parents pa ON b.parent_id = pa.id
                 LEFT JOIN {$wpdb->users} u ON pa.user_id = u.ID
                 WHERE b.session_date >= %s AND b.session_date <= %s";
        
        $params2 = array($start, $end);
        
        if (!empty($trainer_ids)) {
            $sql2 .= " AND b.trainer_id IN ($placeholders)";
            $params2 = array_merge($params2, $trainer_ids);
        }
        
        $sql2 .= " ORDER BY b.session_date, b.start_time";
        
        $bookings = $wpdb->get_results($wpdb->prepare($sql2, $params2));
        
        foreach ($bookings as $b) {
            // Map booking status to session status
            $status_map = array(
                'pending' => 'pending',
                'confirmed' => 'confirmed',
                'completed' => 'completed',
                'cancelled' => 'cancelled',
                'no_show' => 'no_show',
            );
            $status = $status_map[$b->status] ?? 'scheduled';
            
            $events[] = array(
                'id' => 'booking_' . $b->id,
                'title' => ($b->player_name ?: 'Booking') . ' - ' . ($b->trainer_name ?: 'Unassigned'),
                'start' => $b->session_date . 'T' . $b->start_time,
                'end' => $b->session_date . 'T' . $b->end_time,
                'className' => 'status-' . $status . ' source-booking',
                'extendedProps' => array(
                    'source' => 'booking',
                    'booking_id' => $b->id,
                    'booking_number' => $b->booking_number,
                    'trainer_id' => $b->trainer_id,
                    'trainer_name' => $b->trainer_name,
                    'customer_id' => $b->customer_id,
                    'customer_name' => $b->customer_name,
                    'parent_id' => $b->parent_id,
                    'player_id' => $b->player_id,
                    'player_name' => $b->player_name,
                    'player_age' => $b->player_age,
                    'session_status' => $status,
                    'payment_status' => $b->payment_status,
                    'session_type' => '1on1',
                    'location_text' => $b->location,
                    'price' => $b->total_amount,
                    'internal_notes' => $b->notes,
                    'duration_minutes' => $b->duration_minutes,
                ),
            );
        }
        
        wp_send_json($events);
    }
    
    public function ajax_create_session() {
        check_ajax_referer('ptp_schedule_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }
        
        global $wpdb;
        
        $duration = intval($_POST['duration_minutes'] ?? 60);
        $start_time = sanitize_text_field($_POST['start_time']);
        $end_time = date('H:i:s', strtotime($start_time) + ($duration * 60));
        
        $price = floatval($_POST['price'] ?? 0);
        $platform_fee = floatval(get_option('ptp_platform_fee', 20)) / 100;
        $trainer_payout = $price * (1 - $platform_fee);
        
        $data = array(
            'trainer_id' => intval($_POST['trainer_id']),
            'customer_id' => intval($_POST['customer_id']) ?: null,
            'parent_id' => intval($_POST['parent_id']) ?: null,
            'player_id' => intval($_POST['player_id']) ?: null,
            'player_name' => sanitize_text_field($_POST['player_name']),
            'player_age' => intval($_POST['player_age']) ?: null,
            'session_date' => sanitize_text_field($_POST['session_date']),
            'start_time' => $start_time,
            'end_time' => $end_time,
            'duration_minutes' => $duration,
            'session_status' => sanitize_text_field($_POST['session_status'] ?? 'scheduled'),
            'payment_status' => sanitize_text_field($_POST['payment_status'] ?? 'unpaid'),
            'session_type' => sanitize_text_field($_POST['session_type'] ?? '1on1'),
            'location_text' => sanitize_text_field($_POST['location_text'] ?? ''),
            'price' => $price,
            'trainer_payout' => $trainer_payout,
            'internal_notes' => sanitize_textarea_field($_POST['internal_notes'] ?? ''),
            'created_by' => get_current_user_id(),
        );
        
        $result = $wpdb->insert("{$wpdb->prefix}ptp_sessions", $data);
        
        if ($result) {
            wp_send_json_success(array('id' => $wpdb->insert_id));
        } else {
            wp_send_json_error('Failed to create session: ' . $wpdb->last_error);
        }
    }
    
    public function ajax_update_session() {
        check_ajax_referer('ptp_schedule_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }
        
        global $wpdb;
        
        $id = sanitize_text_field($_POST['id'] ?? '');
        $source = 'admin';
        
        // Parse ID to determine source
        if (strpos($id, 'booking_') === 0) {
            $source = 'booking';
            $id = intval(str_replace('booking_', '', $id));
        } else {
            $id = intval(str_replace('session_', '', $id));
        }
        
        $duration = intval($_POST['duration_minutes'] ?? 60);
        $start_time = sanitize_text_field($_POST['start_time']);
        $end_time = date('H:i:s', strtotime($start_time) + ($duration * 60));
        
        if ($source === 'booking') {
            // Update booking
            $status_map = array(
                'pending' => 'pending',
                'confirmed' => 'confirmed',
                'scheduled' => 'confirmed',
                'completed' => 'completed',
                'cancelled' => 'cancelled',
                'no_show' => 'no_show',
            );
            $status = $status_map[$_POST['session_status']] ?? 'pending';
            
            $data = array(
                'trainer_id' => intval($_POST['trainer_id']),
                'session_date' => sanitize_text_field($_POST['session_date']),
                'start_time' => $start_time,
                'end_time' => $end_time,
                'duration_minutes' => $duration,
                'status' => $status,
                'payment_status' => sanitize_text_field($_POST['payment_status'] ?? 'pending'),
                'location' => sanitize_text_field($_POST['location_text'] ?? ''),
                'notes' => sanitize_textarea_field($_POST['internal_notes'] ?? ''),
            );
            
            $result = $wpdb->update("{$wpdb->prefix}ptp_bookings", $data, array('id' => $id));
        } else {
            // Update session
            $price = floatval($_POST['price'] ?? 0);
            $platform_fee = floatval(get_option('ptp_platform_fee', 20)) / 100;
            $trainer_payout = $price * (1 - $platform_fee);
            
            $data = array(
                'trainer_id' => intval($_POST['trainer_id']),
                'customer_id' => intval($_POST['customer_id']) ?: null,
                'parent_id' => intval($_POST['parent_id']) ?: null,
                'player_id' => intval($_POST['player_id']) ?: null,
                'player_name' => sanitize_text_field($_POST['player_name']),
                'player_age' => intval($_POST['player_age']) ?: null,
                'session_date' => sanitize_text_field($_POST['session_date']),
                'start_time' => $start_time,
                'end_time' => $end_time,
                'duration_minutes' => $duration,
                'session_status' => sanitize_text_field($_POST['session_status'] ?? 'scheduled'),
                'payment_status' => sanitize_text_field($_POST['payment_status'] ?? 'unpaid'),
                'session_type' => sanitize_text_field($_POST['session_type'] ?? '1on1'),
                'location_text' => sanitize_text_field($_POST['location_text'] ?? ''),
                'price' => $price,
                'trainer_payout' => $trainer_payout,
                'internal_notes' => sanitize_textarea_field($_POST['internal_notes'] ?? ''),
            );
            
            $result = $wpdb->update("{$wpdb->prefix}ptp_sessions", $data, array('id' => $id));
        }
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to update');
        }
    }
    
    public function ajax_delete_session() {
        check_ajax_referer('ptp_schedule_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }
        
        global $wpdb;
        
        $id = sanitize_text_field($_POST['id'] ?? '');
        
        if (strpos($id, 'booking_') === 0) {
            // Cancel booking instead of delete
            $booking_id = intval(str_replace('booking_', '', $id));
            $result = $wpdb->update(
                "{$wpdb->prefix}ptp_bookings",
                array('status' => 'cancelled', 'cancelled_at' => current_time('mysql'), 'cancelled_by' => 'admin'),
                array('id' => $booking_id)
            );
        } else {
            $session_id = intval(str_replace('session_', '', $id));
            $result = $wpdb->delete("{$wpdb->prefix}ptp_sessions", array('id' => $session_id));
        }
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to delete');
        }
    }
    
    public function ajax_search_customers() {
        check_ajax_referer('ptp_schedule_nonce', 'nonce');
        
        global $wpdb;
        
        $q = sanitize_text_field($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            wp_send_json_success(array());
        }
        
        $like = '%' . $wpdb->esc_like($q) . '%';
        
        // Search parents with their user info
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT p.id as parent_id, p.user_id, u.display_name, u.user_email 
             FROM {$wpdb->prefix}ptp_parents p
             JOIN {$wpdb->users} u ON p.user_id = u.ID
             WHERE u.display_name LIKE %s OR u.user_email LIKE %s 
             ORDER BY u.display_name LIMIT 10",
            $like, $like
        ));
        
        wp_send_json_success($results);
    }
    
    public function ajax_search_players() {
        check_ajax_referer('ptp_schedule_nonce', 'nonce');
        
        global $wpdb;
        
        $parent_id = intval($_GET['parent_id'] ?? 0);
        
        if (!$parent_id) {
            wp_send_json_success(array());
        }
        
        $players = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, age, skill_level FROM {$wpdb->prefix}ptp_players 
             WHERE parent_id = %d AND is_active = 1 ORDER BY name",
            $parent_id
        ));
        
        wp_send_json_success($players);
    }
    
    public function ajax_get_trainer_stats() {
        check_ajax_referer('ptp_schedule_nonce', 'nonce');
        
        global $wpdb;
        
        $start = sanitize_text_field($_GET['start'] ?? date('Y-m-d'));
        $end = sanitize_text_field($_GET['end'] ?? date('Y-m-d'));
        
        // Count from both tables
        $session_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT trainer_id, COUNT(*) as count 
             FROM {$wpdb->prefix}ptp_sessions 
             WHERE session_date BETWEEN %s AND %s 
             GROUP BY trainer_id",
            $start, $end
        ));
        
        $booking_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT trainer_id, COUNT(*) as count 
             FROM {$wpdb->prefix}ptp_bookings 
             WHERE session_date BETWEEN %s AND %s 
             AND status != 'cancelled'
             GROUP BY trainer_id",
            $start, $end
        ));
        
        $result = array();
        foreach ($session_stats as $s) {
            $result[$s->trainer_id] = intval($s->count);
        }
        foreach ($booking_stats as $b) {
            $result[$b->trainer_id] = ($result[$b->trainer_id] ?? 0) + intval($b->count);
        }
        
        wp_send_json_success($result);
    }
}
