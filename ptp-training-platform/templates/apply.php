<?php
/**
 * Template: Apply as Trainer v33
 * Full-width, single-column design with better functionality
 */
defined('ABSPATH') || exit;

$error = '';
$success = false;
$submitted_name = '';

// Ensure database table exists
global $wpdb;
$table_name = $wpdb->prefix . 'ptp_applications';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if (!$table_exists && class_exists('PTP_Database')) {
    PTP_Database::create_tables();
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ptp_apply'])) {
    if (!isset($_POST['ptp_apply_nonce']) || !wp_verify_nonce($_POST['ptp_apply_nonce'], 'ptp_apply')) {
        $error = 'Security verification failed. Please refresh and try again.';
    } elseif (!$table_exists) {
        $error = 'System error. Please contact support.';
    } else {
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $playing_level = sanitize_text_field($_POST['playing_level'] ?? '');
        $college = sanitize_text_field($_POST['college'] ?? '');
        $team = sanitize_text_field($_POST['team'] ?? '');
        $position = sanitize_text_field($_POST['position'] ?? '');
        $city = sanitize_text_field($_POST['city'] ?? '');
        $state = sanitize_text_field($_POST['state'] ?? '');
        $specialties = isset($_POST['specialties']) ? array_map('sanitize_text_field', $_POST['specialties']) : array();
        $instagram = sanitize_text_field($_POST['instagram'] ?? '');
        $bio = sanitize_textarea_field($_POST['bio'] ?? '');
        $hourly_rate = floatval($_POST['hourly_rate'] ?? 70);
        $travel_radius = intval($_POST['travel_radius'] ?? 15);
        $how_heard = sanitize_text_field($_POST['how_heard'] ?? '');

        $submitted_name = $name;

        if (empty($name) || empty($email) || empty($phone) || empty($playing_level) || empty($city) || empty($state)) {
            $error = 'Please fill in all required fields.';
        } elseif (!is_email($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ptp_applications WHERE email = %s", $email
            ));

            $is_trainer = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ptp_trainers WHERE email = %s", $email
            ));

            if ($exists) {
                $error = 'An application with this email already exists.';
            } elseif ($is_trainer) {
                $error = 'You\'re already a trainer. Please log in.';
            } else {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'ptp_applications',
                    array(
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'playing_level' => $playing_level,
                        'college' => $college,
                        'team' => $team,
                        'position' => $position,
                        'location' => trim($city . ', ' . $state),
                        'specialties' => implode(', ', $specialties),
                        'instagram' => ltrim($instagram, '@'),
                        'bio' => $bio,
                        'hourly_rate' => $hourly_rate,
                        'travel_radius' => $travel_radius,
                        'admin_notes' => 'How heard: ' . $how_heard,
                        'status' => 'pending',
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%d', '%s', '%s', '%s')
                );

                if ($result) {
                    $success = true;

                    // Send notifications
                    $admin_email = get_option('admin_email');
                    add_filter('wp_mail_content_type', function() { return 'text/html'; });
                    wp_mail($admin_email, 'New Trainer Application - ' . $name,
                        '<h2>New Application</h2><p><strong>' . esc_html($name) . '</strong><br>' . esc_html($email) . '<br>' . esc_html($phone) . '</p>'
                    );
                    remove_filter('wp_mail_content_type', function() { return 'text/html'; });

                    if (class_exists('PTP_Email')) {
                        PTP_Email::send_application_received($email, $name);
                    }
                } else {
                    $error = 'Error submitting application. Please try again.';
                    error_log('PTP Application Error: ' . $wpdb->last_error);
                }
            }
        }
    }
}

$logo_url = class_exists('PTP_Images') ? PTP_Images::logo() : '';
get_header();
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
*{box-sizing:border-box;margin:0;padding:0}
body{overflow-x:hidden}

.ptp-apply{
    font-family:'Inter',-apple-system,sans-serif;
    -webkit-font-smoothing:antialiased;
    background:#0E0F11;
    min-height:100vh;
}

/* Header */
.apply-header{
    padding:16px 24px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    max-width:1200px;
    margin:0 auto;
}
.apply-logo{height:36px;width:auto}
.apply-login{color:#9CA3AF;font-size:14px;text-decoration:none}
.apply-login:hover{color:#fff}

/* Hero Section */
.apply-hero{
    text-align:center;
    padding:40px 24px 60px;
    max-width:800px;
    margin:0 auto;
}
.apply-hero h1{
    font-size:48px;
    font-weight:900;
    color:#fff;
    line-height:1.1;
    margin:0 0 16px;
}
.apply-hero h1 span{color:#FCB900}
.apply-hero p{
    font-size:18px;
    color:#9CA3AF;
    margin:0 0 32px;
    line-height:1.6;
}

/* Stats Bar */
.apply-stats{
    display:flex;
    justify-content:center;
    gap:40px;
    flex-wrap:wrap;
}
.apply-stat{text-align:center}
.apply-stat-value{
    font-size:32px;
    font-weight:800;
    color:#FCB900;
    display:block;
}
.apply-stat-label{
    font-size:13px;
    color:#6B7280;
    text-transform:uppercase;
    letter-spacing:0.5px;
}

/* Form Section */
.apply-form-section{
    background:#fff;
    border-radius:24px 24px 0 0;
    padding:48px 24px 60px;
    margin-top:20px;
}
.apply-form-container{
    max-width:700px;
    margin:0 auto;
}

/* Progress Bar */
.apply-progress{
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:40px;
}
.progress-step{
    flex:1;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:8px;
}
.progress-circle{
    width:40px;
    height:40px;
    border-radius:50%;
    background:#F3F4F6;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    font-size:14px;
    color:#9CA3AF;
    transition:all 0.3s;
}
.progress-step.active .progress-circle{
    background:#FCB900;
    color:#0E0F11;
}
.progress-step.complete .progress-circle{
    background:#10B981;
    color:#fff;
}
.progress-label{
    font-size:12px;
    color:#9CA3AF;
    font-weight:500;
}
.progress-step.active .progress-label{color:#111}
.progress-line{
    flex:1;
    height:3px;
    background:#E5E7EB;
    border-radius:3px;
}
.progress-line.complete{background:#10B981}

/* Form Steps */
.form-step{display:none}
.form-step.active{display:block}
.step-title{
    font-size:24px;
    font-weight:800;
    color:#111;
    margin:0 0 8px;
}
.step-subtitle{
    font-size:15px;
    color:#6B7280;
    margin:0 0 32px;
}

/* Form Elements */
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
}
.form-grid.single{grid-template-columns:1fr}
.form-group{margin-bottom:24px}
.form-group.full{grid-column:1/-1}
.form-label{
    display:block;
    font-size:14px;
    font-weight:600;
    color:#374151;
    margin-bottom:8px;
}
.form-label .req{color:#EF4444}
.form-input{
    width:100%;
    padding:16px;
    border:2px solid #E5E7EB;
    border-radius:12px;
    font-size:16px;
    font-family:inherit;
    background:#fff;
    transition:all 0.2s;
}
.form-input:focus{
    outline:none;
    border-color:#FCB900;
    box-shadow:0 0 0 4px rgba(252,185,0,0.1);
}
.form-input::placeholder{color:#9CA3AF}
.form-hint{font-size:13px;color:#9CA3AF;margin-top:6px}

/* Specialty Pills */
.specialty-pills{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}
.specialty-pill{
    position:relative;
}
.specialty-pill input{
    position:absolute;
    opacity:0;
    pointer-events:none;
}
.specialty-pill label{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:12px 18px;
    background:#F9FAFB;
    border:2px solid #E5E7EB;
    border-radius:50px;
    font-size:14px;
    font-weight:500;
    color:#374151;
    cursor:pointer;
    transition:all 0.2s;
}
.specialty-pill input:checked + label{
    background:#FFFBEB;
    border-color:#FCB900;
    color:#0E0F11;
}
.specialty-pill label:hover{
    border-color:#D1D5DB;
}

/* Rate Selector */
.rate-selector{
    background:#F9FAFB;
    border-radius:16px;
    padding:24px;
    text-align:center;
}
.rate-display{
    font-size:56px;
    font-weight:800;
    color:#0E0F11;
    margin-bottom:8px;
}
.rate-display span{font-size:20px;color:#6B7280;font-weight:500}
.rate-slider{
    width:100%;
    height:8px;
    -webkit-appearance:none;
    background:#E5E7EB;
    border-radius:8px;
    margin:16px 0;
}
.rate-slider::-webkit-slider-thumb{
    -webkit-appearance:none;
    width:28px;
    height:28px;
    background:#FCB900;
    border-radius:50%;
    cursor:pointer;
    box-shadow:0 2px 8px rgba(0,0,0,0.2);
}
.rate-labels{
    display:flex;
    justify-content:space-between;
    font-size:14px;
    color:#6B7280;
}

/* Navigation */
.form-nav{
    display:flex;
    gap:16px;
    margin-top:40px;
    padding-top:24px;
    border-top:1px solid #E5E7EB;
}
.btn{
    padding:18px 32px;
    border-radius:12px;
    font-size:16px;
    font-weight:600;
    font-family:inherit;
    cursor:pointer;
    transition:all 0.2s;
    border:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
}
.btn-back{
    background:#F3F4F6;
    color:#374151;
}
.btn-back:hover{background:#E5E7EB}
.btn-primary{
    flex:1;
    background:#FCB900;
    color:#0E0F11;
}
.btn-primary:hover{
    background:#EAA800;
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(252,185,0,0.3);
}
.btn-primary:disabled{
    opacity:0.6;
    cursor:not-allowed;
    transform:none;
}

/* Error */
.form-error{
    background:#FEF2F2;
    border:1px solid #FECACA;
    border-radius:12px;
    padding:16px;
    margin-bottom:24px;
    display:flex;
    align-items:center;
    gap:12px;
    color:#DC2626;
    font-size:14px;
}

/* Success */
.apply-success{
    max-width:500px;
    margin:0 auto;
    text-align:center;
    padding:60px 24px;
}
.success-icon{
    width:80px;
    height:80px;
    background:#ECFDF5;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 24px;
}
.success-icon svg{color:#10B981}
.apply-success h2{
    font-size:28px;
    font-weight:800;
    color:#111;
    margin:0 0 12px;
}
.apply-success p{
    font-size:16px;
    color:#6B7280;
    margin:0 0 32px;
    line-height:1.6;
}
.success-steps{
    background:#F9FAFB;
    border-radius:16px;
    padding:24px;
    text-align:left;
    margin-bottom:32px;
}
.success-step{
    display:flex;
    align-items:flex-start;
    gap:16px;
    padding:12px 0;
    border-bottom:1px solid #E5E7EB;
}
.success-step:last-child{border:none}
.success-num{
    width:28px;
    height:28px;
    background:#FCB900;
    color:#0E0F11;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:13px;
    font-weight:700;
    flex-shrink:0;
}
.success-step strong{display:block;color:#111;margin-bottom:2px}
.success-step span{font-size:14px;color:#6B7280}

/* Mobile */
@media(max-width:768px){
    .apply-hero h1{font-size:32px}
    .apply-hero p{font-size:16px}
    .apply-stats{gap:24px}
    .apply-stat-value{font-size:24px}
    .apply-form-section{padding:32px 16px 40px;border-radius:20px 20px 0 0}
    .form-grid{grid-template-columns:1fr;gap:16px}
    .progress-label{display:none}
    .progress-circle{width:36px;height:36px;font-size:13px}
    .step-title{font-size:20px}
    .form-nav{flex-direction:column}
    .btn-back{order:2}
    .btn{width:100%;padding:16px 24px}
    .specialty-pills{gap:8px}
    .specialty-pill label{padding:10px 14px;font-size:13px}
    .rate-display{font-size:42px}
}
@media(max-width:480px){
    .apply-header{padding:12px 16px}
    .apply-logo{height:28px}
    .apply-hero{padding:24px 16px 40px}
    .apply-hero h1{font-size:28px}
    .apply-stats{flex-direction:column;gap:16px}
}
</style>

<div class="ptp-apply">
    <!-- Header -->
    <div class="apply-header">
        <a href="<?php echo esc_url(home_url('/training/')); ?>">
            <img src="<?php echo esc_url($logo_url); ?>" alt="PTP" class="apply-logo">
        </a>
        <a href="<?php echo esc_url(home_url('/login/')); ?>" class="apply-login">Already a trainer? Log in</a>
    </div>

    <?php if ($success): ?>
    <!-- Success State -->
    <div class="apply-form-section">
        <div class="apply-success">
            <div class="success-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <h2>Application Received!</h2>
            <p>Thanks <?php echo esc_html(explode(' ', $submitted_name)[0]); ?>! We'll review your application and get back to you within 24-48 hours.</p>

            <div class="success-steps">
                <div class="success-step">
                    <div class="success-num">1</div>
                    <div><strong>Review</strong><span>We'll verify your playing experience</span></div>
                </div>
                <div class="success-step">
                    <div class="success-num">2</div>
                    <div><strong>Approval</strong><span>Receive login credentials via email</span></div>
                </div>
                <div class="success-step">
                    <div class="success-num">3</div>
                    <div><strong>Setup</strong><span>Complete your profile & availability</span></div>
                </div>
                <div class="success-step">
                    <div class="success-num">4</div>
                    <div><strong>Earn</strong><span>Get matched with families & start training</span></div>
                </div>
            </div>

            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">Back to Home</a>
        </div>
    </div>

    <?php else: ?>

    <!-- Hero -->
    <div class="apply-hero">
        <h1>Train Athletes.<br><span>Get Paid.</span></h1>
        <p>Join our network of elite trainers earning $50-100/hr on your own schedule. We handle the bookings, you focus on training.</p>

        <div class="apply-stats">
            <div class="apply-stat">
                <span class="apply-stat-value">$75</span>
                <span class="apply-stat-label">Avg Hourly Rate</span>
            </div>
            <div class="apply-stat">
                <span class="apply-stat-value">500+</span>
                <span class="apply-stat-label">Sessions Booked</span>
            </div>
            <div class="apply-stat">
                <span class="apply-stat-value">4.9</span>
                <span class="apply-stat-label">Avg Rating</span>
            </div>
        </div>
    </div>

    <!-- Form Section -->
    <div class="apply-form-section">
        <div class="apply-form-container">
            <!-- Progress -->
            <div class="apply-progress">
                <div class="progress-step active" data-step="1">
                    <div class="progress-circle">1</div>
                    <span class="progress-label">Your Info</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="2">
                    <div class="progress-circle">2</div>
                    <span class="progress-label">Experience</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="3">
                    <div class="progress-circle">3</div>
                    <span class="progress-label">Preferences</span>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="form-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?php echo esc_html($error); ?>
            </div>
            <?php endif; ?>

            <form method="post" id="applyForm">
                <?php wp_nonce_field('ptp_apply', 'ptp_apply_nonce'); ?>
                <input type="hidden" name="ptp_apply" value="1">

                <!-- Step 1: Basic Info -->
                <div class="form-step active" data-step="1">
                    <h2 class="step-title">Let's start with your info</h2>
                    <p class="step-subtitle">Tell us about yourself so we can set up your profile.</p>

                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label">Full Name <span class="req">*</span></label>
                            <input type="text" name="name" class="form-input" placeholder="John Smith" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email <span class="req">*</span></label>
                            <input type="email" name="email" class="form-input" placeholder="john@email.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone <span class="req">*</span></label>
                            <input type="tel" name="phone" id="phone" class="form-input" placeholder="(555) 123-4567" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">City <span class="req">*</span></label>
                            <input type="text" name="city" class="form-input" placeholder="Philadelphia" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">State <span class="req">*</span></label>
                            <select name="state" class="form-input" required>
                                <option value="">Select state</option>
                                <option value="PA">Pennsylvania</option>
                                <option value="NJ">New Jersey</option>
                                <option value="DE">Delaware</option>
                                <option value="MD">Maryland</option>
                                <option value="NY">New York</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-nav">
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                            Continue
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Experience -->
                <div class="form-step" data-step="2">
                    <h2 class="step-title">Your playing experience</h2>
                    <p class="step-subtitle">Parents want trainers who've competed at a high level.</p>

                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label">Highest Playing Level <span class="req">*</span></label>
                            <select name="playing_level" class="form-input" required>
                                <option value="">Select your level</option>
                                <option value="pro">Professional</option>
                                <option value="college_d1">NCAA Division 1</option>
                                <option value="college_d2">NCAA Division 2</option>
                                <option value="college_d3">NCAA Division 3</option>
                                <option value="semi_pro">Semi-Professional</option>
                                <option value="academy">Elite Academy / Club</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">College/University</label>
                            <input type="text" name="college" class="form-input" placeholder="e.g., Villanova University">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Team/Club</label>
                            <input type="text" name="team" class="form-input" placeholder="e.g., Philadelphia Union">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Primary Sport</label>
                            <select name="position" class="form-input">
                                <option value="soccer">Soccer</option>
                                <option value="basketball">Basketball</option>
                                <option value="baseball">Baseball</option>
                                <option value="lacrosse">Lacrosse</option>
                                <option value="field_hockey">Field Hockey</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Training Specialties</label>
                            <div class="specialty-pills">
                                <div class="specialty-pill">
                                    <input type="checkbox" name="specialties[]" value="Technical Skills" id="s1">
                                    <label for="s1">Technical Skills</label>
                                </div>
                                <div class="specialty-pill">
                                    <input type="checkbox" name="specialties[]" value="Speed & Agility" id="s2">
                                    <label for="s2">Speed & Agility</label>
                                </div>
                                <div class="specialty-pill">
                                    <input type="checkbox" name="specialties[]" value="Strength" id="s3">
                                    <label for="s3">Strength</label>
                                </div>
                                <div class="specialty-pill">
                                    <input type="checkbox" name="specialties[]" value="Game IQ" id="s4">
                                    <label for="s4">Game IQ</label>
                                </div>
                                <div class="specialty-pill">
                                    <input type="checkbox" name="specialties[]" value="Position-Specific" id="s5">
                                    <label for="s5">Position-Specific</label>
                                </div>
                                <div class="specialty-pill">
                                    <input type="checkbox" name="specialties[]" value="Youth" id="s6">
                                    <label for="s6">Youth Development</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-nav">
                        <button type="button" class="btn btn-back" onclick="prevStep()">Back</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                            Continue
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Preferences -->
                <div class="form-step" data-step="3">
                    <h2 class="step-title">Almost done!</h2>
                    <p class="step-subtitle">Set your rate and tell us about yourself.</p>

                    <div class="form-group">
                        <label class="form-label">Your Hourly Rate</label>
                        <div class="rate-selector">
                            <div class="rate-display">$<span id="rateValue">70</span><span>/hr</span></div>
                            <input type="range" name="hourly_rate" class="rate-slider" min="40" max="150" value="70" step="5" oninput="document.getElementById('rateValue').textContent=this.value">
                            <div class="rate-labels">
                                <span>$40</span>
                                <span>$150</span>
                            </div>
                        </div>
                        <p class="form-hint" style="text-align:center;margin-top:12px">Most trainers charge $60-90/hr. You can adjust later.</p>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Travel Radius</label>
                            <select name="travel_radius" class="form-input">
                                <option value="10">10 miles</option>
                                <option value="15" selected>15 miles</option>
                                <option value="20">20 miles</option>
                                <option value="25">25 miles</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Instagram (optional)</label>
                            <input type="text" name="instagram" class="form-input" placeholder="@yourhandle">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tell us about yourself</label>
                        <textarea name="bio" class="form-input" rows="4" placeholder="Share your athletic background, achievements, and why you want to train with PTP..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">How did you hear about us?</label>
                        <select name="how_heard" class="form-input">
                            <option value="">Select one</option>
                            <option value="instagram">Instagram</option>
                            <option value="friend">Friend/Teammate</option>
                            <option value="google">Google Search</option>
                            <option value="college">College Coach</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-nav">
                        <button type="button" class="btn btn-back" onclick="prevStep()">Back</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            Submit Application
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
let currentStep = 1;
const totalSteps = 3;

function updateProgress() {
    document.querySelectorAll('.progress-step').forEach((step, i) => {
        step.classList.remove('active', 'complete');
        if (i + 1 === currentStep) step.classList.add('active');
        if (i + 1 < currentStep) step.classList.add('complete');
    });
    document.querySelectorAll('.progress-line').forEach((line, i) => {
        line.classList.toggle('complete', i + 1 < currentStep);
    });
}

function showStep(step) {
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    document.querySelector(`.form-step[data-step="${step}"]`)?.classList.add('active');
    updateProgress();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateStep(step) {
    const stepEl = document.querySelector(`.form-step[data-step="${step}"]`);
    const required = stepEl.querySelectorAll('[required]');
    let valid = true;

    required.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#EF4444';
            valid = false;
        } else {
            field.style.borderColor = '#E5E7EB';
        }
    });

    if (step === 1) {
        const email = stepEl.querySelector('input[type="email"]');
        if (email && !email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            email.style.borderColor = '#EF4444';
            valid = false;
        }
    }

    return valid;
}

function nextStep() {
    if (!validateStep(currentStep)) return;
    if (currentStep < totalSteps) {
        currentStep++;
        showStep(currentStep);
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Phone formatting
    const phone = document.getElementById('phone');
    if (phone) {
        phone.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    }

    // Form submission
    document.getElementById('applyForm')?.addEventListener('submit', function(e) {
        for (let i = 1; i <= totalSteps; i++) {
            if (!validateStep(i)) {
                e.preventDefault();
                currentStep = i;
                showStep(i);
                return;
            }
        }

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg width="20" height="20" style="animation:spin 1s linear infinite" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10"/></svg> Submitting...';
    });

    // Focus styling
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('focus', () => input.style.borderColor = '#FCB900');
        input.addEventListener('blur', function() {
            if (this.required && !this.value.trim()) {
                this.style.borderColor = '#EF4444';
            } else {
                this.style.borderColor = '#E5E7EB';
            }
        });
    });
});
</script>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<?php get_footer(); ?>
