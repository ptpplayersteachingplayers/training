<?php
/**
 * Template: Register v29.5.1
 * Logo + Mobile Optimized
 */
defined('ABSPATH') || exit;

if (is_user_logged_in()) {
    wp_redirect(home_url('/my-training/'));
    exit;
}

$error = '';
$success = false;

if (isset($_POST['ptp_register']) && wp_verify_nonce($_POST['ptp_register_nonce'], 'ptp_register')) {
    $email = sanitize_email($_POST['email']);
    $name = sanitize_text_field($_POST['name']);
    $password = $_POST['password'];
    $phone = sanitize_text_field($_POST['phone']);
    
    if (empty($email) || empty($name) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!is_email($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (email_exists($email)) {
        $error = 'An account with this email already exists.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $user_id = wp_create_user($email, $password, $email);
        if (is_wp_error($user_id)) {
            $error = $user_id->get_error_message();
        } else {
            wp_update_user(array('ID' => $user_id, 'display_name' => $name, 'first_name' => explode(' ', $name)[0]));
            update_user_meta($user_id, 'phone', $phone);
            update_user_meta($user_id, 'ptp_role', 'parent');
            
            // Auto login
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            wp_redirect(home_url('/my-training/'));
            exit;
        }
    }
}

$logo_url = PTP_Images::logo();
$bg_image = PTP_Images::get('BG7A1797');

get_header();
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
*{box-sizing:border-box;margin:0;padding:0}
.ptp-auth{font-family:'Inter',-apple-system,sans-serif;min-height:100vh;display:flex;-webkit-font-smoothing:antialiased}
.ptp-auth-left{flex:1;background:linear-gradient(135deg,rgba(14,15,17,0.9),rgba(14,15,17,0.7)),url('<?php echo esc_url($bg_image); ?>') center/cover;display:flex;flex-direction:column;justify-content:center;padding:60px}
.ptp-auth-right{width:480px;background:#fff;display:flex;align-items:center;justify-content:center;padding:40px}
.ptp-auth-card{width:100%;max-width:360px}
.ptp-auth input:focus{border-color:#FCB900!important;outline:none}
@media(max-width:968px){
    .ptp-auth-left{display:none}
    .ptp-auth-right{width:100%;min-height:100vh}
}
@media(max-width:480px){
    .ptp-auth-right{padding:24px 16px}
    .ptp-auth-card{max-width:100%}
}
</style>

<div class="ptp-auth">
    <!-- Left Side - Image + Branding (hidden on mobile) -->
    <div class="ptp-auth-left">
        <img src="<?php echo esc_url($logo_url); ?>" alt="PTP Soccer" style="height:45px;max-width:180px;width:auto;object-fit:contain;margin-bottom:40px">
        <h2 style="font-size:42px;font-weight:800;color:#fff;margin:0 0 16px;line-height:1.1">Train Like<br>a Pro</h2>
        <p style="font-size:18px;color:rgba(255,255,255,0.7);margin:0;max-width:400px;line-height:1.6">Join thousands of players who've elevated their game with personalized training from elite NCAA and professional athletes.</p>
    </div>
    
    <!-- Right Side - Register Form -->
    <div class="ptp-auth-right">
        <div class="ptp-auth-card">
            <!-- Mobile Logo -->
            <div style="text-align:center;margin-bottom:32px">
                <img src="<?php echo esc_url($logo_url); ?>" alt="PTP Soccer" style="height:40px;max-width:160px;width:auto;object-fit:contain;margin-bottom:20px">
                <h1 style="font-size:24px;font-weight:800;color:#0E0F11;margin:0 0 4px">Create Account</h1>
                <p style="font-size:15px;color:#6B7280;margin:0">Join PTP to book training sessions</p>
            </div>
            
            <?php if ($error): ?>
            <div style="padding:14px 16px;background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span style="font-size:14px;color:#DC2626"><?php echo esc_html($error); ?></span>
            </div>
            <?php endif; ?>
            
            <form method="post">
                <?php wp_nonce_field('ptp_register', 'ptp_register_nonce'); ?>
                
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:14px;font-weight:600;color:#374151;margin-bottom:8px">Full Name *</label>
                    <input type="text" name="name" required style="width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:16px" placeholder="John Smith" value="<?php echo isset($_POST['name']) ? esc_attr($_POST['name']) : ''; ?>">
                </div>
                
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:14px;font-weight:600;color:#374151;margin-bottom:8px">Email *</label>
                    <input type="email" name="email" required style="width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:16px" placeholder="john@example.com" value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
                </div>
                
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:14px;font-weight:600;color:#374151;margin-bottom:8px">Phone</label>
                    <input type="tel" name="phone" style="width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:16px" placeholder="(555) 123-4567" value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>">
                </div>
                
                <div style="margin-bottom:20px">
                    <label style="display:block;font-size:14px;font-weight:600;color:#374151;margin-bottom:8px">Password *</label>
                    <input type="password" name="password" required minlength="8" style="width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:16px" placeholder="Minimum 8 characters">
                </div>
                
                <div style="margin-bottom:24px">
                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer">
                        <input type="checkbox" name="terms" required style="width:18px;height:18px;accent-color:#FCB900;margin-top:2px;flex-shrink:0">
                        <span style="font-size:14px;color:#6B7280;line-height:1.5">I agree to the <a href="#" style="color:#FCB900;text-decoration:none">Terms of Service</a> and <a href="#" style="color:#FCB900;text-decoration:none">Privacy Policy</a></span>
                    </label>
                </div>
                
                <button type="submit" name="ptp_register" value="1" style="width:100%;padding:16px;background:#FCB900;color:#0E0F11;border:none;border-radius:12px;font-family:inherit;font-size:16px;font-weight:700;cursor:pointer;margin-bottom:16px">
                    Create Account
                </button>
            </form>
            
            <div style="text-align:center;padding-top:20px;border-top:1px solid #E5E7EB">
                <p style="font-size:15px;color:#6B7280;margin:0">
                    Already have an account? 
                    <a href="<?php echo esc_url(home_url('/login/')); ?>" style="color:#FCB900;font-weight:600;text-decoration:none">Sign in</a>
                </p>
            </div>
            
            <div style="text-align:center;margin-top:20px">
                <p style="font-size:14px;color:#9CA3AF;margin:0">
                    Are you a D1 or Pro player? 
                    <a href="<?php echo esc_url(home_url('/apply/')); ?>" style="color:#6B7280;text-decoration:underline">Apply as trainer</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
