<?php
/**
 * PTP Admin Class
 */

defined('ABSPATH') || exit;

class PTP_Admin {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_head', array($this, 'admin_head_css'));
        
        // AJAX handlers
        add_action('wp_ajax_ptp_get_players', array($this, 'ajax_get_players'));
        add_action('wp_ajax_ptp_delete_record', array($this, 'ajax_delete_record'));
    }
    
    /**
     * AJAX: Get players by parent ID
     */
    public function ajax_get_players() {
        global $wpdb;
        $parent_id = intval($_GET['parent_id']);
        
        $players = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) as age 
             FROM {$wpdb->prefix}ptp_players 
             WHERE parent_id = %d AND is_active = 1 
             ORDER BY name",
            $parent_id
        ));
        
        wp_send_json_success($players);
    }
    
    /**
     * AJAX: Delete a record
     */
    public function ajax_delete_record() {
        check_ajax_referer('ptp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        global $wpdb;
        $table = sanitize_text_field($_POST['table']);
        $id = intval($_POST['id']);
        
        $allowed_tables = array('ptp_trainers', 'ptp_parents', 'ptp_players', 'ptp_bookings', 'ptp_applications');
        
        if (!in_array($table, $allowed_tables)) {
            wp_send_json_error('Invalid table');
        }
        
        $result = $wpdb->delete($wpdb->prefix . $table, array('id' => $id), array('%d'));
        
        if ($result) {
            wp_send_json_success('Deleted');
        } else {
            wp_send_json_error('Delete failed');
        }
    }
    
    /**
     * Output CSS in admin head - most reliable method
     */
    public function admin_head_css() {
        // Only on PTP pages
        if (!isset($_GET['page']) || strpos($_GET['page'], 'ptp') === false) {
            return;
        }
        
        echo '<style id="ptp-admin-critical-css">';
        $this->output_critical_css_content();
        echo '</style>';
    }
    
    public function add_menu() {
        add_menu_page(
            'PTP Training',
            'PTP Training', 
            'manage_options', 
            'ptp-dashboard', 
            array($this, 'dashboard_page'), 
            'dashicons-universal-access', 
            30
        );
        
        add_submenu_page('ptp-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'ptp-dashboard', array($this, 'dashboard_page'));
        add_submenu_page('ptp-dashboard', 'Schedule', 'Schedule', 'manage_options', 'ptp-schedule', array($this, 'schedule_page'));
        add_submenu_page('ptp-dashboard', 'Trainers', 'Trainers', 'manage_options', 'ptp-trainers', array($this, 'trainers_page'));
        add_submenu_page('ptp-dashboard', 'Applications', 'Applications', 'manage_options', 'ptp-applications', array($this, 'applications_page'));
        add_submenu_page('ptp-dashboard', 'Bookings', 'Bookings', 'manage_options', 'ptp-bookings', array($this, 'bookings_page'));
        add_submenu_page('ptp-dashboard', 'Parents', 'Parents', 'manage_options', 'ptp-parents', array($this, 'parents_page'));
        add_submenu_page('ptp-dashboard', 'Payouts', 'Payouts', 'manage_options', 'ptp-payouts', array($this, 'payouts_page'));
        add_submenu_page('ptp-dashboard', 'Messages', 'Messages', 'manage_options', 'ptp-messages', array($this, 'messages_page'));
        add_submenu_page('ptp-dashboard', 'Settings', 'Settings', 'manage_options', 'ptp-settings', array($this, 'settings_page'));
        add_submenu_page('ptp-dashboard', 'Tools', 'Tools', 'manage_options', 'ptp-tools', array($this, 'tools_page'));
    }
    
    public function register_settings() {
        register_setting('ptp_general', 'ptp_from_email');
        register_setting('ptp_general', 'ptp_platform_fee');
        register_setting('ptp_general', 'ptp_min_payout');
        register_setting('ptp_general', 'ptp_refund_window');
        register_setting('ptp_general', 'ptp_google_maps_api_key');
        
        register_setting('ptp_company', 'ptp_company_name');
        register_setting('ptp_company', 'ptp_company_ein');
        register_setting('ptp_company', 'ptp_company_address');
        register_setting('ptp_company', 'ptp_company_city');
        register_setting('ptp_company', 'ptp_company_state');
        register_setting('ptp_company', 'ptp_company_zip');
        register_setting('ptp_company', 'ptp_company_phone');
        register_setting('ptp_company', 'ptp_1099_threshold');
        register_setting('ptp_company', 'ptp_require_w9');
        register_setting('ptp_company', 'ptp_require_safesport');
        
        register_setting('ptp_twilio', 'ptp_twilio_sid');
        register_setting('ptp_twilio', 'ptp_twilio_token');
        register_setting('ptp_twilio', 'ptp_twilio_from');
        register_setting('ptp_twilio', 'ptp_sms_enabled');
        
        register_setting('ptp_stripe', 'ptp_stripe_test_mode');
        register_setting('ptp_stripe', 'ptp_stripe_test_publishable');
        register_setting('ptp_stripe', 'ptp_stripe_test_secret');
        register_setting('ptp_stripe', 'ptp_stripe_live_publishable');
        register_setting('ptp_stripe', 'ptp_stripe_live_secret');
        register_setting('ptp_stripe', 'ptp_stripe_webhook_secret');
        register_setting('ptp_stripe', 'ptp_stripe_connect_enabled');
        
        register_setting('ptp_notifications', 'ptp_email_booking_confirmation');
        register_setting('ptp_notifications', 'ptp_email_session_reminder');
        register_setting('ptp_notifications', 'ptp_sms_booking_confirmation');
        register_setting('ptp_notifications', 'ptp_sms_session_reminder');
        register_setting('ptp_notifications', 'ptp_fcm_server_key');
    }
    
    private function get_stats() {
        global $wpdb;
        
        // Safely get stats - tables might not exist
        $stats = array(
            'trainers' => 0,
            'parents' => 0,
            'players' => 0,
            'bookings' => 0,
            'upcoming' => 0,
            'completed' => 0,
            'revenue' => 0,
            'pending_apps' => 0,
        );
        
        // Check if tables exist first
        $trainers_table = $wpdb->prefix . 'ptp_trainers';
        if ($wpdb->get_var("SHOW TABLES LIKE '$trainers_table'") == $trainers_table) {
            $stats['trainers'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ptp_trainers WHERE status = 'active'") ?: 0;
        }
        
        $parents_table = $wpdb->prefix . 'ptp_parents';
        if ($wpdb->get_var("SHOW TABLES LIKE '$parents_table'") == $parents_table) {
            $stats['parents'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ptp_parents") ?: 0;
        }
        
        $players_table = $wpdb->prefix . 'ptp_players';
        if ($wpdb->get_var("SHOW TABLES LIKE '$players_table'") == $players_table) {
            $stats['players'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ptp_players WHERE is_active = 1") ?: 0;
        }
        
        $bookings_table = $wpdb->prefix . 'ptp_bookings';
        if ($wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") == $bookings_table) {
            $stats['bookings'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ptp_bookings") ?: 0;
            $stats['completed'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ptp_bookings WHERE status = 'completed'") ?: 0;
            $stats['upcoming'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ptp_bookings WHERE status = 'confirmed' AND session_date >= %s", current_time('Y-m-d'))) ?: 0;
            $stats['revenue'] = $wpdb->get_var("SELECT COALESCE(SUM(total_amount), 0) FROM {$wpdb->prefix}ptp_bookings WHERE status IN ('completed', 'confirmed')") ?: 0;
        }
        
        $apps_table = $wpdb->prefix . 'ptp_applications';
        if ($wpdb->get_var("SHOW TABLES LIKE '$apps_table'") == $apps_table) {
            $stats['pending_apps'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ptp_applications WHERE status = 'pending'") ?: 0;
        }
        
        return $stats;
    }
    
    /**
     * Render navigation helper
     */
    private function render_nav($active = 'dashboard') {
        $stats = $this->get_stats();
        ?>
        <nav class="ptp-admin-nav">
            <a href="<?php echo admin_url('admin.php?page=ptp-dashboard'); ?>" class="ptp-admin-nav-item <?php echo $active === 'dashboard' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-dashboard"></span> Dashboard
            </a>
            <a href="<?php echo admin_url('admin.php?page=ptp-schedule'); ?>" class="ptp-admin-nav-item <?php echo $active === 'schedule' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-calendar"></span> Schedule
            </a>
            <a href="<?php echo admin_url('admin.php?page=ptp-trainers'); ?>" class="ptp-admin-nav-item <?php echo $active === 'trainers' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-groups"></span> Trainers
            </a>
            <a href="<?php echo admin_url('admin.php?page=ptp-applications'); ?>" class="ptp-admin-nav-item <?php echo $active === 'applications' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-portfolio"></span> Applications
                <?php if ($stats['pending_apps'] > 0): ?>
                <span class="count alert"><?php echo $stats['pending_apps']; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=ptp-bookings'); ?>" class="ptp-admin-nav-item <?php echo $active === 'bookings' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-calendar-alt"></span> Bookings
            </a>
            <a href="<?php echo admin_url('admin.php?page=ptp-parents'); ?>" class="ptp-admin-nav-item <?php echo $active === 'parents' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-admin-users"></span> Parents
            </a>
            <a href="<?php echo admin_url('admin.php?page=ptp-payouts'); ?>" class="ptp-admin-nav-item <?php echo $active === 'payouts' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-money-alt"></span> Payouts
            </a>
            <a href="<?php echo admin_url('admin.php?page=ptp-messages'); ?>" class="ptp-admin-nav-item <?php echo $active === 'messages' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-email"></span> Messages
            </a>
            <a href="<?php echo admin_url('admin.php?page=ptp-settings'); ?>" class="ptp-admin-nav-item <?php echo $active === 'settings' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-admin-settings"></span> Settings
            </a>
        </nav>
        <?php
    }
    
    /**
     * Output critical CSS content (no style tags)
     */
    private function output_critical_css_content() {
        ?>
        /* Critical PTP Admin Styles */
        .ptp-admin-wrap {
            margin: 20px 20px 40px 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .ptp-admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding: 24px 32px;
            background: linear-gradient(135deg, #0A0A0A 0%, #1a1a1a 50%, #252525 100%);
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            position: relative;
        }
        .ptp-admin-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #FCB900, #C99200, #FCB900);
        }
        .ptp-admin-header-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .ptp-admin-logo {
            width: 56px;
            height: 56px;
            background: #FCB900;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ptp-admin-logo .dashicons {
            font-size: 28px;
            width: 28px;
            height: 28px;
            color: #0A0A0A;
        }
        .ptp-admin-title {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            color: #fff;
        }
        .ptp-admin-title span {
            color: #FCB900;
        }
        .ptp-admin-subtitle {
            margin: 4px 0 0;
            color: #9CA3AF;
            font-size: 14px;
        }
        .ptp-admin-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 32px;
            padding: 12px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            flex-wrap: wrap;
        }
        .ptp-admin-nav-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            color: #4B5563;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .ptp-admin-nav-item:hover {
            background: #F3F4F6;
            color: #0A0A0A;
        }
        .ptp-admin-nav-item.active {
            background: #0A0A0A;
            color: #fff;
        }
        .ptp-admin-nav-item .count {
            padding: 2px 8px;
            font-size: 12px;
            background: #E5E7EB;
            border-radius: 20px;
        }
        .ptp-admin-nav-item .count.alert {
            background: #EF4444;
            color: #fff;
        }
        .ptp-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }
        .ptp-stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            border: 1px solid #E5E7EB;
        }
        .ptp-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #6B7280;
        }
        .ptp-stat-card.yellow::before { background: #FCB900; }
        .ptp-stat-card.green::before { background: #10B981; }
        .ptp-stat-card.blue::before { background: #3B82F6; }
        .ptp-stat-card.purple::before { background: #8B5CF6; }
        .ptp-stat-card.red::before { background: #EF4444; }
        .ptp-stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            background: #F3F4F6;
        }
        .ptp-stat-icon.yellow { background: #FFF8E1; color: #FCB900; }
        .ptp-stat-icon.green { background: #D1FAE5; color: #10B981; }
        .ptp-stat-icon.blue { background: #DBEAFE; color: #3B82F6; }
        .ptp-stat-icon.purple { background: #EDE9FE; color: #8B5CF6; }
        .ptp-stat-icon.red { background: #FEE2E2; color: #EF4444; }
        .ptp-stat-icon .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
        }
        .ptp-stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #0A0A0A;
            line-height: 1.2;
        }
        .ptp-stat-label {
            color: #6B7280;
            font-size: 14px;
            margin-top: 4px;
        }
        .ptp-stat-footer {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #E5E7EB;
        }
        .ptp-stat-footer a {
            color: #3B82F6;
            text-decoration: none;
            font-weight: 500;
        }
        .ptp-dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }
        .ptp-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #E5E7EB;
            overflow: hidden;
        }
        .ptp-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #E5E7EB;
        }
        .ptp-card-header.dark {
            background: #0A0A0A;
            color: #fff;
            border-bottom: none;
        }
        .ptp-card-title {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ptp-card-body {
            padding: 24px;
        }
        .ptp-card-body.no-padding {
            padding: 0;
        }
        .ptp-empty-state {
            text-align: center;
            padding: 48px 24px;
        }
        .ptp-empty-state-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #F3F4F6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ptp-empty-state-icon .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            color: #9CA3AF;
        }
        .ptp-empty-state h3 {
            margin: 0 0 8px;
            color: #0A0A0A;
        }
        .ptp-empty-state p {
            margin: 0;
            color: #6B7280;
        }
        .ptp-quick-links {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .ptp-quick-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: #F9FAFB;
            border-radius: 10px;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s;
        }
        .ptp-quick-link:hover {
            background: #F3F4F6;
            transform: translateX(4px);
        }
        .ptp-quick-link .arrow {
            margin-left: auto;
            color: #9CA3AF;
        }
        .ptp-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        .ptp-btn-primary {
            background: #FCB900;
            color: #0A0A0A;
        }
        .ptp-btn-dark {
            background: #0A0A0A;
            color: #fff;
        }
        .ptp-btn-success {
            background: #10B981;
            color: #fff;
        }
        .ptp-btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        @media (max-width: 1200px) {
            .ptp-stats-grid { grid-template-columns: repeat(2, 1fr); }
            .ptp-dashboard-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 782px) {
            .ptp-stats-grid { grid-template-columns: 1fr; }
            .ptp-admin-nav { flex-wrap: wrap; }
        }
        <?php
    }

    /* =========================================================================
       DASHBOARD PAGE
       ========================================================================= */
    public function dashboard_page() {
        global $wpdb;
        $stats = $this->get_stats();
        
        // Get recent/upcoming bookings
        $upcoming_bookings = array();
        $table = $wpdb->prefix . 'ptp_bookings';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $upcoming_bookings = $wpdb->get_results($wpdb->prepare("
                SELECT b.*, t.display_name as trainer_name, p.display_name as parent_name, pl.name as player_name
                FROM {$wpdb->prefix}ptp_bookings b
                LEFT JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
                LEFT JOIN {$wpdb->prefix}ptp_parents p ON b.parent_id = p.id
                LEFT JOIN {$wpdb->prefix}ptp_players pl ON b.player_id = pl.id
                WHERE b.status = 'confirmed' AND b.session_date >= %s
                ORDER BY b.session_date ASC, b.start_time ASC
                LIMIT 5
            ", current_time('Y-m-d')));
        }
        ?>
        <div class="ptp-admin-wrap">
            <div class="ptp-admin-header">
                <div class="ptp-admin-header-content">
                    <div class="ptp-admin-logo">
                        <span class="dashicons dashicons-universal-access"></span>
                    </div>
                    <div class="ptp-admin-title-wrap">
                        <h1 class="ptp-admin-title">PTP <span>Training</span></h1>
                        <p class="ptp-admin-subtitle">Welcome back! Here's your platform overview.</p>
                    </div>
                </div>
                <div class="ptp-admin-actions">
                    <a href="<?php echo home_url('/find-trainers/'); ?>" class="ptp-btn ptp-btn-dark" target="_blank">
                        <span class="dashicons dashicons-external"></span> View Site
                    </a>
                </div>
            </div>
            
            <nav class="ptp-admin-nav">
                <a href="<?php echo admin_url('admin.php?page=ptp-dashboard'); ?>" class="ptp-admin-nav-item active">
                    <span class="dashicons dashicons-dashboard"></span> Dashboard
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-trainers'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-groups"></span> Trainers
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-applications'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-portfolio"></span> Applications
                    <?php if ($stats['pending_apps'] > 0): ?>
                    <span class="count alert"><?php echo $stats['pending_apps']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-bookings'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-calendar-alt"></span> Bookings
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-parents'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-admin-users"></span> Parents
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-payouts'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-money-alt"></span> Payouts
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-messages'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-email"></span> Messages
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-settings'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-admin-settings"></span> Settings
                </a>
            </nav>
            
            <!-- Cross-sell Banner -->
            <div style="background: linear-gradient(135deg, #FCB900 0%, #F59E0B 100%); border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h3 style="margin: 0 0 4px; font-size: 18px; font-weight: 700; color: #0E0F11;">⚽ Boost Revenue with Camps &amp; Clinics</h3>
                    <p style="margin: 0; color: rgba(14, 15, 17, 0.7); font-size: 14px;">Cross-sell your 1-on-1 training parents into group programs for higher lifetime value.</p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <a href="<?php echo home_url('/camps/'); ?>" class="ptp-btn" style="background: #0E0F11; color: #FCB900;" target="_blank">View Camps</a>
                    <a href="<?php echo home_url('/clinics/'); ?>" class="ptp-btn" style="background: #0E0F11; color: #FCB900;" target="_blank">View Clinics</a>
                </div>
            </div>
            
            <div class="ptp-stats-grid">
                <div class="ptp-stat-card yellow">
                    <div class="ptp-stat-header">
                        <div class="ptp-stat-icon yellow">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                    </div>
                    <div class="ptp-stat-value"><?php echo $stats['trainers']; ?></div>
                    <div class="ptp-stat-label">Active Trainers</div>
                    <div class="ptp-stat-footer">
                        <a href="<?php echo admin_url('admin.php?page=ptp-trainers'); ?>">Manage trainers →</a>
                    </div>
                </div>
                
                <div class="ptp-stat-card green">
                    <div class="ptp-stat-header">
                        <div class="ptp-stat-icon green">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                    </div>
                    <div class="ptp-stat-value"><?php echo $stats['bookings']; ?></div>
                    <div class="ptp-stat-label">Total Bookings</div>
                    <div class="ptp-stat-footer">
                        <span><?php echo $stats['upcoming']; ?> upcoming · <?php echo $stats['completed']; ?> completed</span>
                    </div>
                </div>
                
                <div class="ptp-stat-card blue">
                    <div class="ptp-stat-header">
                        <div class="ptp-stat-icon blue">
                            <span class="dashicons dashicons-chart-bar"></span>
                        </div>
                    </div>
                    <div class="ptp-stat-value">$<?php echo number_format($stats['revenue'], 0); ?></div>
                    <div class="ptp-stat-label">Total Revenue</div>
                </div>
                
                <div class="ptp-stat-card purple">
                    <div class="ptp-stat-header">
                        <div class="ptp-stat-icon purple">
                            <span class="dashicons dashicons-admin-users"></span>
                        </div>
                    </div>
                    <div class="ptp-stat-value"><?php echo $stats['parents']; ?></div>
                    <div class="ptp-stat-label">Registered Parents</div>
                    <div class="ptp-stat-footer">
                        <span><?php echo $stats['players']; ?> players registered</span>
                    </div>
                </div>
                
                <div class="ptp-stat-card <?php echo $stats['pending_apps'] > 0 ? 'red' : 'orange'; ?>">
                    <div class="ptp-stat-header">
                        <div class="ptp-stat-icon <?php echo $stats['pending_apps'] > 0 ? 'red' : 'orange'; ?>">
                            <span class="dashicons dashicons-portfolio"></span>
                        </div>
                    </div>
                    <div class="ptp-stat-value"><?php echo $stats['pending_apps']; ?></div>
                    <div class="ptp-stat-label">Pending Applications</div>
                    <?php if ($stats['pending_apps'] > 0): ?>
                    <div class="ptp-stat-footer">
                        <a href="<?php echo admin_url('admin.php?page=ptp-applications&status=pending'); ?>">Review now →</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="ptp-dashboard-grid">
                <div class="ptp-card">
                    <div class="ptp-card-header">
                        <h3 class="ptp-card-title">
                            <span class="dashicons dashicons-calendar-alt"></span> Upcoming Sessions
                        </h3>
                        <a href="<?php echo admin_url('admin.php?page=ptp-bookings&status=confirmed'); ?>" class="ptp-btn ptp-btn-secondary" style="font-size:12px;padding:6px 12px;">View All</a>
                    </div>
                    <div class="ptp-card-body">
                        <?php if ($upcoming_bookings): ?>
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <?php foreach ($upcoming_bookings as $session): ?>
                            <div style="display:flex;align-items:center;gap:12px;padding:12px;background:#F9FAFB;border-radius:8px;">
                                <div style="text-align:center;min-width:50px;">
                                    <div style="font-size:20px;font-weight:700;color:#111;line-height:1;"><?php echo date('j', strtotime($session->session_date)); ?></div>
                                    <div style="font-size:11px;text-transform:uppercase;color:#6B7280;"><?php echo date('M', strtotime($session->session_date)); ?></div>
                                </div>
                                <div style="flex:1;">
                                    <strong style="display:block;color:#111;"><?php echo esc_html($session->trainer_name ?: 'Trainer'); ?> → <?php echo esc_html($session->player_name ?: 'Player'); ?></strong>
                                    <small style="color:#6B7280;"><?php echo date('g:i A', strtotime($session->start_time)); ?> · <?php echo esc_html($session->location ?: 'TBD'); ?></small>
                                </div>
                                <div style="font-weight:600;color:#059669;">$<?php echo number_format($session->total_amount, 0); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="ptp-empty-state">
                            <div class="ptp-empty-state-icon">
                                <span class="dashicons dashicons-calendar-alt"></span>
                            </div>
                            <h3>No upcoming sessions</h3>
                            <p>Bookings will appear here once parents start scheduling.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="ptp-card">
                    <div class="ptp-card-header dark">
                        <h3 class="ptp-card-title">
                            <span class="dashicons dashicons-admin-links"></span> Quick Links
                        </h3>
                    </div>
                    <div class="ptp-card-body">
                        <div class="ptp-quick-links">
                            <a href="<?php echo admin_url('admin.php?page=ptp-applications'); ?>" class="ptp-quick-link">
                                <span class="dashicons dashicons-portfolio"></span>
                                Review Applications
                                <span class="dashicons dashicons-arrow-right-alt2 arrow"></span>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=ptp-settings'); ?>" class="ptp-quick-link">
                                <span class="dashicons dashicons-admin-settings"></span>
                                Platform Settings
                                <span class="dashicons dashicons-arrow-right-alt2 arrow"></span>
                            </a>
                            <a href="<?php echo home_url('/find-trainers/'); ?>" target="_blank" class="ptp-quick-link">
                                <span class="dashicons dashicons-external"></span>
                                View Public Site
                                <span class="dashicons dashicons-arrow-right-alt2 arrow"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /* =========================================================================
       SCHEDULE PAGE - Admin Calendar for Creating Sessions
       ========================================================================= */
    public function schedule_page() {
        global $wpdb;
        
        $success_message = '';
        $error_message = '';
        
        // Handle session creation
        if (isset($_POST['ptp_create_session']) && wp_verify_nonce($_POST['_wpnonce'], 'ptp_create_session')) {
            $trainer_id = intval($_POST['trainer_id']);
            $parent_id = intval($_POST['parent_id']);
            $player_id = intval($_POST['player_id']);
            $session_date = sanitize_text_field($_POST['session_date']);
            $start_time = sanitize_text_field($_POST['start_time']);
            $end_time = sanitize_text_field($_POST['end_time']);
            $location = sanitize_text_field($_POST['location']);
            $hourly_rate = floatval($_POST['hourly_rate']);
            $notes = sanitize_textarea_field($_POST['notes']);
            $status = sanitize_text_field($_POST['status']);
            
            // Calculate duration and amount
            $start = strtotime($start_time);
            $end = strtotime($end_time);
            $duration = ($end - $start) / 60; // minutes
            $total_amount = ($duration / 60) * $hourly_rate;
            
            // Generate booking number
            $booking_number = 'PTP-' . strtoupper(substr(md5(uniqid()), 0, 8));
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'ptp_bookings',
                array(
                    'booking_number' => $booking_number,
                    'trainer_id' => $trainer_id,
                    'parent_id' => $parent_id,
                    'player_id' => $player_id,
                    'session_date' => $session_date,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'duration_minutes' => $duration,
                    'location' => $location,
                    'hourly_rate' => $hourly_rate,
                    'total_amount' => $total_amount,
                    'notes' => $notes,
                    'status' => $status,
                    'created_at' => current_time('mysql'),
                    'created_by' => get_current_user_id()
                ),
                array('%s', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%f', '%f', '%s', '%s', '%s', '%d')
            );
            
            if ($result) {
                $success_message = "Session created successfully! Booking #: {$booking_number}";
            } else {
                $error_message = "Failed to create session. Please try again.";
            }
        }
        
        // Handle session deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete_session' && isset($_GET['id'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_session_' . $_GET['id'])) {
                $wpdb->delete($wpdb->prefix . 'ptp_bookings', array('id' => intval($_GET['id'])), array('%d'));
                $success_message = "Session deleted successfully.";
            }
        }
        
        // Get trainers for dropdown
        $trainers = $wpdb->get_results("SELECT id, display_name, hourly_rate FROM {$wpdb->prefix}ptp_trainers WHERE status = 'active' ORDER BY display_name");
        
        // Get parents for dropdown
        $parents = $wpdb->get_results("SELECT id, display_name FROM {$wpdb->prefix}ptp_parents ORDER BY display_name");
        
        // Get current week's sessions for calendar view
        $week_start = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : date('Y-m-d', strtotime('monday this week'));
        $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
        
        $sessions = $wpdb->get_results($wpdb->prepare("
            SELECT b.*, t.display_name as trainer_name, p.display_name as parent_name, pl.name as player_name
            FROM {$wpdb->prefix}ptp_bookings b
            LEFT JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
            LEFT JOIN {$wpdb->prefix}ptp_parents p ON b.parent_id = p.id
            LEFT JOIN {$wpdb->prefix}ptp_players pl ON b.player_id = pl.id
            WHERE b.session_date BETWEEN %s AND %s
            ORDER BY b.session_date, b.start_time
        ", $week_start, $week_end));
        
        // Organize sessions by date
        $sessions_by_date = array();
        foreach ($sessions as $s) {
            $sessions_by_date[$s->session_date][] = $s;
        }
        
        ?>
        <div class="ptp-admin-wrap">
            <div class="ptp-admin-header">
                <div class="ptp-admin-header-content">
                    <div class="ptp-admin-logo">
                        <span class="dashicons dashicons-universal-access"></span>
                    </div>
                    <div class="ptp-admin-title-wrap">
                        <h1 class="ptp-admin-title">PTP <span>Training</span></h1>
                        <p class="ptp-admin-subtitle">Admin Calendar &amp; Session Scheduler</p>
                    </div>
                </div>
                <div class="ptp-admin-actions">
                    <button type="button" onclick="document.getElementById('create-session-modal').style.display='flex';" class="ptp-btn ptp-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span> Create Session
                    </button>
                </div>
            </div>
            
            <?php $this->render_nav('schedule'); ?>
            
            <?php if ($success_message): ?>
            <div class="notice notice-success is-dismissible" style="margin: 15px 0;"><p><?php echo esc_html($success_message); ?></p></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="notice notice-error is-dismissible" style="margin: 15px 0;"><p><?php echo esc_html($error_message); ?></p></div>
            <?php endif; ?>
            
            <!-- Week Navigation -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; background: #fff; padding: 16px 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <a href="?page=ptp-schedule&week=<?php echo date('Y-m-d', strtotime($week_start . ' -7 days')); ?>" class="ptp-btn ptp-btn-secondary">← Previous Week</a>
                <h2 style="margin: 0; font-size: 18px;">
                    <?php echo date('M j', strtotime($week_start)); ?> - <?php echo date('M j, Y', strtotime($week_end)); ?>
                </h2>
                <div style="display: flex; gap: 10px;">
                    <a href="?page=ptp-schedule&week=<?php echo date('Y-m-d', strtotime('monday this week')); ?>" class="ptp-btn ptp-btn-secondary">Today</a>
                    <a href="?page=ptp-schedule&week=<?php echo date('Y-m-d', strtotime($week_start . ' +7 days')); ?>" class="ptp-btn ptp-btn-secondary">Next Week →</a>
                </div>
            </div>
            
            <!-- Calendar Grid -->
            <div class="ptp-card">
                <div class="ptp-card-body no-padding">
                    <div style="display: grid; grid-template-columns: repeat(7, 1fr); border-bottom: 2px solid #E5E7EB;">
                        <?php 
                        $days = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
                        for ($i = 0; $i < 7; $i++): 
                            $date = date('Y-m-d', strtotime($week_start . " +{$i} days"));
                            $is_today = $date === date('Y-m-d');
                        ?>
                        <div style="padding: 12px; text-align: center; background: <?php echo $is_today ? '#FCB900' : '#F9FAFB'; ?>; border-right: 1px solid #E5E7EB; <?php echo $i === 6 ? 'border-right:none;' : ''; ?>">
                            <div style="font-weight: 600; color: <?php echo $is_today ? '#0E0F11' : '#6B7280'; ?>;"><?php echo $days[$i]; ?></div>
                            <div style="font-size: 20px; font-weight: 700; color: <?php echo $is_today ? '#0E0F11' : '#111'; ?>;"><?php echo date('j', strtotime($date)); ?></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(7, 1fr); min-height: 400px;">
                        <?php for ($i = 0; $i < 7; $i++): 
                            $date = date('Y-m-d', strtotime($week_start . " +{$i} days"));
                            $day_sessions = isset($sessions_by_date[$date]) ? $sessions_by_date[$date] : array();
                        ?>
                        <div style="border-right: 1px solid #E5E7EB; padding: 8px; <?php echo $i === 6 ? 'border-right:none;' : ''; ?> background: <?php echo date('Y-m-d') === $date ? '#FFFBEB' : '#fff'; ?>;">
                            <?php foreach ($day_sessions as $session): 
                                $status_colors = array(
                                    'confirmed' => '#10B981',
                                    'pending' => '#F59E0B',
                                    'completed' => '#3B82F6',
                                    'cancelled' => '#EF4444'
                                );
                                $bg = isset($status_colors[$session->status]) ? $status_colors[$session->status] : '#6B7280';
                            ?>
                            <div style="background: <?php echo $bg; ?>; color: #fff; padding: 8px 10px; border-radius: 6px; margin-bottom: 6px; font-size: 12px; position: relative;">
                                <div style="font-weight: 700;"><?php echo date('g:i A', strtotime($session->start_time)); ?></div>
                                <div style="opacity: 0.9;"><?php echo esc_html($session->trainer_name ?: 'No Trainer'); ?></div>
                                <div style="opacity: 0.8; font-size: 11px;"><?php echo esc_html($session->player_name ?: 'No Player'); ?></div>
                                <a href="?page=ptp-schedule&week=<?php echo $week_start; ?>&action=delete_session&id=<?php echo $session->id; ?>&_wpnonce=<?php echo wp_create_nonce('delete_session_' . $session->id); ?>" 
                                   onclick="return confirm('Delete this session?');"
                                   style="position: absolute; top: 4px; right: 4px; color: #fff; opacity: 0.7; text-decoration: none; font-size: 14px;">×</a>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($day_sessions)): ?>
                            <div style="color: #9CA3AF; font-size: 12px; text-align: center; padding: 20px 0;">No sessions</div>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- Legend -->
            <div style="display: flex; gap: 20px; margin-top: 16px; padding: 12px 16px; background: #fff; border-radius: 8px;">
                <span style="display: flex; align-items: center; gap: 6px; font-size: 13px;"><span style="width: 12px; height: 12px; background: #10B981; border-radius: 3px;"></span> Confirmed</span>
                <span style="display: flex; align-items: center; gap: 6px; font-size: 13px;"><span style="width: 12px; height: 12px; background: #F59E0B; border-radius: 3px;"></span> Pending</span>
                <span style="display: flex; align-items: center; gap: 6px; font-size: 13px;"><span style="width: 12px; height: 12px; background: #3B82F6; border-radius: 3px;"></span> Completed</span>
                <span style="display: flex; align-items: center; gap: 6px; font-size: 13px;"><span style="width: 12px; height: 12px; background: #EF4444; border-radius: 3px;"></span> Cancelled</span>
            </div>
        </div>
        
        <!-- Create Session Modal -->
        <div id="create-session-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99999; align-items: center; justify-content: center;">
            <div style="background: #fff; border-radius: 16px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; margin: 20px;">
                <div style="padding: 20px 24px; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0; font-size: 20px;">Create Training Session</h2>
                    <button type="button" onclick="document.getElementById('create-session-modal').style.display='none';" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6B7280;">&times;</button>
                </div>
                
                <form method="post" style="padding: 24px;">
                    <?php wp_nonce_field('ptp_create_session'); ?>
                    <input type="hidden" name="ptp_create_session" value="1">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">Trainer *</label>
                            <select name="trainer_id" required style="width: 100%; padding: 10px 12px; border: 2px solid #E5E7EB; border-radius: 8px; font-size: 14px;" onchange="updateRate(this)">
                                <option value="">Select Trainer</option>
                                <?php foreach ($trainers as $t): ?>
                                <option value="<?php echo $t->id; ?>" data-rate="<?php echo $t->hourly_rate; ?>"><?php echo esc_html($t->display_name); ?> ($<?php echo number_format($t->hourly_rate, 0); ?>/hr)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">Parent *</label>
                            <select name="parent_id" required id="parent-select" style="width: 100%; padding: 10px 12px; border: 2px solid #E5E7EB; border-radius: 8px; font-size: 14px;" onchange="loadPlayers(this.value)">
                                <option value="">Select Parent</option>
                                <?php foreach ($parents as $p): ?>
                                <option value="<?php echo $p->id; ?>"><?php echo esc_html($p->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">Player</label>
                            <select name="player_id" id="player-select" style="width: 100%; padding: 10px 12px; border: 2px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                                <option value="">Select Player (optional)</option>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">Session Date *</label>
                            <input type="date" name="session_date" required value="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 10px 12px; border: 2px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">Start Time *</label>
                            <input type="time" name="start_time" required value="09:00" style="width: 100%; padding: 10px 12px; border: 2px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">End Time *</label>
                            <input type="time" name="end_time" required value="10:00" style="width: 100%; padding: 10px 12px; border: 2px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">Hourly Rate ($)</label>
                            <input type="number" name="hourly_rate" id="hourly-rate" value="70" min="0" step="5" style="width: 100%; padding: 10px 12px; border: 2px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">Status</label>
                            <select name="status" style="width: 100%; padding: 10px 12px; border: 2px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                                <option value="confirmed">Confirmed</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-top: 16px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">Location</label>
                        <input type="text" name="location" placeholder="e.g., Main Field, Indoor Gym" style="width: 100%; padding: 10px 12px; border: 2px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                    </div>
                    
                    <div style="margin-top: 16px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">Notes</label>
                        <textarea name="notes" rows="3" placeholder="Any additional notes..." style="width: 100%; padding: 10px 12px; border: 2px solid #E5E7EB; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
                    </div>
                    
                    <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="button" onclick="document.getElementById('create-session-modal').style.display='none';" style="padding: 12px 24px; background: #F3F4F6; color: #374151; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                        <button type="submit" style="padding: 12px 24px; background: #FCB900; color: #0E0F11; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">Create Session</button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        function updateRate(select) {
            var option = select.options[select.selectedIndex];
            var rate = option.getAttribute('data-rate');
            if (rate) {
                document.getElementById('hourly-rate').value = rate;
            }
        }
        
        function loadPlayers(parentId) {
            var playerSelect = document.getElementById('player-select');
            playerSelect.innerHTML = '<option value="">Loading...</option>';
            
            if (!parentId) {
                playerSelect.innerHTML = '<option value="">Select Player (optional)</option>';
                return;
            }
            
            // AJAX to get players
            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=ptp_get_players&parent_id=' + parentId)
                .then(response => response.json())
                .then(data => {
                    playerSelect.innerHTML = '<option value="">Select Player (optional)</option>';
                    if (data.success && data.data) {
                        data.data.forEach(function(player) {
                            var opt = document.createElement('option');
                            opt.value = player.id;
                            opt.textContent = player.name + ' (Age ' + player.age + ')';
                            playerSelect.appendChild(opt);
                        });
                    }
                })
                .catch(() => {
                    playerSelect.innerHTML = '<option value="">Select Player (optional)</option>';
                });
        }
        </script>
        <?php
    }
    
    /* =========================================================================
       TRAINERS PAGE
       ========================================================================= */
    public function trainers_page() {
        global $wpdb;
        
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        $trainers = array();
        $counts = array();
        $total = 0;
        
        $table = $wpdb->prefix . 'ptp_trainers';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $where = "WHERE 1=1";
            if ($status) $where .= $wpdb->prepare(" AND t.status = %s", $status);
            if ($search) $where .= $wpdb->prepare(" AND (t.display_name LIKE %s OR u.user_email LIKE %s)", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
            
            $trainers = $wpdb->get_results("
                SELECT t.*, COALESCE(u.user_email, t.email) as user_email FROM {$wpdb->prefix}ptp_trainers t 
                LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID {$where} ORDER BY t.created_at DESC
            ");
            
            $counts = $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$wpdb->prefix}ptp_trainers GROUP BY status", OBJECT_K);
            $total = array_sum(array_column((array)$counts, 'count'));
        }
        
        
        ?>
        <div class="ptp-admin-wrap">
            <?php if (isset($_GET['trainer_deleted'])): ?>
            <div class="notice notice-success is-dismissible" style="margin: 10px 0;"><p>✅ Trainer has been deleted.</p></div>
            <?php endif; ?>
            <?php if (isset($_GET['trainer_updated'])): ?>
            <div class="notice notice-success is-dismissible" style="margin: 10px 0;"><p>✅ Trainer has been updated.</p></div>
            <?php endif; ?>
            <div class="ptp-admin-header">
                <div class="ptp-admin-header-content">
                    <div class="ptp-admin-logo">
                        <span class="dashicons dashicons-universal-access"></span>
                    </div>
                    <div class="ptp-admin-title-wrap">
                        <h1 class="ptp-admin-title">PTP <span>Training</span></h1>
                        <p class="ptp-admin-subtitle">Manage your trainer roster</p>
                    </div>
                </div>
            </div>
            
            <nav class="ptp-admin-nav">
                <a href="<?php echo admin_url('admin.php?page=ptp-dashboard'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-dashboard"></span> Dashboard
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-trainers'); ?>" class="ptp-admin-nav-item active">
                    <span class="dashicons dashicons-groups"></span> Trainers
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-applications'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-portfolio"></span> Applications
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-bookings'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-calendar-alt"></span> Bookings
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-parents'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-admin-users"></span> Parents
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-payouts'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-money-alt"></span> Payouts
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-messages'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-email"></span> Messages
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-settings'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-admin-settings"></span> Settings
                </a>
            </nav>
            
            <div class="ptp-filter-tabs">
                <a href="?page=ptp-trainers" class="ptp-filter-tab <?php echo !$status ? 'active' : ''; ?>">
                    All <span class="count"><?php echo $total; ?></span>
                </a>
                <a href="?page=ptp-trainers&status=active" class="ptp-filter-tab <?php echo $status === 'active' ? 'active' : ''; ?>">
                    Active <span class="count"><?php echo isset($counts['active']) ? $counts['active']->count : 0; ?></span>
                </a>
                <a href="?page=ptp-trainers&status=pending" class="ptp-filter-tab <?php echo $status === 'pending' ? 'active' : ''; ?>">
                    Pending <span class="count"><?php echo isset($counts['pending']) ? $counts['pending']->count : 0; ?></span>
                </a>
                <a href="?page=ptp-trainers&status=inactive" class="ptp-filter-tab <?php echo $status === 'inactive' ? 'active' : ''; ?>">
                    Inactive <span class="count"><?php echo isset($counts['inactive']) ? $counts['inactive']->count : 0; ?></span>
                </a>
            </div>
            
            <div class="ptp-toolbar">
                <form method="get" class="ptp-search-box">
                    <input type="hidden" name="page" value="ptp-trainers">
                    <div class="ptp-search-input-wrap">
                        <span class="dashicons dashicons-search"></span>
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search trainers..." class="ptp-search-input">
                    </div>
                    <button type="submit" class="ptp-btn ptp-btn-secondary">Search</button>
                </form>
            </div>
            
            <div class="ptp-card">
                <div class="ptp-card-body no-padding">
                    <?php if ($trainers): ?>
                    <div class="ptp-table-wrap">
                        <table class="ptp-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Trainer</th>
                                    <th>Location</th>
                                    <th>Rate</th>
                                    <th>Compliance</th>
                                    <th>Sessions</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trainers as $t): 
                                    // Check compliance status
                                    $has_safesport = !empty($t->safesport_doc_url);
                                    $safesport_verified = !empty($t->safesport_verified);
                                    $has_w9 = !empty($t->w9_submitted);
                                    $has_stripe = !empty($t->stripe_account_id) && !empty($t->stripe_charges_enabled);
                                    $is_verified = !empty($t->is_verified);
                                    $background_verified = !empty($t->background_verified);
                                    $compliance_complete = $safesport_verified && $has_w9;
                                ?>
                                <tr>
                                    <td><?php echo $t->id; ?></td>
                                    <td>
                                        <div class="ptp-table-user">
                                            <div class="ptp-table-user-avatar"><?php echo strtoupper(substr($t->display_name, 0, 1)); ?></div>
                                            <div class="ptp-table-user-info">
                                                <div class="ptp-table-user-name">
                                                    <?php echo esc_html($t->display_name); ?>
                                                    <?php if ($is_verified): ?>
                                                    <span title="Verified Trainer" style="color: #10B981;">✓</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ptp-table-user-email"><?php echo esc_html($t->user_email); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($t->location ?: '-'); ?></td>
                                    <td><strong>$<?php echo number_format($t->hourly_rate, 0); ?></strong>/hr</td>
                                    <td>
                                        <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                            <span title="SafeSport <?php echo $safesport_verified ? 'Verified' : ($has_safesport ? 'Pending' : 'Required'); ?>" 
                                                  class="ptp-compliance-badge" 
                                                  data-trainer="<?php echo $t->id; ?>" 
                                                  data-type="safesport"
                                                  style="cursor: pointer; display: inline-flex; align-items: center; gap: 2px; padding: 2px 6px; border-radius: 4px; font-size: 11px; <?php echo $safesport_verified ? 'background: #D1FAE5; color: #065F46;' : ($has_safesport ? 'background: #FEF3C7; color: #92400E;' : 'background: #FEE2E2; color: #DC2626;'); ?>">
                                                🛡️ <?php echo $safesport_verified ? '✓' : ($has_safesport ? '⏳' : '✗'); ?>
                                            </span>
                                            <span title="W-9 <?php echo $has_w9 ? 'Submitted' : 'Required'; ?>" 
                                                  class="ptp-compliance-badge" 
                                                  data-trainer="<?php echo $t->id; ?>" 
                                                  data-type="w9"
                                                  style="cursor: pointer; display: inline-flex; align-items: center; gap: 2px; padding: 2px 6px; border-radius: 4px; font-size: 11px; <?php echo $has_w9 ? 'background: #D1FAE5; color: #065F46;' : 'background: #FEE2E2; color: #DC2626;'; ?>">
                                                📋 <?php echo $has_w9 ? '✓' : '✗'; ?>
                                            </span>
                                            <span title="Background <?php echo $background_verified ? 'Verified' : 'Required'; ?>" 
                                                  class="ptp-compliance-badge" 
                                                  data-trainer="<?php echo $t->id; ?>" 
                                                  data-type="background"
                                                  style="cursor: pointer; display: inline-flex; align-items: center; gap: 2px; padding: 2px 6px; border-radius: 4px; font-size: 11px; <?php echo $background_verified ? 'background: #D1FAE5; color: #065F46;' : 'background: #F3F4F6; color: #6B7280;'; ?>">
                                                🔍 <?php echo $background_verified ? '✓' : '-'; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td><?php echo $t->total_sessions; ?></td>
                                    <td><span class="ptp-status ptp-status-<?php echo esc_attr($t->status); ?>"><?php echo ucfirst($t->status); ?></span></td>
                                    <td>
                                        <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                            <button type="button" class="button button-small ptp-edit-trainer" data-id="<?php echo $t->id; ?>">Edit</button>
                                            <button type="button" class="button button-small ptp-compliance-menu" data-id="<?php echo $t->id; ?>" title="Send Compliance Emails">📧</button>
                                            <?php if ($t->status === 'active'): ?>
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-trainers&action=deactivate&id=' . $t->id), 'ptp_trainer_action'); ?>" class="button button-small" title="Deactivate">⏸</a>
                                            <?php elseif ($compliance_complete): ?>
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-trainers&action=activate&id=' . $t->id), 'ptp_trainer_action'); ?>" class="button button-small button-primary" title="Activate">▶</a>
                                            <?php endif; ?>
                                            <a href="<?php echo home_url('/trainer/' . $t->slug . '/'); ?>" class="button button-small" target="_blank" title="View Profile">👁</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="ptp-empty-state">
                        <div class="ptp-empty-state-icon">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                        <h3>No trainers found</h3>
                        <p>Trainers will appear here once they complete their applications.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Edit Trainer Modal -->
            <div id="edit-trainer-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:100000;align-items:center;justify-content:center;">
                <div style="background:#fff;border-radius:16px;max-width:600px;width:90%;max-height:90vh;overflow-y:auto;padding:32px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
                        <h2 style="margin:0;font-size:20px;">Edit Trainer</h2>
                        <button type="button" onclick="document.getElementById('edit-trainer-modal').style.display='none';" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
                    </div>
                    <form id="edit-trainer-form">
                        <input type="hidden" name="trainer_id" id="edit-trainer-id">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ptp_admin_nonce'); ?>">
                        
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Display Name</label>
                                <input type="text" name="display_name" id="edit-display_name" class="regular-text" style="width:100%;">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Phone</label>
                                <input type="text" name="phone" id="edit-phone" class="regular-text" style="width:100%;">
                            </div>
                        </div>
                        
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Location</label>
                                <input type="text" name="location" id="edit-location" class="regular-text" style="width:100%;">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Hourly Rate ($)</label>
                                <input type="number" name="hourly_rate" id="edit-hourly_rate" class="regular-text" style="width:100%;">
                            </div>
                        </div>
                        
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">College</label>
                                <input type="text" name="college" id="edit-college" class="regular-text" style="width:100%;">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Team</label>
                                <input type="text" name="team" id="edit-team" class="regular-text" style="width:100%;">
                            </div>
                        </div>
                        
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-weight:600;margin-bottom:4px;">Headline</label>
                            <input type="text" name="headline" id="edit-headline" class="regular-text" style="width:100%;">
                        </div>
                        
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-weight:600;margin-bottom:4px;">Bio</label>
                            <textarea name="bio" id="edit-bio" rows="4" style="width:100%;"></textarea>
                        </div>
                        
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Status</label>
                                <select name="status" id="edit-status" style="width:100%;">
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Playing Level</label>
                                <select name="playing_level" id="edit-playing_level" style="width:100%;">
                                    <option value="">Select...</option>
                                    <option value="pro">Pro</option>
                                    <option value="college_d1">D1 College</option>
                                    <option value="college_d2">D2 College</option>
                                    <option value="college_d3">D3 College</option>
                                    <option value="academy">Academy</option>
                                    <option value="semi_pro">Semi-Pro</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="background:#F9FAFB;padding:16px;border-radius:12px;margin-bottom:20px;">
                            <h4 style="margin:0 0 12px;">Compliance Status</h4>
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                                <label style="display:flex;align-items:center;gap:8px;">
                                    <input type="checkbox" name="safesport_verified" id="edit-safesport_verified" value="1">
                                    SafeSport Verified
                                </label>
                                <label style="display:flex;align-items:center;gap:8px;">
                                    <input type="checkbox" name="w9_submitted" id="edit-w9_submitted" value="1">
                                    W-9 Submitted
                                </label>
                                <label style="display:flex;align-items:center;gap:8px;">
                                    <input type="checkbox" name="background_verified" id="edit-background_verified" value="1">
                                    Background Verified
                                </label>
                            </div>
                            <div style="margin-top:12px;">
                                <label style="display:flex;align-items:center;gap:8px;">
                                    <input type="checkbox" name="is_verified" id="edit-is_verified" value="1">
                                    <strong>Fully Verified Trainer</strong> (shows verified badge)
                                </label>
                            </div>
                        </div>
                        
                        <div style="display:flex;gap:12px;justify-content:flex-end;">
                            <button type="button" onclick="document.getElementById('edit-trainer-modal').style.display='none';" class="button">Cancel</button>
                            <button type="submit" class="button button-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Compliance Email Modal -->
            <div id="compliance-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:100000;align-items:center;justify-content:center;">
                <div style="background:#fff;border-radius:16px;max-width:400px;width:90%;padding:32px;">
                    <h2 style="margin:0 0 8px;">Send Compliance Request</h2>
                    <p style="color:#6B7280;margin:0 0 24px;">Send an email requesting the trainer to complete compliance requirements.</p>
                    <input type="hidden" id="compliance-trainer-id">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <button type="button" class="button" onclick="sendComplianceEmail('safesport')">
                            🛡️ Request SafeSport Certification
                        </button>
                        <button type="button" class="button" onclick="sendComplianceEmail('w9')">
                            📋 Request W-9 Form
                        </button>
                        <button type="button" class="button" onclick="sendComplianceEmail('background')">
                            🔍 Request Background Check
                        </button>
                    </div>
                    <div style="margin-top:20px;text-align:right;">
                        <button type="button" onclick="document.getElementById('compliance-modal').style.display='none';" class="button">Close</button>
                    </div>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Edit trainer button
                $('.ptp-edit-trainer').click(function() {
                    var trainerId = $(this).data('id');
                    $.post(ajaxurl, {
                        action: 'ptp_admin_get_trainer',
                        trainer_id: trainerId,
                        nonce: '<?php echo wp_create_nonce('ptp_admin_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            var t = response.data;
                            $('#edit-trainer-id').val(t.id);
                            $('#edit-display_name').val(t.display_name);
                            $('#edit-phone').val(t.phone);
                            $('#edit-location').val(t.location);
                            $('#edit-hourly_rate').val(t.hourly_rate);
                            $('#edit-college').val(t.college);
                            $('#edit-team').val(t.team);
                            $('#edit-headline').val(t.headline);
                            $('#edit-bio').val(t.bio);
                            $('#edit-status').val(t.status);
                            $('#edit-playing_level').val(t.playing_level);
                            $('#edit-safesport_verified').prop('checked', t.safesport_verified == 1);
                            $('#edit-w9_submitted').prop('checked', t.w9_submitted == 1);
                            $('#edit-background_verified').prop('checked', t.background_verified == 1);
                            $('#edit-is_verified').prop('checked', t.is_verified == 1);
                            $('#edit-trainer-modal').css('display', 'flex');
                        }
                    });
                });
                
                // Save trainer
                $('#edit-trainer-form').submit(function(e) {
                    e.preventDefault();
                    var formData = $(this).serialize();
                    formData += '&action=ptp_admin_update_trainer';
                    
                    $.post(ajaxurl, formData, function(response) {
                        if (response.success) {
                            alert('Trainer updated successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    });
                });
                
                // Compliance menu
                $('.ptp-compliance-menu').click(function() {
                    $('#compliance-trainer-id').val($(this).data('id'));
                    $('#compliance-modal').css('display', 'flex');
                });
            });
            
            function sendComplianceEmail(type) {
                var trainerId = document.getElementById('compliance-trainer-id').value;
                var actions = {
                    'safesport': 'ptp_admin_send_safesport_request',
                    'w9': 'ptp_admin_send_w9_request',
                    'background': 'ptp_admin_send_background_check_request'
                };
                
                jQuery.post(ajaxurl, {
                    action: actions[type],
                    trainer_id: trainerId,
                    nonce: '<?php echo wp_create_nonce('ptp_admin_nonce'); ?>'
                }, function(response) {
                    alert(response.success ? response.data.message : 'Error: ' + response.data.message);
                    if (response.success) {
                        document.getElementById('compliance-modal').style.display = 'none';
                    }
                });
            }
            </script>
        </div>
        <?php
    }
    
    /* =========================================================================
       APPLICATIONS PAGE
       ========================================================================= */
    public function applications_page() {
        global $wpdb;
        
        // Handle actions
        if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'ptp_app_action')) {
                $app_id = intval($_GET['id']);
                $action = sanitize_text_field($_GET['action']);
                
                if ($action === 'approve') {
                    $result = $this->approve_application($app_id);
                    if ($result) {
                        wp_redirect(admin_url('admin.php?page=ptp-applications&message=approved'));
                    } else {
                        wp_redirect(admin_url('admin.php?page=ptp-applications&message=error&error_type=approval_failed'));
                    }
                    exit;
                } elseif ($action === 'reject') {
                    $wpdb->update(
                        $wpdb->prefix . 'ptp_applications',
                        array('status' => 'rejected', 'reviewed_at' => current_time('mysql'), 'reviewed_by' => get_current_user_id()),
                        array('id' => $app_id)
                    );
                    // Send rejection email
                    $app = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ptp_applications WHERE id = %d", $app_id));
                    if ($app && class_exists('PTP_Email')) {
                        PTP_Email::send_application_rejected($app->email, $app->name);
                    }
                    wp_redirect(admin_url('admin.php?page=ptp-applications&message=rejected'));
                    exit;
                } elseif ($action === 'delete') {
                    $wpdb->delete($wpdb->prefix . 'ptp_applications', array('id' => $app_id));
                    wp_redirect(admin_url('admin.php?page=ptp-applications&message=deleted'));
                    exit;
                }
            }
        }
        
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
        
        $apps = array();
        $counts = array();
        $total = 0;
        $pending = 0;
        
        $table = $wpdb->prefix . 'ptp_applications';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $where = $status ? $wpdb->prepare("WHERE status = %s", $status) : "";
            $apps = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ptp_applications {$where} ORDER BY created_at DESC");
            $counts = $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$wpdb->prefix}ptp_applications GROUP BY status", OBJECT_K);
            $total = array_sum(array_column((array)$counts, 'count'));
            $pending = isset($counts['pending']) ? $counts['pending']->count : 0;
        }
        
        $levels = array('professional' => 'Professional', 'pro' => 'Professional', 'd1' => 'NCAA D1', 'college_d1' => 'NCAA D1', 'd2' => 'NCAA D2', 'college_d2' => 'NCAA D2', 'd3' => 'NCAA D3', 'college_d3' => 'NCAA D3', 'academy' => 'Elite Academy', 'semi_pro' => 'Semi-Pro', 'other' => 'Other');
        
        ?>
        <style>
            .ptp-modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 100000; }
            .ptp-modal-overlay.active { display: flex; align-items: center; justify-content: center; }
            .ptp-modal { background: #fff; border-radius: 12px; max-width: 600px; width: 90%; max-height: 80vh; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
            .ptp-modal-header { padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
            .ptp-modal-header h2 { margin: 0; font-size: 18px; }
            .ptp-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; }
            .ptp-modal-body { padding: 24px; overflow-y: auto; max-height: 50vh; }
            .ptp-modal-footer { padding: 16px 24px; border-top: 1px solid #e5e7eb; display: flex; gap: 12px; justify-content: flex-end; }
            .ptp-detail-row { display: flex; padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
            .ptp-detail-row:last-child { border-bottom: none; }
            .ptp-detail-label { width: 140px; font-weight: 500; color: #6b7280; font-size: 13px; }
            .ptp-detail-value { flex: 1; color: #111827; }
            .ptp-actions-dropdown { position: relative; display: inline-block; }
            .ptp-actions-btn { background: #f3f4f6; border: 1px solid #e5e7eb; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 4px; }
            .ptp-actions-btn:hover { background: #e5e7eb; }
            .ptp-actions-menu { display: none; position: absolute; right: 0; top: 100%; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); min-width: 160px; z-index: 100; overflow: hidden; }
            .ptp-actions-dropdown.open .ptp-actions-menu { display: block; }
            .ptp-actions-menu a { display: flex; align-items: center; gap: 8px; padding: 10px 14px; color: #374151; text-decoration: none; font-size: 13px; }
            .ptp-actions-menu a:hover { background: #f9fafb; }
            .ptp-actions-menu a.danger { color: #dc2626; }
            .ptp-actions-menu a.danger:hover { background: #fef2f2; }
            .ptp-actions-menu a.success { color: #059669; }
            .ptp-actions-menu a.success:hover { background: #ecfdf5; }
            .ptp-actions-menu .divider { height: 1px; background: #e5e7eb; margin: 4px 0; }
            .ptp-alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
            .ptp-alert-success { background: #ecfdf5; color: #065f46; }
            .ptp-alert-warning { background: #fef3c7; color: #92400e; }
            .ptp-alert-danger { background: #fef2f2; color: #991b1b; }
            .ptp-bio-preview { background: #f9fafb; padding: 12px; border-radius: 6px; font-size: 14px; line-height: 1.5; max-height: 100px; overflow-y: auto; }
        </style>
        
        <div class="ptp-admin-wrap">
            <div class="ptp-admin-header">
                <div class="ptp-admin-header-content">
                    <div class="ptp-admin-logo">
                        <span class="dashicons dashicons-universal-access"></span>
                    </div>
                    <div class="ptp-admin-title-wrap">
                        <h1 class="ptp-admin-title">PTP <span>Training</span></h1>
                        <p class="ptp-admin-subtitle">Review trainer applications</p>
                    </div>
                </div>
            </div>
            
            <nav class="ptp-admin-nav">
                <a href="<?php echo admin_url('admin.php?page=ptp-dashboard'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-dashboard"></span> Dashboard
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-trainers'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-groups"></span> Trainers
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-applications'); ?>" class="ptp-admin-nav-item active">
                    <span class="dashicons dashicons-portfolio"></span> Applications
                    <?php if ($pending > 0): ?><span class="count alert"><?php echo $pending; ?></span><?php endif; ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-bookings'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-calendar-alt"></span> Bookings
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-parents'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-admin-users"></span> Parents
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-payouts'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-money-alt"></span> Payouts
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-messages'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-email"></span> Messages
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-settings'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-admin-settings"></span> Settings
                </a>
            </nav>
            
            <?php if ($message === 'approved'): ?>
            <div class="ptp-alert ptp-alert-success">
                <span class="dashicons dashicons-yes-alt"></span>
                Application approved! The trainer has been sent login credentials.
            </div>
            <?php elseif ($message === 'rejected'): ?>
            <div class="ptp-alert ptp-alert-warning">
                <span class="dashicons dashicons-dismiss"></span>
                Application rejected. The applicant has been notified.
            </div>
            <?php elseif ($message === 'deleted'): ?>
            <div class="ptp-alert ptp-alert-danger">
                <span class="dashicons dashicons-trash"></span>
                Application permanently deleted.
            </div>
            <?php elseif ($message === 'error'): ?>
            <div class="ptp-alert ptp-alert-danger">
                <span class="dashicons dashicons-warning"></span>
                <?php 
                $error_type = isset($_GET['error_type']) ? sanitize_text_field($_GET['error_type']) : 'unknown';
                if ($error_type === 'approval_failed') {
                    echo 'Failed to approve application. Check the error log for details. The application or trainer record may already exist.';
                } else {
                    echo 'An error occurred. Please try again.';
                }
                ?>
            </div>
            <?php endif; ?>
            
            <?php if ($pending > 0 && !$status && !$message): ?>
            <div class="ptp-notice ptp-notice-warning">
                <span class="dashicons dashicons-warning"></span>
                <div class="ptp-notice-content">
                    <strong>Action Required:</strong> You have <?php echo $pending; ?> pending application<?php echo $pending > 1 ? 's' : ''; ?> waiting for review.
                </div>
                <a href="?page=ptp-applications&status=pending" class="ptp-btn ptp-btn-warning ptp-btn-sm">Review Now</a>
            </div>
            <?php endif; ?>
            
            <div class="ptp-filter-tabs">
                <a href="?page=ptp-applications" class="ptp-filter-tab <?php echo !$status ? 'active' : ''; ?>">
                    All <span class="count"><?php echo $total; ?></span>
                </a>
                <a href="?page=ptp-applications&status=pending" class="ptp-filter-tab <?php echo $status === 'pending' ? 'active' : ''; ?>">
                    Pending <span class="count"><?php echo $pending; ?></span>
                </a>
                <a href="?page=ptp-applications&status=approved" class="ptp-filter-tab <?php echo $status === 'approved' ? 'active' : ''; ?>">
                    Approved <span class="count"><?php echo isset($counts['approved']) ? $counts['approved']->count : 0; ?></span>
                </a>
                <a href="?page=ptp-applications&status=rejected" class="ptp-filter-tab <?php echo $status === 'rejected' ? 'active' : ''; ?>">
                    Rejected <span class="count"><?php echo isset($counts['rejected']) ? $counts['rejected']->count : 0; ?></span>
                </a>
            </div>
            
            <?php if ($apps): ?>
            <div class="ptp-card">
                <div class="ptp-card-body no-padding">
                    <div class="ptp-table-wrap">
                        <table class="ptp-table">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Contact</th>
                                    <th>Experience</th>
                                    <th>Rate</th>
                                    <th>Status</th>
                                    <th>Applied</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apps as $a): ?>
                                <tr>
                                    <td>
                                        <div class="ptp-table-user">
                                            <div class="ptp-table-user-avatar"><?php echo strtoupper(substr($a->name ?: 'A', 0, 1)); ?></div>
                                            <div class="ptp-table-user-info">
                                                <div class="ptp-table-user-name"><?php echo esc_html($a->name ?: 'No name'); ?></div>
                                                <div class="ptp-table-user-email"><?php echo esc_html($a->location ?: 'No location'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo esc_attr($a->email); ?>"><?php echo esc_html($a->email); ?></a><br>
                                        <small style="color: #6b7280;"><?php echo esc_html($a->phone ?: 'No phone'); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo isset($levels[$a->playing_level]) ? $levels[$a->playing_level] : esc_html($a->playing_level ?: 'Not specified'); ?></strong><br>
                                        <small style="color: #6b7280;"><?php echo esc_html($a->college ?: $a->team ?: '—'); ?></small>
                                    </td>
                                    <td><strong>$<?php echo number_format($a->hourly_rate ?: 0, 0); ?></strong>/hr</td>
                                    <td><span class="ptp-status ptp-status-<?php echo esc_attr($a->status); ?>"><?php echo ucfirst($a->status); ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($a->created_at)); ?></td>
                                    <td>
                                        <?php if ($a->status === 'pending'): ?>
                                        <div style="display:flex;gap:6px;align-items:center;">
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-applications&action=approve&id=' . $a->id), 'ptp_app_action'); ?>" 
                                               class="ptp-btn ptp-btn-success ptp-btn-sm" 
                                               title="Approve"
                                               onclick="return confirm('Approve this application? This will create a trainer account and send login credentials.');">
                                                <span class="dashicons dashicons-yes" style="font-size:16px;width:16px;height:16px;"></span>
                                            </a>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-applications&action=reject&id=' . $a->id), 'ptp_app_action'); ?>" 
                                               class="ptp-btn ptp-btn-outline ptp-btn-sm" 
                                               title="Reject"
                                               style="color:#DC2626;border-color:#DC2626;"
                                               onclick="return confirm('Reject this application?');">
                                                <span class="dashicons dashicons-no" style="font-size:16px;width:16px;height:16px;"></span>
                                            </a>
                                            <div class="ptp-actions-dropdown">
                                                <button class="ptp-actions-btn" onclick="this.parentElement.classList.toggle('open')" style="padding:4px 8px;">
                                                    <span class="dashicons dashicons-ellipsis" style="font-size:16px;"></span>
                                                </button>
                                                <div class="ptp-actions-menu">
                                                    <a href="#" onclick="viewApplication(<?php echo $a->id; ?>); return false;">
                                                        <span class="dashicons dashicons-visibility"></span> View Details
                                                    </a>
                                                    <div class="divider"></div>
                                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-applications&action=delete&id=' . $a->id), 'ptp_app_action'); ?>" class="danger" onclick="return confirm('Permanently delete this application? This cannot be undone.');">
                                                        <span class="dashicons dashicons-trash"></span> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div class="ptp-actions-dropdown">
                                            <button class="ptp-actions-btn" onclick="this.parentElement.classList.toggle('open')">
                                                Actions <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 14px;"></span>
                                            </button>
                                            <div class="ptp-actions-menu">
                                                <a href="#" onclick="viewApplication(<?php echo $a->id; ?>); return false;">
                                                    <span class="dashicons dashicons-visibility"></span> View Details
                                                </a>
                                                <div class="divider"></div>
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-applications&action=delete&id=' . $a->id), 'ptp_app_action'); ?>" class="danger" onclick="return confirm('Permanently delete this application? This cannot be undone.');">
                                                    <span class="dashicons dashicons-trash"></span> Delete
                                                </a>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="ptp-card">
                <div class="ptp-card-body">
                    <div class="ptp-empty-state">
                        <div class="ptp-empty-state-icon">
                            <span class="dashicons dashicons-portfolio"></span>
                        </div>
                        <h3>No applications found</h3>
                        <p>Applications will appear here when trainers submit their information.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Application Detail Modal -->
        <div class="ptp-modal-overlay" id="applicationModal">
            <div class="ptp-modal">
                <div class="ptp-modal-header">
                    <h2>Application Details</h2>
                    <button class="ptp-modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="ptp-modal-body" id="applicationModalBody">
                    Loading...
                </div>
                <div class="ptp-modal-footer" id="applicationModalFooter">
                </div>
            </div>
        </div>
        
        <script>
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.ptp-actions-dropdown')) {
                document.querySelectorAll('.ptp-actions-dropdown.open').forEach(function(el) {
                    el.classList.remove('open');
                });
            }
        });
        
        // Application data for modal
        var applications = <?php echo json_encode(array_map(function($a) use ($levels) {
            return array(
                'id' => $a->id,
                'name' => $a->name,
                'email' => $a->email,
                'phone' => $a->phone,
                'location' => $a->location,
                'playing_level' => isset($levels[$a->playing_level]) ? $levels[$a->playing_level] : $a->playing_level,
                'college' => $a->college,
                'team' => $a->team,
                'specialties' => $a->specialties,
                'instagram' => $a->instagram ?? '',
                'headline' => $a->headline,
                'bio' => $a->bio,
                'hourly_rate' => $a->hourly_rate,
                'travel_radius' => $a->travel_radius,
                'status' => $a->status,
                'created_at' => date('M j, Y g:i A', strtotime($a->created_at)),
                'approve_url' => wp_nonce_url(admin_url('admin.php?page=ptp-applications&action=approve&id=' . $a->id), 'ptp_app_action'),
                'reject_url' => wp_nonce_url(admin_url('admin.php?page=ptp-applications&action=reject&id=' . $a->id), 'ptp_app_action'),
            );
        }, $apps)); ?>;
        
        function viewApplication(id) {
            var app = applications.find(function(a) { return a.id == id; });
            if (!app) return;
            
            document.querySelectorAll('.ptp-actions-dropdown.open').forEach(function(el) {
                el.classList.remove('open');
            });
            
            var html = '<div class="ptp-detail-row"><div class="ptp-detail-label">Name</div><div class="ptp-detail-value"><strong>' + (app.name || 'Not provided') + '</strong></div></div>';
            html += '<div class="ptp-detail-row"><div class="ptp-detail-label">Email</div><div class="ptp-detail-value"><a href="mailto:' + app.email + '">' + app.email + '</a></div></div>';
            html += '<div class="ptp-detail-row"><div class="ptp-detail-label">Phone</div><div class="ptp-detail-value">' + (app.phone || 'Not provided') + '</div></div>';
            html += '<div class="ptp-detail-row"><div class="ptp-detail-label">Location</div><div class="ptp-detail-value">' + (app.location || 'Not provided') + '</div></div>';
            html += '<div class="ptp-detail-row"><div class="ptp-detail-label">Playing Level</div><div class="ptp-detail-value">' + (app.playing_level || 'Not specified') + '</div></div>';
            html += '<div class="ptp-detail-row"><div class="ptp-detail-label">College/Team</div><div class="ptp-detail-value">' + (app.college || app.team || 'Not provided') + '</div></div>';
            html += '<div class="ptp-detail-row"><div class="ptp-detail-label">Specialties</div><div class="ptp-detail-value">' + (app.specialties || 'None selected') + '</div></div>';
            html += '<div class="ptp-detail-row"><div class="ptp-detail-label">Instagram</div><div class="ptp-detail-value">' + (app.instagram ? '@' + app.instagram : 'Not provided') + '</div></div>';
            html += '<div class="ptp-detail-row"><div class="ptp-detail-label">Hourly Rate</div><div class="ptp-detail-value"><strong>$' + parseFloat(app.hourly_rate || 0).toFixed(0) + '/hr</strong></div></div>';
            html += '<div class="ptp-detail-row"><div class="ptp-detail-label">Travel Radius</div><div class="ptp-detail-value">' + (app.travel_radius || 15) + ' miles</div></div>';
            html += '<div class="ptp-detail-row"><div class="ptp-detail-label">Headline</div><div class="ptp-detail-value">' + (app.headline || 'Not provided') + '</div></div>';
            html += '<div class="ptp-detail-row"><div class="ptp-detail-label">Bio</div><div class="ptp-detail-value"><div class="ptp-bio-preview">' + (app.bio || 'Not provided') + '</div></div></div>';
            html += '<div class="ptp-detail-row"><div class="ptp-detail-label">Applied</div><div class="ptp-detail-value">' + app.created_at + '</div></div>';
            
            document.getElementById('applicationModalBody').innerHTML = html;
            
            var footer = '';
            if (app.status === 'pending') {
                footer = '<a href="' + app.reject_url + '" class="ptp-btn ptp-btn-outline" onclick="return confirm(\'Reject this application?\');">Reject</a>';
                footer += '<a href="' + app.approve_url + '" class="ptp-btn ptp-btn-success" onclick="return confirm(\'Approve this application? This will create a trainer account.\');">Approve Application</a>';
            } else {
                footer = '<span style="color: #6b7280; font-size: 14px;">Status: ' + app.status.charAt(0).toUpperCase() + app.status.slice(1) + '</span>';
                footer += '<button class="ptp-btn ptp-btn-outline" onclick="closeModal()">Close</button>';
            }
            document.getElementById('applicationModalFooter').innerHTML = footer;
            
            document.getElementById('applicationModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('applicationModal').classList.remove('active');
        }
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
        
        // Close modal on overlay click
        document.getElementById('applicationModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        </script>
        <?php
    }
    
    /**
     * Approve an application and create trainer account
     */
    private function approve_application($app_id) {
        global $wpdb;
        
        error_log('PTP: Starting approval for application ID: ' . $app_id);
        
        $app = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ptp_applications WHERE id = %d", $app_id));
        if (!$app) {
            error_log('PTP: Application not found: ' . $app_id);
            return false;
        }
        
        error_log('PTP: Application found - Name: ' . $app->name . ', Email: ' . $app->email . ', User ID: ' . ($app->user_id ?: 'NULL'));
        
        // Auto-migrate: ensure trainers table exists and has all required columns
        $trainers_table = $wpdb->prefix . 'ptp_trainers';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$trainers_table}'") === $trainers_table;
        
        if (!$table_exists) {
            error_log('PTP: Trainers table does not exist! Running create_tables...');
            PTP_Database::create_tables();
            // Re-check
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$trainers_table}'") === $trainers_table;
            if (!$table_exists) {
                error_log('PTP: CRITICAL - Failed to create trainers table!');
                return false;
            }
        }
        
        // Get existing columns
        $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM {$trainers_table}", 0);
        error_log('PTP: Existing trainer columns: ' . implode(', ', $existing_columns));
        
        // Comprehensive list of all columns that might be missing
        $required_columns = array(
            'email' => "ALTER TABLE {$trainers_table} ADD COLUMN email varchar(255) DEFAULT ''",
            'phone' => "ALTER TABLE {$trainers_table} ADD COLUMN phone varchar(20) DEFAULT ''",
            'headline' => "ALTER TABLE {$trainers_table} ADD COLUMN headline varchar(255) DEFAULT ''",
            'bio' => "ALTER TABLE {$trainers_table} ADD COLUMN bio text",
            'photo_url' => "ALTER TABLE {$trainers_table} ADD COLUMN photo_url varchar(500) DEFAULT ''",
            'hourly_rate' => "ALTER TABLE {$trainers_table} ADD COLUMN hourly_rate decimal(10,2) DEFAULT 0",
            'location' => "ALTER TABLE {$trainers_table} ADD COLUMN location varchar(255) DEFAULT ''",
            'latitude' => "ALTER TABLE {$trainers_table} ADD COLUMN latitude decimal(10,8) DEFAULT NULL",
            'longitude' => "ALTER TABLE {$trainers_table} ADD COLUMN longitude decimal(11,8) DEFAULT NULL",
            'travel_radius' => "ALTER TABLE {$trainers_table} ADD COLUMN travel_radius int(11) DEFAULT 15",
            'college' => "ALTER TABLE {$trainers_table} ADD COLUMN college varchar(255) DEFAULT ''",
            'team' => "ALTER TABLE {$trainers_table} ADD COLUMN team varchar(255) DEFAULT ''",
            'playing_level' => "ALTER TABLE {$trainers_table} ADD COLUMN playing_level varchar(50) DEFAULT ''",
            'position' => "ALTER TABLE {$trainers_table} ADD COLUMN position varchar(100) DEFAULT ''",
            'experience_years' => "ALTER TABLE {$trainers_table} ADD COLUMN experience_years int(11) DEFAULT 0",
            'specialties' => "ALTER TABLE {$trainers_table} ADD COLUMN specialties text",
            'instagram' => "ALTER TABLE {$trainers_table} ADD COLUMN instagram varchar(100) DEFAULT ''",
            'status' => "ALTER TABLE {$trainers_table} ADD COLUMN status varchar(20) DEFAULT 'pending'",
            'is_featured' => "ALTER TABLE {$trainers_table} ADD COLUMN is_featured tinyint(1) DEFAULT 0",
            'is_verified' => "ALTER TABLE {$trainers_table} ADD COLUMN is_verified tinyint(1) DEFAULT 0",
            'is_background_checked' => "ALTER TABLE {$trainers_table} ADD COLUMN is_background_checked tinyint(1) DEFAULT 0",
            'total_sessions' => "ALTER TABLE {$trainers_table} ADD COLUMN total_sessions int(11) DEFAULT 0",
            'total_earnings' => "ALTER TABLE {$trainers_table} ADD COLUMN total_earnings decimal(10,2) DEFAULT 0",
            'average_rating' => "ALTER TABLE {$trainers_table} ADD COLUMN average_rating decimal(3,2) DEFAULT 0",
            'review_count' => "ALTER TABLE {$trainers_table} ADD COLUMN review_count int(11) DEFAULT 0",
        );
        
        foreach ($required_columns as $col => $sql) {
            if (!in_array($col, $existing_columns)) {
                error_log('PTP: Adding missing column: ' . $col);
                $result = $wpdb->query($sql);
                if ($result === false) {
                    error_log('PTP: Failed to add column ' . $col . ': ' . $wpdb->last_error);
                }
            }
        }
        
        // Determine user_id - check if user already exists
        $user_id = $app->user_id;
        $password = null;
        $user_created_during_application = false;
        
        if (!$user_id || $user_id == 0) {
            error_log('PTP: No user_id in application, checking for existing user by email');
            // Legacy: user wasn't created during application, create now
            $user = get_user_by('email', $app->email);
            if (!$user) {
                error_log('PTP: No existing user found, creating new user');
                // Generate password for new user
                $password = wp_generate_password(12, false);
                $user_id = wp_create_user($app->email, $password, $app->email);
                
                if (is_wp_error($user_id)) {
                    error_log('PTP: Failed to create user for application ' . $app_id . ': ' . $user_id->get_error_message());
                    return false;
                }
                
                error_log('PTP: Created new user with ID: ' . $user_id);
                
                // Update user meta
                wp_update_user(array(
                    'ID' => $user_id,
                    'first_name' => explode(' ', $app->name)[0],
                    'last_name' => implode(' ', array_slice(explode(' ', $app->name), 1)),
                    'display_name' => $app->name,
                ));
                
                // Add trainer role
                $user = get_user_by('ID', $user_id);
                if ($user) {
                    $user->add_role('ptp_trainer');
                    error_log('PTP: Added ptp_trainer role to new user');
                }
            } else {
                $user_id = $user->ID;
                error_log('PTP: Found existing user with ID: ' . $user_id);
                // Generate new temporary password for existing user (legacy case)
                $password = wp_generate_password(12, false);
                wp_set_password($password, $user_id);
                error_log('PTP: Reset password for existing user');
            }
        } else {
            error_log('PTP: Application has user_id: ' . $user_id . ' - user created account during application');
            // User was created during application - they already have their password
            // DO NOT reset their password - they set it themselves
            $user_created_during_application = true;
            error_log('PTP: Keeping user password (set during application)');
        }
        
        // Verify user exists and add trainer role
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            error_log('PTP: CRITICAL - User ID ' . $user_id . ' does not exist! Creating new user.');
            // User was deleted, create a new one
            $password = wp_generate_password(12, false);
            $user_id = wp_create_user($app->email, $password, $app->email);
            
            if (is_wp_error($user_id)) {
                error_log('PTP: Failed to create replacement user: ' . $user_id->get_error_message());
                return false;
            }
            
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => explode(' ', $app->name)[0],
                'last_name' => implode(' ', array_slice(explode(' ', $app->name), 1)),
                'display_name' => $app->name,
            ));
            
            $user = get_user_by('ID', $user_id);
            error_log('PTP: Created replacement user with ID: ' . $user_id);
        }
        
        // Ensure user has trainer role
        if ($user && !in_array('ptp_trainer', (array) $user->roles)) {
            $user->add_role('ptp_trainer');
            error_log('PTP: Added ptp_trainer role to user ' . $user_id);
        }
        
        // Check if trainer record already exists for this user
        $existing_trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ptp_trainers WHERE user_id = %d",
            $user_id
        ));
        
        if ($existing_trainer) {
            // Trainer already exists - just update status to active and update application
            $wpdb->update(
                $wpdb->prefix . 'ptp_trainers',
                array('status' => 'active'),
                array('id' => $existing_trainer->id)
            );
            error_log('PTP: Trainer record already exists (ID: ' . $existing_trainer->id . ') for user ' . $user_id . ', updated status to active');
            
            // Update application status and return success
            $wpdb->update(
                $wpdb->prefix . 'ptp_applications',
                array('status' => 'approved', 'reviewed_at' => current_time('mysql'), 'reviewed_by' => get_current_user_id()),
                array('id' => $app_id)
            );
            
            return true;
        }
        
        // Generate unique slug
        $base_slug = sanitize_title($app->name);
        if (empty($base_slug)) {
            $base_slug = 'trainer';
        }
        $slug = $base_slug . '-' . $user_id;
        
        // Make sure slug is unique
        $slug_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ptp_trainers WHERE slug = %s",
            $slug
        ));
        if ($slug_exists) {
            $slug = $base_slug . '-' . $user_id . '-' . time();
        }
        
        error_log('PTP: Creating trainer with slug: ' . $slug);
        
        // Create trainer profile - only include columns that exist
        $trainer_data = array(
            'user_id' => $user_id,
            'display_name' => $app->name ?: 'Trainer',
            'slug' => $slug,
            'status' => 'active',
        );
        
        // Add optional fields only if they have values (prevents issues with missing columns)
        $optional_fields = array(
            'email' => $app->email ?: '',
            'phone' => $app->phone ?: '',
            'location' => $app->location ?: '',
            'college' => $app->college ?: '',
            'team' => $app->team ?: '',
            'playing_level' => $app->playing_level ?: '',
            'specialties' => $app->specialties ?: '',
            'instagram' => isset($app->instagram) ? ($app->instagram ?: '') : '',
            'headline' => isset($app->headline) ? ($app->headline ?: '') : '',
            'bio' => $app->bio ?: '',
            'hourly_rate' => floatval($app->hourly_rate) ?: 0,
            'travel_radius' => intval($app->travel_radius) ?: 15,
        );
        
        // Re-fetch columns after migration
        $current_columns = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}ptp_trainers", 0);
        
        foreach ($optional_fields as $field => $value) {
            if (in_array($field, $current_columns)) {
                $trainer_data[$field] = $value;
            } else {
                error_log('PTP: Skipping field ' . $field . ' - column does not exist');
            }
        }
        
        error_log('PTP: Trainer data to insert: ' . print_r($trainer_data, true));
        
        $insert_result = $wpdb->insert($wpdb->prefix . 'ptp_trainers', $trainer_data);
        
        if ($insert_result === false) {
            error_log('PTP: FAILED to create trainer record for user ' . $user_id);
            error_log('PTP: Database error: ' . $wpdb->last_error);
            error_log('PTP: Last query: ' . $wpdb->last_query);
            return false;
        }
        
        $trainer_id = $wpdb->insert_id;
        error_log('PTP: Successfully created trainer record ID ' . $trainer_id . ' for user ' . $user_id);
        
        // Update application status
        $wpdb->update(
            $wpdb->prefix . 'ptp_applications',
            array(
                'status' => 'approved', 
                'reviewed_at' => current_time('mysql'), 
                'reviewed_by' => get_current_user_id(),
                'user_id' => $user_id  // Update with correct user_id in case it changed
            ),
            array('id' => $app_id)
        );
        
        // Send approval email
        if (class_exists('PTP_Email')) {
            // If user created account during application, they already have their password
            // Pass null to indicate no new password was generated
            PTP_Email::send_application_approved($app->email, $app->name, $user_created_during_application ? null : $password);
        }
        
        error_log('PTP: Application ' . $app_id . ' approved successfully');
        return true;
    }
    
    /* =========================================================================
       BOOKINGS PAGE
       ========================================================================= */
    public function bookings_page() {
        global $wpdb;
        
        $success_message = '';
        $error_message = '';
        
        // Handle booking actions
        if (isset($_GET['action']) && isset($_GET['id']) && wp_verify_nonce($_GET['_wpnonce'], 'ptp_booking_action')) {
            $booking_id = intval($_GET['id']);
            $action = sanitize_text_field($_GET['action']);
            
            switch ($action) {
                case 'complete':
                    $wpdb->update($wpdb->prefix . 'ptp_bookings', array('status' => 'completed', 'completed_at' => current_time('mysql')), array('id' => $booking_id), array('%s', '%s'), array('%d'));
                    $success_message = 'Booking marked as completed.';
                    break;
                case 'cancel':
                    $wpdb->update($wpdb->prefix . 'ptp_bookings', array('status' => 'cancelled', 'cancelled_at' => current_time('mysql')), array('id' => $booking_id), array('%s', '%s'), array('%d'));
                    $success_message = 'Booking cancelled.';
                    break;
                case 'delete':
                    $wpdb->delete($wpdb->prefix . 'ptp_bookings', array('id' => $booking_id), array('%d'));
                    $success_message = 'Booking deleted permanently.';
                    break;
            }
        }
        
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        $bookings = array();
        $counts = array();
        $total = 0;
        
        $table = $wpdb->prefix . 'ptp_bookings';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $where = $status ? $wpdb->prepare("WHERE b.status = %s", $status) : "";
            $bookings = $wpdb->get_results("
                SELECT b.*, t.display_name as trainer_name, p.display_name as parent_name
                FROM {$wpdb->prefix}ptp_bookings b
                LEFT JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
                LEFT JOIN {$wpdb->prefix}ptp_parents p ON b.parent_id = p.id
                {$where}
                ORDER BY b.session_date DESC, b.start_time DESC
            ");
            $counts = $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$wpdb->prefix}ptp_bookings GROUP BY status", OBJECT_K);
            $total = array_sum(array_column((array)$counts, 'count'));
        }
        
        
        ?>
        <div class="ptp-admin-wrap">
            <?php if ($success_message): ?>
            <div class="notice notice-success is-dismissible" style="margin: 10px 0;"><p>✅ <?php echo esc_html($success_message); ?></p></div>
            <?php endif; ?>
            
            <div class="ptp-admin-header">
                <div class="ptp-admin-header-content">
                    <div class="ptp-admin-logo">
                        <span class="dashicons dashicons-universal-access"></span>
                    </div>
                    <div class="ptp-admin-title-wrap">
                        <h1 class="ptp-admin-title">PTP <span>Training</span></h1>
                        <p class="ptp-admin-subtitle">Manage training sessions</p>
                    </div>
                </div>
                <div class="ptp-admin-actions">
                    <a href="<?php echo admin_url('admin.php?page=ptp-schedule'); ?>" class="ptp-btn ptp-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span> Create Session
                    </a>
                </div>
            </div>
            
            <?php $this->render_nav('bookings'); ?>
            
            <div class="ptp-filter-tabs">
                <a href="?page=ptp-bookings" class="ptp-filter-tab <?php echo !$status ? 'active' : ''; ?>">
                    All <span class="count"><?php echo $total; ?></span>
                </a>
                <a href="?page=ptp-bookings&status=confirmed" class="ptp-filter-tab <?php echo $status === 'confirmed' ? 'active' : ''; ?>">
                    Confirmed <span class="count"><?php echo isset($counts['confirmed']) ? $counts['confirmed']->count : 0; ?></span>
                </a>
                <a href="?page=ptp-bookings&status=completed" class="ptp-filter-tab <?php echo $status === 'completed' ? 'active' : ''; ?>">
                    Completed <span class="count"><?php echo isset($counts['completed']) ? $counts['completed']->count : 0; ?></span>
                </a>
                <a href="?page=ptp-bookings&status=cancelled" class="ptp-filter-tab <?php echo $status === 'cancelled' ? 'active' : ''; ?>">
                    Cancelled <span class="count"><?php echo isset($counts['cancelled']) ? $counts['cancelled']->count : 0; ?></span>
                </a>
            </div>
            
            <div class="ptp-card">
                <div class="ptp-card-body no-padding">
                    <?php if ($bookings): ?>
                    <div class="ptp-table-wrap">
                        <table class="ptp-table">
                            <thead>
                                <tr>
                                    <th>Booking #</th>
                                    <th>Trainer</th>
                                    <th>Parent</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($b->booking_number); ?></strong></td>
                                    <td><?php echo esc_html($b->trainer_name ?: '-'); ?></td>
                                    <td><?php echo esc_html($b->parent_name ?: '-'); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($b->session_date)); ?><br><small style="color:#6B7280;"><?php echo date('g:i A', strtotime($b->start_time)); ?></small></td>
                                    <td><span class="ptp-status ptp-status-<?php echo esc_attr($b->status); ?>"><?php echo ucfirst($b->status); ?></span></td>
                                    <td class="ptp-table-amount positive">$<?php echo number_format($b->total_amount, 2); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 6px;">
                                            <button type="button" class="button button-small ptp-edit-booking" data-id="<?php echo $b->id; ?>" title="Edit">✏️</button>
                                            <?php if ($b->status === 'confirmed'): ?>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-bookings&action=complete&id=' . $b->id), 'ptp_booking_action'); ?>" class="button button-small" title="Mark Complete">✓</a>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-bookings&action=cancel&id=' . $b->id), 'ptp_booking_action'); ?>" class="button button-small" title="Cancel" onclick="return confirm('Cancel this booking?');">✕</a>
                                            <?php endif; ?>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-bookings&action=delete&id=' . $b->id), 'ptp_booking_action'); ?>" class="button button-small" style="color:#DC2626;" title="Delete" onclick="return confirm('Permanently delete this booking?');">🗑</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="ptp-empty-state">
                        <div class="ptp-empty-state-icon">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <h3>No bookings yet</h3>
                        <p>Bookings will appear here as parents schedule training sessions.</p>
                        <a href="<?php echo admin_url('admin.php?page=ptp-schedule'); ?>" class="button button-primary">Create First Session</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Edit Booking Modal -->
            <div id="edit-booking-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:100000;align-items:center;justify-content:center;">
                <div style="background:#fff;border-radius:16px;max-width:600px;width:90%;max-height:90vh;overflow-y:auto;padding:32px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
                        <h2 style="margin:0;font-size:20px;">Edit Booking</h2>
                        <button type="button" onclick="document.getElementById('edit-booking-modal').style.display='none';" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
                    </div>
                    <form id="edit-booking-form">
                        <input type="hidden" name="booking_id" id="booking-id">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ptp_admin_nonce'); ?>">
                        
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Session Date</label>
                                <input type="date" name="session_date" id="booking-session_date" class="regular-text" style="width:100%;">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Status</label>
                                <select name="status" id="booking-status" style="width:100%;">
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Start Time</label>
                                <input type="time" name="start_time" id="booking-start_time" class="regular-text" style="width:100%;">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">End Time</label>
                                <input type="time" name="end_time" id="booking-end_time" class="regular-text" style="width:100%;">
                            </div>
                        </div>
                        
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-weight:600;margin-bottom:4px;">Location</label>
                            <input type="text" name="location" id="booking-location" class="regular-text" style="width:100%;">
                        </div>
                        
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Hourly Rate ($)</label>
                                <input type="number" name="hourly_rate" id="booking-hourly_rate" class="regular-text" style="width:100%;" step="0.01">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Total Amount</label>
                                <div id="booking-total-display" style="padding:8px 12px;background:#F3F4F6;border-radius:6px;font-weight:700;">$0.00</div>
                            </div>
                        </div>
                        
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-weight:600;margin-bottom:4px;">Notes</label>
                            <textarea name="notes" id="booking-notes" rows="3" style="width:100%;"></textarea>
                        </div>
                        
                        <div style="display:flex;gap:12px;justify-content:flex-end;">
                            <button type="button" onclick="document.getElementById('edit-booking-modal').style.display='none';" class="button">Cancel</button>
                            <button type="submit" class="button button-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Edit booking
                $('.ptp-edit-booking').click(function() {
                    var bookingId = $(this).data('id');
                    $.post(ajaxurl, {
                        action: 'ptp_admin_get_booking',
                        booking_id: bookingId,
                        nonce: '<?php echo wp_create_nonce('ptp_admin_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            var b = response.data;
                            $('#booking-id').val(b.id);
                            $('#booking-session_date').val(b.session_date);
                            $('#booking-start_time').val(b.start_time);
                            $('#booking-end_time').val(b.end_time);
                            $('#booking-location').val(b.location);
                            $('#booking-hourly_rate').val(b.hourly_rate);
                            $('#booking-status').val(b.status);
                            $('#booking-notes').val(b.notes);
                            $('#booking-total-display').text('$' + parseFloat(b.total_amount).toFixed(2));
                            $('#edit-booking-modal').css('display', 'flex');
                        }
                    });
                });
                
                // Save booking
                $('#edit-booking-form').submit(function(e) {
                    e.preventDefault();
                    var formData = $(this).serialize();
                    formData += '&action=ptp_admin_update_booking';
                    
                    $.post(ajaxurl, formData, function(response) {
                        if (response.success) {
                            alert('Booking updated!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    });
                });
                
                // Calculate total on time/rate change
                $('#booking-start_time, #booking-end_time, #booking-hourly_rate').change(function() {
                    var start = $('#booking-start_time').val();
                    var end = $('#booking-end_time').val();
                    var rate = parseFloat($('#booking-hourly_rate').val()) || 0;
                    if (start && end) {
                        var startMin = parseInt(start.split(':')[0]) * 60 + parseInt(start.split(':')[1]);
                        var endMin = parseInt(end.split(':')[0]) * 60 + parseInt(end.split(':')[1]);
                        var duration = (endMin - startMin) / 60;
                        var total = duration * rate;
                        $('#booking-total-display').text('$' + total.toFixed(2));
                    }
                });
            });
            </script>
        </div>
        <?php
    }
    
    /* =========================================================================
       PARENTS PAGE
       ========================================================================= */
    public function parents_page() {
        global $wpdb;
        
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $parents = array();
        
        $table = $wpdb->prefix . 'ptp_parents';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $where = $search ? $wpdb->prepare("WHERE p.display_name LIKE %s OR u.user_email LIKE %s", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%') : "";
            $parents = $wpdb->get_results("
                SELECT p.*, u.user_email,
                       (SELECT COUNT(*) FROM {$wpdb->prefix}ptp_players WHERE parent_id = p.id AND is_active = 1) as player_count,
                       (SELECT COUNT(*) FROM {$wpdb->prefix}ptp_bookings WHERE parent_id = p.id) as total_bookings,
                       (SELECT COALESCE(SUM(total_amount), 0) FROM {$wpdb->prefix}ptp_bookings WHERE parent_id = p.id AND status IN ('confirmed', 'completed')) as lifetime_value,
                       (SELECT MAX(session_date) FROM {$wpdb->prefix}ptp_bookings WHERE parent_id = p.id) as last_booking
                FROM {$wpdb->prefix}ptp_parents p 
                LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID {$where} ORDER BY p.created_at DESC
            ");
        }
        
        
        ?>
        <div class="ptp-admin-wrap">
            <div class="ptp-admin-header">
                <div class="ptp-admin-header-content">
                    <div class="ptp-admin-logo">
                        <span class="dashicons dashicons-universal-access"></span>
                    </div>
                    <div class="ptp-admin-title-wrap">
                        <h1 class="ptp-admin-title">PTP <span>Training</span></h1>
                        <p class="ptp-admin-subtitle">Parent accounts &amp; cross-sell opportunities</p>
                    </div>
                </div>
            </div>
            
            <nav class="ptp-admin-nav">
                <a href="<?php echo admin_url('admin.php?page=ptp-dashboard'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-dashboard"></span> Dashboard
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-trainers'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-groups"></span> Trainers
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-applications'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-portfolio"></span> Applications
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-bookings'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-calendar-alt"></span> Bookings
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-parents'); ?>" class="ptp-admin-nav-item active">
                    <span class="dashicons dashicons-admin-users"></span> Parents
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-payouts'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-money-alt"></span> Payouts
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-messages'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-email"></span> Messages
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-settings'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-admin-settings"></span> Settings
                </a>
            </nav>
            
            <div class="ptp-toolbar">
                <form method="get" class="ptp-search-box">
                    <input type="hidden" name="page" value="ptp-parents">
                    <div class="ptp-search-input-wrap">
                        <span class="dashicons dashicons-search"></span>
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search parents..." class="ptp-search-input">
                    </div>
                    <button type="submit" class="ptp-btn ptp-btn-secondary">Search</button>
                </form>
            </div>
            
            <div class="ptp-card">
                <div class="ptp-card-body no-padding">
                    <?php if ($parents): ?>
                    <div class="ptp-table-wrap">
                        <table class="ptp-table">
                            <thead>
                                <tr>
                                    <th>Parent</th>
                                    <th>Players</th>
                                    <th>Bookings</th>
                                    <th>Lifetime Value</th>
                                    <th>Last Booking</th>
                                    <th>Cross-sell</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($parents as $p): 
                                    $days_since = $p->last_booking ? floor((time() - strtotime($p->last_booking)) / 86400) : null;
                                    $is_inactive = $days_since === null || $days_since > 30;
                                    $ltv = floatval($p->lifetime_value);
                                ?>
                                <tr>
                                    <td>
                                        <div class="ptp-table-user">
                                            <div class="ptp-table-user-avatar"><?php echo strtoupper(substr($p->display_name, 0, 1)); ?></div>
                                            <div class="ptp-table-user-info">
                                                <div class="ptp-table-user-name"><?php echo esc_html($p->display_name); ?></div>
                                                <div class="ptp-table-user-email"><?php echo esc_html($p->user_email ?: $p->phone ?: '-'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo intval($p->player_count); ?></strong>
                                        <?php if ($p->player_count > 1): ?>
                                        <span style="display:inline-flex;align-items:center;padding:2px 6px;background:#FEF3C7;color:#92400E;border-radius:4px;font-size:10px;margin-left:4px;">👨‍👩‍👧‍👦</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo intval($p->total_bookings); ?></td>
                                    <td class="ptp-table-amount positive">$<?php echo number_format($ltv, 0); ?></td>
                                    <td>
                                        <?php if ($p->last_booking): ?>
                                            <?php echo date('M j', strtotime($p->last_booking)); ?>
                                            <?php if ($is_inactive): ?>
                                            <br><small style="color:#EF4444;"><?php echo $days_since; ?>d ago</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:#6B7280;">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ltv > 500): ?>
                                            <span style="display:inline-flex;align-items:center;padding:2px 8px;background:#FEF3C7;color:#92400E;border-radius:4px;font-size:11px;font-weight:500;">🔥 Camp Bundle</span>
                                        <?php elseif ($is_inactive): ?>
                                            <span style="display:inline-flex;align-items:center;padding:2px 8px;background:#FEE2E2;color:#DC2626;border-radius:4px;font-size:11px;font-weight:500;">📧 Re-engage</span>
                                        <?php elseif ($p->player_count > 1): ?>
                                            <span style="display:inline-flex;align-items:center;padding:2px 8px;background:#DBEAFE;color:#1D4ED8;border-radius:4px;font-size:11px;font-weight:500;">👨‍👩‍👧‍👦 Sibling Deal</span>
                                        <?php else: ?>
                                            <span style="color:#6B7280;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo esc_attr($p->user_email); ?>" class="button button-small">Email</a>
                                        <?php if ($p->phone): ?>
                                        <a href="tel:<?php echo esc_attr($p->phone); ?>" class="button button-small">Call</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="ptp-empty-state">
                        <div class="ptp-empty-state-icon">
                            <span class="dashicons dashicons-admin-users"></span>
                        </div>
                        <h3>No parents found</h3>
                        <p>Parent accounts will appear here once they register.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /* =========================================================================
       PAYOUTS PAGE
       ========================================================================= */
    public function payouts_page() {
        global $wpdb;
        
        $success_message = '';
        $error_message = '';
        
        // Check if payouts table exists
        $payouts_table = $wpdb->prefix . 'ptp_payouts';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$payouts_table'") == $payouts_table;
        
        if (!$table_exists) {
            // Show helpful message if table doesn't exist
            ?>
            <div class="ptp-admin-wrap">
                <div class="ptp-admin-header">
                    <div class="ptp-admin-header-content">
                        <div class="ptp-admin-logo">
                            <span class="dashicons dashicons-universal-access"></span>
                        </div>
                        <div class="ptp-admin-title-wrap">
                            <h1 class="ptp-admin-title">PTP <span>Training</span></h1>
                            <p class="ptp-admin-subtitle">Manage trainer payouts</p>
                        </div>
                    </div>
                </div>
                
                <?php $this->render_nav('payouts'); ?>
                
                <div class="ptp-card">
                    <div class="ptp-card-body">
                        <div class="ptp-empty-state">
                            <div class="ptp-empty-state-icon">
                                <span class="dashicons dashicons-money-alt"></span>
                            </div>
                            <h3>Payouts Not Configured</h3>
                            <p>The payouts system hasn't been set up yet. Please ensure Stripe is configured in Settings.</p>
                            <a href="<?php echo admin_url('admin.php?page=ptp-settings'); ?>" class="ptp-btn ptp-btn-primary">Configure Settings</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            return;
        }
        
        // Get stats safely
        $stats = (object) array(
            'pending_count' => 0,
            'pending_total' => 0,
            'paid_30_days' => 0,
            'paid_all_time' => 0
        );
        
        $stats->pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ptp_payouts WHERE status = 'pending'") ?: 0;
        $stats->pending_total = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}ptp_payouts WHERE status = 'pending'") ?: 0;
        $stats->paid_30_days = $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}ptp_payouts WHERE status = 'completed' AND processed_at >= %s", date('Y-m-d', strtotime('-30 days')))) ?: 0;
        $stats->paid_all_time = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}ptp_payouts WHERE status = 'completed'") ?: 0;
        
        // Get pending payouts
        $pending = $wpdb->get_results("
            SELECT p.*, t.display_name as trainer_name, COALESCE(t.email, '') as trainer_email
            FROM {$wpdb->prefix}ptp_payouts p
            LEFT JOIN {$wpdb->prefix}ptp_trainers t ON p.trainer_id = t.id
            WHERE p.status = 'pending'
            ORDER BY p.created_at DESC
        ");
        
        // Get recent completed payouts
        $completed = $wpdb->get_results("
            SELECT p.*, t.display_name as trainer_name, COALESCE(t.email, '') as trainer_email
            FROM {$wpdb->prefix}ptp_payouts p
            LEFT JOIN {$wpdb->prefix}ptp_trainers t ON p.trainer_id = t.id
            WHERE p.status = 'completed'
            ORDER BY p.processed_at DESC
            LIMIT 20
        ");
        
        ?>
        <div class="ptp-admin-wrap">
            <div class="ptp-admin-header">
                <div class="ptp-admin-header-content">
                    <div class="ptp-admin-logo">
                        <span class="dashicons dashicons-universal-access"></span>
                    </div>
                    <div class="ptp-admin-title-wrap">
                        <h1 class="ptp-admin-title">PTP <span>Training</span></h1>
                        <p class="ptp-admin-subtitle">Manage trainer payouts</p>
                    </div>
                </div>
            </div>
            
            <nav class="ptp-admin-nav">
                <a href="<?php echo admin_url('admin.php?page=ptp-dashboard'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-dashboard"></span> Dashboard
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-trainers'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-groups"></span> Trainers
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-applications'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-portfolio"></span> Applications
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-bookings'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-calendar-alt"></span> Bookings
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-parents'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-admin-users"></span> Parents
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-payouts'); ?>" class="ptp-admin-nav-item active">
                    <span class="dashicons dashicons-money-alt"></span> Payouts
                    <?php if ($stats->pending_count > 0): ?>
                        <span class="count alert"><?php echo $stats->pending_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-messages'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-email"></span> Messages
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-settings'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-admin-settings"></span> Settings
                </a>
            </nav>
            
            <?php if (!empty($success_message)): ?>
                <div class="ptp-notice ptp-notice-success"><?php echo esc_html($success_message); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="ptp-notice ptp-notice-error"><?php echo esc_html($error_message); ?></div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="ptp-stats-grid">
                <div class="ptp-stat-card yellow">
                    <div class="ptp-stat-value">$<?php echo number_format($stats->pending_total, 0); ?></div>
                    <div class="ptp-stat-label">Pending Payouts</div>
                </div>
                <div class="ptp-stat-card">
                    <div class="ptp-stat-value">$<?php echo number_format($stats->paid_30_days, 0); ?></div>
                    <div class="ptp-stat-label">Paid (30 Days)</div>
                </div>
                <div class="ptp-stat-card">
                    <div class="ptp-stat-value">$<?php echo number_format($stats->paid_all_time, 0); ?></div>
                    <div class="ptp-stat-label">Paid (All Time)</div>
                </div>
                <div class="ptp-stat-card">
                    <div class="ptp-stat-value"><?php echo $stats->pending_count; ?></div>
                    <div class="ptp-stat-label">Trainers Awaiting</div>
                </div>
            </div>
            
            <!-- Pending Payouts -->
            <div class="ptp-card" style="margin-top: 24px;">
                <div class="ptp-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Pending Payouts</h3>
                    <?php if (!empty($pending)): ?>
                    <form method="post" style="margin: 0;">
                        <?php wp_nonce_field('ptp_batch_payouts'); ?>
                        <button type="submit" name="ptp_process_all" class="ptp-btn ptp-btn-primary" onclick="return confirm('Process all pending payouts? This will transfer funds to trainers.');">
                            Process All ($<?php echo number_format($stats->pending_total, 2); ?>)
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="ptp-card-body">
                    <?php if (empty($pending)): ?>
                        <div class="ptp-empty-state">
                            <div class="ptp-empty-state-icon">
                                <span class="dashicons dashicons-yes-alt"></span>
                            </div>
                            <h3>All caught up!</h3>
                            <p>No pending payouts at this time.</p>
                        </div>
                    <?php else: ?>
                        <table class="ptp-table">
                            <thead>
                                <tr>
                                    <th>Trainer</th>
                                    <th>Email</th>
                                    <th>Amount</th>
                                    <th>Stripe Status</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending as $payout): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($payout->trainer_name); ?></strong></td>
                                    <td><?php echo esc_html($payout->trainer_email); ?></td>
                                    <td><strong>$<?php echo number_format($payout->amount, 2); ?></strong></td>
                                    <td>
                                        <?php if ($payout->stripe_account_id): ?>
                                            <span class="ptp-badge ptp-badge-success">Connected</span>
                                        <?php else: ?>
                                            <span class="ptp-badge ptp-badge-warning">Not Connected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($payout->created_at)); ?></td>
                                    <td>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-payouts&action=process&id=' . $payout->id), 'ptp_process_payout'); ?>" 
                                           class="ptp-btn ptp-btn-sm ptp-btn-success"
                                           onclick="return confirm('Process this payout of $<?php echo number_format($payout->amount, 2); ?> to <?php echo esc_js($payout->trainer_name); ?>?');">
                                            Process
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Completed -->
            <?php if (!empty($completed)): ?>
            <div class="ptp-card" style="margin-top: 24px;">
                <div class="ptp-card-header">
                    <h3>Recent Payouts</h3>
                </div>
                <div class="ptp-card-body">
                    <table class="ptp-table">
                        <thead>
                            <tr>
                                <th>Trainer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Processed</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completed as $payout): ?>
                            <tr>
                                <td><?php echo esc_html($payout->trainer_name); ?></td>
                                <td><strong>$<?php echo number_format($payout->amount, 2); ?></strong></td>
                                <td><?php echo ucfirst($payout->payout_method); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($payout->processed_at)); ?></td>
                                <td>
                                    <?php if ($payout->payout_reference): ?>
                                        <code style="font-size: 11px;"><?php echo esc_html(substr($payout->payout_reference, 0, 20)); ?>...</code>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /* =========================================================================
       MESSAGES PAGE
       ========================================================================= */
    public function messages_page() {
        ?>
        <div class="ptp-admin-wrap">
            <div class="ptp-admin-header">
                <div class="ptp-admin-header-content">
                    <div class="ptp-admin-logo">
                        <span class="dashicons dashicons-universal-access"></span>
                    </div>
                    <div class="ptp-admin-title-wrap">
                        <h1 class="ptp-admin-title">PTP <span>Training</span></h1>
                        <p class="ptp-admin-subtitle">Platform conversations</p>
                    </div>
                </div>
            </div>
            
            <nav class="ptp-admin-nav">
                <a href="<?php echo admin_url('admin.php?page=ptp-dashboard'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-dashboard"></span> Dashboard
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-trainers'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-groups"></span> Trainers
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-applications'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-portfolio"></span> Applications
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-bookings'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-calendar-alt"></span> Bookings
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-parents'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-admin-users"></span> Parents
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-payouts'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-money-alt"></span> Payouts
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-messages'); ?>" class="ptp-admin-nav-item active">
                    <span class="dashicons dashicons-email"></span> Messages
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-settings'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-admin-settings"></span> Settings
                </a>
            </nav>
            
            <div class="ptp-card">
                <div class="ptp-card-body">
                    <div class="ptp-empty-state">
                        <div class="ptp-empty-state-icon">
                            <span class="dashicons dashicons-email"></span>
                        </div>
                        <h3>No conversations yet</h3>
                        <p>Messages between trainers and parents will appear here.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /* =========================================================================
       SETTINGS PAGE
       ========================================================================= */
    public function settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="ptp-admin-wrap">
            <div class="ptp-admin-header">
                <div class="ptp-admin-header-content">
                    <div class="ptp-admin-logo">
                        <span class="dashicons dashicons-universal-access"></span>
                    </div>
                    <div class="ptp-admin-title-wrap">
                        <h1 class="ptp-admin-title">PTP <span>Training</span></h1>
                        <p class="ptp-admin-subtitle">Platform configuration</p>
                    </div>
                </div>
            </div>
            
            <nav class="ptp-admin-nav">
                <a href="<?php echo admin_url('admin.php?page=ptp-dashboard'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-dashboard"></span> Dashboard
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-trainers'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-groups"></span> Trainers
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-applications'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-portfolio"></span> Applications
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-bookings'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-calendar-alt"></span> Bookings
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-parents'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-admin-users"></span> Parents
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-payouts'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-money-alt"></span> Payouts
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-messages'); ?>" class="ptp-admin-nav-item">
                    <span class="dashicons dashicons-email"></span> Messages
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-settings'); ?>" class="ptp-admin-nav-item active">
                    <span class="dashicons dashicons-admin-settings"></span> Settings
                </a>
            </nav>
            
            <div class="ptp-settings-tabs">
                <a href="?page=ptp-settings&tab=general" class="ptp-settings-tab <?php echo $active_tab === 'general' ? 'active' : ''; ?>">General</a>
                <a href="?page=ptp-settings&tab=company" class="ptp-settings-tab <?php echo $active_tab === 'company' ? 'active' : ''; ?>">Company / Tax</a>
                <a href="?page=ptp-settings&tab=payments" class="ptp-settings-tab <?php echo $active_tab === 'payments' ? 'active' : ''; ?>">Payments</a>
                <a href="?page=ptp-settings&tab=notifications" class="ptp-settings-tab <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>">Notifications</a>
                <a href="?page=ptp-settings&tab=sms" class="ptp-settings-tab <?php echo $active_tab === 'sms' ? 'active' : ''; ?>">SMS</a>
            </div>
            
            <?php if ($active_tab === 'general'): ?>
            <form method="post" action="options.php">
                <?php settings_fields('ptp_general'); ?>
                <div class="ptp-settings-section">
                    <div class="ptp-settings-section-header">
                        <h3>General Settings</h3>
                    </div>
                    <div class="ptp-settings-section-body">
                        <div class="ptp-form-row">
                            <label>Platform Fee (%)</label>
                            <input type="number" name="ptp_platform_fee" value="<?php echo esc_attr(get_option('ptp_platform_fee', 20)); ?>" min="0" max="50">
                            <p style="color: #6B7280; font-size: 12px; margin-top: 5px;">PTP keeps this percentage, trainers receive the rest (currently <?php echo 100 - intval(get_option('ptp_platform_fee', 20)); ?>%)</p>
                        </div>
                        <div class="ptp-form-row">
                            <label>Minimum Payout ($)</label>
                            <input type="number" name="ptp_min_payout" value="<?php echo esc_attr(get_option('ptp_min_payout', 25)); ?>" min="1" max="500">
                            <p style="color: #6B7280; font-size: 12px; margin-top: 5px;">Trainers must accumulate this amount before automatic payout</p>
                        </div>
                        <div class="ptp-form-row">
                            <label>Cancellation Refund Window (hours)</label>
                            <input type="number" name="ptp_refund_window" value="<?php echo esc_attr(get_option('ptp_refund_window', 24)); ?>" min="1" max="168">
                            <p style="color: #6B7280; font-size: 12px; margin-top: 5px;">Full refund if cancelled this many hours before session</p>
                        </div>
                        <div class="ptp-form-row">
                            <label>From Email</label>
                            <input type="email" name="ptp_from_email" value="<?php echo esc_attr(get_option('ptp_from_email', get_option('admin_email'))); ?>">
                        </div>
                        <div class="ptp-form-row">
                            <label>Google Maps API Key</label>
                            <input type="text" name="ptp_google_maps_api_key" value="<?php echo esc_attr(get_option('ptp_google_maps_api_key')); ?>" placeholder="AIzaSy...">
                            <?php $has_maps_key = !empty(get_option('ptp_google_maps_api_key')); ?>
                            <div style="display: flex; align-items: center; gap: 6px; margin-top: 8px;">
                                <span style="color: <?php echo $has_maps_key ? '#059669' : '#DC2626'; ?>; font-size: 16px;">●</span>
                                <span style="font-size: 13px; color: <?php echo $has_maps_key ? '#059669' : '#DC2626'; ?>;">
                                    <?php echo $has_maps_key ? '✓ API Key configured' : '✗ API Key not set - Map will not display'; ?>
                                </span>
                            </div>
                            <p style="color: #6B7280; font-size: 12px; margin-top: 5px;">
                                Required for trainer map on Find Trainers page and location features.
                            </p>
                            <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 12px; margin-top: 10px;">
                                <h4 style="margin: 0 0 8px; color: #1E40AF; font-size: 13px;">📍 Google Maps Setup:</h4>
                                <ol style="margin: 0; padding-left: 16px; color: #1E40AF; font-size: 12px; line-height: 1.6;">
                                    <li>Go to <a href="https://console.cloud.google.com/google/maps-apis" target="_blank" style="color: #2563EB;">Google Cloud Console</a></li>
                                    <li>Create a project or select existing</li>
                                    <li>Enable these APIs: <strong>Maps JavaScript API</strong>, <strong>Places API</strong>, <strong>Geocoding API</strong></li>
                                    <li>Create an API key under "Credentials"</li>
                                    <li>Restrict the key to your domain for security</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <p><button type="submit" class="ptp-btn ptp-btn-primary ptp-btn-lg">Save Settings</button></p>
            </form>
            
            <?php elseif ($active_tab === 'company'): ?>
            <form method="post" action="options.php">
                <?php settings_fields('ptp_company'); ?>
                <div class="ptp-settings-section">
                    <div class="ptp-settings-section-header">
                        <h3>Company Information (for 1099-NEC)</h3>
                        <p style="color: #6B7280; font-size: 13px; margin: 5px 0 0;">This information appears on 1099-NEC forms sent to trainers</p>
                    </div>
                    <div class="ptp-settings-section-body">
                        <div class="ptp-form-row">
                            <label>Company Legal Name *</label>
                            <input type="text" name="ptp_company_name" value="<?php echo esc_attr(get_option('ptp_company_name', 'Players Teaching Players LLC')); ?>" required>
                        </div>
                        <div class="ptp-form-row">
                            <label>Company EIN *</label>
                            <input type="text" name="ptp_company_ein" value="<?php echo esc_attr(get_option('ptp_company_ein')); ?>" placeholder="XX-XXXXXXX">
                            <p style="color: #6B7280; font-size: 12px; margin-top: 5px;">Your Employer Identification Number (required for 1099s)</p>
                        </div>
                        <div class="ptp-form-row">
                            <label>Street Address *</label>
                            <input type="text" name="ptp_company_address" value="<?php echo esc_attr(get_option('ptp_company_address')); ?>">
                        </div>
                        <div class="ptp-form-row">
                            <label>City *</label>
                            <input type="text" name="ptp_company_city" value="<?php echo esc_attr(get_option('ptp_company_city')); ?>">
                        </div>
                        <div class="ptp-form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label>State *</label>
                                <select name="ptp_company_state">
                                    <option value="">Select</option>
                                    <?php
                                    $states = array('AL','AK','AZ','AR','CA','CO','CT','DE','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY','DC');
                                    $current_state = get_option('ptp_company_state', '');
                                    foreach ($states as $st): ?>
                                        <option value="<?php echo $st; ?>" <?php selected($current_state, $st); ?>><?php echo $st; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>ZIP Code *</label>
                                <input type="text" name="ptp_company_zip" value="<?php echo esc_attr(get_option('ptp_company_zip')); ?>">
                            </div>
                        </div>
                        <div class="ptp-form-row">
                            <label>Company Phone</label>
                            <input type="tel" name="ptp_company_phone" value="<?php echo esc_attr(get_option('ptp_company_phone')); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="ptp-settings-section">
                    <div class="ptp-settings-section-header">
                        <h3>1099 Filing Settings</h3>
                    </div>
                    <div class="ptp-settings-section-body">
                        <div class="ptp-form-row">
                            <label>1099 Threshold</label>
                            <input type="number" name="ptp_1099_threshold" value="<?php echo esc_attr(get_option('ptp_1099_threshold', 600)); ?>" min="0">
                            <p style="color: #6B7280; font-size: 12px; margin-top: 5px;">IRS requires 1099-NEC for payments of $600 or more</p>
                        </div>
                        <div class="ptp-form-row">
                            <label>
                                <input type="checkbox" name="ptp_require_w9" value="1" <?php checked(get_option('ptp_require_w9', 1)); ?>>
                                Require W-9 before trainer activation
                            </label>
                        </div>
                        <div class="ptp-form-row">
                            <label>
                                <input type="checkbox" name="ptp_require_safesport" value="1" <?php checked(get_option('ptp_require_safesport', 1)); ?>>
                                Require SafeSport certificate before trainer activation
                            </label>
                        </div>
                    </div>
                </div>
                
                <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; color: #1E40AF; font-size: 13px;">
                        <strong>📋 1099 Reporting:</strong> Go to <a href="<?php echo admin_url('admin.php?page=ptp-tax-reports'); ?>">PTP Training → 1099 Reports</a> to view trainers who need 1099s and export tax data.
                    </p>
                </div>
                
                <p><button type="submit" class="ptp-btn ptp-btn-primary ptp-btn-lg">Save Settings</button></p>
            </form>
            
            <?php elseif ($active_tab === 'payments'): ?>
            <form method="post" action="options.php">
                <?php settings_fields('ptp_stripe'); ?>
                <div class="ptp-settings-section">
                    <div class="ptp-settings-section-header">
                        <h3>Stripe Configuration</h3>
                    </div>
                    <div class="ptp-settings-section-body">
                        <div class="ptp-form-row">
                            <label>Test Mode</label>
                            <select name="ptp_stripe_test_mode">
                                <option value="1" <?php selected(get_option('ptp_stripe_test_mode', 1), 1); ?>>Yes</option>
                                <option value="0" <?php selected(get_option('ptp_stripe_test_mode', 1), 0); ?>>No</option>
                            </select>
                        </div>
                        <div class="ptp-form-row">
                            <label>Test Publishable Key</label>
                            <input type="text" name="ptp_stripe_test_publishable" value="<?php echo esc_attr(get_option('ptp_stripe_test_publishable')); ?>">
                        </div>
                        <div class="ptp-form-row">
                            <label>Test Secret Key</label>
                            <input type="password" name="ptp_stripe_test_secret" value="<?php echo esc_attr(get_option('ptp_stripe_test_secret')); ?>">
                        </div>
                        <div class="ptp-form-row">
                            <label>Live Publishable Key</label>
                            <input type="text" name="ptp_stripe_live_publishable" value="<?php echo esc_attr(get_option('ptp_stripe_live_publishable')); ?>">
                        </div>
                        <div class="ptp-form-row">
                            <label>Live Secret Key</label>
                            <input type="password" name="ptp_stripe_live_secret" value="<?php echo esc_attr(get_option('ptp_stripe_live_secret')); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="ptp-settings-section">
                    <div class="ptp-settings-section-header">
                        <h3>Stripe Connect (Trainer Payouts)</h3>
                        <p style="color: #6B7280; font-size: 13px; margin: 5px 0 0;">Enable direct deposits to trainers via Stripe Connect</p>
                    </div>
                    <div class="ptp-settings-section-body">
                        <div style="background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                            <h4 style="margin: 0 0 10px; color: #92400E;">⚠️ Setup Required in Stripe Dashboard</h4>
                            <p style="margin: 0 0 10px; color: #78350F; font-size: 13px;">Before enabling Stripe Connect, you must:</p>
                            <ol style="margin: 0; padding-left: 20px; color: #78350F; font-size: 13px;">
                                <li style="margin-bottom: 5px;">Log in to <a href="https://dashboard.stripe.com" target="_blank" style="color: #1D4ED8;">Stripe Dashboard</a></li>
                                <li style="margin-bottom: 5px;">Go to <strong>Settings → Connect → Get Started</strong></li>
                                <li style="margin-bottom: 5px;">Complete the Connect platform application</li>
                                <li style="margin-bottom: 5px;">Set platform type as <strong>"Express"</strong></li>
                                <li style="margin-bottom: 5px;">Configure your branding and payout settings</li>
                                <li>Wait for Stripe to approve your Connect application</li>
                            </ol>
                            <p style="margin: 10px 0 0; color: #78350F; font-size: 12px;"><a href="https://stripe.com/docs/connect" target="_blank" style="color: #1D4ED8;">📖 Stripe Connect Documentation</a></p>
                        </div>
                        
                        <div class="ptp-form-row">
                            <label>Enable Stripe Connect</label>
                            <select name="ptp_stripe_connect_enabled">
                                <option value="0" <?php selected(get_option('ptp_stripe_connect_enabled', 0), 0); ?>>Disabled - Setup not complete</option>
                                <option value="1" <?php selected(get_option('ptp_stripe_connect_enabled', 0), 1); ?>>Enabled - Connect is ready</option>
                            </select>
                            <p style="color: #6B7280; font-size: 12px; margin-top: 5px;">Only enable after completing Stripe Connect setup above</p>
                        </div>
                        
                        <!-- Configuration Status -->
                        <?php
                        $test_mode = get_option('ptp_stripe_test_mode', true);
                        $has_test_secret = !empty(get_option('ptp_stripe_test_secret'));
                        $has_test_pub = !empty(get_option('ptp_stripe_test_publishable'));
                        $has_live_secret = !empty(get_option('ptp_stripe_live_secret'));
                        $has_live_pub = !empty(get_option('ptp_stripe_live_publishable'));
                        $connect_enabled = get_option('ptp_stripe_connect_enabled', false);
                        
                        $current_mode = $test_mode ? 'Test' : 'Live';
                        $has_keys = $test_mode ? ($has_test_secret && $has_test_pub) : ($has_live_secret && $has_live_pub);
                        ?>
                        <div style="background: #F3F4F6; border-radius: 8px; padding: 15px; margin-top: 20px;">
                            <h4 style="margin: 0 0 12px; font-size: 14px; font-weight: 600;">📊 Current Configuration Status</h4>
                            <div style="display: grid; gap: 8px; font-size: 13px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: <?php echo $test_mode ? '#059669' : '#DC2626'; ?>;">●</span>
                                    <strong>Mode:</strong> <?php echo $current_mode; ?> Mode
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: <?php echo $has_keys ? '#059669' : '#DC2626'; ?>;">●</span>
                                    <strong><?php echo $current_mode; ?> API Keys:</strong> 
                                    <?php echo $has_keys ? '✓ Configured' : '✗ Missing'; ?>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: <?php echo $connect_enabled ? '#059669' : '#F59E0B'; ?>;">●</span>
                                    <strong>Stripe Connect:</strong> 
                                    <?php echo $connect_enabled ? '✓ Enabled' : '⚠ Disabled'; ?>
                                </div>
                            </div>
                            
                            <?php if (!$has_keys || !$connect_enabled): ?>
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #E5E7EB;">
                                <p style="margin: 0; color: #92400E; font-size: 12px; font-weight: 600;">⚠️ Action Required:</p>
                                <ul style="margin: 8px 0 0; padding-left: 16px; color: #78350F; font-size: 12px;">
                                    <?php if (!$has_keys): ?>
                                    <li>Add your Stripe <?php echo $current_mode; ?> API keys above</li>
                                    <?php endif; ?>
                                    <?php if (!$connect_enabled): ?>
                                    <li>Complete Stripe Connect setup in your Stripe Dashboard, then enable it here</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <?php else: ?>
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #E5E7EB;">
                                <p style="margin: 0; color: #059669; font-size: 12px; font-weight: 600;">✓ Stripe is fully configured!</p>
                                <p style="margin: 4px 0 0; color: #6B7280; font-size: 12px;">Trainers can now connect their bank accounts for payouts.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <p><button type="submit" class="ptp-btn ptp-btn-primary ptp-btn-lg">Save Settings</button></p>
            </form>
            
            <?php elseif ($active_tab === 'notifications'): ?>
            <form method="post" action="options.php">
                <?php settings_fields('ptp_notifications'); ?>
                <div class="ptp-settings-section">
                    <div class="ptp-settings-section-header">
                        <h3>Notification Settings</h3>
                    </div>
                    <div class="ptp-settings-section-body">
                        <div class="ptp-form-row">
                            <label>Email Booking Confirmation</label>
                            <select name="ptp_email_booking_confirmation">
                                <option value="1" <?php selected(get_option('ptp_email_booking_confirmation', 1), 1); ?>>Enabled</option>
                                <option value="0" <?php selected(get_option('ptp_email_booking_confirmation', 1), 0); ?>>Disabled</option>
                            </select>
                        </div>
                        <div class="ptp-form-row">
                            <label>Email Session Reminder</label>
                            <select name="ptp_email_session_reminder">
                                <option value="1" <?php selected(get_option('ptp_email_session_reminder', 1), 1); ?>>Enabled</option>
                                <option value="0" <?php selected(get_option('ptp_email_session_reminder', 1), 0); ?>>Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="ptp-settings-section">
                    <div class="ptp-settings-section-header">
                        <h3>Push Notifications (Mobile App)</h3>
                        <p style="color: #6B7280; font-size: 13px; margin: 5px 0 0;">Firebase Cloud Messaging for iOS/Android push notifications</p>
                    </div>
                    <div class="ptp-settings-section-body">
                        <div class="ptp-form-row">
                            <label>FCM Server Key</label>
                            <input type="password" name="ptp_fcm_server_key" value="<?php echo esc_attr(get_option('ptp_fcm_server_key')); ?>" placeholder="Enter Firebase Cloud Messaging server key">
                            <p style="color: #6B7280; font-size: 12px; margin-top: 5px;">Get this from Firebase Console → Project Settings → Cloud Messaging</p>
                        </div>
                    </div>
                </div>
                <p><button type="submit" class="ptp-btn ptp-btn-primary ptp-btn-lg">Save Settings</button></p>
            </form>
            
            <?php elseif ($active_tab === 'sms'): ?>
            <form method="post" action="options.php">
                <?php settings_fields('ptp_twilio'); ?>
                <div class="ptp-settings-section">
                    <div class="ptp-settings-section-header">
                        <h3>Twilio SMS Settings</h3>
                    </div>
                    <div class="ptp-settings-section-body">
                        <div class="ptp-form-row">
                            <label>Enable SMS</label>
                            <select name="ptp_sms_enabled">
                                <option value="1" <?php selected(get_option('ptp_sms_enabled'), 1); ?>>Enabled</option>
                                <option value="0" <?php selected(get_option('ptp_sms_enabled'), 0); ?>>Disabled</option>
                            </select>
                        </div>
                        <div class="ptp-form-row">
                            <label>Account SID</label>
                            <input type="text" name="ptp_twilio_sid" value="<?php echo esc_attr(get_option('ptp_twilio_sid')); ?>">
                        </div>
                        <div class="ptp-form-row">
                            <label>Auth Token</label>
                            <input type="password" name="ptp_twilio_token" value="<?php echo esc_attr(get_option('ptp_twilio_token')); ?>">
                        </div>
                        <div class="ptp-form-row">
                            <label>From Number</label>
                            <input type="text" name="ptp_twilio_from" value="<?php echo esc_attr(get_option('ptp_twilio_from')); ?>">
                        </div>
                    </div>
                </div>
                <p><button type="submit" class="ptp-btn ptp-btn-primary ptp-btn-lg">Save Settings</button></p>
            </form>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /* =========================================================================
       HANDLE ACTIONS
       ========================================================================= */
    public function handle_actions() {
        // SECURITY: Require admin capability for all actions
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (!isset($_GET['action']) && !isset($_POST['trainer_id'])) return;
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        
        global $wpdb;
        
        // Handle trainer profile update (POST)
        if ($action === 'update_trainer' && isset($_POST['trainer_id']) && wp_verify_nonce($_POST['ptp_trainer_nonce'] ?? '', 'ptp_update_trainer')) {
            $trainer_id = absint($_POST['trainer_id']);
            
            $update_data = array(
                'display_name' => sanitize_text_field($_POST['display_name'] ?? ''),
                'headline' => sanitize_text_field($_POST['headline'] ?? ''),
                'location' => sanitize_text_field($_POST['location'] ?? ''),
                'hourly_rate' => floatval($_POST['hourly_rate'] ?? 75),
                'college' => sanitize_text_field($_POST['college'] ?? ''),
                'playing_level' => sanitize_text_field($_POST['playing_level'] ?? ''),
                'phone' => sanitize_text_field($_POST['phone'] ?? ''),
                'travel_radius' => intval($_POST['travel_radius'] ?? 15),
                'bio' => sanitize_textarea_field($_POST['bio'] ?? ''),
                'specialties' => sanitize_text_field($_POST['specialties'] ?? ''),
                'status' => sanitize_text_field($_POST['status'] ?? 'pending'),
                'is_featured' => intval($_POST['is_featured'] ?? 0),
            );
            
            $wpdb->update(
                $wpdb->prefix . 'ptp_trainers',
                $update_data,
                array('id' => $trainer_id),
                array('%s', '%s', '%s', '%f', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d'),
                array('%d')
            );
            
            wp_safe_redirect(admin_url('admin.php?page=ptp-trainers&action=view&id=' . $trainer_id . '&updated=1'));
            exit;
        }
        
        // Trainer view action (no nonce needed for view)
        if ($action === 'view' && $id && isset($_GET['page']) && $_GET['page'] === 'ptp-trainers') {
            $this->trainer_detail_page($id);
            exit;
        }
        
        // Actions requiring nonce
        if (!isset($_GET['_wpnonce'])) return;
        
        // Trainer actions
        if (in_array($action, array('activate', 'deactivate', 'delete'), true) && wp_verify_nonce($_GET['_wpnonce'], 'ptp_trainer_action')) {
            $table = $wpdb->prefix . 'ptp_trainers';
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) != $table) return;
            
            if ($action === 'delete') {
                // Delete trainer record
                $wpdb->delete("{$wpdb->prefix}ptp_trainers", array('id' => $id), array('%d'));
                wp_safe_redirect(admin_url('admin.php?page=ptp-trainers&trainer_deleted=1'));
                exit;
            }
            
            $new_status = $action === 'activate' ? 'active' : 'inactive';
            $wpdb->update("{$wpdb->prefix}ptp_trainers", array('status' => $new_status), array('id' => $id), array('%s'), array('%d'));
            wp_safe_redirect(admin_url('admin.php?page=ptp-trainers&trainer_updated=1'));
            exit;
        }
        
        // Document verification actions
        if (in_array($action, array('verify_safesport', 'reject_safesport', 'verify_background', 'reject_background'), true) && wp_verify_nonce($_GET['_wpnonce'], 'ptp_verify_doc')) {
            $table = $wpdb->prefix . 'ptp_trainers';
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) != $table) return;
            
            if ($action === 'verify_safesport') {
                $wpdb->update($table, array('safesport_verified' => 1, 'is_background_checked' => 1), array('id' => $id), array('%d', '%d'), array('%d'));
            } elseif ($action === 'reject_safesport') {
                $wpdb->update($table, array('safesport_verified' => 0, 'safesport_doc_url' => ''), array('id' => $id), array('%d', '%s'), array('%d'));
            } elseif ($action === 'verify_background') {
                $wpdb->update($table, array('background_verified' => 1), array('id' => $id), array('%d'), array('%d'));
            } elseif ($action === 'reject_background') {
                $wpdb->update($table, array('background_verified' => 0, 'background_doc_url' => ''), array('id' => $id), array('%d', '%s'), array('%d'));
            }
            
            wp_safe_redirect(admin_url('admin.php?page=ptp-trainers&action=view&id=' . $id . '&doc_updated=1'));
            exit;
        }
    }
    
    /**
     * Trainer Detail / Compliance Page
     */
    private function trainer_detail_page($trainer_id) {
        global $wpdb;
        
        $trainer = $wpdb->get_row($wpdb->prepare("
            SELECT t.*, u.user_email, u.user_registered 
            FROM {$wpdb->prefix}ptp_trainers t 
            JOIN {$wpdb->users} u ON t.user_id = u.ID 
            WHERE t.id = %d
        ", $trainer_id));
        
        if (!$trainer) {
            wp_die('Trainer not found');
        }
        
        // Get earnings
        $earnings = $wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}ptp_payouts 
            WHERE trainer_id = %d AND status = 'completed'
        ", $trainer_id)) ?: 0;
        
        // Compliance checks
        $has_safesport = !empty($trainer->safesport_doc_url);
        $safesport_verified = !empty($trainer->safesport_verified);
        $has_background = !empty($trainer->background_doc_url);
        $background_verified = !empty($trainer->background_verified);
        $has_w9 = !empty($trainer->w9_submitted);
        $has_stripe = !empty($trainer->stripe_account_id) && !empty($trainer->stripe_charges_enabled);
        $compliance_complete = $safesport_verified && $has_w9;
        
        ?>
        <div class="wrap">
            <h1>
                <a href="<?php echo admin_url('admin.php?page=ptp-trainers'); ?>">← Trainers</a> / 
                <?php echo esc_html($trainer->display_name); ?>
            </h1>
            
            <?php if (isset($_GET['doc_updated'])): ?>
            <div class="notice notice-success"><p>Document status updated!</p></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated'])): ?>
            <div class="notice notice-success"><p>✓ Trainer profile updated successfully!</p></div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <!-- Left Column: Profile Info -->
                <div>
                    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <h2 style="margin-top: 0;">👤 Profile Information</h2>
                        <table class="form-table">
                            <tr><th>Name</th><td><?php echo esc_html($trainer->display_name); ?></td></tr>
                            <tr><th>Legal Name</th><td><?php echo esc_html($trainer->legal_name ?: '-'); ?></td></tr>
                            <tr><th>Email</th><td><?php echo esc_html($trainer->user_email); ?></td></tr>
                            <tr><th>Phone</th><td><?php echo esc_html($trainer->phone ?: '-'); ?></td></tr>
                            <tr><th>Location</th><td><?php echo esc_html($trainer->location ?: '-'); ?></td></tr>
                            <tr><th>Hourly Rate</th><td>$<?php echo number_format($trainer->hourly_rate, 0); ?></td></tr>
                            <tr><th>College</th><td><?php echo esc_html($trainer->college ?: '-'); ?></td></tr>
                            <tr><th>Status</th><td><span class="ptp-status ptp-status-<?php echo esc_attr($trainer->status); ?>"><?php echo ucfirst($trainer->status); ?></span></td></tr>
                            <tr><th>Joined</th><td><?php echo date('M j, Y', strtotime($trainer->created_at)); ?></td></tr>
                            <tr><th>Total Sessions</th><td><?php echo $trainer->total_sessions; ?></td></tr>
                            <tr><th>Total Earnings</th><td>$<?php echo number_format($earnings, 2); ?></td></tr>
                        </table>
                        
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #E5E7EB;">
                            <a href="<?php echo home_url('/trainer/' . $trainer->slug); ?>" class="button" target="_blank">View Public Profile</a>
                            <?php if ($trainer->status === 'active'): ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-trainers&action=deactivate&id=' . $trainer->id), 'ptp_trainer_action'); ?>" class="button">Deactivate</a>
                            <?php elseif ($compliance_complete): ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-trainers&action=activate&id=' . $trainer->id), 'ptp_trainer_action'); ?>" class="button button-primary">Activate Trainer</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Compliance -->
                <div>
                    <!-- Compliance Status -->
                    <div style="background: <?php echo $compliance_complete ? '#D1FAE5' : '#FEF3C7'; ?>; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0; color: <?php echo $compliance_complete ? '#065F46' : '#92400E'; ?>;">
                            <?php echo $compliance_complete ? '✅ Compliance Complete' : '⚠️ Compliance Incomplete'; ?>
                        </h3>
                        <p style="margin: 0; color: <?php echo $compliance_complete ? '#065F46' : '#92400E'; ?>;">
                            <?php echo $compliance_complete ? 'This trainer has completed all required compliance steps and can be activated.' : 'This trainer needs to complete the items below before activation.'; ?>
                        </p>
                    </div>
                    
                    <!-- SafeSport Document -->
                    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <h3 style="margin-top: 0;">🛡️ SafeSport Certificate</h3>
                        <?php if ($has_safesport): ?>
                            <div style="margin-bottom: 15px;">
                                <a href="<?php echo esc_url($trainer->safesport_doc_url); ?>" target="_blank" class="button">📄 View Document</a>
                            </div>
                            <?php if ($safesport_verified): ?>
                                <div style="background: #D1FAE5; padding: 12px; border-radius: 6px; color: #065F46;">
                                    ✅ <strong>Verified</strong> - Document has been reviewed and approved
                                </div>
                            <?php else: ?>
                                <div style="background: #FEF3C7; padding: 12px; border-radius: 6px; color: #92400E; margin-bottom: 10px;">
                                    ⏳ <strong>Pending Review</strong> - Please review the document
                                </div>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-trainers&action=verify_safesport&id=' . $trainer->id), 'ptp_verify_doc'); ?>" class="button button-primary">✓ Verify & Approve</a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-trainers&action=reject_safesport&id=' . $trainer->id), 'ptp_verify_doc'); ?>" class="button" onclick="return confirm('Reject this document? The trainer will need to upload a new one.');">✗ Reject</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="background: #FEE2E2; padding: 12px; border-radius: 6px; color: #DC2626;">
                                ❌ <strong>Not Submitted</strong> - Trainer has not uploaded a SafeSport certificate
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Background Check -->
                    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <h3 style="margin-top: 0;">📋 Background Check</h3>
                        <?php if ($has_background): ?>
                            <div style="margin-bottom: 15px;">
                                <a href="<?php echo esc_url($trainer->background_doc_url); ?>" target="_blank" class="button">📄 View Document</a>
                            </div>
                            <?php if ($background_verified): ?>
                                <div style="background: #D1FAE5; padding: 12px; border-radius: 6px; color: #065F46;">
                                    ✅ <strong>Verified</strong>
                                </div>
                            <?php else: ?>
                                <div style="background: #FEF3C7; padding: 12px; border-radius: 6px; color: #92400E; margin-bottom: 10px;">
                                    ⏳ <strong>Pending Review</strong>
                                </div>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-trainers&action=verify_background&id=' . $trainer->id), 'ptp_verify_doc'); ?>" class="button button-primary">✓ Verify</a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ptp-trainers&action=reject_background&id=' . $trainer->id), 'ptp_verify_doc'); ?>" class="button">✗ Reject</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="background: #F3F4F6; padding: 12px; border-radius: 6px; color: #6B7280;">
                                — <strong>Not Submitted</strong> (Optional)
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- W-9 Tax Info -->
                    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <h3 style="margin-top: 0;">📝 W-9 / Tax Information</h3>
                        <?php if ($has_w9): ?>
                            <div style="background: #D1FAE5; padding: 12px; border-radius: 6px; color: #065F46; margin-bottom: 15px;">
                                ✅ <strong>W-9 Submitted</strong> on <?php echo date('M j, Y', strtotime($trainer->w9_submitted_at)); ?>
                            </div>
                            <table class="form-table" style="margin: 0;">
                                <tr><th>Legal Name</th><td><?php echo esc_html($trainer->legal_name); ?></td></tr>
                                <tr><th>Tax ID</th><td><?php echo strtoupper($trainer->tax_id_type ?: 'SSN'); ?>: ***-**-<?php echo esc_html($trainer->tax_id_last4); ?></td></tr>
                                <tr><th>Address</th><td>
                                    <?php echo esc_html($trainer->tax_address_line1); ?><br>
                                    <?php if ($trainer->tax_address_line2) echo esc_html($trainer->tax_address_line2) . '<br>'; ?>
                                    <?php echo esc_html($trainer->tax_city . ', ' . $trainer->tax_state . ' ' . $trainer->tax_zip); ?>
                                </td></tr>
                            </table>
                        <?php else: ?>
                            <div style="background: #FEE2E2; padding: 12px; border-radius: 6px; color: #DC2626;">
                                ❌ <strong>Not Submitted</strong> - Required for 1099 reporting
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Contractor Agreement -->
                    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <h3 style="margin-top: 0;">📜 Contractor Agreement</h3>
                        <?php if (!empty($trainer->contractor_agreement_signed)): ?>
                            <div style="background: #D1FAE5; padding: 12px; border-radius: 6px; color: #065F46;">
                                ✅ <strong>Signed</strong> on <?php echo date('M j, Y \a\t g:i A', strtotime($trainer->contractor_agreement_signed_at)); ?>
                            </div>
                            <p style="margin: 10px 0 0; font-size: 12px; color: #6B7280;">
                                IP Address: <?php echo esc_html($trainer->contractor_agreement_ip ?: 'N/A'); ?>
                            </p>
                        <?php else: ?>
                            <div style="background: #FEE2E2; padding: 12px; border-radius: 6px; color: #DC2626;">
                                ❌ <strong>Not Signed</strong> - Trainer must agree to terms
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Stripe Payouts -->
                    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0;">💳 Stripe Payouts</h3>
                        <?php if ($has_stripe): ?>
                            <div style="background: #D1FAE5; padding: 12px; border-radius: 6px; color: #065F46;">
                                ✅ <strong>Connected</strong> - Trainer can receive direct deposits
                            </div>
                            <p style="margin: 10px 0 0; font-size: 13px; color: #6B7280;">
                                Account ID: <?php echo esc_html(substr($trainer->stripe_account_id, 0, 12) . '...'); ?>
                            </p>
                        <?php elseif ($trainer->stripe_account_id): ?>
                            <div style="background: #FEF3C7; padding: 12px; border-radius: 6px; color: #92400E;">
                                ⏳ <strong>Setup Incomplete</strong> - Trainer needs to finish Stripe onboarding
                            </div>
                        <?php else: ?>
                            <div style="background: #F3F4F6; padding: 12px; border-radius: 6px; color: #6B7280;">
                                — <strong>Not Connected</strong> - Trainer will be prompted to connect Stripe
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Full Width Sections -->
            <div style="grid-column: 1 / -1;">
                <!-- Edit Trainer Profile -->
                <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">✏️ Edit Profile</h2>
                    <form method="post" action="<?php echo admin_url('admin.php?page=ptp-trainers&action=update_trainer&id=' . $trainer->id); ?>">
                        <?php wp_nonce_field('ptp_update_trainer', 'ptp_trainer_nonce'); ?>
                        <input type="hidden" name="trainer_id" value="<?php echo $trainer->id; ?>">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Display Name</label>
                                <input type="text" name="display_name" value="<?php echo esc_attr($trainer->display_name); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Headline</label>
                                <input type="text" name="headline" value="<?php echo esc_attr($trainer->headline ?? ''); ?>" placeholder="e.g., NCAA D1 Player at Villanova" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Location</label>
                                <input type="text" name="location" value="<?php echo esc_attr($trainer->location ?? ''); ?>" placeholder="City, State" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Hourly Rate ($)</label>
                                <input type="number" name="hourly_rate" value="<?php echo esc_attr($trainer->hourly_rate ?? 75); ?>" min="25" max="300" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">College/Team</label>
                                <input type="text" name="college" value="<?php echo esc_attr($trainer->college ?? ''); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Playing Level</label>
                                <select name="playing_level" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                                    <option value="">Select Level</option>
                                    <option value="pro" <?php selected($trainer->playing_level ?? '', 'pro'); ?>>Professional</option>
                                    <option value="college_d1" <?php selected($trainer->playing_level ?? '', 'college_d1'); ?>>NCAA D1</option>
                                    <option value="college_d2" <?php selected($trainer->playing_level ?? '', 'college_d2'); ?>>NCAA D2</option>
                                    <option value="college_d3" <?php selected($trainer->playing_level ?? '', 'college_d3'); ?>>NCAA D3</option>
                                    <option value="academy" <?php selected($trainer->playing_level ?? '', 'academy'); ?>>MLS/NWSL Academy</option>
                                    <option value="semi_pro" <?php selected($trainer->playing_level ?? '', 'semi_pro'); ?>>Semi-Pro</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Phone</label>
                                <input type="text" name="phone" value="<?php echo esc_attr($trainer->phone ?? ''); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Travel Radius (miles)</label>
                                <input type="number" name="travel_radius" value="<?php echo esc_attr($trainer->travel_radius ?? 15); ?>" min="5" max="100" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Bio</label>
                                <textarea name="bio" rows="4" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;"><?php echo esc_textarea($trainer->bio ?? ''); ?></textarea>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Specialties (comma-separated)</label>
                                <input type="text" name="specialties" value="<?php echo esc_attr($trainer->specialties ?? ''); ?>" placeholder="shooting, dribbling, passing, defense" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Status</label>
                                <select name="status" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                                    <option value="active" <?php selected($trainer->status, 'active'); ?>>Active</option>
                                    <option value="pending" <?php selected($trainer->status, 'pending'); ?>>Pending</option>
                                    <option value="suspended" <?php selected($trainer->status, 'suspended'); ?>>Suspended</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Featured Trainer</label>
                                <select name="is_featured" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                                    <option value="0" <?php selected($trainer->is_featured ?? 0, 0); ?>>No</option>
                                    <option value="1" <?php selected($trainer->is_featured ?? 0, 1); ?>>Yes</option>
                                </select>
                            </div>
                        </div>
                        <p style="margin-top: 20px;">
                            <button type="submit" class="button button-primary button-large">💾 Save Changes</button>
                        </p>
                    </form>
                </div>
                
                <!-- Stripe Transactions -->
                <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h2 style="margin-top: 0;">💳 Transaction History</h2>
                    <?php
                    // Get bookings with payment info
                    $transactions = $wpdb->get_results($wpdb->prepare("
                        SELECT b.*, p.name as player_name, par.display_name as parent_name
                        FROM {$wpdb->prefix}ptp_bookings b
                        LEFT JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
                        LEFT JOIN {$wpdb->prefix}ptp_parents par ON b.parent_id = par.id
                        WHERE b.trainer_id = %d AND b.payment_status IN ('paid', 'refunded')
                        ORDER BY b.created_at DESC
                        LIMIT 20
                    ", $trainer_id));
                    
                    if (!empty($transactions)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Session</th>
                                <th>Amount</th>
                                <th>Trainer Payout</th>
                                <th>Status</th>
                                <th>Stripe ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $txn): 
                                $platform_fee = get_option('ptp_platform_fee', 20);
                                $trainer_payout = $txn->total_amount * (1 - ($platform_fee / 100));
                            ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($txn->created_at)); ?></td>
                                <td><?php echo esc_html($txn->parent_name ?: 'N/A'); ?></td>
                                <td><?php echo date('M j', strtotime($txn->session_date)) . ' @ ' . date('g:i A', strtotime($txn->session_time)); ?></td>
                                <td><strong>$<?php echo number_format($txn->total_amount, 2); ?></strong></td>
                                <td style="color: #059669;">$<?php echo number_format($trainer_payout, 2); ?></td>
                                <td>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 100px; font-size: 12px; font-weight: 600; <?php 
                                        echo $txn->payment_status === 'paid' ? 'background: #D1FAE5; color: #065F46;' : 'background: #FEE2E2; color: #DC2626;'; 
                                    ?>">
                                        <?php echo $txn->payment_status === 'paid' ? '✓ Paid' : '↺ Refunded'; ?>
                                    </span>
                                </td>
                                <td style="font-size: 12px; color: #6B7280;">
                                    <?php if (!empty($txn->stripe_payment_intent_id)): ?>
                                        <a href="https://dashboard.stripe.com/payments/<?php echo esc_attr($txn->stripe_payment_intent_id); ?>" target="_blank" style="color: #2563EB;">
                                            <?php echo esc_html(substr($txn->stripe_payment_intent_id, 0, 20)); ?>...
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php
                    // Calculate totals
                    $total_paid = array_sum(array_map(function($t) { return $t->payment_status === 'paid' ? $t->total_amount : 0; }, $transactions));
                    $total_refunded = array_sum(array_map(function($t) { return $t->payment_status === 'refunded' ? $t->total_amount : 0; }, $transactions));
                    $trainer_total = $total_paid * (1 - ($platform_fee / 100));
                    ?>
                    <div style="margin-top: 20px; padding: 20px; background: #F3F4F6; border-radius: 8px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; text-align: center;">
                        <div>
                            <div style="font-size: 24px; font-weight: 800; color: #111;">$<?php echo number_format($total_paid, 2); ?></div>
                            <div style="font-size: 13px; color: #6B7280;">Total Collected</div>
                        </div>
                        <div>
                            <div style="font-size: 24px; font-weight: 800; color: #DC2626;">$<?php echo number_format($total_refunded, 2); ?></div>
                            <div style="font-size: 13px; color: #6B7280;">Refunded</div>
                        </div>
                        <div>
                            <div style="font-size: 24px; font-weight: 800; color: #059669;">$<?php echo number_format($trainer_total, 2); ?></div>
                            <div style="font-size: 13px; color: #6B7280;">Trainer Earnings</div>
                        </div>
                        <div>
                            <div style="font-size: 24px; font-weight: 800; color: #FCB900;">$<?php echo number_format($total_paid - $trainer_total, 2); ?></div>
                            <div style="font-size: 13px; color: #6B7280;">Platform Fee (<?php echo $platform_fee; ?>%)</div>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <p style="color: #6B7280; text-align: center; padding: 40px;">No transactions yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /* =========================================================================
       TOOLS PAGE
       ========================================================================= */
    public function tools_page() {
        // Define all required pages
        $required_pages = array(
            'training' => array('title' => 'PTP Training', 'content' => '[ptp_home]'),
            'find-trainers' => array('title' => 'Find Trainers', 'content' => '[ptp_trainers_grid]'),
            'trainer' => array('title' => 'Trainer Profile', 'content' => '[ptp_trainer_profile]'),
            'book-session' => array('title' => 'Book Session', 'content' => '[ptp_booking_form]'),
            'booking-confirmation' => array('title' => 'Booking Confirmed', 'content' => '[ptp_booking_confirmation]'),
            'my-training' => array('title' => 'My Training', 'content' => '[ptp_my_training]'),
            'trainer-dashboard' => array('title' => 'Trainer Dashboard', 'content' => '[ptp_trainer_dashboard]'),
            'trainer-onboarding' => array('title' => 'Complete Your Profile', 'content' => '[ptp_trainer_onboarding]'),
            'messages' => array('title' => 'Messages', 'content' => '[ptp_messaging]'),
            'account' => array('title' => 'Account', 'content' => '[ptp_account]'),
            'login' => array('title' => 'Login', 'content' => '[ptp_login]'),
            'register' => array('title' => 'Register', 'content' => '[ptp_register]'),
            'apply' => array('title' => 'Become a Trainer', 'content' => '[ptp_apply]'),
            'parent-dashboard' => array('title' => 'Parent Dashboard', 'content' => '[ptp_parent_dashboard]'),
            'player-progress' => array('title' => 'Player Progress', 'content' => '[ptp_player_progress]'),
            'training-plans' => array('title' => 'Training Plans', 'content' => '[ptp_training_plans]'),
        );
        
        $message = '';
        $message_type = '';
        
        // Handle form submissions
        if (isset($_POST['ptp_tools_action']) && wp_verify_nonce($_POST['ptp_tools_nonce'], 'ptp_tools')) {
            $action = sanitize_text_field($_POST['ptp_tools_action']);
            
            if ($action === 'create_all') {
                $created = 0;
                foreach ($required_pages as $slug => $page) {
                    if (!get_page_by_path($slug)) {
                        wp_insert_post(array(
                            'post_title' => $page['title'],
                            'post_name' => $slug,
                            'post_content' => $page['content'],
                            'post_status' => 'publish',
                            'post_type' => 'page',
                        ));
                        $created++;
                    }
                }
                $message = $created > 0 ? "Created {$created} missing pages." : "All pages already exist.";
                $message_type = 'success';
            }
            
            if ($action === 'update_all') {
                $updated = 0;
                foreach ($required_pages as $slug => $page) {
                    $existing = get_page_by_path($slug);
                    if ($existing) {
                        wp_update_post(array(
                            'ID' => $existing->ID,
                            'post_content' => $page['content'],
                        ));
                        $updated++;
                    }
                }
                $message = "Updated {$updated} pages with correct shortcodes.";
                $message_type = 'success';
            }
            
            if ($action === 'recreate_all') {
                $recreated = 0;
                foreach ($required_pages as $slug => $page) {
                    $existing = get_page_by_path($slug);
                    if ($existing) {
                        wp_delete_post($existing->ID, true);
                    }
                    wp_insert_post(array(
                        'post_title' => $page['title'],
                        'post_name' => $slug,
                        'post_content' => $page['content'],
                        'post_status' => 'publish',
                        'post_type' => 'page',
                    ));
                    $recreated++;
                }
                $message = "Recreated all {$recreated} pages.";
                $message_type = 'success';
            }
            
            if ($action === 'create_single' && !empty($_POST['page_slug'])) {
                $slug = sanitize_text_field($_POST['page_slug']);
                if (isset($required_pages[$slug])) {
                    $existing = get_page_by_path($slug);
                    if ($existing) {
                        wp_delete_post($existing->ID, true);
                    }
                    wp_insert_post(array(
                        'post_title' => $required_pages[$slug]['title'],
                        'post_name' => $slug,
                        'post_content' => $required_pages[$slug]['content'],
                        'post_status' => 'publish',
                        'post_type' => 'page',
                    ));
                    $message = "Page '{$slug}' has been created/recreated.";
                    $message_type = 'success';
                }
            }
            
            if ($action === 'recreate_tables') {
                PTP_Database::create_tables();
                if (class_exists('PTP_SMS')) PTP_SMS::create_table();
                if (class_exists('PTP_Social')) PTP_Social::create_table();
                if (class_exists('PTP_Geocoding')) PTP_Geocoding::create_table();
                if (class_exists('PTP_WooCommerce')) PTP_WooCommerce::create_table();
                if (class_exists('PTP_Recurring')) PTP_Recurring::create_tables();
                if (class_exists('PTP_Groups')) PTP_Groups::create_tables();
                if (class_exists('PTP_Calendar_Sync')) PTP_Calendar_Sync::create_tables();
                if (class_exists('PTP_Training_Plans')) PTP_Training_Plans::create_tables();
                $message = "All database tables have been recreated/updated.";
                $message_type = 'success';
            }
            
            if ($action === 'reset_trainers') {
                // Delete all trainers from database
                $wpdb->query("DELETE FROM {$wpdb->prefix}ptp_trainers");
                $wpdb->query("DELETE FROM {$wpdb->prefix}ptp_applications");
                $wpdb->query("DELETE FROM {$wpdb->prefix}ptp_availability");
                $wpdb->query("DELETE FROM {$wpdb->prefix}ptp_reviews");
                
                // Remove trainer role from all users
                $trainer_users = get_users(array('role' => 'ptp_trainer'));
                foreach ($trainer_users as $user) {
                    $user->remove_role('ptp_trainer');
                    // If user has no other roles, give them subscriber
                    if (empty($user->roles)) {
                        $user->add_role('subscriber');
                    }
                }
                
                $message = "All trainers and applications have been reset. " . count($trainer_users) . " users updated.";
                $message_type = 'success';
            }
        }
        
        // Build page status
        $page_status = array();
        foreach ($required_pages as $slug => $page) {
            $existing = get_page_by_path($slug);
            $page_status[$slug] = array(
                'title' => $page['title'],
                'shortcode' => $page['content'],
                'exists' => $existing ? true : false,
                'id' => $existing ? $existing->ID : null,
                'current_content' => $existing ? $existing->post_content : null,
                'correct' => $existing && trim($existing->post_content) === trim($page['content']),
                'url' => $existing ? get_permalink($existing->ID) : null,
            );
        }
        
        ?>
        <div class="wrap ptp-admin">
            <h1>PTP Tools</h1>
            
            <?php if ($message): ?>
                <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
                    <p><?php echo esc_html($message); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="ptp-tools-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                
                <!-- Page Creator -->
                <div class="ptp-card">
                    <div class="ptp-card-header">
                        <h2 style="margin: 0;">Page Manager</h2>
                    </div>
                    <div class="ptp-card-body">
                        <p>Manage all PTP pages and their shortcodes.</p>
                        
                        <form method="post" style="margin-bottom: 20px;">
                            <?php wp_nonce_field('ptp_tools', 'ptp_tools_nonce'); ?>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <button type="submit" name="ptp_tools_action" value="create_all" class="button button-primary">
                                    Create Missing Pages
                                </button>
                                <button type="submit" name="ptp_tools_action" value="update_all" class="button">
                                    Update All Shortcodes
                                </button>
                                <button type="submit" name="ptp_tools_action" value="recreate_all" class="button" 
                                        onclick="return confirm('This will delete and recreate ALL PTP pages. Are you sure?');">
                                    Recreate All Pages
                                </button>
                            </div>
                        </form>
                        
                        <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th>Slug</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($page_status as $slug => $status): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($status['title']); ?></strong>
                                            <br><code style="font-size: 11px;"><?php echo esc_html($status['shortcode']); ?></code>
                                        </td>
                                        <td><code>/<?php echo esc_html($slug); ?>/</code></td>
                                        <td>
                                            <?php if (!$status['exists']): ?>
                                                <span style="color: #dc3232;">❌ Missing</span>
                                            <?php elseif (!$status['correct']): ?>
                                                <span style="color: #dba617;">⚠️ Wrong Shortcode</span>
                                            <?php else: ?>
                                                <span style="color: #46b450;">✅ OK</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="post" style="display: inline;">
                                                <?php wp_nonce_field('ptp_tools', 'ptp_tools_nonce'); ?>
                                                <input type="hidden" name="page_slug" value="<?php echo esc_attr($slug); ?>">
                                                <button type="submit" name="ptp_tools_action" value="create_single" class="button button-small">
                                                    <?php echo $status['exists'] ? 'Recreate' : 'Create'; ?>
                                                </button>
                                            </form>
                                            <?php if ($status['exists']): ?>
                                                <a href="<?php echo esc_url($status['url']); ?>" class="button button-small" target="_blank">View</a>
                                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $status['id'] . '&action=edit')); ?>" class="button button-small">Edit</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- System Info -->
                <div class="ptp-card">
                    <div class="ptp-card-header">
                        <h2 style="margin: 0;">System Info</h2>
                    </div>
                    <div class="ptp-card-body">
                        <table class="widefat" style="border: none;">
                            <tr>
                                <td><strong>Plugin Version</strong></td>
                                <td><?php echo PTP_VERSION; ?></td>
                            </tr>
                            <tr>
                                <td><strong>WordPress Version</strong></td>
                                <td><?php echo get_bloginfo('version'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>PHP Version</strong></td>
                                <td><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Database Prefix</strong></td>
                                <td><?php global $wpdb; echo $wpdb->prefix; ?></td>
                            </tr>
                        </table>
                        
                        <h3 style="margin-top: 20px;">Database Tables</h3>
                        <?php
                        global $wpdb;
                        $tables = array(
                            'ptp_trainers',
                            'ptp_parents', 
                            'ptp_applications',
                            'ptp_bookings',
                            'ptp_availability',
                            'ptp_reviews',
                            'ptp_conversations',
                            'ptp_messages',
                            'ptp_payouts',
                        );
                        ?>
                        <table class="widefat" style="border: none;">
                            <?php foreach ($tables as $table): 
                                $full_table = $wpdb->prefix . $table;
                                $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") === $full_table;
                                $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $full_table") : 0;
                            ?>
                                <tr>
                                    <td><?php echo $table; ?></td>
                                    <td>
                                        <?php if ($exists): ?>
                                            <span style="color: #46b450;">✅ <?php echo $count; ?> rows</span>
                                        <?php else: ?>
                                            <span style="color: #dc3232;">❌ Missing</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <h3 style="margin-top: 20px;">User Roles</h3>
                        <?php
                        $trainer_count = count(get_users(array('role' => 'ptp_trainer')));
                        $parent_count = count(get_users(array('role' => 'ptp_parent')));
                        ?>
                        <table class="widefat" style="border: none;">
                            <tr>
                                <td>ptp_trainer</td>
                                <td><?php echo $trainer_count; ?> users</td>
                            </tr>
                            <tr>
                                <td>ptp_parent</td>
                                <td><?php echo $parent_count; ?> users</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
            </div>
            
            <!-- Repair Tools -->
            <div style="margin-top: 20px;">
                <div class="ptp-card">
                    <div class="ptp-card-header">
                        <h2 style="margin: 0;">🔧 Repair Tools</h2>
                    </div>
                    <div class="ptp-card-body">
                        <p>Fix common issues with trainer accounts and applications.</p>
                        
                        <?php
                        // Find broken trainers (have role but no trainer record)
                        $trainer_users = get_users(array('role' => 'ptp_trainer'));
                        $broken_trainers = array();
                        
                        foreach ($trainer_users as $user) {
                            $has_record = $wpdb->get_var($wpdb->prepare(
                                "SELECT id FROM {$wpdb->prefix}ptp_trainers WHERE user_id = %d",
                                $user->ID
                            ));
                            
                            if (!$has_record) {
                                // Check if they have an approved application
                                $app = $wpdb->get_row($wpdb->prepare(
                                    "SELECT * FROM {$wpdb->prefix}ptp_applications WHERE user_id = %d ORDER BY id DESC LIMIT 1",
                                    $user->ID
                                ));
                                
                                $broken_trainers[] = array(
                                    'user' => $user,
                                    'application' => $app,
                                );
                            }
                        }
                        
                        // Handle repair action
                        if (isset($_POST['ptp_repair_action']) && wp_verify_nonce($_POST['ptp_tools_nonce'], 'ptp_tools')) {
                            $repair_action = sanitize_text_field($_POST['ptp_repair_action']);
                            
                            if ($repair_action === 'repair_trainer' && !empty($_POST['repair_user_id'])) {
                                $repair_user_id = intval($_POST['repair_user_id']);
                                $repair_user = get_user_by('ID', $repair_user_id);
                                
                                if ($repair_user) {
                                    // Check for application
                                    $app = $wpdb->get_row($wpdb->prepare(
                                        "SELECT * FROM {$wpdb->prefix}ptp_applications WHERE user_id = %d ORDER BY id DESC LIMIT 1",
                                        $repair_user_id
                                    ));
                                    
                                    // Create trainer record
                                    $slug = sanitize_title($repair_user->display_name) . '-' . $repair_user_id;
                                    
                                    // Make sure slug is unique
                                    $slug_exists = $wpdb->get_var($wpdb->prepare(
                                        "SELECT id FROM {$wpdb->prefix}ptp_trainers WHERE slug = %s", $slug
                                    ));
                                    if ($slug_exists) {
                                        $slug .= '-' . time();
                                    }
                                    
                                    $trainer_data = array(
                                        'user_id' => $repair_user_id,
                                        'display_name' => $app ? $app->name : $repair_user->display_name,
                                        'slug' => $slug,
                                        'email' => $app ? $app->email : $repair_user->user_email,
                                        'phone' => $app ? ($app->phone ?: '') : '',
                                        'location' => $app ? ($app->location ?: '') : '',
                                        'college' => $app ? ($app->college ?: '') : '',
                                        'team' => $app ? ($app->team ?: '') : '',
                                        'playing_level' => $app ? ($app->playing_level ?: '') : '',
                                        'specialties' => $app ? ($app->specialties ?: '') : '',
                                        'instagram' => $app ? ($app->instagram ?: '') : '',
                                        'headline' => $app ? ($app->headline ?: '') : '',
                                        'bio' => $app ? ($app->bio ?: '') : '',
                                        'hourly_rate' => $app ? floatval($app->hourly_rate) : 0,
                                        'travel_radius' => $app ? intval($app->travel_radius) : 15,
                                        'status' => 'active',
                                    );
                                    
                                    $insert_result = $wpdb->insert($wpdb->prefix . 'ptp_trainers', $trainer_data);
                                    
                                    if ($insert_result) {
                                        echo '<div class="notice notice-success"><p>✅ Created trainer record for ' . esc_html($repair_user->display_name) . '</p></div>';
                                        // Refresh the broken trainers list
                                        $broken_trainers = array_filter($broken_trainers, function($bt) use ($repair_user_id) {
                                            return $bt['user']->ID !== $repair_user_id;
                                        });
                                    } else {
                                        echo '<div class="notice notice-error"><p>❌ Failed to create trainer record: ' . esc_html($wpdb->last_error) . '</p></div>';
                                    }
                                }
                            }
                            
                            if ($repair_action === 'repair_all_trainers') {
                                $repaired = 0;
                                foreach ($broken_trainers as $bt) {
                                    $repair_user = $bt['user'];
                                    $app = $bt['application'];
                                    
                                    $slug = sanitize_title($repair_user->display_name) . '-' . $repair_user->ID;
                                    $slug_exists = $wpdb->get_var($wpdb->prepare(
                                        "SELECT id FROM {$wpdb->prefix}ptp_trainers WHERE slug = %s", $slug
                                    ));
                                    if ($slug_exists) {
                                        $slug .= '-' . time() . '-' . $repaired;
                                    }
                                    
                                    $trainer_data = array(
                                        'user_id' => $repair_user->ID,
                                        'display_name' => $app ? $app->name : $repair_user->display_name,
                                        'slug' => $slug,
                                        'email' => $app ? $app->email : $repair_user->user_email,
                                        'phone' => $app ? ($app->phone ?: '') : '',
                                        'location' => $app ? ($app->location ?: '') : '',
                                        'college' => $app ? ($app->college ?: '') : '',
                                        'team' => $app ? ($app->team ?: '') : '',
                                        'playing_level' => $app ? ($app->playing_level ?: '') : '',
                                        'specialties' => $app ? ($app->specialties ?: '') : '',
                                        'instagram' => $app ? ($app->instagram ?: '') : '',
                                        'headline' => $app ? ($app->headline ?: '') : '',
                                        'bio' => $app ? ($app->bio ?: '') : '',
                                        'hourly_rate' => $app ? floatval($app->hourly_rate) : 0,
                                        'travel_radius' => $app ? intval($app->travel_radius) : 15,
                                        'status' => 'active',
                                    );
                                    
                                    if ($wpdb->insert($wpdb->prefix . 'ptp_trainers', $trainer_data)) {
                                        $repaired++;
                                    }
                                }
                                
                                if ($repaired > 0) {
                                    echo '<div class="notice notice-success"><p>✅ Repaired ' . $repaired . ' trainer(s)</p></div>';
                                    $broken_trainers = array(); // Clear the list
                                }
                            }
                        }
                        ?>
                        
                        <?php if (!empty($broken_trainers)): ?>
                            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px 16px; margin: 16px 0; border-radius: 0 4px 4px 0;">
                                <strong>⚠️ Found <?php echo count($broken_trainers); ?> user(s) with trainer role but no trainer record:</strong>
                            </div>
                            
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Application Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($broken_trainers as $bt): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($bt['user']->display_name); ?></strong>
                                                <br><small>ID: <?php echo $bt['user']->ID; ?></small>
                                            </td>
                                            <td><?php echo esc_html($bt['user']->user_email); ?></td>
                                            <td>
                                                <?php if ($bt['application']): ?>
                                                    <span style="color: <?php echo $bt['application']->status === 'approved' ? '#28a745' : '#dc3545'; ?>">
                                                        <?php echo ucfirst($bt['application']->status); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #6c757d;">No application found</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="post" style="display: inline;">
                                                    <?php wp_nonce_field('ptp_tools', 'ptp_tools_nonce'); ?>
                                                    <input type="hidden" name="repair_user_id" value="<?php echo $bt['user']->ID; ?>">
                                                    <button type="submit" name="ptp_repair_action" value="repair_trainer" class="button button-primary button-small">
                                                        Create Trainer Record
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <form method="post" style="margin-top: 16px;">
                                <?php wp_nonce_field('ptp_tools', 'ptp_tools_nonce'); ?>
                                <button type="submit" name="ptp_repair_action" value="repair_all_trainers" class="button button-primary">
                                    🔧 Repair All (Create <?php echo count($broken_trainers); ?> trainer records)
                                </button>
                            </form>
                        <?php else: ?>
                            <div style="background: #d4edda; border-left: 4px solid #28a745; padding: 12px 16px; margin: 16px 0; border-radius: 0 4px 4px 0;">
                                <strong>✅ All users with trainer role have valid trainer records.</strong>
                            </div>
                        <?php endif; ?>
                        
                        <hr style="margin: 24px 0;">
                        
                        <h4>Database Maintenance</h4>
                        <form method="post" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <?php wp_nonce_field('ptp_tools', 'ptp_tools_nonce'); ?>
                            <button type="submit" name="ptp_tools_action" value="recreate_tables" class="button"
                                    onclick="return confirm('This will recreate all database tables. Existing data will be preserved. Continue?');">
                                Recreate Database Tables
                            </button>
                        </form>
                        
                        <hr style="margin: 24px 0;">
                        
                        <h4>⚠️ Danger Zone</h4>
                        <form method="post" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <?php wp_nonce_field('ptp_tools', 'ptp_tools_nonce'); ?>
                            <button type="submit" name="ptp_tools_action" value="reset_trainers" class="button" style="background: #DC2626; color: #fff; border-color: #DC2626;"
                                    onclick="return confirm('⚠️ WARNING: This will DELETE ALL trainers, applications, availability, and reviews. This cannot be undone! Are you absolutely sure?');">
                                🗑️ Reset All Trainers
                            </button>
                        </form>
                        <p style="color: #6B7280; font-size: 12px; margin-top: 8px;">This deletes all trainer records, applications, availability, and reviews. Users keep their accounts but lose trainer role.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
