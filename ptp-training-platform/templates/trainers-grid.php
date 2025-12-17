<?php
/**
 * Template: Find Trainers v31.0.0
 * TeachMe.to-inspired clean UX
 * Optimized for mobile and desktop
 */
defined('ABSPATH') || exit;

$google_maps_api_key = get_option('ptp_google_maps_api_key', '');
$logo_url = class_exists('PTP_Images') ? PTP_Images::logo() : '';
$nonce = wp_create_nonce('ptp_nonce');

$specialties = array(
    'ball_control' => 'Ball Control',
    'dribbling' => 'Dribbling',
    'passing' => 'Passing',
    'shooting' => 'Shooting',
    'finishing' => 'Finishing',
    'defending' => 'Defending',
    'goalkeeping' => 'Goalkeeping',
    'speed_agility' => 'Speed & Agility',
    'tactical' => 'Tactical IQ',
    'fitness' => 'Fitness',
);
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
*{box-sizing:border-box;margin:0;padding:0}
html,body{overflow-x:hidden!important}

.ptp-find{font-family:'Inter',-apple-system,sans-serif;background:#fff;min-height:100vh;color:#111;-webkit-font-smoothing:antialiased}
.ptp-find *{box-sizing:border-box}

/* Top Navigation Bar */
.ptp-topbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:12px 20px;
    border-bottom:1px solid #E5E7EB;
    background:#fff;
    position:sticky;
    top:0;
    z-index:200;
}
.ptp-topbar-left{display:flex;align-items:center;gap:24px}
.ptp-topbar-logo img{height:32px;width:auto}
.ptp-topbar-selects{display:flex;align-items:center;gap:16px}
.ptp-select-group{display:flex;flex-direction:column}
.ptp-select-label{font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.5px}
.ptp-select-value{display:flex;align-items:center;gap:4px;font-size:16px;font-weight:700;color:#111;cursor:pointer}
.ptp-select-value svg{color:#FCB900}

/* Filter Bar */
.ptp-filterbar{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 20px;
    border-bottom:1px solid #E5E7EB;
    background:#fff;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
}
.ptp-filterbar::-webkit-scrollbar{display:none}
.ptp-filter-label{font-size:13px;font-weight:600;color:#6B7280;text-transform:uppercase;flex-shrink:0}
.ptp-dropdown{
    display:flex;
    align-items:center;
    gap:6px;
    padding:10px 14px;
    background:#F9FAFB;
    border:1px solid #E5E7EB;
    border-radius:8px;
    font-size:14px;
    font-weight:500;
    color:#374151;
    cursor:pointer;
    white-space:nowrap;
    flex-shrink:0;
    transition:all 0.15s;
}
.ptp-dropdown:hover{border-color:#9CA3AF;background:#F3F4F6}
.ptp-dropdown.active{border-color:#FCB900;background:#FFFBEB}
.ptp-dropdown svg{width:16px;height:16px;color:#9CA3AF}
.ptp-sort-group{display:flex;align-items:center;gap:8px;margin-left:auto;flex-shrink:0}
.ptp-sort-group span{font-size:13px;color:#6B7280}
.ptp-sort-select{
    padding:8px 12px;
    border:none;
    background:transparent;
    font-size:14px;
    font-weight:600;
    color:#111;
    cursor:pointer;
    font-family:inherit;
}

/* Main Layout */
.ptp-layout{display:flex;height:calc(100vh - 110px)}
.ptp-list{
    width:100%;
    overflow-y:auto;
    padding:0;
    background:#F9FAFB;
}
.ptp-map-panel{
    display:none;
    flex:1;
    position:sticky;
    top:110px;
    height:calc(100vh - 110px);
    background:#E5E7EB;
}
@media(min-width:1024px){
    .ptp-list{width:55%;min-width:500px;max-width:700px}
    .ptp-map-panel{display:block}
}
@media(min-width:1280px){
    .ptp-list{width:50%;max-width:800px}
}

/* Trainer Cards */
.ptp-cards{
    display:flex;
    flex-direction:column;
}
.ptp-trainer-card{
    display:flex;
    background:#fff;
    border-bottom:1px solid #E5E7EB;
    text-decoration:none;
    color:inherit;
    transition:background 0.15s;
    cursor:pointer;
}
.ptp-trainer-card:hover{background:#F9FAFB}

/* Card Image */
.ptp-card-image{
    position:relative;
    width:200px;
    min-width:200px;
    aspect-ratio:3/4;
    overflow:hidden;
    flex-shrink:0;
}
.ptp-card-image img{
    width:100%;
    height:100%;
    object-fit:cover;
}
.ptp-card-image::after{
    content:'';
    position:absolute;
    bottom:0;
    left:0;
    right:0;
    height:60%;
    background:linear-gradient(to top,rgba(0,0,0,0.7),transparent);
}
.ptp-card-name{
    position:absolute;
    bottom:12px;
    left:12px;
    right:50px;
    z-index:2;
}
.ptp-card-name h3{
    font-size:20px;
    font-weight:700;
    color:#fff;
    margin:0 0 4px;
    display:flex;
    align-items:center;
    gap:6px;
}
.ptp-card-name h3 .info-icon{
    width:16px;
    height:16px;
    background:rgba(255,255,255,0.3);
    border-radius:50%;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:10px;
    cursor:help;
}
.ptp-card-price{font-size:14px;color:rgba(255,255,255,0.9)}
.ptp-card-arrow{
    position:absolute;
    bottom:12px;
    right:12px;
    width:36px;
    height:36px;
    background:#FCB900;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:2;
    transition:transform 0.2s;
}
.ptp-trainer-card:hover .ptp-card-arrow{transform:scale(1.1)}
.ptp-card-score{
    position:absolute;
    bottom:12px;
    right:12px;
    background:linear-gradient(135deg,#10B981,#059669);
    color:#fff;
    padding:6px 10px;
    border-radius:8px;
    font-size:18px;
    font-weight:800;
    z-index:2;
    text-align:center;
    line-height:1;
}
.ptp-card-score span{display:block;font-size:9px;font-weight:600;margin-top:2px;opacity:0.9}

/* Card Details */
.ptp-card-details{
    flex:1;
    padding:16px 20px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    gap:10px;
}
.ptp-card-meta{
    display:flex;
    align-items:center;
    gap:16px;
    flex-wrap:wrap;
}
.ptp-card-sport{
    display:flex;
    align-items:center;
    gap:6px;
    font-size:14px;
    color:#6B7280;
}
.ptp-card-sport svg{color:#FCB900}
.ptp-card-rating{
    display:flex;
    align-items:center;
    gap:4px;
    font-size:14px;
    font-weight:600;
}
.ptp-card-rating svg{fill:#FCB900;width:16px;height:16px}
.ptp-card-rating .count{color:#6B7280;font-weight:400}
.ptp-card-location{
    display:flex;
    align-items:center;
    gap:6px;
    font-size:13px;
    color:#6B7280;
}
.ptp-card-location svg{width:14px;height:14px;color:#EF4444}
.ptp-card-location .label{color:#EF4444;font-weight:600}
.ptp-card-availability{
    display:flex;
    align-items:center;
    gap:6px;
    font-size:13px;
    color:#6B7280;
}
.ptp-card-availability svg{width:14px;height:14px;color:#10B981}
.ptp-card-availability .highlight{color:#10B981;font-weight:600}

/* Info Banner */
.ptp-info-banner{
    display:flex;
    align-items:center;
    gap:16px;
    padding:16px 20px;
    background:#fff;
    border-bottom:1px solid #E5E7EB;
}
.ptp-info-icon{
    width:48px;
    height:48px;
    background:#F0F9FF;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
}
.ptp-info-text{font-size:14px;color:#374151;line-height:1.5}
.ptp-info-text strong{color:#111}
.ptp-info-text a{color:#3B82F6;text-decoration:none;font-weight:600}

/* Mobile Toggle */
.ptp-mobile-toggle{
    display:none;
    position:fixed;
    bottom:20px;
    left:50%;
    transform:translateX(-50%);
    z-index:300;
    background:#0E0F11;
    color:#fff;
    padding:14px 24px;
    border-radius:40px;
    font-size:14px;
    font-weight:600;
    border:none;
    cursor:pointer;
    box-shadow:0 4px 20px rgba(0,0,0,0.3);
    gap:8px;
    align-items:center;
}
@media(max-width:1023px){
    .ptp-mobile-toggle{display:flex}
    .ptp-layout.map-active .ptp-list{display:none}
    .ptp-layout.map-active .ptp-map-panel{display:block;width:100%;height:calc(100vh - 110px)}
}

/* Loading & Empty States */
.ptp-loading,.ptp-empty{
    display:none;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:60px 20px;
    text-align:center;
    background:#fff;
    min-height:400px;
}
.ptp-loading.active,.ptp-empty.active{display:flex}
.ptp-spinner{
    width:48px;height:48px;
    border:3px solid #E5E7EB;
    border-top-color:#FCB900;
    border-radius:50%;
    animation:spin 0.8s linear infinite;
    margin-bottom:16px;
}
@keyframes spin{to{transform:rotate(360deg)}}
.ptp-empty-icon{
    width:80px;height:80px;
    background:#F3F4F6;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-bottom:20px;
}
.ptp-empty h3{font-size:20px;font-weight:700;margin:0 0 8px}
.ptp-empty p{font-size:15px;color:#6B7280;margin:0 0 20px}
.ptp-reset-btn{
    padding:12px 24px;
    background:#0E0F11;
    color:#fff;
    border:none;
    border-radius:8px;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
}

/* Mobile Card Layout */
@media(max-width:640px){
    .ptp-trainer-card{flex-direction:column}
    .ptp-card-image{width:100%;aspect-ratio:16/10;min-width:auto}
    .ptp-card-details{padding:16px}
    .ptp-topbar-selects{display:none}
    .ptp-filterbar{padding:12px 16px}
}

/* Dropdown Panels */
.ptp-dropdown-panel{
    display:none;
    position:absolute;
    top:100%;
    left:0;
    background:#fff;
    border:1px solid #E5E7EB;
    border-radius:12px;
    box-shadow:0 10px 40px rgba(0,0,0,0.15);
    z-index:300;
    min-width:280px;
    padding:16px;
}
.ptp-dropdown-panel.open{display:block}
.ptp-dropdown-options{display:flex;flex-direction:column;gap:4px}
.ptp-dropdown-option{
    padding:10px 12px;
    border-radius:8px;
    font-size:14px;
    cursor:pointer;
    transition:background 0.15s;
}
.ptp-dropdown-option:hover{background:#F3F4F6}
.ptp-dropdown-option.active{background:#FCB900;font-weight:600}

/* Location Input */
.ptp-location-input{
    display:flex;
    align-items:center;
    gap:8px;
    padding:12px;
    background:#F9FAFB;
    border:1px solid #E5E7EB;
    border-radius:8px;
    margin-bottom:12px;
}
.ptp-location-input input{
    flex:1;
    border:none;
    background:transparent;
    font-size:14px;
    outline:none;
    font-family:inherit;
}
.ptp-location-input button{
    padding:8px 12px;
    background:#FCB900;
    border:none;
    border-radius:6px;
    font-size:13px;
    font-weight:600;
    cursor:pointer;
}
</style>

<div class="ptp-find" id="ptp-trainer-search">
    <!-- Top Navigation -->
    <div class="ptp-topbar">
        <div class="ptp-topbar-left">
            <a href="<?php echo esc_url(home_url('/training/')); ?>" class="ptp-topbar-logo">
                <img src="<?php echo esc_url($logo_url); ?>" alt="PTP Soccer">
            </a>
            <div class="ptp-topbar-selects">
                <div class="ptp-select-group">
                    <span class="ptp-select-label">TRAIN</span>
                    <div class="ptp-select-value">
                        Soccer
                        <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><polygon points="12,16 4,8 20,8"/></svg>
                    </div>
                </div>
                <div class="ptp-select-group" style="position:relative">
                    <span class="ptp-select-label">NEAR</span>
                    <div class="ptp-select-value" id="location-trigger" onclick="toggleLocationPanel()">
                        <span id="location-display">Philadelphia</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <!-- Location Panel -->
                    <div class="ptp-dropdown-panel" id="location-panel">
                        <div class="ptp-location-input">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <input type="text" id="zip-input" placeholder="Enter ZIP code" maxlength="5">
                            <button onclick="applyLocation()">Go</button>
                        </div>
                        <button onclick="useMyLocation()" style="width:100%;padding:10px;background:#F3F4F6;border:none;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
                            Use my current location
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px">
            <a href="<?php echo esc_url(home_url('/apply/')); ?>" style="padding:10px 20px;background:#FCB900;color:#0E0F11;border-radius:8px;font-size:14px;font-weight:700;text-decoration:none">Become a Trainer</a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="ptp-filterbar">
        <span class="ptp-filter-label">Filter</span>

        <div class="ptp-dropdown" id="filter-level" onclick="toggleFilter('level')">
            Level
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        <div class="ptp-dropdown" id="filter-specialty" onclick="toggleFilter('specialty')">
            Specialty
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        <div class="ptp-dropdown" id="filter-price" onclick="toggleFilter('price')">
            Price
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        <div class="ptp-dropdown" id="filter-rating" onclick="toggleFilter('rating')">
            Rating
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        <div class="ptp-sort-group">
            <span>Sort</span>
            <select class="ptp-sort-select" id="sort-select" onchange="applySort()">
                <option value="recommended">Recommended</option>
                <option value="rating">Top Rated</option>
                <option value="price_low">Price: Low to High</option>
                <option value="price_high">Price: High to Low</option>
                <option value="distance">Nearest</option>
            </select>
        </div>
    </div>

    <!-- Main Layout -->
    <div class="ptp-layout" id="main-layout">
        <!-- Trainer List -->
        <div class="ptp-list" id="trainer-list">
            <!-- Info Banner -->
            <div class="ptp-info-banner">
                <div class="ptp-info-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div class="ptp-info-text">
                    <strong>All trainers are verified</strong> NCAA athletes or professional players with background checks completed.
                    <a href="<?php echo esc_url(home_url('/about/')); ?>">Learn more</a>
                </div>
            </div>

            <!-- Loading State -->
            <div class="ptp-loading" id="loading-state">
                <div class="ptp-spinner"></div>
                <p style="color:#6B7280">Finding trainers near you...</p>
            </div>

            <!-- Trainer Cards -->
            <div class="ptp-cards" id="trainer-cards"></div>

            <!-- Empty State -->
            <div class="ptp-empty" id="empty-state">
                <div class="ptp-empty-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </div>
                <h3>No trainers found</h3>
                <p>Try adjusting your filters or search in a different area</p>
                <button class="ptp-reset-btn" onclick="resetFilters()">Reset Filters</button>
            </div>

            <!-- Load More -->
            <div id="load-more-wrap" style="padding:20px;text-align:center;display:none">
                <button id="load-more-btn" onclick="loadMore()" style="padding:14px 32px;background:#0E0F11;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;width:100%;max-width:300px">Load More</button>
            </div>
        </div>

        <!-- Map Panel -->
        <div class="ptp-map-panel" id="map-panel">
            <div id="map" style="width:100%;height:100%"></div>
            <?php if (empty($google_maps_api_key)): ?>
            <div style="display:flex;align-items:center;justify-content:center;height:100%;background:#F3F4F6;color:#6B7280;text-align:center;padding:40px">
                <div>
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 16px;display:block;opacity:0.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <p>Map requires Google Maps API key</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile Map Toggle -->
    <button class="ptp-mobile-toggle" id="mobile-toggle" onclick="toggleMobileView()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <span>Map</span>
    </button>
</div>

<!-- Filter Dropdown Panels (positioned absolutely) -->
<div id="filter-panels" style="display:none">
    <!-- Level Panel -->
    <div class="ptp-dropdown-panel" id="level-panel" data-filter="level">
        <div class="ptp-dropdown-options">
            <div class="ptp-dropdown-option active" data-value="">All Levels</div>
            <div class="ptp-dropdown-option" data-value="pro">Professional</div>
            <div class="ptp-dropdown-option" data-value="college_d1">NCAA Division 1</div>
            <div class="ptp-dropdown-option" data-value="college_d2">NCAA Division 2</div>
            <div class="ptp-dropdown-option" data-value="college_d3">NCAA Division 3</div>
            <div class="ptp-dropdown-option" data-value="academy">Academy</div>
        </div>
    </div>

    <!-- Specialty Panel -->
    <div class="ptp-dropdown-panel" id="specialty-panel" data-filter="specialty">
        <div class="ptp-dropdown-options">
            <div class="ptp-dropdown-option active" data-value="">All Specialties</div>
            <?php foreach ($specialties as $key => $label): ?>
            <div class="ptp-dropdown-option" data-value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Price Panel -->
    <div class="ptp-dropdown-panel" id="price-panel" data-filter="price">
        <div class="ptp-dropdown-options">
            <div class="ptp-dropdown-option active" data-value="">Any Price</div>
            <div class="ptp-dropdown-option" data-value="0-50">Under $50/hr</div>
            <div class="ptp-dropdown-option" data-value="50-75">$50 - $75/hr</div>
            <div class="ptp-dropdown-option" data-value="75-100">$75 - $100/hr</div>
            <div class="ptp-dropdown-option" data-value="100-999">$100+/hr</div>
        </div>
    </div>

    <!-- Rating Panel -->
    <div class="ptp-dropdown-panel" id="rating-panel" data-filter="rating">
        <div class="ptp-dropdown-options">
            <div class="ptp-dropdown-option active" data-value="">Any Rating</div>
            <div class="ptp-dropdown-option" data-value="4.5">4.5+ ★</div>
            <div class="ptp-dropdown-option" data-value="4">4.0+ ★</div>
            <div class="ptp-dropdown-option" data-value="3">3.0+ ★</div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var config = {
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo $nonce; ?>',
        mapsApiKey: '<?php echo esc_js($google_maps_api_key); ?>',
        homeUrl: '<?php echo home_url('/trainer/'); ?>',
        perPage: 10
    };

    var state = {
        trainers: [],
        page: 1,
        totalPages: 1,
        total: 0,
        loading: false,
        filters: {
            zip: '',
            radius: 25,
            level: '',
            specialty: '',
            min_price: 0,
            max_price: 999,
            min_rating: 0,
            sort: 'recommended'
        },
        mapView: false
    };

    var map = null;
    var markers = [];
    var infoWindow = null;

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        setupFilterPanels();
        loadTrainers();
    });

    // Toggle location panel
    window.toggleLocationPanel = function() {
        var panel = document.getElementById('location-panel');
        panel.classList.toggle('open');
    };

    // Apply location
    window.applyLocation = function() {
        var zip = document.getElementById('zip-input').value.trim();
        if (zip && zip.length === 5) {
            state.filters.zip = zip;
            document.getElementById('location-display').textContent = 'ZIP ' + zip;
            document.getElementById('location-panel').classList.remove('open');
            state.page = 1;
            loadTrainers();
        }
    };

    // Use my location
    window.useMyLocation = function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                document.getElementById('location-display').textContent = 'Near me';
                document.getElementById('location-panel').classList.remove('open');
                state.userLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                state.page = 1;
                loadTrainers();
            }, function() {
                alert('Unable to get your location');
            });
        }
    };

    // Setup filter panels
    function setupFilterPanels() {
        document.querySelectorAll('.ptp-dropdown-option').forEach(function(opt) {
            opt.addEventListener('click', function() {
                var panel = this.closest('.ptp-dropdown-panel');
                var filterType = panel.dataset.filter;
                var value = this.dataset.value;

                // Update active state
                panel.querySelectorAll('.ptp-dropdown-option').forEach(function(o) {
                    o.classList.remove('active');
                });
                this.classList.add('active');

                // Update filter state
                if (filterType === 'level') state.filters.level = value;
                else if (filterType === 'specialty') state.filters.specialty = value;
                else if (filterType === 'price') {
                    if (value) {
                        var parts = value.split('-');
                        state.filters.min_price = parseInt(parts[0]);
                        state.filters.max_price = parseInt(parts[1]);
                    } else {
                        state.filters.min_price = 0;
                        state.filters.max_price = 999;
                    }
                }
                else if (filterType === 'rating') state.filters.min_rating = value ? parseFloat(value) : 0;

                // Update button text
                var btn = document.getElementById('filter-' + filterType);
                if (btn) {
                    btn.classList.toggle('active', value !== '');
                }

                // Close panel and reload
                closeAllPanels();
                state.page = 1;
                loadTrainers();
            });
        });

        // Close panels when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.ptp-dropdown') && !e.target.closest('.ptp-dropdown-panel') && !e.target.closest('.ptp-select-value')) {
                closeAllPanels();
            }
        });
    }

    // Toggle filter dropdown
    window.toggleFilter = function(type) {
        var panel = document.getElementById(type + '-panel');
        var btn = document.getElementById('filter-' + type);
        var isOpen = panel.classList.contains('open');

        closeAllPanels();

        if (!isOpen) {
            var rect = btn.getBoundingClientRect();
            panel.style.position = 'fixed';
            panel.style.top = (rect.bottom + 8) + 'px';
            panel.style.left = rect.left + 'px';
            panel.style.display = 'block';
            panel.classList.add('open');
        }
    };

    function closeAllPanels() {
        document.querySelectorAll('.ptp-dropdown-panel').forEach(function(p) {
            p.classList.remove('open');
            p.style.display = 'none';
        });
    }

    // Apply sort
    window.applySort = function() {
        state.filters.sort = document.getElementById('sort-select').value;
        state.page = 1;
        loadTrainers();
    };

    // Reset filters
    window.resetFilters = function() {
        state.filters = {
            zip: '',
            radius: 25,
            level: '',
            specialty: '',
            min_price: 0,
            max_price: 999,
            min_rating: 0,
            sort: 'recommended'
        };
        document.getElementById('sort-select').value = 'recommended';
        document.querySelectorAll('.ptp-dropdown').forEach(function(d) { d.classList.remove('active'); });
        document.querySelectorAll('.ptp-dropdown-option').forEach(function(o, i) {
            o.classList.toggle('active', o.dataset.value === '');
        });
        state.page = 1;
        loadTrainers();
    };

    // Load more
    window.loadMore = function() {
        if (!state.loading && state.page < state.totalPages) {
            state.page++;
            loadTrainers(true);
        }
    };

    // Toggle mobile view
    window.toggleMobileView = function() {
        var layout = document.getElementById('main-layout');
        var btn = document.getElementById('mobile-toggle');
        state.mapView = !state.mapView;
        layout.classList.toggle('map-active', state.mapView);
        btn.querySelector('span').textContent = state.mapView ? 'List' : 'Map';

        if (state.mapView && map) {
            setTimeout(function() {
                google.maps.event.trigger(map, 'resize');
                updateMap();
            }, 100);
        }
    };

    // Load trainers
    function loadTrainers(append) {
        if (state.loading) return;
        state.loading = true;

        var loadingEl = document.getElementById('loading-state');
        var emptyEl = document.getElementById('empty-state');
        var cardsEl = document.getElementById('trainer-cards');
        var loadMoreWrap = document.getElementById('load-more-wrap');

        if (!append) {
            cardsEl.innerHTML = '';
            loadingEl.classList.add('active');
            emptyEl.classList.remove('active');
        }

        var formData = new FormData();
        formData.append('action', 'ptp_get_trainers');
        formData.append('nonce', config.nonce);
        formData.append('zip', state.filters.zip);
        formData.append('radius', state.filters.radius);
        formData.append('playing_level', state.filters.level);
        formData.append('specialty', state.filters.specialty);
        formData.append('min_price', state.filters.min_price);
        formData.append('max_price', state.filters.max_price);
        formData.append('min_rating', state.filters.min_rating);
        formData.append('sort', state.filters.sort);
        formData.append('page', state.page);
        formData.append('per_page', config.perPage);

        if (state.userLocation) {
            formData.append('lat', state.userLocation.lat);
            formData.append('lng', state.userLocation.lng);
        }

        fetch(config.ajaxUrl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                state.loading = false;
                loadingEl.classList.remove('active');

                if (data.success) {
                    state.trainers = append ? state.trainers.concat(data.data.trainers) : data.data.trainers;
                    state.total = data.data.total;
                    state.totalPages = data.data.total_pages;

                    renderTrainers(data.data.trainers, append);

                    loadMoreWrap.style.display = state.page < state.totalPages ? 'block' : 'none';

                    if (config.mapsApiKey && map) {
                        updateMap();
                    }
                } else {
                    emptyEl.classList.add('active');
                }
            })
            .catch(function(err) {
                state.loading = false;
                loadingEl.classList.remove('active');
                emptyEl.classList.add('active');
                console.error(err);
            });
    }

    // Render trainers
    function renderTrainers(trainers, append) {
        var container = document.getElementById('trainer-cards');
        var emptyEl = document.getElementById('empty-state');

        if (!trainers.length && !append) {
            emptyEl.classList.add('active');
            return;
        }

        trainers.forEach(function(t) {
            var card = document.createElement('a');
            card.href = t.profile_url;
            card.className = 'ptp-trainer-card';

            var photo = t.photo || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(t.name) + '&size=400&background=FCB900&color=0E0F11';
            var rating = t.reviews > 0 ? '<div class="ptp-card-rating"><svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> ' + t.rating + ' <span class="count">(' + t.reviews + ')</span></div>' : '<div class="ptp-card-rating" style="color:#8B5CF6;font-weight:600">New</div>';
            var distance = t.distance !== null ? '<div class="ptp-card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg><span class="label">Nearby</span> • ' + esc(t.city || 'Philadelphia') + ' • ' + t.distance + ' mi</div>' : '<div class="ptp-card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>' + esc(t.city || 'Philadelphia Area') + '</div>';

            // Generate a score for trainers with reviews
            var scoreHtml = '';
            if (t.reviews >= 3 && t.rating >= 4.5) {
                var score = Math.min(99, Math.floor(85 + (t.rating * 2) + Math.min(t.reviews, 10)));
                scoreHtml = '<div class="ptp-card-score">' + score + '<span>Score</span></div>';
            } else {
                scoreHtml = '<div class="ptp-card-arrow"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0E0F11" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>';
            }

            card.innerHTML =
                '<div class="ptp-card-image">' +
                    '<img src="' + esc(photo) + '" alt="' + esc(t.name) + '" loading="lazy">' +
                    '<div class="ptp-card-name">' +
                        '<h3>' + esc(t.name) + ' <span class="info-icon">i</span></h3>' +
                        '<div class="ptp-card-price">$' + t.rate + ' per session</div>' +
                    '</div>' +
                    scoreHtml +
                '</div>' +
                '<div class="ptp-card-details">' +
                    '<div class="ptp-card-meta">' +
                        '<div class="ptp-card-sport"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg> Soccer</div>' +
                        rating +
                    '</div>' +
                    distance +
                    '<div class="ptp-card-availability"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><span class="highlight">Great availability</span> • This week</div>' +
                '</div>';

            container.appendChild(card);
        });
    }

    function esc(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Map functions
    window.initMap = function() {
        if (!config.mapsApiKey) return;

        map = new google.maps.Map(document.getElementById('map'), {
            zoom: 10,
            center: { lat: 39.9526, lng: -75.1652 },
            styles: [
                { featureType: 'poi', stylers: [{ visibility: 'off' }] },
                { featureType: 'transit', stylers: [{ visibility: 'off' }] }
            ],
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false
        });

        infoWindow = new google.maps.InfoWindow();

        if (state.trainers.length) updateMap();
    };

    function updateMap() {
        if (!map) return;

        markers.forEach(function(m) { m.setMap(null); });
        markers = [];

        var bounds = new google.maps.LatLngBounds();
        var hasMarkers = false;

        state.trainers.forEach(function(t) {
            if (t.lat && t.lng) {
                hasMarkers = true;
                var pos = { lat: t.lat, lng: t.lng };
                bounds.extend(pos);

                var marker = new google.maps.Marker({
                    position: pos,
                    map: map,
                    title: t.name,
                    icon: {
                        url: 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36"><circle cx="18" cy="18" r="16" fill="' + (t.featured ? '#8B5CF6' : '#FCB900') + '" stroke="#0E0F11" stroke-width="2"/><text x="18" y="23" text-anchor="middle" fill="#0E0F11" font-size="14" font-weight="bold" font-family="sans-serif">⚽</text></svg>'),
                        scaledSize: new google.maps.Size(36, 36)
                    }
                });

                marker.addListener('click', function() {
                    infoWindow.setContent(
                        '<div style="font-family:Inter,sans-serif;padding:8px;min-width:200px">' +
                        '<div style="font-weight:700;font-size:16px;margin-bottom:4px">' + esc(t.name) + '</div>' +
                        '<div style="color:#6B7280;font-size:14px;margin-bottom:8px">$' + t.rate + '/session</div>' +
                        '<a href="' + t.profile_url + '" style="display:block;background:#FCB900;color:#0E0F11;padding:10px;border-radius:8px;text-align:center;font-weight:600;text-decoration:none">View Profile</a>' +
                        '</div>'
                    );
                    infoWindow.open(map, marker);
                });

                markers.push(marker);
            }
        });

        if (hasMarkers) {
            map.fitBounds(bounds);
            var listener = google.maps.event.addListener(map, 'idle', function() {
                if (map.getZoom() > 14) map.setZoom(14);
                google.maps.event.removeListener(listener);
            });
        }
    }
})();
</script>

<?php if (!empty($google_maps_api_key)): ?>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr($google_maps_api_key); ?>&callback=initMap"></script>
<?php endif; ?>
