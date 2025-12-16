<?php
/**
 * Template: Trainer Profile v29.5.1
 * Logo + Mobile Optimized, social links, inline messaging
 */
defined('ABSPATH') || exit;

$logo_url = PTP_Images::logo();

// Get trainer
$slug = get_query_var('trainer_slug');
if (empty($slug)) $slug = isset($_GET['trainer']) ? sanitize_text_field($_GET['trainer']) : '';
if (empty($slug)) {
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    if (preg_match('#trainer/([^/]+)/?$#', $path, $m)) $slug = sanitize_text_field($m[1]);
}
$trainer = $slug ? PTP_Trainer::get_by_slug($slug) : null;

if (!$trainer || $trainer->status !== 'active') {
    get_header();
    echo '<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:24px;font-family:Inter,-apple-system,sans-serif">
        <div style="background:#fff;border-radius:24px;padding:48px 40px;text-align:center;max-width:420px;box-shadow:0 25px 50px -12px rgba(0,0,0,0.08)">
            <img src="' . esc_url($logo_url) . '" alt="PTP Soccer" style="height:40px;max-width:160px;width:auto;object-fit:contain;margin-bottom:24px">
            <h2 style="font-size:24px;font-weight:700;margin:0 0 12px;color:#111">Trainer Not Found</h2>
            <p style="color:#6B7280;margin:0 0 28px">This trainer profile isn\'t available.</p>
            <a href="' . esc_url(home_url('/find-trainers/')) . '" style="display:inline-flex;background:#FCB900;color:#0E0F11;padding:14px 32px;border-radius:12px;font-weight:700;text-decoration:none">Find Trainers</a>
        </div>
    </div>';
    get_footer();
    return;
}

// Profile data
$photo = $trainer->photo_url ?: PTP_Images::avatar($trainer->display_name, 500);
$rate = (int)($trainer->hourly_rate ?: 80);
$location = $trainer->city && $trainer->state ? $trainer->city . ', ' . $trainer->state : ($trainer->location ?: 'Philadelphia Area');
$rating = $trainer->average_rating ? number_format((float)$trainer->average_rating, 1) : '5.0';
$reviews = class_exists('PTP_Reviews') ? PTP_Reviews::get_trainer_reviews($trainer->id) : array();
$review_count = is_array($reviews) ? count($reviews) : 0;
$bio = $trainer->bio ?: '';
$headline = $trainer->headline ?: ($trainer->college ?: 'Professional Soccer Trainer');
$first_name = explode(' ', $trainer->display_name)[0];

// Social links
$instagram = !empty($trainer->instagram) ? trim($trainer->instagram) : '';
$facebook = !empty($trainer->facebook) ? trim($trainer->facebook) : '';

// Specialties
$specialties = array();
if (!empty($trainer->specialties)) {
    $decoded = json_decode($trainer->specialties, true);
    if (is_array($decoded)) $specialties = $decoded;
    else $specialties = array_filter(array_map('trim', explode(',', $trainer->specialties)));
}
$specialty_labels = array(
    'shooting'=>'Shooting','dribbling'=>'Dribbling','passing'=>'Passing','defense'=>'Defending',
    'defending'=>'Defending','goalkeeping'=>'Goalkeeping','speed'=>'Speed & Agility',
    'speed_agility'=>'Speed & Agility','fitness'=>'Fitness','tactics'=>'Tactics',
    'first_touch'=>'First Touch','ball_control'=>'Ball Control','finishing'=>'Finishing',
);

$level_labels = array('pro'=>'Pro','college_d1'=>'D1','college_d2'=>'D2','college_d3'=>'D3','academy'=>'Academy','semi_pro'=>'Semi-Pro');
$playing_level = $trainer->playing_level ?? '';
$level_label = isset($level_labels[$playing_level]) ? $level_labels[$playing_level] : '';

// Training locations
$training_locations = array();
if (!empty($trainer->training_locations)) {
    $decoded = json_decode($trainer->training_locations, true);
    if (is_array($decoded)) {
        foreach ($decoded as $loc) {
            if (is_array($loc) && !empty($loc['name'])) {
                $training_locations[] = $loc;
            }
        }
    }
}
if (empty($training_locations)) {
    $training_locations[] = array('id' => 'default', 'name' => $location, 'address' => $location);
}

// Current user for messaging
$current_user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();

get_header();
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
*{box-sizing:border-box}html,body{overflow-x:hidden!important;max-width:100vw}
.tp{font-family:'Inter',-apple-system,sans-serif;-webkit-font-smoothing:antialiased;background:#F5F5F5;color:#111;line-height:1.5}
.tp *{box-sizing:border-box}.tp h1,.tp h2,.tp h3,.tp p{margin:0}.tp img{max-width:100%;height:auto}

/* Hero */
.tp-hero{background:#0E0F11;padding:40px 20px 48px}
.tp-hero-inner{max-width:1100px;margin:0 auto;display:flex;gap:36px;align-items:flex-start}
.tp-hero-left{display:flex;flex-direction:column;align-items:center;flex-shrink:0}
.tp-hero-photo{width:260px;height:340px;border-radius:20px;overflow:hidden;border:4px solid rgba(252,185,0,0.6);box-shadow:0 24px 48px rgba(0,0,0,0.4)}
.tp-hero-photo img{width:100%;height:100%;object-fit:cover}
.tp-hero-social{display:flex;gap:12px;margin-top:16px}
.tp-hero-social a{width:44px;height:44px;background:rgba(255,255,255,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;transition:all 0.2s}
.tp-hero-social a:hover{background:#FCB900;color:#0E0F11}
.tp-hero-info{flex:1;color:#fff;padding-top:8px}

/* Main Layout */
.tp-main{max-width:1100px;margin:0 auto;padding:28px 20px 140px}
.tp-grid{display:grid;grid-template-columns:1fr 400px;gap:28px}
.tp-sidebar{position:sticky;top:20px;height:fit-content}

/* Cards */
.tp-card{background:#fff;border-radius:20px;padding:24px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);border:1px solid #E5E7EB}
.tp-card-title{font-size:18px;font-weight:700;margin-bottom:16px;color:#111}

/* Booking Steps */
.tp-steps{display:flex;gap:6px;margin-bottom:20px}
.tp-step{flex:1;padding:10px 6px;text-align:center;background:#F3F4F6;border-radius:8px;font-size:12px;font-weight:600;color:#9CA3AF;transition:all 0.2s}
.tp-step.active{background:#FCB900;color:#0E0F11}
.tp-step.done{background:#D1FAE5;color:#059669}

/* Calendar */
.tp-cal{display:grid;grid-template-columns:repeat(7,1fr);gap:4px}
.tp-cal-day{padding:12px 4px;font-size:14px;border-radius:10px;cursor:pointer;text-align:center;font-weight:600;transition:all 0.15s}
.tp-cal-day:hover:not(.disabled){background:#FEF3C7}
.tp-cal-day.available{background:#FFFBEB}
.tp-cal-day.selected{background:#FCB900!important;transform:scale(1.05)}
.tp-cal-day.disabled{color:#D1D5DB;cursor:default}

/* Time slots */
.tp-slot{padding:14px 12px;border:2px solid #E5E7EB;border-radius:10px;cursor:pointer;text-align:center;font-size:14px;font-weight:600;transition:all 0.15s}
.tp-slot:hover{border-color:#FCB900;background:#FFFBEB}
.tp-slot.selected{border-color:#FCB900;background:#FCB900}

/* Location cards */
.tp-loc{padding:16px;border:2px solid #E5E7EB;border-radius:12px;cursor:pointer;transition:all 0.15s;display:flex;align-items:center;gap:12px;margin-bottom:10px}
.tp-loc:hover{border-color:#FCB900;background:#FFFBEB}
.tp-loc.selected{border-color:#FCB900;background:#FEF3C7}
.tp-loc-icon{width:44px;height:44px;background:#F3F4F6;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.tp-loc.selected .tp-loc-icon{background:#FCB900}

/* Messaging */
.tp-msg-box{background:#F9FAFB;border-radius:16px;padding:20px;margin-top:20px}
.tp-msg-input{width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:15px;resize:none;outline:none;transition:border-color 0.2s}
.tp-msg-input:focus{border-color:#FCB900}
.tp-msg-btn{width:100%;padding:14px;background:#0E0F11;color:#fff;border:none;border-radius:12px;font-family:inherit;font-size:15px;font-weight:700;cursor:pointer;margin-top:12px;display:flex;align-items:center;justify-content:center;gap:8px}
.tp-msg-btn:hover{background:#1a1a1a}

/* Mobile booking bar */
.tp-book-bar{display:none;position:fixed;bottom:0;left:0;right:0;background:#fff;padding:16px 20px;box-shadow:0 -4px 20px rgba(0,0,0,0.12);z-index:100;border-top:1px solid #E5E7EB}

/* Responsive */
@media(max-width:1024px){
    .tp-grid{grid-template-columns:1fr}
    .tp-sidebar{position:relative;top:0}
    .tp-book-bar{display:flex;align-items:center;justify-content:space-between;gap:16px}
}
@media(max-width:768px){
    .tp-hero{padding:28px 16px 36px}
    .tp-hero-inner{flex-direction:column;align-items:center;text-align:center}
    .tp-hero-photo{width:200px;height:260px}
    .tp-hero-info{padding-top:0}
    .tp-main{padding:20px 16px 160px}
    .tp-card{padding:20px}
    .tp-hero-name{font-size:28px!important}
}
@media(max-width:480px){
    .tp-hero-photo{width:160px;height:210px}
    .tp-hero-name{font-size:24px!important}
    .tp-hero-social a{width:40px;height:40px}
    .tp-slot{padding:12px 8px;font-size:13px}
}
</style>

<div class="tp">
    <!-- Hero -->
    <div class="tp-hero">
        <div class="tp-hero-inner">
            <div class="tp-hero-left">
                <div class="tp-hero-photo">
                    <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($trainer->display_name); ?>">
                </div>
                
                <!-- Social Links -->
                <?php if ($instagram || $facebook): ?>
                <div class="tp-hero-social">
                    <?php if ($instagram): 
                        $insta_url = strpos($instagram, 'http') === 0 ? $instagram : 'https://instagram.com/' . ltrim($instagram, '@');
                    ?>
                    <a href="<?php echo esc_url($insta_url); ?>" target="_blank" title="Instagram">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($facebook): 
                        $fb_url = strpos($facebook, 'http') === 0 ? $facebook : 'https://facebook.com/' . $facebook;
                    ?>
                    <a href="<?php echo esc_url($fb_url); ?>" target="_blank" title="Facebook">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="tp-hero-info">
                <?php if ($level_label): ?>
                <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(252,185,0,0.2);border:1px solid rgba(252,185,0,0.5);padding:8px 14px;border-radius:100px;font-size:12px;font-weight:700;color:#FCB900;margin-bottom:14px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <?php echo esc_html($level_label); ?> Athlete
                </div>
                <?php endif; ?>
                
                <h1 class="tp-hero-name" style="font-size:38px;font-weight:900;margin-bottom:8px;color:#FFFFFF"><?php echo esc_html($trainer->display_name); ?></h1>
                <p style="font-size:17px;color:#9CA3AF;margin-bottom:18px"><?php echo esc_html($headline); ?></p>
                
                <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:20px">
                    <div style="display:flex;align-items:center;gap:6px;font-size:15px">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#FCB900"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <strong style="color:#fff"><?php echo esc_html($rating); ?></strong>
                        <span style="color:#9CA3AF">(<?php echo $review_count; ?>)</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;font-size:15px;color:#9CA3AF">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FCB900" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?php echo esc_html($location); ?>
                    </div>
                </div>
                
                <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:center">
                    <div style="background:rgba(255,255,255,0.1);padding:14px 24px;border-radius:14px">
                        <div style="font-size:32px;font-weight:900;color:#FCB900">$<?php echo $rate; ?></div>
                        <div style="font-size:13px;color:#9CA3AF">per hour</div>
                    </div>
                    <div style="background:rgba(16,185,129,0.2);padding:12px 20px;border-radius:12px;display:flex;align-items:center;gap:8px">
                        <div style="width:10px;height:10px;background:#10B981;border-radius:50%"></div>
                        <span style="font-size:14px;font-weight:600;color:#10B981">Available Now</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main -->
    <div class="tp-main">
        <div class="tp-grid">
            <!-- Left Column -->
            <div>
                <!-- About -->
                <div class="tp-card">
                    <h2 class="tp-card-title">About <?php echo esc_html($first_name); ?></h2>
                    <?php if ($bio): ?>
                    <p style="font-size:15px;line-height:1.75;color:#4B5563"><?php echo nl2br(esc_html($bio)); ?></p>
                    <?php else: ?>
                    <p style="font-size:15px;line-height:1.75;color:#4B5563">
                        <?php echo esc_html($first_name); ?> is a <?php echo $level_label ? strtolower($level_label) . ' level' : 'professional'; ?> soccer trainer 
                        <?php echo $trainer->college ? 'from ' . esc_html($trainer->college) : ''; ?> specializing in personalized 1-on-1 training.
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Specialties -->
                <?php if (!empty($specialties)): ?>
                <div class="tp-card">
                    <h2 class="tp-card-title">Specialties</h2>
                    <div style="display:flex;flex-wrap:wrap;gap:10px">
                        <?php foreach ($specialties as $spec): 
                            $label = isset($specialty_labels[$spec]) ? $specialty_labels[$spec] : ucfirst(str_replace('_', ' ', $spec));
                        ?>
                        <span style="padding:10px 18px;background:#F3F4F6;border-radius:10px;font-size:14px;font-weight:600;color:#374151"><?php echo esc_html($label); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Training Locations -->
                <div class="tp-card">
                    <h2 class="tp-card-title">Training Locations</h2>
                    <div style="display:flex;flex-direction:column;gap:12px">
                        <?php foreach ($training_locations as $loc): ?>
                        <div style="display:flex;align-items:center;gap:14px;padding:16px;background:#F9FAFB;border-radius:14px">
                            <div style="width:48px;height:48px;background:#FCB900;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0E0F11" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <div>
                                <div style="font-weight:700;color:#111;font-size:16px"><?php echo esc_html($loc['name']); ?></div>
                                <?php if (!empty($loc['address']) && $loc['address'] !== $loc['name']): ?>
                                <div style="font-size:14px;color:#6B7280"><?php echo esc_html($loc['address']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Inline Message Box -->
                <div class="tp-card">
                    <h2 class="tp-card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:8px"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        Message <?php echo esc_html($first_name); ?>
                    </h2>
                    
                    <div class="tp-msg-box">
                        <?php if ($is_logged_in): ?>
                        <form id="tp-message-form">
                            <textarea class="tp-msg-input" id="tp-message" rows="3" placeholder="Hi <?php echo esc_attr($first_name); ?>, I'm interested in training sessions for my son/daughter..."><?php echo isset($_GET['msg']) ? esc_textarea($_GET['msg']) : ''; ?></textarea>
                            <button type="submit" class="tp-msg-btn" id="tp-send-btn">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                Send Message
                            </button>
                        </form>
                        <div id="tp-msg-success" style="display:none;text-align:center;padding:20px;background:#D1FAE5;border-radius:12px">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" style="margin:0 auto 8px;display:block"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <p style="font-weight:700;color:#059669;margin:0">Message sent!</p>
                        </div>
                        <?php else: ?>
                        <p style="font-size:14px;color:#6B7280;margin-bottom:12px">Log in to send a message</p>
                        <a href="<?php echo esc_url(home_url('/login/?redirect=' . urlencode($_SERVER['REQUEST_URI']))); ?>" class="tp-msg-btn" style="text-decoration:none">
                            Log In to Message
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reviews -->
                <div class="tp-card">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                        <h2 class="tp-card-title" style="margin-bottom:0">Reviews (<?php echo $review_count; ?>)</h2>
                        <div style="display:flex;align-items:center;gap:4px">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $i <= round($rating) ? '#FCB900' : '#E5E7EB'; ?>"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php endfor; ?>
                            <span style="font-weight:800;font-size:18px;margin-left:6px"><?php echo esc_html($rating); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($reviews)): ?>
                        <?php foreach (array_slice($reviews, 0, 5) as $i => $review): ?>
                        <div style="padding:20px 0;<?php echo $i > 0 ? 'border-top:1px solid #E5E7EB;' : ''; ?>">
                            <div style="display:flex;justify-content:space-between;margin-bottom:10px">
                                <strong style="color:#111;font-size:15px"><?php echo esc_html($review->reviewer_name ?: 'Parent'); ?></strong>
                                <div style="display:flex;gap:2px">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="<?php echo $s <= $review->rating ? '#FCB900' : '#E5E7EB'; ?>"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p style="font-size:14px;color:#4B5563;margin:0;line-height:1.6"><?php echo esc_html($review->comment); ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div style="text-align:center;padding:40px 20px;color:#6B7280">
                        <p style="margin:0;font-size:15px">No reviews yet. Be the first to train with <?php echo esc_html($first_name); ?>!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Booking -->
            <div class="tp-sidebar">
                <div style="background:#fff;border-radius:20px;padding:28px;box-shadow:0 12px 40px rgba(0,0,0,0.1);border:1px solid #E5E7EB">
                    <!-- Price -->
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                        <div>
                            <span style="font-size:32px;font-weight:900;color:#0E0F11">$<?php echo $rate; ?></span>
                            <span style="font-size:15px;color:#6B7280">/hour</span>
                        </div>
                        <span style="background:#D1FAE5;color:#059669;padding:8px 14px;border-radius:10px;font-size:13px;font-weight:700">Available</span>
                    </div>

                    <!-- Steps -->
                    <div class="tp-steps">
                        <div class="tp-step active" id="step-1">1. Date</div>
                        <div class="tp-step" id="step-2">2. Time</div>
                        <div class="tp-step" id="step-3">3. Location</div>
                    </div>

                    <!-- Step 1: Calendar -->
                    <div id="panel-1">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                            <span id="tp-month" style="font-weight:700;font-size:15px"></span>
                            <div style="display:flex;gap:6px">
                                <button onclick="tpPrev()" style="width:36px;height:36px;border-radius:10px;border:1px solid #E5E7EB;background:#fff;cursor:pointer;font-size:18px">‹</button>
                                <button onclick="tpNext()" style="width:36px;height:36px;border-radius:10px;border:1px solid #E5E7EB;background:#fff;cursor:pointer;font-size:18px">›</button>
                            </div>
                        </div>
                        <div class="tp-cal" style="margin-bottom:8px">
                            <div style="font-size:11px;color:#9CA3AF;font-weight:600;padding:8px;text-align:center">S</div>
                            <div style="font-size:11px;color:#9CA3AF;font-weight:600;padding:8px;text-align:center">M</div>
                            <div style="font-size:11px;color:#9CA3AF;font-weight:600;padding:8px;text-align:center">T</div>
                            <div style="font-size:11px;color:#9CA3AF;font-weight:600;padding:8px;text-align:center">W</div>
                            <div style="font-size:11px;color:#9CA3AF;font-weight:600;padding:8px;text-align:center">T</div>
                            <div style="font-size:11px;color:#9CA3AF;font-weight:600;padding:8px;text-align:center">F</div>
                            <div style="font-size:11px;color:#9CA3AF;font-weight:600;padding:8px;text-align:center">S</div>
                        </div>
                        <div id="tp-days" class="tp-cal"></div>
                    </div>

                    <!-- Step 2: Times -->
                    <div id="panel-2" style="display:none">
                        <button onclick="tpBack(1)" style="display:flex;align-items:center;gap:4px;background:none;border:none;color:#6B7280;font-size:13px;font-weight:600;cursor:pointer;margin-bottom:12px;padding:0">← Back</button>
                        <div style="font-weight:700;margin-bottom:12px;font-size:15px">Select time for <span id="date-display"></span></div>
                        <div id="tp-slots" style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px"></div>
                        <div id="tp-no-slots" style="display:none;text-align:center;padding:24px;color:#6B7280;font-size:14px;background:#F9FAFB;border-radius:10px">No times available</div>
                    </div>

                    <!-- Step 3: Location -->
                    <div id="panel-3" style="display:none">
                        <button onclick="tpBack(2)" style="display:flex;align-items:center;gap:4px;background:none;border:none;color:#6B7280;font-size:13px;font-weight:600;cursor:pointer;margin-bottom:12px;padding:0">← Back</button>
                        <div style="font-weight:700;margin-bottom:12px;font-size:15px">Choose location</div>
                        <div id="tp-locs">
                            <?php foreach ($training_locations as $i => $loc): ?>
                            <div class="tp-loc" data-name="<?php echo esc_attr($loc['name']); ?>" onclick="tpSelectLoc(this)">
                                <div class="tp-loc-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                </div>
                                <div style="flex:1">
                                    <div style="font-weight:700;font-size:15px"><?php echo esc_html($loc['name']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Book Button -->
                    <button id="tp-book" onclick="tpBook()" disabled style="width:100%;padding:18px;background:#FCB900;color:#0E0F11;border:none;border-radius:12px;font-family:inherit;font-size:16px;font-weight:800;cursor:pointer;margin-top:20px;opacity:0.5;transition:all 0.2s;text-transform:uppercase">
                        Select Date & Time
                    </button>
                    <p style="text-align:center;font-size:12px;color:#6B7280;margin:14px 0 0">Free cancellation 24hrs before</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Bar -->
    <div class="tp-book-bar">
        <div>
            <span style="font-size:28px;font-weight:900">$<?php echo $rate; ?></span>
            <span style="font-size:13px;color:#6B7280">/hr</span>
        </div>
        <button onclick="document.querySelector('.tp-sidebar').scrollIntoView({behavior:'smooth'})" style="padding:14px 28px;background:#FCB900;color:#0E0F11;border:none;border-radius:10px;font-family:inherit;font-size:15px;font-weight:700;cursor:pointer">Book Now</button>
    </div>
</div>

<script>
var tpTrainerId=<?php echo (int)$trainer->id; ?>,tpRate=<?php echo $rate; ?>,tpDate=null,tpTime=null,tpLoc=null,tpMonth=new Date().getMonth(),tpYear=new Date().getFullYear();

function tpRender(){
    var now=new Date(),first=new Date(tpYear,tpMonth,1).getDay(),days=new Date(tpYear,tpMonth+1,0).getDate();
    var months=['January','February','March','April','May','June','July','August','September','October','November','December'];
    document.getElementById('tp-month').textContent=months[tpMonth]+' '+tpYear;
    var h='';
    for(var i=0;i<first;i++)h+='<div></div>';
    for(var d=1;d<=days;d++){
        var dt=new Date(tpYear,tpMonth,d),str=tpYear+'-'+String(tpMonth+1).padStart(2,'0')+'-'+String(d).padStart(2,'0');
        var past=dt<new Date(now.getFullYear(),now.getMonth(),now.getDate());
        var cls='tp-cal-day'+(past?' disabled':' available')+(tpDate===str?' selected':'');
        h+='<div class="'+cls+'" data-d="'+str+'" onclick="tpPickDate(\''+str+'\')">'+d+'</div>';
    }
    document.getElementById('tp-days').innerHTML=h;
}
function tpPrev(){tpMonth--;if(tpMonth<0){tpMonth=11;tpYear--;}tpRender();}
function tpNext(){tpMonth++;if(tpMonth>11){tpMonth=0;tpYear++;}tpRender();}
function tpPickDate(d){
    if(document.querySelector('[data-d="'+d+'"]').classList.contains('disabled'))return;
    tpDate=d;tpTime=null;tpLoc=null;
    var dt=new Date(d+'T12:00:00');
    document.getElementById('date-display').textContent=dt.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric'});
    tpShow(2);tpLoadSlots(d);tpUpdate();
}
function tpShow(n){
    for(var i=1;i<=3;i++){
        document.getElementById('panel-'+i).style.display=i===n?'block':'none';
        document.getElementById('step-'+i).className='tp-step'+(i<n?' done':i===n?' active':'');
    }
}
function tpBack(n){tpShow(n);if(n===1)tpRender();}
function tpLoadSlots(d){
    document.getElementById('tp-slots').innerHTML='<div style="grid-column:span 3;text-align:center;padding:20px;color:#6B7280">Loading...</div>';
    document.getElementById('tp-no-slots').style.display='none';
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=ptp_get_available_slots&trainer_id='+tpTrainerId+'&date='+d)
    .then(r=>r.json()).then(data=>{
        if(data.success&&data.data&&data.data.length){
            var h='';data.data.forEach(s=>{if(s.available)h+='<div class="tp-slot" onclick="tpPickTime(\''+s.time+'\',this)">'+s.display+'</div>';});
            if(h){document.getElementById('tp-slots').innerHTML=h;}else{document.getElementById('tp-slots').innerHTML='';document.getElementById('tp-no-slots').style.display='block';}
        }else{document.getElementById('tp-slots').innerHTML='';document.getElementById('tp-no-slots').style.display='block';}
    });
}
function tpPickTime(t,el){
    document.querySelectorAll('.tp-slot').forEach(s=>s.classList.remove('selected'));
    el.classList.add('selected');tpTime=t;tpLoc=null;
    document.querySelectorAll('.tp-loc').forEach(l=>l.classList.remove('selected'));
    tpShow(3);tpUpdate();
}
function tpSelectLoc(el){
    document.querySelectorAll('.tp-loc').forEach(l=>l.classList.remove('selected'));
    el.classList.add('selected');tpLoc=el.dataset.name;tpUpdate();
}
function tpUpdate(){
    var btn=document.getElementById('tp-book');
    if(tpDate&&tpTime&&tpLoc){btn.disabled=false;btn.style.opacity='1';btn.textContent='Book - $'+tpRate;}
    else if(tpDate&&tpTime){btn.disabled=true;btn.style.opacity='0.5';btn.textContent='Select Location';}
    else if(tpDate){btn.disabled=true;btn.style.opacity='0.5';btn.textContent='Select Time';}
    else{btn.disabled=true;btn.style.opacity='0.5';btn.textContent='Select Date & Time';}
}
function tpBook(){if(tpDate&&tpTime&&tpLoc)window.location.href='<?php echo home_url('/checkout/'); ?>?trainer_id='+tpTrainerId+'&date='+tpDate+'&time='+encodeURIComponent(tpTime)+'&location='+encodeURIComponent(tpLoc);}
tpRender();

// Messaging
<?php if ($is_logged_in): ?>
document.getElementById('tp-message-form')?.addEventListener('submit',function(e){
    e.preventDefault();
    var msg=document.getElementById('tp-message').value.trim();
    if(!msg)return;
    var btn=document.getElementById('tp-send-btn');
    btn.disabled=true;btn.innerHTML='Sending...';
    fetch('<?php echo admin_url('admin-ajax.php'); ?>',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=ptp_send_public_message&nonce=<?php echo wp_create_nonce('ptp_nonce'); ?>&trainer_id=<?php echo $trainer->id; ?>&message='+encodeURIComponent(msg)
    }).then(r=>r.json()).then(data=>{
        if(data.success){
            document.getElementById('tp-message-form').style.display='none';
            document.getElementById('tp-msg-success').style.display='block';
        }else{
            alert(data.data?.message||'Failed to send');
            btn.disabled=false;btn.innerHTML='<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Send Message';
        }
    });
});
<?php endif; ?>
</script>

<?php get_footer(); ?>
