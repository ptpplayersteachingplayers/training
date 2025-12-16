<?php
/**
 * Template: Account Settings - PTP Style v25.0
 * Clean trainer settings - no emojis, PTP branding
 */
defined('ABSPATH') || exit;

$user = wp_get_current_user();
$is_trainer = PTP_User::is_trainer();
$trainer = $is_trainer ? PTP_Trainer::get_by_user_id($user->ID) : null;
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'profile';

$availability_by_day = array();
if ($trainer) {
    $availability = PTP_Availability::get_weekly($trainer->id);
    foreach ($availability as $slot) {
        $availability_by_day[$slot->day_of_week] = $slot;
    }
}

$stripe_connected = $trainer && !empty($trainer->stripe_account_id);
$stripe_status = 'not_connected';
if ($stripe_connected) {
    $stripe_status = ($trainer->stripe_charges_enabled ?? false) ? 'active' : 'pending';
}

$photo = $trainer && $trainer->photo_url ? $trainer->photo_url : 'https://ui-avatars.com/api/?name=' . urlencode($user->display_name) . '&size=200&background=FCB900&color=0A0A0A&bold=true';
$first_name = $user->first_name ?: explode(' ', $user->display_name)[0];
$dashboard_url = $is_trainer ? home_url('/trainer-dashboard/') : home_url('/my-training/');
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { overflow-x: hidden !important; max-width: 100vw; }

:root {
    --ptp-yellow: #FCB900;
    --ptp-black: #0E0F11;
    --ptp-dark: #1A1B1E;
    --ptp-gray-900: #111827;
    --ptp-gray-700: #374151;
    --ptp-gray-500: #6B7280;
    --ptp-gray-400: #9CA3AF;
    --ptp-gray-200: #E5E7EB;
    --ptp-gray-100: #F3F4F6;
    --ptp-gray-50: #F9FAFB;
    --ptp-green: #10B981;
    --ptp-red: #EF4444;
    --ptp-blue: #3B82F6;
    --ptp-purple: #635BFF;
}

.ptp-account {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--ptp-gray-100);
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
    color: var(--ptp-gray-900);
}

/* Header */
.ptp-account-header {
    background: var(--ptp-black);
    padding: 32px 16px 80px;
    text-align: center;
    position: relative;
}

.ptp-account-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 40px;
    background: var(--ptp-gray-100);
    border-radius: 40px 40px 0 0;
}

.ptp-back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 24px;
    transition: color 0.2s;
}

.ptp-back-link:hover { color: #fff; }

.ptp-account-avatar {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    border: 3px solid var(--ptp-yellow);
    object-fit: cover;
    margin-bottom: 16px;
}

.ptp-account-name {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 4px;
}

.ptp-account-email {
    color: rgba(255,255,255,0.6);
    font-size: 14px;
    margin-bottom: 12px;
}

.ptp-account-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(252,185,0,0.2);
    color: var(--ptp-yellow);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

/* Container */
.ptp-account-container {
    max-width: 800px;
    margin: -40px auto 0;
    padding: 0 16px 60px;
    position: relative;
    z-index: 1;
}

/* Tabs */
.ptp-account-tabs {
    display: flex;
    gap: 4px;
    background: #fff;
    padding: 6px;
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 24px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.ptp-account-tabs::-webkit-scrollbar { display: none; }

.ptp-account-tab {
    flex: 1;
    min-width: max-content;
    padding: 12px 20px;
    border: none;
    background: transparent;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    color: var(--ptp-gray-500);
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    text-align: center;
    white-space: nowrap;
}

.ptp-account-tab:hover {
    background: var(--ptp-gray-50);
    color: var(--ptp-gray-900);
}

.ptp-account-tab.active {
    background: var(--ptp-black);
    color: #fff;
}

/* Panels */
.ptp-panel { display: none; }
.ptp-panel.active { display: block; }

/* Cards */
.ptp-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    overflow: hidden;
    margin-bottom: 20px;
}

.ptp-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--ptp-gray-100);
}

.ptp-card-title {
    font-size: 17px;
    font-weight: 700;
    color: var(--ptp-gray-900);
}

.ptp-card-subtitle {
    font-size: 13px;
    color: var(--ptp-gray-500);
    margin-top: 4px;
}

.ptp-card-body { padding: 24px; }

/* Form Elements */
.ptp-form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

@media (min-width: 600px) {
    .ptp-form-grid { grid-template-columns: 1fr 1fr; }
    .ptp-form-grid .ptp-form-full { grid-column: 1 / -1; }
}

.ptp-form-group { margin-bottom: 0; }
.ptp-form-group.mb { margin-bottom: 20px; }

.ptp-form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--ptp-gray-700);
    margin-bottom: 8px;
}

.ptp-form-input,
.ptp-form-select,
.ptp-form-textarea {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid var(--ptp-gray-200);
    border-radius: 10px;
    font-size: 15px;
    font-family: inherit;
    transition: all 0.2s;
    background: var(--ptp-gray-50);
}

.ptp-form-input:focus,
.ptp-form-select:focus,
.ptp-form-textarea:focus {
    outline: none;
    border-color: var(--ptp-yellow);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(252,185,0,0.1);
}

.ptp-form-textarea {
    resize: vertical;
    min-height: 100px;
}

.ptp-form-hint {
    font-size: 12px;
    color: var(--ptp-gray-400);
    margin-top: 6px;
}

/* Photo Upload */
.ptp-photo-upload {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 24px;
    background: var(--ptp-gray-50);
    border-radius: 12px;
    border: 2px dashed var(--ptp-gray-200);
    text-align: center;
}

@media (min-width: 600px) {
    .ptp-photo-upload {
        flex-direction: row;
        text-align: left;
    }
}

.ptp-photo-preview {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--ptp-yellow);
    flex-shrink: 0;
}

.ptp-photo-info { flex: 1; }
.ptp-photo-info p {
    color: var(--ptp-gray-500);
    font-size: 13px;
    margin-top: 4px;
}

/* Buttons */
.ptp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.ptp-btn-primary {
    background: var(--ptp-yellow);
    color: var(--ptp-black);
}

.ptp-btn-primary:hover {
    background: #E5A800;
    transform: translateY(-1px);
}

.ptp-btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.ptp-btn-secondary {
    background: var(--ptp-black);
    color: #fff;
}

.ptp-btn-secondary:hover { background: var(--ptp-dark); }

.ptp-btn-outline {
    background: #fff;
    color: var(--ptp-gray-700);
    border: 2px solid var(--ptp-gray-200);
}

.ptp-btn-outline:hover {
    border-color: var(--ptp-gray-300);
    background: var(--ptp-gray-50);
}

.ptp-btn-stripe {
    background: var(--ptp-purple);
    color: #fff;
}

.ptp-btn-stripe:hover { background: #5147e5; }

/* Toggles */
.ptp-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 0;
    border-bottom: 1px solid var(--ptp-gray-100);
    gap: 16px;
}

.ptp-toggle-row:last-child { border-bottom: none; padding-bottom: 0; }
.ptp-toggle-row:first-child { padding-top: 0; }

.ptp-toggle-info { flex: 1; }

.ptp-toggle-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--ptp-gray-900);
    margin-bottom: 2px;
}

.ptp-toggle-desc {
    font-size: 13px;
    color: var(--ptp-gray-500);
}

.ptp-switch {
    position: relative;
    width: 48px;
    height: 26px;
    background: var(--ptp-gray-200);
    border-radius: 13px;
    cursor: pointer;
    transition: all 0.3s;
    flex-shrink: 0;
}

.ptp-switch.active { background: var(--ptp-yellow); }

.ptp-switch::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    background: #fff;
    border-radius: 50%;
    transition: all 0.3s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ptp-switch.active::after { left: 25px; }

/* Availability Grid */
.ptp-avail-grid { display: flex; flex-direction: column; gap: 8px; }

.ptp-avail-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: var(--ptp-gray-50);
    border-radius: 12px;
    flex-wrap: wrap;
}

@media (min-width: 600px) {
    .ptp-avail-row { flex-wrap: nowrap; }
}

.ptp-avail-day {
    width: 80px;
    font-weight: 600;
    font-size: 14px;
    color: var(--ptp-gray-900);
}

.ptp-avail-toggle {
    position: relative;
    width: 40px;
    height: 22px;
    flex-shrink: 0;
}

.ptp-avail-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
    position: absolute;
}

.ptp-avail-toggle-track {
    position: absolute;
    inset: 0;
    background: var(--ptp-gray-200);
    border-radius: 11px;
    transition: background 0.2s;
    cursor: pointer;
}

.ptp-avail-toggle input:checked + .ptp-avail-toggle-track {
    background: var(--ptp-green);
}

.ptp-avail-toggle-track::after {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    background: #fff;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: transform 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.ptp-avail-toggle input:checked + .ptp-avail-toggle-track::after {
    transform: translateX(18px);
}

.ptp-avail-times {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
}

.ptp-avail-times.disabled { opacity: 0.4; pointer-events: none; }

.ptp-avail-time {
    padding: 10px 12px;
    border: 1px solid var(--ptp-gray-200);
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    flex: 1;
    min-width: 100px;
}

.ptp-avail-time:focus {
    outline: none;
    border-color: var(--ptp-yellow);
    box-shadow: 0 0 0 3px rgba(252,185,0,0.1);
}

.ptp-avail-time:disabled {
    background: var(--ptp-gray-100);
    cursor: not-allowed;
}

/* Stripe Box */
.ptp-stripe-box {
    background: linear-gradient(135deg, var(--ptp-purple) 0%, #7B73FF 100%);
    border-radius: 16px;
    padding: 24px;
    color: #fff;
    margin-bottom: 20px;
}

.ptp-stripe-box h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 8px;
}

.ptp-stripe-box p {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 16px;
    line-height: 1.5;
}

.ptp-stripe-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(255,255,255,0.2);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 16px;
}

.ptp-stripe-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.ptp-stripe-status-dot.green { background: #10B981; }
.ptp-stripe-status-dot.yellow { background: #FBBF24; }
.ptp-stripe-status-dot.red { background: #EF4444; }

/* Specialties */
.ptp-specialties-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

@media (min-width: 600px) {
    .ptp-specialties-grid { grid-template-columns: repeat(3, 1fr); }
}

.ptp-specialty-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 14px;
    background: var(--ptp-gray-50);
    border: 2px solid var(--ptp-gray-200);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 13px;
    font-weight: 500;
}

.ptp-specialty-chip:has(input:checked) {
    background: #FEF3C7;
    border-color: var(--ptp-yellow);
}

.ptp-specialty-chip input {
    accent-color: var(--ptp-yellow);
    width: 16px;
    height: 16px;
}

/* Alert */
.ptp-alert {
    padding: 16px;
    border-radius: 10px;
    font-size: 14px;
    margin-bottom: 20px;
}

.ptp-alert-success {
    background: #D1FAE5;
    color: #065F46;
}

.ptp-alert-error {
    background: #FEE2E2;
    color: #991B1B;
}

.ptp-alert-info {
    background: #DBEAFE;
    color: #1E40AF;
}
</style>

<div class="ptp-account">

<!-- Header -->
<div class="ptp-account-header">
    <a href="<?php echo esc_url($dashboard_url); ?>" class="ptp-back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
        Back to Dashboard
    </a>
    
    <img src="<?php echo esc_url($photo); ?>" alt="" class="ptp-account-avatar">
    <h1 class="ptp-account-name"><?php echo esc_html($user->display_name); ?></h1>
    <p class="ptp-account-email"><?php echo esc_html($user->user_email); ?></p>
    
    <?php if ($is_trainer): ?>
        <span class="ptp-account-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            Verified Trainer
        </span>
    <?php endif; ?>
</div>

<!-- Container -->
<div class="ptp-account-container">
    
    <!-- Tabs -->
    <div class="ptp-account-tabs">
        <a href="?tab=profile" class="ptp-account-tab <?php echo $active_tab === 'profile' ? 'active' : ''; ?>">Profile</a>
        <?php if ($is_trainer): ?>
            <a href="?tab=trainer" class="ptp-account-tab <?php echo $active_tab === 'trainer' ? 'active' : ''; ?>">Trainer Info</a>
            <a href="?tab=availability" class="ptp-account-tab <?php echo $active_tab === 'availability' ? 'active' : ''; ?>">Availability</a>
            <a href="?tab=payments" class="ptp-account-tab <?php echo $active_tab === 'payments' ? 'active' : ''; ?>">Payments</a>
        <?php endif; ?>
        <a href="?tab=notifications" class="ptp-account-tab <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>">Notifications</a>
        <a href="?tab=security" class="ptp-account-tab <?php echo $active_tab === 'security' ? 'active' : ''; ?>">Security</a>
    </div>
    
    <!-- Profile Panel -->
    <div class="ptp-panel <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" id="panel-profile">
        <form id="profile-form">
            <input type="hidden" name="action" value="ptp_update_profile">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ptp_nonce'); ?>">
            
            <div class="ptp-card">
                <div class="ptp-card-header">
                    <h2 class="ptp-card-title">Profile Photo</h2>
                </div>
                <div class="ptp-card-body">
                    <div class="ptp-photo-upload">
                        <img src="<?php echo esc_url($photo); ?>" alt="" class="ptp-photo-preview" id="photo-preview">
                        <div class="ptp-photo-info">
                            <input type="file" id="photo-input" name="photo" accept="image/*" style="display: none;">
                            <button type="button" class="ptp-btn ptp-btn-secondary" onclick="document.getElementById('photo-input').click()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                Upload Photo
                            </button>
                            <p>JPG or PNG. Max 5MB.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="ptp-card">
                <div class="ptp-card-header">
                    <h2 class="ptp-card-title">Personal Information</h2>
                </div>
                <div class="ptp-card-body">
                    <div class="ptp-form-grid">
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">First Name</label>
                            <input type="text" name="first_name" class="ptp-form-input" value="<?php echo esc_attr($user->first_name); ?>">
                        </div>
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">Last Name</label>
                            <input type="text" name="last_name" class="ptp-form-input" value="<?php echo esc_attr($user->last_name); ?>">
                        </div>
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">Email</label>
                            <input type="email" name="email" class="ptp-form-input" value="<?php echo esc_attr($user->user_email); ?>">
                        </div>
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">Phone</label>
                            <input type="tel" name="phone" class="ptp-form-input" value="<?php echo esc_attr(get_user_meta($user->ID, 'phone', true)); ?>" placeholder="(555) 123-4567">
                        </div>
                    </div>
                    <div style="margin-top: 24px;">
                        <button type="submit" class="ptp-btn ptp-btn-primary">Save Changes</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <?php if ($is_trainer): ?>
    <!-- Trainer Info Panel -->
    <div class="ptp-panel <?php echo $active_tab === 'trainer' ? 'active' : ''; ?>" id="panel-trainer">
        <form id="trainer-profile-form">
            <input type="hidden" name="action" value="ptp_update_trainer_profile">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ptp_nonce'); ?>">
            
            <div class="ptp-card">
                <div class="ptp-card-header">
                    <h2 class="ptp-card-title">Trainer Profile</h2>
                    <p class="ptp-card-subtitle">This information appears on your public profile</p>
                </div>
                <div class="ptp-card-body">
                    <div class="ptp-form-grid">
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">Display Name</label>
                            <input type="text" name="display_name" class="ptp-form-input" value="<?php echo esc_attr($trainer->display_name ?? ''); ?>">
                        </div>
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">Hourly Rate ($)</label>
                            <input type="number" name="hourly_rate" class="ptp-form-input" value="<?php echo esc_attr($trainer->hourly_rate ?? 80); ?>" min="40" max="300">
                        </div>
                        <div class="ptp-form-group ptp-form-full">
                            <label class="ptp-form-label">Bio</label>
                            <textarea name="bio" class="ptp-form-textarea" placeholder="Tell parents about your background, coaching style, and what makes your training unique..."><?php echo esc_textarea($trainer->bio ?? ''); ?></textarea>
                        </div>
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">Playing Level</label>
                            <select name="playing_level" class="ptp-form-select">
                                <option value="">Select level</option>
                                <option value="pro" <?php selected($trainer->playing_level ?? '', 'pro'); ?>>Professional Player</option>
                                <option value="college_d1" <?php selected($trainer->playing_level ?? '', 'college_d1'); ?>>NCAA Division 1</option>
                                <option value="college_d2" <?php selected($trainer->playing_level ?? '', 'college_d2'); ?>>NCAA Division 2</option>
                                <option value="college_d3" <?php selected($trainer->playing_level ?? '', 'college_d3'); ?>>NCAA Division 3</option>
                                <option value="academy" <?php selected($trainer->playing_level ?? '', 'academy'); ?>>MLS/NWSL Academy</option>
                                <option value="semi_pro" <?php selected($trainer->playing_level ?? '', 'semi_pro'); ?>>Semi-Professional</option>
                            </select>
                        </div>
                        <div class="ptp-form-group">
                            <label class="ptp-form-label">College/Team</label>
                            <input type="text" name="college" class="ptp-form-input" value="<?php echo esc_attr($trainer->college ?? ''); ?>" placeholder="e.g., Villanova University">
                        </div>
                    </div>
                    
                    <div class="ptp-form-group ptp-form-full" style="margin-top: 20px;">
                        <label class="ptp-form-label">Specialties</label>
                        <?php
                        $all_specialties = array('Ball Control', 'Shooting', 'Passing', 'Dribbling', 'Defending', 'Goalkeeper', 'Speed Training', 'Game IQ', 'Fitness');
                        $trainer_specialties = $trainer->specialties ? json_decode($trainer->specialties, true) : array();
                        if (!is_array($trainer_specialties)) $trainer_specialties = array();
                        ?>
                        <div class="ptp-specialties-grid">
                            <?php foreach ($all_specialties as $spec): ?>
                                <label class="ptp-specialty-chip">
                                    <input type="checkbox" name="specialties[]" value="<?php echo esc_attr($spec); ?>" <?php checked(in_array($spec, $trainer_specialties)); ?>>
                                    <?php echo esc_html($spec); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div style="margin-top: 24px;">
                        <button type="submit" class="ptp-btn ptp-btn-primary">Save Changes</button>
                    </div>
                </div>
            </div>
            
            <div class="ptp-card">
                <div class="ptp-card-header">
                    <h2 class="ptp-card-title">Training Locations</h2>
                    <p class="ptp-card-subtitle">Add fields where you train players</p>
                </div>
                <div class="ptp-card-body">
                    <div class="ptp-form-group">
                        <label class="ptp-form-label">Primary City</label>
                        <input type="text" name="city" class="ptp-form-input" value="<?php echo esc_attr($trainer->city ?? ''); ?>" placeholder="e.g., Philadelphia">
                    </div>
                    <div class="ptp-form-group" style="margin-top: 16px;">
                        <label class="ptp-form-label">State</label>
                        <select name="state" class="ptp-form-select">
                            <option value="">Select state</option>
                            <?php
                            $states = array('PA' => 'Pennsylvania', 'NJ' => 'New Jersey', 'DE' => 'Delaware', 'MD' => 'Maryland', 'NY' => 'New York');
                            foreach ($states as $abbr => $name):
                            ?>
                                <option value="<?php echo $abbr; ?>" <?php selected($trainer->state ?? '', $abbr); ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ptp-form-group" style="margin-top: 16px;">
                        <label class="ptp-form-label">Training Locations (one per line)</label>
                        <textarea name="training_locations" class="ptp-form-textarea" placeholder="Higgins Soccer Complex&#10;Villanova Stadium&#10;Haverford Reserve"><?php 
                            $locs = $trainer->training_locations ? json_decode($trainer->training_locations, true) : array();
                            if (is_array($locs)) echo esc_textarea(implode("\n", $locs));
                        ?></textarea>
                        <p class="ptp-form-hint">These locations will be shown to parents when booking</p>
                    </div>
                    <div style="margin-top: 24px;">
                        <button type="submit" class="ptp-btn ptp-btn-primary">Save Locations</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Availability Panel -->
    <div class="ptp-panel <?php echo $active_tab === 'availability' ? 'active' : ''; ?>" id="panel-availability">
        <form id="availability-form">
            <input type="hidden" name="action" value="ptp_update_availability">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ptp_nonce'); ?>">
            
            <div class="ptp-card">
                <div class="ptp-card-header">
                    <h2 class="ptp-card-title">Weekly Availability</h2>
                    <p class="ptp-card-subtitle">Set your regular training hours</p>
                </div>
                <div class="ptp-card-body">
                    <div class="ptp-avail-grid">
                        <?php
                        $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
                        foreach ($days as $i => $day):
                            $slot = $availability_by_day[$i] ?? null;
                            $is_available = $slot && $slot->is_available;
                            $start = $slot->start_time ?? '09:00';
                            $end = $slot->end_time ?? '17:00';
                        ?>
                        <div class="ptp-avail-row" data-day="<?php echo $i; ?>">
                            <div class="ptp-avail-day"><?php echo $day; ?></div>
                            <label class="ptp-avail-toggle">
                                <input type="checkbox" name="available[<?php echo $i; ?>]" value="1" <?php checked($is_available); ?>>
                                <span class="ptp-avail-toggle-track"></span>
                            </label>
                            <div class="ptp-avail-times <?php echo !$is_available ? 'disabled' : ''; ?>">
                                <input type="time" name="start[<?php echo $i; ?>]" class="ptp-avail-time" value="<?php echo esc_attr($start); ?>" <?php echo !$is_available ? 'disabled' : ''; ?>>
                                <span style="color: var(--ptp-gray-400);">to</span>
                                <input type="time" name="end[<?php echo $i; ?>]" class="ptp-avail-time" value="<?php echo esc_attr($end); ?>" <?php echo !$is_available ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 24px;">
                        <button type="submit" class="ptp-btn ptp-btn-primary">Save Availability</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Payments Panel -->
    <div class="ptp-panel <?php echo $active_tab === 'payments' ? 'active' : ''; ?>" id="panel-payments">
        <div class="ptp-stripe-box">
            <h3>Stripe Connect</h3>
            <p>Connect your bank account to receive payments directly from training sessions. Payments are processed securely through Stripe and deposited to your account.</p>
            
            <?php if ($stripe_status === 'active'): ?>
                <div class="ptp-stripe-status">
                    <span class="ptp-stripe-status-dot green"></span>
                    Connected and Active
                </div>
                <button type="button" id="stripe-dashboard-btn" class="ptp-btn" style="background: rgba(255,255,255,0.2); color: #fff;">
                    Open Stripe Dashboard
                </button>
            <?php elseif ($stripe_status === 'pending'): ?>
                <div class="ptp-stripe-status">
                    <span class="ptp-stripe-status-dot yellow"></span>
                    Pending Verification
                </div>
                <button type="button" id="stripe-connect-btn" class="ptp-btn" style="background: #fff; color: var(--ptp-purple);">
                    Complete Setup
                </button>
            <?php else: ?>
                <div class="ptp-stripe-status">
                    <span class="ptp-stripe-status-dot red"></span>
                    Not Connected
                </div>
                <button type="button" id="stripe-connect-btn" class="ptp-btn" style="background: #fff; color: var(--ptp-purple);">
                    Connect with Stripe
                </button>
            <?php endif; ?>
        </div>
        
        <div class="ptp-card">
            <div class="ptp-card-header">
                <h2 class="ptp-card-title">Payment Information</h2>
            </div>
            <div class="ptp-card-body">
                <div class="ptp-alert ptp-alert-info">
                    Payments are processed through Stripe Connect. You can connect as an individual - no business registration required. Funds are deposited directly to your bank account within 2-3 business days after each session.
                </div>
                
                <div style="display: grid; gap: 16px; margin-top: 20px;">
                    <div style="padding: 16px; background: var(--ptp-gray-50); border-radius: 10px;">
                        <div style="font-size: 13px; color: var(--ptp-gray-500); margin-bottom: 4px;">Revenue Split</div>
                        <div style="font-size: 20px; font-weight: 700;">80% to you / 20% platform fee</div>
                    </div>
                    <div style="padding: 16px; background: var(--ptp-gray-50); border-radius: 10px;">
                        <div style="font-size: 13px; color: var(--ptp-gray-500); margin-bottom: 4px;">Payout Schedule</div>
                        <div style="font-size: 20px; font-weight: 700;">2-3 business days</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Notifications Panel -->
    <div class="ptp-panel <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>" id="panel-notifications">
        <form id="notifications-form">
            <input type="hidden" name="action" value="ptp_update_notifications">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ptp_nonce'); ?>">
            
            <div class="ptp-card">
                <div class="ptp-card-header">
                    <h2 class="ptp-card-title">Email Notifications</h2>
                </div>
                <div class="ptp-card-body">
                    <?php
                    $notif_prefs = get_user_meta($user->ID, 'ptp_notification_prefs', true) ?: array();
                    $notifications = array(
                        'booking_confirmation' => array('title' => 'Booking Confirmations', 'desc' => 'When a session is booked or confirmed'),
                        'session_reminder' => array('title' => 'Session Reminders', 'desc' => '24 hours before each training session'),
                        'new_message' => array('title' => 'New Messages', 'desc' => 'When you receive a new message'),
                        'payment_received' => array('title' => 'Payment Notifications', 'desc' => 'When you receive a payout'),
                    );
                    foreach ($notifications as $key => $notif):
                        $enabled = !isset($notif_prefs[$key]) || $notif_prefs[$key];
                    ?>
                    <div class="ptp-toggle-row">
                        <div class="ptp-toggle-info">
                            <div class="ptp-toggle-title"><?php echo $notif['title']; ?></div>
                            <div class="ptp-toggle-desc"><?php echo $notif['desc']; ?></div>
                        </div>
                        <div class="ptp-switch <?php echo $enabled ? 'active' : ''; ?>" data-input="notif_<?php echo $key; ?>" onclick="toggleSwitch(this)">
                            <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $enabled ? '1' : '0'; ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div style="margin-top: 24px;">
                        <button type="submit" class="ptp-btn ptp-btn-primary">Save Preferences</button>
                    </div>
                </div>
            </div>
            
            <div class="ptp-card">
                <div class="ptp-card-header">
                    <h2 class="ptp-card-title">SMS Notifications</h2>
                </div>
                <div class="ptp-card-body">
                    <?php
                    $sms_enabled = get_user_meta($user->ID, 'ptp_sms_enabled', true) !== '0';
                    ?>
                    <div class="ptp-toggle-row">
                        <div class="ptp-toggle-info">
                            <div class="ptp-toggle-title">Text Message Alerts</div>
                            <div class="ptp-toggle-desc">Receive session reminders and important updates via SMS</div>
                        </div>
                        <div class="ptp-switch <?php echo $sms_enabled ? 'active' : ''; ?>" data-input="sms_enabled" onclick="toggleSwitch(this)">
                            <input type="hidden" name="sms_enabled" value="<?php echo $sms_enabled ? '1' : '0'; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Security Panel -->
    <div class="ptp-panel <?php echo $active_tab === 'security' ? 'active' : ''; ?>" id="panel-security">
        <form id="password-form">
            <input type="hidden" name="action" value="ptp_change_password">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ptp_nonce'); ?>">
            
            <div class="ptp-card">
                <div class="ptp-card-header">
                    <h2 class="ptp-card-title">Change Password</h2>
                </div>
                <div class="ptp-card-body">
                    <div class="ptp-form-group mb">
                        <label class="ptp-form-label">Current Password</label>
                        <input type="password" name="current_password" class="ptp-form-input" required>
                    </div>
                    <div class="ptp-form-group mb">
                        <label class="ptp-form-label">New Password</label>
                        <input type="password" name="new_password" class="ptp-form-input" required minlength="8">
                        <p class="ptp-form-hint">Minimum 8 characters</p>
                    </div>
                    <div class="ptp-form-group mb">
                        <label class="ptp-form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="ptp-form-input" required>
                    </div>
                    <button type="submit" class="ptp-btn ptp-btn-primary">Update Password</button>
                </div>
            </div>
        </form>
        
        <div class="ptp-card">
            <div class="ptp-card-header">
                <h2 class="ptp-card-title">Account Actions</h2>
            </div>
            <div class="ptp-card-body">
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="ptp-btn ptp-btn-outline">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Log Out
                </a>
            </div>
        </div>
    </div>
    
</div>

</div>

<script>
// Toggle switch
function toggleSwitch(el) {
    el.classList.toggle('active');
    const input = el.querySelector('input');
    input.value = el.classList.contains('active') ? '1' : '0';
}

// Availability toggles
document.querySelectorAll('.ptp-avail-toggle input').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const row = this.closest('.ptp-avail-row');
        const times = row.querySelector('.ptp-avail-times');
        const inputs = times.querySelectorAll('input');
        
        if (this.checked) {
            times.classList.remove('disabled');
            inputs.forEach(i => i.disabled = false);
        } else {
            times.classList.add('disabled');
            inputs.forEach(i => i.disabled = true);
        }
    });
});

// Photo preview
document.getElementById('photo-input')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photo-preview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// Form handlers
function handleForm(formId, successMsg) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Saving...';
        
        const formData = new FormData(this);
        
        fetch(typeof ptp_ajax !== 'undefined' ? ptp_ajax.ajax_url : '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.innerHTML = 'Saved!';
                btn.style.background = '#10B981';
                setTimeout(() => {
                    btn.innerHTML = origText;
                    btn.style.background = '';
                    btn.disabled = false;
                }, 2000);
            } else {
                alert(data.data?.message || 'Error saving');
                btn.innerHTML = origText;
                btn.disabled = false;
            }
        })
        .catch(() => {
            alert('Error saving');
            btn.innerHTML = origText;
            btn.disabled = false;
        });
    });
}

handleForm('profile-form');
handleForm('trainer-profile-form');
handleForm('availability-form');
handleForm('notifications-form');
handleForm('password-form');

// Stripe Connect
document.getElementById('stripe-connect-btn')?.addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = 'Connecting...';
    
    const formData = new FormData();
    formData.append('action', 'ptp_create_stripe_connect_account');
    formData.append('nonce', '<?php echo wp_create_nonce('ptp_nonce'); ?>');
    
    fetch(typeof ptp_ajax !== 'undefined' ? ptp_ajax.ajax_url : '/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.data.url) {
            window.location.href = data.data.url;
        } else {
            alert(data.data?.message || 'Unable to connect. Please try again.');
            btn.disabled = false;
            btn.innerHTML = 'Connect with Stripe';
        }
    })
    .catch(() => {
        alert('Connection error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = 'Connect with Stripe';
    });
});

document.getElementById('stripe-dashboard-btn')?.addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = 'Loading...';
    
    const formData = new FormData();
    formData.append('action', 'ptp_get_stripe_dashboard_link');
    formData.append('nonce', '<?php echo wp_create_nonce('ptp_nonce'); ?>');
    
    fetch(typeof ptp_ajax !== 'undefined' ? ptp_ajax.ajax_url : '/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.data.url) {
            window.open(data.data.url, '_blank');
        } else {
            alert(data.data?.message || 'Unable to open dashboard');
        }
        btn.disabled = false;
        btn.innerHTML = 'Open Stripe Dashboard';
    })
    .catch(() => {
        alert('Error');
        btn.disabled = false;
        btn.innerHTML = 'Open Stripe Dashboard';
    });
});
</script>
