<?php
/**
 * Trainer Earnings Dashboard Component
 * PTP v25.0 - Robust payment management for individuals
 */
defined('ABSPATH') || exit;

// Get trainer data
$user_id = get_current_user_id();
$trainer = PTP_Trainer::get_by_user_id($user_id);

if (!$trainer) {
    return;
}

global $wpdb;

// Get earnings stats
$stats = $wpdb->get_row($wpdb->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN trainer_earnings ELSE 0 END), 0) as total_earned,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' AND (payout_status IS NULL OR payout_status = 'pending') THEN trainer_earnings ELSE 0 END), 0) as available_balance,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' AND payout_status = 'paid' THEN trainer_earnings ELSE 0 END), 0) as total_paid_out,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_sessions
    FROM {$wpdb->prefix}ptp_bookings
    WHERE trainer_id = %d
", $trainer->id));

// Get recent transactions
$recent_transactions = $wpdb->get_results($wpdb->prepare("
    SELECT 
        b.id,
        b.session_date,
        b.trainer_earnings,
        b.payment_status,
        b.payout_status,
        b.paid_at,
        p.name as player_name,
        u.display_name as parent_name
    FROM {$wpdb->prefix}ptp_bookings b
    LEFT JOIN {$wpdb->prefix}ptp_players p ON b.player_id = p.id
    LEFT JOIN {$wpdb->prefix}users u ON b.parent_id = u.ID
    WHERE b.trainer_id = %d AND b.payment_status = 'paid'
    ORDER BY b.paid_at DESC
    LIMIT 5
", $trainer->id));

// Check Stripe status
$stripe_connected = !empty($trainer->stripe_account_id);
$stripe_active = $stripe_connected && $trainer->stripe_charges_enabled && $trainer->stripe_payouts_enabled;
$stripe_pending = $stripe_connected && (!$trainer->stripe_charges_enabled || !$trainer->stripe_payouts_enabled);

// Calculate this month's earnings
$this_month_start = date('Y-m-01');
$this_month_earnings = $wpdb->get_var($wpdb->prepare("
    SELECT COALESCE(SUM(trainer_earnings), 0)
    FROM {$wpdb->prefix}ptp_bookings
    WHERE trainer_id = %d AND payment_status = 'paid' AND paid_at >= %s
", $trainer->id, $this_month_start));
?>

<style>
.ptp-earnings {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.ptp-earnings-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.ptp-earnings-title {
    font-size: 20px;
    font-weight: 800;
    color: #111827;
}

.ptp-earnings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.ptp-earnings-stat {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.ptp-earnings-stat-label {
    font-size: 12px;
    font-weight: 600;
    color: #6B7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.ptp-earnings-stat-value {
    font-size: 28px;
    font-weight: 800;
    color: #111827;
}

.ptp-earnings-stat-value.green {
    color: #10B981;
}

.ptp-earnings-stat-sub {
    font-size: 12px;
    color: #9CA3AF;
    margin-top: 4px;
}

/* Stripe Connect Section */
.ptp-stripe-connect {
    background: linear-gradient(135deg, #635BFF 0%, #7B73FF 100%);
    border-radius: 16px;
    padding: 24px;
    color: #fff;
    margin-bottom: 24px;
}

.ptp-stripe-connect h3 {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 8px;
}

.ptp-stripe-connect p {
    font-size: 14px;
    opacity: 0.9;
    margin: 0 0 16px;
    line-height: 1.5;
}

.ptp-stripe-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: rgba(255,255,255,0.2);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 16px;
}

.ptp-stripe-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.ptp-stripe-dot.green { background: #10B981; }
.ptp-stripe-dot.yellow { background: #FBBF24; }
.ptp-stripe-dot.red { background: #EF4444; }

.ptp-stripe-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: #fff;
    color: #635BFF;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}

.ptp-stripe-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.ptp-stripe-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.ptp-stripe-btn.outline {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

/* Payout Section */
.ptp-payout-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 24px;
}

.ptp-payout-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.ptp-payout-title {
    font-size: 16px;
    font-weight: 700;
}

.ptp-payout-amount {
    font-size: 24px;
    font-weight: 800;
    color: #10B981;
}

.ptp-payout-btn {
    width: 100%;
    padding: 14px;
    background: #FCB900;
    color: #0E0F11;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}

.ptp-payout-btn:hover {
    background: #E5A800;
    transform: translateY(-1px);
}

.ptp-payout-btn:disabled {
    background: #E5E7EB;
    color: #9CA3AF;
    cursor: not-allowed;
    transform: none;
}

.ptp-payout-note {
    font-size: 12px;
    color: #9CA3AF;
    text-align: center;
    margin-top: 12px;
}

/* Transactions */
.ptp-transactions {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    overflow: hidden;
}

.ptp-transactions-header {
    padding: 16px 20px;
    border-bottom: 1px solid #F3F4F6;
}

.ptp-transactions-title {
    font-size: 16px;
    font-weight: 700;
}

.ptp-transaction {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #F3F4F6;
}

.ptp-transaction:last-child {
    border-bottom: none;
}

.ptp-transaction-info {
    flex: 1;
}

.ptp-transaction-name {
    font-weight: 600;
    font-size: 14px;
    color: #111827;
}

.ptp-transaction-date {
    font-size: 12px;
    color: #9CA3AF;
}

.ptp-transaction-amount {
    font-size: 16px;
    font-weight: 700;
    color: #10B981;
}

.ptp-transaction-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 12px;
}

.ptp-transaction-badge.paid {
    background: #D1FAE5;
    color: #065F46;
}

.ptp-transaction-badge.pending {
    background: #FEF3C7;
    color: #92400E;
}

.ptp-transactions-empty {
    padding: 40px 20px;
    text-align: center;
    color: #9CA3AF;
}

.ptp-transactions-footer {
    padding: 16px 20px;
    border-top: 1px solid #F3F4F6;
    text-align: center;
}

.ptp-transactions-footer a {
    color: #FCB900;
    font-weight: 600;
    text-decoration: none;
}

/* Info Box */
.ptp-info-box {
    background: #EFF6FF;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
}

.ptp-info-box h4 {
    font-size: 14px;
    font-weight: 700;
    color: #1E40AF;
    margin: 0 0 8px;
}

.ptp-info-box p {
    font-size: 13px;
    color: #1E40AF;
    margin: 0;
    line-height: 1.5;
}

.ptp-info-box ul {
    margin: 8px 0 0;
    padding-left: 20px;
    font-size: 13px;
    color: #1E40AF;
}

.ptp-info-box li {
    margin-bottom: 4px;
}
</style>

<div class="ptp-earnings">
    
    <!-- Header -->
    <div class="ptp-earnings-header">
        <h2 class="ptp-earnings-title">Earnings</h2>
    </div>
    
    <!-- Stats Grid -->
    <div class="ptp-earnings-grid">
        <div class="ptp-earnings-stat">
            <div class="ptp-earnings-stat-label">This Month</div>
            <div class="ptp-earnings-stat-value">$<?php echo number_format($this_month_earnings, 2); ?></div>
            <div class="ptp-earnings-stat-sub"><?php echo date('F Y'); ?></div>
        </div>
        <div class="ptp-earnings-stat">
            <div class="ptp-earnings-stat-label">Available Balance</div>
            <div class="ptp-earnings-stat-value green">$<?php echo number_format($stats->available_balance, 2); ?></div>
            <div class="ptp-earnings-stat-sub">Ready for payout</div>
        </div>
        <div class="ptp-earnings-stat">
            <div class="ptp-earnings-stat-label">Total Earned</div>
            <div class="ptp-earnings-stat-value">$<?php echo number_format($stats->total_earned, 2); ?></div>
            <div class="ptp-earnings-stat-sub"><?php echo $stats->paid_sessions; ?> sessions</div>
        </div>
        <div class="ptp-earnings-stat">
            <div class="ptp-earnings-stat-label">Total Paid Out</div>
            <div class="ptp-earnings-stat-value">$<?php echo number_format($stats->total_paid_out, 2); ?></div>
            <div class="ptp-earnings-stat-sub">To your bank</div>
        </div>
    </div>
    
    <!-- Stripe Connect Section -->
    <div class="ptp-stripe-connect">
        <h3>Payment Setup</h3>
        
        <?php if ($stripe_active): ?>
            <div class="ptp-stripe-status">
                <span class="ptp-stripe-dot green"></span>
                Connected & Active
            </div>
            <p>Your bank account is connected. Payments are deposited automatically within 2-3 business days.</p>
            <button type="button" class="ptp-stripe-btn" onclick="openStripeDashboard()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
                    <polyline points="15 3 21 3 21 9"/>
                    <line x1="10" y1="14" x2="21" y2="3"/>
                </svg>
                Open Stripe Dashboard
            </button>
        <?php elseif ($stripe_pending): ?>
            <div class="ptp-stripe-status">
                <span class="ptp-stripe-dot yellow"></span>
                Setup Incomplete
            </div>
            <p>Almost there! Complete your Stripe setup to start receiving payments.</p>
            <button type="button" class="ptp-stripe-btn" onclick="connectStripe()">
                Complete Setup
            </button>
        <?php else: ?>
            <p>Connect your bank account to receive payments from training sessions. It only takes a few minutes and works for individuals - no business required.</p>
            <button type="button" class="ptp-stripe-btn" onclick="connectStripe()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                </svg>
                Connect Bank Account
            </button>
        <?php endif; ?>
    </div>
    
    <!-- Info Box for non-connected trainers -->
    <?php if (!$stripe_active): ?>
    <div class="ptp-info-box">
        <h4>How Payments Work</h4>
        <p>PTP uses Stripe to securely process payments and pay you directly:</p>
        <ul>
            <li>Parents pay through our secure checkout</li>
            <li>You receive 80% of each session fee</li>
            <li>Funds deposit to your bank in 2-3 business days</li>
            <li>Works for individuals - no business required</li>
            <li>All tax documents provided automatically</li>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Payout Section (only if Stripe is active and has balance) -->
    <?php if ($stripe_active && $stats->available_balance >= 10): ?>
    <div class="ptp-payout-card">
        <div class="ptp-payout-header">
            <span class="ptp-payout-title">Available for Payout</span>
            <span class="ptp-payout-amount">$<?php echo number_format($stats->available_balance, 2); ?></span>
        </div>
        <button type="button" class="ptp-payout-btn" onclick="requestPayout()">
            Request Payout
        </button>
        <p class="ptp-payout-note">Minimum payout: $10. Funds arrive in 2-3 business days.</p>
    </div>
    <?php endif; ?>
    
    <!-- Recent Transactions -->
    <div class="ptp-transactions">
        <div class="ptp-transactions-header">
            <h3 class="ptp-transactions-title">Recent Earnings</h3>
        </div>
        
        <?php if (!empty($recent_transactions)): ?>
            <?php foreach ($recent_transactions as $tx): ?>
            <div class="ptp-transaction">
                <div class="ptp-transaction-info">
                    <div class="ptp-transaction-name">
                        <?php echo esc_html($tx->player_name ?: $tx->parent_name ?: 'Session'); ?>
                    </div>
                    <div class="ptp-transaction-date">
                        <?php echo date('M j, Y', strtotime($tx->session_date)); ?>
                    </div>
                </div>
                <div class="ptp-transaction-amount">
                    +$<?php echo number_format($tx->trainer_earnings, 2); ?>
                </div>
                <span class="ptp-transaction-badge <?php echo $tx->payout_status === 'paid' ? 'paid' : 'pending'; ?>">
                    <?php echo $tx->payout_status === 'paid' ? 'Paid Out' : 'Available'; ?>
                </span>
            </div>
            <?php endforeach; ?>
            
            <div class="ptp-transactions-footer">
                <a href="<?php echo esc_url(home_url('/account/?tab=payments')); ?>">View All Transactions</a>
            </div>
        <?php else: ?>
            <div class="ptp-transactions-empty">
                <p>No earnings yet. Complete sessions to start earning!</p>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<script>
function connectStripe() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = 'Connecting...';
    
    const formData = new FormData();
    formData.append('action', 'ptp_create_stripe_connect_account');
    formData.append('nonce', '<?php echo wp_create_nonce('ptp_nonce'); ?>');
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
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
            btn.innerHTML = 'Connect Bank Account';
        }
    })
    .catch(() => {
        alert('Connection error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = 'Connect Bank Account';
    });
}

function openStripeDashboard() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = 'Loading...';
    
    const formData = new FormData();
    formData.append('action', 'ptp_get_stripe_dashboard_link');
    formData.append('nonce', '<?php echo wp_create_nonce('ptp_nonce'); ?>');
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
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
        btn.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
                <polyline points="15 3 21 3 21 9"/>
                <line x1="10" y1="14" x2="21" y2="3"/>
            </svg>
            Open Stripe Dashboard
        `;
    })
    .catch(() => {
        alert('Error loading dashboard');
        btn.disabled = false;
    });
}

function requestPayout() {
    if (!confirm('Request payout of $<?php echo number_format($stats->available_balance, 2); ?>? Funds will arrive in 2-3 business days.')) {
        return;
    }
    
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = 'Processing...';
    
    const formData = new FormData();
    formData.append('action', 'ptp_request_payout');
    formData.append('nonce', '<?php echo wp_create_nonce('ptp_nonce'); ?>');
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            location.reload();
        } else {
            alert(data.data?.message || 'Unable to process payout');
            btn.disabled = false;
            btn.innerHTML = 'Request Payout';
        }
    })
    .catch(() => {
        alert('Error processing payout');
        btn.disabled = false;
        btn.innerHTML = 'Request Payout';
    });
}
</script>
