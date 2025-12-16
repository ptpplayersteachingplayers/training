<?php
/**
 * Template: Apply as Trainer v31
 * Multi-step wizard, mobile-optimized, conversion-focused
 * FIXED: Form submission and validation
 */
defined('ABSPATH') || exit;

$error = '';
$success = false;
$submitted_name = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ptp_apply'])) {
    // Verify nonce
    if (!isset($_POST['ptp_apply_nonce']) || !wp_verify_nonce($_POST['ptp_apply_nonce'], 'ptp_apply')) {
        $error = 'Security verification failed. Please refresh the page and try again.';
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
            global $wpdb;

            // Check for existing application
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ptp_applications WHERE email = %s",
                $email
            ));

            // Check if already a trainer
            $is_trainer = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ptp_trainers WHERE email = %s",
                $email
            ));

            if ($exists) {
                $error = 'An application with this email already exists. We\'ll be in touch soon!';
            } elseif ($is_trainer) {
                $error = 'You\'re already registered as a trainer. Please log in to your account.';
            } else {
                $location = trim($city . ', ' . $state);
                $specialties_str = implode(', ', $specialties);

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
                        'location' => $location,
                        'specialties' => $specialties_str,
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
                    $app_id = $wpdb->insert_id;

                    // Get playing level label
                    $levels = array(
                        'pro' => 'Professional',
                        'college_d1' => 'NCAA Division 1',
                        'college_d2' => 'NCAA Division 2',
                        'college_d3' => 'NCAA Division 3',
                        'semi_pro' => 'Semi-Professional',
                        'academy' => 'Elite Academy / Club'
                    );
                    $level_label = $levels[$playing_level] ?? $playing_level;
                    $review_url = admin_url('admin.php?page=ptp-applications&status=pending');

                    // Build admin notification HTML
                    $admin_html = '
                    <div style="font-family:-apple-system,sans-serif;max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
                        <div style="background:#0E0F11;padding:24px 32px;">
                            <h1 style="margin:0;color:#FCB900;font-size:20px;">New Trainer Application</h1>
                        </div>
                        <div style="padding:32px;">
                            <div style="background:#F0FDF4;border-left:4px solid #22C55E;padding:16px;border-radius:0 8px 8px 0;margin-bottom:24px;">
                                <strong style="color:#166534;">' . esc_html($name) . '</strong><br>
                                <span style="color:#15803D;">' . esc_html($level_label) . '</span>
                            </div>
                            <table style="width:100%;border-collapse:collapse;">
                                <tr><td style="padding:8px 0;color:#6B7280;width:100px;">Email</td><td style="padding:8px 0;"><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></td></tr>
                                <tr><td style="padding:8px 0;color:#6B7280;">Phone</td><td style="padding:8px 0;"><a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a></td></tr>
                                <tr><td style="padding:8px 0;color:#6B7280;">College</td><td style="padding:8px 0;">' . esc_html($college ?: 'Not specified') . '</td></tr>
                                <tr><td style="padding:8px 0;color:#6B7280;">Location</td><td style="padding:8px 0;">' . esc_html($location) . '</td></tr>
                                <tr><td style="padding:8px 0;color:#6B7280;">Specialties</td><td style="padding:8px 0;">' . esc_html($specialties_str ?: 'None selected') . '</td></tr>
                                <tr><td style="padding:8px 0;color:#6B7280;">Rate</td><td style="padding:8px 0;">$' . number_format($hourly_rate, 0) . '/hr</td></tr>
                            </table>
                            ' . ($bio ? '<div style="margin-top:20px;padding:16px;background:#F9FAFB;border-radius:8px;"><strong style="display:block;margin-bottom:8px;color:#374151;">Bio</strong><p style="margin:0;color:#6B7280;line-height:1.6;">' . esc_html(wp_trim_words($bio, 50)) . '</p></div>' : '') . '
                            <div style="margin-top:24px;text-align:center;">
                                <a href="' . esc_url($review_url) . '" style="display:inline-block;background:#FCB900;color:#0E0F11;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:600;">Review Application</a>
                            </div>
                        </div>
                    </div>';

                    // Send admin notification
                    $admin_email = get_option('admin_email');
                    $admin_subject = 'New Trainer Application - ' . $name;

                    add_filter('wp_mail_content_type', function() { return 'text/html'; });
                    wp_mail($admin_email, $admin_subject, $admin_html);
                    remove_filter('wp_mail_content_type', function() { return 'text/html'; });

                    // Send applicant confirmation
                    if (class_exists('PTP_Email')) {
                        PTP_Email::send_application_received($email, $name);
                    }

                    // Also notify via SMS if enabled
                    if (class_exists('PTP_SMS') && method_exists('PTP_SMS', 'send')) {
                        $admin_phone = get_option('ptp_admin_phone', '');
                        if ($admin_phone) {
                            PTP_SMS::send($admin_phone, "New trainer application from {$name} ({$level_label}). Check admin panel to review.");
                        }
                    }
                } else {
                    $error = 'Something went wrong. Please try again. (DB Error: ' . $wpdb->last_error . ')';
                }
            }
        }
    }
}

$logo_url = class_exists('PTP_Images') ? PTP_Images::logo() : '';
$hero_bg = class_exists('PTP_Images') ? PTP_Images::get('BG7A1642') : '';

get_header();
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
*{box-sizing:border-box;margin:0;padding:0}
.ptp-apply{font-family:'Inter',-apple-system,sans-serif;-webkit-font-smoothing:antialiased;background:#F9FAFB;min-height:100vh}
.ptp-apply input:focus,.ptp-apply select:focus,.ptp-apply textarea:focus{border-color:#FCB900!important;outline:none;box-shadow:0 0 0 3px rgba(252,185,0,0.15)}
.ptp-apply input,.ptp-apply select,.ptp-apply textarea{transition:all 0.2s}

/* Hero */
.apply-hero{background:linear-gradient(135deg,rgba(14,15,17,0.92),rgba(14,15,17,0.85)),url('<?php echo esc_url($hero_bg); ?>') center/cover;padding:50px 24px 60px;text-align:center}
.apply-hero-content{max-width:700px;margin:0 auto}
.apply-logo{height:42px;max-width:170px;width:auto;object-fit:contain;margin-bottom:28px}
.apply-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(252,185,0,0.12);color:#FCB900;padding:10px 20px;border-radius:50px;font-size:14px;font-weight:600;margin-bottom:16px}
.apply-hero h1{font-size:38px;font-weight:900;color:#fff;margin:0 0 12px;line-height:1.15}
.apply-hero p{font-size:17px;color:rgba(255,255,255,0.75);margin:0;line-height:1.6}

/* Success State */
.apply-success{background:#fff;border-radius:24px;padding:48px 40px;max-width:520px;margin:-30px auto 60px;box-shadow:0 20px 60px rgba(0,0,0,0.12);text-align:center}
.success-icon{width:80px;height:80px;background:#ECFDF5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px}
.success-icon svg{color:#10B981}
.apply-success h2{font-size:26px;font-weight:800;color:#111;margin:0 0 12px}
.apply-success p{font-size:16px;color:#6B7280;margin:0 0 28px;line-height:1.6}
.success-timeline{background:#F9FAFB;border-radius:16px;padding:24px;margin-bottom:28px;text-align:left}
.timeline-item{display:flex;gap:16px;padding:12px 0;border-bottom:1px solid #E5E7EB}
.timeline-item:last-child{border:none}
.timeline-num{width:28px;height:28px;background:#FCB900;color:#0E0F11;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}
.timeline-text{font-size:14px;color:#374151;line-height:1.5}
.timeline-text strong{color:#111;display:block;margin-bottom:2px}

/* Main Form Area */
.apply-main{max-width:900px;margin:0 auto;padding:40px 24px 60px;display:grid;grid-template-columns:1fr 320px;gap:32px;align-items:start}

/* Form Container */
.apply-form-container{background:#fff;border-radius:20px;padding:36px;box-shadow:0 8px 40px rgba(0,0,0,0.06)}
.form-header{margin-bottom:28px}
.form-header h2{font-size:22px;font-weight:800;color:#111;margin:0 0 6px}
.form-header p{font-size:14px;color:#6B7280;margin:0}

/* Step Indicator */
.step-indicator{display:flex;gap:8px;margin-bottom:32px}
.step-dot{height:4px;flex:1;background:#E5E7EB;border-radius:4px;transition:all 0.3s}
.step-dot.active{background:#FCB900}
.step-dot.complete{background:#10B981}

/* Form Steps */
.form-step{display:none}
.form-step.active{display:block}

/* Form Elements */
.form-group{margin-bottom:20px}
.form-label{display:block;font-size:14px;font-weight:600;color:#374151;margin-bottom:8px}
.form-label .required{color:#EF4444;margin-left:2px}
.form-input{width:100%;padding:14px 16px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:16px;background:#fff}
.form-input::placeholder{color:#9CA3AF}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-hint{font-size:12px;color:#9CA3AF;margin-top:6px}

/* Specialty Checkboxes */
.specialty-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
.specialty-item{position:relative}
.specialty-item input{position:absolute;opacity:0}
.specialty-item label{display:flex;align-items:center;gap:10px;padding:12px 14px;border:2px solid #E5E7EB;border-radius:10px;cursor:pointer;font-size:14px;color:#374151;transition:all 0.2s}
.specialty-item input:checked+label{border-color:#FCB900;background:#FFFBEB}
.specialty-item label:hover{border-color:#D1D5DB}
.specialty-icon{width:32px;height:32px;background:#F3F4F6;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px}

/* Rate Slider */
.rate-display{text-align:center;margin-bottom:16px}
.rate-value{font-size:42px;font-weight:800;color:#0E0F11}
.rate-value span{font-size:18px;color:#6B7280;font-weight:500}
.rate-slider{width:100%;height:8px;-webkit-appearance:none;background:#E5E7EB;border-radius:4px;outline:none}
.rate-slider::-webkit-slider-thumb{-webkit-appearance:none;width:24px;height:24px;background:#FCB900;border-radius:50%;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.15)}
.rate-range{display:flex;justify-content:space-between;font-size:13px;color:#9CA3AF;margin-top:8px}

/* Navigation Buttons */
.form-nav{display:flex;gap:12px;margin-top:28px}
.btn{padding:16px 28px;border-radius:12px;font-family:inherit;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.2s;border:none}
.btn-back{background:#F3F4F6;color:#374151}
.btn-back:hover{background:#E5E7EB}
.btn-next,.btn-submit{background:#FCB900;color:#0E0F11;flex:1}
.btn-next:hover,.btn-submit:hover{background:#EAA800;transform:translateY(-1px)}
.btn-submit{display:flex;align-items:center;justify-content:center;gap:8px}

/* Error State */
.form-error{padding:14px 16px;background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:#DC2626;font-size:14px}

/* Sidebar */
.apply-sidebar{position:sticky;top:100px}
.sidebar-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,0.05);margin-bottom:20px}
.sidebar-card h3{font-size:16px;font-weight:700;color:#111;margin:0 0 16px;display:flex;align-items:center;gap:8px}
.benefit-list{list-style:none;padding:0;margin:0}
.benefit-list li{display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid #F3F4F6;font-size:14px;color:#374151;line-height:1.5}
.benefit-list li:last-child{border:none}
.benefit-list li strong{color:#111}
.benefit-icon{width:24px;height:24px;background:#ECFDF5;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.benefit-icon svg{width:14px;height:14px;color:#10B981}

/* Testimonial */
.testimonial{background:#FFFBEB;border-radius:16px;padding:24px}
.testimonial-quote{font-size:15px;color:#374151;line-height:1.6;margin:0 0 16px;font-style:italic}
.testimonial-author{display:flex;align-items:center;gap:12px}
.testimonial-avatar{width:44px;height:44px;background:#FCB900;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#0E0F11}
.testimonial-info{font-size:14px}
.testimonial-name{font-weight:600;color:#111}
.testimonial-role{color:#6B7280;font-size:13px}

/* Mobile Responsive */
@media(max-width:900px){
    .apply-main{grid-template-columns:1fr;padding:24px 16px 40px;gap:24px}
    .apply-sidebar{position:static;order:2}
    .apply-form-container{order:1}
}
@media(max-width:768px){
    .apply-hero{padding:36px 20px 44px}
    .apply-hero h1{font-size:26px}
    .apply-hero p{font-size:15px}
    .apply-logo{height:36px;margin-bottom:20px}
    .apply-badge{padding:8px 16px;font-size:13px;margin-bottom:12px}
    .apply-form-container{padding:24px 20px;border-radius:16px}
    .form-header h2{font-size:20px}
    .form-row{grid-template-columns:1fr}
    .specialty-grid{grid-template-columns:1fr}
    .form-input{padding:12px 14px;font-size:16px}
    .btn{padding:14px 24px;font-size:15px}
    .rate-value{font-size:36px}
    .sidebar-card{padding:20px}
    .testimonial{padding:20px}
    .apply-success{margin:-20px 16px 40px;padding:32px 24px;border-radius:20px}
    .apply-success h2{font-size:22px}
    .success-icon{width:64px;height:64px}
    .success-icon svg{width:32px;height:32px}
}
@media(max-width:380px){
    .apply-hero h1{font-size:24px}
    .form-nav{flex-direction:column}
    .btn-back{order:2;margin-top:8px}
    .btn-next,.btn-submit{order:1}
}
</style>

<div class="ptp-apply">
    <!-- Hero -->
    <div class="apply-hero">
        <div class="apply-hero-content">
            <img src="<?php echo esc_url($logo_url); ?>" alt="PTP Soccer" class="apply-logo">
            <div class="apply-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Now Recruiting Trainers
            </div>
            <h1>Train Athletes.<br>Earn Money.</h1>
            <p>Join our team of elite trainers earning $50-100/hr on your schedule</p>
        </div>
    </div>
    
    <?php if ($success): ?>
    <!-- Success State -->
    <div class="apply-success">
        <div class="success-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <h2>Application Submitted!</h2>
        <p>Thanks <?php echo esc_html(explode(' ', $submitted_name)[0]); ?>! We've received your application and will review it within 24-48 hours.</p>
        
        <div class="success-timeline">
            <div class="timeline-item">
                <div class="timeline-num">1</div>
                <div class="timeline-text">
                    <strong>Application Review</strong>
                    We'll review your playing experience and location
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-num">2</div>
                <div class="timeline-text">
                    <strong>Approval Email</strong>
                    You'll receive login credentials if approved
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-num">3</div>
                <div class="timeline-text">
                    <strong>Complete Profile</strong>
                    Add your photo, availability & set rates
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-num">4</div>
                <div class="timeline-text">
                    <strong>Start Earning</strong>
                    Get matched with families in your area
                </div>
            </div>
        </div>
        
        <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-next" style="display:inline-flex;width:auto;padding:16px 32px;">
            Back to Home
        </a>
    </div>
    
    <?php else: ?>
    
    <!-- Main Content -->
    <div class="apply-main">
        <!-- Form -->
        <div class="apply-form-container">
            <div class="form-header">
                <h2>Trainer Application</h2>
                <p>Takes about 3 minutes to complete</p>
            </div>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step-dot active" data-step="1"></div>
                <div class="step-dot" data-step="2"></div>
                <div class="step-dot" data-step="3"></div>
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
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" name="name" class="form-input" placeholder="Your full name" data-required="true">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email <span class="required">*</span></label>
                            <input type="email" name="email" class="form-input" placeholder="you@email.com" data-required="true">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone <span class="required">*</span></label>
                            <input type="tel" name="phone" class="form-input" placeholder="(555) 123-4567" data-required="true">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Highest Playing Level <span class="required">*</span></label>
                        <select name="playing_level" class="form-input" data-required="true">
                            <option value="">Select your level</option>
                            <option value="pro">Professional</option>
                            <option value="college_d1">NCAA Division 1</option>
                            <option value="college_d2">NCAA Division 2</option>
                            <option value="college_d3">NCAA Division 3</option>
                            <option value="semi_pro">Semi-Professional</option>
                            <option value="academy">Elite Academy / Club</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">College/University</label>
                            <input type="text" name="college" class="form-input" placeholder="e.g., Villanova University">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Team/Club</label>
                            <input type="text" name="team" class="form-input" placeholder="e.g., Philadelphia Union">
                        </div>
                    </div>
                    
                    <div class="form-nav">
                        <button type="button" class="btn btn-next" onclick="nextStep()">
                            Continue
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-left:8px"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
                
                <!-- Step 2: Location & Specialties -->
                <div class="form-step" data-step="2">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">City <span class="required">*</span></label>
                            <input type="text" name="city" class="form-input" placeholder="Philadelphia" data-required="true">
                        </div>
                        <div class="form-group">
                            <label class="form-label">State <span class="required">*</span></label>
                            <select name="state" class="form-input" data-required="true">
                                <option value="">Select</option>
                                <option value="PA">Pennsylvania</option>
                                <option value="NJ">New Jersey</option>
                                <option value="DE">Delaware</option>
                                <option value="MD">Maryland</option>
                                <option value="NY">New York</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Primary Sport</label>
                        <select name="position" class="form-input">
                            <option value="">Select sport</option>
                            <option value="soccer">Soccer</option>
                            <option value="basketball">Basketball</option>
                            <option value="baseball">Baseball</option>
                            <option value="softball">Softball</option>
                            <option value="lacrosse">Lacrosse</option>
                            <option value="field_hockey">Field Hockey</option>
                            <option value="volleyball">Volleyball</option>
                            <option value="tennis">Tennis</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Training Specialties</label>
                        <p class="form-hint" style="margin:-4px 0 12px">Select all that apply</p>
                        <div class="specialty-grid">
                            <div class="specialty-item">
                                <input type="checkbox" name="specialties[]" value="Technical Skills" id="spec-tech">
                                <label for="spec-tech"><span class="specialty-icon">🎯</span> Technical Skills</label>
                            </div>
                            <div class="specialty-item">
                                <input type="checkbox" name="specialties[]" value="Speed & Agility" id="spec-speed">
                                <label for="spec-speed"><span class="specialty-icon">⚡</span> Speed & Agility</label>
                            </div>
                            <div class="specialty-item">
                                <input type="checkbox" name="specialties[]" value="Strength & Conditioning" id="spec-strength">
                                <label for="spec-strength"><span class="specialty-icon">💪</span> Strength & Conditioning</label>
                            </div>
                            <div class="specialty-item">
                                <input type="checkbox" name="specialties[]" value="Game IQ" id="spec-iq">
                                <label for="spec-iq"><span class="specialty-icon">🧠</span> Game IQ & Strategy</label>
                            </div>
                            <div class="specialty-item">
                                <input type="checkbox" name="specialties[]" value="Position-Specific" id="spec-position">
                                <label for="spec-position"><span class="specialty-icon">📍</span> Position-Specific</label>
                            </div>
                            <div class="specialty-item">
                                <input type="checkbox" name="specialties[]" value="Youth Development" id="spec-youth">
                                <label for="spec-youth"><span class="specialty-icon">⭐</span> Youth Development</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Instagram Handle</label>
                        <input type="text" name="instagram" class="form-input" placeholder="@yourhandle">
                        <p class="form-hint">Optional - helps verify your playing background</p>
                    </div>
                    
                    <div class="form-nav">
                        <button type="button" class="btn btn-back" onclick="prevStep()">Back</button>
                        <button type="button" class="btn btn-next" onclick="nextStep()">
                            Continue
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-left:8px"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
                
                <!-- Step 3: Bio & Rate -->
                <div class="form-step" data-step="3">
                    <div class="form-group">
                        <label class="form-label">Your Hourly Rate</label>
                        <div class="rate-display">
                            <div class="rate-value">$<span id="rateValue">70</span><span>/hr</span></div>
                        </div>
                        <input type="range" name="hourly_rate" class="rate-slider" min="40" max="150" value="70" step="5" oninput="updateRate(this.value)">
                        <div class="rate-range">
                            <span>$40</span>
                            <span>$150</span>
                        </div>
                        <p class="form-hint" style="text-align:center;margin-top:12px">You can change this later. Most trainers charge $60-$90/hr</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Travel Radius</label>
                        <select name="travel_radius" class="form-input">
                            <option value="10">10 miles</option>
                            <option value="15" selected>15 miles</option>
                            <option value="20">20 miles</option>
                            <option value="25">25 miles</option>
                            <option value="30">30+ miles</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tell us about yourself</label>
                        <textarea name="bio" class="form-input" rows="4" placeholder="Share your athletic background, achievements, coaching experience, and why you want to train with PTP..."></textarea>
                        <p class="form-hint">This will help parents get to know you</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">How did you hear about us?</label>
                        <select name="how_heard" class="form-input">
                            <option value="">Select one</option>
                            <option value="instagram">Instagram</option>
                            <option value="friend">Friend/Teammate</option>
                            <option value="google">Google Search</option>
                            <option value="college">College Coach</option>
                            <option value="camp">PTP Camp</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-nav">
                        <button type="button" class="btn btn-back" onclick="prevStep()">Back</button>
                        <button type="submit" class="btn btn-submit">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            Submit Application
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Sidebar -->
        <div class="apply-sidebar">
            <div class="sidebar-card">
                <h3>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Why Train with PTP?
                </h3>
                <ul class="benefit-list">
                    <li>
                        <span class="benefit-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></span>
                        <span><strong>Earn $50-100+/hour</strong> - top trainers make $1,000+/week</span>
                    </li>
                    <li>
                        <span class="benefit-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg></span>
                        <span><strong>Flexible schedule</strong> - train when and where you want</span>
                    </li>
                    <li>
                        <span class="benefit-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></span>
                        <span><strong>We handle bookings</strong> - focus on training, not admin</span>
                    </li>
                    <li>
                        <span class="benefit-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                        <span><strong>Insurance included</strong> - you're fully covered during sessions</span>
                    </li>
                </ul>
            </div>
            
            <div class="testimonial">
                <p class="testimonial-quote">"PTP has been an amazing way to stay connected to my sport while making solid money on my own schedule. Parents love that we've actually competed at a high level."</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">MK</div>
                    <div class="testimonial-info">
                        <div class="testimonial-name">Matt K.</div>
                        <div class="testimonial-role">D1 Athlete • PTP Trainer since 2024</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
let currentStep = 1;
const totalSteps = 3;
let isSubmitting = false;

function updateStepIndicator() {
    document.querySelectorAll('.step-dot').forEach((dot, i) => {
        dot.classList.remove('active', 'complete');
        if (i + 1 === currentStep) dot.classList.add('active');
        if (i + 1 < currentStep) dot.classList.add('complete');
    });
}

function showStep(step) {
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    const targetStep = document.querySelector(`.form-step[data-step="${step}"]`);
    if (targetStep) {
        targetStep.classList.add('active');
    }
    updateStepIndicator();
    const formContainer = document.querySelector('.apply-form-container');
    if (formContainer) {
        window.scrollTo({ top: formContainer.offsetTop - 20, behavior: 'smooth' });
    }
}

function validateStep(step) {
    const stepEl = document.querySelector(`.form-step[data-step="${step}"]`);
    if (!stepEl) return true;

    const fields = stepEl.querySelectorAll('[data-required="true"]');
    let valid = true;
    let firstInvalid = null;

    fields.forEach(field => {
        if (!field.value || !field.value.trim()) {
            field.style.borderColor = '#EF4444';
            valid = false;
            if (!firstInvalid) firstInvalid = field;
        } else {
            field.style.borderColor = '#E5E7EB';
        }
    });

    // Email validation for step 1
    if (step === 1) {
        const email = stepEl.querySelector('input[name="email"]');
        if (email && email.value && !email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            email.style.borderColor = '#EF4444';
            valid = false;
            if (!firstInvalid) firstInvalid = email;
        }
    }

    // Focus first invalid field
    if (!valid && firstInvalid) {
        firstInvalid.focus();
    }

    return valid;
}

function nextStep() {
    if (!validateStep(currentStep)) {
        return;
    }

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

function updateRate(value) {
    const el = document.getElementById('rateValue');
    if (el) el.textContent = value;
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('applyForm');
    if (!form) return;

    // Handle form submission
    form.addEventListener('submit', function(e) {
        // Prevent double submission
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }

        // Validate all steps before submitting
        for (let i = 1; i <= totalSteps; i++) {
            if (!validateStep(i)) {
                e.preventDefault();
                currentStep = i;
                showStep(i);
                return false;
            }
        }

        // Form is valid, show loading state and allow submission
        isSubmitting = true;
        const submitBtn = form.querySelector('.btn-submit');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10"/></svg> Submitting...';
        }

        return true;
    });

    // Phone formatting
    const phoneInput = document.querySelector('input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    }

    // Form validation feedback
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.dataset.required === 'true' && (!this.value || !this.value.trim())) {
                this.style.borderColor = '#EF4444';
            } else {
                this.style.borderColor = '#E5E7EB';
            }
        });
        input.addEventListener('focus', function() {
            this.style.borderColor = '#FCB900';
        });
    });
});

// Add spin animation
const styleSheet = document.createElement('style');
styleSheet.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
document.head.appendChild(styleSheet);
</script>

<?php get_footer(); ?>
