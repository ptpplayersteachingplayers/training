<?php
/**
 * Notifications Class
 */

defined('ABSPATH') || exit;

class PTP_Notifications {
    
    public static function create($user_id, $type, $title, $message, $data = array()) {
        global $wpdb;
        
        return $wpdb->insert($wpdb->prefix . 'ptp_notifications', array(
            'user_id' => $user_id,
            'type' => sanitize_text_field($type),
            'title' => sanitize_text_field($title),
            'message' => sanitize_textarea_field($message),
            'data' => json_encode($data),
        ));
    }
    
    public static function get_for_user($user_id, $limit = 20, $unread_only = false) {
        global $wpdb;
        
        $where = "user_id = %d";
        $params = array($user_id);
        
        if ($unread_only) {
            $where .= " AND is_read = 0";
        }
        
        $params[] = $limit;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_notifications WHERE $where ORDER BY created_at DESC LIMIT %d",
            $params
        ));
    }
    
    public static function get_unread_count($user_id) {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ptp_notifications WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
    }
    
    public static function mark_as_read($notification_id, $user_id) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'ptp_notifications',
            array('is_read' => 1, 'read_at' => current_time('mysql')),
            array('id' => $notification_id, 'user_id' => $user_id)
        );
    }
    
    public static function mark_all_read($user_id) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'ptp_notifications',
            array('is_read' => 1, 'read_at' => current_time('mysql')),
            array('user_id' => $user_id, 'is_read' => 0)
        );
    }
    
    public static function booking_created($booking_id) {
        $booking = PTP_Booking::get_full($booking_id);
        if (!$booking) return;
        
        $date = date('l, F j', strtotime($booking->session_date));
        $time = date('g:i A', strtotime($booking->start_time));
        
        // Notify trainer
        self::create(
            $booking->trainer_user_id,
            'new_booking',
            'New Booking!',
            sprintf('%s booked a session with %s on %s at %s', 
                $booking->parent_name, $booking->player_name, $date, $time),
            array('booking_id' => $booking_id)
        );
        
        // Send email to trainer
        $trainer_email = get_userdata($booking->trainer_user_id)->user_email;
        wp_mail(
            $trainer_email,
            'New Booking - ' . $booking->player_name,
            sprintf("You have a new booking!\n\nPlayer: %s\nDate: %s\nTime: %s\n\nLog in to your dashboard to view details.",
                $booking->player_name, $date, $time)
        );
        
        // Notify parent
        self::create(
            $booking->parent_user_id,
            'booking_confirmed',
            'Booking Confirmed!',
            sprintf('Your session with %s on %s at %s is confirmed!', 
                $booking->trainer_name, $date, $time),
            array('booking_id' => $booking_id)
        );
        
        // Send email to parent
        $parent_email = get_userdata($booking->parent_user_id)->user_email;
        wp_mail(
            $parent_email,
            'Booking Confirmed - ' . $booking->trainer_name,
            sprintf("Your booking is confirmed!\n\nTrainer: %s\nPlayer: %s\nDate: %s\nTime: %s\nBooking #: %s\n\nMessage your trainer to coordinate the location.",
                $booking->trainer_name, $booking->player_name, $date, $time, $booking->booking_number)
        );
    }
    
    public static function booking_cancelled($booking_id, $cancelled_by) {
        $booking = PTP_Booking::get_full($booking_id);
        if (!$booking) return;
        
        $date = date('l, F j', strtotime($booking->session_date));
        
        // Determine who to notify
        if ($cancelled_by == $booking->trainer_user_id) {
            self::create(
                $booking->parent_user_id,
                'booking_cancelled',
                'Booking Cancelled',
                sprintf('Your session with %s on %s has been cancelled by the trainer.', 
                    $booking->trainer_name, $date),
                array('booking_id' => $booking_id)
            );
        } else {
            self::create(
                $booking->trainer_user_id,
                'booking_cancelled',
                'Booking Cancelled',
                sprintf('The session with %s on %s has been cancelled.', 
                    $booking->player_name, $date),
                array('booking_id' => $booking_id)
            );
        }
    }
    
    public static function session_reminder($booking_id) {
        $booking = PTP_Booking::get_full($booking_id);
        if (!$booking) return;
        
        $time = date('g:i A', strtotime($booking->start_time));
        
        // Remind trainer
        self::create(
            $booking->trainer_user_id,
            'session_reminder',
            'Session Tomorrow',
            sprintf('Reminder: You have a session with %s tomorrow at %s', 
                $booking->player_name, $time),
            array('booking_id' => $booking_id)
        );
        
        // Remind parent
        self::create(
            $booking->parent_user_id,
            'session_reminder',
            'Session Tomorrow',
            sprintf('Reminder: %s has a session with %s tomorrow at %s', 
                $booking->player_name, $booking->trainer_name, $time),
            array('booking_id' => $booking_id)
        );
    }
    
    public static function new_message($conversation_id, $sender_id) {
        $conversation = PTP_Messaging::get_conversation($conversation_id);
        if (!$conversation) return;
        
        $sender = get_userdata($sender_id);
        $sender_name = $sender ? $sender->display_name : 'Someone';
        
        $trainer = PTP_Trainer::get($conversation->trainer_id);
        $parent = PTP_Parent::get($conversation->parent_id);
        
        // Notify the other person
        if ($trainer && $trainer->user_id == $sender_id) {
            // Trainer sent, notify parent
            self::create(
                $parent->user_id,
                'new_message',
                'New Message',
                sprintf('You have a new message from %s', $trainer->display_name),
                array('conversation_id' => $conversation_id)
            );
        } elseif ($parent && $parent->user_id == $sender_id) {
            // Parent sent, notify trainer
            self::create(
                $trainer->user_id,
                'new_message',
                'New Message',
                sprintf('You have a new message from %s', $parent->display_name),
                array('conversation_id' => $conversation_id)
            );
        }
    }
}
