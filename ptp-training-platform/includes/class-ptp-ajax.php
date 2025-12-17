<?php
/**
 * AJAX Handler Class
 * Bulletproof v24.6.0 - Enhanced error handling and validation
 */

defined('ABSPATH') || exit;

class PTP_Ajax {
    
    /**
     * Rate limiting - prevent abuse
     */
    private static $rate_limits = array(
        'add_player' => array('limit' => 10, 'window' => 60),
        'send_message' => array('limit' => 30, 'window' => 60),
        'create_booking' => array('limit' => 5, 'window' => 60),
    );
    
    public static function init() {
        // Public actions
        add_action('wp_ajax_nopriv_ptp_login', array(__CLASS__, 'login'));
        add_action('wp_ajax_nopriv_ptp_register', array(__CLASS__, 'register'));
        add_action('wp_ajax_nopriv_ptp_get_trainers', array(__CLASS__, 'get_trainers'));
        add_action('wp_ajax_nopriv_ptp_get_available_slots', array(__CLASS__, 'get_available_slots'));
        add_action('wp_ajax_nopriv_ptp_submit_application', array(__CLASS__, 'submit_application'));
        add_action('wp_ajax_ptp_submit_application', array(__CLASS__, 'submit_application')); // Also for logged-in users
        
        // Guest booking actions (for guest checkout)
        add_action('wp_ajax_nopriv_ptp_create_booking', array(__CLASS__, 'create_booking'));
        add_action('wp_ajax_nopriv_ptp_create_payment_intent', array(__CLASS__, 'create_payment_intent'));
        add_action('wp_ajax_nopriv_ptp_confirm_booking_payment', array(__CLASS__, 'confirm_booking_payment'));
        
        // Logged in actions
        add_action('wp_ajax_ptp_get_trainers', array(__CLASS__, 'get_trainers'));
        add_action('wp_ajax_ptp_get_available_slots', array(__CLASS__, 'get_available_slots'));
        add_action('wp_ajax_ptp_create_booking', array(__CLASS__, 'create_booking'));
        add_action('wp_ajax_ptp_cancel_booking', array(__CLASS__, 'cancel_booking'));
        add_action('wp_ajax_ptp_confirm_session', array(__CLASS__, 'confirm_session'));
        add_action('wp_ajax_ptp_add_player', array(__CLASS__, 'add_player'));
        add_action('wp_ajax_ptp_update_player', array(__CLASS__, 'update_player'));
        add_action('wp_ajax_ptp_delete_player', array(__CLASS__, 'delete_player'));
        add_action('wp_ajax_ptp_get_conversations', array(__CLASS__, 'get_conversations'));
        add_action('wp_ajax_ptp_get_messages', array(__CLASS__, 'get_messages'));
        add_action('wp_ajax_ptp_get_new_messages', array(__CLASS__, 'get_new_messages'));
        add_action('wp_ajax_ptp_send_message', array(__CLASS__, 'send_message'));
        add_action('wp_ajax_ptp_start_conversation', array(__CLASS__, 'start_conversation'));
        add_action('wp_ajax_ptp_update_profile', array(__CLASS__, 'update_profile'));
        add_action('wp_ajax_ptp_update_trainer_profile', array(__CLASS__, 'update_trainer_profile'));
        add_action('wp_ajax_ptp_upload_trainer_photo', array(__CLASS__, 'upload_trainer_photo'));
        add_action('wp_ajax_ptp_complete_onboarding', array(__CLASS__, 'complete_onboarding'));
        add_action('wp_ajax_ptp_update_password', array(__CLASS__, 'update_password'));
        add_action('wp_ajax_ptp_update_notifications', array(__CLASS__, 'update_notifications'));
        add_action('wp_ajax_ptp_update_availability', array(__CLASS__, 'update_availability'));
        add_action('wp_ajax_ptp_submit_review', array(__CLASS__, 'submit_review'));
        
        // Payment actions
        add_action('wp_ajax_ptp_create_payment_intent', array(__CLASS__, 'create_payment_intent'));
        add_action('wp_ajax_ptp_confirm_booking_payment', array(__CLASS__, 'confirm_booking_payment'));
        
        // Stripe Connect actions
        add_action('wp_ajax_ptp_create_stripe_connect_account', array(__CLASS__, 'create_stripe_connect_account'));
        add_action('wp_ajax_ptp_get_stripe_dashboard_link', array(__CLASS__, 'get_stripe_dashboard_link'));
        
        // Trainer geocoding (public - auto-saves trainer coordinates)
        add_action('wp_ajax_nopriv_ptp_save_trainer_coords', array(__CLASS__, 'save_trainer_coords'));
        add_action('wp_ajax_ptp_save_trainer_coords', array(__CLASS__, 'save_trainer_coords'));
        
        // Enhanced availability management
        add_action('wp_ajax_ptp_quick_update_availability', array(__CLASS__, 'quick_update_availability'));
        add_action('wp_ajax_ptp_toggle_day_availability', array(__CLASS__, 'toggle_day_availability'));
        add_action('wp_ajax_ptp_add_availability_exception', array(__CLASS__, 'add_availability_exception'));
        add_action('wp_ajax_ptp_remove_availability_exception', array(__CLASS__, 'remove_availability_exception'));
        
        // Fallback route for new schedule save (forwards to PTP_Availability class)
        add_action('wp_ajax_ptp_save_trainer_schedule', array(__CLASS__, 'save_trainer_schedule_fallback'));
        
        // Public messaging (send message to trainer from public profile)
        add_action('wp_ajax_ptp_send_public_message', array(__CLASS__, 'send_public_message'));
        add_action('wp_ajax_nopriv_ptp_send_public_message', array(__CLASS__, 'send_public_message_guest'));
        
        // Trainer availability calendar (public - for booking calendar)
        add_action('wp_ajax_nopriv_ptp_get_trainer_availability', array(__CLASS__, 'get_trainer_availability'));
        add_action('wp_ajax_ptp_get_trainer_availability', array(__CLASS__, 'get_trainer_availability'));
    }
    
    /**
     * Verify nonce with detailed error
     */
    private static function verify_nonce() {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (empty($nonce)) {
            error_log('PTP AJAX: Missing nonce');
            wp_send_json_error(array('message' => 'Security token missing. Please refresh the page.'));
            exit;
        }
        if (!wp_verify_nonce($nonce, 'ptp_nonce')) {
            error_log('PTP AJAX: Invalid nonce');
            wp_send_json_error(array('message' => 'Security check failed. Please refresh the page and try again.'));
            exit;
        }
    }
    
    /**
     * Require login with redirect URL
     */
    private static function require_login() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => 'Please log in to continue',
                'login_url' => wp_login_url(),
                'code' => 'not_logged_in'
            ));
            exit;
        }
    }
    
    /**
     * Sanitize and validate integer
     */
    private static function get_int($key, $default = 0, $min = null, $max = null) {
        $value = isset($_POST[$key]) ? intval($_POST[$key]) : $default;
        if ($min !== null && $value < $min) $value = $min;
        if ($max !== null && $value > $max) $value = $max;
        return $value;
    }
    
    /**
     * Sanitize and validate string
     */
    private static function get_string($key, $default = '', $max_length = 1000) {
        $value = isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : $default;
        if (strlen($value) > $max_length) {
            $value = substr($value, 0, $max_length);
        }
        return $value;
    }
    
    /**
     * Sanitize and validate email
     */
    private static function get_email($key, $default = '') {
        $value = isset($_POST[$key]) ? sanitize_email($_POST[$key]) : $default;
        return is_email($value) ? $value : $default;
    }
    
    /**
     * Validate time format (HH:MM)
     */
    private static function validate_time($time, $default = '16:00') {
        if (empty($time)) return $default;
        $time = substr(trim($time), 0, 5);
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            return $default;
        }
        return $time;
    }
    
    /**
     * Validate date format (YYYY-MM-DD)
     */
    private static function validate_date($date) {
        if (empty($date)) return false;
        $date = trim($date);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        $parts = explode('-', $date);
        return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]) ? $date : false;
    }
    
    /**
     * Check rate limit
     */
    private static function check_rate_limit($action) {
        if (!isset(self::$rate_limits[$action])) return true;
        
        $user_id = get_current_user_id();
        $ip = self::get_client_ip();
        $key = 'ptp_rate_' . $action . '_' . ($user_id ?: md5($ip));
        
        $data = get_transient($key);
        $limit = self::$rate_limits[$action]['limit'];
        $window = self::$rate_limits[$action]['window'];
        
        if ($data === false) {
            set_transient($key, array('count' => 1, 'start' => time()), $window);
            return true;
        }
        
        if ($data['count'] >= $limit) {
            return false;
        }
        
        $data['count']++;
        set_transient($key, $data, $window - (time() - $data['start']));
        return true;
    }
    
    /**
     * Get client IP safely
     */
    private static function get_client_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }
    
    /**
     * Safe JSON response with logging
     */
    private static function send_error($message, $code = 'error', $log = true) {
        if ($log) {
            error_log('PTP AJAX Error [' . $code . ']: ' . $message);
        }
        wp_send_json_error(array('message' => $message, 'code' => $code));
        exit;
    }
    
    /**
     * Ensure database tables exist
     */
    private static function ensure_tables() {
        if (class_exists('PTP_Database')) {
            PTP_Database::create_tables();
        }
    }
    
    public static function login() {
        self::verify_nonce();
        
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);
        
        if (empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Please enter email and password'));
        }
        
        $user = wp_authenticate($email, $password);
        
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => 'Invalid email or password'));
        }
        
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        
        wp_send_json_success(array(
            'message' => 'Login successful',
            'redirect' => PTP_User::get_dashboard_url($user->ID),
        ));
    }
    
    public static function register() {
        self::verify_nonce();
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $user_type = sanitize_text_field($_POST['user_type'] ?? 'parent');
        
        if (empty($name) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Please fill in all required fields'));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Please enter a valid email address'));
        }
        
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'An account with this email already exists'));
        }
        
        if (strlen($password) < 8) {
            wp_send_json_error(array('message' => 'Password must be at least 8 characters'));
        }
        
        $name_parts = explode(' ', $name, 2);
        $user_id = PTP_User::create_user($email, $password, array(
            'first_name' => $name_parts[0],
            'last_name' => $name_parts[1] ?? '',
            'display_name' => $name,
            'phone' => $phone,
        ));
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }
        
        // Create parent profile
        if ($user_type === 'parent') {
            PTP_Parent::create($user_id, array(
                'display_name' => $name,
                'phone' => $phone,
            ));
        }
        
        // Log in the user
        PTP_User::login_user($user_id);
        
        wp_send_json_success(array(
            'message' => 'Account created successfully',
            'redirect' => $user_type === 'parent' ? home_url('/my-training/') : home_url('/become-a-trainer/'),
        ));
    }
    
    /**
     * Enhanced trainer search with comprehensive filters
     * Supports: location/radius, specialties, price range, rating, availability, playing level
     */
    public static function get_trainers() {
        global $wpdb;

        // Parse filter parameters
        $search = sanitize_text_field($_POST['search'] ?? '');
        $specialty = sanitize_text_field($_POST['specialty'] ?? '');
        $sort = sanitize_text_field($_POST['sort'] ?? 'featured');
        $zip = sanitize_text_field($_POST['zip'] ?? '');
        $radius = intval($_POST['radius'] ?? 25);
        $min_price = floatval($_POST['min_price'] ?? 0);
        $max_price = floatval($_POST['max_price'] ?? 500);
        $min_rating = floatval($_POST['min_rating'] ?? 0);
        $playing_level = sanitize_text_field($_POST['playing_level'] ?? '');
        $featured_only = !empty($_POST['featured_only']);
        $available_date = sanitize_text_field($_POST['available_date'] ?? '');
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = min(50, max(6, intval($_POST['per_page'] ?? 12)));

        // Clamp radius between 5-100 miles
        $radius = max(5, min(100, $radius));

        // Build query
        $where = array("t.status = 'active'");
        $params = array();
        $select_extra = '';
        $having = array();

        // Text search
        if (!empty($search)) {
            $where[] = "(t.display_name LIKE %s OR t.headline LIKE %s OR t.bio LIKE %s OR t.location LIKE %s OR t.college LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Specialty filter
        if (!empty($specialty)) {
            $where[] = "t.specialties LIKE %s";
            $params[] = '%' . $wpdb->esc_like($specialty) . '%';
        }

        // Price range
        if ($min_price > 0) {
            $where[] = "t.hourly_rate >= %f";
            $params[] = $min_price;
        }
        if ($max_price < 500) {
            $where[] = "t.hourly_rate <= %f";
            $params[] = $max_price;
        }

        // Rating filter
        if ($min_rating > 0) {
            $where[] = "t.average_rating >= %f";
            $params[] = $min_rating;
        }

        // Playing level filter
        if (!empty($playing_level)) {
            $levels = array_map('sanitize_text_field', explode(',', $playing_level));
            $placeholders = implode(',', array_fill(0, count($levels), '%s'));
            $where[] = "t.playing_level IN ($placeholders)";
            $params = array_merge($params, $levels);
        }

        // Featured only
        if ($featured_only) {
            $where[] = "t.is_featured = 1";
        }

        // Location/radius search
        $user_lat = null;
        $user_lng = null;
        if (!empty($zip) && class_exists('PTP_Geocoding')) {
            $geo = PTP_Geocoding::geocode($zip);
            if (!empty($geo['success']) && !empty($geo['latitude'])) {
                $user_lat = floatval($geo['latitude']);
                $user_lng = floatval($geo['longitude']);

                // Add distance calculation to select
                $select_extra = ", (3959 * acos(
                    cos(radians(%f)) * cos(radians(t.latitude)) *
                    cos(radians(t.longitude) - radians(%f)) +
                    sin(radians(%f)) * sin(radians(t.latitude))
                )) AS distance";

                // Prepend lat/lng params
                array_unshift($params, $user_lat, $user_lng, $user_lat);

                // Filter by radius using HAVING (because distance is calculated)
                $having[] = "distance <= %f";
                $params[] = $radius;

                // Require coordinates to be set
                $where[] = "t.latitude IS NOT NULL AND t.longitude IS NOT NULL";
            }
        }

        // Availability filter - check if trainer has slots on specified date
        if (!empty($available_date) && class_exists('PTP_Availability')) {
            $date = self::validate_date($available_date);
            if ($date) {
                $day_of_week = date('w', strtotime($date));
                $where[] = "EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}ptp_availability a
                    WHERE a.trainer_id = t.id
                    AND a.day_of_week = %d
                    AND a.is_active = 1
                )";
                $params[] = $day_of_week;

                // Exclude trainers with blocked dates
                $where[] = "NOT EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}ptp_availability_exceptions ae
                    WHERE ae.trainer_id = t.id
                    AND ae.exception_date = %s
                    AND ae.is_available = 0
                )";
                $params[] = $date;
            }
        }

        // Build ORDER BY
        $orderby = 't.is_featured DESC, t.average_rating DESC';
        switch ($sort) {
            case 'rating':
                $orderby = 't.average_rating DESC, t.review_count DESC';
                break;
            case 'price_low':
                $orderby = 't.hourly_rate ASC, t.average_rating DESC';
                break;
            case 'price_high':
                $orderby = 't.hourly_rate DESC, t.average_rating DESC';
                break;
            case 'reviews':
                $orderby = 't.review_count DESC, t.average_rating DESC';
                break;
            case 'distance':
                if ($user_lat !== null) {
                    $orderby = 'distance ASC, t.is_featured DESC';
                }
                break;
            case 'newest':
                $orderby = 't.id DESC';
                break;
        }

        // Build WHERE clause
        $where_clause = implode(' AND ', $where);

        // Build HAVING clause
        $having_clause = !empty($having) ? 'HAVING ' . implode(' AND ', $having) : '';

        // Count total results (for pagination)
        $count_sql = "SELECT COUNT(DISTINCT t.id) FROM {$wpdb->prefix}ptp_trainers t WHERE $where_clause";
        if (!empty($having)) {
            // For HAVING clause, we need a subquery
            $count_sql = "SELECT COUNT(*) FROM (
                SELECT t.id $select_extra
                FROM {$wpdb->prefix}ptp_trainers t
                WHERE $where_clause
                GROUP BY t.id
                $having_clause
            ) AS filtered";
        }

        // Get params for count query (without pagination)
        $count_params = $params;
        $total = $wpdb->get_var($wpdb->prepare($count_sql, $count_params));

        // Calculate pagination
        $offset = ($page - 1) * $per_page;
        $total_pages = ceil($total / $per_page);

        // Main query
        $sql = "SELECT t.* $select_extra
                FROM {$wpdb->prefix}ptp_trainers t
                WHERE $where_clause
                GROUP BY t.id
                $having_clause
                ORDER BY $orderby
                LIMIT %d OFFSET %d";

        $params[] = $per_page;
        $params[] = $offset;

        $trainers = $wpdb->get_results($wpdb->prepare($sql, $params));

        // Format trainers for response
        $level_labels = array(
            'pro' => 'PRO',
            'college_d1' => 'D1',
            'college_d2' => 'D2',
            'college_d3' => 'D3',
            'academy' => 'ACADEMY',
            'semi_pro' => 'SEMI-PRO'
        );

        $formatted = array();
        foreach ($trainers as $t) {
            $formatted[] = array(
                'id' => (int)$t->id,
                'name' => $t->display_name,
                'slug' => $t->slug,
                'photo' => $t->photo_url ?: (class_exists('PTP_Images') ? PTP_Images::avatar($t->display_name, 400) : ''),
                'headline' => $t->headline ?: ($t->college ?: 'Soccer Trainer'),
                'bio' => wp_trim_words($t->bio ?: '', 20),
                'rate' => (int)($t->hourly_rate ?: 70),
                'rating' => $t->average_rating ? round((float)$t->average_rating, 1) : 5.0,
                'reviews' => (int)($t->review_count ?: 0),
                'lat' => $t->latitude ? (float)$t->latitude : null,
                'lng' => $t->longitude ? (float)$t->longitude : null,
                'location' => $t->location ?: 'Philadelphia Area',
                'city' => $t->city ?: '',
                'state' => $t->state ?: '',
                'featured' => !empty($t->is_featured),
                'verified' => !empty($t->is_verified),
                'level' => isset($level_labels[$t->playing_level]) ? $level_labels[$t->playing_level] : '',
                'level_raw' => $t->playing_level ?: '',
                'college' => $t->college ?: '',
                'specialties' => $t->specialties ? explode(',', $t->specialties) : array(),
                'distance' => isset($t->distance) ? round($t->distance, 1) : null,
                'profile_url' => home_url('/trainer/' . $t->slug . '/'),
            );
        }

        wp_send_json_success(array(
            'trainers' => $formatted,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => (int)$total_pages,
            'has_more' => $page < $total_pages,
            'filters_applied' => array(
                'search' => $search,
                'specialty' => $specialty,
                'zip' => $zip,
                'radius' => $radius,
                'min_price' => $min_price,
                'max_price' => $max_price,
                'min_rating' => $min_rating,
                'playing_level' => $playing_level,
                'featured_only' => $featured_only,
                'available_date' => $available_date,
            ),
            'user_location' => $user_lat ? array('lat' => $user_lat, 'lng' => $user_lng) : null,
        ));
    }
    
    /**
     * Get available time slots for a trainer on a specific date
     * BULLETPROOF: Validation, caching, and graceful degradation
     */
    public static function get_available_slots() {
        try {
            $trainer_id = self::get_int('trainer_id', 0, 1);
            $date = self::get_string('date', '', 10);
            
            // Validate trainer ID
            if (!$trainer_id || $trainer_id < 1) {
                wp_send_json_error(array('message' => 'Invalid trainer', 'slots' => array()));
                return;
            }
            
            // Validate date
            $date = self::validate_date($date);
            if (!$date) {
                wp_send_json_error(array('message' => 'Invalid date format', 'slots' => array()));
                return;
            }
            
            // Don't allow dates too far in the past or future
            $date_ts = strtotime($date);
            $today_ts = strtotime('today');
            $max_future = strtotime('+90 days');
            
            if ($date_ts < $today_ts) {
                wp_send_json_success(array('slots' => array(), 'message' => 'Date is in the past'));
                return;
            }
            
            if ($date_ts > $max_future) {
                wp_send_json_success(array('slots' => array(), 'message' => 'Date is too far in the future'));
                return;
            }
            
            // Verify trainer exists and is active
            $trainer = PTP_Trainer::get($trainer_id);
            if (!$trainer || $trainer->status !== 'active') {
                wp_send_json_error(array('message' => 'Trainer not available', 'slots' => array()));
                return;
            }
            
            // Try cache first
            $cache_key = 'ptp_slots_' . $trainer_id . '_' . $date;
            $cached_slots = wp_cache_get($cache_key, 'ptp');
            if ($cached_slots !== false) {
                wp_send_json_success(array('slots' => $cached_slots));
                return;
            }
            
            // Ensure availability class exists
            if (!class_exists('PTP_Availability')) {
                wp_send_json_success(array('slots' => array(), 'message' => 'Availability system not ready'));
                return;
            }
            
            $slots = PTP_Availability::get_available_slots($trainer_id, $date);
            
            // Ensure we return an array
            if (!is_array($slots)) {
                $slots = array();
            }
            
            // Cache for 5 minutes
            wp_cache_set($cache_key, $slots, 'ptp', 300);
            
            wp_send_json_success(array('slots' => $slots));
            
        } catch (Exception $e) {
            error_log('PTP Get Slots Exception: ' . $e->getMessage());
            wp_send_json_success(array('slots' => array(), 'message' => 'Could not load times'));
        }
    }
    
    public static function create_booking() {
        self::verify_nonce();
        
        // Quick repair tables if needed
        PTP_Database::quick_repair();
        
        $is_guest = !empty($_POST['is_guest']);
        $parent = null;
        $player_id = 0;
        $user_id = 0;
        
        if ($is_guest) {
            // Guest booking flow
            $guest_email = sanitize_email($_POST['guest_email'] ?? '');
            $guest_first_name = sanitize_text_field($_POST['guest_first_name'] ?? '');
            $guest_last_name = sanitize_text_field($_POST['guest_last_name'] ?? '');
            $guest_phone = sanitize_text_field($_POST['guest_phone'] ?? '');
            
            if (empty($guest_email) || !is_email($guest_email)) {
                wp_send_json_error(array('message' => 'Valid email address is required'));
            }
            
            if (empty($guest_first_name) || empty($guest_last_name)) {
                wp_send_json_error(array('message' => 'Name is required'));
            }
            
            // Check if user exists
            $existing_user = get_user_by('email', $guest_email);
            
            if ($existing_user) {
                $user_id = $existing_user->ID;
                $parent = PTP_Parent::get_by_user_id($user_id);
                
                // Create parent profile if missing
                if (!$parent) {
                    $parent_id = PTP_Parent::create($user_id, array(
                        'display_name' => $guest_first_name . ' ' . $guest_last_name,
                        'phone' => $guest_phone,
                    ));
                    $parent = PTP_Parent::get($parent_id);
                }
            } else {
                // Check if they want to create an account
                $create_account = !empty($_POST['create_account']);
                $password = isset($_POST['password']) ? $_POST['password'] : '';
                
                if ($create_account && !empty($password)) {
                    if (strlen($password) < 8) {
                        wp_send_json_error(array('message' => 'Password must be at least 8 characters'));
                    }
                    
                    // Create full user account
                    $user_id = wp_create_user($guest_email, $password, $guest_email);
                    
                    if (is_wp_error($user_id)) {
                        wp_send_json_error(array('message' => 'Failed to create account: ' . $user_id->get_error_message()));
                    }
                    
                    // Update user info
                    wp_update_user(array(
                        'ID' => $user_id,
                        'first_name' => $guest_first_name,
                        'last_name' => $guest_last_name,
                        'display_name' => $guest_first_name . ' ' . $guest_last_name,
                    ));
                    
                    // Assign parent role
                    $user = new WP_User($user_id);
                    $user->set_role('ptp_parent');
                    
                } else {
                    // Create guest user (no password)
                    $random_password = wp_generate_password(20, true, true);
                    $user_id = wp_create_user($guest_email, $random_password, $guest_email);
                    
                    if (is_wp_error($user_id)) {
                        wp_send_json_error(array('message' => 'Failed to process booking: ' . $user_id->get_error_message()));
                    }
                    
                    // Update user info
                    wp_update_user(array(
                        'ID' => $user_id,
                        'first_name' => $guest_first_name,
                        'last_name' => $guest_last_name,
                        'display_name' => $guest_first_name . ' ' . $guest_last_name,
                    ));
                    
                    // Assign parent role
                    $user = new WP_User($user_id);
                    $user->set_role('ptp_parent');
                    
                    // Mark as guest account
                    update_user_meta($user_id, 'ptp_guest_account', true);
                }
                
                // Create parent profile
                $parent_id = PTP_Parent::create($user_id, array(
                    'display_name' => $guest_first_name . ' ' . $guest_last_name,
                    'phone' => $guest_phone,
                ));
                $parent = PTP_Parent::get($parent_id);
            }
            
            // Create the player
            $player_first_name = sanitize_text_field($_POST['player_first_name'] ?? '');
            $player_last_name = sanitize_text_field($_POST['player_last_name'] ?? '');
            $player_age = intval($_POST['player_age'] ?? 0);
            $player_skill = sanitize_text_field($_POST['player_skill'] ?? 'beginner');
            $player_goals = sanitize_textarea_field($_POST['player_goals'] ?? '');
            
            if (empty($player_first_name)) {
                wp_send_json_error(array('message' => 'Player name is required'));
            }
            
            global $wpdb;
            $wpdb->insert($wpdb->prefix . 'ptp_players', array(
                'parent_id' => $parent->id,
                'name' => $player_first_name . ' ' . $player_last_name,
                'age' => $player_age,
                'skill_level' => $player_skill,
                'goals' => $player_goals,
                'created_at' => current_time('mysql'),
            ));
            $player_id = $wpdb->insert_id;
            
        } else {
            // Logged in user booking
            self::require_login();
            
            $user_id = get_current_user_id();
            $parent = PTP_Parent::get_by_user_id($user_id);
            
            // Auto-create parent profile if missing
            if (!$parent) {
                $user = wp_get_current_user();
                $parent_id = PTP_Parent::create($user_id, array(
                    'display_name' => $user->display_name ?: $user->user_login ?: 'Parent',
                ));
                
                if (is_wp_error($parent_id)) {
                    wp_send_json_error(array('message' => 'Could not create parent profile: ' . $parent_id->get_error_message()));
                }
                
                $parent = PTP_Parent::get($parent_id);
                
                if (!$parent) {
                    wp_send_json_error(array('message' => 'Parent profile creation failed'));
                }
            }
            
            $player_id = intval($_POST['player_id'] ?? 0);
            
            if (!$player_id) {
                wp_send_json_error(array('message' => 'No player selected. Please select or add a player first.'));
            }
            
            // Validate player belongs to parent
            if (!PTP_Player::belongs_to_parent($player_id, $parent->id)) {
                error_log("PTP Booking: Player $player_id does not belong to parent {$parent->id}");
                wp_send_json_error(array('message' => 'Invalid player selected. Please refresh the page and try again.'));
            }
            
            // Update parent phone if provided
            $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
            if (!empty($phone) && $parent) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'ptp_parents',
                    array('phone' => $phone),
                    array('id' => $parent->id),
                    array('%s'),
                    array('%d')
                );
            }
        }
        
        $data = array(
            'trainer_id' => intval($_POST['trainer_id'] ?? 0),
            'parent_id' => $parent->id,
            'player_id' => $player_id,
            'session_date' => sanitize_text_field($_POST['date'] ?? ''),
            'start_time' => sanitize_text_field($_POST['time'] ?? ''),
            'location' => sanitize_text_field($_POST['location'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
        );
        
        // Handle recurring bookings (only for logged-in users)
        $recurring = sanitize_text_field($_POST['recurring'] ?? 'none');
        $recurring_count = intval($_POST['recurring_count'] ?? 8);
        
        if (!$is_guest && $recurring !== 'none' && $recurring_count > 1) {
            // Create recurring booking series
            global $wpdb;
            
            // Create recurring series record
            $wpdb->insert($wpdb->prefix . 'ptp_recurring_bookings', array(
                'parent_id' => $parent->id,
                'trainer_id' => $data['trainer_id'],
                'player_id' => $data['player_id'],
                'frequency' => $recurring,
                'total_sessions' => $recurring_count,
                'day_of_week' => date('w', strtotime($data['session_date'])),
                'preferred_time' => $data['start_time'],
                'status' => 'active',
                'created_at' => current_time('mysql'),
            ));
            $recurring_id = $wpdb->insert_id;
            
            // Determine interval
            $interval = $recurring === 'weekly' ? '+1 week' : '+2 weeks';
            
            // Create all bookings in the series
            $created_bookings = array();
            $current_date = $data['session_date'];
            
            for ($i = 0; $i < $recurring_count; $i++) {
                $booking_data = $data;
                $booking_data['session_date'] = $current_date;
                $booking_data['is_recurring'] = 1;
                $booking_data['recurring_id'] = $recurring_id;
                
                $result = PTP_Booking::create($booking_data);
                
                if (!is_wp_error($result)) {
                    $created_bookings[] = $result;
                }
                
                // Move to next date
                $current_date = date('Y-m-d', strtotime($current_date . ' ' . $interval));
            }
            
            // Update recurring series
            $wpdb->update(
                $wpdb->prefix . 'ptp_recurring_bookings',
                array('sessions_created' => count($created_bookings)),
                array('id' => $recurring_id)
            );
            
            // Return first booking for payment
            if (!empty($created_bookings)) {
                $booking = PTP_Booking::get($created_bookings[0]);
                
                wp_send_json_success(array(
                    'message' => 'Recurring bookings created',
                    'booking_id' => $created_bookings[0],
                    'booking_number' => $booking->booking_number,
                    'recurring_id' => $recurring_id,
                    'total_sessions' => count($created_bookings),
                    'redirect' => home_url('/booking-confirmation/?booking=' . $booking->booking_number),
                ));
            } else {
                wp_send_json_error(array('message' => 'Failed to create recurring bookings'));
            }
        }
        
        // Single booking
        error_log('PTP Create Booking Data: ' . print_r($data, true));
        $result = PTP_Booking::create($data);
        
        if (is_wp_error($result)) {
            error_log('PTP Create Booking Error: ' . $result->get_error_message());
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        $booking = PTP_Booking::get($result);
        
        wp_send_json_success(array(
            'message' => 'Booking created successfully',
            'booking_id' => $result,
            'booking_number' => $booking->booking_number,
            'redirect' => home_url('/booking-confirmation/?booking=' . $booking->booking_number),
        ));
    }
    
    public static function confirm_session() {
        self::verify_nonce();
        self::require_login();
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $user_id = get_current_user_id();
        
        $trainer = PTP_Trainer::get_by_user_id($user_id);
        $parent = PTP_Parent::get_by_user_id($user_id);
        
        if ($trainer) {
            $result = PTP_Booking::confirm_by_trainer($booking_id, $trainer->id);
        } elseif ($parent) {
            $result = PTP_Booking::confirm_by_parent($booking_id, $parent->id);
        } else {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => 'Session confirmed'));
    }
    
    /**
     * Add a new player to parent account
     * BULLETPROOF: Rate limited, validated, with comprehensive error handling
     */
    public static function add_player() {
        try {
            self::verify_nonce();
            self::require_login();
            
            // Rate limit check
            if (!self::check_rate_limit('add_player')) {
                self::send_error('Too many requests. Please wait a moment and try again.', 'rate_limited');
            }
            
            $user_id = get_current_user_id();
            if (!$user_id) {
                self::send_error('Session expired. Please log in again.', 'session_expired');
            }
            
            global $wpdb;
            $parents_table = $wpdb->prefix . 'ptp_parents';
            $players_table = $wpdb->prefix . 'ptp_players';
            $charset = $wpdb->get_charset_collate();
            
            // Step 1: Check if parents table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$parents_table'");
            
            if ($table_exists) {
                // Table exists - check if it has the user_id column
                $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $parents_table LIKE 'user_id'");
                
                if (!$column_exists) {
                    // Old table schema - need to drop and recreate or add column
                    // Safer to add the column
                    $wpdb->query("ALTER TABLE $parents_table ADD COLUMN user_id bigint(20) UNSIGNED NOT NULL AFTER id");
                    $wpdb->query("ALTER TABLE $parents_table ADD UNIQUE KEY user_id (user_id)");
                    error_log('PTP: Added user_id column to existing ptp_parents table');
                }
            } else {
                // Create fresh table
                $wpdb->query("CREATE TABLE $parents_table (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id bigint(20) UNSIGNED NOT NULL,
                    display_name varchar(100) NOT NULL DEFAULT '',
                    phone varchar(20) DEFAULT '',
                    location varchar(255) DEFAULT '',
                    latitude decimal(10,8) DEFAULT NULL,
                    longitude decimal(11,8) DEFAULT NULL,
                    total_sessions int(11) DEFAULT 0,
                    total_spent decimal(10,2) DEFAULT 0.00,
                    notification_email tinyint(1) DEFAULT 1,
                    notification_sms tinyint(1) DEFAULT 1,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY user_id (user_id)
                ) $charset");
            }
            
            // Step 2: Ensure players table exists
            $wpdb->query("CREATE TABLE IF NOT EXISTS $players_table (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                parent_id bigint(20) UNSIGNED NOT NULL,
                name varchar(100) NOT NULL DEFAULT '',
                age int(11) DEFAULT NULL,
                skill_level varchar(20) DEFAULT 'beginner',
                position varchar(100) DEFAULT '',
                goals text,
                notes text,
                is_active tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY parent_id (parent_id)
            ) $charset");
            
            // Step 3: Get or create parent profile
            $parent = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $parents_table WHERE user_id = %d",
                $user_id
            ));
            
            if (!$parent) {
                // Create parent profile
                $user = wp_get_current_user();
                $display_name = $user->display_name ?: $user->user_login ?: 'Parent';
                $phone = get_user_meta($user_id, 'phone', true) ?: get_user_meta($user_id, 'billing_phone', true) ?: '';
                
                $inserted = $wpdb->insert(
                    $parents_table,
                    array(
                        'user_id' => $user_id,
                        'display_name' => sanitize_text_field($display_name),
                        'phone' => sanitize_text_field($phone),
                    ),
                    array('%d', '%s', '%s')
                );
                
                if ($inserted === false) {
                    error_log('PTP Add Player: Failed to create parent. Error: ' . $wpdb->last_error);
                    error_log('PTP Add Player: Query was: ' . $wpdb->last_query);
                    self::send_error('Could not create parent profile: ' . $wpdb->last_error, 'db_error');
                }
                
                $parent_id = $wpdb->insert_id;
                if (!$parent_id) {
                    // Maybe it was created by another request - try to fetch
                    $parent = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $parents_table WHERE user_id = %d",
                        $user_id
                    ));
                    if (!$parent) {
                        self::send_error('Parent profile creation failed', 'parent_error');
                    }
                    $parent_id = $parent->id;
                }
            } else {
                $parent_id = $parent->id;
            }
            
            // Step 4: Validate player data
            $name = isset($_POST['name']) ? sanitize_text_field(trim($_POST['name'])) : '';
            $age = isset($_POST['age']) ? intval($_POST['age']) : 0;
            $skill_level = isset($_POST['skill_level']) ? sanitize_text_field($_POST['skill_level']) : 'beginner';
            $position = isset($_POST['position']) ? sanitize_text_field($_POST['position']) : '';
            $goals = isset($_POST['goals']) ? sanitize_textarea_field($_POST['goals']) : '';
            
            if (empty($name) || strlen($name) < 2) {
                self::send_error('Please enter a valid player name', 'invalid_name');
            }
            
            if ($age < 4 || $age > 18) {
                self::send_error('Please enter age between 4 and 18', 'invalid_age');
            }
            
            $valid_skills = array('beginner', 'intermediate', 'advanced', 'elite');
            if (!in_array($skill_level, $valid_skills)) {
                $skill_level = 'beginner';
            }
            
            // Step 5: Check player limit
            $player_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $players_table WHERE parent_id = %d",
                $parent_id
            ));
            if ($player_count >= 20) {
                self::send_error('Maximum 20 players allowed', 'player_limit');
            }
            
            // Step 6: Create player
            $inserted = $wpdb->insert(
                $players_table,
                array(
                    'parent_id' => $parent_id,
                    'name' => $name,
                    'age' => $age,
                    'skill_level' => $skill_level,
                    'position' => $position,
                    'goals' => $goals,
                    'is_active' => 1,
                ),
                array('%d', '%s', '%d', '%s', '%s', '%s', '%d')
            );
            
            if ($inserted === false) {
                error_log('PTP Add Player: Failed to create player. Error: ' . $wpdb->last_error);
                self::send_error('Could not add player: ' . $wpdb->last_error, 'player_error');
            }
            
            $player_id = $wpdb->insert_id;
            
            wp_send_json_success(array(
                'message' => 'Player added successfully',
                'player' => array(
                    'id' => $player_id,
                    'name' => $name,
                    'age' => $age,
                    'skill_level' => $skill_level,
                ),
            ));
            
        } catch (Exception $e) {
            error_log('PTP Add Player Exception: ' . $e->getMessage());
            self::send_error('An unexpected error occurred: ' . $e->getMessage(), 'exception');
        }
    }
    
    public static function send_message() {
        self::verify_nonce();
        self::require_login();
        
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        
        if (empty($message)) {
            wp_send_json_error(array('message' => 'Message cannot be empty'));
        }
        
        $result = PTP_Messaging::send_message($conversation_id, get_current_user_id(), $message);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message_id' => $result));
    }
    
    public static function get_messages() {
        self::verify_nonce();
        self::require_login();
        
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        
        $messages = PTP_Messaging::get_messages($conversation_id);
        PTP_Messaging::mark_as_read($conversation_id, get_current_user_id());
        
        wp_send_json_success(array('messages' => array_reverse($messages)));
    }
    
    /**
     * Get new messages since last ID (for polling)
     */
    public static function get_new_messages() {
        self::verify_nonce();
        self::require_login();
        
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $last_id = intval($_POST['last_id'] ?? 0);
        
        global $wpdb;
        $table = $wpdb->prefix . 'ptp_messages';
        
        // Get messages after last ID
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} 
             WHERE conversation_id = %d AND id > %d 
             ORDER BY created_at ASC",
            $conversation_id, $last_id
        ));
        
        // Mark as read
        PTP_Messaging::mark_as_read($conversation_id, get_current_user_id());
        
        wp_send_json_success(array('messages' => $messages));
    }
    
    /**
     * Start a new conversation
     */
    public static function start_conversation() {
        self::verify_nonce();
        self::require_login();
        
        $trainer_id = intval($_POST['trainer_id'] ?? 0);
        $parent_id = intval($_POST['parent_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        
        if (!$trainer_id) {
            wp_send_json_error(array('message' => 'Trainer ID required'));
            return;
        }
        
        // Verify the trainer exists
        $trainer = PTP_Trainer::get($trainer_id);
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer not found'));
            return;
        }
        
        // Get or create conversation
        $user_id = get_current_user_id();
        $is_trainer = PTP_User::is_trainer();
        
        if ($is_trainer) {
            // Trainer starting conversation with parent
            if (!$parent_id) {
                wp_send_json_error(array('message' => 'Parent ID required'));
                return;
            }
            $conversation = PTP_Messaging::get_or_create_conversation($trainer_id, $parent_id);
        } else {
            // Parent (or user) starting conversation with trainer
            $parent = PTP_Parent::get_by_user_id($user_id);
            
            // Auto-create parent profile if needed
            if (!$parent) {
                $current_user = wp_get_current_user();
                
                // Use the proper create method
                $result = PTP_Parent::create($user_id, array(
                    'display_name' => $current_user->display_name ?: $current_user->user_login,
                    'phone' => '',
                ));
                
                if (!is_wp_error($result)) {
                    // Add parent role if not already present
                    if (!in_array('ptp_parent', (array) $current_user->roles)) {
                        $current_user->add_role('ptp_parent');
                    }
                    $parent = PTP_Parent::get_by_user_id($user_id);
                }
            }
            
            if (!$parent) {
                wp_send_json_error(array('message' => 'Could not create your profile. Please try again.'));
                return;
            }
            
            $conversation = PTP_Messaging::get_or_create_conversation($trainer_id, $parent->id);
        }
        
        if (!$conversation) {
            wp_send_json_error(array('message' => 'Failed to create conversation'));
            return;
        }
        
        // Send initial message if provided
        if ($message) {
            PTP_Messaging::send_message($conversation->id, $user_id, $message);
            
            // Send email notification to trainer
            if (class_exists('PTP_Email')) {
                $current_user = wp_get_current_user();
                PTP_Email::send_new_message_notification($trainer->user_id, $current_user->display_name, $message);
            }
        }
        
        wp_send_json_success(array(
            'conversation_id' => $conversation->id,
            'message' => 'Message sent! The trainer will respond via the messaging system.',
            'redirect' => home_url('/messages/?conversation=' . $conversation->id)
        ));
    }
    
    public static function get_conversations() {
        self::verify_nonce();
        self::require_login();
        
        $conversations = PTP_Messaging::get_conversations_for_user(get_current_user_id());
        
        wp_send_json_success(array('conversations' => $conversations));
    }
    
    public static function update_availability() {
        self::verify_nonce();
        self::require_login();
        
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer profile not found'));
            return;
        }
        
        $availability = $_POST['availability'] ?? array();
        
        if (empty($availability) || !is_array($availability)) {
            wp_send_json_error(array('message' => 'No availability data provided'));
            return;
        }
        
        global $wpdb;
        
        // Ensure table exists
        if (class_exists('PTP_Availability')) {
            PTP_Availability::ensure_table();
        }
        
        $table = $wpdb->prefix . 'ptp_availability';
        
        // Clear existing availability
        $wpdb->delete($table, array('trainer_id' => $trainer->id));
        
        // Insert new availability - handle both formats:
        // Format 1 (from onboarding): availability[day_num][field] - day is array key
        // Format 2 (alternative): availability[index][day], availability[index][active]
        foreach ($availability as $key => $day_data) {
            // Determine day number
            $day = null;
            
            // Check if key is the day number (Format 1)
            if (is_numeric($key) && isset($day_data['start'])) {
                $day = intval($key);
                $is_active = !empty($day_data['enabled']) && ($day_data['enabled'] === '1' || $day_data['enabled'] === 1);
                $start_time = sanitize_text_field($day_data['start'] ?? '16:00');
                $end_time = sanitize_text_field($day_data['end'] ?? '20:00');
            }
            // Check for Format 2
            elseif (isset($day_data['day'])) {
                $day = intval($day_data['day']);
                $is_active = !empty($day_data['active']) && ($day_data['active'] === '1' || $day_data['active'] === 1);
                $start_time = sanitize_text_field($day_data['start'] ?? '16:00');
                $end_time = sanitize_text_field($day_data['end'] ?? '20:00');
            }
            
            if ($day === null || $day < 0 || $day > 6) continue;
            
            // Normalize time format - ensure HH:MM:SS
            if (preg_match('/^\d{1,2}:\d{2}$/', $start_time)) {
                // Pad single digit hours
                if (strlen($start_time) === 4) $start_time = '0' . $start_time;
                $start_time .= ':00';
            }
            if (preg_match('/^\d{1,2}:\d{2}$/', $end_time)) {
                if (strlen($end_time) === 4) $end_time = '0' . $end_time;
                $end_time .= ':00';
            }
            
            // Validate final format
            if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $start_time)) {
                $start_time = '16:00:00';
            }
            if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $end_time)) {
                $end_time = '20:00:00';
            }
            
            $wpdb->insert($table, array(
                'trainer_id' => $trainer->id,
                'day_of_week' => $day,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'is_active' => $is_active ? 1 : 0,
            ));
        }
        
        // Clear cache
        wp_cache_delete('ptp_availability_' . $trainer->id . '_' . date('Y') . '_' . date('n'), 'ptp');
        
        wp_send_json_success(array('message' => 'Availability updated'));
    }
    
    public static function update_profile() {
        self::verify_nonce();
        self::require_login();
        
        $user_id = get_current_user_id();
        $trainer = PTP_Trainer::get_by_user_id($user_id);
        $parent = PTP_Parent::get_by_user_id($user_id);
        
        // Sanitize all inputs before passing to model
        $sanitized_data = array();
        $text_fields = array('first_name', 'last_name', 'display_name', 'phone', 'headline', 'location', 'college', 'team', 'position', 'instagram', 'playing_level');
        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                $sanitized_data[$field] = sanitize_text_field($_POST[$field]);
            }
        }
        if (isset($_POST['email'])) {
            $sanitized_data['email'] = sanitize_email($_POST['email']);
        }
        if (isset($_POST['bio'])) {
            $sanitized_data['bio'] = sanitize_textarea_field($_POST['bio']);
        }
        if (isset($_POST['hourly_rate'])) {
            $sanitized_data['hourly_rate'] = floatval($_POST['hourly_rate']);
        }
        if (isset($_POST['travel_radius'])) {
            $sanitized_data['travel_radius'] = absint($_POST['travel_radius']);
        }
        if (isset($_POST['specialties']) && is_array($_POST['specialties'])) {
            $sanitized_data['specialties'] = array_map('sanitize_text_field', $_POST['specialties']);
        }
        
        if ($trainer) {
            PTP_Trainer::update($trainer->id, $sanitized_data);
        } elseif ($parent) {
            PTP_Parent::update($parent->id, $sanitized_data);
        }
        
        // Update WP user
        $user_data = array('ID' => $user_id);
        if (!empty($sanitized_data['first_name'])) $user_data['first_name'] = $sanitized_data['first_name'];
        if (!empty($sanitized_data['last_name'])) $user_data['last_name'] = $sanitized_data['last_name'];
        if (!empty($sanitized_data['display_name'])) $user_data['display_name'] = $sanitized_data['display_name'];
        
        wp_update_user($user_data);
        
        wp_send_json_success(array('message' => 'Profile updated'));
    }
    
    /**
     * Update trainer profile (headline, bio, rate, specialties, etc.)
     */
    public static function update_trainer_profile() {
        self::verify_nonce();
        self::require_login();
        
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer profile not found'));
        }
        
        // Handle photo upload if present
        if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            
            $allowed_types = array('image/jpeg', 'image/png', 'image/webp');
            if (!in_array($file['type'], $allowed_types)) {
                wp_send_json_error(array('message' => 'Invalid file type. Please upload JPG, PNG, or WebP.'));
            }
            
            if ($file['size'] > 2 * 1024 * 1024) {
                wp_send_json_error(array('message' => 'Photo too large. Maximum size is 2MB.'));
            }
            
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $upload = wp_handle_upload($file, array('test_form' => false));
            
            if (isset($upload['error'])) {
                wp_send_json_error(array('message' => 'Photo upload failed: ' . $upload['error']));
            }
            
            $attachment = array(
                'post_mime_type' => $upload['type'],
                'post_title' => sanitize_file_name($file['name']),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $attach_id = wp_insert_attachment($attachment, $upload['file']);
            $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);
            
            PTP_Trainer::update($trainer->id, array('photo_url' => $upload['url']));
        }
        
        $data = array(
            'headline' => sanitize_text_field($_POST['headline'] ?? ''),
            'bio' => sanitize_textarea_field($_POST['bio'] ?? ''),
            'college' => sanitize_text_field($_POST['college'] ?? ''),
            'team' => sanitize_text_field($_POST['team'] ?? ''),
            'hourly_rate' => floatval($_POST['hourly_rate'] ?? 0),
            'specialties' => is_array($_POST['specialties'] ?? null) 
                ? implode(',', array_map('sanitize_text_field', $_POST['specialties'])) 
                : sanitize_text_field($_POST['specialties'] ?? ''),
        );
        
        // Optional fields
        if (isset($_POST['position'])) {
            $data['position'] = sanitize_text_field($_POST['position']);
        }
        if (isset($_POST['travel_radius'])) {
            $data['travel_radius'] = intval($_POST['travel_radius']);
        }
        if (isset($_POST['location'])) {
            $data['location'] = sanitize_text_field($_POST['location']);
        }
        if (isset($_POST['intro_video_url'])) {
            $video_url = esc_url_raw($_POST['intro_video_url']);
            // Validate it's a YouTube or Vimeo URL
            if (!empty($video_url) && (
                strpos($video_url, 'youtube.com') !== false || 
                strpos($video_url, 'youtu.be') !== false ||
                strpos($video_url, 'vimeo.com') !== false
            )) {
                $data['intro_video_url'] = $video_url;
            } else if (empty($video_url)) {
                $data['intro_video_url'] = '';
            }
        }
        if (isset($_POST['instagram'])) {
            // Clean Instagram handle
            $instagram = sanitize_text_field($_POST['instagram']);
            $instagram = preg_replace('/^@/', '', $instagram);
            $instagram = preg_replace('/^(https?:\/\/)?(www\.)?instagram\.com\//', '', $instagram);
            $instagram = preg_replace('/\/$/', '', $instagram);
            $data['instagram'] = $instagram;
        }
        if (isset($_POST['facebook'])) {
            // Clean Facebook handle
            $facebook = sanitize_text_field($_POST['facebook']);
            $facebook = preg_replace('/^(https?:\/\/)?(www\.)?facebook\.com\//', '', $facebook);
            $facebook = preg_replace('/\/$/', '', $facebook);
            $data['facebook'] = $facebook;
        }
        
        // Group session settings
        if (isset($_POST['accepts_groups'])) {
            $data['accepts_groups'] = intval($_POST['accepts_groups']) ? 1 : 0;
        }
        if (isset($_POST['group_max_size'])) {
            $data['group_max_size'] = min(5, max(2, intval($_POST['group_max_size'])));
        }
        
        PTP_Trainer::update($trainer->id, $data);
        
        wp_send_json_success(array('message' => 'Profile updated successfully'));
    }
    
    /**
     * Upload trainer profile photo
     */
    public static function upload_trainer_photo() {
        self::verify_nonce();
        self::require_login();
        
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer profile not found'));
        }
        
        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'No file uploaded or upload error'));
        }
        
        $file = $_FILES['photo'];
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => 'Invalid file type. Please upload JPG, PNG, or WebP.'));
        }
        
        // Validate file size (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'File too large. Maximum size is 2MB.'));
        }
        
        // Include WordPress file handling functions
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        // Upload to WordPress media library
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }
        
        // Create attachment
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        
        // Generate metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        // Update trainer profile with new photo URL
        PTP_Trainer::update($trainer->id, array('photo_url' => $upload['url']));
        
        wp_send_json_success(array(
            'message' => 'Photo uploaded successfully',
            'photo_url' => $upload['url']
        ));
    }
    
    /**
     * Complete trainer onboarding (profile + availability in one step)
     */
    public static function complete_onboarding() {
        self::verify_nonce();
        self::require_login();
        
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer profile not found'));
        }
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        // Handle main photo upload if present
        if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            
            $allowed_types = array('image/jpeg', 'image/png', 'image/webp');
            if (!in_array($file['type'], $allowed_types)) {
                wp_send_json_error(array('message' => 'Invalid file type. Please upload JPG, PNG, or WebP.'));
            }
            
            if ($file['size'] > 2 * 1024 * 1024) {
                wp_send_json_error(array('message' => 'Photo too large. Maximum size is 2MB.'));
            }
            
            $upload = wp_handle_upload($file, array('test_form' => false));
            
            if (isset($upload['error'])) {
                wp_send_json_error(array('message' => 'Photo upload failed: ' . $upload['error']));
            }
            
            $attachment = array(
                'post_mime_type' => $upload['type'],
                'post_title' => sanitize_file_name($file['name']),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $attach_id = wp_insert_attachment($attachment, $upload['file']);
            $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);
            
            PTP_Trainer::update($trainer->id, array('photo_url' => $upload['url']));
        }
        
        // Handle gallery uploads
        $gallery_urls = array();
        
        // Keep existing gallery images
        if (!empty($_POST['existing_gallery']) && is_array($_POST['existing_gallery'])) {
            foreach ($_POST['existing_gallery'] as $url) {
                $gallery_urls[] = esc_url_raw($url);
            }
        }
        
        // Upload new gallery images
        if (!empty($_FILES['gallery']) && is_array($_FILES['gallery']['name'])) {
            $allowed_types = array('image/jpeg', 'image/png', 'image/webp');
            
            for ($i = 0; $i < count($_FILES['gallery']['name']); $i++) {
                if ($_FILES['gallery']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if ($_FILES['gallery']['size'][$i] > 2 * 1024 * 1024) continue;
                if (!in_array($_FILES['gallery']['type'][$i], $allowed_types)) continue;
                
                $file = array(
                    'name' => $_FILES['gallery']['name'][$i],
                    'type' => $_FILES['gallery']['type'][$i],
                    'tmp_name' => $_FILES['gallery']['tmp_name'][$i],
                    'error' => $_FILES['gallery']['error'][$i],
                    'size' => $_FILES['gallery']['size'][$i],
                );
                
                $upload = wp_handle_upload($file, array('test_form' => false));
                if (!isset($upload['error'])) {
                    $gallery_urls[] = $upload['url'];
                }
            }
        }
        
        // Update trainer profile data
        $profile_data = array(
            'headline' => sanitize_text_field($_POST['headline'] ?? ''),
            'bio' => sanitize_textarea_field($_POST['bio'] ?? ''),
            'college' => sanitize_text_field($_POST['college'] ?? ''),
            'team' => sanitize_text_field($_POST['team'] ?? ''),
            'location' => sanitize_text_field($_POST['location'] ?? ''),
            'travel_radius' => intval($_POST['travel_radius'] ?? 15),
            'hourly_rate' => floatval($_POST['hourly_rate'] ?? 0),
            'position' => sanitize_text_field($_POST['position'] ?? ''),
            'specialties' => is_array($_POST['specialties'] ?? null) 
                ? implode(',', array_map('sanitize_text_field', $_POST['specialties'])) 
                : '',
            'gallery' => !empty($gallery_urls) ? json_encode($gallery_urls) : null,
        );
        
        // Handle intro video URL
        if (isset($_POST['intro_video_url'])) {
            $video_url = esc_url_raw($_POST['intro_video_url']);
            // Validate it's a YouTube or Vimeo URL
            if (empty($video_url) || 
                strpos($video_url, 'youtube.com') !== false || 
                strpos($video_url, 'youtu.be') !== false || 
                strpos($video_url, 'vimeo.com') !== false) {
                $profile_data['intro_video_url'] = $video_url;
            }
        }
        
        // Handle training locations - now with structured data (name, address, lat, lng)
        if (isset($_POST['training_locations']) && is_array($_POST['training_locations'])) {
            $locations = array();
            foreach ($_POST['training_locations'] as $loc) {
                if (is_array($loc)) {
                    // New format with structured data
                    $name = isset($loc['name']) ? sanitize_text_field($loc['name']) : '';
                    if (!empty($name)) {
                        $locations[] = array(
                            'name' => $name,
                            'address' => isset($loc['address']) ? sanitize_text_field($loc['address']) : '',
                            'lat' => isset($loc['lat']) && $loc['lat'] !== '' ? floatval($loc['lat']) : null,
                            'lng' => isset($loc['lng']) && $loc['lng'] !== '' ? floatval($loc['lng']) : null,
                        );
                    }
                } else {
                    // Legacy format - plain string
                    $name = sanitize_text_field($loc);
                    if (!empty($name)) {
                        $locations[] = array(
                            'name' => $name,
                            'address' => '',
                            'lat' => null,
                            'lng' => null,
                        );
                    }
                }
            }
            $profile_data['training_locations'] = !empty($locations) ? json_encode($locations) : null;
        }
        
        // Handle lat/lng if provided
        if (!empty($_POST['latitude'])) {
            $profile_data['latitude'] = floatval($_POST['latitude']);
        }
        if (!empty($_POST['longitude'])) {
            $profile_data['longitude'] = floatval($_POST['longitude']);
        }
        
        // Handle custom SEO slug if provided
        if (!empty($_POST['slug'])) {
            $profile_data['slug'] = sanitize_text_field($_POST['slug']);
        }
        
        // Handle SafeSport document upload
        if (!empty($_FILES['safesport_doc']) && $_FILES['safesport_doc']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['safesport_doc'];
            $allowed_types = array('application/pdf', 'image/jpeg', 'image/png');
            
            if (in_array($file['type'], $allowed_types) && $file['size'] <= 5 * 1024 * 1024) {
                $upload = wp_handle_upload($file, array('test_form' => false));
                if (!isset($upload['error'])) {
                    $profile_data['safesport_doc_url'] = $upload['url'];
                    $profile_data['safesport_verified'] = 0; // Reset verification on new upload
                }
            }
        }
        
        // Handle background check document upload
        if (!empty($_FILES['background_doc']) && $_FILES['background_doc']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['background_doc'];
            $allowed_types = array('application/pdf', 'image/jpeg', 'image/png');
            
            if (in_array($file['type'], $allowed_types) && $file['size'] <= 5 * 1024 * 1024) {
                $upload = wp_handle_upload($file, array('test_form' => false));
                if (!isset($upload['error'])) {
                    $profile_data['background_doc_url'] = $upload['url'];
                    $profile_data['background_verified'] = 0; // Reset verification on new upload
                }
            }
        }
        
        // Handle tax information
        if (!empty($_POST['legal_name'])) {
            $profile_data['legal_name'] = sanitize_text_field($_POST['legal_name']);
        }
        if (!empty($_POST['tax_id_type'])) {
            $profile_data['tax_id_type'] = in_array($_POST['tax_id_type'], array('ssn', 'ein')) ? $_POST['tax_id_type'] : 'ssn';
        }
        if (!empty($_POST['tax_id'])) {
            // Only store last 4 digits - encrypt or hash full ID in production
            $tax_id = preg_replace('/\D/', '', $_POST['tax_id']);
            if (strlen($tax_id) >= 4) {
                $profile_data['tax_id_last4'] = substr($tax_id, -4);
            }
        }
        if (!empty($_POST['tax_address_line1'])) {
            $profile_data['tax_address_line1'] = sanitize_text_field($_POST['tax_address_line1']);
        }
        if (isset($_POST['tax_address_line2'])) {
            $profile_data['tax_address_line2'] = sanitize_text_field($_POST['tax_address_line2']);
        }
        if (!empty($_POST['tax_city'])) {
            $profile_data['tax_city'] = sanitize_text_field($_POST['tax_city']);
        }
        if (!empty($_POST['tax_state'])) {
            $profile_data['tax_state'] = sanitize_text_field($_POST['tax_state']);
        }
        if (!empty($_POST['tax_zip'])) {
            $profile_data['tax_zip'] = sanitize_text_field($_POST['tax_zip']);
        }
        
        // W-9 certification
        if (!empty($_POST['w9_certification']) && !empty($_POST['legal_name']) && !empty($_POST['tax_id'])) {
            $profile_data['w9_submitted'] = 1;
            $profile_data['w9_submitted_at'] = current_time('mysql');
        }
        
        // Contractor agreement
        if (!empty($_POST['contractor_agreement'])) {
            $profile_data['contractor_agreement_signed'] = 1;
            $profile_data['contractor_agreement_signed_at'] = current_time('mysql');
            $profile_data['contractor_agreement_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        PTP_Trainer::update($trainer->id, $profile_data);
        
        // Send contractor agreement email if just signed
        if (!empty($_POST['contractor_agreement']) && class_exists('PTP_Email')) {
            // Refresh trainer data to get signed_at timestamp
            $updated_trainer = PTP_Trainer::get($trainer->id);
            if ($updated_trainer && $updated_trainer->contractor_agreement_signed) {
                PTP_Email::send_contractor_agreement($trainer->id);
            }
        }
        
        // Update availability
        if (!empty($_POST['availability']) && is_array($_POST['availability'])) {
            global $wpdb;
            
            // Ensure table exists
            if (class_exists('PTP_Availability')) {
                PTP_Availability::ensure_table();
            }
            
            $table = $wpdb->prefix . 'ptp_availability';
            
            // Clear existing availability
            $wpdb->delete($table, array('trainer_id' => $trainer->id));
            
            foreach ($_POST['availability'] as $day => $slot) {
                $day = intval($day);
                if ($day < 0 || $day > 6) continue;
                
                $is_enabled = !empty($slot['enabled']) && ($slot['enabled'] === '1' || $slot['enabled'] === 1);
                
                // Normalize time format - ensure HH:MM:SS
                $start_time = sanitize_text_field($slot['start'] ?? '16:00');
                $end_time = sanitize_text_field($slot['end'] ?? '20:00');
                
                // Add seconds if not present
                if (preg_match('/^\d{2}:\d{2}$/', $start_time)) {
                    $start_time .= ':00';
                }
                if (preg_match('/^\d{2}:\d{2}$/', $end_time)) {
                    $end_time .= ':00';
                }
                
                // Validate format
                if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $start_time)) {
                    $start_time = '16:00:00';
                }
                if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $end_time)) {
                    $end_time = '20:00:00';
                }
                
                $result = $wpdb->insert($table, array(
                    'trainer_id' => $trainer->id,
                    'day_of_week' => $day,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'is_active' => $is_enabled ? 1 : 0,
                ));
                
                if ($result === false) {
                    error_log('PTP Onboarding: Failed to save availability for day ' . $day . ': ' . $wpdb->last_error);
                }
            }
            
            // Clear cache for this trainer's availability
            wp_cache_delete('ptp_availability_' . $trainer->id . '_' . date('Y') . '_' . date('n'), 'ptp');
        }
        
        // Mark onboarding as complete
        PTP_Trainer::complete_onboarding($trainer->id);
        
        // Send onboarding complete email
        if (class_exists('PTP_Email')) {
            PTP_Email::send_onboarding_complete($trainer->id);
        }
        
        wp_send_json_success(array(
            'message' => 'Profile completed successfully!',
            'redirect' => home_url('/trainer-dashboard/?welcome=1')
        ));
    }
    
    /**
     * Update password
     */
    public static function update_password() {
        self::verify_nonce();
        self::require_login();
        
        $user = wp_get_current_user();
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            wp_send_json_error(array('message' => 'Please fill in all password fields'));
        }
        
        // Verify current password
        if (!wp_check_password($current_password, $user->user_pass, $user->ID)) {
            wp_send_json_error(array('message' => 'Current password is incorrect'));
        }
        
        // Check passwords match
        if ($new_password !== $confirm_password) {
            wp_send_json_error(array('message' => 'New passwords do not match'));
        }
        
        // Check password strength
        if (strlen($new_password) < 8) {
            wp_send_json_error(array('message' => 'Password must be at least 8 characters'));
        }
        
        // Update password
        wp_set_password($new_password, $user->ID);
        
        // Re-authenticate user so they stay logged in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        wp_send_json_success(array('message' => 'Password updated successfully'));
    }
    
    /**
     * Update notification preferences
     */
    public static function update_notifications() {
        self::verify_nonce();
        self::require_login();
        
        $user_id = get_current_user_id();
        
        $preferences = array(
            'notify_booking' => !empty($_POST['notify_booking']),
            'notify_messages' => !empty($_POST['notify_messages']),
            'notify_reminders' => !empty($_POST['notify_reminders']),
            'notify_marketing' => !empty($_POST['notify_marketing']),
        );
        
        update_user_meta($user_id, 'ptp_notification_preferences', $preferences);
        
        wp_send_json_success(array('message' => 'Notification preferences saved'));
    }
    
    public static function submit_application() {
        self::verify_nonce();
        
        global $wpdb;
        
        // Auto-migrate: ensure all required columns exist
        $table = $wpdb->prefix . 'ptp_applications';
        $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
        
        $required_columns = array(
            'user_id' => "ALTER TABLE {$table} ADD COLUMN user_id bigint(20) UNSIGNED DEFAULT NULL AFTER id",
            'name' => "ALTER TABLE {$table} ADD COLUMN name varchar(100) NOT NULL DEFAULT '' AFTER email",
            'phone' => "ALTER TABLE {$table} ADD COLUMN phone varchar(20) DEFAULT '' AFTER name",
            'location' => "ALTER TABLE {$table} ADD COLUMN location varchar(255) DEFAULT '' AFTER phone",
            'college' => "ALTER TABLE {$table} ADD COLUMN college varchar(255) DEFAULT '' AFTER location",
            'team' => "ALTER TABLE {$table} ADD COLUMN team varchar(255) DEFAULT '' AFTER college",
            'playing_level' => "ALTER TABLE {$table} ADD COLUMN playing_level varchar(50) DEFAULT '' AFTER team",
            'position' => "ALTER TABLE {$table} ADD COLUMN position varchar(100) DEFAULT '' AFTER playing_level",
            'specialties' => "ALTER TABLE {$table} ADD COLUMN specialties text AFTER position",
            'instagram' => "ALTER TABLE {$table} ADD COLUMN instagram varchar(100) DEFAULT '' AFTER specialties",
            'facebook' => "ALTER TABLE {$table} ADD COLUMN facebook varchar(100) DEFAULT '' AFTER instagram",
            'headline' => "ALTER TABLE {$table} ADD COLUMN headline varchar(255) DEFAULT '' AFTER facebook",
            'bio' => "ALTER TABLE {$table} ADD COLUMN bio text AFTER headline",
            'hourly_rate' => "ALTER TABLE {$table} ADD COLUMN hourly_rate decimal(10,2) DEFAULT 0 AFTER bio",
            'travel_radius' => "ALTER TABLE {$table} ADD COLUMN travel_radius int(11) DEFAULT 15 AFTER hourly_rate",
        );
        
        foreach ($required_columns as $col => $sql) {
            if (!in_array($col, $existing_columns)) {
                $wpdb->query($sql);
            }
        }
        
        // Build name from first/last if provided separately
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $full_name = trim($first_name . ' ' . $last_name);
        if (empty($full_name)) {
            $full_name = sanitize_text_field($_POST['name'] ?? '');
        }
        
        // Build location from city/state if provided separately
        $city = sanitize_text_field($_POST['city'] ?? '');
        $state = sanitize_text_field($_POST['state'] ?? '');
        $location = trim($city . ', ' . $state, ', ');
        if (empty($location)) {
            $location = sanitize_text_field($_POST['location'] ?? '');
        }
        
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        // Validate required fields
        if (empty($full_name) || empty($email)) {
            wp_send_json_error(array('message' => 'Name and email are required'));
        }
        
        // Validate password
        if (empty($password)) {
            wp_send_json_error(array('message' => 'Password is required'));
        }
        
        if (strlen($password) < 8) {
            wp_send_json_error(array('message' => 'Password must be at least 8 characters'));
        }
        
        if ($password !== $password_confirm) {
            wp_send_json_error(array('message' => 'Passwords do not match'));
        }
        
        // Check if email already exists
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'An account with this email already exists. Please log in or use a different email.'));
        }
        
        // Create WordPress user account
        $user_id = wp_create_user($email, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => 'Could not create account: ' . $user_id->get_error_message()));
        }
        
        // Update user details
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $full_name,
        ));
        
        // Add pending trainer role
        $user = get_user_by('ID', $user_id);
        $user->add_role('ptp_trainer');
        
        $data = array(
            'user_id' => $user_id,
            'name' => $full_name,
            'email' => $email,
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'location' => $location,
            'college' => sanitize_text_field($_POST['college'] ?? ''),
            'team' => sanitize_text_field($_POST['team'] ?? ''),
            'playing_level' => sanitize_text_field($_POST['playing_level'] ?? ''),
            'position' => sanitize_text_field($_POST['position'] ?? ''),
            'specialties' => is_array($_POST['specialties'] ?? null) ? implode(',', array_map('sanitize_text_field', $_POST['specialties'])) : '',
            'instagram' => sanitize_text_field($_POST['instagram'] ?? ''),
            'headline' => sanitize_text_field($_POST['headline'] ?? ''),
            'bio' => sanitize_textarea_field($_POST['bio'] ?? ''),
            'hourly_rate' => floatval($_POST['hourly_rate'] ?? 0),
            'travel_radius' => intval($_POST['travel_radius'] ?? 15),
            'status' => 'pending',
        );
        
        $result = $wpdb->insert($wpdb->prefix . 'ptp_applications', $data);
        
        if ($result === false) {
            error_log('PTP Application Insert Error: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
        }
        
        // Send confirmation email to applicant
        if (class_exists('PTP_Email')) {
            PTP_Email::send_application_received($data['email'], $data['name']);
        }
        
        // Send notification to admin
        wp_mail(
            get_option('admin_email'),
            'New Trainer Application - ' . $data['name'],
            sprintf("New trainer application received:\n\nName: %s\nEmail: %s\nPhone: %s\nLocation: %s\nTeam/College: %s\nPlaying Level: %s\n\nReview in admin dashboard: %s",
                $data['name'], $data['email'], $data['phone'], $data['location'], 
                $data['team'] ?: $data['college'], $data['playing_level'],
                admin_url('admin.php?page=ptp-applications'))
        );
        
        wp_send_json_success(array('message' => 'Application submitted successfully! Your account has been created - you can log in once your application is approved.'));
    }
    
    public static function submit_review() {
        self::verify_nonce();
        self::require_login();
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 0);
        $review = sanitize_textarea_field($_POST['review'] ?? '');
        
        if (!$rating || $rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => 'Please select a rating'));
        }
        
        $result = PTP_Reviews::create($booking_id, $rating, $review);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => 'Review submitted'));
    }
    
    public static function cancel_booking() {
        self::verify_nonce();
        self::require_login();
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        $booking = PTP_Booking::get($booking_id);
        if (!$booking) {
            wp_send_json_error(array('message' => 'Booking not found'));
        }
        
        $user_id = get_current_user_id();
        $trainer = PTP_Trainer::get_by_user_id($user_id);
        $parent = PTP_Parent::get_by_user_id($user_id);
        
        // Verify ownership
        if ((!$trainer || $trainer->id != $booking->trainer_id) && (!$parent || $parent->id != $booking->parent_id)) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'ptp_bookings',
            array(
                'status' => 'cancelled',
                'cancelled_by' => $user_id,
                'cancellation_reason' => $reason,
                'cancelled_at' => current_time('mysql'),
            ),
            array('id' => $booking_id)
        );
        
        PTP_Notifications::booking_cancelled($booking_id, $user_id);
        
        wp_send_json_success(array('message' => 'Booking cancelled'));
    }
    
    public static function update_player() {
        self::verify_nonce();
        self::require_login();
        
        $player_id = intval($_POST['player_id'] ?? 0);
        $parent = PTP_Parent::get_by_user_id(get_current_user_id());
        
        if (!$parent || !PTP_Player::belongs_to_parent($player_id, $parent->id)) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        PTP_Player::update($player_id, $_POST);
        
        wp_send_json_success(array('message' => 'Player updated'));
    }
    
    public static function delete_player() {
        self::verify_nonce();
        self::require_login();
        
        $player_id = intval($_POST['player_id'] ?? 0);
        $parent = PTP_Parent::get_by_user_id(get_current_user_id());
        
        if (!$parent || !PTP_Player::belongs_to_parent($player_id, $parent->id)) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        PTP_Player::delete($player_id);
        
        wp_send_json_success(array('message' => 'Player removed'));
    }
    
    /**
     * Create Stripe payment intent
     * Supports both existing booking and pre-booking (trainer_id + package)
     */
    public static function create_payment_intent() {
        self::verify_nonce();
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $trainer_id = intval($_POST['trainer_id'] ?? 0);
        $package = intval($_POST['package'] ?? 1);
        
        // Check if Stripe is configured
        if (!class_exists('PTP_Stripe') || !PTP_Stripe::is_enabled()) {
            wp_send_json_error(array('message' => 'Payment system not configured'));
        }
        
        // If we have a booking_id, use the existing flow
        if ($booking_id) {
            global $wpdb;
            $booking = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}ptp_bookings WHERE id = %d
            ", $booking_id));
            
            if (!$booking) {
                wp_send_json_error(array('message' => 'Booking not found'));
            }
            
            // Verify ownership (if logged in)
            if (is_user_logged_in()) {
                $parent = PTP_Parent::get_by_user_id(get_current_user_id());
                if (!$parent || $parent->id != $booking->parent_id) {
                    wp_send_json_error(array('message' => 'Unauthorized'));
                }
            }
            
            $amount = $booking->total_amount;
            $metadata = array(
                'booking_id' => $booking_id,
                'trainer_id' => $booking->trainer_id,
                'parent_id' => $booking->parent_id,
            );
        } 
        // Pre-booking flow: calculate amount from trainer rate and package
        elseif ($trainer_id) {
            $trainer = PTP_Trainer::get($trainer_id);
            if (!$trainer) {
                wp_send_json_error(array('message' => 'Trainer not found'));
            }
            
            $rate = floatval($trainer->hourly_rate ?: 70);
            
            // Calculate package price
            switch ($package) {
                case 4:
                    $amount = round($rate * 4 * 0.95, 2);
                    break;
                case 8:
                    $amount = round($rate * 8 * 0.90, 2);
                    break;
                case 12:
                    $amount = round($rate * 12 * 0.85, 2);
                    break;
                default:
                    $amount = $rate;
            }
            
            $metadata = array(
                'trainer_id' => $trainer_id,
                'package' => $package,
                'type' => 'pre_booking',
            );
        } else {
            wp_send_json_error(array('message' => 'Invalid request'));
        }
        
        // Create payment intent
        $intent = PTP_Stripe::create_payment_intent($amount, $metadata);
        
        if (is_wp_error($intent)) {
            wp_send_json_error(array('message' => $intent->get_error_message()));
        }
        
        wp_send_json_success(array(
            'client_secret' => $intent['client_secret'],
            'amount' => $amount,
        ));
    }
    
    /**
     * Confirm booking after payment
     */
    public static function confirm_booking_payment() {
        self::verify_nonce();
        // Don't require login - guest bookings need this too
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $payment_intent_id = sanitize_text_field($_POST['payment_intent_id'] ?? '');
        
        if (!$booking_id || !$payment_intent_id) {
            wp_send_json_error(array('message' => 'Invalid request'));
        }
        
        global $wpdb;
        
        // Update booking
        $wpdb->update(
            $wpdb->prefix . 'ptp_bookings',
            array(
                'payment_intent_id' => $payment_intent_id,
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ),
            array('id' => $booking_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        // Send notifications
        if (class_exists('PTP_Email')) {
            PTP_Email::send_booking_confirmation($booking_id);
            PTP_Email::send_trainer_new_booking($booking_id);
        }
        
        if (class_exists('PTP_SMS') && PTP_SMS::is_enabled()) {
            PTP_SMS::send_booking_confirmation($booking_id);
            PTP_SMS::send_trainer_new_booking($booking_id);
        }
        
        wp_send_json_success(array('message' => 'Payment confirmed'));
    }
    
    /**
     * Create Stripe Connect account for trainer
     */
    public static function create_stripe_connect_account() {
        self::verify_nonce();
        self::require_login();
        
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer profile not found'));
        }
        
        // Ensure PTP_Stripe is loaded and initialized
        if (!class_exists('PTP_Stripe')) {
            wp_send_json_error(array('message' => 'Payment system not available. Please contact support.'));
        }
        
        // Check configuration status
        $config = PTP_Stripe::get_config_status();
        
        if (!$config['has_secret_key']) {
            $mode = $config['test_mode'] ? 'Test' : 'Live';
            wp_send_json_error(array(
                'message' => "Stripe {$mode} Secret Key is not configured. Please add it in WordPress Admin → PTP Settings → Stripe."
            ));
        }
        
        if (!$config['connect_enabled']) {
            wp_send_json_error(array(
                'message' => 'Stripe Connect is not enabled. Please enable it in WordPress Admin → PTP Settings → Stripe → Enable Stripe Connect.'
            ));
        }
        
        // Try to start connect onboarding
        if (!method_exists('PTP_Stripe', 'start_connect_onboarding')) {
            wp_send_json_error(array('message' => 'Payment system method not available. Please update the plugin.'));
        }
        
        $result = PTP_Stripe::start_connect_onboarding($trainer->id);
        
        if (is_wp_error($result)) {
            $error_msg = $result->get_error_message();
            $error_code = $result->get_error_code();
            
            // Log for debugging
            error_log('PTP Stripe Connect Error for trainer ' . $trainer->id . ' [' . $error_code . ']: ' . $error_msg);
            
            // Provide friendly messages for common errors
            if (strpos($error_msg, 'signed up for Connect') !== false || strpos($error_msg, 'connect') !== false) {
                wp_send_json_error(array('message' => 'Stripe Connect must be set up in your Stripe Dashboard first. Go to Stripe Dashboard → Connect → Get Started.'));
            } elseif (strpos($error_msg, 'Invalid API Key') !== false || $error_code === 'no_api_key') {
                wp_send_json_error(array('message' => 'Invalid Stripe API key. Please check your keys in WordPress Admin → PTP Settings → Stripe.'));
            } elseif ($error_code === 'stripe_not_configured') {
                wp_send_json_error(array('message' => 'Stripe API keys are not configured. Please add them in WordPress Admin → PTP Settings → Stripe.'));
            } elseif ($error_code === 'connect_not_enabled') {
                wp_send_json_error(array('message' => 'Stripe Connect is disabled. Please enable it in WordPress Admin → PTP Settings → Stripe.'));
            } else {
                wp_send_json_error(array('message' => 'Unable to start payment setup: ' . $error_msg));
            }
        }
        
        wp_send_json_success(array(
            'url' => $result['url'],
            'message' => 'Redirecting to Stripe...'
        ));
    }
    
    /**
     * Get Stripe Connect dashboard link for trainer
     */
    public static function get_stripe_dashboard_link() {
        self::verify_nonce();
        self::require_login();
        
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        if (!$trainer || empty($trainer->stripe_account_id)) {
            wp_send_json_error(array('message' => 'No payment account found. Please connect your account first.'));
        }
        
        // First check if account needs to complete onboarding
        $account = PTP_Stripe::get_account($trainer->stripe_account_id);
        
        if (is_wp_error($account)) {
            wp_send_json_error(array('message' => 'Unable to access your payment account. Please reconnect.'));
        }
        
        // If not fully set up, send to onboarding
        if (!$account['charges_enabled'] || !$account['payouts_enabled']) {
            $link = PTP_Stripe::create_account_link($trainer->stripe_account_id);
            if (!is_wp_error($link)) {
                wp_send_json_success(array('url' => $link['url']));
            }
        }
        
        // Fully set up - send to dashboard
        $link = PTP_Stripe::create_login_link($trainer->stripe_account_id);
        
        if (is_wp_error($link)) {
            wp_send_json_error(array('message' => 'Unable to access Stripe dashboard. Please try again.'));
        }
        
        wp_send_json_success(array('url' => $link['url']));
    }
    
    /**
     * Save trainer coordinates from frontend geocoding
     */
    public static function save_trainer_coords() {
        $trainer_id = isset($_POST['trainer_id']) ? intval($_POST['trainer_id']) : 0;
        $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
        $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
        
        if (!$trainer_id || !$latitude || !$longitude) {
            wp_send_json_error(array('message' => 'Invalid data'));
        }
        
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'ptp_trainers',
            array(
                'latitude' => $latitude,
                'longitude' => $longitude
            ),
            array('id' => $trainer_id),
            array('%f', '%f'),
            array('%d')
        );
        
        wp_send_json_success();
    }
    
    /**
     * Quick update availability for a specific day (inline editing from dashboard)
     * BULLETPROOF: Full validation, error handling, and fallbacks
     */
    public static function quick_update_availability() {
        // Log the incoming request for debugging
        error_log('PTP Availability Save: ' . print_r($_POST, true));
        
        try {
            // Verify nonce
            $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
            if (empty($nonce) || !wp_verify_nonce($nonce, 'ptp_nonce')) {
                error_log('PTP Availability: Nonce verification failed');
                wp_send_json_error(array('message' => 'Security check failed. Please refresh the page.'));
                return;
            }
            
            // Check login
            if (!is_user_logged_in()) {
                error_log('PTP Availability: User not logged in');
                wp_send_json_error(array('message' => 'Please log in to continue'));
                return;
            }
            
            $user_id = get_current_user_id();
            
            // Get trainer
            $trainer = PTP_Trainer::get_by_user_id($user_id);
            if (!$trainer) {
                error_log('PTP Availability: Trainer not found for user ' . $user_id);
                wp_send_json_error(array('message' => 'Trainer profile not found'));
                return;
            }
            
            // Get and validate day
            $day = isset($_POST['day']) ? intval($_POST['day']) : -1;
            if ($day < 0 || $day > 6) {
                error_log('PTP Availability: Invalid day ' . $day);
                wp_send_json_error(array('message' => 'Invalid day selection'));
                return;
            }
            
            // Get and validate times
            $start = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : '16:00';
            $end = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : '20:00';
            
            // Clean up time format - remove any non-time characters
            $start = preg_replace('/[^0-9:]/', '', $start);
            $end = preg_replace('/[^0-9:]/', '', $end);
            
            // Ensure proper format
            if (!preg_match('/^\d{1,2}:\d{2}$/', $start)) $start = '16:00';
            if (!preg_match('/^\d{1,2}:\d{2}$/', $end)) $end = '20:00';
            
            // Pad to 5 characters (HH:MM)
            if (strlen($start) === 4) $start = '0' . $start;
            if (strlen($end) === 4) $end = '0' . $end;
            
            // Get enabled status
            $enabled_raw = isset($_POST['enabled']) ? $_POST['enabled'] : '0';
            $enabled = in_array($enabled_raw, array('1', 1, true, 'true'), true) ? 1 : 0;
            
            // Validate time logic
            $start_mins = intval(substr($start, 0, 2)) * 60 + intval(substr($start, 3, 2));
            $end_mins = intval(substr($end, 0, 2)) * 60 + intval(substr($end, 3, 2));
            if ($end_mins <= $start_mins) {
                error_log('PTP Availability: End time not after start time');
                wp_send_json_error(array('message' => 'End time must be after start time'));
                return;
            }
            
            // CRITICAL: Force table check/repair before any save operation
            if (class_exists('PTP_Availability')) {
                PTP_Availability::force_table_check();
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'ptp_availability';
            
            // Check for existing record
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table WHERE trainer_id = %d AND day_of_week = %d",
                $trainer->id, $day
            ));
            
            $result = false;
            
            if ($existing && isset($existing->id)) {
                $result = $wpdb->update(
                    $table, 
                    array(
                        'start_time' => $start . ':00',
                        'end_time' => $end . ':00',
                        'is_active' => $enabled
                    ), 
                    array('id' => $existing->id)
                );
                error_log('PTP Availability: Updated existing record ' . $existing->id . ' result: ' . var_export($result, true));
            } else {
                $result = $wpdb->insert(
                    $table, 
                    array(
                        'trainer_id' => $trainer->id,
                        'day_of_week' => $day,
                        'start_time' => $start . ':00',
                        'end_time' => $end . ':00',
                        'is_active' => $enabled
                    )
                );
                error_log('PTP Availability: Inserted new record, result: ' . var_export($result, true));
            }
            
            if ($result === false) {
                $error = $wpdb->last_error;
                error_log('PTP Availability DB Error: ' . $error);
                wp_send_json_error(array('message' => 'Database error: ' . $error));
                return;
            }
            
            // Clear cache for this trainer's availability
            $current_month = date('n');
            $current_year = date('Y');
            wp_cache_delete('ptp_availability_' . $trainer->id . '_' . $current_year . '_' . $current_month, 'ptp');
            // Also clear next month in case they're booking ahead
            $next_month = $current_month == 12 ? 1 : $current_month + 1;
            $next_year = $current_month == 12 ? $current_year + 1 : $current_year;
            wp_cache_delete('ptp_availability_' . $trainer->id . '_' . $next_year . '_' . $next_month, 'ptp');
            
            wp_send_json_success(array(
                'message' => 'Availability saved',
                'day' => $day,
                'enabled' => $enabled,
                'start' => $start,
                'end' => $end
            ));
            
        } catch (Exception $e) {
            error_log('PTP Availability Exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * Fallback handler for ptp_save_trainer_schedule
     * Routes to PTP_Availability::ajax_save_schedule if available
     */
    public static function save_trainer_schedule_fallback() {
        if (class_exists('PTP_Availability') && method_exists('PTP_Availability', 'ajax_save_schedule')) {
            PTP_Availability::ajax_save_schedule();
        } else {
            // Use the existing quick_update_availability logic
            self::quick_update_availability();
        }
    }
    
    /**
     * Toggle day availability on/off
     */
    public static function toggle_day_availability() {
        self::verify_nonce();
        self::require_login();
        
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer profile not found'));
            return;
        }
        
        $day = intval($_POST['day'] ?? -1);
        
        if ($day < 0 || $day > 6) {
            wp_send_json_error(array('message' => 'Invalid day'));
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ptp_availability';
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, is_active FROM $table WHERE trainer_id = %d AND day_of_week = %d",
            $trainer->id, $day
        ));
        
        if ($existing) {
            $new_status = $existing->is_active ? 0 : 1;
            $wpdb->update($table, array('is_active' => $new_status), array('id' => $existing->id));
        } else {
            // Create new availability slot with default hours
            $wpdb->insert($table, array(
                'trainer_id' => $trainer->id,
                'day_of_week' => $day,
                'start_time' => '16:00:00',
                'end_time' => '20:00:00',
                'is_active' => 1
            ));
            $new_status = 1;
        }
        
        wp_send_json_success(array(
            'enabled' => (bool)$new_status,
            'message' => $new_status ? 'Day enabled' : 'Day disabled'
        ));
    }
    
    /**
     * Add availability exception (block specific date or add extra hours)
     */
    public static function add_availability_exception() {
        self::verify_nonce();
        self::require_login();
        
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer profile not found'));
            return;
        }
        
        $date = sanitize_text_field($_POST['date'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? 'blocked'); // blocked, available
        $start = sanitize_text_field($_POST['start'] ?? '');
        $end = sanitize_text_field($_POST['end'] ?? '');
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        
        if (!$date || !strtotime($date)) {
            wp_send_json_error(array('message' => 'Invalid date'));
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ptp_availability_exceptions';
        
        // Create table if it doesn't exist
        $wpdb->query("CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            trainer_id bigint(20) UNSIGNED NOT NULL,
            exception_date date NOT NULL,
            exception_type enum('blocked','available') DEFAULT 'blocked',
            start_time time DEFAULT NULL,
            end_time time DEFAULT NULL,
            reason varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY trainer_date (trainer_id, exception_date)
        ) " . $wpdb->get_charset_collate());
        
        $wpdb->insert($table, array(
            'trainer_id' => $trainer->id,
            'exception_date' => $date,
            'exception_type' => $type,
            'start_time' => $type === 'available' && $start ? $start . ':00' : null,
            'end_time' => $type === 'available' && $end ? $end . ':00' : null,
            'reason' => $reason
        ));
        
        wp_send_json_success(array(
            'id' => $wpdb->insert_id,
            'message' => $type === 'blocked' ? 'Date blocked' : 'Extra availability added'
        ));
    }
    
    /**
     * Remove availability exception
     */
    public static function remove_availability_exception() {
        self::verify_nonce();
        self::require_login();
        
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer profile not found'));
            return;
        }
        
        $exception_id = intval($_POST['exception_id'] ?? 0);
        
        if (!$exception_id) {
            wp_send_json_error(array('message' => 'Invalid exception ID'));
            return;
        }
        
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'ptp_availability_exceptions',
            array('id' => $exception_id, 'trainer_id' => $trainer->id)
        );
        
        wp_send_json_success(array('message' => 'Exception removed'));
    }
    
    /**
     * Send message to trainer from public profile (logged in user)
     */
    public static function send_public_message() {
        self::verify_nonce();
        self::require_login();
        
        $trainer_id = intval($_POST['trainer_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? 'Inquiry from profile');
        
        if (!$trainer_id || empty($message)) {
            wp_send_json_error(array('message' => 'Trainer ID and message are required'));
            return;
        }
        
        $trainer = PTP_Trainer::get($trainer_id);
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer not found'));
            return;
        }
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        // Get or create conversation
        if (class_exists('PTP_Messaging')) {
            $conversation_id = PTP_Messaging::get_or_create_conversation($user_id, $trainer->user_id);
            
            if ($conversation_id) {
                PTP_Messaging::send_message($conversation_id, $user_id, $message);
                
                // Send email notification to trainer
                if (class_exists('PTP_Email')) {
                    PTP_Email::send_new_message_notification($trainer->user_id, $user->display_name, $message);
                }
                
                wp_send_json_success(array(
                    'message' => 'Message sent! The trainer will respond via the messaging system.',
                    'conversation_id' => $conversation_id
                ));
                return;
            }
        }
        
        // Fallback: Send email directly if messaging system not available
        $trainer_email = get_userdata($trainer->user_id)->user_email ?? '';
        if ($trainer_email) {
            $subject_line = '[PTP] New inquiry from ' . $user->display_name;
            $body = "You have a new message from {$user->display_name}:\n\n" . $message;
            $body .= "\n\n---\nReply to: " . $user->user_email;
            
            wp_mail($trainer_email, $subject_line, $body);
        }
        
        wp_send_json_success(array('message' => 'Message sent to trainer!'));
    }
    
    /**
     * Send message to trainer from public profile (guest user - creates inquiry)
     */
    public static function send_public_message_guest() {
        // Verify nonce from POST
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (!wp_verify_nonce($nonce, 'ptp_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed. Please refresh the page.'));
            return;
        }
        
        $trainer_id = intval($_POST['trainer_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        if (!$trainer_id || empty($message) || empty($name) || empty($email)) {
            wp_send_json_error(array('message' => 'Name, email, and message are required'));
            return;
        }
        
        $trainer = PTP_Trainer::get($trainer_id);
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer not found'));
            return;
        }
        
        // Send email to trainer
        $trainer_email = get_userdata($trainer->user_id)->user_email ?? '';
        if ($trainer_email) {
            $subject = '[PTP] New inquiry from ' . $name;
            $body = "You have a new inquiry from your PTP profile:\n\n";
            $body .= "Name: $name\n";
            $body .= "Email: $email\n";
            if ($phone) $body .= "Phone: $phone\n";
            $body .= "\nMessage:\n" . $message;
            $body .= "\n\n---\nThis inquiry was sent from your PTP trainer profile.";
            
            wp_mail($trainer_email, $subject, $body, array('Reply-To: ' . $email));
        }
        
        // Also email schedule coordinator
        $coordinator_email = get_option('ptp_schedule_coordinator_email', 'luke@ptpsummercamps.com');
        if ($coordinator_email) {
            $subject = '[PTP] New trainer inquiry - ' . $trainer->display_name;
            $body = "New inquiry received:\n\n";
            $body .= "Trainer: " . $trainer->display_name . "\n";
            $body .= "From: $name ($email)\n";
            if ($phone) $body .= "Phone: $phone\n";
            $body .= "\nMessage:\n" . $message;
            
            wp_mail($coordinator_email, $subject, $body);
        }
        
        wp_send_json_success(array(
            'message' => 'Your message has been sent! ' . $trainer->display_name . ' will get back to you soon.'
        ));
    }
    
    /**
     * Get trainer availability for a month (for booking calendar)
     * Returns availability data for each day in the month
     * Format: { "2025-01-15": ["09:00", "10:00", "11:00"], ... }
     * v26.4: Added comprehensive debugging
     */
    public static function get_trainer_availability() {
        // Get parameters from GET or POST
        $trainer_id = isset($_GET['trainer_id']) ? intval($_GET['trainer_id']) : (isset($_POST['trainer_id']) ? intval($_POST['trainer_id']) : 0);
        $month = isset($_GET['month']) ? intval($_GET['month']) : (isset($_POST['month']) ? intval($_POST['month']) : date('n'));
        $year = isset($_GET['year']) ? intval($_GET['year']) : (isset($_POST['year']) ? intval($_POST['year']) : date('Y'));
        $debug = isset($_GET['debug']) || isset($_POST['debug']);
        
        $debug_info = array();
        
        // Validate trainer ID
        if (!$trainer_id || $trainer_id < 1) {
            wp_send_json_error(array('message' => 'Invalid trainer ID', 'trainer_id' => $trainer_id));
            return;
        }
        
        // Validate month (1-12)
        if ($month < 1 || $month > 12) {
            wp_send_json_error(array('message' => 'Invalid month'));
            return;
        }
        
        // Validate year (reasonable range)
        if ($year < 2020 || $year > 2100) {
            wp_send_json_error(array('message' => 'Invalid year'));
            return;
        }
        
        // Verify trainer exists and is active
        $trainer = PTP_Trainer::get($trainer_id);
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer not found', 'trainer_id' => $trainer_id));
            return;
        }
        if ($trainer->status !== 'active') {
            wp_send_json_error(array('message' => 'Trainer not active', 'status' => $trainer->status));
            return;
        }
        
        $debug_info['trainer'] = array('id' => $trainer->id, 'name' => $trainer->display_name, 'status' => $trainer->status);
        
        // Skip cache if debugging
        if (!$debug) {
            $cache_key = 'ptp_availability_' . $trainer_id . '_' . $year . '_' . $month;
            $cached = wp_cache_get($cache_key, 'ptp');
            if ($cached !== false) {
                wp_send_json_success(array('availability' => $cached, 'cached' => true));
                return;
            }
        }
        
        // Ensure availability class exists
        if (!class_exists('PTP_Availability')) {
            wp_send_json_error(array('message' => 'PTP_Availability class not found'));
            return;
        }
        
        // Check what's in the database for this trainer
        global $wpdb;
        $weekly_avail = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_availability WHERE trainer_id = %d ORDER BY day_of_week",
            $trainer_id
        ));
        
        $debug_info['weekly_availability_rows'] = count($weekly_avail);
        $debug_info['weekly_availability'] = array();
        foreach ($weekly_avail as $row) {
            $days = array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');
            $debug_info['weekly_availability'][] = array(
                'day' => $days[$row->day_of_week] ?? $row->day_of_week,
                'start' => $row->start_time,
                'end' => $row->end_time,
                'active' => (bool)$row->is_active
            );
        }
        
        // Get all days in the month
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $today = date('Y-m-d');
        $availability = array();
        
        $debug_info['month'] = $month;
        $debug_info['year'] = $year;
        $debug_info['days_in_month'] = $days_in_month;
        $debug_info['today'] = $today;
        
        // Iterate through each day and get available slots
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            
            // Skip past dates
            if ($date < $today) {
                continue;
            }
            
            // Don't look more than 90 days ahead
            $date_ts = strtotime($date);
            $max_future = strtotime('+90 days');
            if ($date_ts > $max_future) {
                continue;
            }
            
            // Get available slots for this date
            $slots = PTP_Availability::get_available_slots($trainer_id, $date);
            
            if (!empty($slots) && is_array($slots)) {
                // Convert slots to simple time strings for frontend compatibility
                $time_strings = array();
                foreach ($slots as $slot) {
                    if (is_array($slot) && isset($slot['start'])) {
                        $time_strings[] = $slot['start'];
                    } elseif (is_object($slot) && isset($slot->start)) {
                        $time_strings[] = $slot->start;
                    } elseif (is_string($slot)) {
                        $time_strings[] = $slot;
                    }
                }
                
                if (!empty($time_strings)) {
                    $availability[$date] = $time_strings;
                }
            }
        }
        
        $debug_info['dates_with_availability'] = count($availability);
        
        // Cache for 5 minutes (skip if debugging)
        if (!$debug) {
            $cache_key = 'ptp_availability_' . $trainer_id . '_' . $year . '_' . $month;
            wp_cache_set($cache_key, $availability, 'ptp', 300);
        }
        
        $response = array('availability' => $availability);
        if ($debug) {
            $response['debug'] = $debug_info;
        }
        
        wp_send_json_success($response);
    }
}
