<?php
/**
 * PTP SMS System (Twilio Integration)
 * Handles all SMS notifications
 */

defined('ABSPATH') || exit;

class PTP_SMS {
    
    private static $account_sid;
    private static $auth_token;
    private static $from_number;
    private static $enabled = false;
    
    public static function init() {
        self::$account_sid = get_option('ptp_twilio_sid', '');
        self::$auth_token = get_option('ptp_twilio_token', '');
        self::$from_number = get_option('ptp_twilio_from', '');
        self::$enabled = !empty(self::$account_sid) && !empty(self::$auth_token) && !empty(self::$from_number);
    }
    
    /**
     * Check if SMS is configured
     */
    public static function is_enabled() {
        return self::$enabled;
    }
    
    /**
     * Send SMS via Twilio
     */
    public static function send($to, $message) {
        if (!self::$enabled) {
            return new WP_Error('sms_disabled', 'SMS is not configured');
        }
        
        // Format phone number
        $to = self::format_phone($to);
        if (!$to) {
            return new WP_Error('invalid_phone', 'Invalid phone number');
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/" . self::$account_sid . "/Messages.json";
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode(self::$account_sid . ':' . self::$auth_token),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'To' => $to,
                'From' => self::$from_number,
                'Body' => $message,
            ),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            self::log_error('Twilio API Error', $response->get_error_message());
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error_code'])) {
            self::log_error('Twilio Error', $body['error_message']);
            return new WP_Error('twilio_error', $body['error_message']);
        }
        
        // Log success
        self::log_message($to, $message, $body['sid']);
        
        return $body['sid'];
    }
    
    /**
     * Format phone number for Twilio (E.164 format)
     */
    private static function format_phone($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // US numbers
        if (strlen($phone) === 10) {
            return '+1' . $phone;
        }
        
        // Already has country code
        if (strlen($phone) === 11 && substr($phone, 0, 1) === '1') {
            return '+' . $phone;
        }
        
        // Already formatted
        if (strlen($phone) > 10) {
            return '+' . $phone;
        }
        
        return false;
    }
    
    /**
     * Send booking confirmation SMS to parent
     */
    public static function send_booking_confirmation($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, 
                   t.display_name as trainer_name,
                   p.name as player_name,
                   pa.phone as parent_phone
            FROM {$wpdb->prefix}ptp_bookings b
            JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
            JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
            JOIN {$wpdb->prefix}ptp_parents pa ON b.parent_id = pa.id
            WHERE b.id = %d
        ", $booking_id));
        
        if (!$booking || !$booking->parent_phone) return false;
        
        $message = "PTP Training Confirmed! ⚽\n\n";
        $message .= "{$booking->player_name} with {$booking->trainer_name}\n";
        $message .= date('D, M j', strtotime($booking->session_date)) . " at " . date('g:i A', strtotime($booking->start_time)) . "\n";
        if ($booking->location) {
            $message .= "📍 {$booking->location}\n";
        }
        $message .= "\nBooking #{$booking->booking_number}";
        
        return self::send($booking->parent_phone, $message);
    }
    
    /**
     * Send new booking SMS to trainer
     */
    public static function send_trainer_new_booking($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, 
                   t.phone as trainer_phone,
                   p.name as player_name, p.age as player_age,
                   pa.display_name as parent_name
            FROM {$wpdb->prefix}ptp_bookings b
            JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
            JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
            JOIN {$wpdb->prefix}ptp_parents pa ON b.parent_id = pa.id
            WHERE b.id = %d
        ", $booking_id));
        
        if (!$booking || !$booking->trainer_phone) return false;
        
        $message = "New PTP Booking! 🎉\n\n";
        $message .= "Player: {$booking->player_name} ({$booking->player_age} yrs)\n";
        $message .= "Parent: {$booking->parent_name}\n";
        $message .= date('D, M j', strtotime($booking->session_date)) . " at " . date('g:i A', strtotime($booking->start_time)) . "\n";
        if ($booking->location) {
            $message .= "📍 {$booking->location}\n";
        }
        $message .= "\nYou'll earn: $" . number_format($booking->trainer_payout, 2);
        
        return self::send($booking->trainer_phone, $message);
    }
    
    /**
     * Send session reminder (24 hours before)
     */
    public static function send_session_reminder($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, 
                   t.display_name as trainer_name, t.phone as trainer_phone,
                   p.name as player_name,
                   pa.display_name as parent_name, pa.phone as parent_phone
            FROM {$wpdb->prefix}ptp_bookings b
            JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
            JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
            JOIN {$wpdb->prefix}ptp_parents pa ON b.parent_id = pa.id
            WHERE b.id = %d
        ", $booking_id));
        
        if (!$booking) return false;
        
        // Parent reminder
        if ($booking->parent_phone) {
            $message = "Reminder: {$booking->player_name}'s training is TOMORROW!\n\n";
            $message .= "🕐 " . date('g:i A', strtotime($booking->start_time)) . "\n";
            $message .= "👤 {$booking->trainer_name}\n";
            if ($booking->location) {
                $message .= "📍 {$booking->location}";
            }
            
            self::send($booking->parent_phone, $message);
        }
        
        // Trainer reminder
        if ($booking->trainer_phone) {
            $message = "Reminder: Session with {$booking->player_name} TOMORROW!\n\n";
            $message .= "🕐 " . date('g:i A', strtotime($booking->start_time)) . "\n";
            $message .= "📱 Parent: {$booking->parent_name}\n";
            if ($booking->location) {
                $message .= "📍 {$booking->location}";
            }
            
            self::send($booking->trainer_phone, $message);
        }
        
        return true;
    }
    
    /**
     * Send cancellation SMS
     */
    public static function send_cancellation($booking_id, $cancelled_by = 'parent') {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, 
                   t.display_name as trainer_name, t.phone as trainer_phone,
                   p.name as player_name,
                   pa.display_name as parent_name, pa.phone as parent_phone
            FROM {$wpdb->prefix}ptp_bookings b
            JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
            JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
            JOIN {$wpdb->prefix}ptp_parents pa ON b.parent_id = pa.id
            WHERE b.id = %d
        ", $booking_id));
        
        if (!$booking) return false;
        
        $date_str = date('D, M j', strtotime($booking->session_date)) . " at " . date('g:i A', strtotime($booking->start_time));
        
        // Notify the other party
        if ($cancelled_by === 'parent' && $booking->trainer_phone) {
            $message = "Session Cancelled ❌\n\n";
            $message .= "{$booking->player_name}'s session on {$date_str} has been cancelled by the parent.";
            self::send($booking->trainer_phone, $message);
        } elseif ($cancelled_by === 'trainer' && $booking->parent_phone) {
            $message = "Session Cancelled ❌\n\n";
            $message .= "{$booking->trainer_name} has cancelled the session on {$date_str}. We apologize for the inconvenience.";
            self::send($booking->parent_phone, $message);
        }
        
        return true;
    }
    
    /**
     * Send new message notification
     */
    public static function send_message_notification($recipient_phone, $sender_name) {
        if (!$recipient_phone) return false;
        
        $message = "New PTP message from {$sender_name}. Check your inbox at " . home_url('/messages/');
        
        return self::send($recipient_phone, $message);
    }
    
    /**
     * Send payout notification
     */
    public static function send_payout_notification($trainer_phone, $amount) {
        if (!$trainer_phone) return false;
        
        $message = "PTP Payout Sent! 💰\n\n";
        $message .= "You've received a payout of $" . number_format($amount, 2) . ".\n";
        $message .= "Check your bank account in 1-3 business days.";
        
        return self::send($trainer_phone, $message);
    }
    
    /**
     * Send session completion confirmation request
     */
    public static function send_completion_request($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, 
                   t.display_name as trainer_name, t.phone as trainer_phone,
                   p.name as player_name,
                   pa.display_name as parent_name, pa.phone as parent_phone
            FROM {$wpdb->prefix}ptp_bookings b
            JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
            JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
            JOIN {$wpdb->prefix}ptp_parents pa ON b.parent_id = pa.id
            WHERE b.id = %d
        ", $booking_id));
        
        if (!$booking) return false;
        
        // Ask both parties to confirm completion
        $confirm_url = home_url('/confirm-session/?booking=' . $booking_id);
        
        if ($booking->parent_phone) {
            $message = "Was {$booking->player_name}'s session today completed?\n\n";
            $message .= "Please confirm: {$confirm_url}";
            self::send($booking->parent_phone, $message);
        }
        
        if ($booking->trainer_phone) {
            $message = "Did you complete today's session with {$booking->player_name}?\n\n";
            $message .= "Please confirm: {$confirm_url}";
            self::send($booking->trainer_phone, $message);
        }
        
        return true;
    }
    
    /**
     * Send application status update
     */
    public static function send_application_update($phone, $status, $name) {
        if (!$phone) return false;
        
        if ($status === 'approved') {
            $message = "Congratulations {$name}! 🎉\n\n";
            $message .= "Your PTP trainer application has been approved!\n\n";
            $message .= "Login to set up your profile: " . home_url('/login/');
        } else {
            $message = "Hi {$name},\n\n";
            $message .= "Thank you for your interest in PTP Training. Unfortunately, we're unable to move forward with your application at this time.";
        }
        
        return self::send($phone, $message);
    }
    
    /**
     * Log SMS message
     */
    private static function log_message($to, $message, $sid) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'ptp_sms_log',
            array(
                'phone_to' => $to,
                'message' => $message,
                'twilio_sid' => $sid,
                'status' => 'sent',
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Log error
     */
    private static function log_error($type, $message) {
        error_log("PTP SMS Error [{$type}]: {$message}");
    }
    
    /**
     * Create SMS log table
     */
    public static function create_table() {
        global $wpdb;
        
        $charset = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ptp_sms_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            phone_to varchar(20) NOT NULL,
            message text NOT NULL,
            twilio_sid varchar(50),
            status varchar(20) DEFAULT 'pending',
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY phone_to (phone_to),
            KEY created_at (created_at)
        ) {$charset};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Test SMS configuration
     */
    public static function test_connection($phone = null) {
        if (!self::$enabled) {
            return array(
                'success' => false,
                'message' => 'SMS is not configured. Please add Twilio credentials.'
            );
        }
        
        if ($phone) {
            $result = self::send($phone, 'PTP Training SMS test successful! ✓');
            
            if (is_wp_error($result)) {
                return array(
                    'success' => false,
                    'message' => $result->get_error_message()
                );
            }
            
            return array(
                'success' => true,
                'message' => 'Test SMS sent successfully!',
                'sid' => $result
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Twilio credentials are configured.'
        );
    }
}
