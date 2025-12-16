<?php
/**
 * Trainer Class
 */

defined('ABSPATH') || exit;

class PTP_Trainer {
    
    public static function get($trainer_id) {
        global $wpdb;
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_trainers WHERE id = %d",
            $trainer_id
        ));
        
        // Ensure trainer has a slug
        if ($trainer && empty($trainer->slug)) {
            $trainer = self::ensure_slug($trainer);
        }
        
        return $trainer;
    }
    
    public static function get_by_user_id($user_id) {
        global $wpdb;
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_trainers WHERE user_id = %d",
            $user_id
        ));
        
        // Ensure trainer has a slug
        if ($trainer && empty($trainer->slug)) {
            $trainer = self::ensure_slug($trainer);
        }
        
        return $trainer;
    }
    
    public static function get_by_slug($slug) {
        if (empty($slug)) return null;
        
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_trainers WHERE slug = %s",
            $slug
        ));
    }
    
    /**
     * Ensure a trainer has a valid slug, generate one if missing
     */
    public static function ensure_slug($trainer) {
        if (!$trainer || !empty($trainer->slug)) {
            return $trainer;
        }
        
        global $wpdb;
        
        // Generate slug from display name
        $base_slug = sanitize_title($trainer->display_name ?: 'trainer');
        $slug = $base_slug;
        $counter = 1;
        
        // Ensure uniqueness
        while ($wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ptp_trainers WHERE slug = %s AND id != %d",
            $slug, $trainer->id
        ))) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
            if ($counter > 100) {
                $slug = $base_slug . '-' . time();
                break;
            }
        }
        
        // Save the slug
        $wpdb->update(
            $wpdb->prefix . 'ptp_trainers',
            array('slug' => $slug),
            array('id' => $trainer->id),
            array('%s'),
            array('%d')
        );
        
        // Update the object
        $trainer->slug = $slug;
        
        return $trainer;
    }
    
    public static function get_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'active',
            'orderby' => 'is_featured DESC, average_rating',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
            'search' => '',
            'specialty' => '',
            'min_rate' => 0,
            'max_rate' => 9999,
            'latitude' => null,
            'longitude' => null,
            'radius' => 50,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array("status = %s");
        $params = array($args['status']);
        
        if (!empty($args['search'])) {
            $where[] = "(display_name LIKE %s OR headline LIKE %s OR location LIKE %s)";
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        if (!empty($args['specialty'])) {
            $where[] = "specialties LIKE %s";
            $params[] = '%' . $wpdb->esc_like($args['specialty']) . '%';
        }
        
        if ($args['min_rate'] > 0) {
            $where[] = "hourly_rate >= %f";
            $params[] = $args['min_rate'];
        }
        
        if ($args['max_rate'] < 9999) {
            $where[] = "hourly_rate <= %f";
            $params[] = $args['max_rate'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Distance calculation if coordinates provided
        $select = "*";
        if ($args['latitude'] && $args['longitude']) {
            $select .= ", (3959 * acos(cos(radians(%f)) * cos(radians(latitude)) * cos(radians(longitude) - radians(%f)) + sin(radians(%f)) * sin(radians(latitude)))) AS distance";
            array_unshift($params, $args['latitude'], $args['longitude'], $args['latitude']);
            
            $where_clause .= " HAVING distance <= %f";
            $params[] = $args['radius'];
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'is_featured DESC, average_rating DESC';
        }
        
        $sql = "SELECT $select FROM {$wpdb->prefix}ptp_trainers WHERE $where_clause ORDER BY $orderby LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    public static function create($user_id, $data = array()) {
        global $wpdb;
        
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('invalid_user', 'User not found');
        }
        
        $display_name = !empty($data['display_name']) ? $data['display_name'] : $user->display_name;
        $slug = self::generate_unique_slug($display_name);
        
        $insert_data = array(
            'user_id' => $user_id,
            'display_name' => sanitize_text_field($display_name),
            'slug' => $slug,
            'headline' => sanitize_text_field($data['headline'] ?? ''),
            'bio' => sanitize_textarea_field($data['bio'] ?? ''),
            'photo_url' => esc_url_raw($data['photo_url'] ?? ''),
            'hourly_rate' => floatval($data['hourly_rate'] ?? 0),
            'location' => sanitize_text_field($data['location'] ?? ''),
            'travel_radius' => intval($data['travel_radius'] ?? 15),
            'college' => sanitize_text_field($data['college'] ?? ''),
            'team' => sanitize_text_field($data['team'] ?? ''),
            'position' => sanitize_text_field($data['position'] ?? ''),
            'specialties' => is_array($data['specialties'] ?? null) ? implode(',', array_map('sanitize_text_field', $data['specialties'])) : sanitize_text_field($data['specialties'] ?? ''),
            'status' => 'pending',
        );
        
        $result = $wpdb->insert($wpdb->prefix . 'ptp_trainers', $insert_data);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create trainer');
        }
        
        return $wpdb->insert_id;
    }
    
    public static function update($trainer_id, $data) {
        global $wpdb;
        
        $update_data = array();
        $allowed_fields = array(
            'display_name', 'slug', 'headline', 'bio', 'photo_url', 'hourly_rate',
            'location', 'city', 'state', 'latitude', 'longitude', 'travel_radius', 'college',
            'team', 'position', 'specialties', 'instagram', 'status', 'is_featured',
            'is_verified', 'is_background_checked', 'email', 'phone', 'playing_level'
        );
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                if ($field === 'specialties' && is_array($data[$field])) {
                    $update_data[$field] = implode(',', array_map('sanitize_text_field', $data[$field]));
                } elseif (in_array($field, array('hourly_rate', 'latitude', 'longitude'))) {
                    $update_data[$field] = floatval($data[$field]);
                } elseif (in_array($field, array('travel_radius', 'is_featured', 'is_verified', 'is_background_checked'))) {
                    $update_data[$field] = intval($data[$field]);
                } elseif ($field === 'slug') {
                    // Validate and sanitize slug, ensure uniqueness
                    $new_slug = self::generate_unique_slug($data[$field], $trainer_id);
                    if ($new_slug) {
                        $update_data[$field] = $new_slug;
                    }
                } else {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                }
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        return $wpdb->update(
            $wpdb->prefix . 'ptp_trainers',
            $update_data,
            array('id' => $trainer_id)
        );
    }
    
    /**
     * Generate a unique SEO-friendly slug
     */
    public static function generate_unique_slug($base_slug, $exclude_trainer_id = null) {
        global $wpdb;
        
        // Sanitize the base slug
        $slug = sanitize_title($base_slug);
        
        // Remove any numbers that were appended by previous slug generation
        $slug = preg_replace('/-\d+$/', '', $slug);
        
        if (empty($slug)) {
            return false;
        }
        
        // Check if slug exists (excluding current trainer)
        $where_clause = $exclude_trainer_id 
            ? $wpdb->prepare("WHERE slug = %s AND id != %d", $slug, $exclude_trainer_id)
            : $wpdb->prepare("WHERE slug = %s", $slug);
            
        $exists = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ptp_trainers {$where_clause}");
        
        if (!$exists) {
            return $slug;
        }
        
        // Add a number suffix to make unique
        $counter = 1;
        while ($counter < 100) {
            $new_slug = $slug . '-' . $counter;
            $where_clause = $exclude_trainer_id 
                ? $wpdb->prepare("WHERE slug = %s AND id != %d", $new_slug, $exclude_trainer_id)
                : $wpdb->prepare("WHERE slug = %s", $new_slug);
                
            $exists = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ptp_trainers {$where_clause}");
            
            if (!$exists) {
                return $new_slug;
            }
            $counter++;
        }
        
        // Fallback: add timestamp
        return $slug . '-' . time();
    }
    
    public static function update_stats($trainer_id) {
        global $wpdb;
        
        // Update total sessions
        $total_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ptp_bookings WHERE trainer_id = %d AND status = 'completed'",
            $trainer_id
        ));
        
        // Update total earnings
        $total_earnings = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(trainer_payout), 0) FROM {$wpdb->prefix}ptp_bookings WHERE trainer_id = %d AND status = 'completed'",
            $trainer_id
        ));
        
        // Update average rating
        $rating_data = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM {$wpdb->prefix}ptp_reviews WHERE trainer_id = %d AND is_published = 1",
            $trainer_id
        ));
        
        $wpdb->update(
            $wpdb->prefix . 'ptp_trainers',
            array(
                'total_sessions' => intval($total_sessions),
                'total_earnings' => floatval($total_earnings),
                'average_rating' => floatval($rating_data->avg_rating ?? 0),
                'review_count' => intval($rating_data->review_count ?? 0),
            ),
            array('id' => $trainer_id)
        );
    }
    
    public static function get_specialties_list() {
        return array(
            'ball_control' => 'Ball Control',
            'dribbling' => 'Dribbling',
            'passing' => 'Passing',
            'shooting' => 'Shooting',
            'finishing' => 'Finishing',
            'defending' => 'Defending',
            'goalkeeping' => 'Goalkeeping',
            'speed_agility' => 'Speed & Agility',
            'tactical' => 'Tactical IQ',
            'fitness' => 'Fitness & Conditioning',
            'mental' => 'Mental Training',
            '1v1' => '1v1 Training',
        );
    }
    
    public static function get_reviews($trainer_id, $limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, p.display_name as parent_name 
             FROM {$wpdb->prefix}ptp_reviews r
             JOIN {$wpdb->prefix}ptp_parents p ON r.parent_id = p.id
             WHERE r.trainer_id = %d AND r.is_published = 1
             ORDER BY r.created_at DESC
             LIMIT %d",
            $trainer_id, $limit
        ));
    }
    
    /**
     * Check if trainer profile is complete
     */
    public static function is_profile_complete($trainer) {
        $status = self::get_profile_completion_status($trainer);
        return $status['percentage'] >= 100;
    }
    
    /**
     * Get detailed profile completion status
     */
    public static function get_profile_completion_status($trainer) {
        $required_fields = array(
            'photo_url' => array('label' => 'Profile Photo', 'weight' => 20),
            'headline' => array('label' => 'Headline', 'weight' => 15),
            'bio' => array('label' => 'Bio', 'weight' => 20),
            'location' => array('label' => 'Location', 'weight' => 15),
            'hourly_rate' => array('label' => 'Hourly Rate', 'weight' => 10),
            'specialties' => array('label' => 'Specialties', 'weight' => 10),
            'college_or_team' => array('label' => 'College or Team', 'weight' => 10),
        );
        
        $completed = array();
        $missing = array();
        $total_weight = 0;
        $earned_weight = 0;
        
        foreach ($required_fields as $field => $info) {
            $total_weight += $info['weight'];
            
            // Special case for college_or_team - either one counts
            if ($field === 'college_or_team') {
                $is_complete = !empty($trainer->college) || !empty($trainer->team);
            } else {
                $value = $trainer->$field ?? '';
                $is_complete = !empty($value) && $value != '0' && $value != '0.00';
            }
            
            if ($is_complete) {
                $earned_weight += $info['weight'];
                $completed[] = $info['label'];
            } else {
                $missing[] = array(
                    'field' => $field,
                    'label' => $info['label'],
                    'weight' => $info['weight']
                );
            }
        }
        
        // Check availability separately (bonus)
        global $wpdb;
        $has_availability = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ptp_availability WHERE trainer_id = %d AND is_active = 1",
            $trainer->id
        ));
        
        return array(
            'percentage' => round(($earned_weight / $total_weight) * 100),
            'completed' => $completed,
            'missing' => $missing,
            'has_availability' => $has_availability > 0,
        );
    }
    
    /**
     * Check if this is a new trainer (just approved, never logged in to dashboard)
     */
    public static function is_new_trainer($trainer_id) {
        global $wpdb;
        return !get_user_meta(
            $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}ptp_trainers WHERE id = %d", $trainer_id)),
            'ptp_onboarding_completed',
            true
        );
    }
    
    /**
     * Mark trainer onboarding as complete
     */
    public static function complete_onboarding($trainer_id) {
        global $wpdb;
        $user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}ptp_trainers WHERE id = %d", $trainer_id));
        if ($user_id) {
            update_user_meta($user_id, 'ptp_onboarding_completed', current_time('mysql'));
        }
    }
}
