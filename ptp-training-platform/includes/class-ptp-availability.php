<?php
/**
 * PTP Availability Class - BULLETPROOF v28.0.0
 * Complete trainer schedule management with robust error handling
 * Handles: Weekly availability, exceptions, booking calendar, slot generation
 */

defined('ABSPATH') || exit;

class PTP_Availability {
    
    private static $table_verified = false;
    private static $exceptions_table_verified = false;
    
    /**
     * Initialize - called on plugin load
     */
    public static function init() {
        // Reset verification flags so tables are checked on each page load
        self::$table_verified = false;
        self::$exceptions_table_verified = false;
        
        // Ensure tables exist on admin init
        add_action('admin_init', array(__CLASS__, 'maybe_create_tables'), 5);
        
        // Also ensure on init for frontend
        add_action('init', array(__CLASS__, 'ensure_table'), 5);
        
        // AJAX handlers for trainer schedule management
        add_action('wp_ajax_ptp_save_trainer_schedule', array(__CLASS__, 'ajax_save_schedule'));
        add_action('wp_ajax_ptp_get_trainer_schedule', array(__CLASS__, 'ajax_get_schedule'));
        add_action('wp_ajax_ptp_block_date', array(__CLASS__, 'ajax_block_date'));
        add_action('wp_ajax_ptp_unblock_date', array(__CLASS__, 'ajax_unblock_date'));
        
        // Public AJAX for booking calendar
        add_action('wp_ajax_ptp_get_availability_calendar', array(__CLASS__, 'ajax_get_calendar'));
        add_action('wp_ajax_nopriv_ptp_get_availability_calendar', array(__CLASS__, 'ajax_get_calendar'));
    }
    
    /**
     * Force re-check of table (call this to repair)
     */
    public static function force_table_check() {
        self::$table_verified = false;
        return self::ensure_table();
    }
    
    /**
     * Create tables if they don't exist
     */
    public static function maybe_create_tables() {
        self::ensure_table();
        self::ensure_exceptions_table();
    }
    
    /**
     * Ensure availability table exists with correct schema
     */
    public static function ensure_table() {
        if (self::$table_verified) {
            return true;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ptp_availability';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        
        if ($table_exists !== $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                trainer_id bigint(20) UNSIGNED NOT NULL,
                day_of_week tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Sun,1=Mon...6=Sat',
                start_time time NOT NULL DEFAULT '09:00:00',
                end_time time NOT NULL DEFAULT '17:00:00',
                slot_duration int(11) DEFAULT 60 COMMENT 'Minutes per slot',
                is_active tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY trainer_day (trainer_id, day_of_week),
                KEY trainer_id (trainer_id),
                KEY is_active (is_active)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('PTP: Created availability table');
        } else {
            // Table exists - fix legacy schema issues
            $columns = $wpdb->get_col("DESCRIBE $table_name");
            
            // CRITICAL: Drop any foreign key constraints first (legacy schema issue)
            // This handles the coach_id -> wp_ptp_coaches foreign key from old schema
            $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");
            
            // Get all foreign keys on this table
            $fk_query = $wpdb->prepare("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = %s 
                AND TABLE_NAME = %s 
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", DB_NAME, $table_name);
            
            $foreign_keys = $wpdb->get_col($fk_query);
            
            foreach ($foreign_keys as $fk_name) {
                $wpdb->query("ALTER TABLE $table_name DROP FOREIGN KEY `$fk_name`");
                error_log("PTP: Dropped foreign key $fk_name from availability table");
            }
            
            // If coach_id exists (legacy), rename it to trainer_id
            if (in_array('coach_id', $columns) && !in_array('trainer_id', $columns)) {
                $wpdb->query("ALTER TABLE $table_name CHANGE COLUMN coach_id trainer_id bigint(20) UNSIGNED NOT NULL");
                error_log('PTP: Renamed coach_id to trainer_id in availability table');
                // Refresh columns
                $columns = $wpdb->get_col("DESCRIBE $table_name");
            }
            
            // If both exist, drop coach_id
            if (in_array('coach_id', $columns) && in_array('trainer_id', $columns)) {
                $wpdb->query("ALTER TABLE $table_name DROP COLUMN coach_id");
                error_log('PTP: Dropped redundant coach_id from availability table');
            }
            
            // Add trainer_id if missing
            if (!in_array('trainer_id', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN trainer_id bigint(20) UNSIGNED NOT NULL AFTER id");
                error_log('PTP: Added trainer_id column to availability table');
            }
            
            // Add other missing columns
            if (!in_array('day_of_week', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN day_of_week tinyint(1) NOT NULL DEFAULT 0 AFTER trainer_id");
            }
            
            if (!in_array('start_time', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN start_time time NOT NULL DEFAULT '09:00:00' AFTER day_of_week");
            }
            
            if (!in_array('end_time', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN end_time time NOT NULL DEFAULT '17:00:00' AFTER start_time");
            }
            
            if (!in_array('is_active', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN is_active tinyint(1) DEFAULT 1 AFTER end_time");
            }
            
            if (!in_array('slot_duration', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN slot_duration int(11) DEFAULT 60 AFTER end_time");
            }
            
            if (!in_array('updated_at', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }
            
            // Re-enable foreign key checks
            $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");
            
            error_log('PTP: Availability table schema verified/repaired');
        }
        
        self::$table_verified = true;
        return true;
    }
    
    /**
     * Ensure exceptions table exists
     */
    public static function ensure_exceptions_table() {
        if (self::$exceptions_table_verified) {
            return true;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ptp_availability_exceptions';
        
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        
        if ($table_exists !== $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                trainer_id bigint(20) UNSIGNED NOT NULL,
                exception_date date NOT NULL,
                exception_type enum('blocked','modified','extra') DEFAULT 'blocked',
                is_available tinyint(1) DEFAULT 0,
                start_time time DEFAULT NULL,
                end_time time DEFAULT NULL,
                reason varchar(255) DEFAULT '',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY trainer_date (trainer_id, exception_date),
                KEY trainer_id (trainer_id),
                KEY exception_date (exception_date)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('PTP: Created availability_exceptions table');
        }
        
        self::$exceptions_table_verified = true;
        return true;
    }
    
    /**
     * Get weekly schedule for a trainer
     */
    public static function get_weekly($trainer_id, $active_only = false) {
        if (!$trainer_id || !is_numeric($trainer_id)) {
            return array();
        }
        
        self::ensure_table();
        
        global $wpdb;
        $table = $wpdb->prefix . 'ptp_availability';
        
        $where = $active_only ? "AND is_active = 1" : "";
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE trainer_id = %d $where ORDER BY day_of_week ASC",
            intval($trainer_id)
        ));
        
        return is_array($results) ? $results : array();
    }
    
    /**
     * Get active weekly availability
     */
    public static function get_active_weekly($trainer_id) {
        return self::get_weekly($trainer_id, true);
    }
    
    /**
     * Save a single day's availability
     * @param int $trainer_id
     * @param int $day 0-6 (Sun-Sat)
     * @param bool $enabled
     * @param string $start HH:MM format
     * @param string $end HH:MM format
     * @return bool|WP_Error
     */
    public static function save_day($trainer_id, $day, $enabled, $start, $end) {
        if (!$trainer_id || !is_numeric($trainer_id)) {
            return new WP_Error('invalid_trainer', 'Invalid trainer ID');
        }
        
        $day = intval($day);
        if ($day < 0 || $day > 6) {
            return new WP_Error('invalid_day', 'Day must be 0-6');
        }
        
        // Normalize time format
        $start = self::normalize_time($start);
        $end = self::normalize_time($end);
        
        if (!$start || !$end) {
            return new WP_Error('invalid_time', 'Invalid time format');
        }
        
        // Validate end is after start
        if (strtotime($end) <= strtotime($start)) {
            return new WP_Error('invalid_range', 'End time must be after start time');
        }
        
        self::ensure_table();
        
        global $wpdb;
        $table = $wpdb->prefix . 'ptp_availability';
        
        // Check for existing record
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE trainer_id = %d AND day_of_week = %d",
            intval($trainer_id), $day
        ));
        
        $data = array(
            'trainer_id' => intval($trainer_id),
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'is_active' => $enabled ? 1 : 0,
            'updated_at' => current_time('mysql')
        );
        
        if ($existing) {
            $result = $wpdb->update($table, $data, array('id' => $existing));
        } else {
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($table, $data);
        }
        
        if ($result === false) {
            error_log('PTP Availability Save Error: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Database error: ' . $wpdb->last_error);
        }
        
        // Clear cache
        self::clear_cache($trainer_id);
        
        return true;
    }
    
    /**
     * Save complete weekly schedule
     */
    public static function save_weekly($trainer_id, $schedule) {
        if (!$trainer_id || !is_array($schedule)) {
            return false;
        }
        
        $errors = array();
        
        foreach ($schedule as $day => $data) {
            $day = intval($day);
            if ($day < 0 || $day > 6) continue;
            
            $enabled = !empty($data['enabled']);
            $start = isset($data['start']) ? $data['start'] : '09:00';
            $end = isset($data['end']) ? $data['end'] : '17:00';
            
            $result = self::save_day($trainer_id, $day, $enabled, $start, $end);
            
            if (is_wp_error($result)) {
                $errors[$day] = $result->get_error_message();
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Normalize time to HH:MM:SS format
     */
    private static function normalize_time($time) {
        if (empty($time)) return null;
        
        $time = trim($time);
        
        // Already in HH:MM:SS format
        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $time, $m)) {
            return sprintf('%02d:%02d:%02d', $m[1], $m[2], $m[3]);
        }
        
        // HH:MM format
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $m)) {
            return sprintf('%02d:%02d:00', $m[1], $m[2]);
        }
        
        // Try strtotime
        $ts = strtotime($time);
        if ($ts !== false) {
            return date('H:i:s', $ts);
        }
        
        return null;
    }
    
    /**
     * Get available time slots for a specific date
     */
    public static function get_available_slots($trainer_id, $date) {
        if (!$trainer_id || !is_numeric($trainer_id)) {
            return array();
        }
        
        // Validate date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return array();
        }
        
        $date_ts = strtotime($date);
        if ($date_ts === false) {
            return array();
        }
        
        // Don't return slots for past dates
        if ($date_ts < strtotime('today')) {
            return array();
        }
        
        self::ensure_table();
        self::ensure_exceptions_table();
        
        global $wpdb;
        
        $day_of_week = (int) date('w', $date_ts);
        
        // Check for exception first
        $exception = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_availability_exceptions 
             WHERE trainer_id = %d AND exception_date = %s",
            intval($trainer_id), $date
        ));
        
        // If day is blocked, return empty
        if ($exception && !$exception->is_available && $exception->exception_type === 'blocked') {
            return array();
        }
        
        // Get weekly schedule for this day
        $weekly = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_availability 
             WHERE trainer_id = %d AND day_of_week = %d AND is_active = 1",
            intval($trainer_id), $day_of_week
        ));
        
        // No availability set for this day
        if (!$weekly && !($exception && $exception->is_available)) {
            return array();
        }
        
        // Determine working hours
        if ($exception && $exception->is_available && $exception->start_time && $exception->end_time) {
            $start_time = $exception->start_time;
            $end_time = $exception->end_time;
        } elseif ($weekly) {
            $start_time = $weekly->start_time;
            $end_time = $weekly->end_time;
        } else {
            return array();
        }
        
        // Parse times
        $start_ts = strtotime($start_time);
        $end_ts = strtotime($end_time);
        
        if ($start_ts === false || $end_ts === false || $start_ts >= $end_ts) {
            return array();
        }
        
        // Generate hourly slots
        $slots = array();
        $current_ts = $start_ts;
        $slot_duration = isset($weekly->slot_duration) ? intval($weekly->slot_duration) : 60;
        if ($slot_duration < 30) $slot_duration = 60;
        
        $now = time();
        $is_today = ($date === date('Y-m-d'));
        
        // Get existing bookings for this trainer on this date
        $booked_times = self::get_booked_times($trainer_id, $date);
        
        while ($current_ts < $end_ts) {
            $time_str = date('H:i', $current_ts);
            $time_full = $time_str . ':00';
            
            // Skip past times for today
            if ($is_today) {
                $slot_datetime = strtotime($date . ' ' . $time_str);
                if ($slot_datetime <= $now) {
                    $current_ts += $slot_duration * 60;
                    continue;
                }
            }
            
            // Check if slot is booked
            $is_booked = in_array($time_full, $booked_times);
            
            if (!$is_booked) {
                $slots[] = array(
                    'time' => $time_full,
                    'start' => $time_str,
                    'display' => date('g:i A', $current_ts),
                    'available' => true
                );
            }
            
            $current_ts += $slot_duration * 60;
        }
        
        return $slots;
    }
    
    /**
     * Get booked times for a trainer on a date
     */
    private static function get_booked_times($trainer_id, $date) {
        global $wpdb;
        
        $times = array();
        
        // Check bookings table
        $bookings_table = $wpdb->prefix . 'ptp_bookings';
        if ($wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") === $bookings_table) {
            $booked = $wpdb->get_col($wpdb->prepare(
                "SELECT start_time FROM $bookings_table 
                 WHERE trainer_id = %d AND session_date = %s AND status NOT IN ('cancelled', 'refunded')",
                intval($trainer_id), $date
            ));
            $times = array_merge($times, $booked);
        }
        
        // Check sessions table
        $sessions_table = $wpdb->prefix . 'ptp_sessions';
        if ($wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") === $sessions_table) {
            $sessions = $wpdb->get_col($wpdb->prepare(
                "SELECT start_time FROM $sessions_table 
                 WHERE trainer_id = %d AND session_date = %s AND session_status NOT IN ('cancelled', 'no_show')",
                intval($trainer_id), $date
            ));
            $times = array_merge($times, $sessions);
        }
        
        return array_unique($times);
    }
    
    /**
     * Check if trainer has any availability set
     */
    public static function has_availability($trainer_id) {
        if (!$trainer_id) return false;
        
        self::ensure_table();
        
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ptp_availability 
             WHERE trainer_id = %d AND is_active = 1",
            intval($trainer_id)
        ));
        
        return intval($count) > 0;
    }
    
    /**
     * Get dates with availability for a month
     */
    public static function get_available_dates($trainer_id, $month, $year) {
        if (!$trainer_id || !is_numeric($trainer_id)) {
            return array();
        }
        
        $month = intval($month);
        $year = intval($year);
        
        if ($month < 1 || $month > 12 || $year < 2020 || $year > 2100) {
            return array();
        }
        
        $dates = array();
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $today = date('Y-m-d');
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            
            if ($date < $today) {
                continue;
            }
            
            $slots = self::get_available_slots($trainer_id, $date);
            
            if (!empty($slots)) {
                $dates[$date] = count($slots);
            }
        }
        
        return $dates;
    }
    
    /**
     * Block a specific date
     */
    public static function block_date($trainer_id, $date, $reason = '') {
        if (!$trainer_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        
        self::ensure_exceptions_table();
        
        global $wpdb;
        $table = $wpdb->prefix . 'ptp_availability_exceptions';
        
        // Remove existing exception
        $wpdb->delete($table, array(
            'trainer_id' => intval($trainer_id),
            'exception_date' => $date
        ));
        
        // Insert block
        $result = $wpdb->insert($table, array(
            'trainer_id' => intval($trainer_id),
            'exception_date' => $date,
            'exception_type' => 'blocked',
            'is_available' => 0,
            'reason' => sanitize_text_field(substr($reason, 0, 255)),
            'created_at' => current_time('mysql')
        ));
        
        self::clear_cache($trainer_id);
        
        return $result !== false;
    }
    
    /**
     * Unblock a specific date
     */
    public static function unblock_date($trainer_id, $date) {
        if (!$trainer_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        
        self::ensure_exceptions_table();
        
        global $wpdb;
        
        $result = $wpdb->delete($wpdb->prefix . 'ptp_availability_exceptions', array(
            'trainer_id' => intval($trainer_id),
            'exception_date' => $date
        ));
        
        self::clear_cache($trainer_id);
        
        return $result !== false;
    }
    
    /**
     * Get blocked dates for a trainer
     */
    public static function get_blocked_dates($trainer_id, $start_date = null, $end_date = null) {
        if (!$trainer_id) return array();
        
        self::ensure_exceptions_table();
        
        global $wpdb;
        $table = $wpdb->prefix . 'ptp_availability_exceptions';
        
        $sql = "SELECT exception_date, reason FROM $table WHERE trainer_id = %d AND is_available = 0";
        $params = array(intval($trainer_id));
        
        if ($start_date) {
            $sql .= " AND exception_date >= %s";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $sql .= " AND exception_date <= %s";
            $params[] = $end_date;
        }
        
        $sql .= " ORDER BY exception_date ASC";
        
        return $wpdb->get_results($wpdb->prepare($sql, ...$params));
    }
    
    /**
     * Clear availability cache for trainer
     */
    public static function clear_cache($trainer_id) {
        $year = date('Y');
        $month = date('n');
        
        // Clear current and next 3 months
        for ($i = 0; $i < 4; $i++) {
            $cache_key = 'ptp_availability_' . $trainer_id . '_' . $year . '_' . $month;
            wp_cache_delete($cache_key, 'ptp');
            
            // Also clear slot cache
            wp_cache_delete('ptp_slots_' . $trainer_id, 'ptp');
            
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
        }
    }
    
    /**
     * AJAX: Save trainer schedule (primary handler)
     */
    public static function ajax_save_schedule() {
        // Log for debugging
        error_log('PTP Schedule Save Request: ' . print_r($_POST, true));
        
        // CRITICAL: Force table check/repair before any save operation
        self::$table_verified = false;
        self::ensure_table();
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ptp_nonce')) {
            error_log('PTP Schedule Save: Nonce failed');
            wp_send_json_error(array('message' => 'Security check failed. Please refresh the page.'));
            return;
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please log in'));
            return;
        }
        
        // Get trainer
        $trainer = null;
        if (class_exists('PTP_Trainer')) {
            $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        }
        
        if (!$trainer) {
            error_log('PTP Schedule Save: No trainer found for user ' . get_current_user_id());
            wp_send_json_error(array('message' => 'Trainer profile not found'));
            return;
        }
        
        // Get day data
        $day = isset($_POST['day']) ? intval($_POST['day']) : -1;
        
        if ($day < 0 || $day > 6) {
            wp_send_json_error(array('message' => 'Invalid day'));
            return;
        }
        
        $enabled = isset($_POST['enabled']) && in_array($_POST['enabled'], array('1', 1, 'true', true), true);
        $start = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : '09:00';
        $end = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : '17:00';
        
        // Save
        $result = self::save_day($trainer->id, $day, $enabled, $start, $end);
        
        if (is_wp_error($result)) {
            error_log('PTP Schedule Save Error: ' . $result->get_error_message());
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Trigger calendar sync if connected
        if (class_exists('PTP_Calendar_Sync')) {
            do_action('ptp_availability_updated', $trainer->id);
        }
        
        error_log('PTP Schedule Save: Success for day ' . $day);
        
        wp_send_json_success(array(
            'message' => 'Schedule saved',
            'day' => $day,
            'enabled' => $enabled,
            'start' => $start,
            'end' => $end
        ));
    }
    
    /**
     * AJAX: Get trainer schedule
     */
    public static function ajax_get_schedule() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ptp_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $trainer_id = isset($_POST['trainer_id']) ? intval($_POST['trainer_id']) : 0;
        
        // If no trainer_id, get current user's trainer
        if (!$trainer_id && is_user_logged_in() && class_exists('PTP_Trainer')) {
            $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
            if ($trainer) {
                $trainer_id = $trainer->id;
            }
        }
        
        if (!$trainer_id) {
            wp_send_json_error(array('message' => 'Invalid trainer'));
            return;
        }
        
        $weekly = self::get_weekly($trainer_id);
        
        // Format for frontend
        $schedule = array();
        foreach ($weekly as $slot) {
            $schedule[$slot->day_of_week] = array(
                'enabled' => (bool) $slot->is_active,
                'start' => substr($slot->start_time, 0, 5),
                'end' => substr($slot->end_time, 0, 5)
            );
        }
        
        wp_send_json_success(array('schedule' => $schedule));
    }
    
    /**
     * AJAX: Block date
     */
    public static function ajax_block_date() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ptp_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        if (!is_user_logged_in() || !class_exists('PTP_Trainer')) {
            wp_send_json_error(array('message' => 'Please log in'));
            return;
        }
        
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer not found'));
            return;
        }
        
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
        
        if (self::block_date($trainer->id, $date, $reason)) {
            wp_send_json_success(array('message' => 'Date blocked'));
        } else {
            wp_send_json_error(array('message' => 'Failed to block date'));
        }
    }
    
    /**
     * AJAX: Unblock date
     */
    public static function ajax_unblock_date() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ptp_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        if (!is_user_logged_in() || !class_exists('PTP_Trainer')) {
            wp_send_json_error(array('message' => 'Please log in'));
            return;
        }
        
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer not found'));
            return;
        }
        
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (self::unblock_date($trainer->id, $date)) {
            wp_send_json_success(array('message' => 'Date unblocked'));
        } else {
            wp_send_json_error(array('message' => 'Failed to unblock date'));
        }
    }
    
    /**
     * AJAX: Get availability calendar (public - for booking)
     */
    public static function ajax_get_calendar() {
        $trainer_id = isset($_REQUEST['trainer_id']) ? intval($_REQUEST['trainer_id']) : 0;
        $month = isset($_REQUEST['month']) ? intval($_REQUEST['month']) : intval(date('n'));
        $year = isset($_REQUEST['year']) ? intval($_REQUEST['year']) : intval(date('Y'));
        
        if (!$trainer_id) {
            wp_send_json_error(array('message' => 'Invalid trainer'));
            return;
        }
        
        // Validate month/year
        if ($month < 1 || $month > 12) $month = intval(date('n'));
        if ($year < 2020 || $year > 2100) $year = intval(date('Y'));
        
        // Try cache
        $cache_key = 'ptp_availability_' . $trainer_id . '_' . $year . '_' . $month;
        $cached = wp_cache_get($cache_key, 'ptp');
        
        if ($cached !== false) {
            wp_send_json_success(array(
                'availability' => $cached,
                'cached' => true
            ));
            return;
        }
        
        // Get available dates
        $available_dates = self::get_available_dates($trainer_id, $month, $year);
        
        // Get blocked dates
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = sprintf('%04d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));
        $blocked = self::get_blocked_dates($trainer_id, $start_date, $end_date);
        
        $blocked_dates = array();
        foreach ($blocked as $b) {
            $blocked_dates[] = $b->exception_date;
        }
        
        // Cache for 5 minutes
        wp_cache_set($cache_key, $available_dates, 'ptp', 300);
        
        wp_send_json_success(array(
            'availability' => $available_dates,
            'blocked' => $blocked_dates,
            'month' => $month,
            'year' => $year
        ));
    }
}

// Initialize on plugins loaded
add_action('plugins_loaded', array('PTP_Availability', 'init'), 20);
