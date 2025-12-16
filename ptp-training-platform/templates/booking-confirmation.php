<?php
/**
 * Template: Booking Confirmation v29.5.1
 * Logo + Mobile Optimized
 */
defined('ABSPATH') || exit;

$logo_url = PTP_Images::logo();

$payment_intent = isset($_GET['payment_intent']) ? sanitize_text_field($_GET['payment_intent']) : '';
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

global $wpdb;
$booking = null;
$trainer = null;

// Try to get booking by payment intent or ID
if ($payment_intent) {
    $booking = $wpdb->get_row($wpdb->prepare(
        "SELECT b.*, t.display_name as trainer_name, t.photo_url, t.slug, t.city, t.state
         FROM {$wpdb->prefix}ptp_bookings b 
         JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id 
         WHERE b.stripe_payment_intent = %s",
        $payment_intent
    ));
} elseif ($booking_id) {
    $booking = $wpdb->get_row($wpdb->prepare(
        "SELECT b.*, t.display_name as trainer_name, t.photo_url, t.slug, t.city, t.state
         FROM {$wpdb->prefix}ptp_bookings b 
         JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id 
         WHERE b.id = %d",
        $booking_id
    ));
}

$trainer_photo = $booking && $booking->photo_url ? $booking->photo_url : PTP_Images::avatar($booking ? $booking->trainer_name : 'Trainer', 120);
$trainer_name = $booking ? $booking->trainer_name : 'Your Trainer';
$session_date = $booking ? date('l, F j, Y', strtotime($booking->session_date)) : 'Date TBD';
$session_time = $booking ? date('g:i A', strtotime($booking->session_date)) : 'Time TBD';
$location = $booking && $booking->city ? $booking->city . ', ' . $booking->state : 'Training Location';
$amount = $booking ? number_format($booking->total_amount, 2) : '0.00';

get_header();
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
*{box-sizing:border-box;margin:0;padding:0}
.ptp-confirm{font-family:'Inter',-apple-system,sans-serif;background:#F3F4F6;min-height:80vh;padding:40px 20px;-webkit-font-smoothing:antialiased}
@keyframes checkmark{0%{transform:scale(0)}50%{transform:scale(1.2)}100%{transform:scale(1)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.ptp-confirm-card{animation:fadeUp 0.5s ease}
.ptp-confirm-check{animation:checkmark 0.5s ease 0.2s both}
@media(max-width:480px){.ptp-confirm{padding:24px 16px}.ptp-confirm-card{padding:32px 24px!important}}
</style>

<div class="ptp-confirm">
    <div style="max-width:500px;margin:0 auto">
        <!-- Logo -->
        <div style="text-align:center;margin-bottom:24px">
            <img src="<?php echo esc_url($logo_url); ?>" alt="PTP Soccer" style="height:40px;max-width:160px;width:auto;object-fit:contain">
        </div>
        
        <!-- Success Card -->
        <div class="ptp-confirm-card" style="background:#fff;border-radius:24px;padding:40px 32px;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,0.08);margin-bottom:20px">
            <!-- Check Icon -->
            <div class="ptp-confirm-check" style="width:80px;height:80px;background:#ECFDF5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            
            <h1 style="font-size:28px;font-weight:800;color:#111;margin:0 0 8px">Booking Confirmed!</h1>
            <p style="font-size:16px;color:#6B7280;margin:0 0 32px">Your training session has been scheduled</p>
            
            <!-- Trainer -->
            <div style="display:flex;align-items:center;gap:16px;padding:20px;background:#F9FAFB;border-radius:16px;margin-bottom:24px;text-align:left">
                <img src="<?php echo esc_url($trainer_photo); ?>" alt="<?php echo esc_attr($trainer_name); ?>" style="width:64px;height:64px;border-radius:12px;object-fit:cover">
                <div>
                    <div style="font-size:18px;font-weight:700;color:#111;margin-bottom:2px"><?php echo esc_html($trainer_name); ?></div>
                    <div style="font-size:14px;color:#6B7280">Your Trainer</div>
                </div>
            </div>
            
            <!-- Details -->
            <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:32px">
                <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#F9FAFB;border-radius:12px">
                    <div style="width:40px;height:40px;background:#FEF3C7;border-radius:10px;display:flex;align-items:center;justify-content:center">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div style="text-align:left">
                        <div style="font-size:15px;font-weight:600;color:#111"><?php echo esc_html($session_date); ?></div>
                        <div style="font-size:13px;color:#6B7280">Date</div>
                    </div>
                </div>
                
                <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#F9FAFB;border-radius:12px">
                    <div style="width:40px;height:40px;background:#DBEAFE;border-radius:10px;display:flex;align-items:center;justify-content:center">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div style="text-align:left">
                        <div style="font-size:15px;font-weight:600;color:#111"><?php echo esc_html($session_time); ?></div>
                        <div style="font-size:13px;color:#6B7280">Time</div>
                    </div>
                </div>
                
                <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#F9FAFB;border-radius:12px">
                    <div style="width:40px;height:40px;background:#FCE7F3;border-radius:10px;display:flex;align-items:center;justify-content:center">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#EC4899" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div style="text-align:left">
                        <div style="font-size:15px;font-weight:600;color:#111"><?php echo esc_html($location); ?></div>
                        <div style="font-size:13px;color:#6B7280">Location</div>
                    </div>
                </div>
            </div>
            
            <!-- Amount -->
            <div style="padding:16px;background:#ECFDF5;border-radius:12px;margin-bottom:24px">
                <div style="font-size:14px;color:#059669;margin-bottom:4px">Amount Paid</div>
                <div style="font-size:28px;font-weight:800;color:#059669">$<?php echo $amount; ?></div>
            </div>
            
            <!-- Actions -->
            <div style="display:flex;flex-direction:column;gap:12px">
                <a href="<?php echo esc_url(home_url('/my-training/')); ?>" style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:16px;background:#FCB900;color:#0E0F11;border-radius:12px;font-size:16px;font-weight:700;text-decoration:none">
                    View My Sessions
                </a>
                <a href="<?php echo esc_url(home_url('/find-trainers/')); ?>" style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:16px;background:#fff;color:#374151;border:2px solid #E5E7EB;border-radius:12px;font-size:16px;font-weight:600;text-decoration:none">
                    Find More Trainers
                </a>
            </div>
        </div>
        
        <!-- Info -->
        <div style="text-align:center;padding:20px">
            <p style="font-size:14px;color:#6B7280;margin:0 0 8px">
                📧 A confirmation email has been sent to you
            </p>
            <p style="font-size:14px;color:#6B7280;margin:0">
                Questions? <a href="<?php echo esc_url(home_url('/contact/')); ?>" style="color:#FCB900;font-weight:600;text-decoration:none">Contact Support</a>
            </p>
        </div>
    </div>
</div>

<?php get_footer(); ?>
