<?php
/**
 * Template: Login v29.5.1
 * Logo + Mobile Optimized
 */
defined('ABSPATH') || exit;

if (is_user_logged_in()) {
    wp_redirect(home_url('/my-training/'));
    exit;
}

$error = '';
if (isset($_POST['ptp_login']) && wp_verify_nonce($_POST['ptp_login_nonce'], 'ptp_login')) {
    $creds = array(
        'user_login' => sanitize_user($_POST['username']),
        'user_password' => $_POST['password'],
        'remember' => isset($_POST['remember'])
    );
    $user = wp_signon($creds, is_ssl());
    if (is_wp_error($user)) {
        $error = 'Invalid username or password.';
    } else {
        $redirect = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : home_url('/my-training/');
        wp_redirect($redirect);
        exit;
    }
}

$logo_url = PTP_Images::logo();
$bg_image = PTP_Images::get('BG7A1874');

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
        <h2 style="font-size:42px;font-weight:800;color:#fff;margin:0 0 16px;line-height:1.1">Elite Soccer<br>Training</h2>
        <p style="font-size:18px;color:rgba(255,255,255,0.7);margin:0;max-width:400px;line-height:1.6">Train with current MLS players and NCAA Division 1 athletes. Personalized 1-on-1 sessions that fit your schedule.</p>
    </div>
    
    <!-- Right Side - Login Form -->
    <div class="ptp-auth-right">
        <div class="ptp-auth-card">
            <!-- Mobile Logo -->
            <div style="text-align:center;margin-bottom:32px">
                <img src="<?php echo esc_url($logo_url); ?>" alt="PTP Soccer" style="height:40px;max-width:160px;width:auto;object-fit:contain;margin-bottom:20px">
                <h1 style="font-size:24px;font-weight:800;color:#0E0F11;margin:0 0 4px">Welcome Back</h1>
                <p style="font-size:15px;color:#6B7280;margin:0">Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
            <div style="padding:14px 16px;background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span style="font-size:14px;color:#DC2626"><?php echo esc_html($error); ?></span>
            </div>
            <?php endif; ?>
            
            <form method="post">
                <?php wp_nonce_field('ptp_login', 'ptp_login_nonce'); ?>
                
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:14px;font-weight:600;color:#374151;margin-bottom:8px">Email or Username</label>
                    <input type="text" name="username" required style="width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:16px;transition:border-color 0.2s" placeholder="Enter your email">
                </div>
                
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:14px;font-weight:600;color:#374151;margin-bottom:8px">Password</label>
                    <input type="password" name="password" required style="width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:16px;transition:border-color 0.2s" placeholder="Enter your password">
                </div>
                
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="remember" style="width:18px;height:18px;accent-color:#FCB900">
                        <span style="font-size:14px;color:#6B7280">Remember me</span>
                    </label>
                    <a href="<?php echo wp_lostpassword_url(); ?>" style="font-size:14px;color:#FCB900;font-weight:600;text-decoration:none">Forgot password?</a>
                </div>
                
                <button type="submit" name="ptp_login" value="1" style="width:100%;padding:16px;background:#FCB900;color:#0E0F11;border:none;border-radius:12px;font-family:inherit;font-size:16px;font-weight:700;cursor:pointer;margin-bottom:16px;transition:background 0.2s">
                    Sign In
                </button>
            </form>
            
            <div style="text-align:center;padding-top:20px;border-top:1px solid #E5E7EB">
                <p style="font-size:15px;color:#6B7280;margin:0">
                    Don't have an account? 
                    <a href="<?php echo esc_url(home_url('/register/')); ?>" style="color:#FCB900;font-weight:600;text-decoration:none">Sign up</a>
                </p>
            </div>
            
            <div style="text-align:center;margin-top:20px">
                <p style="font-size:14px;color:#9CA3AF;margin:0">
                    Are you a trainer? 
                    <a href="<?php echo esc_url(home_url('/apply/')); ?>" style="color:#6B7280;text-decoration:underline">Apply here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
