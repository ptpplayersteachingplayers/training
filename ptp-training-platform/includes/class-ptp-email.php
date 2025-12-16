<?php
/**
 * PTP Email System v29.4
 * Professional, bulletproof email templates for all inboxes
 * No emojis, consistent branding, mobile-optimized
 */

defined('ABSPATH') || exit;

class PTP_Email {
    
    private static $from_name = 'PTP Soccer';
    private static $from_email = '';
    
    public static function init() {
        self::$from_email = get_option('ptp_from_email', get_option('admin_email'));
        add_filter('wp_mail_from', array(__CLASS__, 'set_from_email'));
        add_filter('wp_mail_from_name', array(__CLASS__, 'set_from_name'));
        add_filter('wp_mail_content_type', array(__CLASS__, 'set_html_content_type'));
    }
    
    public static function set_from_email($email) {
        return self::$from_email;
    }
    
    public static function set_from_name($name) {
        return self::$from_name;
    }
    
    public static function set_html_content_type() {
        return 'text/html';
    }
    
    /**
     * Send booking confirmation to parent
     */
    public static function send_booking_confirmation($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, 
                   t.display_name as trainer_name, t.photo_url as trainer_photo,
                   p.name as player_name,
                   pa.display_name as parent_name, pa.user_id as parent_user_id
            FROM {$wpdb->prefix}ptp_bookings b
            JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
            JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
            JOIN {$wpdb->prefix}ptp_parents pa ON b.parent_id = pa.id
            WHERE b.id = %d
        ", $booking_id));
        
        if (!$booking) return false;
        
        $user = get_user_by('ID', $booking->parent_user_id);
        if (!$user) return false;
        
        $subject = "Booking Confirmed - {$booking->booking_number}";
        
        $data = array(
            'parent_name' => $booking->parent_name,
            'trainer_name' => $booking->trainer_name,
            'trainer_photo' => $booking->trainer_photo,
            'player_name' => $booking->player_name,
            'date' => date('l, F j, Y', strtotime($booking->session_date)),
            'time' => date('g:i A', strtotime($booking->start_time)) . ' - ' . date('g:i A', strtotime($booking->end_time)),
            'location' => $booking->location,
            'total' => number_format($booking->total_amount, 2),
            'booking_number' => $booking->booking_number,
            'dashboard_url' => home_url('/my-training/'),
        );
        
        $body = self::render_template('booking-confirmation', $data);
        
        return wp_mail($user->user_email, $subject, $body);
    }
    
    /**
     * Send new booking notification to trainer
     */
    public static function send_trainer_new_booking($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, 
                   t.display_name as trainer_name, t.user_id as trainer_user_id, t.hourly_rate,
                   p.name as player_name, p.age as player_age, p.skill_level,
                   pa.display_name as parent_name
            FROM {$wpdb->prefix}ptp_bookings b
            JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
            JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
            JOIN {$wpdb->prefix}ptp_parents pa ON b.parent_id = pa.id
            WHERE b.id = %d
        ", $booking_id));
        
        if (!$booking) return false;
        
        $user = get_user_by('ID', $booking->trainer_user_id);
        if (!$user) return false;
        
        $subject = "New Booking - {$booking->player_name}";
        
        $earnings = round($booking->total_amount * 0.80, 2);
        
        $data = array(
            'trainer_name' => explode(' ', $booking->trainer_name)[0],
            'player_name' => $booking->player_name,
            'player_age' => $booking->player_age,
            'skill_level' => $booking->skill_level,
            'parent_name' => $booking->parent_name,
            'date' => date('l, F j, Y', strtotime($booking->session_date)),
            'time' => date('g:i A', strtotime($booking->start_time)) . ' - ' . date('g:i A', strtotime($booking->end_time)),
            'location' => $booking->location,
            'earnings' => number_format($earnings, 2),
            'notes' => $booking->notes ?? '',
            'dashboard_url' => home_url('/trainer-dashboard/'),
        );
        
        $body = self::render_template('trainer-new-booking', $data);
        
        return wp_mail($user->user_email, $subject, $body);
    }
    
    /**
     * Send session reminder
     */
    public static function send_session_reminder($booking_id, $to = 'both') {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, 
                   t.display_name as trainer_name, t.user_id as trainer_user_id,
                   p.name as player_name,
                   pa.display_name as parent_name, pa.user_id as parent_user_id
            FROM {$wpdb->prefix}ptp_bookings b
            JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
            JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
            JOIN {$wpdb->prefix}ptp_parents pa ON b.parent_id = pa.id
            WHERE b.id = %d
        ", $booking_id));
        
        if (!$booking) return false;
        
        $date = date('l, F j', strtotime($booking->session_date));
        $time = date('g:i A', strtotime($booking->start_time));
        
        if ($to === 'parent' || $to === 'both') {
            $parent_user = get_user_by('ID', $booking->parent_user_id);
            if ($parent_user) {
                $body = self::render_template('session-reminder-parent', array(
                    'name' => explode(' ', $booking->parent_name)[0],
                    'player_name' => $booking->player_name,
                    'trainer_name' => $booking->trainer_name,
                    'date' => $date,
                    'time' => $time,
                    'location' => $booking->location,
                    'dashboard_url' => home_url('/my-training/'),
                ));
                wp_mail($parent_user->user_email, "Training Tomorrow - {$booking->player_name}", $body);
            }
        }
        
        if ($to === 'trainer' || $to === 'both') {
            $trainer_user = get_user_by('ID', $booking->trainer_user_id);
            if ($trainer_user) {
                $body = self::render_template('session-reminder-trainer', array(
                    'name' => explode(' ', $booking->trainer_name)[0],
                    'player_name' => $booking->player_name,
                    'date' => $date,
                    'time' => $time,
                    'location' => $booking->location,
                    'dashboard_url' => home_url('/trainer-dashboard/'),
                ));
                wp_mail($trainer_user->user_email, "Session Tomorrow - {$booking->player_name}", $body);
            }
        }
        
        return true;
    }
    
    /**
     * Send booking cancellation
     */
    public static function send_booking_cancelled($booking_id, $cancelled_by = 'parent', $reason = '') {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, 
                   t.display_name as trainer_name, t.user_id as trainer_user_id,
                   p.name as player_name,
                   pa.display_name as parent_name, pa.user_id as parent_user_id
            FROM {$wpdb->prefix}ptp_bookings b
            JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
            JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
            JOIN {$wpdb->prefix}ptp_parents pa ON b.parent_id = pa.id
            WHERE b.id = %d
        ", $booking_id));
        
        if (!$booking) return false;
        
        $data = array(
            'player_name' => $booking->player_name,
            'trainer_name' => $booking->trainer_name,
            'date' => date('l, F j, Y', strtotime($booking->session_date)),
            'time' => date('g:i A', strtotime($booking->start_time)),
            'reason' => $reason,
        );
        
        // Notify trainer
        $trainer_user = get_user_by('ID', $booking->trainer_user_id);
        if ($trainer_user) {
            $data['name'] = explode(' ', $booking->trainer_name)[0];
            $body = self::render_template('booking-cancelled', $data);
            wp_mail($trainer_user->user_email, "Session Cancelled - {$booking->player_name}", $body);
        }
        
        // Notify parent
        $parent_user = get_user_by('ID', $booking->parent_user_id);
        if ($parent_user) {
            $data['name'] = explode(' ', $booking->parent_name)[0];
            $body = self::render_template('booking-cancelled', $data);
            wp_mail($parent_user->user_email, "Session Cancelled - {$booking->player_name}", $body);
        }
        
        return true;
    }
    
    /**
     * Send new message notification
     */
    public static function send_new_message($conversation_id, $sender_id, $message_text) {
        global $wpdb;
        
        $conv = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_conversations WHERE id = %d",
            $conversation_id
        ));
        
        if (!$conv) return false;
        
        $recipient_id = ($conv->user_1_id == $sender_id) ? $conv->user_2_id : $conv->user_1_id;
        $recipient = get_user_by('ID', $recipient_id);
        $sender = get_user_by('ID', $sender_id);
        
        if (!$recipient || !$sender) return false;
        
        $body = self::render_template('new-message', array(
            'recipient_name' => explode(' ', $recipient->display_name)[0],
            'sender_name' => $sender->display_name,
            'message' => wp_trim_words($message_text, 30),
            'messages_url' => home_url('/messages/'),
        ));
        
        return wp_mail($recipient->user_email, "New Message from {$sender->display_name}", $body);
    }
    
    /**
     * Send payout notification
     */
    public static function send_payout_processed($trainer_id, $amount, $method) {
        $trainer = PTP_Trainer::get($trainer_id);
        if (!$trainer) return false;
        
        $user = get_user_by('ID', $trainer->user_id);
        if (!$user) return false;
        
        $body = self::render_template('payout-processed', array(
            'name' => explode(' ', $trainer->display_name)[0],
            'amount' => number_format($amount, 2),
            'method' => ucfirst($method),
            'date' => date('F j, Y'),
            'dashboard_url' => home_url('/trainer-dashboard/?tab=earnings'),
        ));
        
        return wp_mail($user->user_email, "Payout Processed - \${$amount}", $body);
    }
    
    /**
     * Send application received
     */
    public static function send_application_received($email, $name) {
        $body = self::render_template('application-received', array(
            'name' => explode(' ', $name)[0],
        ));
        
        return wp_mail($email, "Application Received - PTP Soccer", $body);
    }
    
    /**
     * Send application approved
     */
    public static function send_application_approved($trainer_id, $password = '') {
        $trainer = PTP_Trainer::get($trainer_id);
        if (!$trainer) return false;
        
        $user = get_user_by('ID', $trainer->user_id);
        if (!$user) return false;
        
        $body = self::render_template('application-approved', array(
            'name' => explode(' ', $trainer->display_name)[0],
            'email' => $user->user_email,
            'password' => $password,
            'login_url' => home_url('/login/'),
        ));
        
        return wp_mail($user->user_email, "Welcome to PTP - You're Approved!", $body);
    }
    
    /**
     * Send application rejected
     */
    public static function send_application_rejected($email, $name) {
        $body = self::render_template('application-rejected', array(
            'name' => explode(' ', $name)[0],
        ));
        
        return wp_mail($email, "Application Update - PTP Soccer", $body);
    }
    
    /**
     * Send contractor agreement
     */
    public static function send_contractor_agreement($trainer_id) {
        $trainer = PTP_Trainer::get($trainer_id);
        if (!$trainer || !$trainer->contractor_agreement_signed) return false;
        
        $user = get_user_by('ID', $trainer->user_id);
        if (!$user) return false;
        
        $body = self::render_template('contractor-agreement', array(
            'name' => $trainer->display_name,
            'signed_date' => date('F j, Y g:i A', strtotime($trainer->contractor_agreement_signed_at)),
            'ip_address' => $trainer->contractor_agreement_ip,
        ));
        
        return wp_mail($user->user_email, "Contractor Agreement Signed - PTP Soccer", $body);
    }
    
    /**
     * Send onboarding complete
     */
    public static function send_onboarding_complete($trainer_id) {
        $trainer = PTP_Trainer::get($trainer_id);
        if (!$trainer) return false;
        
        $user = get_user_by('ID', $trainer->user_id);
        if (!$user) return false;
        
        $body = self::render_template('onboarding-complete', array(
            'name' => explode(' ', $trainer->display_name)[0],
            'profile_url' => home_url('/trainer/' . $trainer->slug . '/'),
            'dashboard_url' => home_url('/trainer-dashboard/'),
        ));
        
        return wp_mail($user->user_email, "Profile Complete - You're Live!", $body);
    }
    
    /**
     * Send welcome to parent
     */
    public static function send_welcome_parent($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return false;
        
        $body = self::render_template('welcome-parent', array(
            'name' => explode(' ', $user->display_name)[0],
            'trainers_url' => home_url('/find-trainers/'),
        ));
        
        return wp_mail($user->user_email, "Welcome to PTP Soccer", $body);
    }
    
    /**
     * Send review request
     */
    public static function send_review_request($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, 
                   t.display_name as trainer_name, t.slug as trainer_slug,
                   p.name as player_name,
                   pa.display_name as parent_name, pa.user_id as parent_user_id
            FROM {$wpdb->prefix}ptp_bookings b
            JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
            JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
            JOIN {$wpdb->prefix}ptp_parents pa ON b.parent_id = pa.id
            WHERE b.id = %d
        ", $booking_id));
        
        if (!$booking) return false;
        
        $user = get_user_by('ID', $booking->parent_user_id);
        if (!$user) return false;
        
        $body = self::render_template('review-request', array(
            'parent_name' => explode(' ', $booking->parent_name)[0],
            'player_name' => $booking->player_name,
            'trainer_name' => $booking->trainer_name,
            'review_url' => home_url('/trainer/' . $booking->trainer_slug . '/?review=' . $booking_id),
        ));
        
        return wp_mail($user->user_email, "How was {$booking->player_name}'s session?", $body);
    }
    
    /**
     * Master template renderer - Bulletproof for all email clients
     */
    private static function render_template($template, $data) {
        $logo_url = 'https://ptpsummercamps.com/wp-content/uploads/2025/11/PTP-LOGO-2.png';
        $home_url = home_url();
        
        ob_start();
        ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <title>PTP Soccer</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <style type="text/css">
        table {border-collapse: collapse;}
        .button-td, .button-a {padding: 16px 32px !important;}
    </style>
    <![endif]-->
    <style type="text/css">
        body {margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;}
        table, td {border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;}
        img {border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic;}
        @media screen and (max-width: 600px) {
            .mobile-padding {padding-left: 16px !important; padding-right: 16px !important;}
            .mobile-stack {display: block !important; width: 100% !important;}
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #0E0F11; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">

    <!-- Preheader (hidden text for email preview) -->
    <div style="display: none; max-height: 0; overflow: hidden;">
        <?php echo self::get_preheader($template, $data); ?>
    </div>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #0E0F11;">
        <tr>
            <td align="center" style="padding: 40px 20px;" class="mobile-padding">
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 560px;">
                    
                    <!-- Logo -->
                    <tr>
                        <td align="center" style="padding-bottom: 32px;">
                            <a href="<?php echo esc_url($home_url); ?>" target="_blank">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="PTP Soccer" width="100" style="display: block; max-width: 100px; height: auto;">
                            </a>
                        </td>
                    </tr>
                    
                    <!-- Content Card -->
                    <tr>
                        <td>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff; border-radius: 16px; overflow: hidden;">
                                
                                <!-- Yellow Top Border -->
                                <tr>
                                    <td style="background-color: #FCB900; height: 5px; line-height: 5px; font-size: 5px;">&nbsp;</td>
                                </tr>
                                
                                <!-- Main Content -->
                                <tr>
                                    <td style="padding: 40px 36px 36px;" class="mobile-padding">
                                        <?php echo self::get_template_content($template, $data); ?>
                                    </td>
                                </tr>
                                
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 32px 20px; text-align: center;">
                            <p style="margin: 0 0 8px; font-size: 14px; color: #9CA3AF; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                                PTP Soccer - Elite 1-on-1 Training
                            </p>
                            <p style="margin: 0 0 16px; font-size: 13px; color: #6B7280; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                                PA &bull; NJ &bull; DE &bull; MD &bull; NY
                            </p>
                            <p style="margin: 0; font-size: 12px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                                <a href="<?php echo esc_url($home_url); ?>" style="color: #FCB900; text-decoration: none;">Website</a>
                                <span style="color: #4B5563; margin: 0 8px;">|</span>
                                <a href="<?php echo esc_url(home_url('/account/')); ?>" style="color: #FCB900; text-decoration: none;">Account</a>
                                <span style="color: #4B5563; margin: 0 8px;">|</span>
                                <a href="mailto:info@ptpsummercamps.com" style="color: #FCB900; text-decoration: none;">Contact</a>
                            </p>
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>

</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get preheader text for email preview
     */
    private static function get_preheader($template, $data) {
        switch ($template) {
            case 'booking-confirmation':
                return "Your training session with {$data['trainer_name']} is confirmed.";
            case 'trainer-new-booking':
                return "New session booked with {$data['player_name']}.";
            case 'session-reminder-parent':
            case 'session-reminder-trainer':
                return "Reminder: Training session tomorrow at {$data['time']}.";
            case 'application-approved':
                return "Congratulations! You've been approved as a PTP trainer.";
            case 'payout-processed':
                return "Your payout of \${$data['amount']} has been processed.";
            default:
                return "PTP Soccer - Elite 1-on-1 Training";
        }
    }
    
    /**
     * Get template-specific content - Professional, no emojis
     */
    private static function get_template_content($template, $data) {
        // Button styles
        $btn_primary = 'display: inline-block; background-color: #FCB900; color: #0E0F11; padding: 16px 32px; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 15px; font-family: -apple-system, BlinkMacSystemFont, sans-serif;';
        $btn_outline = 'display: inline-block; background-color: transparent; color: #374151; padding: 14px 28px; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 14px; border: 2px solid #E5E7EB; font-family: -apple-system, BlinkMacSystemFont, sans-serif;';
        
        // Text styles
        $h1 = 'margin: 0 0 8px; font-size: 26px; font-weight: 800; color: #0E0F11; font-family: -apple-system, BlinkMacSystemFont, sans-serif;';
        $subtitle = 'margin: 0 0 28px; font-size: 16px; color: #6B7280; line-height: 1.5; font-family: -apple-system, BlinkMacSystemFont, sans-serif;';
        $label = 'font-size: 11px; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.5px;';
        $value = 'font-size: 16px; font-weight: 600; color: #0E0F11; font-family: -apple-system, BlinkMacSystemFont, sans-serif;';
        
        ob_start();
        
        switch ($template) {
            
            case 'booking-confirmation':
                ?>
                <h1 style="<?php echo $h1; ?>">Booking Confirmed</h1>
                <p style="<?php echo $subtitle; ?>">Hi <?php echo esc_html($data['parent_name']); ?>, your training session is all set.</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F9FAFB; border-radius: 12px; margin-bottom: 28px;">
                    <tr>
                        <td style="padding: 24px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB;">
                                        <span style="<?php echo $label; ?>">Booking Number</span><br>
                                        <span style="font-size: 16px; font-weight: 700; color: #0E0F11;"><?php echo esc_html($data['booking_number']); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB;">
                                        <span style="<?php echo $label; ?>">Trainer</span><br>
                                        <span style="<?php echo $value; ?>"><?php echo esc_html($data['trainer_name']); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB;">
                                        <span style="<?php echo $label; ?>">Player</span><br>
                                        <span style="<?php echo $value; ?>"><?php echo esc_html($data['player_name']); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB;">
                                        <span style="<?php echo $label; ?>">Date &amp; Time</span><br>
                                        <span style="<?php echo $value; ?>"><?php echo esc_html($data['date']); ?></span><br>
                                        <span style="font-size: 14px; color: #374151;"><?php echo esc_html($data['time']); ?></span>
                                    </td>
                                </tr>
                                <?php if (!empty($data['location'])): ?>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB;">
                                        <span style="<?php echo $label; ?>">Location</span><br>
                                        <span style="font-size: 15px; color: #0E0F11;"><?php echo esc_html($data['location']); ?></span>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <span style="<?php echo $label; ?>">Total Paid</span><br>
                                        <span style="font-size: 22px; font-weight: 800; color: #0E0F11;">$<?php echo esc_html($data['total']); ?></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td align="center">
                            <a href="<?php echo esc_url($data['dashboard_url']); ?>" style="<?php echo $btn_primary; ?>" target="_blank">View Booking Details</a>
                        </td>
                    </tr>
                </table>
                <?php
                break;
                
            case 'trainer-new-booking':
                ?>
                <h1 style="<?php echo $h1; ?>">New Booking</h1>
                <p style="<?php echo $subtitle; ?>">Great news <?php echo esc_html($data['trainer_name']); ?>, you have a new training session booked.</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F9FAFB; border-radius: 12px; margin-bottom: 28px;">
                    <tr>
                        <td style="padding: 24px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB;">
                                        <span style="<?php echo $label; ?>">Player</span><br>
                                        <span style="<?php echo $value; ?>"><?php echo esc_html($data['player_name']); ?></span>
                                        <?php if (!empty($data['player_age'])): ?>
                                        <span style="font-size: 14px; color: #6B7280;"> - <?php echo esc_html($data['player_age']); ?> years old</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB;">
                                        <span style="<?php echo $label; ?>">Parent</span><br>
                                        <span style="font-size: 15px; color: #0E0F11;"><?php echo esc_html($data['parent_name']); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB;">
                                        <span style="<?php echo $label; ?>">Date &amp; Time</span><br>
                                        <span style="<?php echo $value; ?>"><?php echo esc_html($data['date']); ?></span><br>
                                        <span style="font-size: 14px; color: #374151;"><?php echo esc_html($data['time']); ?></span>
                                    </td>
                                </tr>
                                <?php if (!empty($data['location'])): ?>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB;">
                                        <span style="<?php echo $label; ?>">Location</span><br>
                                        <span style="font-size: 15px; color: #0E0F11;"><?php echo esc_html($data['location']); ?></span>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <span style="<?php echo $label; ?>">Your Earnings</span><br>
                                        <span style="font-size: 24px; font-weight: 800; color: #059669;">$<?php echo esc_html($data['earnings']); ?></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                
                <?php if (!empty($data['notes'])): ?>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 28px;">
                    <tr>
                        <td style="background-color: #FEF3C7; border-radius: 10px; padding: 16px; border-left: 4px solid #FCB900;">
                            <p style="margin: 0 0 4px; font-size: 11px; font-weight: 700; color: #92400E; text-transform: uppercase;">Note from Parent</p>
                            <p style="margin: 0; font-size: 14px; color: #78350F; line-height: 1.5;"><?php echo esc_html($data['notes']); ?></p>
                        </td>
                    </tr>
                </table>
                <?php endif; ?>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td align="center">
                            <a href="<?php echo esc_url($data['dashboard_url']); ?>" style="<?php echo $btn_primary; ?>" target="_blank">View Dashboard</a>
                        </td>
                    </tr>
                </table>
                <?php
                break;
                
            case 'session-reminder-parent':
            case 'session-reminder-trainer':
                ?>
                <h1 style="<?php echo $h1; ?>">Session Tomorrow</h1>
                <p style="<?php echo $subtitle; ?>">Hi <?php echo esc_html($data['name']); ?>, just a friendly reminder about tomorrow's training session.</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F9FAFB; border-radius: 12px; margin-bottom: 28px;">
                    <tr>
                        <td style="padding: 24px;">
                            <p style="margin: 0 0 4px; font-size: 18px; font-weight: 700; color: #0E0F11;"><?php echo esc_html($data['date']); ?></p>
                            <p style="margin: 0 0 12px; font-size: 16px; color: #374151;"><?php echo esc_html($data['time']); ?></p>
                            <p style="margin: 0; font-size: 15px; color: #6B7280;">
                                <?php if ($template === 'session-reminder-parent'): ?>
                                Trainer: <?php echo esc_html($data['trainer_name']); ?>
                                <?php else: ?>
                                Player: <?php echo esc_html($data['player_name']); ?>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($data['location'])): ?>
                            <p style="margin: 12px 0 0; font-size: 14px; color: #6B7280;">Location: <?php echo esc_html($data['location']); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td align="center">
                            <a href="<?php echo esc_url($data['dashboard_url']); ?>" style="<?php echo $btn_primary; ?>" target="_blank">View Details</a>
                        </td>
                    </tr>
                </table>
                <?php
                break;
                
            case 'booking-cancelled':
                ?>
                <h1 style="<?php echo $h1; ?>">Session Cancelled</h1>
                <p style="<?php echo $subtitle; ?>">Hi <?php echo esc_html($data['name']); ?>, the following session has been cancelled.</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #FEF2F2; border-radius: 12px; margin-bottom: 28px;">
                    <tr>
                        <td style="padding: 24px;">
                            <p style="margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #991B1B;"><?php echo esc_html($data['date']); ?> at <?php echo esc_html($data['time']); ?></p>
                            <p style="margin: 0; font-size: 14px; color: #7F1D1D;">
                                Player: <?php echo esc_html($data['player_name']); ?> &bull; Trainer: <?php echo esc_html($data['trainer_name']); ?>
                            </p>
                            <?php if (!empty($data['reason'])): ?>
                            <p style="margin: 12px 0 0; font-size: 14px; color: #991B1B;">Reason: <?php echo esc_html($data['reason']); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <p style="margin: 0; font-size: 14px; color: #6B7280; line-height: 1.5;">If you have any questions, please reply to this email or contact us.</p>
                <?php
                break;
                
            case 'payout-processed':
                ?>
                <h1 style="<?php echo $h1; ?>">Payout Processed</h1>
                <p style="<?php echo $subtitle; ?>">Great news <?php echo esc_html($data['name']); ?>, your earnings have been sent.</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ECFDF5; border-radius: 12px; margin-bottom: 28px;">
                    <tr>
                        <td style="padding: 32px; text-align: center;">
                            <p style="margin: 0 0 4px; font-size: 14px; color: #065F46;">Amount Deposited</p>
                            <p style="margin: 0; font-size: 40px; font-weight: 800; color: #059669;">$<?php echo esc_html($data['amount']); ?></p>
                            <p style="margin: 8px 0 0; font-size: 14px; color: #6B7280;">via <?php echo esc_html($data['method']); ?> &bull; <?php echo esc_html($data['date']); ?></p>
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td align="center">
                            <a href="<?php echo esc_url($data['dashboard_url']); ?>" style="<?php echo $btn_primary; ?>" target="_blank">View Earnings</a>
                        </td>
                    </tr>
                </table>
                <?php
                break;
                
            case 'application-received':
                ?>
                <h1 style="<?php echo $h1; ?>">We Got Your Application!</h1>
                <p style="<?php echo $subtitle; ?>">Hi <?php echo esc_html($data['name']); ?>, thanks for applying to join the PTP team!</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ECFDF5; border-radius: 12px; margin-bottom: 24px; border: 1px solid #BBF7D0;">
                    <tr>
                        <td style="padding: 20px; text-align: center;">
                            <p style="margin: 0; font-size: 14px; color: #166534; font-weight: 600;">Application Status: Under Review</p>
                            <p style="margin: 8px 0 0; font-size: 13px; color: #15803D;">We typically respond within 24-48 hours</p>
                        </td>
                    </tr>
                </table>
                
                <p style="margin: 0 0 24px; font-size: 15px; color: #374151; line-height: 1.6;">We're excited to learn about your soccer background. Our team reviews every application personally to ensure we maintain the highest quality trainers on our platform.</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F9FAFB; border-radius: 12px; margin-bottom: 28px;">
                    <tr>
                        <td style="padding: 24px;">
                            <p style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: #0E0F11;">What happens next?</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 10px 0; font-size: 14px; color: #374151; vertical-align: top;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="width: 36px; vertical-align: top;">
                                                    <span style="display: inline-block; width: 28px; height: 28px; background: #FCB900; color: #0E0F11; border-radius: 50%; text-align: center; line-height: 28px; font-weight: 700; font-size: 13px;">1</span>
                                                </td>
                                                <td style="vertical-align: top;">
                                                    <strong style="color: #111;">Application Review</strong><br>
                                                    <span style="color: #6B7280; font-size: 13px;">We verify your playing experience</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0; font-size: 14px; color: #374151; vertical-align: top;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="width: 36px; vertical-align: top;">
                                                    <span style="display: inline-block; width: 28px; height: 28px; background: #E5E7EB; color: #6B7280; border-radius: 50%; text-align: center; line-height: 28px; font-weight: 700; font-size: 13px;">2</span>
                                                </td>
                                                <td style="vertical-align: top;">
                                                    <strong style="color: #111;">Approval Email</strong><br>
                                                    <span style="color: #6B7280; font-size: 13px;">Login credentials sent if approved</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0; font-size: 14px; color: #374151; vertical-align: top;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="width: 36px; vertical-align: top;">
                                                    <span style="display: inline-block; width: 28px; height: 28px; background: #E5E7EB; color: #6B7280; border-radius: 50%; text-align: center; line-height: 28px; font-weight: 700; font-size: 13px;">3</span>
                                                </td>
                                                <td style="vertical-align: top;">
                                                    <strong style="color: #111;">Profile Setup</strong><br>
                                                    <span style="color: #6B7280; font-size: 13px;">Add photo, availability &amp; complete profile</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0; font-size: 14px; color: #374151; vertical-align: top;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="width: 36px; vertical-align: top;">
                                                    <span style="display: inline-block; width: 28px; height: 28px; background: #E5E7EB; color: #6B7280; border-radius: 50%; text-align: center; line-height: 28px; font-weight: 700; font-size: 13px;">4</span>
                                                </td>
                                                <td style="vertical-align: top;">
                                                    <strong style="color: #111;">Start Earning</strong><br>
                                                    <span style="color: #6B7280; font-size: 13px;">Get matched with families &amp; accept bookings</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #FFFBEB; border-radius: 12px; margin-bottom: 24px; border: 1px solid #FDE68A;">
                    <tr>
                        <td style="padding: 20px;">
                            <p style="margin: 0 0 8px; font-size: 14px; font-weight: 700; color: #92400E;">While you wait...</p>
                            <p style="margin: 0; font-size: 14px; color: #B45309; line-height: 1.5;">Follow us on Instagram <a href="https://instagram.com/ptpsummercamps" style="color: #B45309; font-weight: 600;">@ptpsummercamps</a> to see what our trainers are doing!</p>
                        </td>
                    </tr>
                </table>
                
                <p style="margin: 0; font-size: 14px; color: #6B7280;">Questions? Just reply to this email - we're here to help!</p>
                <?php
                break;
                
            case 'application-approved':
                ?>
                <h1 style="<?php echo $h1; ?>">You're In! Welcome to PTP</h1>
                <p style="<?php echo $subtitle; ?>">Congratulations <?php echo esc_html($data['name']); ?>! Your application has been approved.</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ECFDF5; border-radius: 12px; margin-bottom: 24px; border: 1px solid #BBF7D0;">
                    <tr>
                        <td style="padding: 24px; text-align: center;">
                            <p style="margin: 0 0 4px; font-size: 14px; color: #166534; font-weight: 600;">Application Approved</p>
                            <p style="margin: 0; font-size: 13px; color: #15803D;">You're now part of the PTP trainer network</p>
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F9FAFB; border-radius: 12px; margin-bottom: 24px;">
                    <tr>
                        <td style="padding: 24px;">
                            <p style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: #0E0F11;">Your Login Details</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 8px 0;">
                                        <span style="display: inline-block; width: 80px; font-size: 13px; color: #6B7280;">Email:</span>
                                        <span style="font-size: 14px; color: #111; font-weight: 500;"><?php echo esc_html($data['email']); ?></span>
                                    </td>
                                </tr>
                                <?php if (!empty($data['password'])): ?>
                                <tr>
                                    <td style="padding: 8px 0;">
                                        <span style="display: inline-block; width: 80px; font-size: 13px; color: #6B7280;">Password:</span>
                                        <code style="background: #FEF3C7; padding: 6px 14px; border-radius: 6px; font-family: 'SF Mono', Monaco, monospace; font-size: 14px; color: #92400E; font-weight: 600;"><?php echo esc_html($data['password']); ?></code>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0 0;">
                                        <p style="margin: 0; font-size: 13px; color: #DC2626; font-weight: 500;">Important: Change your password after logging in</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <tr>
                                    <td style="padding: 8px 0;">
                                        <span style="display: inline-block; width: 80px; font-size: 13px; color: #6B7280;">Password:</span>
                                        <span style="font-size: 14px; color: #111;">Use the password you created when applying</span>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                    <tr>
                        <td align="center">
                            <a href="<?php echo esc_url($data['login_url']); ?>" style="<?php echo $btn_primary; ?>" target="_blank">Login &amp; Complete Your Profile</a>
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F9FAFB; border-radius: 12px; margin-bottom: 24px;">
                    <tr>
                        <td style="padding: 24px;">
                            <p style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: #0E0F11;">Complete these 3 steps to go live:</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 8px 0; font-size: 14px; color: #374151;">
                                        <span style="display: inline-block; width: 24px; color: #FCB900; font-weight: 700;">1.</span>
                                        <strong>Add a professional photo</strong> - Parents want to see who they're booking
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-size: 14px; color: #374151;">
                                        <span style="display: inline-block; width: 24px; color: #FCB900; font-weight: 700;">2.</span>
                                        <strong>Set your availability</strong> - Block out times you can train
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-size: 14px; color: #374151;">
                                        <span style="display: inline-block; width: 24px; color: #FCB900; font-weight: 700;">3.</span>
                                        <strong>Complete your bio</strong> - Share your soccer story
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #FFFBEB; border-radius: 12px; border: 1px solid #FDE68A;">
                    <tr>
                        <td style="padding: 20px;">
                            <p style="margin: 0 0 8px; font-size: 14px; font-weight: 700; color: #92400E;">Pro Tip</p>
                            <p style="margin: 0; font-size: 14px; color: #B45309; line-height: 1.5;">Trainers who complete their profile within 24 hours get 3x more bookings in their first month!</p>
                        </td>
                    </tr>
                </table>
                <?php
                break;
                
            case 'application-rejected':
                ?>
                <h1 style="<?php echo $h1; ?>">Application Update</h1>
                <p style="<?php echo $subtitle; ?>">Hi <?php echo esc_html($data['name']); ?>, thank you for your interest in joining PTP Soccer.</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F9FAFB; border-radius: 12px; margin-bottom: 24px;">
                    <tr>
                        <td style="padding: 24px;">
                            <p style="margin: 0 0 16px; font-size: 15px; color: #374151; line-height: 1.6;">After careful review of your application, we're unable to move forward at this time. This decision could be based on:</p>
                            <ul style="margin: 0 0 16px 20px; padding: 0; color: #6B7280; font-size: 14px; line-height: 1.8;">
                                <li>Current trainer capacity in your area</li>
                                <li>Playing experience requirements</li>
                                <li>Geographic coverage limitations</li>
                            </ul>
                            <p style="margin: 0; font-size: 15px; color: #374151; line-height: 1.6;">This doesn't mean the door is closed - we encourage you to reapply in the future, especially as you gain additional playing or coaching experience.</p>
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #FFFBEB; border-radius: 12px; margin-bottom: 24px; border: 1px solid #FDE68A;">
                    <tr>
                        <td style="padding: 20px;">
                            <p style="margin: 0 0 8px; font-size: 14px; font-weight: 700; color: #92400E;">Stay Connected</p>
                            <p style="margin: 0; font-size: 14px; color: #B45309; line-height: 1.5;">Follow us on Instagram <a href="https://instagram.com/ptpsummercamps" style="color: #B45309; font-weight: 600;">@ptpsummercamps</a> and check back in a few months - our needs change as we expand!</p>
                        </td>
                    </tr>
                </table>
                
                <p style="margin: 0; font-size: 14px; color: #6B7280; line-height: 1.6;">We appreciate you taking the time to apply and wish you the best in your soccer journey. Keep grinding!</p>
                <?php
                break;
                
            case 'contractor-agreement':
                ?>
                <h1 style="<?php echo $h1; ?>">Agreement Signed</h1>
                <p style="<?php echo $subtitle; ?>">Hi <?php echo esc_html($data['name']); ?>, this confirms your acceptance of the PTP Independent Contractor Agreement.</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F0FDF4; border-radius: 12px; margin-bottom: 28px; border: 1px solid #BBF7D0;">
                    <tr>
                        <td style="padding: 20px;">
                            <p style="margin: 0 0 12px; font-size: 14px; font-weight: 700; color: #166534;">Agreement Signed Successfully</p>
                            <p style="margin: 0 0 4px; font-size: 13px; color: #374151;"><strong>Date:</strong> <?php echo esc_html($data['signed_date']); ?></p>
                            <p style="margin: 0 0 4px; font-size: 13px; color: #374151;"><strong>IP Address:</strong> <?php echo esc_html($data['ip_address']); ?></p>
                            <p style="margin: 0; font-size: 13px; color: #374151;"><strong>Agreement Version:</strong> 1.0</p>
                        </td>
                    </tr>
                </table>
                
                <p style="margin: 0; font-size: 14px; color: #6B7280; line-height: 1.5;">A copy of this agreement has been saved to your account. You can access it anytime from your trainer dashboard.</p>
                <?php
                break;
                
            case 'onboarding-complete':
                ?>
                <h1 style="<?php echo $h1; ?>">You're Live!</h1>
                <p style="<?php echo $subtitle; ?>">Congratulations <?php echo esc_html($data['name']); ?>! Your profile is now live and visible to parents in your area.</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F9FAFB; border-radius: 12px; margin-bottom: 28px;">
                    <tr>
                        <td style="padding: 24px;">
                            <p style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: #0E0F11;">Quick Tips to Get Bookings</p>
                            <ul style="margin: 0; padding-left: 20px; color: #374151; font-size: 14px; line-height: 1.8;">
                                <li>Add a professional photo and bio</li>
                                <li>Set competitive hourly rates for your area</li>
                                <li>Keep your availability up to date</li>
                                <li>Respond to messages promptly</li>
                            </ul>
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td align="center" style="padding-bottom: 12px;">
                            <a href="<?php echo esc_url($data['profile_url']); ?>" style="<?php echo $btn_primary; ?>" target="_blank">View Your Profile</a>
                        </td>
                    </tr>
                    <tr>
                        <td align="center">
                            <a href="<?php echo esc_url($data['dashboard_url']); ?>" style="<?php echo $btn_outline; ?>" target="_blank">Go to Dashboard</a>
                        </td>
                    </tr>
                </table>
                <?php
                break;
                
            case 'new-message':
                ?>
                <h1 style="<?php echo $h1; ?>">New Message</h1>
                <p style="<?php echo $subtitle; ?>">Hi <?php echo esc_html($data['recipient_name']); ?>, you have a new message from <?php echo esc_html($data['sender_name']); ?>.</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F9FAFB; border-radius: 12px; margin-bottom: 28px;">
                    <tr>
                        <td style="padding: 24px;">
                            <p style="margin: 0 0 8px; font-size: 13px; font-weight: 700; color: #6B7280;"><?php echo esc_html($data['sender_name']); ?> wrote:</p>
                            <p style="margin: 0; font-size: 15px; color: #374151; line-height: 1.6; font-style: italic;">"<?php echo esc_html($data['message']); ?>"</p>
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td align="center">
                            <a href="<?php echo esc_url($data['messages_url']); ?>" style="<?php echo $btn_primary; ?>" target="_blank">View &amp; Reply</a>
                        </td>
                    </tr>
                </table>
                <?php
                break;
                
            case 'welcome-parent':
                ?>
                <h1 style="<?php echo $h1; ?>">Welcome to PTP Soccer</h1>
                <p style="<?php echo $subtitle; ?>">Hi <?php echo esc_html($data['name']); ?>, thanks for joining PTP! We connect families with elite soccer trainers for personalized 1-on-1 sessions.</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F9FAFB; border-radius: 12px; margin-bottom: 28px;">
                    <tr>
                        <td style="padding: 24px;">
                            <p style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: #0E0F11;">What makes PTP different?</p>
                            <ul style="margin: 0; padding-left: 20px; color: #374151; font-size: 14px; line-height: 1.8;">
                                <li>All trainers are current or former D1/Pro players</li>
                                <li>Personalized training focused on your player's needs</li>
                                <li>Flexible scheduling at locations near you</li>
                                <li>Easy booking and secure payments</li>
                            </ul>
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td align="center">
                            <a href="<?php echo esc_url($data['trainers_url']); ?>" style="<?php echo $btn_primary; ?>" target="_blank">Find a Trainer</a>
                        </td>
                    </tr>
                </table>
                <?php
                break;
                
            case 'review-request':
                ?>
                <h1 style="<?php echo $h1; ?>">How Was the Session?</h1>
                <p style="<?php echo $subtitle; ?>">Hi <?php echo esc_html($data['parent_name']); ?>, we hope <?php echo esc_html($data['player_name']); ?>'s session with <?php echo esc_html($data['trainer_name']); ?> went great!</p>
                
                <p style="margin: 0 0 28px; font-size: 15px; color: #374151; line-height: 1.6;">Your feedback helps other families find great trainers and helps our trainers improve. It only takes a minute!</p>
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td align="center">
                            <a href="<?php echo esc_url($data['review_url']); ?>" style="<?php echo $btn_primary; ?>" target="_blank">Leave a Review</a>
                        </td>
                    </tr>
                </table>
                <?php
                break;
                
            default:
                ?>
                <p style="font-size: 15px; color: #374151;">Thank you for using PTP Soccer.</p>
                <?php
                break;
        }
        
        return ob_get_clean();
    }
}
