<?php
/**
 * Shortcodes Class - Embedded CSS in templates
 */

defined('ABSPATH') || exit;

class PTP_Shortcodes {
    
    private static $css_output = false;
    
    // PTP Images array
    public static $images = array(
        'hero' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1915.jpg',
        'hero2' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1899.jpg',
        'training1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1874.jpg',
        'training2' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1847.jpg',
        'action1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1797.jpg',
        'action2' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1790.jpg',
        'coach1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1595.jpg',
        'coach2' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1563.jpg',
        'drill1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1539.jpg',
        'drill2' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1520.jpg',
        'group1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1393.jpg',
        'group2' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1356.jpg',
        'skill1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1288.jpg',
        'skill2' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1283.jpg',
        'skill3' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1281.jpg',
        'skill4' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1279.jpg',
        'skill5' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1278.jpg',
        'skill6' => 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1272.jpg',
        'celebration' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/august-18-soccer-camp-goal-celebration.jpg-scaled.jpg',
        'signing1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-guests-signing-gear-3.jpg-scaled.jpg',
        'signing2' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-guests-signing-2.jpg-scaled.jpg',
        'campers1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-guests-picture-summer-camp-5.jpg-scaled.jpg',
        'campers2' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-guests-picture-summer-camp-4.jpg-scaled.jpg',
        'guest' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-guest-isiah-lefore-banner-photo-camp.jpg.jpg',
        'workout' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coach-workout-cole-mcevoy.jpg.jpg',
        '1v1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coach-versus-ptp-player-1v1-soccer-training.jpg.jpg',
        'longball' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coach-training-session-long-ball.jpg.jpg',
        'winning1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coaches-versus-camps-winning-pic-july-25.jpg-scaled.jpg',
        'winning2' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coaches-versus-campers-winning-pic.jpg.jpg',
        'coaches' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coaches-july-16-group-photo.jpg.jpg',
        'feedback' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coaches-feedback-with-campers.jpg.jpg',
        'drew1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coach-drew-skill-show.jpg.jpg',
        'drew2' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-coach-drew-skills-clinic.jpg.jpg',
        'receiving' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-clinic-receiving-a-ball.jpg.jpg',
        'dribbling' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/soccer-dribbling-small-game-.jpg-scaled.jpg',
        'ritas1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-summer-camp-ritas-party.jpg.jpg',
        'ritas2' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-summer-camp-eating-ritas-on-bench-group-pic.jpg.jpg',
        'closing' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-summer-camp-closing-cermony.jpg.jpg',
        'clinic1v1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-skills-clinic-1v1.jpg.jpg',
        'ritas3' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-receiving-ritas-on-hot-day-soccer-camp.jpg.jpg',
        'july16' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-july-16-summer-camp-group-photo.jpg.jpg',
        'individual1' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-individual-training-john-2.jpg.jpg',
        'individual2' => 'https://ptpsummercamps.com/wp-content/uploads/2025/09/ptp-individual-training-coach-luke.jpg.jpg',
    );
    
    public static function init() {
        add_shortcode('ptp_home', array(__CLASS__, 'home_page'));
        add_shortcode('ptp_trainers_grid', array(__CLASS__, 'trainers_grid'));
        add_shortcode('ptp_trainer_profile', array(__CLASS__, 'trainer_profile'));
        add_shortcode('ptp_book', array(__CLASS__, 'booking_form'));
        add_shortcode('ptp_booking_form', array(__CLASS__, 'booking_form'));
        add_shortcode('ptp_booking_confirmation', array(__CLASS__, 'booking_confirmation'));
        add_shortcode('ptp_login', array(__CLASS__, 'login'));
        add_shortcode('ptp_register', array(__CLASS__, 'register'));
        add_shortcode('ptp_apply', array(__CLASS__, 'trainer_application'));
        add_shortcode('ptp_trainer_application', array(__CLASS__, 'trainer_application'));
        add_shortcode('ptp_debug', array(__CLASS__, 'debug_info'));
        add_shortcode('ptp_trainer_dashboard', array(__CLASS__, 'trainer_dashboard'));
        add_shortcode('ptp_trainer_onboarding', array(__CLASS__, 'trainer_onboarding'));
        add_shortcode('ptp_parent_dashboard', array(__CLASS__, 'parent_dashboard'));
        add_shortcode('ptp_my_training', array(__CLASS__, 'my_training'));
        add_shortcode('ptp_messaging', array(__CLASS__, 'messaging'));
        add_shortcode('ptp_account', array(__CLASS__, 'account'));
        add_shortcode('ptp_find_trainers', array(__CLASS__, 'trainers_grid'));
        
        // Advanced features
        add_shortcode('ptp_player_progress', array(__CLASS__, 'player_progress'));
        add_shortcode('ptp_training_plans', array(__CLASS__, 'training_plans'));
        add_shortcode('ptp_group_sessions', array(__CLASS__, 'group_sessions'));
    }
    
    /**
     * Output embedded CSS - call at start of each template
     */
    public static function embed_css() {
        if (self::$css_output) return '';
        self::$css_output = true;
        
        return '<style id="ptp-embedded-css">
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap");
.ptp-wrap{font-family:Inter,-apple-system,BlinkMacSystemFont,sans-serif!important;color:#111827!important;line-height:1.6!important;-webkit-font-smoothing:antialiased!important;box-sizing:border-box!important}
.ptp-wrap *,.ptp-wrap *::before,.ptp-wrap *::after{box-sizing:border-box!important}
.ptp-wrap a{text-decoration:none!important;color:inherit!important}
.ptp-wrap h1,.ptp-wrap h2,.ptp-wrap h3,.ptp-wrap h4{font-family:Inter,sans-serif!important;font-weight:800!important;line-height:1.1!important;letter-spacing:-0.02em!important;margin:0!important;padding:0!important}
.ptp-wrap p{margin:0!important}
.ptp-container{max-width:1280px!important;margin:0 auto!important;padding:0 24px!important}
.ptp-btn{display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:8px!important;padding:14px 28px!important;font-family:Inter,sans-serif!important;font-size:15px!important;font-weight:600!important;letter-spacing:-0.01em!important;border-radius:12px!important;border:none!important;cursor:pointer!important;transition:all 0.2s ease!important;text-decoration:none!important;line-height:1!important}
.ptp-btn-primary{background:#FCB900!important;color:#0E0F11!important}
.ptp-btn-primary:hover{background:#E5A800!important;transform:translateY(-2px)!important;box-shadow:0 8px 20px rgba(252,185,0,0.3)!important}
.ptp-btn-dark{background:#0E0F11!important;color:#FFFFFF!important}
.ptp-btn-dark:hover{background:#1F2937!important}
.ptp-btn-outline{background:transparent!important;border:2px solid #E5E7EB!important;color:#374151!important}
.ptp-btn-outline:hover{border-color:#0E0F11!important;color:#0E0F11!important}
.ptp-btn-white{background:#FFFFFF!important;color:#0E0F11!important}
.ptp-btn-sm{padding:10px 18px!important;font-size:14px!important}
.ptp-btn-lg{padding:18px 36px!important;font-size:17px!important}
.ptp-hero{position:relative!important;min-height:100vh!important;display:flex!important;align-items:center!important;justify-content:center!important;padding:120px 24px!important;background:#0E0F11!important;overflow:hidden!important;margin:0!important}
.ptp-hero-bg{position:absolute!important;top:0!important;left:0!important;right:0!important;bottom:0!important;background-size:cover!important;background-position:center!important;opacity:0.5!important}
.ptp-hero-overlay{position:absolute!important;top:0!important;left:0!important;right:0!important;bottom:0!important;background:linear-gradient(to bottom,rgba(14,15,17,0.4),rgba(14,15,17,0.9))!important}
.ptp-hero-content{position:relative!important;z-index:2!important;text-align:center!important;max-width:800px!important}
.ptp-hero-badge{display:inline-flex!important;align-items:center!important;gap:8px!important;background:rgba(252,185,0,0.15)!important;color:#FCB900!important;padding:8px 16px!important;border-radius:50px!important;font-size:14px!important;font-weight:600!important;margin-bottom:24px!important}
.ptp-hero-title{font-size:64px!important;font-weight:900!important;color:#FFFFFF!important;margin-bottom:24px!important;line-height:1.05!important;letter-spacing:-0.03em!important}
.ptp-hero-title span{color:#FCB900!important}
.ptp-hero-subtitle{font-size:20px!important;color:#9CA3AF!important;margin-bottom:48px!important;font-weight:400!important;line-height:1.6!important}
.ptp-location-search{background:#FFFFFF!important;border-radius:16px!important;padding:24px!important;max-width:480px!important;margin:0 auto 48px!important;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25)!important}
.ptp-location-search h3{font-size:18px!important;font-weight:700!important;color:#0E0F11!important;margin-bottom:16px!important;text-align:center!important}
.ptp-location-input-wrap{display:flex!important;gap:12px!important}
.ptp-location-input{flex:1!important;padding:16px 20px!important;border:2px solid #E5E7EB!important;border-radius:12px!important;font-family:Inter,sans-serif!important;font-size:16px!important;outline:none!important;transition:border-color 0.2s!important}
.ptp-location-input:focus{border-color:#FCB900!important}
.ptp-location-btn{padding:16px 24px!important;background:#FCB900!important;color:#0E0F11!important;border:none!important;border-radius:12px!important;font-family:Inter,sans-serif!important;font-size:15px!important;font-weight:600!important;cursor:pointer!important;transition:all 0.2s!important;white-space:nowrap!important}
.ptp-location-btn:hover{background:#E5A800!important}
.ptp-location-or{text-align:center!important;color:#6B7280!important;font-size:14px!important;margin:16px 0!important}
.ptp-use-location{display:flex!important;align-items:center!important;justify-content:center!important;gap:8px!important;color:#0E0F11!important;font-weight:600!important;cursor:pointer!important;padding:12px!important;border-radius:8px!important;transition:background 0.2s!important}
.ptp-use-location:hover{background:#F3F4F6!important}
.ptp-trust-badges{display:flex!important;justify-content:center!important;gap:24px!important;flex-wrap:wrap!important}
.ptp-trust-badge{display:flex!important;align-items:center!important;gap:8px!important;color:rgba(255,255,255,0.8)!important;font-size:14px!important;font-weight:500!important}
.ptp-trust-badge svg{width:20px!important;height:20px!important;color:#FCB900!important}
.ptp-section{padding:100px 0!important;margin:0!important}
.ptp-section-dark{background:#0E0F11!important;color:#FFFFFF!important}
.ptp-section-gray{background:#F9FAFB!important}
.ptp-section-header{display:flex!important;justify-content:space-between!important;align-items:flex-end!important;margin-bottom:48px!important;gap:24px!important;flex-wrap:wrap!important}
.ptp-section-title{font-size:42px!important;font-weight:800!important;margin-bottom:12px!important;letter-spacing:-0.02em!important}
.ptp-section-subtitle{color:#6B7280!important;font-size:18px!important;font-weight:400!important}
.ptp-section-dark .ptp-section-subtitle{color:#9CA3AF!important}
.ptp-trainers-section{padding:100px 0!important;background:#FFFFFF!important}
.ptp-trainers-grid{display:grid!important;grid-template-columns:repeat(auto-fill,minmax(320px,1fr))!important;gap:28px!important}
.ptp-trainer-card{background:#FFFFFF!important;border-radius:20px!important;overflow:hidden!important;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1)!important;border:1px solid #F3F4F6!important;transition:all 0.3s ease!important;display:block!important}
.ptp-trainer-card:hover{transform:translateY(-8px)!important;box-shadow:0 25px 50px -12px rgba(0,0,0,0.15)!important}
.ptp-trainer-image{position:relative!important;height:280px!important;overflow:hidden!important;background:#F3F4F6!important}
.ptp-trainer-image img{width:100%!important;height:100%!important;object-fit:cover!important;transition:transform 0.3s ease!important}
.ptp-trainer-card:hover .ptp-trainer-image img{transform:scale(1.05)!important}
.ptp-trainer-badges{position:absolute!important;top:16px!important;left:16px!important;display:flex!important;gap:8px!important;flex-wrap:wrap!important}
.ptp-badge{padding:6px 12px!important;border-radius:50px!important;font-size:12px!important;font-weight:600!important}
.ptp-badge-featured{background:#FCB900!important;color:#0E0F11!important}
.ptp-badge-verified{background:#10B981!important;color:#FFFFFF!important}
.ptp-badge-new{background:#3B82F6!important;color:#FFFFFF!important}
.ptp-trainer-info{padding:24px!important}
.ptp-trainer-rating{display:flex!important;align-items:center!important;gap:6px!important;font-size:15px!important;font-weight:600!important;margin-bottom:10px!important}
.ptp-trainer-rating .star{color:#FCB900!important}
.ptp-trainer-rating .count{color:#9CA3AF!important;font-weight:400!important}
.ptp-trainer-name{font-size:20px!important;font-weight:700!important;margin-bottom:6px!important;color:#0E0F11!important}
.ptp-trainer-headline{color:#4B5563!important;font-size:15px!important;margin-bottom:10px!important}
.ptp-trainer-location{color:#6B7280!important;font-size:14px!important;margin-bottom:6px!important;display:flex!important;align-items:center!important;gap:6px!important}
.ptp-trainer-availability{color:#10B981!important;font-size:14px!important;font-weight:500!important;margin-bottom:20px!important}
.ptp-trainer-meta{display:flex!important;justify-content:space-between!important;align-items:center!important;padding-top:20px!important;border-top:1px solid #F3F4F6!important}
.ptp-trainer-price{font-size:28px!important;font-weight:800!important;color:#0E0F11!important}
.ptp-trainer-price span{font-size:15px!important;font-weight:400!important;color:#6B7280!important}
.ptp-empty-state{text-align:center!important;padding:80px 24px!important;background:#F9FAFB!important;border-radius:20px!important}
.ptp-empty-icon{width:100px!important;height:100px!important;margin:0 auto 24px!important;background:#FFFFFF!important;border-radius:50%!important;display:flex!important;align-items:center!important;justify-content:center!important;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1)!important}
.ptp-empty-title{font-size:28px!important;font-weight:700!important;margin-bottom:12px!important}
.ptp-empty-text{color:#6B7280!important;margin-bottom:24px!important;font-size:16px!important}
.ptp-stats-row{display:grid!important;grid-template-columns:repeat(3,1fr)!important;gap:48px!important;max-width:900px!important;margin:0 auto!important;text-align:center!important}
.ptp-stat-value{font-size:64px!important;font-weight:900!important;color:#FCB900!important;line-height:1!important;margin-bottom:12px!important;letter-spacing:-0.02em!important}
.ptp-stat-label{color:#6B7280!important;font-size:16px!important;font-weight:500!important}
.ptp-section-dark .ptp-stat-label{color:#9CA3AF!important}
.ptp-gallery{display:grid!important;grid-template-columns:repeat(4,1fr)!important;gap:16px!important}
.ptp-gallery-item{aspect-ratio:1!important;border-radius:16px!important;overflow:hidden!important}
.ptp-gallery-item img{width:100%!important;height:100%!important;object-fit:cover!important;transition:transform 0.3s!important}
.ptp-gallery-item:hover img{transform:scale(1.1)!important}
.ptp-auth-page{min-height:100vh!important;display:flex!important;align-items:center!important;justify-content:center!important;padding:40px 24px!important;background:#F9FAFB!important}
.ptp-auth-box{width:100%!important;max-width:440px!important;background:#FFFFFF!important;border-radius:24px!important;box-shadow:0 25px 50px -12px rgba(0,0,0,0.1)!important;padding:48px 40px!important}
.ptp-auth-logo{text-align:center!important;margin-bottom:32px!important}
.ptp-auth-title{font-size:32px!important;font-weight:800!important;text-align:center!important;margin-bottom:8px!important}
.ptp-auth-subtitle{text-align:center!important;color:#6B7280!important;margin-bottom:32px!important;font-size:16px!important}
.ptp-auth-footer{text-align:center!important;margin-top:24px!important;color:#6B7280!important;font-size:15px!important}
.ptp-auth-footer a{color:#0E0F11!important;font-weight:600!important}
.ptp-auth-divider{display:flex!important;align-items:center!important;gap:16px!important;margin:24px 0!important;color:#9CA3AF!important;font-size:14px!important}
.ptp-auth-divider::before,.ptp-auth-divider::after{content:""!important;flex:1!important;height:1px!important;background:#E5E7EB!important}
.ptp-form-group{margin-bottom:20px!important}
.ptp-form-label{display:block!important;font-size:14px!important;font-weight:600!important;color:#374151!important;margin-bottom:8px!important}
.ptp-form-input,.ptp-form-select,.ptp-form-textarea{width:100%!important;padding:14px 16px!important;border:2px solid #E5E7EB!important;border-radius:12px!important;font-family:Inter,sans-serif!important;font-size:16px!important;transition:border-color 0.2s!important;background:#FFFFFF!important;color:#111827!important;outline:none!important}
.ptp-form-input:focus,.ptp-form-select:focus,.ptp-form-textarea:focus{border-color:#FCB900!important;box-shadow:0 0 0 3px rgba(252,185,0,0.1)!important}
.ptp-form-row{display:grid!important;grid-template-columns:1fr 1fr!important;gap:16px!important}
.ptp-form-hint{font-size:13px!important;color:#6B7280!important;margin-top:6px!important}
.ptp-card{background:#FFFFFF!important;border-radius:20px!important;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1)!important;border:1px solid #F3F4F6!important;overflow:hidden!important}
.ptp-card-header{padding:24px!important;border-bottom:1px solid #F3F4F6!important}
.ptp-card-header-dark{background:#0E0F11!important;color:#FFFFFF!important;border-bottom:none!important}
.ptp-card-title{font-size:18px!important;font-weight:700!important;margin:0!important}
.ptp-card-body{padding:24px!important}
.ptp-dashboard{background:#F9FAFB!important;min-height:100vh!important;padding:32px 0!important}
.ptp-dashboard-header{background:#0E0F11!important;padding:40px!important;border-radius:24px!important;margin-bottom:32px!important;color:#FFFFFF!important}
.ptp-dashboard-title{font-size:36px!important;font-weight:800!important;margin-bottom:8px!important;color:#FFFFFF!important}
.ptp-dashboard-subtitle{color:#9CA3AF!important;font-size:16px!important}
.ptp-stat-cards{display:grid!important;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))!important;gap:24px!important}
.ptp-stat-card{background:#FFFFFF!important;border-radius:20px!important;padding:28px!important;border:1px solid #F3F4F6!important}
.ptp-stat-card-icon{width:56px!important;height:56px!important;border-radius:14px!important;display:flex!important;align-items:center!important;justify-content:center!important;margin-bottom:20px!important}
.ptp-stat-card-icon.yellow{background:#FEF3C7!important}
.ptp-stat-card-icon.green{background:#D1FAE5!important}
.ptp-stat-card-icon.blue{background:#DBEAFE!important}
.ptp-stat-card-value{font-size:36px!important;font-weight:800!important;color:#0E0F11!important;margin-bottom:4px!important}
.ptp-stat-card-label{color:#6B7280!important;font-size:15px!important}
.ptp-table-wrap{overflow-x:auto!important}
.ptp-table{width:100%!important;border-collapse:collapse!important}
.ptp-table th{text-align:left!important;padding:14px 16px!important;font-size:13px!important;font-weight:600!important;text-transform:uppercase!important;letter-spacing:0.5px!important;color:#6B7280!important;border-bottom:1px solid #E5E7EB!important}
.ptp-table td{padding:18px 16px!important;border-bottom:1px solid #F3F4F6!important}
.ptp-table tr:hover td{background:#F9FAFB!important}
.ptp-status{display:inline-flex!important;align-items:center!important;padding:6px 12px!important;border-radius:50px!important;font-size:13px!important;font-weight:600!important}
.ptp-status-confirmed,.ptp-status-active,.ptp-status-completed{background:#D1FAE5!important;color:#065F46!important}
.ptp-status-pending{background:#FEF3C7!important;color:#92400E!important}
.ptp-status-cancelled,.ptp-status-rejected{background:#FEE2E2!important;color:#991B1B!important}
.ptp-alert{padding:16px 20px!important;border-radius:12px!important;margin-bottom:20px!important;font-weight:500!important}
.ptp-alert-success{background:#D1FAE5!important;color:#065F46!important}
.ptp-alert-error{background:#FEE2E2!important;color:#991B1B!important}
.ptp-alert-warning{background:#FEF3C7!important;color:#92400E!important}
.ptp-alert-info{background:#DBEAFE!important;color:#1E40AF!important}
.ptp-filters{display:flex!important;gap:12px!important}
.ptp-filter-select{padding:12px 16px!important;border:2px solid #E5E7EB!important;border-radius:12px!important;font-family:Inter,sans-serif!important;font-size:14px!important;font-weight:500!important;background:#FFFFFF!important;cursor:pointer!important;min-width:160px!important}
@media(max-width:768px){.ptp-hero{padding:80px 16px!important;min-height:auto!important}.ptp-hero-title{font-size:40px!important}.ptp-hero-subtitle{font-size:17px!important}.ptp-location-input-wrap{flex-direction:column!important}.ptp-trust-badges{gap:16px!important}.ptp-section{padding:60px 0!important}.ptp-section-header{flex-direction:column!important;text-align:center!important}.ptp-section-title{font-size:32px!important}.ptp-trainers-grid{grid-template-columns:1fr!important}.ptp-stats-row{grid-template-columns:1fr!important;gap:32px!important}.ptp-stat-value{font-size:48px!important}.ptp-gallery{grid-template-columns:repeat(2,1fr)!important}.ptp-form-row{grid-template-columns:1fr!important}.ptp-auth-box{padding:32px 24px!important}.ptp-container{padding:0 16px!important}}
</style>';
    }
    
    public static function home_page($atts) {
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/home.php';
        return ob_get_clean();
    }
    
    public static function trainers_grid($atts) {
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/trainers-grid.php';
        return ob_get_clean();
    }
    
    public static function trainer_profile($atts) {
        // Try multiple methods to get the trainer identifier
        $trainer_param = '';
        $debug_info = array();
        
        // Method 1: Query var from rewrite rule
        $trainer_param = get_query_var('trainer_slug');
        if ($trainer_param) $debug_info[] = 'query_var: ' . $trainer_param;
        
        // Method 2: GET parameter (?trainer=slug or ?trainer=123)
        if (empty($trainer_param)) {
            $trainer_param = isset($_GET['trainer']) ? sanitize_text_field($_GET['trainer']) : '';
            if ($trainer_param) $debug_info[] = 'get_param: ' . $trainer_param;
        }
        
        // Method 3: Parse from pretty URL /trainer/slug/
        if (empty($trainer_param)) {
            $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
            if (preg_match('#trainer/([^/]+)/?$#', $path, $matches)) {
                $trainer_param = sanitize_text_field($matches[1]);
                if ($trainer_param) $debug_info[] = 'url_path: ' . $trainer_param;
            }
        }
        
        // Method 4: Check for trainer ID in path /trainer-profile/123
        if (empty($trainer_param)) {
            $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
            if (preg_match('#trainer-profile/(\d+)/?$#', $path, $matches)) {
                $trainer_param = sanitize_text_field($matches[1]);
                if ($trainer_param) $debug_info[] = 'profile_path: ' . $trainer_param;
            }
        }
        
        // Method 5: Check URL for any trainer-like segment
        if (empty($trainer_param)) {
            $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
            $segments = explode('/', $path);
            foreach ($segments as $i => $segment) {
                if ($segment === 'trainer' && isset($segments[$i + 1]) && !empty($segments[$i + 1])) {
                    $trainer_param = sanitize_text_field($segments[$i + 1]);
                    if ($trainer_param) $debug_info[] = 'segment: ' . $trainer_param;
                    break;
                }
            }
        }
        
        if (empty($trainer_param)) {
            error_log('PTP Trainer Profile: No trainer parameter found. URL: ' . $_SERVER['REQUEST_URI']);
            return '<div class="ptp-wrap">' . self::embed_css() . '
                <div style="padding: 40px; text-align: center;">
                    <h2 style="margin-bottom: 16px;">Trainer Not Found</h2>
                    <p style="color: #6B7280;">Please select a trainer from our directory.</p>
                    <a href="' . esc_url(home_url('/find-trainers/')) . '" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background: #FCB900; color: #0A0A0A; font-weight: 600; border-radius: 8px; text-decoration: none;">Find a Trainer</a>
                </div>
            </div>';
        }
        
        // Try to find trainer by ID first, then slug
        $trainer = null;
        
        // If numeric, try as ID
        if (is_numeric($trainer_param)) {
            $trainer = PTP_Trainer::get(intval($trainer_param));
            if ($trainer) $debug_info[] = 'found_by_id';
        }
        
        // Try exact slug match
        if (!$trainer) {
            $trainer = PTP_Trainer::get_by_slug($trainer_param);
            if ($trainer) $debug_info[] = 'found_by_slug';
        }
        
        // Try with sanitized slug (handles URL encoding)
        if (!$trainer) {
            $trainer = PTP_Trainer::get_by_slug(sanitize_title($trainer_param));
            if ($trainer) $debug_info[] = 'found_by_sanitized_slug';
        }
        
        // Try URL decoded version
        if (!$trainer) {
            $trainer = PTP_Trainer::get_by_slug(urldecode($trainer_param));
            if ($trainer) $debug_info[] = 'found_by_decoded_slug';
        }
        
        // Log debug info for troubleshooting
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('PTP Trainer Profile Debug: ' . implode(', ', $debug_info));
        }
        
        if (!$trainer) {
            error_log('PTP Trainer Profile: Trainer not found for param: ' . $trainer_param . ' | Debug: ' . implode(', ', $debug_info));
            return '<div class="ptp-wrap">' . self::embed_css() . '
                <div style="padding: 40px; text-align: center;">
                    <h2 style="margin-bottom: 16px;">Trainer Not Found</h2>
                    <p style="color: #6B7280;">This trainer may no longer be available or the link may be incorrect.</p>
                    <a href="' . esc_url(home_url('/find-trainers/')) . '" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background: #FCB900; color: #0A0A0A; font-weight: 600; border-radius: 8px; text-decoration: none;">Browse All Trainers</a>
                </div>
            </div>';
        }
        
        if ($trainer->status !== 'active') {
            error_log('PTP Trainer Profile: Trainer ' . $trainer_param . ' (ID: ' . $trainer->id . ') status is: ' . $trainer->status);
            return '<div class="ptp-wrap">' . self::embed_css() . '
                <div style="padding: 40px; text-align: center;">
                    <h2 style="margin-bottom: 16px;">Trainer Unavailable</h2>
                    <p style="color: #6B7280;">This trainer is not currently accepting bookings.</p>
                    <a href="' . esc_url(home_url('/find-trainers/')) . '" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background: #FCB900; color: #0A0A0A; font-weight: 600; border-radius: 8px; text-decoration: none;">Find Another Trainer</a>
                </div>
            </div>';
        }
        
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/trainer-profile.php';
        return ob_get_clean();
    }
    
    public static function booking_form($atts) {
        $css = self::embed_css();
        
        // Accept either trainer ID (?trainer=123) or slug (?trainer=first-last).
        $trainer_param = isset($_GET['trainer']) ? sanitize_text_field($_GET['trainer']) : '';
        if (empty($trainer_param)) {
            return '<div class="ptp-wrap">' . $css . '<p>Please select a trainer first.</p></div>';
        }

        if (is_numeric($trainer_param)) {
            $trainer = PTP_Trainer::get(intval($trainer_param));
        } else {
            $trainer = PTP_Trainer::get_by_slug($trainer_param);
        }

        if (!$trainer || $trainer->status !== 'active') {
            return '<div class="ptp-wrap">' . $css . '<p>Trainer not found.</p></div>';
        }
        
        // Check if user is logged in
        $is_guest = !is_user_logged_in();
        $parent = null;
        $players = array();
        
        if (!$is_guest) {
            $parent = PTP_Parent::get_by_user_id(get_current_user_id());
            if ($parent) {
                $players = PTP_Player::get_by_parent($parent->id);
            }
        }
        
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/booking-form.php';
        return ob_get_clean();
    }
    
    public static function booking_confirmation($atts) {
        $css = self::embed_css();
        $booking_number = sanitize_text_field($_GET['booking'] ?? '');
        if (!$booking_number) {
            return '<div class="ptp-wrap">' . $css . '<p>Booking not found.</p></div>';
        }
        
        $booking = PTP_Booking::get_by_number($booking_number);
        if (!$booking) {
            return '<div class="ptp-wrap">' . $css . '<p>Booking not found.</p></div>';
        }
        
        $booking = PTP_Booking::get_full($booking->id);
        
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/booking-confirmation.php';
        return ob_get_clean();
    }
    
    public static function my_training($atts) {
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/login/'));
            exit;
        }
        
        $parent = PTP_Parent::get_by_user_id(get_current_user_id());
        if (!$parent) {
            $user = wp_get_current_user();
            $parent_id = PTP_Parent::create(get_current_user_id(), array(
                'display_name' => $user->display_name,
            ));
            $parent = PTP_Parent::get($parent_id);
        }
        
        $players = PTP_Player::get_by_parent($parent->id);
        $upcoming = PTP_Parent::get_upcoming_sessions($parent->id);
        $history = PTP_Parent::get_past_sessions($parent->id);
        
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/my-training.php';
        return ob_get_clean();
    }
    
    public static function trainer_dashboard($atts) {
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/login/'));
            exit;
        }
        
        $current_user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        
        $trainer = PTP_Trainer::get_by_user_id($current_user_id);
        if (!$trainer) {
            // No trainer record - check if they have the trainer role (means they applied)
            if (in_array('ptp_trainer', (array) $current_user->roles)) {
                // They have the role but no trainer record = pending approval
                // Show pending page
                ob_start();
                include PTP_PLUGIN_DIR . 'templates/trainer-pending.php';
                return ob_get_clean();
            }
            
            // No role and no record - they haven't applied
            wp_redirect(home_url('/apply/'));
            exit;
        }
        
        if (PTP_Trainer::is_new_trainer($trainer->id)) {
            wp_redirect(home_url('/trainer-onboarding/'));
            exit;
        }
        
        // Safely get data with fallbacks
        $upcoming = array();
        $pending_confirmations = array();
        $earnings = array('this_month' => 0, 'total_earnings' => 0, 'pending_payout' => 0);
        $availability = array();
        $completion = array('percentage' => 100, 'missing' => array());
        
        if (class_exists('PTP_Booking')) {
            if (method_exists('PTP_Booking', 'get_trainer_bookings')) {
                $upcoming = PTP_Booking::get_trainer_bookings($trainer->id, null, true) ?: array();
            }
            if (method_exists('PTP_Booking', 'get_pending_confirmations')) {
                $pending_confirmations = PTP_Booking::get_pending_confirmations($trainer->id) ?: array();
            }
        }
        
        if (class_exists('PTP_Payments') && method_exists('PTP_Payments', 'get_trainer_earnings')) {
            $earnings = PTP_Payments::get_trainer_earnings($trainer->id) ?: $earnings;
        }
        
        if (class_exists('PTP_Availability') && method_exists('PTP_Availability', 'get_weekly')) {
            $availability = PTP_Availability::get_weekly($trainer->id) ?: array();
        }
        
        if (method_exists('PTP_Trainer', 'get_profile_completion_status')) {
            $completion = PTP_Trainer::get_profile_completion_status($trainer) ?: $completion;
        }
        
        $show_welcome = isset($_GET['welcome']) && $_GET['welcome'] == '1';
        
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/trainer-dashboard.php';
        return ob_get_clean();
    }
    
    public static function trainer_onboarding($atts) {
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/login/'));
            exit;
        }
        
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        if (!$trainer) {
            wp_redirect(home_url('/apply/'));
            exit;
        }
        
        if (!PTP_Trainer::is_new_trainer($trainer->id) && !isset($_GET['edit'])) {
            wp_redirect(home_url('/trainer-dashboard/'));
            exit;
        }
        
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/trainer-onboarding.php';
        return ob_get_clean();
    }
    
    public static function messaging($atts) {
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/login/'));
            exit;
        }
        
        $conversations = PTP_Messaging::get_conversations_for_user(get_current_user_id());
        $active_conversation = null;
        $messages = array();
        
        if (!empty($_GET['conversation'])) {
            $conversation_id = intval($_GET['conversation']);
            $active_conversation = PTP_Messaging::get_conversation($conversation_id);
            if ($active_conversation) {
                $messages = PTP_Messaging::get_messages($conversation_id);
                PTP_Messaging::mark_as_read($conversation_id, get_current_user_id());
            }
        } elseif (!empty($_GET['trainer'])) {
            $trainer_id = intval($_GET['trainer']);
            $parent = PTP_Parent::get_by_user_id(get_current_user_id());
            if ($parent) {
                $active_conversation = PTP_Messaging::get_or_create_conversation($trainer_id, $parent->id);
                $messages = PTP_Messaging::get_messages($active_conversation->id);
            }
        }
        
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/messaging.php';
        return ob_get_clean();
    }
    
    public static function account($atts) {
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/login/'));
            exit;
        }
        
        $user = wp_get_current_user();
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        $parent = PTP_Parent::get_by_user_id(get_current_user_id());
        
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/account.php';
        return ob_get_clean();
    }
    
    public static function login($atts) {
        // Redirect non-admin logged-in users to dashboard
        if (is_user_logged_in() && !current_user_can('manage_options')) {
            wp_redirect(PTP_User::get_dashboard_url());
            exit;
        }
        
        // Template handles everything including full HTML output
        include PTP_PLUGIN_DIR . 'templates/login.php';
        return ''; // Template calls exit, this won't be reached for logged-out users
    }
    
    public static function register($atts) {
        // Redirect non-admin logged-in users to dashboard
        if (is_user_logged_in() && !current_user_can('manage_options')) {
            wp_redirect(PTP_User::get_dashboard_url());
            exit;
        }
        
        // Template handles everything including full HTML output
        include PTP_PLUGIN_DIR . 'templates/register.php';
        return ''; // Template calls exit, this won't be reached for logged-out users
    }
    
    public static function trainer_application($atts) {
        // If logged in, check for existing application
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
        
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/apply.php';
        return ob_get_clean();
    }
    
    public static function parent_dashboard($atts) {
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/parent-dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * Debug shortcode - shows system status
     */
    public static function debug_info($atts) {
        if (!current_user_can('manage_options')) {
            return '<p style="color: red;">Debug info is only visible to administrators.</p>';
        }
        
        global $wpdb;
        
        $output = '<div style="font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;">';
        $output .= '<h3 style="margin-top: 0;">PTP Training Platform - Debug Info</h3>';
        
        // Check tables
        $tables = array(
            'ptp_applications',
            'ptp_trainers',
            'ptp_parents',
            'ptp_bookings',
        );
        
        $output .= '<h4>Database Tables:</h4><ul>';
        foreach ($tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") === $full_table;
            $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $full_table") : 'N/A';
            $status = $exists ? '✅' : '❌';
            $output .= "<li>{$status} <strong>{$table}</strong>: " . ($exists ? "exists ({$count} rows)" : "MISSING") . "</li>";
        }
        $output .= '</ul>';
        
        // Check AJAX actions
        $output .= '<h4>AJAX Actions Registered:</h4><ul>';
        $ajax_actions = array(
            'ptp_submit_application' => has_action('wp_ajax_ptp_submit_application'),
            'ptp_submit_application (nopriv)' => has_action('wp_ajax_nopriv_ptp_submit_application'),
            'ptp_login (nopriv)' => has_action('wp_ajax_nopriv_ptp_login'),
            'ptp_register (nopriv)' => has_action('wp_ajax_nopriv_ptp_register'),
        );
        foreach ($ajax_actions as $action => $registered) {
            $status = $registered ? '✅' : '❌';
            $output .= "<li>{$status} {$action}</li>";
        }
        $output .= '</ul>';
        
        // Check if scripts are enqueued
        $output .= '<h4>Scripts Status:</h4><ul>';
        $output .= '<li>ptp_ajax URL: ' . admin_url('admin-ajax.php') . '</li>';
        $output .= '<li>Plugin Version: ' . PTP_VERSION . '</li>';
        $output .= '<li>WordPress Version: ' . get_bloginfo('version') . '</li>';
        $output .= '</ul>';
        
        // Recent applications
        $apps_table = $wpdb->prefix . 'ptp_applications';
        if ($wpdb->get_var("SHOW TABLES LIKE '$apps_table'") === $apps_table) {
            $recent = $wpdb->get_results("SELECT id, name, email, status, created_at FROM $apps_table ORDER BY created_at DESC LIMIT 5");
            $output .= '<h4>Recent Applications:</h4>';
            if ($recent) {
                $output .= '<table style="width: 100%; border-collapse: collapse; font-size: 12px;">';
                $output .= '<tr style="background: #ddd;"><th style="padding: 5px; text-align: left;">ID</th><th style="padding: 5px; text-align: left;">Name</th><th style="padding: 5px; text-align: left;">Email</th><th style="padding: 5px; text-align: left;">Status</th><th style="padding: 5px; text-align: left;">Date</th></tr>';
                foreach ($recent as $app) {
                    $output .= "<tr><td style='padding: 5px;'>{$app->id}</td><td style='padding: 5px;'>{$app->name}</td><td style='padding: 5px;'>{$app->email}</td><td style='padding: 5px;'>{$app->status}</td><td style='padding: 5px;'>{$app->created_at}</td></tr>";
                }
                $output .= '</table>';
            } else {
                $output .= '<p>No applications found.</p>';
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Player Progress Report (for parents)
     */
    public static function player_progress($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ptp-alert ptp-alert-warning">Please <a href="' . home_url('/login/') . '">log in</a> to view player progress.</div>';
        }
        
        self::output_css();
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/player-progress.php';
        return ob_get_clean();
    }
    
    /**
     * Training Plans Management (for trainers)
     */
    public static function training_plans($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ptp-alert ptp-alert-warning">Please <a href="' . home_url('/login/') . '">log in</a> to access training plans.</div>';
        }
        
        $trainer = PTP_Trainer::get_by_user_id(get_current_user_id());
        if (!$trainer) {
            return '<div class="ptp-alert ptp-alert-error">This page is for trainers only.</div>';
        }
        
        self::output_css();
        ob_start();
        include PTP_PLUGIN_DIR . 'templates/training-plans.php';
        return ob_get_clean();
    }
    
    /**
     * Group Sessions Browser
     */
    public static function group_sessions($atts) {
        $atts = shortcode_atts(array(
            'trainer' => '',
            'limit' => 12,
        ), $atts);
        
        self::output_css();
        
        $filters = array('limit' => intval($atts['limit']));
        if ($atts['trainer']) {
            global $wpdb;
            $trainer = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ptp_trainers WHERE slug = %s",
                sanitize_text_field($atts['trainer'])
            ));
            if ($trainer) {
                $filters['trainer_id'] = $trainer->id;
            }
        }
        
        $sessions = PTP_Groups::get_open_sessions($filters);
        
        ob_start();
        ?>
        <div class="ptp-group-sessions">
            <?php if (empty($sessions)): ?>
            <div class="ptp-empty-state" style="text-align: center; padding: 60px 20px;">
                <div style="font-size: 48px; margin-bottom: 16px;">👥</div>
                <h3 style="margin: 0 0 8px;">No Group Sessions Available</h3>
                <p style="color: var(--ptp-gray-500);">Check back soon for upcoming group training sessions.</p>
            </div>
            <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px;">
                <?php foreach ($sessions as $session): ?>
                <div class="ptp-card" style="overflow: hidden;">
                    <div style="padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                            <div>
                                <h3 style="margin: 0 0 4px; font-size: 18px;"><?php echo esc_html($session->title); ?></h3>
                                <p style="margin: 0; font-size: 14px; color: var(--ptp-gray-500);">with <?php echo esc_html($session->trainer_name); ?></p>
                            </div>
                            <span class="ptp-badge ptp-badge-<?php echo $session->status === 'open' ? 'success' : 'warning'; ?>">
                                <?php echo $session->current_players; ?>/<?php echo $session->max_players; ?> spots
                            </span>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; font-size: 14px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span>📅</span>
                                <span><?php echo date('l, M j, Y', strtotime($session->session_date)); ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span>🕐</span>
                                <span><?php echo date('g:i A', strtotime($session->start_time)); ?> - <?php echo date('g:i A', strtotime($session->end_time)); ?></span>
                            </div>
                            <?php if ($session->location): ?>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span>📍</span>
                                <span><?php echo esc_html($session->location); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($session->skill_level !== 'all'): ?>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span>🎯</span>
                                <span><?php echo ucfirst($session->skill_level); ?> level</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($session->description): ?>
                        <p style="margin: 0 0 16px; font-size: 14px; color: var(--ptp-gray-600);"><?php echo esc_html(substr($session->description, 0, 100)); ?><?php echo strlen($session->description) > 100 ? '...' : ''; ?></p>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid var(--ptp-gray-100);">
                            <div style="font-size: 20px; font-weight: 700;">$<?php echo number_format($session->price_per_player, 0); ?><span style="font-size: 14px; font-weight: 400; color: var(--ptp-gray-500);">/player</span></div>
                            <?php if ($session->status === 'open'): ?>
                            <a href="<?php echo home_url('/book-group/?session=' . $session->id); ?>" class="ptp-btn ptp-btn-primary">Join Session</a>
                            <?php else: ?>
                            <span class="ptp-btn ptp-btn-outline" style="opacity: 0.5; pointer-events: none;">Full</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
