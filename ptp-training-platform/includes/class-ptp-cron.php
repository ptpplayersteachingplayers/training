<?php
/**
 * PTP Cron Jobs
 * Handles scheduled tasks like reminders, payouts, and cleanup
 */

defined('ABSPATH') || exit;

class PTP_Cron {
    
    public static function init() {
        // Register cron hooks
        add_action('ptp_send_session_reminders', array(__CLASS__, 'send_session_reminders'));
        add_action('ptp_send_hour_reminders', array(__CLASS__, 'send_hour_reminders'));
        add_action('ptp_send_review_requests', array(__CLASS__, 'send_review_requests'));
        add_action('ptp_process_payouts', array(__CLASS__, 'process_payouts'));
        add_action('ptp_process_refunds', array(__CLASS__, 'process_refunds'));
        add_action('ptp_cleanup_old_data', array(__CLASS__, 'cleanup_old_data'));
        add_action('ptp_send_completion_requests', array(__CLASS__, 'send_completion_requests'));
        add_action('ptp_auto_complete_sessions', array(__CLASS__, 'auto_complete_sessions'));
        
        // Add 15-minute interval
        add_filter('cron_schedules', array(__CLASS__, 'add_cron_intervals'));
        
        // Schedule events on init
        add_action('init', array(__CLASS__, 'schedule_events'));
    }
    
    /**
     * Add custom cron intervals
     */
    public static function add_cron_intervals($schedules) {
        $schedules['fifteen_minutes'] = array(
            'interval' => 900,
            'display' => 'Every 15 Minutes'
        );
        return $schedules;
    }
    
    /**
     * Schedule cron events
     */
    public static function schedule_events() {
        // Session reminders (24 hours) - run every hour
        if (!wp_next_scheduled('ptp_send_session_reminders')) {
            wp_schedule_event(time(), 'hourly', 'ptp_send_session_reminders');
        }
        
        // 1-hour reminders - run every 15 minutes
        if (!wp_next_scheduled('ptp_send_hour_reminders')) {
            wp_schedule_event(time(), 'fifteen_minutes', 'ptp_send_hour_reminders');
        }
        
        // Review requests - run every hour
        if (!wp_next_scheduled('ptp_send_review_requests')) {
            wp_schedule_event(time(), 'hourly', 'ptp_send_review_requests');
        }
        
        // Payout processing - run twice daily
        if (!wp_next_scheduled('ptp_process_payouts')) {
            wp_schedule_event(time(), 'twicedaily', 'ptp_process_payouts');
        }
        
        // Refund processing - run hourly
        if (!wp_next_scheduled('ptp_process_refunds')) {
            wp_schedule_event(time(), 'hourly', 'ptp_process_refunds');
        }
        
        // Data cleanup - run weekly
        if (!wp_next_scheduled('ptp_cleanup_old_data')) {
            wp_schedule_event(time(), 'weekly', 'ptp_cleanup_old_data');
        }
        
        // Completion requests - run every hour
        if (!wp_next_scheduled('ptp_send_completion_requests')) {
            wp_schedule_event(time(), 'hourly', 'ptp_send_completion_requests');
        }
        
        // Auto-complete old sessions - run daily
        if (!wp_next_scheduled('ptp_auto_complete_sessions')) {
            wp_schedule_event(time(), 'daily', 'ptp_auto_complete_sessions');
        }
    }
    
    /**
     * Clear all scheduled events (on deactivation)
     */
    public static function clear_events() {
        wp_clear_scheduled_hook('ptp_send_session_reminders');
        wp_clear_scheduled_hook('ptp_send_hour_reminders');
        wp_clear_scheduled_hook('ptp_send_review_requests');
        wp_clear_scheduled_hook('ptp_process_payouts');
        wp_clear_scheduled_hook('ptp_process_refunds');
        wp_clear_scheduled_hook('ptp_cleanup_old_data');
        wp_clear_scheduled_hook('ptp_send_completion_requests');
        wp_clear_scheduled_hook('ptp_auto_complete_sessions');
    }
    
    /**
     * Send session reminders (24 hours before)
     */
    public static function send_session_reminders() {
        global $wpdb;
        
        if (!get_option('ptp_email_session_reminder', true) && !get_option('ptp_sms_session_reminder', true)) {
            return;
        }
        
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $bookings = $wpdb->get_results($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}ptp_bookings 
            WHERE session_date = %s 
            AND status IN ('confirmed', 'pending')
            AND reminder_sent = 0
        ", $tomorrow));
        
        foreach ($bookings as $booking) {
            if (get_option('ptp_email_session_reminder', true)) {
                PTP_Email::send_session_reminder($booking->id);
            }
            
            if (get_option('ptp_sms_session_reminder', true) && class_exists('PTP_SMS') && PTP_SMS::is_enabled()) {
                PTP_SMS::send_session_reminder($booking->id);
            }
            
            $wpdb->update(
                $wpdb->prefix . 'ptp_bookings',
                array('reminder_sent' => 1),
                array('id' => $booking->id)
            );
        }
    }
    
    /**
     * Send 1-hour reminders (push notifications)
     */
    public static function send_hour_reminders() {
        global $wpdb;
        
        // Get sessions starting in the next 60-75 minutes
        $now = current_time('mysql');
        $hour_from_now = date('Y-m-d H:i:s', strtotime('+60 minutes'));
        $hour_15_from_now = date('Y-m-d H:i:s', strtotime('+75 minutes'));
        
        $bookings = $wpdb->get_results($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}ptp_bookings 
            WHERE CONCAT(session_date, ' ', start_time) BETWEEN %s AND %s
            AND status = 'confirmed'
            AND hour_reminder_sent = 0
        ", $hour_from_now, $hour_15_from_now));
        
        foreach ($bookings as $booking) {
            // Send push notification
            if (class_exists('PTP_Push_Notifications')) {
                do_action('ptp_session_reminder', $booking->id);
            }
            
            $wpdb->update(
                $wpdb->prefix . 'ptp_bookings',
                array('hour_reminder_sent' => 1),
                array('id' => $booking->id)
            );
        }
    }
    
    /**
     * Send review requests (after session completes)
     */
    public static function send_review_requests() {
        global $wpdb;
        
        if (!get_option('ptp_email_review_request', true)) {
            return;
        }
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $bookings = $wpdb->get_results($wpdb->prepare("
            SELECT b.id 
            FROM {$wpdb->prefix}ptp_bookings b
            LEFT JOIN {$wpdb->prefix}ptp_reviews r ON b.id = r.booking_id
            WHERE b.session_date = %s 
            AND b.status = 'completed'
            AND b.review_request_sent = 0
            AND r.id IS NULL
        ", $yesterday));
        
        foreach ($bookings as $booking) {
            PTP_Email::send_review_request($booking->id);
            
            $wpdb->update(
                $wpdb->prefix . 'ptp_bookings',
                array('review_request_sent' => 1),
                array('id' => $booking->id)
            );
        }
    }
    
    /**
     * Send completion confirmation requests
     */
    public static function send_completion_requests() {
        global $wpdb;
        
        $two_hours_ago = date('Y-m-d H:i:s', strtotime('-2 hours'));
        $now = current_time('mysql');
        
        $bookings = $wpdb->get_results($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}ptp_bookings 
            WHERE CONCAT(session_date, ' ', end_time) BETWEEN %s AND %s
            AND status = 'confirmed'
            AND completion_request_sent = 0
        ", $two_hours_ago, $now));
        
        foreach ($bookings as $booking) {
            if (class_exists('PTP_Push_Notifications')) {
                do_action('ptp_booking_completed', $booking->id);
            }
            
            $wpdb->update(
                $wpdb->prefix . 'ptp_bookings',
                array('completion_request_sent' => 1),
                array('id' => $booking->id)
            );
        }
    }
    
    /**
     * Auto-complete sessions that are 48+ hours old
     */
    public static function auto_complete_sessions() {
        global $wpdb;
        
        $two_days_ago = date('Y-m-d', strtotime('-2 days'));
        
        $bookings = $wpdb->get_results($wpdb->prepare("
            SELECT id, trainer_id, total_price FROM {$wpdb->prefix}ptp_bookings 
            WHERE session_date <= %s 
            AND status = 'confirmed'
            AND payment_status = 'paid'
        ", $two_days_ago));
        
        $platform_fee = floatval(get_option('ptp_platform_fee', 20)) / 100;
        
        foreach ($bookings as $booking) {
            $trainer_payout = $booking->total_price * (1 - $platform_fee);
            
            $wpdb->update(
                $wpdb->prefix . 'ptp_bookings',
                array(
                    'status' => 'completed',
                    'payout_status' => 'pending',
                    'trainer_payout' => $trainer_payout,
                ),
                array('id' => $booking->id)
            );
            
            // Update trainer stats
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}ptp_trainers SET total_sessions = total_sessions + 1 WHERE id = %d",
                $booking->trainer_id
            ));
        }
    }
    
    /**
     * Process pending payouts via Stripe Connect
     */
    public static function process_payouts() {
        global $wpdb;
        
        if (!class_exists('PTP_Stripe') || !get_option('ptp_stripe_connect_enabled')) {
            return;
        }
        
        $min_payout = floatval(get_option('ptp_min_payout', 25));
        
        // Get trainers with pending payouts
        $trainers = $wpdb->get_results($wpdb->prepare("
            SELECT trainer_id, SUM(trainer_payout) as total_pending
            FROM {$wpdb->prefix}ptp_bookings 
            WHERE status = 'completed' 
            AND payout_status = 'pending'
            AND trainer_payout > 0
            GROUP BY trainer_id
            HAVING total_pending >= %f
        ", $min_payout));
        
        foreach ($trainers as $row) {
            $trainer = PTP_Trainer::get($row->trainer_id);
            
            if (!$trainer || empty($trainer->stripe_account_id) || !$trainer->stripe_payouts_enabled) {
                continue;
            }
            
            // Get bookings to include in this payout
            $bookings = $wpdb->get_results($wpdb->prepare("
                SELECT id, trainer_payout FROM {$wpdb->prefix}ptp_bookings 
                WHERE trainer_id = %d AND status = 'completed' AND payout_status = 'pending'
            ", $row->trainer_id));
            
            $amount_cents = intval($row->total_pending * 100);
            
            // Create Stripe transfer
            $result = PTP_Stripe::create_transfer(
                $amount_cents,
                $trainer->stripe_account_id,
                'Trainer payout - ' . count($bookings) . ' sessions'
            );
            
            if (!is_wp_error($result)) {
                // Mark bookings as paid out
                $booking_ids = array_map(function($b) { return $b->id; }, $bookings);
                $placeholders = implode(',', array_fill(0, count($booking_ids), '%d'));
                
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}ptp_bookings 
                     SET payout_status = 'completed', 
                         payout_date = NOW(),
                         stripe_transfer_id = %s
                     WHERE id IN ($placeholders)",
                    array_merge(array($result['id']), $booking_ids)
                ));
                
                // Log payout
                $wpdb->insert($wpdb->prefix . 'ptp_payouts', array(
                    'trainer_id' => $row->trainer_id,
                    'amount' => $row->total_pending,
                    'stripe_transfer_id' => $result['id'],
                    'status' => 'completed',
                    'booking_count' => count($bookings),
                    'created_at' => current_time('mysql'),
                ));
                
                // Notify trainer
                if (class_exists('PTP_Push_Notifications')) {
                    PTP_Push_Notifications::send(
                        $trainer->user_id,
                        '💰 Payout Sent!',
                        '$' . number_format($row->total_pending, 2) . ' is on its way to your bank',
                        array('type' => 'payout', 'amount' => $row->total_pending)
                    );
                }
            }
        }
    }
    
    /**
     * Process pending refunds
     */
    public static function process_refunds() {
        global $wpdb;
        
        if (!class_exists('PTP_Stripe') || !PTP_Stripe::is_enabled()) {
            return;
        }
        
        // Get cancelled bookings needing refund
        $bookings = $wpdb->get_results("
            SELECT id, stripe_payment_id, total_price, cancelled_by, cancelled_at
            FROM {$wpdb->prefix}ptp_bookings 
            WHERE status = 'cancelled'
            AND payment_status = 'paid'
            AND refund_status = 'pending'
            AND stripe_payment_id IS NOT NULL
            AND stripe_payment_id != ''
        ");
        
        foreach ($bookings as $booking) {
            // Calculate refund amount based on cancellation policy
            $hours_before = (strtotime($booking->session_date . ' ' . $booking->start_time) - strtotime($booking->cancelled_at)) / 3600;
            
            $refund_percent = 100;
            if ($hours_before < 24 && $booking->cancelled_by === 'parent') {
                $refund_percent = 50; // 50% refund if cancelled < 24 hours by parent
            }
            if ($hours_before < 2 && $booking->cancelled_by === 'parent') {
                $refund_percent = 0; // No refund if cancelled < 2 hours by parent
            }
            if ($booking->cancelled_by === 'trainer') {
                $refund_percent = 100; // Full refund if trainer cancels
            }
            
            if ($refund_percent > 0) {
                $refund_amount = intval($booking->total_price * ($refund_percent / 100) * 100);
                
                $result = PTP_Stripe::create_refund($booking->stripe_payment_id, $refund_amount);
                
                if (!is_wp_error($result)) {
                    $wpdb->update(
                        $wpdb->prefix . 'ptp_bookings',
                        array(
                            'refund_status' => 'completed',
                            'refund_amount' => $refund_amount / 100,
                            'stripe_refund_id' => $result['id'],
                        ),
                        array('id' => $booking->id)
                    );
                }
            } else {
                $wpdb->update(
                    $wpdb->prefix . 'ptp_bookings',
                    array('refund_status' => 'none'),
                    array('id' => $booking->id)
                );
            }
        }
    }
    
    /**
     * Cleanup old data
     */
    public static function cleanup_old_data() {
        global $wpdb;
        
        // Delete old notifications (90 days)
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}ptp_notifications 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        
        // Delete old FCM tokens (30 days inactive)
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}ptp_fcm_tokens 
            WHERE updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Delete expired sessions (failed payments after 24 hours)
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}ptp_bookings 
            WHERE status = 'pending' 
            AND payment_status = 'pending'
            AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        
        // Archive old conversations (90 days no activity)
        $wpdb->query("
            UPDATE {$wpdb->prefix}ptp_conversations 
            SET status = 'archived'
            WHERE updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
            AND status = 'active'
        ");
    }
    
    /**
     * Run specific task manually (for admin/testing)
     */
    public static function run_task($task) {
        switch ($task) {
            case 'reminders': self::send_session_reminders(); break;
            case 'hour_reminders': self::send_hour_reminders(); break;
            case 'reviews': self::send_review_requests(); break;
            case 'payouts': self::process_payouts(); break;
            case 'refunds': self::process_refunds(); break;
            case 'cleanup': self::cleanup_old_data(); break;
            case 'completions': self::send_completion_requests(); break;
            case 'auto_complete': self::auto_complete_sessions(); break;
        }
    }
}
