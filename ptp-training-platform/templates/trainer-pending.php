<?php
/**
 * Template: Trainer Pending Approval - PTP Style v23.4
 */
defined('ABSPATH') || exit;

$user = wp_get_current_user();
$first_name = $user->first_name ?: $user->display_name;
?>

<style>
html, body { overflow-x: hidden !important; max-width: 100vw; }
.ptp-pending-wrap { min-height: 100vh; background: linear-gradient(135deg, #0E0F11 0%, #1a1a1a 100%); display: flex; align-items: center; justify-content: center; padding: 40px 20px; overflow-x: hidden; }
.ptp-pending-card { background: #fff; border-radius: 24px; max-width: 560px; width: 100%; overflow: hidden; box-shadow: 0 25px 80px rgba(0,0,0,0.4); }
.ptp-pending-header { background: linear-gradient(135deg, #FCB900 0%, #F59E0B 100%); padding: 50px 40px; text-align: center; position: relative; }
.ptp-pending-icon { width: 100px; height: 100px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; animation: pulse 2s ease-in-out infinite; box-shadow: 0 8px 24px rgba(0,0,0,0.15); }
.ptp-pending-icon svg { width: 50px; height: 50px; color: #FCB900; }
@keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
.ptp-pending-title { font-family: 'Oswald', sans-serif; font-size: 28px; font-weight: 700; color: #0E0F11; margin: 0 0 8px; }
.ptp-pending-subtitle { font-size: 16px; color: rgba(0,0,0,0.7); margin: 0; }
.ptp-pending-body { padding: 40px; }
.ptp-pending-message { font-size: 16px; color: #4B5563; line-height: 1.7; margin: 0 0 24px; text-align: center; }
.ptp-pending-message strong { color: #111827; }
.ptp-pending-steps { background: #F9FAFB; border-radius: 16px; padding: 24px; margin-bottom: 32px; }
.ptp-pending-steps-title { font-family: 'Oswald', sans-serif; font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px; display: flex; align-items: center; gap: 10px; }
.ptp-pending-step { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid #E5E7EB; }
.ptp-pending-step:last-child { border-bottom: none; padding-bottom: 0; }
.ptp-pending-step:first-of-type { padding-top: 0; }
.ptp-pending-step-num { width: 28px; height: 28px; background: linear-gradient(135deg, #FCB900 0%, #F59E0B 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; color: #0E0F11; flex-shrink: 0; }
.ptp-pending-step-text { font-size: 14px; color: #4B5563; line-height: 1.5; }
.ptp-pending-actions { display: flex; gap: 12px; }
.ptp-pending-btn { flex: 1; padding: 16px; border-radius: 12px; font-weight: 700; font-size: 15px; text-decoration: none; text-align: center; transition: all 0.2s; }
.ptp-pending-btn-primary { background: linear-gradient(135deg, #FCB900 0%, #F59E0B 100%); color: #0E0F11; }
.ptp-pending-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(252,185,0,0.3); }
.ptp-pending-btn-outline { background: transparent; border: 2px solid #E5E7EB; color: #374151; }
.ptp-pending-btn-outline:hover { background: #F9FAFB; border-color: #D1D5DB; }
.ptp-pending-faq { margin-top: 32px; padding-top: 32px; border-top: 1px solid #E5E7EB; }
.ptp-pending-faq-title { font-family: 'Oswald', sans-serif; font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 20px; }
.ptp-pending-faq-item { margin-bottom: 20px; }
.ptp-pending-faq-item:last-child { margin-bottom: 0; }
.ptp-pending-faq-q { font-weight: 600; font-size: 14px; color: #111827; margin: 0 0 6px; }
.ptp-pending-faq-a { font-size: 14px; color: #6B7280; margin: 0; line-height: 1.6; }
</style>

<div class="ptp-pending-wrap">
    <div class="ptp-pending-card">
        <div class="ptp-pending-header">
            <div class="ptp-pending-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <h1 class="ptp-pending-title">Application Under Review</h1>
            <p class="ptp-pending-subtitle">We're reviewing your trainer application</p>
        </div>
        
        <div class="ptp-pending-body">
            <p class="ptp-pending-message">
                Hi <strong><?php echo esc_html($first_name); ?></strong>! Your application has been submitted successfully. 
                Our team will review your information and you'll receive an email within <strong>48 hours</strong>.
            </p>
            
            <div class="ptp-pending-steps">
                <div class="ptp-pending-steps-title">
                    <span>📋</span> What happens next?
                </div>
                <div class="ptp-pending-step">
                    <span class="ptp-pending-step-num">1</span>
                    <span class="ptp-pending-step-text">We verify your playing experience and credentials</span>
                </div>
                <div class="ptp-pending-step">
                    <span class="ptp-pending-step-num">2</span>
                    <span class="ptp-pending-step-text">You'll receive an approval email with login instructions</span>
                </div>
                <div class="ptp-pending-step">
                    <span class="ptp-pending-step-num">3</span>
                    <span class="ptp-pending-step-text">Complete your profile with photos and availability</span>
                </div>
                <div class="ptp-pending-step">
                    <span class="ptp-pending-step-num">4</span>
                    <span class="ptp-pending-step-text">Start accepting bookings and training players!</span>
                </div>
            </div>
            
            <div class="ptp-pending-actions">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="ptp-pending-btn ptp-pending-btn-outline">Return Home</a>
                <a href="mailto:luke@ptpsummercamps.com" class="ptp-pending-btn ptp-pending-btn-primary">Contact Support</a>
            </div>
            
            <div class="ptp-pending-faq">
                <h3 class="ptp-pending-faq-title">FAQs</h3>
                <div class="ptp-pending-faq-item">
                    <h4 class="ptp-pending-faq-q">How long does approval take?</h4>
                    <p class="ptp-pending-faq-a">Most applications are reviewed within 24-48 hours. You'll receive an email notification once approved.</p>
                </div>
                <div class="ptp-pending-faq-item">
                    <h4 class="ptp-pending-faq-q">Can I edit my application?</h4>
                    <p class="ptp-pending-faq-a">Once approved, you can update all your profile information from your trainer dashboard.</p>
                </div>
                <div class="ptp-pending-faq-item">
                    <h4 class="ptp-pending-faq-q">Didn't receive a confirmation email?</h4>
                    <p class="ptp-pending-faq-a">Check your spam folder. If you still can't find it, contact us at luke@ptpsummercamps.com.</p>
                </div>
            </div>
        </div>
    </div>
</div>
