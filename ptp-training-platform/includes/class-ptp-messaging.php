<?php
/**
 * Messaging Class
 */

defined('ABSPATH') || exit;

class PTP_Messaging {
    
    public static function get_conversation($conversation_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_conversations WHERE id = %d",
            $conversation_id
        ));
    }
    
    public static function get_or_create_conversation($trainer_id, $parent_id) {
        global $wpdb;
        
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_conversations WHERE trainer_id = %d AND parent_id = %d",
            $trainer_id, $parent_id
        ));
        
        if ($conversation) {
            return $conversation;
        }
        
        $wpdb->insert($wpdb->prefix . 'ptp_conversations', array(
            'trainer_id' => $trainer_id,
            'parent_id' => $parent_id,
        ));
        
        return self::get_conversation($wpdb->insert_id);
    }
    
    public static function get_conversations_for_user($user_id) {
        global $wpdb;
        
        // Check if trainer or parent
        $trainer = PTP_Trainer::get_by_user_id($user_id);
        $parent = PTP_Parent::get_by_user_id($user_id);
        
        if ($trainer) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, p.display_name as other_name, p.user_id as other_user_id, c.trainer_unread_count as unread_count
                 FROM {$wpdb->prefix}ptp_conversations c
                 JOIN {$wpdb->prefix}ptp_parents p ON c.parent_id = p.id
                 WHERE c.trainer_id = %d
                 ORDER BY c.last_message_at DESC",
                $trainer->id
            ));
        } elseif ($parent) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, t.display_name as other_name, t.user_id as other_user_id, t.photo_url as other_photo, c.parent_unread_count as unread_count
                 FROM {$wpdb->prefix}ptp_conversations c
                 JOIN {$wpdb->prefix}ptp_trainers t ON c.trainer_id = t.id
                 WHERE c.parent_id = %d
                 ORDER BY c.last_message_at DESC",
                $parent->id
            ));
        }
        
        return array();
    }
    
    public static function get_messages($conversation_id, $limit = 50, $before_id = null) {
        global $wpdb;
        
        $where = "conversation_id = %d";
        $params = array($conversation_id);
        
        if ($before_id) {
            $where .= " AND id < %d";
            $params[] = $before_id;
        }
        
        $params[] = $limit;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_messages WHERE $where ORDER BY created_at DESC LIMIT %d",
            $params
        ));
    }
    
    public static function send_message($conversation_id, $sender_id, $message) {
        global $wpdb;
        
        $conversation = self::get_conversation($conversation_id);
        if (!$conversation) {
            return new WP_Error('invalid_conversation', 'Conversation not found');
        }
        
        // Verify sender is part of conversation
        $trainer = PTP_Trainer::get_by_user_id($sender_id);
        $parent = PTP_Parent::get_by_user_id($sender_id);
        
        $is_trainer_sender = $trainer && $trainer->id == $conversation->trainer_id;
        $is_parent_sender = $parent && $parent->id == $conversation->parent_id;
        
        if (!$is_trainer_sender && !$is_parent_sender) {
            return new WP_Error('unauthorized', 'You are not part of this conversation');
        }
        
        // Insert message
        $wpdb->insert($wpdb->prefix . 'ptp_messages', array(
            'conversation_id' => $conversation_id,
            'sender_id' => $sender_id,
            'message' => sanitize_textarea_field($message),
        ));
        
        $message_id = $wpdb->insert_id;
        
        // Update conversation
        $update_data = array(
            'last_message_id' => $message_id,
            'last_message_at' => current_time('mysql'),
        );
        
        if ($is_trainer_sender) {
            $update_data['parent_unread_count'] = $conversation->parent_unread_count + 1;
        } else {
            $update_data['trainer_unread_count'] = $conversation->trainer_unread_count + 1;
        }
        
        $wpdb->update($wpdb->prefix . 'ptp_conversations', $update_data, array('id' => $conversation_id));
        
        return $message_id;
    }
    
    public static function mark_as_read($conversation_id, $user_id) {
        global $wpdb;
        
        $conversation = self::get_conversation($conversation_id);
        if (!$conversation) {
            return false;
        }
        
        $trainer = PTP_Trainer::get_by_user_id($user_id);
        $parent = PTP_Parent::get_by_user_id($user_id);
        
        if ($trainer && $trainer->id == $conversation->trainer_id) {
            $wpdb->update(
                $wpdb->prefix . 'ptp_conversations',
                array('trainer_unread_count' => 0),
                array('id' => $conversation_id)
            );
        } elseif ($parent && $parent->id == $conversation->parent_id) {
            $wpdb->update(
                $wpdb->prefix . 'ptp_conversations',
                array('parent_unread_count' => 0),
                array('id' => $conversation_id)
            );
        }
        
        // Mark individual messages as read
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}ptp_messages SET is_read = 1, read_at = %s WHERE conversation_id = %d AND sender_id != %d AND is_read = 0",
            current_time('mysql'), $conversation_id, $user_id
        ));
        
        return true;
    }
    
    public static function get_unread_count($user_id) {
        global $wpdb;
        
        $trainer = PTP_Trainer::get_by_user_id($user_id);
        $parent = PTP_Parent::get_by_user_id($user_id);
        
        if ($trainer) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(trainer_unread_count) FROM {$wpdb->prefix}ptp_conversations WHERE trainer_id = %d",
                $trainer->id
            ));
        } elseif ($parent) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(parent_unread_count) FROM {$wpdb->prefix}ptp_conversations WHERE parent_id = %d",
                $parent->id
            ));
        }
        
        return 0;
    }
}
