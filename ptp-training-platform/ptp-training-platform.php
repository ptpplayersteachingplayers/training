<?php
/**
 * Plugin Name: PTP Training Platform
 * Plugin URI: https://ptpsummercamps.com
 * Description: Complete 1-on-1 soccer training marketplace connecting parents with elite NCAA and professional trainers.
 * Version: 30.0
 * Author: PTP Soccer
 * Author URI: https://ptpsummercamps.com
 * Text Domain: ptp-training
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

// Plugin constants
define("PTP_VERSION", "30.0");
define('PTP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PTP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PTP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
final class PTP_Training_Platform {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }
    
    private function includes() {
        // Core classes
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-database.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-images.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-user.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-trainer.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-parent.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-player.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-booking.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-availability.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-messaging.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-reviews.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-payments.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-trainer-payouts.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-notifications.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-ajax.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-shortcodes.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-templates.php';
        
        // Integration classes
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-email.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-sms.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-stripe.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-escrow.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-session-confirmation.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-seo.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-social.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-push-notifications.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-schedule-calendar.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-geocoding.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-maps.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-cron.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-woocommerce.php';
        
        // Advanced features
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-recurring.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-groups.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-calendar-sync.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-training-plans.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-tax-reporting.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-rest-api.php';
        
        // Admin class
        require_once PTP_PLUGIN_DIR . 'admin/class-ptp-admin.php';
        require_once PTP_PLUGIN_DIR . 'admin/class-ptp-admin-ajax.php';
        require_once PTP_PLUGIN_DIR . 'includes/class-ptp-admin-payouts.php';
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // Intercept login/register pages to show full-page templates
        add_action('template_redirect', array($this, 'maybe_override_template'));
        
        // Always instantiate admin - the hooks inside only fire in admin context anyway
        // This is the simplest and most reliable approach
        PTP_Admin::instance();
        
        // Add admin notice to confirm plugin is loaded
        add_action('admin_notices', array($this, 'check_plugin_status'));
    }
    
    /**
     * Check plugin status and show notice if tables missing
     */
    public function check_plugin_status() {
        // Only show to admins
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if tables exist
        global $wpdb;
        $trainers_table = $wpdb->prefix . 'ptp_trainers';
        $tables_exist = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $trainers_table)) == $trainers_table;
        
        if (!$tables_exist) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>PTP Training Platform:</strong> Database tables not found. ';
            echo '<a href="' . esc_url(admin_url('admin.php?page=ptp-tools')) . '">Go to Tools</a> to create them, or deactivate and reactivate the plugin.</p>';
            echo '</div>';
        }
        
        // Show error notice on PTP pages if admin class failed
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        if (strpos($page, 'ptp') === 0) {
            $admin_loaded = class_exists('PTP_Admin');
            if (!$admin_loaded) {
                echo '<div class="notice notice-error">';
                echo '<p><strong>PTP Training Platform:</strong> Admin class failed to load. Please check for PHP errors.</p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Override template for login/register pages to show full-page design
     */
    public function maybe_override_template() {
        if (is_page('login')) {
            // Redirect logged-in non-admin users to dashboard
            if (is_user_logged_in() && !current_user_can('manage_options')) {
                wp_redirect(PTP_User::get_dashboard_url());
                exit;
            }
            // Load full-page login template
            include PTP_PLUGIN_DIR . 'templates/login.php';
            // Template calls exit, but just in case
            exit;
        }
        
        if (is_page('register')) {
            // Redirect logged-in non-admin users to dashboard
            if (is_user_logged_in() && !current_user_can('manage_options')) {
                wp_redirect(PTP_User::get_dashboard_url());
                exit;
            }
            // Load full-page register template
            include PTP_PLUGIN_DIR . 'templates/register.php';
            // Template calls exit, but just in case
            exit;
        }
        
        // Handle trainer-dashboard redirects before shortcode runs
        if (is_page('trainer-dashboard') && is_user_logged_in()) {
            $current_user_id = get_current_user_id();
            $current_user = wp_get_current_user();
            
            $trainer = PTP_Trainer::get_by_user_id($current_user_id);
            
            if (!$trainer) {
                // No trainer record - check if they have the trainer role (means they applied)
                if (in_array('ptp_trainer', (array) $current_user->roles)) {
                    // They have the role but no trainer record = pending approval
                    // Let the shortcode show the pending page
                    return;
                }
                
                // No trainer role and no trainer record - redirect to apply
                wp_redirect(home_url('/apply/'));
                exit;
            }
        }
        
        // Handle apply page - full-page template like login/register
        if (is_page('apply')) {
            // Check if already logged in and already a trainer
            if (is_user_logged_in()) {
                $current_user_id = get_current_user_id();
                $current_user = wp_get_current_user();
                
                // Check if already a trainer
                $trainer = PTP_Trainer::get_by_user_id($current_user_id);
                if ($trainer) {
                    wp_redirect(home_url('/trainer-dashboard/'));
                    exit;
                }
                
                // Check if they have the trainer role (means they already applied)
                if (in_array('ptp_trainer', (array) $current_user->roles)) {
                    wp_redirect(home_url('/trainer-dashboard/'));
                    exit;
                }
            }
            
            // Load full-page apply template (handles form POST processing)
            include PTP_PLUGIN_DIR . 'templates/apply.php';
            exit;
        }
    }
    
    public function init() {
        load_plugin_textdomain('ptp-training', false, dirname(PTP_PLUGIN_BASENAME) . '/languages');
        
        // Quick repair tables if needed (runs once per request, cached)
        if (class_exists('PTP_Database')) {
            PTP_Database::quick_repair();
        }
        
        // Ensure custom roles exist (needed for application approval)
        $this->ensure_roles_exist();
        
        // Auto-create missing pages (check on admin or if trainer page is missing)
        if (is_admin() || !get_page_by_path('trainer')) {
            $this->maybe_create_pages();
        }
        
        // Core functionality
        PTP_Ajax::init();
        PTP_Shortcodes::init();
        PTP_Templates::init();
        PTP_Availability::init(); // CRITICAL: Schedule/availability AJAX handlers
        
        // Integrations
        PTP_Email::init();
        PTP_SMS::init();
        PTP_Stripe::init();
        PTP_SEO::init();
        PTP_Social::init();
        PTP_Geocoding::init();
        PTP_Cron::init();
        PTP_WooCommerce::init();
        
        // Advanced features
        PTP_Recurring::init();
        PTP_Groups::init();
        PTP_Calendar_Sync::init();
        PTP_Training_Plans::init();
        PTP_Tax_Reporting::init();
        PTP_REST_API::init();
        PTP_Push_Notifications::init();
        PTP_Schedule_Calendar::init();
        
        // Rewrite rules for trainer profiles
        // Route /trainer/<slug>/ to the trainer page with trainer_slug query var
        add_rewrite_rule('^trainer/([^/]+)/?$', 'index.php?pagename=trainer&trainer_slug=$matches[1]', 'top');
        add_rewrite_tag('%trainer_slug%', '([^&]+)');
        
        // Register query var so WordPress recognizes it
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Filter request to properly handle trainer URLs (fallback if rewrite rules don't work)
        add_filter('request', array($this, 'filter_trainer_request'), 1);
        
        // Flush rewrite rules if needed (once per version)
        $flush_version = get_option('ptp_rewrite_flush_version', '0');
        if (version_compare($flush_version, PTP_VERSION, '<') || $flush_version !== '24.6.1-bulletproof') {
            flush_rewrite_rules();
            update_option('ptp_rewrite_flush_version', '24.6.1-bulletproof');
        }
        
        // Redirect old trainer-profile URLs to new format
        add_action('template_redirect', array($this, 'redirect_old_trainer_urls'));
    }
    
    /**
     * Redirect old /trainer-profile/?trainer=slug URLs to /trainer/slug/
     */
    public function redirect_old_trainer_urls() {
        if (is_page('trainer-profile') && !empty($_GET['trainer'])) {
            $trainer_slug = sanitize_text_field($_GET['trainer']);
            wp_redirect(home_url('/trainer/' . $trainer_slug . '/'), 301);
            exit;
        }
    }
    
    /**
     * Filter request to handle trainer profile URLs
     */
    public function filter_trainer_request($query_vars) {
        // Only intercept if we don't already have the trainer_slug set
        if (!empty($query_vars['trainer_slug'])) {
            return $query_vars;
        }
        
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
        
        // Remove site subdirectory if present
        $home_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');
        if ($home_path && strpos($path, $home_path) === 0) {
            $path = trim(substr($path, strlen($home_path)), '/');
        }
        
        if (preg_match('#^trainer/([^/]+)/?$#', $path, $matches)) {
            $trainer_slug = sanitize_text_field($matches[1]);
            
            // Skip if this looks like a static file
            if (strpos($trainer_slug, '.') !== false) {
                return $query_vars;
            }
            
            // Set up query vars to load the trainer page
            $query_vars['pagename'] = 'trainer';
            $query_vars['trainer_slug'] = $trainer_slug;
            
            // Remove any 404 indicators
            unset($query_vars['error']);
        }
        
        return $query_vars;
    }
    
    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'trainer_slug';
        return $vars;
    }
    
    /**
     * Ensure custom roles exist (runs on every init to catch any missing roles)
     */
    private function ensure_roles_exist() {
        if (!get_role('ptp_trainer')) {
            add_role('ptp_trainer', 'PTP Trainer', array(
                'read' => true,
                'upload_files' => true,
            ));
        }
        
        if (!get_role('ptp_parent')) {
            add_role('ptp_parent', 'PTP Parent', array(
                'read' => true,
            ));
        }
    }
    
    public function enqueue_scripts() {
        // Use Inter as the default UI font to match PTP site styling.
        wp_enqueue_style('ptp-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap', array(), null);
        wp_enqueue_style('ptp-frontend', PTP_PLUGIN_URL . 'assets/css/frontend.css', array(), PTP_VERSION);
        wp_enqueue_script('ptp-frontend', PTP_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), PTP_VERSION, true);
        
        wp_localize_script('ptp-frontend', 'ptp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ptp_nonce'),
            'home_url' => home_url(),
            'is_logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id(),
        ));
    }
    
    public function admin_scripts($hook) {
        // Always load admin CSS on ALL admin pages
        // CSS is scoped to .ptp-admin-wrap so it won't affect other pages
        wp_enqueue_style(
            'ptp-admin-css', 
            PTP_PLUGIN_URL . 'assets/css/admin.css', 
            array(), 
            PTP_VERSION . '.' . date('His')
        );
        
        // Only load JS on PTP pages
        if (strpos($hook, 'ptp') !== false) {
            wp_enqueue_script('ptp-admin', PTP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PTP_VERSION, true);
            wp_localize_script('ptp-admin', 'ptpAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ptp_admin_nonce')
            ));
        }
    }
    
    public function activate() {
        // Create all database tables
        PTP_Database::create_tables();
        
        // Repair any broken table schemas (for upgrades)
        PTP_Database::repair_tables();
        
        PTP_SMS::create_table();
        PTP_Social::create_table();
        PTP_Geocoding::create_table();
        PTP_WooCommerce::create_table();
        
        // Advanced features tables
        PTP_Recurring::create_tables();
        PTP_Groups::create_tables();
        PTP_Calendar_Sync::create_tables();
        PTP_Training_Plans::create_tables();
        
        // Run migrations for existing installations
        $this->run_migrations();
        
        // Create pages
        $this->create_pages();
        
        // Add capabilities
        $this->add_capabilities();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron jobs
        PTP_Cron::schedule_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Store version
        update_option('ptp_version', PTP_VERSION);
    }
    
    /**
     * Run database migrations for existing installations
     */
    private function run_migrations() {
        global $wpdb;
        
        // Helper function to add column if it doesn't exist
        $add_column_if_missing = function($table, $column, $definition) use ($wpdb) {
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SHOW COLUMNS FROM {$table} LIKE %s", $column
            ));
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            }
        };
        
        // Migrate applications table
        $apps_table = $wpdb->prefix . 'ptp_applications';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$apps_table}'") === $apps_table) {
            $add_column_if_missing($apps_table, 'name', "varchar(100) NOT NULL DEFAULT '' AFTER email");
            $add_column_if_missing($apps_table, 'phone', "varchar(20) DEFAULT '' AFTER name");
            $add_column_if_missing($apps_table, 'location', "varchar(255) DEFAULT '' AFTER phone");
            $add_column_if_missing($apps_table, 'college', "varchar(255) DEFAULT '' AFTER location");
            $add_column_if_missing($apps_table, 'team', "varchar(255) DEFAULT '' AFTER college");
            $add_column_if_missing($apps_table, 'playing_level', "varchar(50) DEFAULT '' AFTER team");
            $add_column_if_missing($apps_table, 'position', "varchar(100) DEFAULT '' AFTER playing_level");
            $add_column_if_missing($apps_table, 'specialties', "text AFTER position");
            $add_column_if_missing($apps_table, 'instagram', "varchar(100) DEFAULT '' AFTER specialties");
            $add_column_if_missing($apps_table, 'headline', "varchar(255) DEFAULT '' AFTER instagram");
            $add_column_if_missing($apps_table, 'bio', "text AFTER headline");
            $add_column_if_missing($apps_table, 'hourly_rate', "decimal(10,2) DEFAULT 0 AFTER bio");
            $add_column_if_missing($apps_table, 'travel_radius', "int(11) DEFAULT 15 AFTER hourly_rate");
        }
        
        // Migrate trainers table
        $trainers_table = $wpdb->prefix . 'ptp_trainers';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$trainers_table}'") === $trainers_table) {
            $add_column_if_missing($trainers_table, 'email', "varchar(255) DEFAULT '' AFTER slug");
            $add_column_if_missing($trainers_table, 'phone', "varchar(20) DEFAULT '' AFTER email");
            $add_column_if_missing($trainers_table, 'playing_level', "varchar(50) DEFAULT '' AFTER team");
            $add_column_if_missing($trainers_table, 'instagram', "varchar(100) DEFAULT '' AFTER specialties");
            $add_column_if_missing($trainers_table, 'gallery', "text AFTER photo_url");
            $add_column_if_missing($trainers_table, 'training_locations', "text AFTER travel_radius");
            // Verification documents
            $add_column_if_missing($trainers_table, 'safesport_doc_url', "varchar(500) DEFAULT '' AFTER instagram");
            $add_column_if_missing($trainers_table, 'safesport_verified', "tinyint(1) DEFAULT 0 AFTER safesport_doc_url");
            $add_column_if_missing($trainers_table, 'safesport_expiry', "date DEFAULT NULL AFTER safesport_verified");
            $add_column_if_missing($trainers_table, 'background_doc_url', "varchar(500) DEFAULT '' AFTER safesport_expiry");
            $add_column_if_missing($trainers_table, 'background_verified', "tinyint(1) DEFAULT 0 AFTER background_doc_url");
            // Tax information for 1099
            $add_column_if_missing($trainers_table, 'tax_id_last4', "varchar(4) DEFAULT '' AFTER background_verified");
            $add_column_if_missing($trainers_table, 'tax_id_type', "enum('ssn','ein') DEFAULT 'ssn' AFTER tax_id_last4");
            $add_column_if_missing($trainers_table, 'legal_name', "varchar(255) DEFAULT '' AFTER tax_id_type");
            $add_column_if_missing($trainers_table, 'tax_address_line1', "varchar(255) DEFAULT '' AFTER legal_name");
            $add_column_if_missing($trainers_table, 'tax_address_line2', "varchar(255) DEFAULT '' AFTER tax_address_line1");
            $add_column_if_missing($trainers_table, 'tax_city', "varchar(100) DEFAULT '' AFTER tax_address_line2");
            $add_column_if_missing($trainers_table, 'tax_state', "varchar(2) DEFAULT '' AFTER tax_city");
            $add_column_if_missing($trainers_table, 'tax_zip', "varchar(10) DEFAULT '' AFTER tax_state");
            $add_column_if_missing($trainers_table, 'w9_submitted', "tinyint(1) DEFAULT 0 AFTER tax_zip");
            $add_column_if_missing($trainers_table, 'w9_submitted_at', "datetime DEFAULT NULL AFTER w9_submitted");
            $add_column_if_missing($trainers_table, 'contractor_agreement_signed', "tinyint(1) DEFAULT 0 AFTER w9_submitted_at");
            $add_column_if_missing($trainers_table, 'contractor_agreement_signed_at', "datetime DEFAULT NULL AFTER contractor_agreement_signed");
            $add_column_if_missing($trainers_table, 'contractor_agreement_ip', "varchar(45) DEFAULT '' AFTER contractor_agreement_signed_at");
            $add_column_if_missing($trainers_table, 'stripe_account_id', "varchar(255) DEFAULT '' AFTER contractor_agreement_ip");
            $add_column_if_missing($trainers_table, 'stripe_charges_enabled', "tinyint(1) DEFAULT 0 AFTER stripe_account_id");
            
            // Video profile (TeachMeTo "Vet Em Video" equivalent)
            $add_column_if_missing($trainers_table, 'intro_video_url', "varchar(500) DEFAULT '' AFTER gallery");
            
            // Group sessions
            $add_column_if_missing($trainers_table, 'accepts_groups', "tinyint(1) DEFAULT 1 AFTER training_locations");
            $add_column_if_missing($trainers_table, 'group_max_size', "int(11) DEFAULT 3 AFTER accepts_groups");
            $add_column_if_missing($trainers_table, 'group_rate_2', "decimal(10,2) DEFAULT 0 AFTER group_max_size");
            $add_column_if_missing($trainers_table, 'group_rate_3', "decimal(10,2) DEFAULT 0 AFTER group_rate_2");
        }
        
        // Add session notes table for progress tracking
        $notes_table = $wpdb->prefix . 'ptp_session_notes';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$notes_table}'") !== $notes_table) {
            $charset_collate = $wpdb->get_charset_collate();
            $wpdb->query("CREATE TABLE {$notes_table} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                booking_id bigint(20) UNSIGNED NOT NULL,
                trainer_id bigint(20) UNSIGNED NOT NULL,
                player_id bigint(20) UNSIGNED NOT NULL,
                skills_worked text,
                progress_notes text,
                homework text,
                next_focus text,
                effort_rating tinyint(1) DEFAULT NULL,
                attitude_rating tinyint(1) DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY booking_id (booking_id),
                KEY player_id (player_id),
                KEY trainer_id (trainer_id)
            ) {$charset_collate}");
        }
    }
    
    private function set_default_options() {
        // General settings
        add_option('ptp_platform_fee', '20');
        add_option('ptp_from_email', get_option('admin_email'));
        
        // Notification defaults
        add_option('ptp_email_booking_confirmation', true);
        add_option('ptp_email_session_reminder', true);
        add_option('ptp_email_review_request', true);
        add_option('ptp_sms_booking_confirmation', true);
        add_option('ptp_sms_session_reminder', true);
        
        // Stripe defaults
        add_option('ptp_stripe_test_mode', true);
    }
    
    public function deactivate() {
        PTP_Cron::clear_events();
        flush_rewrite_rules();
    }
    
    /**
     * Check and create missing pages (runs on admin init)
     */
    private function maybe_create_pages() {
        // Only run once per version update
        $pages_version = get_option('ptp_pages_version', '0');
        if (version_compare($pages_version, PTP_VERSION, '>=')) {
            return;
        }
        
        $this->create_pages();
        update_option('ptp_pages_version', PTP_VERSION);
        
        // Flush rewrite rules after creating pages
        flush_rewrite_rules();
    }
    
    private function create_pages() {
        $pages = array(
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
        
        foreach ($pages as $slug => $page) {
            if (!get_page_by_path($slug)) {
                wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_name' => $slug,
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                ));
            }
        }
    }
    
    private function add_capabilities() {
        // Register custom roles
        $trainer_role = get_role('ptp_trainer');
        if (!$trainer_role) {
            add_role('ptp_trainer', 'PTP Trainer', array(
                'read' => true,
                'upload_files' => true,
            ));
        }
        
        $parent_role = get_role('ptp_parent');
        if (!$parent_role) {
            add_role('ptp_parent', 'PTP Parent', array(
                'read' => true,
            ));
        }
        
        // Add admin capabilities
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_ptp_trainers');
            $admin->add_cap('manage_ptp_bookings');
            $admin->add_cap('manage_ptp_payments');
        }
    }
}

/**
 * Get video embed URL from YouTube or Vimeo link
 */
function ptp_get_video_embed_url($url) {
    if (empty($url)) return '';
    
    // YouTube
    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches) ||
        preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches) ||
        preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }
    
    return $url;
}

/**
 * Get video thumbnail URL
 */
function ptp_get_video_thumbnail($url) {
    if (empty($url)) return '';
    
    // YouTube
    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches) ||
        preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return 'https://img.youtube.com/vi/' . $matches[1] . '/maxresdefault.jpg';
    }
    
    return '';
}

/**
 * Format phone number for display
 */
function ptp_format_phone($phone) {
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleaned) === 10) {
        return '(' . substr($cleaned, 0, 3) . ') ' . substr($cleaned, 3, 3) . '-' . substr($cleaned, 6);
    }
    if (strlen($cleaned) === 11 && $cleaned[0] === '1') {
        return '(' . substr($cleaned, 1, 3) . ') ' . substr($cleaned, 4, 3) . '-' . substr($cleaned, 7);
    }
    return $phone;
}

function PTP() {
    return PTP_Training_Platform::instance();
}

PTP();
