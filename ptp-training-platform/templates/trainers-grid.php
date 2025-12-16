<?php
/**
 * Template: Find Trainers v30.0.0
 * Enhanced search & discovery with comprehensive filters
 * Features: Location/radius search, specialty filtering, price range, rating, availability
 */
defined('ABSPATH') || exit;

$google_maps_api_key = get_option('ptp_google_maps_api_key', '');
$logo_url = class_exists('PTP_Images') ? PTP_Images::logo() : '';

// Get specialties list
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
    'fitness' => 'Fitness & Conditioning',
    'mental' => 'Mental Training',
    '1v1' => '1v1 Training',
);

$level_labels = array(
    'pro' => 'PRO',
    'college_d1' => 'D1',
    'college_d2' => 'D2',
    'college_d3' => 'D3',
    'academy' => 'ACADEMY',
    'semi_pro' => 'SEMI-PRO'
);

// Nonce for AJAX
$nonce = wp_create_nonce('ptp_nonce');
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
*{box-sizing:border-box;margin:0;padding:0}html,body{overflow-x:hidden!important;max-width:100vw}
.ptp-find{font-family:'Inter',-apple-system,sans-serif;background:#fff;min-height:100vh;color:#111;-webkit-font-smoothing:antialiased}
.ptp-find *{box-sizing:border-box}.ptp-find img{max-width:100%;height:auto}

/* Layout */
.ptp-f-layout{display:flex;min-height:calc(100vh - 200px)}
.ptp-f-panel{width:100%;overflow-y:auto;padding:20px;background:#fff}
.ptp-f-map{display:none;flex:1;min-height:400px;background:#E5E7EB;position:sticky;top:0;height:calc(100vh - 200px)}

@media(min-width:1024px){
    .ptp-f-panel{width:650px;min-width:650px;border-right:1px solid #E5E7EB;height:calc(100vh - 200px)}
    .ptp-f-map{display:block}
}
@media(min-width:1280px){.ptp-f-panel{width:750px;min-width:750px}}

/* Cards Grid */
.ptp-f-grid{display:grid;grid-template-columns:1fr;gap:20px}
@media(min-width:500px){.ptp-f-grid{grid-template-columns:repeat(2,1fr)}}

.ptp-f-card{display:block;text-decoration:none;color:inherit;border-radius:16px;overflow:hidden;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.06);transition:all 0.2s;border:1px solid #E5E7EB}
.ptp-f-card:hover{box-shadow:0 12px 32px rgba(0,0,0,0.12);transform:translateY(-4px);border-color:#FCB900}

/* Filter Header */
.ptp-filter-header{border-bottom:1px solid #E5E7EB;background:#fff;padding:16px 20px;position:sticky;top:0;z-index:100}
.ptp-filter-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.ptp-filter-logo{display:none}
@media(max-width:768px){.ptp-filter-logo{display:block}}

/* Quick Filters (Pills) */
.ptp-quick-filters{display:flex;gap:8px;flex-wrap:wrap;flex:1}
.ptp-filter-pill{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border:1px solid #E5E7EB;border-radius:24px;font-size:14px;font-weight:500;background:#fff;color:#374151;cursor:pointer;font-family:inherit;transition:all 0.15s}
.ptp-filter-pill:hover{border-color:#9CA3AF;background:#F9FAFB}
.ptp-filter-pill.active{background:#111;color:#fff;border-color:#111}
.ptp-filter-pill svg{width:16px;height:16px}

/* Search Bar Section */
.ptp-search-section{background:#0E0F11;padding:20px}
.ptp-search-container{max-width:1200px;margin:0 auto}
.ptp-search-row{display:flex;gap:12px;flex-wrap:wrap}
.ptp-search-input{flex:1;min-width:200px;display:flex;align-items:center;gap:12px;padding:14px 18px;background:#fff;border-radius:12px}
.ptp-search-input input{border:none;background:transparent;font-size:16px;width:100%;outline:none;font-family:inherit}
.ptp-search-input svg{flex-shrink:0;color:#9CA3AF}

/* Location Search */
.ptp-location-search{display:flex;align-items:center;gap:8px;padding:14px 18px;background:#fff;border-radius:12px;min-width:280px}
.ptp-location-search input{border:none;background:transparent;font-size:16px;width:120px;outline:none;font-family:inherit}
.ptp-location-search select{border:none;background:transparent;font-size:14px;outline:none;font-family:inherit;cursor:pointer;color:#374151;padding:0 4px}

/* Advanced Filters Panel */
.ptp-advanced-filters{background:#F9FAFB;border-bottom:1px solid #E5E7EB;padding:0;max-height:0;overflow:hidden;transition:all 0.3s ease}
.ptp-advanced-filters.open{padding:20px;max-height:500px}
.ptp-filters-grid{display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:20px;max-width:1200px;margin:0 auto}
.ptp-filter-group{display:flex;flex-direction:column;gap:8px}
.ptp-filter-group label{font-size:13px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:0.5px}
.ptp-filter-group select,.ptp-filter-group input[type="date"]{padding:12px 14px;border:1px solid #E5E7EB;border-radius:8px;font-size:14px;background:#fff;color:#111;font-family:inherit;cursor:pointer}
.ptp-filter-group select:focus,.ptp-filter-group input[type="date"]:focus{border-color:#FCB900;outline:none;box-shadow:0 0 0 3px rgba(252,185,0,0.1)}

/* Price Range Slider */
.ptp-price-range{display:flex;flex-direction:column;gap:8px}
.ptp-price-inputs{display:flex;align-items:center;gap:8px}
.ptp-price-inputs input{width:80px;padding:10px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:14px;text-align:center}
.ptp-price-inputs span{color:#6B7280}

/* Rating Stars Filter */
.ptp-rating-filter{display:flex;gap:4px}
.ptp-rating-star{width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;border-radius:6px;transition:all 0.15s;border:1px solid transparent}
.ptp-rating-star:hover{background:#FEF3C7}
.ptp-rating-star.active{background:#FCB900;border-color:#FCB900}
.ptp-rating-star svg{width:20px;height:20px;fill:#D1D5DB}
.ptp-rating-star.active svg,.ptp-rating-star.highlighted svg{fill:#FCB900}
.ptp-rating-star.active svg{fill:#fff}

/* Specialty Chips */
.ptp-specialty-chips{display:flex;flex-wrap:wrap;gap:8px}
.ptp-specialty-chip{padding:8px 14px;border:1px solid #E5E7EB;border-radius:20px;font-size:13px;cursor:pointer;transition:all 0.15s;background:#fff}
.ptp-specialty-chip:hover{border-color:#9CA3AF}
.ptp-specialty-chip.active{background:#FCB900;border-color:#FCB900;color:#0E0F11;font-weight:600}

/* Active Filters Bar */
.ptp-active-filters{display:none;padding:12px 20px;background:#FEF3C7;border-bottom:1px solid #FCD34D}
.ptp-active-filters.has-filters{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.ptp-active-filter-tag{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#fff;border-radius:20px;font-size:13px;font-weight:500;border:1px solid #FCD34D}
.ptp-active-filter-tag button{background:none;border:none;cursor:pointer;padding:0;display:flex;color:#9CA3AF}
.ptp-active-filter-tag button:hover{color:#EF4444}
.ptp-clear-filters{background:none;border:none;color:#92400E;font-size:13px;font-weight:600;cursor:pointer;text-decoration:underline}

/* Results Header */
.ptp-results-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.ptp-results-count{font-size:14px;color:#6B7280}
.ptp-results-count strong{color:#111}
.ptp-sort-select{padding:10px 14px;border:1px solid #E5E7EB;border-radius:8px;font-size:14px;background:#fff;color:#111;cursor:pointer;font-family:inherit}

/* Loading State */
.ptp-loading{display:none;text-align:center;padding:60px 20px}
.ptp-loading.active{display:block}
.ptp-loading-spinner{width:48px;height:48px;border:3px solid #E5E7EB;border-top-color:#FCB900;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 16px}
@keyframes spin{to{transform:rotate(360deg)}}

/* Empty State */
.ptp-empty-state{display:none;text-align:center;padding:60px 20px}
.ptp-empty-state.active{display:block}
.ptp-empty-icon{width:80px;height:80px;background:#F3F4F6;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px}

/* Pagination */
.ptp-pagination{display:flex;justify-content:center;align-items:center;gap:8px;margin-top:32px;padding-top:24px;border-top:1px solid #E5E7EB}
.ptp-page-btn{padding:10px 16px;border:1px solid #E5E7EB;border-radius:8px;font-size:14px;background:#fff;cursor:pointer;font-family:inherit;transition:all 0.15s}
.ptp-page-btn:hover:not(:disabled){border-color:#FCB900;background:#FEF3C7}
.ptp-page-btn:disabled{opacity:0.5;cursor:not-allowed}
.ptp-page-btn.active{background:#FCB900;border-color:#FCB900;font-weight:600}
.ptp-load-more{width:100%;padding:14px 24px;background:#111;color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;font-family:inherit;transition:all 0.15s}
.ptp-load-more:hover{background:#333}
.ptp-load-more:disabled{opacity:0.6;cursor:not-allowed}

/* Info Banner */
.ptp-info-banner{display:flex;align-items:center;gap:12px;padding:16px;background:#F0F9FF;border:1px solid #BAE6FD;border-radius:12px;margin-top:24px}
.ptp-info-banner-icon{width:44px;height:44px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}

/* Mobile Optimizations */
@media(max-width:768px){
    .ptp-filter-row{flex-direction:column;align-items:stretch;gap:12px}
    .ptp-quick-filters{justify-content:flex-start;overflow-x:auto;flex-wrap:nowrap;padding-bottom:4px;-webkit-overflow-scrolling:touch}
    .ptp-filter-pill{white-space:nowrap;flex-shrink:0}
    .ptp-search-row{flex-direction:column}
    .ptp-location-search{width:100%}
    .ptp-filters-grid{grid-template-columns:1fr 1fr}
    .ptp-advanced-filters.open{max-height:800px}
}

/* Distance Badge */
.ptp-distance-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#E0E7FF;color:#3730A3;border-radius:12px;font-size:12px;font-weight:600}
</style>

<div class="ptp-find" id="ptp-trainer-search">
    <!-- Filter Header -->
    <div class="ptp-filter-header">
        <div class="ptp-filter-row" style="max-width:1400px;margin:0 auto">
            <!-- Mobile Logo -->
            <a href="<?php echo esc_url(home_url('/training/')); ?>" class="ptp-filter-logo">
                <img src="<?php echo esc_url($logo_url); ?>" alt="PTP Soccer" style="height:36px;max-width:140px;width:auto;object-fit:contain">
            </a>

            <!-- Quick Filter Pills -->
            <div class="ptp-quick-filters">
                <button type="button" class="ptp-filter-pill active" data-filter="all">All Trainers</button>
                <button type="button" class="ptp-filter-pill" data-filter="featured">
                    <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    Featured
                </button>
                <button type="button" class="ptp-filter-pill" data-filter="pro">Pro Players</button>
                <button type="button" class="ptp-filter-pill" data-filter="d1">D1 Athletes</button>
                <button type="button" class="ptp-filter-pill" data-filter="top_rated">Top Rated</button>
            </div>

            <!-- More Filters Toggle -->
            <button type="button" class="ptp-filter-pill" id="ptp-toggle-filters">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/><circle cx="8" cy="6" r="2" fill="currentColor"/><circle cx="16" cy="12" r="2" fill="currentColor"/><circle cx="10" cy="18" r="2" fill="currentColor"/></svg>
                Filters
            </button>
        </div>
    </div>

    <!-- Search Bar Section -->
    <div class="ptp-search-section">
        <div class="ptp-search-container">
            <div class="ptp-search-row">
                <!-- Text Search -->
                <div class="ptp-search-input">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" id="ptp-search-text" placeholder="Search trainers by name, skill, or location...">
                </div>

                <!-- Location Search -->
                <div class="ptp-location-search">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <input type="text" id="ptp-search-zip" placeholder="ZIP code" maxlength="5" pattern="[0-9]*">
                    <span style="color:#D1D5DB">|</span>
                    <select id="ptp-search-radius">
                        <option value="10">10 mi</option>
                        <option value="25" selected>25 mi</option>
                        <option value="50">50 mi</option>
                        <option value="100">100 mi</option>
                    </select>
                    <button type="button" id="ptp-use-location" title="Use my location" style="background:none;border:none;cursor:pointer;padding:4px">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/></svg>
                    </button>
                </div>

                <!-- Search Button -->
                <button type="button" id="ptp-search-btn" style="padding:14px 28px;background:#FCB900;color:#0E0F11;border:none;border-radius:12px;font-size:16px;font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:8px">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    Search
                </button>
            </div>
        </div>
    </div>

    <!-- Advanced Filters Panel -->
    <div class="ptp-advanced-filters" id="ptp-advanced-filters">
        <div class="ptp-filters-grid">
            <!-- Specialty Filter -->
            <div class="ptp-filter-group">
                <label>Specialty</label>
                <select id="ptp-filter-specialty">
                    <option value="">All Specialties</option>
                    <?php foreach ($specialties as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Playing Level Filter -->
            <div class="ptp-filter-group">
                <label>Experience Level</label>
                <select id="ptp-filter-level">
                    <option value="">All Levels</option>
                    <option value="pro">Professional</option>
                    <option value="college_d1">NCAA Division 1</option>
                    <option value="college_d2">NCAA Division 2</option>
                    <option value="college_d3">NCAA Division 3</option>
                    <option value="semi_pro">Semi-Professional</option>
                    <option value="academy">Academy</option>
                </select>
            </div>

            <!-- Price Range -->
            <div class="ptp-filter-group">
                <label>Price Range (per hour)</label>
                <div class="ptp-price-inputs">
                    <input type="number" id="ptp-filter-min-price" placeholder="$0" min="0" max="500" step="10" value="0">
                    <span>to</span>
                    <input type="number" id="ptp-filter-max-price" placeholder="$500" min="0" max="500" step="10" value="500">
                </div>
            </div>

            <!-- Minimum Rating -->
            <div class="ptp-filter-group">
                <label>Minimum Rating</label>
                <div class="ptp-rating-filter" id="ptp-rating-filter">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" class="ptp-rating-star" data-rating="<?php echo $i; ?>" title="<?php echo $i; ?>+ stars">
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </button>
                    <?php endfor; ?>
                    <span id="ptp-rating-label" style="margin-left:8px;font-size:13px;color:#6B7280">Any rating</span>
                </div>
            </div>

            <!-- Available Date -->
            <div class="ptp-filter-group">
                <label>Available On</label>
                <input type="date" id="ptp-filter-date" min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+90 days')); ?>">
            </div>

            <!-- Sort By -->
            <div class="ptp-filter-group">
                <label>Sort By</label>
                <select id="ptp-filter-sort">
                    <option value="featured">Featured First</option>
                    <option value="rating">Top Rated</option>
                    <option value="reviews">Most Reviews</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                    <option value="distance">Nearest</option>
                    <option value="newest">Newest</option>
                </select>
            </div>
        </div>

        <!-- Apply/Reset Buttons -->
        <div style="display:flex;gap:12px;justify-content:center;margin-top:20px;max-width:400px;margin-left:auto;margin-right:auto">
            <button type="button" id="ptp-reset-filters" style="flex:1;padding:12px 20px;background:#fff;border:1px solid #E5E7EB;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit">Reset Filters</button>
            <button type="button" id="ptp-apply-filters" style="flex:1;padding:12px 20px;background:#FCB900;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;color:#0E0F11">Apply Filters</button>
        </div>
    </div>

    <!-- Active Filters Bar -->
    <div class="ptp-active-filters" id="ptp-active-filters">
        <span style="font-size:13px;font-weight:600;color:#92400E">Active filters:</span>
        <div id="ptp-active-filter-tags"></div>
        <button type="button" class="ptp-clear-filters" id="ptp-clear-all-filters">Clear all</button>
    </div>

    <!-- Main Layout -->
    <div class="ptp-f-layout">
        <!-- Trainer List Panel -->
        <div class="ptp-f-panel" id="ptp-results-panel">
            <!-- Results Header -->
            <div class="ptp-results-header">
                <div>
                    <h1 style="font-size:24px;font-weight:700;margin:0 0 4px;color:#111">Find a Trainer</h1>
                    <p class="ptp-results-count" id="ptp-results-count">Loading trainers...</p>
                </div>
                <select class="ptp-sort-select" id="ptp-sort-mobile">
                    <option value="featured">Featured First</option>
                    <option value="rating">Top Rated</option>
                    <option value="reviews">Most Reviews</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                    <option value="distance">Nearest</option>
                </select>
            </div>

            <!-- Loading State -->
            <div class="ptp-loading" id="ptp-loading">
                <div class="ptp-loading-spinner"></div>
                <p style="color:#6B7280;font-size:15px">Finding trainers...</p>
            </div>

            <!-- Trainers Grid -->
            <div class="ptp-f-grid" id="ptp-trainers-grid"></div>

            <!-- Empty State -->
            <div class="ptp-empty-state" id="ptp-empty-state">
                <div class="ptp-empty-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </div>
                <h3 style="font-size:20px;font-weight:700;color:#111;margin:0 0 8px">No trainers found</h3>
                <p style="font-size:15px;color:#6B7280;margin:0 0 20px">Try adjusting your filters or search terms</p>
                <button type="button" onclick="ptpResetFilters()" style="padding:12px 24px;background:#111;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer">Reset Filters</button>
            </div>

            <!-- Load More / Pagination -->
            <div class="ptp-pagination" id="ptp-pagination" style="display:none">
                <button type="button" class="ptp-load-more" id="ptp-load-more">Load More Trainers</button>
            </div>

            <!-- Info Banner -->
            <div class="ptp-info-banner">
                <div class="ptp-info-banner-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0284C7" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                </div>
                <div style="font-size:14px;color:#0C4A6E;line-height:1.5">
                    <strong style="color:#0284C7">All trainers are background checked</strong> and verified NCAA or professional athletes.
                </div>
            </div>
        </div>

        <!-- Map Panel -->
        <div class="ptp-f-map" id="ptp-map-container">
            <div id="ptp-map" style="width:100%;height:100%"></div>
            <?php if (empty($google_maps_api_key)): ?>
            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#6B7280;text-align:center;padding:40px;position:absolute;top:0;left:0;right:0;bottom:0;background:#F3F4F6">
                <div>
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 16px;display:block;opacity:0.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <p style="margin:0;font-size:15px">Map requires Google Maps API key</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    // Configuration
    var config = {
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo $nonce; ?>',
        mapsApiKey: '<?php echo esc_js($google_maps_api_key); ?>',
        homeUrl: '<?php echo home_url('/trainer/'); ?>',
        perPage: 12
    };

    // State
    var state = {
        trainers: [],
        page: 1,
        totalPages: 1,
        total: 0,
        loading: false,
        filters: {
            search: '',
            zip: '',
            radius: 25,
            specialty: '',
            playing_level: '',
            min_price: 0,
            max_price: 500,
            min_rating: 0,
            available_date: '',
            featured_only: false,
            sort: 'featured'
        },
        userLocation: null
    };

    // DOM Elements
    var elements = {};

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        cacheElements();
        bindEvents();
        loadTrainers();
    });

    function cacheElements() {
        elements.grid = document.getElementById('ptp-trainers-grid');
        elements.loading = document.getElementById('ptp-loading');
        elements.empty = document.getElementById('ptp-empty-state');
        elements.resultsCount = document.getElementById('ptp-results-count');
        elements.pagination = document.getElementById('ptp-pagination');
        elements.loadMore = document.getElementById('ptp-load-more');
        elements.advancedFilters = document.getElementById('ptp-advanced-filters');
        elements.activeFilters = document.getElementById('ptp-active-filters');
        elements.activeFilterTags = document.getElementById('ptp-active-filter-tags');
        elements.searchText = document.getElementById('ptp-search-text');
        elements.searchZip = document.getElementById('ptp-search-zip');
        elements.searchRadius = document.getElementById('ptp-search-radius');
        elements.filterSpecialty = document.getElementById('ptp-filter-specialty');
        elements.filterLevel = document.getElementById('ptp-filter-level');
        elements.filterMinPrice = document.getElementById('ptp-filter-min-price');
        elements.filterMaxPrice = document.getElementById('ptp-filter-max-price');
        elements.filterDate = document.getElementById('ptp-filter-date');
        elements.filterSort = document.getElementById('ptp-filter-sort');
        elements.sortMobile = document.getElementById('ptp-sort-mobile');
        elements.ratingFilter = document.getElementById('ptp-rating-filter');
        elements.ratingLabel = document.getElementById('ptp-rating-label');
    }

    function bindEvents() {
        // Search button
        document.getElementById('ptp-search-btn').addEventListener('click', function() {
            state.page = 1;
            collectFilters();
            loadTrainers();
        });

        // Enter key in search inputs
        elements.searchText.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                state.page = 1;
                collectFilters();
                loadTrainers();
            }
        });

        elements.searchZip.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                state.page = 1;
                collectFilters();
                loadTrainers();
            }
        });

        // Quick filter pills
        document.querySelectorAll('.ptp-quick-filters .ptp-filter-pill').forEach(function(pill) {
            pill.addEventListener('click', function() {
                var filter = this.dataset.filter;
                document.querySelectorAll('.ptp-quick-filters .ptp-filter-pill').forEach(function(p) {
                    p.classList.remove('active');
                });
                this.classList.add('active');

                // Reset filters and apply quick filter
                resetFilterState();

                if (filter === 'featured') {
                    state.filters.featured_only = true;
                } else if (filter === 'pro') {
                    state.filters.playing_level = 'pro';
                } else if (filter === 'd1') {
                    state.filters.playing_level = 'college_d1';
                } else if (filter === 'top_rated') {
                    state.filters.sort = 'rating';
                    state.filters.min_rating = 4;
                }

                state.page = 1;
                loadTrainers();
            });
        });

        // Toggle advanced filters
        document.getElementById('ptp-toggle-filters').addEventListener('click', function() {
            elements.advancedFilters.classList.toggle('open');
            this.classList.toggle('active');
        });

        // Apply filters button
        document.getElementById('ptp-apply-filters').addEventListener('click', function() {
            state.page = 1;
            collectFilters();
            loadTrainers();
            elements.advancedFilters.classList.remove('open');
        });

        // Reset filters button
        document.getElementById('ptp-reset-filters').addEventListener('click', ptpResetFilters);

        // Clear all filters
        document.getElementById('ptp-clear-all-filters').addEventListener('click', ptpResetFilters);

        // Sort change (mobile)
        elements.sortMobile.addEventListener('change', function() {
            state.filters.sort = this.value;
            elements.filterSort.value = this.value;
            state.page = 1;
            loadTrainers();
        });

        // Sort change (advanced panel)
        elements.filterSort.addEventListener('change', function() {
            state.filters.sort = this.value;
            elements.sortMobile.value = this.value;
        });

        // Rating stars
        document.querySelectorAll('.ptp-rating-star').forEach(function(star) {
            star.addEventListener('click', function() {
                var rating = parseInt(this.dataset.rating);
                var currentRating = state.filters.min_rating;

                // Toggle off if clicking same rating
                if (currentRating === rating) {
                    state.filters.min_rating = 0;
                    updateRatingStars(0);
                } else {
                    state.filters.min_rating = rating;
                    updateRatingStars(rating);
                }
            });

            star.addEventListener('mouseenter', function() {
                var rating = parseInt(this.dataset.rating);
                highlightStars(rating);
            });

            star.addEventListener('mouseleave', function() {
                highlightStars(state.filters.min_rating);
            });
        });

        // Use my location button
        document.getElementById('ptp-use-location').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    state.userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    // Note: For proper ZIP reverse geocoding, would need Google API
                    // For now, just trigger a location-based search
                    elements.searchZip.placeholder = 'Using location';
                    state.page = 1;
                    collectFilters();
                    loadTrainers();
                }, function(error) {
                    alert('Unable to get your location. Please enter a ZIP code.');
                });
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        });

        // Load more button
        elements.loadMore.addEventListener('click', function() {
            if (!state.loading && state.page < state.totalPages) {
                state.page++;
                loadTrainers(true);
            }
        });

        // ZIP code input validation
        elements.searchZip.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    function collectFilters() {
        state.filters.search = elements.searchText.value.trim();
        state.filters.zip = elements.searchZip.value.trim();
        state.filters.radius = parseInt(elements.searchRadius.value) || 25;
        state.filters.specialty = elements.filterSpecialty.value;
        state.filters.playing_level = elements.filterLevel.value;
        state.filters.min_price = parseInt(elements.filterMinPrice.value) || 0;
        state.filters.max_price = parseInt(elements.filterMaxPrice.value) || 500;
        state.filters.available_date = elements.filterDate.value;
        state.filters.sort = elements.filterSort.value;

        // Sync mobile sort
        elements.sortMobile.value = state.filters.sort;
    }

    function resetFilterState() {
        state.filters = {
            search: '',
            zip: '',
            radius: 25,
            specialty: '',
            playing_level: '',
            min_price: 0,
            max_price: 500,
            min_rating: 0,
            available_date: '',
            featured_only: false,
            sort: 'featured'
        };
    }

    window.ptpResetFilters = function() {
        resetFilterState();

        // Reset UI
        elements.searchText.value = '';
        elements.searchZip.value = '';
        elements.searchRadius.value = '25';
        elements.filterSpecialty.value = '';
        elements.filterLevel.value = '';
        elements.filterMinPrice.value = '0';
        elements.filterMaxPrice.value = '500';
        elements.filterDate.value = '';
        elements.filterSort.value = 'featured';
        elements.sortMobile.value = 'featured';
        updateRatingStars(0);

        // Reset quick filter pills
        document.querySelectorAll('.ptp-quick-filters .ptp-filter-pill').forEach(function(p) {
            p.classList.remove('active');
        });
        document.querySelector('.ptp-quick-filters .ptp-filter-pill[data-filter="all"]').classList.add('active');

        state.page = 1;
        loadTrainers();
    };

    function updateRatingStars(rating) {
        document.querySelectorAll('.ptp-rating-star').forEach(function(star, index) {
            if (index < rating) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });

        if (rating > 0) {
            elements.ratingLabel.textContent = rating + '+ stars';
        } else {
            elements.ratingLabel.textContent = 'Any rating';
        }
    }

    function highlightStars(rating) {
        document.querySelectorAll('.ptp-rating-star').forEach(function(star, index) {
            if (index < rating) {
                star.classList.add('highlighted');
            } else {
                star.classList.remove('highlighted');
            }
        });
    }

    function loadTrainers(append) {
        if (state.loading) return;

        state.loading = true;

        if (!append) {
            elements.grid.innerHTML = '';
            elements.loading.classList.add('active');
            elements.empty.classList.remove('active');
        }

        elements.loadMore.disabled = true;
        elements.loadMore.textContent = 'Loading...';

        var formData = new FormData();
        formData.append('action', 'ptp_get_trainers');
        formData.append('nonce', config.nonce);
        formData.append('search', state.filters.search);
        formData.append('zip', state.filters.zip);
        formData.append('radius', state.filters.radius);
        formData.append('specialty', state.filters.specialty);
        formData.append('playing_level', state.filters.playing_level);
        formData.append('min_price', state.filters.min_price);
        formData.append('max_price', state.filters.max_price);
        formData.append('min_rating', state.filters.min_rating);
        formData.append('available_date', state.filters.available_date);
        formData.append('featured_only', state.filters.featured_only ? '1' : '');
        formData.append('sort', state.filters.sort);
        formData.append('page', state.page);
        formData.append('per_page', config.perPage);

        fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            state.loading = false;
            elements.loading.classList.remove('active');

            if (data.success) {
                if (append) {
                    state.trainers = state.trainers.concat(data.data.trainers);
                } else {
                    state.trainers = data.data.trainers;
                }

                state.total = data.data.total;
                state.totalPages = data.data.total_pages;

                renderTrainers(data.data.trainers, append);
                updateResultsCount();
                updateActiveFilters();
                updatePagination();

                if (config.mapsApiKey && typeof updateMap === 'function') {
                    updateMap(state.trainers, data.data.user_location);
                }
            } else {
                showError('Failed to load trainers. Please try again.');
            }
        })
        .catch(function(error) {
            state.loading = false;
            elements.loading.classList.remove('active');
            showError('Network error. Please check your connection.');
            console.error('Search error:', error);
        });
    }

    function renderTrainers(trainers, append) {
        if (!append) {
            elements.grid.innerHTML = '';
        }

        if (trainers.length === 0 && !append) {
            elements.empty.classList.add('active');
            return;
        }

        trainers.forEach(function(t) {
            var card = createTrainerCard(t);
            elements.grid.appendChild(card);
        });
    }

    function createTrainerCard(t) {
        var card = document.createElement('a');
        card.href = t.profile_url;
        card.className = 'ptp-f-card';

        var photo = t.photo || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(t.name) + '&size=400&background=FCB900&color=0E0F11';

        var levelBadge = t.level ? '<div style="position:absolute;top:12px;left:12px;padding:5px 12px;background:#FCB900;color:#0E0F11;font-size:11px;font-weight:700;border-radius:8px">' + escapeHtml(t.level) + '</div>' : '';
        var featuredBadge = t.featured ? '<div style="position:absolute;top:12px;right:12px;padding:5px 10px;background:#8B5CF6;color:#fff;font-size:10px;font-weight:700;border-radius:6px">FEATURED</div>' : '';
        var verifiedBadge = t.verified ? '<span style="width:18px;height:18px;background:#3B82F6;border-radius:50%;display:inline-flex;align-items:center;justify-content:center"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>' : '';
        var distanceBadge = t.distance !== null ? '<span class="ptp-distance-badge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>' + t.distance + ' mi</span>' : '';

        var ratingHtml = '';
        if (t.reviews > 0) {
            ratingHtml = '<span style="display:flex;align-items:center;gap:4px;font-size:14px;color:#111">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="#FCB900"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>' +
                t.rating + '<span style="color:#6B7280">(' + t.reviews + ')</span></span>';
        } else {
            ratingHtml = '<span style="font-size:13px;color:#8B5CF6;font-weight:600">New</span>';
        }

        var location = t.location || (t.city && t.state ? t.city + ', ' + t.state : 'Philadelphia Area');

        card.innerHTML =
            '<div style="position:relative;aspect-ratio:4/5;overflow:hidden;background:#f3f4f6">' +
                '<img src="' + escapeHtml(photo) + '" alt="' + escapeHtml(t.name) + '" style="width:100%;height:100%;object-fit:cover;transition:transform 0.3s" loading="lazy">' +
                '<div style="position:absolute;bottom:0;left:0;right:0;height:50%;background:linear-gradient(to top,rgba(0,0,0,0.7),transparent)"></div>' +
                levelBadge +
                featuredBadge +
                '<div style="position:absolute;bottom:16px;left:16px;right:60px;color:#fff">' +
                    '<div style="font-size:18px;font-weight:700;margin-bottom:4px;display:flex;align-items:center;gap:6px">' +
                        escapeHtml(t.name) + verifiedBadge +
                    '</div>' +
                    '<div style="font-size:14px;opacity:0.9">$' + t.rate + '/hour</div>' +
                '</div>' +
                '<div style="position:absolute;bottom:16px;right:16px;width:40px;height:40px;background:#FCB900;border-radius:50%;display:flex;align-items:center;justify-content:center">' +
                    '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0E0F11" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>' +
                '</div>' +
            '</div>' +
            '<div style="padding:14px 16px">' +
                '<div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">' +
                    '<span style="font-size:14px;font-weight:600;color:#111">⚽ Soccer</span>' +
                    ratingHtml +
                    distanceBadge +
                '</div>' +
                '<div style="display:flex;flex-wrap:wrap;gap:12px;font-size:13px;color:#6B7280">' +
                    '<span style="display:flex;align-items:center;gap:4px">' +
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>' +
                        escapeHtml(location) +
                    '</span>' +
                    '<span style="display:flex;align-items:center;gap:4px;color:#059669">' +
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>' +
                        'Available' +
                    '</span>' +
                '</div>' +
            '</div>';

        return card;
    }

    function updateResultsCount() {
        var text = state.total + ' trainer' + (state.total !== 1 ? 's' : '') + ' available';
        if (state.filters.zip) {
            text += ' within ' + state.filters.radius + ' miles';
        }
        elements.resultsCount.innerHTML = '<strong>' + state.total + '</strong> ' + text.substring(text.indexOf(' ') + 1);
    }

    function updateActiveFilters() {
        var tags = [];
        var f = state.filters;

        if (f.search) tags.push({ label: 'Search: ' + f.search, key: 'search' });
        if (f.zip) tags.push({ label: 'Location: ' + f.zip + ' (' + f.radius + ' mi)', key: 'location' });
        if (f.specialty) tags.push({ label: 'Specialty: ' + f.specialty, key: 'specialty' });
        if (f.playing_level) tags.push({ label: 'Level: ' + f.playing_level, key: 'playing_level' });
        if (f.min_price > 0 || f.max_price < 500) tags.push({ label: 'Price: $' + f.min_price + '-$' + f.max_price, key: 'price' });
        if (f.min_rating > 0) tags.push({ label: 'Rating: ' + f.min_rating + '+', key: 'rating' });
        if (f.available_date) tags.push({ label: 'Date: ' + f.available_date, key: 'date' });
        if (f.featured_only) tags.push({ label: 'Featured only', key: 'featured' });

        if (tags.length > 0) {
            elements.activeFilters.classList.add('has-filters');
            elements.activeFilterTags.innerHTML = tags.map(function(tag) {
                return '<span class="ptp-active-filter-tag">' + escapeHtml(tag.label) +
                    '<button type="button" onclick="ptpRemoveFilter(\'' + tag.key + '\')">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                    '</button></span>';
            }).join('');
        } else {
            elements.activeFilters.classList.remove('has-filters');
        }
    }

    window.ptpRemoveFilter = function(key) {
        switch(key) {
            case 'search':
                state.filters.search = '';
                elements.searchText.value = '';
                break;
            case 'location':
                state.filters.zip = '';
                state.filters.radius = 25;
                elements.searchZip.value = '';
                elements.searchRadius.value = '25';
                break;
            case 'specialty':
                state.filters.specialty = '';
                elements.filterSpecialty.value = '';
                break;
            case 'playing_level':
                state.filters.playing_level = '';
                elements.filterLevel.value = '';
                break;
            case 'price':
                state.filters.min_price = 0;
                state.filters.max_price = 500;
                elements.filterMinPrice.value = '0';
                elements.filterMaxPrice.value = '500';
                break;
            case 'rating':
                state.filters.min_rating = 0;
                updateRatingStars(0);
                break;
            case 'date':
                state.filters.available_date = '';
                elements.filterDate.value = '';
                break;
            case 'featured':
                state.filters.featured_only = false;
                break;
        }
        state.page = 1;
        loadTrainers();
    };

    function updatePagination() {
        if (state.totalPages > 1 && state.page < state.totalPages) {
            elements.pagination.style.display = 'flex';
            elements.loadMore.disabled = false;
            elements.loadMore.textContent = 'Load More Trainers (' + (state.total - state.trainers.length) + ' remaining)';
        } else {
            elements.pagination.style.display = 'none';
        }
    }

    function showError(message) {
        elements.empty.classList.add('active');
        elements.empty.querySelector('h3').textContent = 'Something went wrong';
        elements.empty.querySelector('p').textContent = message;
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Google Maps Integration
    var map = null;
    var markers = [];
    var infoWindow = null;

    window.initMap = function() {
        if (!config.mapsApiKey) return;

        map = new google.maps.Map(document.getElementById('ptp-map'), {
            zoom: 9,
            center: { lat: 39.9526, lng: -75.1652 },
            styles: [
                { featureType: 'all', elementType: 'geometry', stylers: [{ color: '#f5f5f5' }] },
                { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#c9c9c9' }] },
                { featureType: 'poi', stylers: [{ visibility: 'off' }] }
            ],
            mapTypeControl: false,
            streetViewControl: false
        });

        infoWindow = new google.maps.InfoWindow();

        // Update map with initial trainers
        if (state.trainers.length > 0) {
            updateMap(state.trainers);
        }
    };

    window.updateMap = function(trainers, userLocation) {
        if (!map) return;

        // Clear existing markers
        markers.forEach(function(m) { m.setMap(null); });
        markers = [];

        var bounds = new google.maps.LatLngBounds();
        var hasMarkers = false;

        trainers.forEach(function(t) {
            if (t.lat && t.lng) {
                hasMarkers = true;
                var pos = { lat: t.lat, lng: t.lng };
                bounds.extend(pos);

                var marker = new google.maps.Marker({
                    position: pos,
                    map: map,
                    title: t.name,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 12,
                        fillColor: t.featured ? '#8B5CF6' : '#FCB900',
                        fillOpacity: 1,
                        strokeColor: '#0E0F11',
                        strokeWeight: 2
                    },
                    animation: google.maps.Animation.DROP
                });

                marker.addListener('click', function() {
                    var content = '<div style="font-family:Inter,sans-serif;padding:8px;min-width:220px">' +
                        '<div style="font-weight:700;font-size:16px;margin-bottom:4px">' + escapeHtml(t.name) + '</div>' +
                        '<div style="color:#6B7280;font-size:14px;margin-bottom:8px">' + escapeHtml(t.headline) + '</div>' +
                        '<div style="display:flex;justify-content:space-between;align-items:center">' +
                        '<span style="font-weight:700;color:#0E0F11">$' + t.rate + '/hr</span>' +
                        (t.distance !== null ? '<span style="color:#6B7280;font-size:13px">' + t.distance + ' mi away</span>' : '') +
                        '</div>' +
                        '<a href="' + t.profile_url + '" style="display:block;margin-top:12px;background:#FCB900;color:#0E0F11;padding:10px 16px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;text-align:center">View Profile</a>' +
                        '</div>';
                    infoWindow.setContent(content);
                    infoWindow.open(map, marker);
                });

                markers.push(marker);
            }
        });

        // Add user location marker if available
        if (userLocation && userLocation.lat && userLocation.lng) {
            var userMarker = new google.maps.Marker({
                position: { lat: userLocation.lat, lng: userLocation.lng },
                map: map,
                title: 'Your location',
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: '#3B82F6',
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 3
                }
            });
            markers.push(userMarker);
            bounds.extend({ lat: userLocation.lat, lng: userLocation.lng });
        }

        if (hasMarkers) {
            map.fitBounds(bounds);
            // Don't zoom in too far
            var listener = google.maps.event.addListener(map, 'idle', function() {
                if (map.getZoom() > 14) map.setZoom(14);
                google.maps.event.removeListener(listener);
            });
        }
    };
})();
</script>

<?php if (!empty($google_maps_api_key)): ?>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr($google_maps_api_key); ?>&callback=initMap"></script>
<?php endif; ?>
