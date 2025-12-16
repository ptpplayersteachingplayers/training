<?php
/**
 * PTP Images Helper
 * Centralized management of all PTP media assets
 */

defined('ABSPATH') || exit;

class PTP_Images {
    
    /**
     * PTP Logo
     */
    const LOGO = 'https://ptpsummercamps.com/wp-content/uploads/2025/11/PTP-LOGO-2.png';
    
    /**
     * All PTP training photos - December 2025 shoot
     */
    const PHOTOS = array(
        'BG7A1915' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1915.jpg',
        'BG7A1899' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1899.jpg',
        'BG7A1886' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1886.jpg',
        'BG7A1874' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1874.jpg',
        'BG7A1847' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1847.jpg',
        'BG7A1804' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1804.jpg',
        'BG7A1797' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1797.jpg',
        'BG7A1790' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1790.jpg',
        'BG7A1787' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1787.jpg',
        'BG7A1730' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1730.jpg',
        'BG7A1642' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1642.jpg',
        'BG7A1596' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1596.jpg',
        'BG7A1595' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1595.jpg',
        'BG7A1563' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1563.jpg',
        'BG7A1539' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1539.jpg',
        'BG7A1520' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1520.jpg',
        'BG7A1463' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1463.jpg',
        'BG7A1403' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1403.jpg',
        'BG7A1393' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1393.jpg',
        'BG7A1356' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1356.jpg',
        'BG7A1347' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1347.jpg',
        'BG7A1288' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1288.jpg',
        'BG7A1283' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1283.jpg',
        'BG7A1281' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1281.jpg',
        'BG7A1279' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1279.jpg',
        'BG7A1278' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1278.jpg',
        'BG7A1272' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1272.jpg',
    );
    
    /**
     * Summer camp photos - September 2025
     */
    const CAMP_PHOTOS = array(
        'goal_celebration' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/august-18-soccer-camp-goal-celebration.jpg-scaled.jpg',
        'coaches_group' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coaches-july-16-group-photo.jpg.jpg',
        'coaches_feedback' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coaches-feedback-with-campers.jpg.jpg',
        'skills_clinic' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coach-drew-skills-clinic.jpg.jpg',
        'skill_show' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coach-drew-skill-show.jpg.jpg',
        '1v1_training' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coach-versus-ptp-player-1v1-soccer-training.jpg.jpg',
        'individual_training' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-individual-training-coach-luke.jpg.jpg',
        'workout' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coach-workout-cole-mcevoy.jpg.jpg',
        'long_ball' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coach-training-session-long-ball.jpg.jpg',
        'receiving' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-clinic-receiving-a-ball.jpg.jpg',
        'dribbling' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/soccer-dribbling-small-game-.jpg-scaled.jpg',
        'skills_1v1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-skills-clinic-1v1.jpg.jpg',
        'camp_group' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-july-16-summer-camp-group-photo.jpg.jpg',
        'winning_team' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coaches-versus-campers-winning-pic.jpg.jpg',
        'guest_isiah' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-guest-isiah-lefore-banner-photo-camp.jpg.jpg',
        'signing_gear' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-guests-signing-gear-3.jpg-scaled.jpg',
        'campers_group' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-guests-picture-summer-camp-5.jpg-scaled.jpg',
        'closing_ceremony' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-summer-camp-closing-cermony.jpg.jpg',
        'water_balloon' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-water-balloon-fight-coach-drew.jpg.jpg',
        'ritas_party' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-summer-camp-ritas-party.jpg.jpg',
    );
    
    /**
     * Recommended usage by context
     */
    const USAGE = array(
        'hero_trainers'     => 'BG7A1915',  // Find Trainers hero
        'hero_application'  => 'BG7A1642',  // Become a Trainer hero  
        'auth_login'        => 'BG7A1874',  // Login page side image
        'auth_register'     => 'BG7A1797',  // Register page side image
        'og_default'        => 'BG7A1915',  // Social sharing default
        'email_header'      => 'BG7A1899',  // Email header background
    );
    
    /**
     * Get logo URL
     */
    public static function logo() {
        return get_option('ptp_logo_url', self::LOGO);
    }
    
    /**
     * Get specific photo by key
     */
    public static function get($key) {
        return self::PHOTOS[$key] ?? self::PHOTOS['BG7A1915'];
    }
    
    /**
     * Get photo for specific usage context
     */
    public static function for_context($context) {
        $key = self::USAGE[$context] ?? 'BG7A1915';
        return self::get($key);
    }
    
    /**
     * Get random photo
     */
    public static function random() {
        $keys = array_keys(self::PHOTOS);
        return self::PHOTOS[$keys[array_rand($keys)]];
    }
    
    /**
     * Get all photos
     */
    public static function all() {
        return array_values(self::PHOTOS);
    }
    
    /**
     * Get default OG image
     */
    public static function og_image() {
        return get_option('ptp_default_og_image', self::for_context('og_default'));
    }
    
    /**
     * Generate avatar placeholder using UI Avatars
     */
    public static function avatar($name, $size = 200) {
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&size=' . $size . '&background=FCB900&color=0A0A0A&bold=true';
    }
    
    /**
     * Get trainer photo or avatar placeholder
     */
    public static function trainer_photo($trainer) {
        if (!empty($trainer->photo_url)) {
            return $trainer->photo_url;
        }
        return self::avatar($trainer->display_name, 400);
    }
}
