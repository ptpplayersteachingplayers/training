<?php
/**
 * Template: Home Page v29.5.1
 * Logo + Mobile Optimized with PTP Images
 */
defined('ABSPATH') || exit;

// Get featured trainers
global $wpdb;
$featured_trainers = $wpdb->get_results("
    SELECT t.*, COALESCE(AVG(r.rating), 5.0) as avg_rating, COUNT(r.id) as review_count
    FROM {$wpdb->prefix}ptp_trainers t
    LEFT JOIN {$wpdb->prefix}ptp_reviews r ON t.id = r.trainer_id
    WHERE t.status = 'active'
    GROUP BY t.id
    ORDER BY t.is_featured DESC, avg_rating DESC
    LIMIT 4
");

$level_labels = array('pro'=>'PRO','college_d1'=>'D1','college_d2'=>'D2','college_d3'=>'D3','academy'=>'ACADEMY','semi_pro'=>'SEMI-PRO');

// Use PTP_Images class
$logo_url = PTP_Images::logo();
$img = array(
    'hero' => PTP_Images::get('BG7A1915'),
    'training1' => PTP_Images::get('BG7A1874'),
    'training2' => PTP_Images::get('BG7A1847'),
    'skill1' => PTP_Images::get('BG7A1288'),
    'skill2' => PTP_Images::get('BG7A1283'),
    'drill1' => PTP_Images::get('BG7A1539'),
    'drill2' => PTP_Images::get('BG7A1520'),
    'group1' => PTP_Images::get('BG7A1393'),
    'group2' => PTP_Images::get('BG7A1356'),
    'action1' => PTP_Images::get('BG7A1797'),
    'action2' => PTP_Images::get('BG7A1790'),
    'coach1' => PTP_Images::get('BG7A1595'),
    'coach2' => PTP_Images::get('BG7A1563'),
);
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
*{box-sizing:border-box;margin:0;padding:0}html,body{overflow-x:hidden!important;max-width:100vw}
.ptp-home{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;color:#111;line-height:1.6;-webkit-font-smoothing:antialiased}
.ptp-home *{box-sizing:border-box}.ptp-home img{max-width:100%;height:auto}
@media(max-width:1024px){.ptp-g4{grid-template-columns:repeat(2,1fr)!important}.ptp-g3{grid-template-columns:repeat(2,1fr)!important}}
@media(max-width:768px){.ptp-hero-t{font-size:38px!important}.ptp-sec-t{font-size:28px!important}.ptp-g4,.ptp-g3{grid-template-columns:1fr!important}.ptp-stats{flex-direction:column!important;gap:32px!important}.ptp-search{margin:0 16px 32px!important;padding:20px!important}.ptp-sf{flex-direction:column!important}.ptp-trust{flex-direction:column!important;gap:12px!important}.ptp-photos{grid-template-columns:repeat(2,1fr)!important}}
@media(max-width:480px){.ptp-hero-t{font-size:32px!important}.ptp-sec-t{font-size:24px!important}.ptp-hero-p{padding:60px 16px 80px!important}}
</style>

<div class="ptp-home">
    <!-- HERO -->
    <div class="ptp-hero-p" style="position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:100px 24px;background:#0E0F11;overflow:hidden">
        <div style="position:absolute;inset:0;background-image:url('<?php echo esc_url($img['hero']); ?>');background-size:cover;background-position:center;opacity:0.5"></div>
        <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(14,15,17,0.3),rgba(14,15,17,0.95))"></div>
        
        <div style="position:relative;z-index:2;text-align:center;max-width:800px;width:100%">
            <!-- Logo -->
            <img src="<?php echo esc_url($logo_url); ?>" alt="PTP Soccer" style="height:50px;max-width:200px;width:auto;object-fit:contain;margin-bottom:32px">
            
            <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(252,185,0,0.15);color:#FCB900;padding:10px 20px;border-radius:50px;font-size:14px;font-weight:600;margin-bottom:24px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Trusted by 500+ Families
            </div>
            
            <h1 class="ptp-hero-t" style="font-size:64px;font-weight:900;color:#fff;margin:0 0 20px;line-height:1.05;letter-spacing:-0.03em">Train With<br><span style="color:#FCB900">The Best</span></h1>
            <p style="font-size:18px;color:#9CA3AF;margin:0 0 40px;line-height:1.6;max-width:600px;margin-left:auto;margin-right:auto">Connect with elite NCAA Division 1 athletes and professional soccer players for personalized 1-on-1 training sessions near you.</p>
            
            <div class="ptp-search" style="background:#fff;border-radius:20px;padding:28px;max-width:460px;margin:0 auto 40px;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25)">
                <h3 style="font-size:18px;font-weight:700;color:#0E0F11;margin:0 0 20px;text-align:center">Find Trainers Near You</h3>
                <form action="<?php echo esc_url(home_url('/find-trainers/')); ?>" method="get">
                    <div class="ptp-sf" style="display:flex;gap:12px;margin-bottom:16px">
                        <input type="text" name="zip" placeholder="Enter ZIP code" pattern="[0-9]{5}" maxlength="5" style="flex:1;padding:16px 20px;border:2px solid #E5E7EB;border-radius:12px;font-family:inherit;font-size:16px;outline:none;min-width:0">
                        <button type="submit" style="padding:16px 28px;background:#FCB900;color:#0E0F11;border:none;border-radius:12px;font-family:inherit;font-size:16px;font-weight:700;cursor:pointer;white-space:nowrap">Search</button>
                    </div>
                </form>
                <div style="text-align:center;color:#6B7280;font-size:14px;margin:16px 0">or</div>
                <div onclick="ptpLoc()" style="display:flex;align-items:center;justify-content:center;gap:10px;color:#0E0F11;font-weight:600;cursor:pointer;padding:14px;border-radius:10px;background:#F9FAFB">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
                    Use my current location
                </div>
            </div>
            
            <div class="ptp-trust" style="display:flex;justify-content:center;gap:32px;flex-wrap:wrap">
                <div style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,0.85);font-size:14px;font-weight:500"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>Background Checked</div>
                <div style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,0.85);font-size:14px;font-weight:500"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Secure Payments</div>
                <div style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,0.85);font-size:14px;font-weight:500"><svg width="18" height="18" viewBox="0 0 24 24" fill="#FCB900"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>5-Star Rated</div>
            </div>
        </div>
    </div>

    <!-- FEATURED TRAINERS -->
    <?php if (!empty($featured_trainers)): ?>
    <div style="padding:80px 24px;background:#fff">
        <div style="max-width:1200px;margin:0 auto">
            <div style="text-align:center;margin-bottom:48px">
                <h2 class="ptp-sec-t" style="font-size:40px;font-weight:800;color:#0E0F11;margin:0 0 12px;letter-spacing:-0.02em">Featured Trainers</h2>
                <p style="font-size:18px;color:#6B7280;margin:0">Elite athletes ready to help you level up</p>
            </div>
            
            <div class="ptp-g4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:24px">
                <?php foreach ($featured_trainers as $t): 
                    $photo = $t->photo_url ?: PTP_Images::avatar($t->display_name, 400);
                    $rate = (int)($t->hourly_rate ?: 70);
                    $rating = number_format((float)$t->avg_rating, 1);
                    $level = isset($level_labels[$t->playing_level]) ? $level_labels[$t->playing_level] : '';
                ?>
                <a href="<?php echo esc_url(home_url('/trainer/' . $t->slug . '/')); ?>" style="display:block;text-decoration:none;color:inherit;border-radius:16px;overflow:hidden;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.06)">
                    <div style="position:relative;aspect-ratio:4/5;overflow:hidden;background:#f3f4f6">
                        <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($t->display_name); ?>" style="width:100%;height:100%;object-fit:cover" loading="lazy">
                        <div style="position:absolute;bottom:0;left:0;right:0;height:60%;background:linear-gradient(to top,rgba(0,0,0,0.8),transparent)"></div>
                        <?php if ($level): ?><div style="position:absolute;top:12px;left:12px;padding:5px 12px;background:#FCB900;color:#0E0F11;font-size:11px;font-weight:700;border-radius:6px"><?php echo esc_html($level); ?></div><?php endif; ?>
                        <div style="position:absolute;bottom:16px;left:16px;right:60px;color:#fff">
                            <div style="font-size:18px;font-weight:700;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo esc_html($t->display_name); ?></div>
                            <div style="font-size:14px;opacity:0.9">$<?php echo $rate; ?>/hour</div>
                        </div>
                        <div style="position:absolute;bottom:16px;right:16px;width:40px;height:40px;background:#FCB900;border-radius:50%;display:flex;align-items:center;justify-content:center">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0E0F11" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </div>
                    </div>
                    <div style="padding:16px">
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px">
                            <span style="font-size:14px;font-weight:600;color:#0E0F11">⚽ Soccer</span>
                            <span style="display:flex;align-items:center;gap:4px;font-size:14px"><svg width="14" height="14" viewBox="0 0 24 24" fill="#FCB900"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><?php echo $rating; ?></span>
                        </div>
                        <div style="font-size:13px;color:#6B7280"><?php echo esc_html($t->city ?: 'Philadelphia Area'); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align:center;margin-top:40px">
                <a href="<?php echo esc_url(home_url('/find-trainers/')); ?>" style="display:inline-flex;align-items:center;gap:8px;padding:16px 32px;background:#0E0F11;color:#fff;border-radius:12px;font-size:16px;font-weight:700;text-decoration:none">View All Trainers <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- HOW IT WORKS -->
    <div style="padding:80px 24px;background:#F9FAFB">
        <div style="max-width:1000px;margin:0 auto">
            <div style="text-align:center;margin-bottom:56px">
                <h2 class="ptp-sec-t" style="font-size:44px;font-weight:800;color:#0E0F11;margin:0 0 12px">How It Works</h2>
                <p style="font-size:18px;color:#6B7280;margin:0">Book your first session in 3 easy steps</p>
            </div>
            <div class="ptp-g3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:40px">
                <div style="text-align:center"><div style="width:80px;height:80px;background:#FEF3C7;border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:32px;font-weight:900;color:#F59E0B">1</div><h3 style="font-size:20px;font-weight:700;color:#0E0F11;margin:0 0 12px">Find a Trainer</h3><p style="color:#6B7280;margin:0;font-size:15px;line-height:1.6">Browse elite trainers near you. Filter by specialty, price, and availability.</p></div>
                <div style="text-align:center"><div style="width:80px;height:80px;background:#FEF3C7;border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:32px;font-weight:900;color:#F59E0B">2</div><h3 style="font-size:20px;font-weight:700;color:#0E0F11;margin:0 0 12px">Book a Session</h3><p style="color:#6B7280;margin:0;font-size:15px;line-height:1.6">Pick a time that works for you. Pay securely online with Stripe.</p></div>
                <div style="text-align:center"><div style="width:80px;height:80px;background:#FEF3C7;border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:32px;font-weight:900;color:#F59E0B">3</div><h3 style="font-size:20px;font-weight:700;color:#0E0F11;margin:0 0 12px">Train & Improve</h3><p style="color:#6B7280;margin:0;font-size:15px;line-height:1.6">Meet your trainer at your chosen location and level up your game.</p></div>
            </div>
        </div>
    </div>

    <!-- PHOTOS -->
    <div style="padding:80px 24px;background:#fff">
        <div style="max-width:1200px;margin:0 auto">
            <div style="text-align:center;margin-bottom:48px">
                <h2 class="ptp-sec-t" style="font-size:44px;font-weight:800;color:#0E0F11;margin:0 0 12px">See PTP in Action</h2>
                <p style="font-size:18px;color:#6B7280;margin:0">Real training sessions with our elite coaches</p>
            </div>
            <div class="ptp-photos" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
                <?php foreach (array($img['training1'],$img['1v1'],$img['skill1'],$img['coaches'],$img['drill1'],$img['individual'],$img['feedback'],$img['celebration']) as $p): ?>
                <div style="aspect-ratio:1;border-radius:16px;overflow:hidden"><img src="<?php echo esc_url($p); ?>" alt="PTP" style="width:100%;height:100%;object-fit:cover" loading="lazy"></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- STATS -->
    <div style="padding:80px 24px;background:#0E0F11">
        <div style="max-width:900px;margin:0 auto">
            <div style="text-align:center;margin-bottom:56px">
                <h2 class="ptp-sec-t" style="font-size:44px;font-weight:800;color:#fff;margin:0 0 12px">Why Parents Choose PTP</h2>
                <p style="font-size:18px;color:#9CA3AF;margin:0">We're not just coaches. We're current players who understand what it takes.</p>
            </div>
            <div class="ptp-stats" style="display:flex;justify-content:center;gap:64px;text-align:center">
                <div><div style="font-size:64px;font-weight:900;color:#FCB900;line-height:1;margin-bottom:8px">5:1</div><div style="color:#9CA3AF;font-size:15px">Player to Coach Ratio</div></div>
                <div><div style="font-size:64px;font-weight:900;color:#FCB900;line-height:1;margin-bottom:8px">D1+</div><div style="color:#9CA3AF;font-size:15px">NCAA & Pro Athletes</div></div>
                <div><div style="font-size:64px;font-weight:900;color:#FCB900;line-height:1;margin-bottom:8px">100%</div><div style="color:#9CA3AF;font-size:15px">Satisfaction Guaranteed</div></div>
            </div>
        </div>
    </div>

    <!-- PROGRAMS -->
    <div style="padding:80px 24px;background:#F9FAFB">
        <div style="max-width:1200px;margin:0 auto">
            <div style="text-align:center;margin-bottom:48px">
                <div style="display:inline-flex;align-items:center;gap:8px;background:#DBEAFE;color:#1E40AF;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600;margin-bottom:16px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>Training Options</div>
                <h2 class="ptp-sec-t" style="font-size:44px;font-weight:800;color:#0E0F11;margin:0 0 12px">Camps, Clinics & 1-on-1</h2>
                <p style="font-size:18px;color:#6B7280;margin:0">Choose the training format that fits your schedule</p>
            </div>
            <div class="ptp-g3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px">
                <div style="background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06)">
                    <div style="height:160px;background:linear-gradient(135deg,#FCB900,#F59E0B);display:flex;align-items:center;justify-content:center"><div style="text-align:center;color:#0E0F11"><div style="font-size:40px;font-weight:900;line-height:1">SUMMER</div><div style="font-size:18px;font-weight:700">CAMPS 2026</div></div></div>
                    <div style="padding:24px"><h3 style="font-size:20px;font-weight:700;color:#0E0F11;margin:0 0 8px">Week-Long Camps</h3><p style="font-size:14px;color:#6B7280;margin:0 0 16px;line-height:1.5">Full or half-day options across PA, NJ, DE, MD & NY. Ages 6-14.</p><div style="display:flex;align-items:center;justify-content:space-between"><span style="font-size:14px;color:#6B7280">From <strong style="color:#0E0F11">$299/week</strong></span><a href="<?php echo esc_url(home_url('/ptp-shop-page/')); ?>" style="background:#0E0F11;color:#fff;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none">View Camps</a></div></div>
                </div>
                <div style="background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06)">
                    <div style="height:160px;background:linear-gradient(135deg,#0E0F11,#1a1a1a);display:flex;align-items:center;justify-content:center"><div style="text-align:center;color:#fff"><div style="font-size:40px;font-weight:900;line-height:1;color:#FCB900">CLINICS</div><div style="font-size:18px;font-weight:700">YEAR-ROUND</div></div></div>
                    <div style="padding:24px"><h3 style="font-size:20px;font-weight:700;color:#0E0F11;margin:0 0 8px">Skills Clinics</h3><p style="font-size:14px;color:#6B7280;margin:0 0 16px;line-height:1.5">Focused 2-3 hour sessions. Finishing, defending, goalkeeping & more.</p><div style="display:flex;align-items:center;justify-content:space-between"><span style="font-size:14px;color:#6B7280">From <strong style="color:#0E0F11">$49/session</strong></span><a href="<?php echo esc_url(home_url('/ptp-shop-page/')); ?>" style="background:#0E0F11;color:#fff;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none">View Clinics</a></div></div>
                </div>
                <div style="background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06)">
                    <div style="height:160px;background:linear-gradient(135deg,#10B981,#059669);display:flex;align-items:center;justify-content:center"><div style="text-align:center;color:#fff"><div style="font-size:40px;font-weight:900;line-height:1">1-ON-1</div><div style="font-size:18px;font-weight:700">TRAINING</div></div></div>
                    <div style="padding:24px"><h3 style="font-size:20px;font-weight:700;color:#0E0F11;margin:0 0 8px">Private Sessions</h3><p style="font-size:14px;color:#6B7280;margin:0 0 16px;line-height:1.5">Personalized training with elite D1 and pro athletes. Flexible scheduling.</p><div style="display:flex;align-items:center;justify-content:space-between"><span style="font-size:14px;color:#6B7280">From <strong style="color:#0E0F11">$50/hour</strong></span><a href="<?php echo esc_url(home_url('/find-trainers/')); ?>" style="background:#0E0F11;color:#fff;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none">Find Trainers</a></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div style="padding:100px 24px;background:#0E0F11;text-align:center">
        <div style="max-width:600px;margin:0 auto">
            <h2 class="ptp-sec-t" style="font-size:44px;font-weight:800;color:#fff;margin:0 0 16px">Ready to <span style="color:#FCB900">Level Up</span>?</h2>
            <p style="font-size:20px;color:#9CA3AF;margin:0 0 40px">Find your perfect trainer and book your first session today.</p>
            <a href="<?php echo esc_url(home_url('/find-trainers/')); ?>" style="display:inline-flex;align-items:center;gap:8px;padding:20px 40px;background:#FCB900;color:#0E0F11;border-radius:12px;font-size:18px;font-weight:700;text-decoration:none">Find Trainers Near Me <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
    </div>
</div>

<script>
function ptpLoc(){if(navigator.geolocation){navigator.geolocation.getCurrentPosition(function(p){window.location.href='<?php echo home_url('/find-trainers/'); ?>?lat='+p.coords.latitude+'&lng='+p.coords.longitude},function(){alert('Unable to get location. Please enter your ZIP code.')});}else{alert('Geolocation not supported.');}}
</script>
