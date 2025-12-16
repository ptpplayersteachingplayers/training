<?php
/**
 * Template: Trainer Onboarding v29.5.2
 * Mobile-first, guided wizard flow
 */
defined('ABSPATH') || exit;

$logo_url = PTP_Images::logo();
$user = wp_get_current_user();
$photo = $trainer->photo_url ?: '';
$specialties_list = PTP_Trainer::get_specialties_list();

// Handle specialties - could be comma-separated or JSON
$current_specialties = array();
if (!empty($trainer->specialties)) {
    $decoded = json_decode($trainer->specialties, true);
    if (is_array($decoded)) {
        $current_specialties = $decoded;
    } else {
        $current_specialties = array_filter(array_map('trim', explode(',', $trainer->specialties)));
    }
}

$availability = PTP_Availability::get_weekly($trainer->id);
$avail_by_day = array();
foreach ($availability as $a) { $avail_by_day[$a->day_of_week] = $a; }
$days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
$is_edit = isset($_GET['edit']);

// Pre-load existing locations for JavaScript
$existing_locations = array();
if (!empty($trainer->training_locations)) {
    $decoded = json_decode($trainer->training_locations, true);
    if (is_array($decoded)) {
        $existing_locations = $decoded;
    }
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
* { box-sizing: border-box; }
html, body { overflow-x: hidden !important; max-width: 100vw; }

.ptp-onboard {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: #F3F4F6;
    min-height: 100vh;
    color: #111827;
    overflow-x: hidden;
}

/* Header */
.ptp-onboard-header {
    background: #fff;
    border-bottom: 1px solid #E5E7EB;
    padding: 16px;
    position: sticky;
    top: 0;
    z-index: 100;
}

.ptp-onboard-header-inner {
    max-width: 800px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.ptp-onboard-logo { height: 28px; max-width: 120px; width: auto; object-fit: contain; }

.ptp-onboard-exit {
    color: #6B7280;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
}

/* Progress */
.ptp-onboard-progress {
    background: #fff;
    border-bottom: 1px solid #E5E7EB;
    padding: 16px;
}

.ptp-onboard-progress-inner {
    max-width: 800px;
    margin: 0 auto;
}

.ptp-onboard-steps {
    display: flex;
    gap: 4px;
    margin-bottom: 12px;
}

.ptp-onboard-step {
    flex: 1;
    height: 4px;
    background: #E5E7EB;
    border-radius: 2px;
    transition: background 0.3s;
}

.ptp-onboard-step.active { background: #FCB900; }
.ptp-onboard-step.done { background: #10B981; }

.ptp-onboard-step-label {
    font-size: 12px;
    font-weight: 600;
    color: #6B7280;
}

.ptp-onboard-step-label span { color: #111827; }

/* Container */
.ptp-onboard-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 24px 16px 100px;
}

/* Card */
.ptp-onboard-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #E5E7EB;
    padding: 24px;
    margin-bottom: 16px;
}

.ptp-onboard-card-title {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 8px;
}

.ptp-onboard-card-desc {
    font-size: 14px;
    color: #6B7280;
    margin: 0 0 24px;
    line-height: 1.5;
}

/* Form Elements */
.ptp-form-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

@media (min-width: 600px) {
    .ptp-form-row.two-col { grid-template-columns: 1fr 1fr; }
}

.ptp-form-group { margin-bottom: 20px; }
.ptp-form-group:last-child { margin-bottom: 0; }

.ptp-form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.ptp-form-label .optional {
    font-weight: 400;
    color: #9CA3AF;
}

.ptp-form-input, .ptp-form-select, .ptp-form-textarea {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #D1D5DB;
    border-radius: 12px;
    font-size: 15px;
    font-family: inherit;
    transition: all 0.2s;
    background: #fff;
}

.ptp-form-input:focus, .ptp-form-select:focus, .ptp-form-textarea:focus {
    outline: none;
    border-color: #FCB900;
    box-shadow: 0 0 0 3px rgba(252,185,0,0.15);
}

.ptp-form-textarea {
    resize: vertical;
    min-height: 120px;
}

.ptp-form-hint {
    font-size: 12px;
    color: #6B7280;
    margin-top: 6px;
}

/* Training Locations Fields */
.ptp-location-field {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.ptp-location-input-group {
    flex: 1;
    position: relative;
}

.ptp-location-input-group .ptp-form-input {
    width: 100%;
    padding-right: 40px;
}

.ptp-location-input-group .location-verified {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #10B981;
    display: none;
}

.ptp-location-input-group.verified .location-verified {
    display: block;
}

.ptp-location-input-group.verified .ptp-form-input {
    border-color: #10B981;
    background: #F0FDF4;
}

.ptp-remove-location {
    width: 44px;
    height: 44px;
    border: 2px solid #E5E7EB;
    border-radius: 10px;
    background: #fff;
    color: #DC2626;
    font-size: 20px;
    cursor: pointer;
    flex-shrink: 0;
}

.ptp-remove-location:hover {
    background: #FEE2E2;
    border-color: #DC2626;
}

.ptp-add-location-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border: 2px dashed #D1D5DB;
    border-radius: 10px;
    background: transparent;
    color: #6B7280;
    font-weight: 500;
    cursor: pointer;
    font-size: 14px;
    margin-top: 8px;
}

.ptp-add-location-btn:hover {
    border-color: #FCB900;
    color: #FCB900;
}

/* Photo Upload */
.ptp-photo-upload {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}

@media (min-width: 500px) {
    .ptp-photo-upload { flex-direction: row; }
}

.ptp-photo-preview {
    width: 120px;
    height: 120px;
    border-radius: 16px;
    background: #F3F4F6;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}

.ptp-photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ptp-photo-preview svg { width: 40px; height: 40px; color: #9CA3AF; }

.ptp-photo-actions { flex: 1; }

.ptp-photo-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: #0F172A;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 8px;
}

.ptp-photo-btn:hover { background: #1E293B; }

.ptp-photo-hint {
    font-size: 12px;
    color: #6B7280;
}

/* Specialties Grid */
.ptp-specialties-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

@media (min-width: 600px) {
    .ptp-specialties-grid { grid-template-columns: repeat(3, 1fr); }
}

.ptp-specialty-item {
    position: relative;
}

.ptp-specialty-item input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.ptp-specialty-label {
    display: block;
    padding: 12px 14px;
    background: #F9FAFB;
    border: 2px solid transparent;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s;
}

.ptp-specialty-item input:checked + .ptp-specialty-label {
    background: #FEF3C7;
    border-color: #FCB900;
    color: #92400E;
}

/* Availability */
.ptp-avail-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ptp-avail-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #F9FAFB;
    border-radius: 12px;
    flex-wrap: wrap;
}

@media (min-width: 600px) {
    .ptp-avail-item { flex-wrap: nowrap; }
}

.ptp-avail-toggle {
    position: relative;
    width: 48px;
    height: 28px;
    flex-shrink: 0;
}

.ptp-avail-toggle input {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
    z-index: 1;
}

.ptp-avail-toggle-track {
    position: absolute;
    inset: 0;
    background: #D1D5DB;
    border-radius: 14px;
    transition: background 0.2s;
}

.ptp-avail-toggle input:checked + .ptp-avail-toggle-track {
    background: #10B981;
}

.ptp-avail-toggle-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 24px;
    height: 24px;
    background: #fff;
    border-radius: 50%;
    transition: left 0.2s;
    pointer-events: none;
}

.ptp-avail-toggle input:checked ~ .ptp-avail-toggle-thumb {
    left: 22px;
}

.ptp-avail-day {
    width: 90px;
    font-weight: 600;
    font-size: 14px;
    color: #111827;
}

.ptp-avail-times {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
}

.ptp-avail-time {
    padding: 10px 12px;
    border: 1px solid #D1D5DB;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    width: 110px;
    min-width: 0;
}

@media (max-width: 400px) {
    .ptp-avail-times { width: 100%; justify-content: flex-start; }
    .ptp-avail-time { flex: 1; width: auto; min-width: 80px; }
    .ptp-avail-day { width: 70px; font-size: 13px; }
}

.ptp-avail-to {
    color: #6B7280;
    font-size: 13px;
}

/* Navigation */
.ptp-onboard-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    border-top: 1px solid #E5E7EB;
    padding: 16px;
    z-index: 100;
}

.ptp-onboard-nav-inner {
    max-width: 800px;
    margin: 0 auto;
    display: flex;
    gap: 12px;
}

.ptp-nav-btn {
    flex: 1;
    padding: 16px 24px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    text-decoration: none;
    border: none;
}

.ptp-nav-btn-back {
    background: #F3F4F6;
    color: #374151;
}

.ptp-nav-btn-next {
    background: #FCB900;
    color: #0F172A;
}

.ptp-nav-btn-next:hover {
    background: #E5A800;
}

.ptp-nav-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Step Content */
.ptp-step { display: none; }
.ptp-step.active { display: block; }

/* Rate Slider */
.ptp-rate-display {
    text-align: center;
    margin-bottom: 16px;
}

.ptp-rate-value {
    font-size: 48px;
    font-weight: 800;
    color: #111827;
}

.ptp-rate-value span {
    font-size: 18px;
    font-weight: 500;
    color: #6B7280;
}

.ptp-rate-slider {
    width: 100%;
    height: 8px;
    -webkit-appearance: none;
    background: #E5E7EB;
    border-radius: 4px;
    outline: none;
}

.ptp-rate-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 24px;
    height: 24px;
    background: #FCB900;
    border-radius: 50%;
    cursor: pointer;
}

.ptp-rate-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    font-size: 12px;
    color: #6B7280;
}

/* Location with autocomplete */
.ptp-location-wrap {
    position: relative;
}

.ptp-location-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #9CA3AF;
}

.ptp-location-wrap .ptp-form-input {
    padding-left: 44px;
}

/* Payment Info */
.ptp-payment-note {
    background: #F0FDF4;
    border: 1px solid #BBF7D0;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.ptp-payment-note svg {
    flex-shrink: 0;
    color: #10B981;
}

.ptp-payment-note-text {
    font-size: 13px;
    color: #166534;
    line-height: 1.5;
}

/* SSN Field */
.ptp-ssn-wrap {
    position: relative;
}

.ptp-ssn-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #6B7280;
}
</style>

<div class="ptp-onboard">
    <form id="onboarding-form" enctype="multipart/form-data">
        <!-- Header -->
        <div class="ptp-onboard-header">
            <div class="ptp-onboard-header-inner">
                <img src="<?php echo esc_url($logo_url); ?>" alt="PTP" class="ptp-onboard-logo">
                <?php if ($is_edit): ?>
                    <a href="<?php echo home_url('/trainer-dashboard/'); ?>" class="ptp-onboard-exit">Back to Dashboard</a>
                <?php else: ?>
                    <span class="ptp-onboard-exit">Complete your profile</span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Progress -->
        <div class="ptp-onboard-progress">
            <div class="ptp-onboard-progress-inner">
                <div class="ptp-onboard-steps">
                    <div class="ptp-onboard-step active" data-step="1"></div>
                    <div class="ptp-onboard-step" data-step="2"></div>
                    <div class="ptp-onboard-step" data-step="3"></div>
                    <div class="ptp-onboard-step" data-step="4"></div>
                    <div class="ptp-onboard-step" data-step="5"></div>
                </div>
                <div class="ptp-onboard-step-label">Step <span id="current-step">1</span> of 5</div>
            </div>
        </div>
        
        <!-- Container -->
        <div class="ptp-onboard-container">
            <!-- Step 1: Photo & Basic Info -->
            <div class="ptp-step active" data-step="1">
                <div class="ptp-onboard-card">
                    <h2 class="ptp-onboard-card-title">Profile Photo</h2>
                    <p class="ptp-onboard-card-desc">Add a professional photo. Trainers with photos get 5x more bookings.</p>
                    
                    <div class="ptp-photo-upload">
                        <div class="ptp-photo-preview" id="photo-preview">
                            <?php if ($photo): ?>
                                <img src="<?php echo esc_url($photo); ?>" alt="">
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <?php endif; ?>
                        </div>
                        <div class="ptp-photo-actions">
                            <label class="ptp-photo-btn">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                Upload Photo
                                <input type="file" name="photo" accept="image/*" hidden onchange="previewPhoto(this)">
                            </label>
                            <p class="ptp-photo-hint">JPG or PNG, max 2MB</p>
                        </div>
                    </div>
                </div>
                
                <div class="ptp-onboard-card">
                    <h2 class="ptp-onboard-card-title">Basic Information</h2>
                    <p class="ptp-onboard-card-desc">Tell families about yourself</p>
                    
                    <div class="ptp-form-group">
                        <label class="ptp-form-label">Headline</label>
                        <input type="text" name="headline" class="ptp-form-input" value="<?php echo esc_attr($trainer->headline ?? ''); ?>" placeholder="e.g., Division 1 Midfielder | Youth Development Specialist">
                        <p class="ptp-form-hint">A short tagline that appears under your name</p>
                    </div>
                    
                    <div class="ptp-form-row two-col">
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">College/University <span class="optional">(optional)</span></label>
                            <input type="text" name="college" class="ptp-form-input" value="<?php echo esc_attr($trainer->college ?? ''); ?>" placeholder="Where you play(ed)">
                        </div>
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">Team <span class="optional">(optional)</span></label>
                            <input type="text" name="team" class="ptp-form-input" value="<?php echo esc_attr($trainer->team ?? ''); ?>" placeholder="Pro/club team">
                        </div>
                    </div>
                    
                    <div class="ptp-form-group">
                        <label class="ptp-form-label">Bio</label>
                        <textarea name="bio" class="ptp-form-textarea" placeholder="Tell families about your playing experience, coaching philosophy, and what makes your training unique..."><?php echo esc_textarea($trainer->bio ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Social Media Links -->
                    <div class="ptp-form-row" style="margin-top: 20px;">
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="display:inline;vertical-align:-2px;margin-right:6px;color:#E1306C"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                Instagram <span class="optional">(optional)</span>
                            </label>
                            <input type="text" name="instagram" class="ptp-form-input" value="<?php echo esc_attr($trainer->instagram ?? ''); ?>" placeholder="@username or full URL">
                        </div>
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="display:inline;vertical-align:-2px;margin-right:6px;color:#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Facebook <span class="optional">(optional)</span>
                            </label>
                            <input type="text" name="facebook" class="ptp-form-input" value="<?php echo esc_attr($trainer->facebook ?? ''); ?>" placeholder="username or full URL">
                        </div>
                    </div>
                    
                    <div class="ptp-form-group" style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #E5E7EB;">
                        <label class="ptp-form-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:-2px;margin-right:6px;"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                            Intro Video <span class="optional">(highly recommended)</span>
                        </label>
                        <input type="url" name="intro_video_url" class="ptp-form-input" value="<?php echo esc_attr($trainer->intro_video_url ?? ''); ?>" placeholder="https://youtube.com/watch?v=... or https://vimeo.com/...">
                        <p class="ptp-form-hint">A 30-60 second video introducing yourself helps families connect with you before booking. Upload to YouTube or Vimeo and paste the link here.</p>
                        <div id="video-preview" style="margin-top:12px;display:<?php echo !empty($trainer->intro_video_url) ? 'block' : 'none'; ?>;">
                            <?php if (!empty($trainer->intro_video_url)): ?>
                            <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:12px;background:#000;">
                                <iframe src="<?php echo esc_url(ptp_get_video_embed_url($trainer->intro_video_url)); ?>" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen></iframe>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Location & Specialties -->
            <div class="ptp-step" data-step="2">
                <div class="ptp-onboard-card">
                    <h2 class="ptp-onboard-card-title">Training Locations</h2>
                    <p class="ptp-onboard-card-desc">Add the exact locations where you train. Parents will select from these when booking.</p>
                    
                    <div class="ptp-form-group">
                        <label class="ptp-form-label">Your Base Location</label>
                        <p class="ptp-form-hint" style="margin-bottom: 8px;">This helps parents find you when searching by location.</p>
                        <div class="ptp-location-wrap">
                            <svg class="ptp-location-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <input type="text" name="location" class="ptp-form-input" value="<?php echo esc_attr($trainer->location ?? ''); ?>" placeholder="Start typing an address..." id="base-location-input" autocomplete="off">
                        </div>
                        <input type="hidden" name="latitude" id="base-latitude" value="<?php echo esc_attr($trainer->latitude ?? ''); ?>">
                        <input type="hidden" name="longitude" id="base-longitude" value="<?php echo esc_attr($trainer->longitude ?? ''); ?>">
                    </div>
                    
                    <div class="ptp-form-group">
                        <label class="ptp-form-label">Travel Radius</label>
                        <select name="travel_radius" class="ptp-form-select">
                            <option value="5" <?php selected($trainer->travel_radius ?? 15, 5); ?>>Up to 5 miles</option>
                            <option value="10" <?php selected($trainer->travel_radius ?? 15, 10); ?>>Up to 10 miles</option>
                            <option value="15" <?php selected($trainer->travel_radius ?? 15, 15); ?>>Up to 15 miles</option>
                            <option value="25" <?php selected($trainer->travel_radius ?? 15, 25); ?>>Up to 25 miles</option>
                            <option value="50" <?php selected($trainer->travel_radius ?? 15, 50); ?>>Up to 50 miles</option>
                        </select>
                    </div>
                    
                    <div class="ptp-form-group">
                        <label class="ptp-form-label">Training Fields / Parks</label>
                        <p class="ptp-form-hint" style="margin-bottom: 12px;">Add specific fields or facilities where you train. Start typing to search for locations.</p>
                        <div id="training-locations-list">
                            <?php if (!empty($existing_locations)): ?>
                                <?php foreach ($existing_locations as $i => $loc): 
                                    $locData = is_array($loc) ? $loc : array('name' => $loc, 'address' => '', 'lat' => '', 'lng' => '');
                                ?>
                                <div class="ptp-location-field">
                                    <div class="ptp-location-input-group">
                                        <input type="text" class="ptp-form-input location-autocomplete" value="<?php echo esc_attr($locData['name'] ?? $locData); ?>" placeholder="Search for a park, field, or facility..." autocomplete="off">
                                        <input type="hidden" name="training_locations[<?php echo $i; ?>][name]" value="<?php echo esc_attr($locData['name'] ?? $locData); ?>">
                                        <input type="hidden" name="training_locations[<?php echo $i; ?>][address]" value="<?php echo esc_attr($locData['address'] ?? ''); ?>">
                                        <input type="hidden" name="training_locations[<?php echo $i; ?>][lat]" value="<?php echo esc_attr($locData['lat'] ?? ''); ?>">
                                        <input type="hidden" name="training_locations[<?php echo $i; ?>][lng]" value="<?php echo esc_attr($locData['lng'] ?? ''); ?>">
                                    </div>
                                    <button type="button" class="ptp-remove-location" onclick="removeLocation(this)">×</button>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="ptp-location-field">
                                    <div class="ptp-location-input-group">
                                        <input type="text" class="ptp-form-input location-autocomplete" placeholder="Search for a park, field, or facility..." autocomplete="off">
                                        <input type="hidden" name="training_locations[0][name]" value="">
                                        <input type="hidden" name="training_locations[0][address]" value="">
                                        <input type="hidden" name="training_locations[0][lat]" value="">
                                        <input type="hidden" name="training_locations[0][lng]" value="">
                                    </div>
                                    <button type="button" class="ptp-remove-location" onclick="removeLocation(this)">×</button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="ptp-add-location-btn" onclick="addLocationField()">+ Add Another Location</button>
                        
                        <!-- Mini Map Preview -->
                        <div id="locations-map-preview" style="margin-top: 16px; height: 200px; border-radius: 12px; overflow: hidden; background: #F3F4F6; display: none;">
                            <div id="locations-map" style="width: 100%; height: 100%;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="ptp-onboard-card">
                    <h2 class="ptp-onboard-card-title">Training Specialties</h2>
                    <p class="ptp-onboard-card-desc">Select what you specialize in (choose at least 2)</p>
                    
                    <div class="ptp-specialties-grid">
                        <?php foreach ($specialties_list as $key => $label): ?>
                            <div class="ptp-specialty-item">
                                <input type="checkbox" name="specialties[]" value="<?php echo esc_attr($key); ?>" id="spec-<?php echo $key; ?>" <?php checked(in_array($key, $current_specialties)); ?>>
                                <label for="spec-<?php echo $key; ?>" class="ptp-specialty-label"><?php echo esc_html($label); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Rate & Availability -->
            <div class="ptp-step" data-step="3">
                <div class="ptp-onboard-card">
                    <h2 class="ptp-onboard-card-title">Session Rate</h2>
                    <p class="ptp-onboard-card-desc">Set your hourly rate. You can change this anytime.</p>
                    
                    <div class="ptp-rate-display">
                        <div class="ptp-rate-value">$<span id="rate-value"><?php echo intval($trainer->hourly_rate ?? 75); ?></span><span>/hour</span></div>
                    </div>
                    
                    <input type="range" name="hourly_rate" class="ptp-rate-slider" min="25" max="200" step="5" value="<?php echo intval($trainer->hourly_rate ?? 75); ?>" oninput="updateRate(this.value)">
                    
                    <div class="ptp-rate-labels">
                        <span>$25</span>
                        <span>$200</span>
                    </div>
                    
                    <p class="ptp-form-hint" style="margin-top: 16px;">PTP takes 20% to cover payment processing and platform costs. You'll receive 80% of your rate.</p>
                </div>
                
                <div class="ptp-onboard-card">
                    <h2 class="ptp-onboard-card-title">Weekly Availability</h2>
                    <p class="ptp-onboard-card-desc">Set your typical available hours. Families will see when you're available.</p>
                    
                    <div class="ptp-avail-list">
                        <?php foreach ($days as $i => $day): 
                            $slot = $avail_by_day[$i] ?? null;
                            $is_active = $slot && $slot->is_active;
                            $start = $slot ? substr($slot->start_time, 0, 5) : '16:00';
                            $end = $slot ? substr($slot->end_time, 0, 5) : '20:00';
                        ?>
                            <div class="ptp-avail-item">
                                <div class="ptp-avail-toggle">
                                    <input type="checkbox" name="availability[<?php echo $i; ?>][enabled]" value="1" <?php checked($is_active); ?>>
                                    <div class="ptp-avail-toggle-track"></div>
                                    <div class="ptp-avail-toggle-thumb"></div>
                                </div>
                                <div class="ptp-avail-day"><?php echo $day; ?></div>
                                <div class="ptp-avail-times">
                                    <input type="time" name="availability[<?php echo $i; ?>][start]" class="ptp-avail-time" value="<?php echo $start; ?>">
                                    <span class="ptp-avail-to">to</span>
                                    <input type="time" name="availability[<?php echo $i; ?>][end]" class="ptp-avail-time" value="<?php echo $end; ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Step 4: Payment Setup -->
            <div class="ptp-step" data-step="4">
                <div class="ptp-onboard-card">
                    <h2 class="ptp-onboard-card-title">Payment Information</h2>
                    <p class="ptp-onboard-card-desc">Required for tax reporting (1099-NEC). Your information is encrypted and secure.</p>
                    
                    <div class="ptp-payment-note">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <div class="ptp-payment-note-text">
                            Your tax information is encrypted and only used for IRS 1099-NEC reporting. We never share this information.
                        </div>
                    </div>
                    
                    <div class="ptp-form-group">
                        <label class="ptp-form-label">Legal Name (as it appears on tax forms)</label>
                        <input type="text" name="legal_name" class="ptp-form-input" value="<?php echo esc_attr($trainer->legal_name ?? $user->display_name); ?>" placeholder="Full legal name">
                    </div>
                    
                    <div class="ptp-form-group">
                        <label class="ptp-form-label">Address Line 1</label>
                        <input type="text" name="tax_address_line1" class="ptp-form-input" value="<?php echo esc_attr($trainer->tax_address_line1 ?? ''); ?>" placeholder="Street address">
                    </div>
                    
                    <div class="ptp-form-group">
                        <label class="ptp-form-label">Address Line 2 <span class="optional">(optional)</span></label>
                        <input type="text" name="tax_address_line2" class="ptp-form-input" value="<?php echo esc_attr($trainer->tax_address_line2 ?? ''); ?>" placeholder="Apt, suite, unit">
                    </div>
                    
                    <div class="ptp-form-row two-col">
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">City</label>
                            <input type="text" name="tax_city" class="ptp-form-input" value="<?php echo esc_attr($trainer->tax_city ?? ''); ?>">
                        </div>
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">State</label>
                            <select name="tax_state" class="ptp-form-select">
                                <option value="">Select</option>
                                <?php
                                $states = array('AL','AK','AZ','AR','CA','CO','CT','DE','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY','DC');
                                foreach ($states as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php selected($trainer->tax_state ?? '', $st); ?>><?php echo $st; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="ptp-form-group">
                        <label class="ptp-form-label">ZIP Code</label>
                        <input type="text" name="tax_zip" class="ptp-form-input" value="<?php echo esc_attr($trainer->tax_zip ?? ''); ?>" maxlength="10" style="max-width: 150px;">
                    </div>
                    
                    <div style="background: #FEF3C7; border-radius: 12px; padding: 16px; margin-top: 20px;">
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#92400E" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <div style="font-size: 13px; color: #92400E;">
                                <strong>Bank account setup:</strong> After completing your profile, you can connect your bank account in the Earnings tab to receive direct deposits. This is handled securely through Stripe.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Step 5: Review & Submit -->
            <div class="ptp-step" data-step="5">
                <div class="ptp-onboard-card">
                    <h2 class="ptp-onboard-card-title">Review Your Profile</h2>
                    <p class="ptp-onboard-card-desc">Make sure everything looks good before going live</p>
                    
                    <div style="background: #F9FAFB; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                        <div style="display: flex; gap: 16px; align-items: flex-start; margin-bottom: 16px;">
                            <div id="review-photo" style="width: 80px; height: 80px; background: #E5E7EB; border-radius: 12px; overflow: hidden;">
                                <?php if ($photo): ?>
                                    <img src="<?php echo esc_url($photo); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 style="font-size: 18px; font-weight: 700; margin: 0 0 4px;"><?php echo esc_html($trainer->display_name); ?></h3>
                                <p id="review-headline" style="font-size: 14px; color: #6B7280; margin: 0;"><?php echo esc_html($trainer->headline ?? 'No headline set'); ?></p>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 14px;">
                            <div><strong>Location:</strong> <span id="review-location"><?php echo esc_html($trainer->location ?? 'Not set'); ?></span></div>
                            <div><strong>Rate:</strong> $<span id="review-rate"><?php echo intval($trainer->hourly_rate ?? 75); ?></span>/hour</div>
                        </div>
                    </div>
                    
                    <div class="ptp-form-group">
                        <label style="display: flex; gap: 12px; cursor: pointer; align-items: flex-start;">
                            <input type="checkbox" name="contractor_agreement" id="contractor-checkbox" value="1" required style="width: 20px; height: 20px; accent-color: #FCB900; flex-shrink: 0; margin-top: 2px;">
                            <span style="font-size: 14px; color: #374151; line-height: 1.5;">I agree to the <a href="#" onclick="openAgreementModal(); return false;" style="color: #FCB900; font-weight: 600;">Independent Contractor Agreement</a> and understand I am responsible for my own taxes as an independent contractor.</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="ptp-onboard-nav">
            <div class="ptp-onboard-nav-inner">
                <button type="button" class="ptp-nav-btn ptp-nav-btn-back" id="btn-back" onclick="prevStep()" style="display: none;">Back</button>
                <button type="button" class="ptp-nav-btn ptp-nav-btn-next" id="btn-next" onclick="nextStep()">Continue</button>
            </div>
        </div>
    </form>
</div>

<!-- Independent Contractor Agreement Modal -->
<div id="agreement-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #fff; border-radius: 20px; width: 100%; max-width: 700px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;">
        <div style="padding: 20px 24px; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 18px; font-weight: 700; margin: 0;">Independent Contractor Agreement</h3>
            <button onclick="closeAgreementModal()" style="width: 36px; height: 36px; border-radius: 50%; border: none; background: #F3F4F6; cursor: pointer; font-size: 20px;">×</button>
        </div>
        <div style="padding: 24px; overflow-y: auto; flex: 1; font-size: 14px; line-height: 1.7; color: #374151;">
            <p style="margin: 0 0 16px;"><strong>PLAYERS TEACHING PLAYERS (PTP) INDEPENDENT CONTRACTOR AGREEMENT</strong></p>
            
            <p style="margin: 0 0 16px;">This Independent Contractor Agreement ("Agreement") is entered into between Players Teaching Players LLC ("PTP" or "Company") and the undersigned independent contractor ("Trainer" or "Contractor").</p>
            
            <p style="margin: 0 0 8px;"><strong>1. INDEPENDENT CONTRACTOR STATUS</strong></p>
            <p style="margin: 0 0 16px;">Trainer acknowledges and agrees that they are an independent contractor and not an employee of PTP. Trainer is responsible for all applicable federal, state, and local taxes, including self-employment taxes. PTP will issue a 1099-NEC form for all earnings over $600 annually.</p>
            
            <p style="margin: 0 0 8px;"><strong>2. SERVICES</strong></p>
            <p style="margin: 0 0 16px;">Trainer agrees to provide soccer training services to clients ("Players") who book sessions through the PTP platform. Trainer maintains control over the methods and means of performing services, including training techniques and drills used.</p>
            
            <p style="margin: 0 0 8px;"><strong>3. COMPENSATION</strong></p>
            <p style="margin: 0 0 16px;">Trainer will receive payment for completed training sessions as specified in their profile rate. PTP will deduct a platform fee (currently 20%) from each session. Payments will be processed through Stripe and deposited to Trainer's connected bank account.</p>
            
            <p style="margin: 0 0 8px;"><strong>4. INSURANCE & LIABILITY</strong></p>
            <p style="margin: 0 0 16px;">Trainer agrees to maintain appropriate liability insurance for their training activities. Trainer assumes all responsibility for any injuries or damages that may occur during training sessions. Trainer agrees to indemnify and hold harmless PTP from any claims arising from training sessions.</p>
            
            <p style="margin: 0 0 8px;"><strong>5. CONDUCT & PROFESSIONALISM</strong></p>
            <p style="margin: 0 0 16px;">Trainer agrees to: (a) Arrive on time for all scheduled sessions; (b) Maintain professional conduct at all times; (c) Never engage in any inappropriate behavior with minors; (d) Follow all applicable laws and regulations; (e) Maintain required background check status.</p>
            
            <p style="margin: 0 0 8px;"><strong>6. CANCELLATION POLICY</strong></p>
            <p style="margin: 0 0 16px;">Trainer must provide at least 24 hours notice for any session cancellations. Repeated late cancellations may result in removal from the platform.</p>
            
            <p style="margin: 0 0 8px;"><strong>7. NON-CIRCUMVENTION</strong></p>
            <p style="margin: 0 0 16px;">Trainer agrees not to solicit clients obtained through PTP for private training arrangements outside the platform for a period of 12 months after their last session with that client through PTP.</p>
            
            <p style="margin: 0 0 8px;"><strong>8. TERMINATION</strong></p>
            <p style="margin: 0 0 16px;">Either party may terminate this Agreement at any time. PTP reserves the right to remove Trainer from the platform for violations of this Agreement or platform policies.</p>
            
            <p style="margin: 0 0 8px;"><strong>9. CONFIDENTIALITY</strong></p>
            <p style="margin: 0 0 16px;">Trainer agrees to keep confidential all client information, including contact details, obtained through the PTP platform.</p>
            
            <p style="margin: 0 0 16px;"><strong>10. ENTIRE AGREEMENT</strong></p>
            <p style="margin: 0 0 16px;">This Agreement constitutes the entire agreement between the parties. By checking the agreement box and completing the onboarding process, Trainer acknowledges they have read, understood, and agree to be bound by this Agreement.</p>
        </div>
        <div style="padding: 16px 24px; border-top: 1px solid #E5E7EB; display: flex; justify-content: flex-end; gap: 12px;">
            <button onclick="closeAgreementModal()" style="padding: 12px 24px; border-radius: 10px; border: 2px solid #E5E7EB; background: #fff; font-weight: 600; cursor: pointer;">Close</button>
            <button onclick="acceptAgreement()" style="padding: 12px 24px; border-radius: 10px; border: none; background: #FCB900; color: #000; font-weight: 600; cursor: pointer;">I Accept</button>
        </div>
    </div>
</div>

<script>
let currentStep = 1;
const totalSteps = 5;

// Agreement Modal Functions
function openAgreementModal() {
    const modal = document.getElementById('agreement-modal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeAgreementModal() {
    const modal = document.getElementById('agreement-modal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

function acceptAgreement() {
    document.getElementById('contractor-checkbox').checked = true;
    closeAgreementModal();
}

// Close modal on backdrop click
document.addEventListener('click', function(e) {
    const modal = document.getElementById('agreement-modal');
    if (e.target === modal) {
        closeAgreementModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAgreementModal();
    }
});

function updateProgress() {
    document.querySelectorAll('.ptp-onboard-step').forEach((el, i) => {
        el.classList.remove('active', 'done');
        if (i + 1 < currentStep) el.classList.add('done');
        if (i + 1 === currentStep) el.classList.add('active');
    });
    document.getElementById('current-step').textContent = currentStep;
    
    document.getElementById('btn-back').style.display = currentStep === 1 ? 'none' : 'block';
    document.getElementById('btn-next').textContent = currentStep === totalSteps ? 'Complete Profile' : 'Continue';
}

function showStep(step) {
    document.querySelectorAll('.ptp-step').forEach(el => el.classList.remove('active'));
    document.querySelector(`.ptp-step[data-step="${step}"]`).classList.add('active');
    window.scrollTo(0, 0);
}

function nextStep() {
    if (currentStep < totalSteps) {
        currentStep++;
        showStep(currentStep);
        updateProgress();
        updateReview();
    } else {
        submitForm();
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
        updateProgress();
    }
}

function updateRate(val) {
    document.getElementById('rate-value').textContent = val;
}

function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photo-preview').innerHTML = '<img src="' + e.target.result + '" alt="">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function updateReview() {
    const form = document.getElementById('onboarding-form');
    document.getElementById('review-headline').textContent = form.headline.value || 'No headline set';
    document.getElementById('review-location').textContent = form.location.value || 'Not set';
    document.getElementById('review-rate').textContent = form.hourly_rate.value;
}

function submitForm() {
    const form = document.getElementById('onboarding-form');
    const formData = new FormData(form);
    formData.append('action', 'ptp_complete_onboarding');
    formData.append('nonce', typeof ptp_ajax !== 'undefined' ? ptp_ajax.nonce : '<?php echo wp_create_nonce('ptp_nonce'); ?>');
    
    document.getElementById('btn-next').disabled = true;
    document.getElementById('btn-next').textContent = 'Saving...';
    
    const ajaxUrl = typeof ptp_ajax !== 'undefined' ? ptp_ajax.ajax_url : '<?php echo admin_url('admin-ajax.php'); ?>';
    
    fetch(ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.data.redirect || '<?php echo home_url('/trainer-dashboard/?welcome=1'); ?>';
        } else {
            alert(data.data?.message || 'Error saving profile. Please try again.');
            document.getElementById('btn-next').disabled = false;
            document.getElementById('btn-next').textContent = 'Complete Profile';
        }
    })
    .catch(err => {
        console.error('Submit error:', err);
        alert('Error saving profile. Please try again.');
        document.getElementById('btn-next').disabled = false;
        document.getElementById('btn-next').textContent = 'Complete Profile';
    });
}

// ===== Google Maps Places Integration =====
let locationIndex = <?php echo max(count($existing_locations), 1); ?>;
let locationsMap = null;
let locationMarkers = [];

function addLocationField() {
    const list = document.getElementById('training-locations-list');
    const field = document.createElement('div');
    field.className = 'ptp-location-field';
    field.innerHTML = `
        <div class="ptp-location-input-group">
            <input type="text" class="ptp-form-input location-autocomplete" placeholder="Search for a park, field, or facility..." autocomplete="off">
            <input type="hidden" name="training_locations[${locationIndex}][name]" value="">
            <input type="hidden" name="training_locations[${locationIndex}][address]" value="">
            <input type="hidden" name="training_locations[${locationIndex}][lat]" value="">
            <input type="hidden" name="training_locations[${locationIndex}][lng]" value="">
            <svg class="location-verified" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <button type="button" class="ptp-remove-location" onclick="removeLocation(this)">×</button>
    `;
    list.appendChild(field);
    locationIndex++;
    
    // Initialize autocomplete on new field
    const input = field.querySelector('.location-autocomplete');
    initLocationAutocomplete(input);
    input.focus();
}

function removeLocation(btn) {
    const field = btn.closest('.ptp-location-field');
    field.remove();
    updateLocationsMap();
}

function initLocationAutocomplete(input) {
    // Allow manual entry to update hidden name field
    input.addEventListener('blur', () => {
        const group = input.closest('.ptp-location-input-group');
        const nameField = group.querySelector('input[name$="[name]"]');
        if (nameField && input.value.trim() && !nameField.value) {
            nameField.value = input.value.trim();
        }
    });
    
    // Also update on input for real-time sync
    input.addEventListener('input', () => {
        const group = input.closest('.ptp-location-input-group');
        const nameField = group.querySelector('input[name$="[name]"]');
        if (nameField) {
            nameField.value = input.value.trim();
        }
        group.classList.remove('verified');
    });
    
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        console.warn('Google Maps API not loaded - using manual entry');
        return;
    }
    
    const autocomplete = new google.maps.places.Autocomplete(input, {
        types: ['establishment', 'geocode'],
        componentRestrictions: { country: 'us' },
        fields: ['name', 'formatted_address', 'geometry', 'place_id']
    });
    
    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        const group = input.closest('.ptp-location-input-group');
        
        if (place.geometry) {
            // Update hidden fields
            group.querySelector('input[name$="[name]"]').value = place.name || place.formatted_address;
            group.querySelector('input[name$="[address]"]').value = place.formatted_address || '';
            group.querySelector('input[name$="[lat]"]').value = place.geometry.location.lat();
            group.querySelector('input[name$="[lng]"]').value = place.geometry.location.lng();
            
            // Mark as verified
            group.classList.add('verified');
            
            // Update map
            updateLocationsMap();
        }
    });
}

function initBaseLocationAutocomplete() {
    const input = document.getElementById('base-location-input');
    if (!input || typeof google === 'undefined') return;
    
    const autocomplete = new google.maps.places.Autocomplete(input, {
        types: ['(cities)'],
        componentRestrictions: { country: 'us' },
        fields: ['formatted_address', 'geometry', 'address_components']
    });
    
    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (place.geometry) {
            document.getElementById('base-latitude').value = place.geometry.location.lat();
            document.getElementById('base-longitude').value = place.geometry.location.lng();
            
            // Extract city and state
            let city = '', state = '';
            place.address_components.forEach(comp => {
                if (comp.types.includes('locality')) city = comp.long_name;
                if (comp.types.includes('administrative_area_level_1')) state = comp.short_name;
            });
            
            if (city && state) {
                input.value = city + ', ' + state;
            }
        }
    });
}

function updateLocationsMap() {
    const mapContainer = document.getElementById('locations-map-preview');
    const mapEl = document.getElementById('locations-map');
    
    if (!mapEl || typeof google === 'undefined') return;
    
    // Gather all locations with coordinates
    const locations = [];
    document.querySelectorAll('.ptp-location-input-group').forEach(group => {
        const lat = group.querySelector('input[name$="[lat]"]')?.value;
        const lng = group.querySelector('input[name$="[lng]"]')?.value;
        const name = group.querySelector('input[name$="[name]"]')?.value;
        
        if (lat && lng && parseFloat(lat) && parseFloat(lng)) {
            locations.push({
                lat: parseFloat(lat),
                lng: parseFloat(lng),
                name: name
            });
        }
    });
    
    // Show/hide map based on whether we have locations
    if (locations.length === 0) {
        mapContainer.style.display = 'none';
        return;
    }
    
    mapContainer.style.display = 'block';
    
    // Initialize or update map
    if (!locationsMap) {
        locationsMap = new google.maps.Map(mapEl, {
            zoom: 12,
            center: locations[0],
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false
        });
    }
    
    // Clear existing markers
    locationMarkers.forEach(m => m.setMap(null));
    locationMarkers = [];
    
    // Add markers
    const bounds = new google.maps.LatLngBounds();
    locations.forEach((loc, i) => {
        const marker = new google.maps.Marker({
            position: { lat: loc.lat, lng: loc.lng },
            map: locationsMap,
            title: loc.name,
            label: {
                text: String(i + 1),
                color: '#000',
                fontWeight: 'bold'
            },
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 12,
                fillColor: '#FCB900',
                fillOpacity: 1,
                strokeColor: '#000',
                strokeWeight: 2
            }
        });
        locationMarkers.push(marker);
        bounds.extend(marker.getPosition());
    });
    
    // Fit bounds
    if (locations.length > 1) {
        locationsMap.fitBounds(bounds);
    } else {
        locationsMap.setCenter(locations[0]);
        locationsMap.setZoom(14);
    }
}

// Initialize Google Maps when API loads
function initGoogleMaps() {
    // Init base location autocomplete
    initBaseLocationAutocomplete();
    
    // Init all existing location autocompletes
    document.querySelectorAll('.location-autocomplete').forEach(input => {
        initLocationAutocomplete(input);
    });
    
    // Update map if we have existing locations
    updateLocationsMap();
}

// Check if Google Maps is already loaded or wait for it
if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
    document.addEventListener('DOMContentLoaded', initGoogleMaps);
} else {
    // Will be called by the callback parameter in the script URL
    window.initGoogleMaps = initGoogleMaps;
}

// Init
updateProgress();
</script>

<?php 
$google_maps_api_key = get_option('ptp_google_maps_api_key', '');
if (!empty($google_maps_api_key)): 
?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr($google_maps_api_key); ?>&libraries=places&callback=initGoogleMaps" async defer></script>
<?php else: ?>
<script>
// No Google Maps API key configured - fallback to basic text input
console.warn('Google Maps API key not configured. Location autocomplete disabled.');
document.addEventListener('DOMContentLoaded', function() {
    // Initialize location inputs for manual entry
    document.querySelectorAll('.location-autocomplete').forEach(input => {
        initLocationAutocomplete(input);
    });
    
    // Show admin notice
    <?php if (current_user_can('manage_options')): ?>
    const notice = document.createElement('div');
    notice.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: #FEF3C7; color: #92400E; padding: 16px 20px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); z-index: 1000; max-width: 320px; font-size: 14px;';
    notice.innerHTML = '<strong>⚙️ Admin:</strong> Add your Google Maps API key in <a href="<?php echo admin_url('admin.php?page=ptp-settings'); ?>" style="color: #B45309; text-decoration: underline;">PTP Settings</a> to enable location autocomplete.';
    document.body.appendChild(notice);
    setTimeout(() => notice.remove(), 10000);
    <?php endif; ?>
});
</script>
<?php endif; ?>
