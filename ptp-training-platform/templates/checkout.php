<?php
/**
 * Template: Checkout v29.5.1
 * Logo + Mobile Optimized, Secure Stripe payment
 */
defined('ABSPATH') || exit;

$logo_url = PTP_Images::logo();

// Get booking data from URL params
$trainer_id = isset($_GET['trainer_id']) ? intval($_GET['trainer_id']) : 0;
$date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
$time = isset($_GET['time']) ? sanitize_text_field($_GET['time']) : '';
$location_param = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
$booking_id = isset($_GET['booking']) ? intval($_GET['booking']) : 0;

global $wpdb;

$booking = null;
$trainer = null;

// Get from existing booking
if ($booking_id) {
    $booking = $wpdb->get_row($wpdb->prepare("
        SELECT b.*, t.display_name as trainer_name, t.photo_url as trainer_photo, 
               t.hourly_rate, t.stripe_account_id, t.stripe_charges_enabled, t.slug,
               t.city, t.state, p.name as player_name
        FROM {$wpdb->prefix}ptp_bookings b
        JOIN {$wpdb->prefix}ptp_trainers t ON b.trainer_id = t.id
        LEFT JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
        WHERE b.id = %d
    ", $booking_id));
    
    if ($booking) {
        $trainer = PTP_Trainer::get($booking->trainer_id);
        $date = date('Y-m-d', strtotime($booking->session_date));
        $time = date('H:i:s', strtotime($booking->session_date));
        $location_param = $booking->location ?? '';
    }
}

// Get trainer by ID
if (!$trainer && $trainer_id) {
    $trainer = PTP_Trainer::get($trainer_id);
}

// Redirect if no trainer
if (!$trainer) {
    wp_redirect(home_url('/find-trainers/'));
    exit;
}

// Calculate amounts
$rate = (int)($trainer->hourly_rate ?: 80);
$platform_fee = round($rate * 0.20, 2);
$trainer_earnings = $rate - $platform_fee;

// Trainer data
$trainer_photo = $trainer->photo_url ?: PTP_Images::avatar($trainer->display_name, 120);
$first_name = explode(' ', $trainer->display_name)[0];
// Use location from URL parameter first, fallback to trainer's default location
$location = $location_param ?: ($trainer->city && $trainer->state ? $trainer->city . ', ' . $trainer->state : 'Training Location');

// Format date/time for display
$display_date = $date ? date('l, F j, Y', strtotime($date)) : 'Select a date';
$display_time = $time ? date('g:i A', strtotime($time)) : 'Select a time';

// Stripe
$stripe_key = class_exists('PTP_Stripe') ? PTP_Stripe::get_publishable_key() : '';
$stripe_enabled = class_exists('PTP_Stripe') ? PTP_Stripe::is_enabled() : false;
$direct_to_trainer = $trainer && !empty($trainer->stripe_account_id) && !empty($trainer->stripe_charges_enabled);

// Check if user logged in
$is_logged_in = is_user_logged_in();
$current_user = $is_logged_in ? wp_get_current_user() : null;

wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - PTP Training</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body style="margin:0;padding:0;font-family:'Inter',-apple-system,sans-serif;background:#F3F4F6;min-height:100vh;-webkit-font-smoothing:antialiased">

<div style="max-width:500px;margin:0 auto;padding:20px;min-height:100vh">
    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <a href="<?php echo esc_url(home_url('/trainer/' . $trainer->slug . '/')); ?>" style="display:flex;align-items:center;gap:8px;color:#6B7280;text-decoration:none;font-size:14px;font-weight:500">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Back
        </a>
        <div style="display:flex;align-items:center;gap:8px">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span style="font-size:13px;color:#10B981;font-weight:600">Secure Checkout</span>
        </div>
    </div>

    <!-- Trainer Card -->
    <div style="background:#fff;border-radius:20px;padding:20px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,0.04)">
        <div style="display:flex;gap:16px;align-items:center">
            <img src="<?php echo esc_url($trainer_photo); ?>" alt="<?php echo esc_attr($trainer->display_name); ?>" style="width:64px;height:64px;border-radius:12px;object-fit:cover">
            <div style="flex:1">
                <h2 style="font-size:18px;font-weight:700;color:#111;margin:0 0 4px"><?php echo esc_html($trainer->display_name); ?></h2>
                <p style="font-size:14px;color:#6B7280;margin:0"><?php echo esc_html($trainer->headline ?: 'Soccer Trainer'); ?></p>
            </div>
        </div>
    </div>

    <!-- Session Details -->
    <div style="background:#fff;border-radius:20px;padding:20px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,0.04)">
        <h3 style="font-size:16px;font-weight:700;color:#111;margin:0 0 16px">Session Details</h3>
        
        <div style="display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#F9FAFB;border-radius:12px">
                <div style="width:40px;height:40px;background:#FEF3C7;border-radius:10px;display:flex;align-items:center;justify-content:center">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div>
                    <div style="font-size:14px;font-weight:600;color:#111"><?php echo esc_html($display_date); ?></div>
                    <div style="font-size:13px;color:#6B7280">Date</div>
                </div>
            </div>
            
            <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#F9FAFB;border-radius:12px">
                <div style="width:40px;height:40px;background:#DBEAFE;border-radius:10px;display:flex;align-items:center;justify-content:center">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <div style="font-size:14px;font-weight:600;color:#111"><?php echo esc_html($display_time); ?></div>
                    <div style="font-size:13px;color:#6B7280">Time (1 hour session)</div>
                </div>
            </div>
            
            <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#F9FAFB;border-radius:12px">
                <div style="width:40px;height:40px;background:#FCE7F3;border-radius:10px;display:flex;align-items:center;justify-content:center">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#EC4899" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <div>
                    <div style="font-size:14px;font-weight:600;color:#111"><?php echo esc_html($location); ?></div>
                    <div style="font-size:13px;color:#6B7280">Location</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Form -->
    <div style="background:#fff;border-radius:20px;padding:20px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,0.04)">
        <h3 style="font-size:16px;font-weight:700;color:#111;margin:0 0 16px">Payment Details</h3>
        
        <form id="ptp-checkout-form">
            <input type="hidden" name="trainer_id" value="<?php echo (int)$trainer->id; ?>">
            <input type="hidden" name="date" value="<?php echo esc_attr($date); ?>">
            <input type="hidden" name="time" value="<?php echo esc_attr($time); ?>">
            <input type="hidden" name="location" value="<?php echo esc_attr($location); ?>">
            <input type="hidden" name="amount" value="<?php echo $rate; ?>">
            
            <?php if (!$is_logged_in): ?>
            <!-- Guest Info -->
            <div style="margin-bottom:16px">
                <label style="display:block;font-size:14px;font-weight:600;color:#111;margin-bottom:8px">Your Name</label>
                <input type="text" name="customer_name" required style="width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:15px;outline:none;transition:border-color 0.2s" placeholder="John Smith">
            </div>
            <div style="margin-bottom:16px">
                <label style="display:block;font-size:14px;font-weight:600;color:#111;margin-bottom:8px">Email</label>
                <input type="email" name="customer_email" required style="width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:15px;outline:none;transition:border-color 0.2s" placeholder="john@example.com">
            </div>
            <div style="margin-bottom:16px">
                <label style="display:block;font-size:14px;font-weight:600;color:#111;margin-bottom:8px">Phone</label>
                <input type="tel" name="customer_phone" required style="width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:15px;outline:none;transition:border-color 0.2s" placeholder="(555) 123-4567">
            </div>
            <?php else: ?>
            <input type="hidden" name="customer_name" value="<?php echo esc_attr($current_user->display_name); ?>">
            <input type="hidden" name="customer_email" value="<?php echo esc_attr($current_user->user_email); ?>">
            <div style="padding:14px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px;margin-bottom:16px;display:flex;align-items:center;gap:10px">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span style="font-size:14px;color:#166534">Logged in as <strong><?php echo esc_html($current_user->display_name); ?></strong></span>
            </div>
            <?php endif; ?>

            <!-- Player Info -->
            <div style="margin-bottom:16px">
                <label style="display:block;font-size:14px;font-weight:600;color:#111;margin-bottom:8px">Player Name</label>
                <input type="text" name="player_name" required style="width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:15px;outline:none;transition:border-color 0.2s" placeholder="Player's name">
            </div>
            <div style="margin-bottom:20px">
                <label style="display:block;font-size:14px;font-weight:600;color:#111;margin-bottom:8px">Player Age</label>
                <select name="player_age" required style="width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:15px;outline:none;background:#fff;cursor:pointer">
                    <option value="">Select age</option>
                    <?php for ($a = 5; $a <= 18; $a++): ?>
                    <option value="<?php echo $a; ?>"><?php echo $a; ?> years old</option>
                    <?php endfor; ?>
                    <option value="adult">Adult (18+)</option>
                </select>
            </div>

            <!-- Card Element -->
            <div style="margin-bottom:16px">
                <label style="display:block;font-size:14px;font-weight:600;color:#111;margin-bottom:8px">Card Information</label>
                <div id="card-element" style="padding:16px;border:2px solid #E5E7EB;border-radius:12px;background:#fff"></div>
                <div id="card-errors" style="color:#EF4444;font-size:13px;margin-top:8px"></div>
            </div>

            <!-- Notes -->
            <div style="margin-bottom:20px">
                <label style="display:block;font-size:14px;font-weight:600;color:#111;margin-bottom:8px">Notes for Trainer (optional)</label>
                <textarea name="notes" rows="3" style="width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:15px;outline:none;resize:vertical" placeholder="Any specific goals or things to work on?"></textarea>
            </div>
        </form>
    </div>

    <!-- Order Summary -->
    <div style="background:#fff;border-radius:20px;padding:20px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,0.04)">
        <h3 style="font-size:16px;font-weight:700;color:#111;margin:0 0 16px">Order Summary</h3>
        
        <div style="display:flex;justify-content:space-between;margin-bottom:12px">
            <span style="font-size:14px;color:#6B7280">1-Hour Training Session</span>
            <span style="font-size:14px;font-weight:600;color:#111">$<?php echo number_format($rate, 2); ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:12px">
            <span style="font-size:14px;color:#6B7280">Platform Fee</span>
            <span style="font-size:14px;color:#6B7280">Included</span>
        </div>
        <div style="border-top:2px solid #E5E7EB;margin:16px 0;padding-top:16px;display:flex;justify-content:space-between">
            <span style="font-size:16px;font-weight:700;color:#111">Total</span>
            <span style="font-size:24px;font-weight:800;color:#111">$<?php echo number_format($rate, 2); ?></span>
        </div>
    </div>

    <!-- Submit Button -->
    <button type="button" id="submit-btn" onclick="ptpSubmitPayment()" style="width:100%;padding:18px;background:#FCB900;color:#0E0F11;border:none;border-radius:14px;font-family:inherit;font-size:17px;font-weight:700;cursor:pointer;margin-bottom:16px;display:flex;align-items:center;justify-content:center;gap:8px">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Pay $<?php echo number_format($rate, 2); ?>
    </button>

    <!-- Trust Badges -->
    <div style="display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap;margin-bottom:20px">
        <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:#6B7280">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            SSL Encrypted
        </span>
        <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:#6B7280">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Stripe Powered
        </span>
    </div>

    <!-- Cancellation Policy -->
    <div style="text-align:center;padding:20px;background:#F9FAFB;border-radius:12px">
        <p style="font-size:13px;color:#6B7280;margin:0;line-height:1.6">
            <strong style="color:#111">Free cancellation</strong> up to 24 hours before your session. 
            By booking, you agree to our <a href="#" style="color:#FCB900">Terms of Service</a>.
        </p>
    </div>
</div>

<!-- Loading Overlay -->
<div id="ptp-loading" style="display:none;position:fixed;inset:0;background:rgba(255,255,255,0.95);z-index:9999;align-items:center;justify-content:center;flex-direction:column;gap:16px">
    <div style="width:48px;height:48px;border:4px solid #E5E7EB;border-top-color:#FCB900;border-radius:50%;animation:spin 1s linear infinite"></div>
    <p style="font-size:16px;font-weight:600;color:#111">Processing payment...</p>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
input:focus, select:focus, textarea:focus { border-color: #FCB900 !important; }
#submit-btn:hover { background: #E5A800; }
#submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }
</style>

<script>
<?php if ($stripe_enabled && $stripe_key): ?>
var stripe = Stripe('<?php echo esc_js($stripe_key); ?>');
var elements = stripe.elements();
var cardElement = elements.create('card', {
    style: {
        base: {
            fontSize: '16px',
            color: '#111827',
            fontFamily: 'Inter, -apple-system, sans-serif',
            '::placeholder': { color: '#9CA3AF' }
        },
        invalid: { color: '#EF4444' }
    }
});
cardElement.mount('#card-element');

cardElement.on('change', function(event) {
    var display = document.getElementById('card-errors');
    display.textContent = event.error ? event.error.message : '';
});

function ptpSubmitPayment() {
    var form = document.getElementById('ptp-checkout-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    var btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.innerHTML = 'Processing...';
    document.getElementById('ptp-loading').style.display = 'flex';

    var formData = new FormData(form);
    formData.append('action', 'ptp_create_payment_intent');
    formData.append('nonce', '<?php echo wp_create_nonce('ptp_checkout'); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success && data.data.client_secret) {
            return stripe.confirmCardPayment(data.data.client_secret, {
                payment_method: { card: cardElement }
            });
        } else {
            throw new Error(data.data || 'Failed to create payment');
        }
    })
    .then(function(result) {
        if (result.error) {
            throw new Error(result.error.message);
        }
        // Payment successful - redirect to confirmation
        window.location.href = '<?php echo home_url('/booking-confirmation/'); ?>?payment_intent=' + result.paymentIntent.id;
    })
    .catch(function(error) {
        document.getElementById('card-errors').textContent = error.message;
        btn.disabled = false;
        btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Pay $<?php echo number_format($rate, 2); ?>';
        document.getElementById('ptp-loading').style.display = 'none';
    });
}
<?php else: ?>
function ptpSubmitPayment() {
    alert('Payment processing is not configured. Please contact support.');
}
<?php endif; ?>
</script>

<?php wp_footer(); ?>
</body>
</html>
