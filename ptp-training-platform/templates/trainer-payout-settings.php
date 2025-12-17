<?php
/**
 * Trainer Payout Settings Template - Stripe Connect Only
 * Automatic instant payouts via Stripe Connect
 * Version 26.0
 */

defined('ABSPATH') || exit;
?>

<div id="ptp-payout-settings" class="ptp-payout-settings">
    <div class="ptp-payout-header">
        <h3>Get Paid Instantly</h3>
        <p class="ptp-subtitle">Connect your bank account to receive automatic payouts after each confirmed session.</p>
    </div>

    <!-- Current Status -->
    <div class="ptp-payout-status" id="payout-status">
        <div class="ptp-status-loading">
            <div class="ptp-spinner"></div>
            <span>Loading payment status...</span>
        </div>
    </div>

    <!-- Stripe Connect Section -->
    <div class="ptp-stripe-section" id="stripe-section" style="display: none;">
        <!-- Not Connected State -->
        <div class="ptp-stripe-not-connected" id="stripe-not-connected" style="display: none;">
            <div class="ptp-stripe-card">
                <div class="ptp-stripe-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#635BFF" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
                <h4>Connect Your Bank Account</h4>
                <p>Set up Stripe Connect to receive automatic payouts. It only takes 2 minutes.</p>

                <ul class="ptp-stripe-benefits">
                    <li><span class="check">✓</span> Instant payouts after session confirmation</li>
                    <li><span class="check">✓</span> Direct deposit to your bank account</li>
                    <li><span class="check">✓</span> Automatic 1099 tax documentation</li>
                    <li><span class="check">✓</span> View earnings dashboard anytime</li>
                </ul>

                <button type="button" id="start-stripe-onboarding" class="ptp-btn ptp-btn-stripe">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Set Up Payouts
                </button>

                <p class="ptp-stripe-secure">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Secured by Stripe - Trusted by millions of businesses
                </p>
            </div>
        </div>

        <!-- Connected State -->
        <div class="ptp-stripe-connected" id="stripe-connected" style="display: none;">
            <div class="ptp-earnings-cards">
                <div class="ptp-earnings-card">
                    <span class="ptp-earnings-label">Total Earned</span>
                    <span class="ptp-earnings-value" id="total-earnings">$0.00</span>
                </div>
                <div class="ptp-earnings-card pending">
                    <span class="ptp-earnings-label">Pending Payout</span>
                    <span class="ptp-earnings-value" id="pending-balance">$0.00</span>
                </div>
            </div>

            <div class="ptp-stripe-status-card">
                <div class="ptp-stripe-status-header">
                    <div class="ptp-stripe-status-icon success">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="ptp-stripe-status-text">
                        <h4>Stripe Connected</h4>
                        <p>Automatic payouts enabled</p>
                    </div>
                </div>

                <div class="ptp-stripe-actions">
                    <button type="button" id="view-stripe-dashboard" class="ptp-btn ptp-btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        View Stripe Dashboard
                    </button>
                </div>
            </div>

            <!-- Recent Payouts -->
            <div class="ptp-recent-payouts" id="recent-payouts">
                <h4>Recent Payouts</h4>
                <div class="ptp-payouts-list" id="payouts-list">
                    <p class="ptp-no-payouts">No payouts yet. Complete sessions to start earning!</p>
                </div>
            </div>
        </div>

        <!-- Pending State (Onboarding incomplete) -->
        <div class="ptp-stripe-pending" id="stripe-pending" style="display: none;">
            <div class="ptp-stripe-card warning">
                <div class="ptp-stripe-icon warning">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <h4>Complete Your Setup</h4>
                <p>Your Stripe account needs additional information before you can receive payouts.</p>

                <button type="button" id="continue-stripe-onboarding" class="ptp-btn ptp-btn-stripe">
                    Complete Setup
                </button>
            </div>
        </div>
    </div>

    <!-- How It Works -->
    <div class="ptp-how-it-works">
        <h4>How Payouts Work</h4>
        <div class="ptp-steps">
            <div class="ptp-step">
                <div class="ptp-step-num">1</div>
                <div class="ptp-step-text">
                    <strong>Complete a Session</strong>
                    <span>Train your client as scheduled</span>
                </div>
            </div>
            <div class="ptp-step">
                <div class="ptp-step-num">2</div>
                <div class="ptp-step-text">
                    <strong>Session Confirmed</strong>
                    <span>Client confirms the session happened</span>
                </div>
            </div>
            <div class="ptp-step">
                <div class="ptp-step-num">3</div>
                <div class="ptp-step-text">
                    <strong>Get Paid</strong>
                    <span>80% transferred to your bank instantly</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ptp-payout-settings {
    max-width: 600px;
    margin: 0 auto;
    padding: 24px;
}

.ptp-payout-header h3 {
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 700;
    color: #111;
}

.ptp-subtitle {
    color: #6B7280;
    margin: 0 0 24px 0;
    font-size: 15px;
}

.ptp-payout-status {
    background: #F9FAFB;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
}

.ptp-status-loading {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #6B7280;
}

.ptp-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #E5E7EB;
    border-top-color: #FCB900;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Stripe Card */
.ptp-stripe-card {
    background: #fff;
    border: 2px solid #E5E7EB;
    border-radius: 16px;
    padding: 32px;
    text-align: center;
}

.ptp-stripe-card.warning {
    border-color: #F59E0B;
    background: #FFFBEB;
}

.ptp-stripe-icon {
    margin-bottom: 16px;
}

.ptp-stripe-card h4 {
    margin: 0 0 8px 0;
    font-size: 20px;
    font-weight: 700;
    color: #111;
}

.ptp-stripe-card p {
    color: #6B7280;
    margin: 0 0 24px 0;
}

.ptp-stripe-benefits {
    list-style: none;
    padding: 0;
    margin: 0 0 24px 0;
    text-align: left;
}

.ptp-stripe-benefits li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    color: #374151;
    font-size: 14px;
}

.ptp-stripe-benefits .check {
    color: #10B981;
    font-weight: 700;
}

.ptp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 28px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ptp-btn-stripe {
    background: #635BFF;
    color: #fff;
    width: 100%;
}

.ptp-btn-stripe:hover {
    background: #4F46E5;
    transform: translateY(-1px);
}

.ptp-btn-secondary {
    background: #F3F4F6;
    color: #374151;
}

.ptp-btn-secondary:hover {
    background: #E5E7EB;
}

.ptp-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.ptp-stripe-secure {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 16px;
    font-size: 13px;
    color: #6B7280;
}

/* Earnings Cards */
.ptp-earnings-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.ptp-earnings-card {
    background: #fff;
    border: 2px solid #E5E7EB;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}

.ptp-earnings-card.pending {
    background: #FFFBEB;
    border-color: #FCB900;
}

.ptp-earnings-label {
    display: block;
    font-size: 13px;
    color: #6B7280;
    margin-bottom: 4px;
}

.ptp-earnings-value {
    font-size: 28px;
    font-weight: 700;
    color: #111;
}

/* Status Card */
.ptp-stripe-status-card {
    background: #D1FAE5;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.ptp-stripe-status-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.ptp-stripe-status-icon {
    width: 44px;
    height: 44px;
    background: #10B981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.ptp-stripe-status-text h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #065F46;
}

.ptp-stripe-status-text p {
    margin: 2px 0 0;
    font-size: 14px;
    color: #047857;
}

.ptp-stripe-actions {
    display: flex;
    gap: 12px;
}

/* Recent Payouts */
.ptp-recent-payouts {
    background: #fff;
    border: 2px solid #E5E7EB;
    border-radius: 12px;
    padding: 20px;
}

.ptp-recent-payouts h4 {
    margin: 0 0 16px 0;
    font-size: 16px;
    font-weight: 600;
}

.ptp-no-payouts {
    color: #6B7280;
    font-size: 14px;
    text-align: center;
    padding: 20px 0;
}

.ptp-payout-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #F3F4F6;
}

.ptp-payout-item:last-child {
    border-bottom: none;
}

.ptp-payout-date {
    font-size: 14px;
    color: #6B7280;
}

.ptp-payout-amount {
    font-weight: 600;
    color: #10B981;
}

/* How It Works */
.ptp-how-it-works {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 2px solid #E5E7EB;
}

.ptp-how-it-works h4 {
    margin: 0 0 20px 0;
    font-size: 16px;
    font-weight: 600;
    color: #111;
}

.ptp-steps {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.ptp-step {
    display: flex;
    align-items: center;
    gap: 16px;
}

.ptp-step-num {
    width: 32px;
    height: 32px;
    background: #FCB900;
    color: #0E0F11;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
}

.ptp-step-text strong {
    display: block;
    font-size: 14px;
    color: #111;
}

.ptp-step-text span {
    font-size: 13px;
    color: #6B7280;
}

/* Mobile Responsive */
@media (max-width: 600px) {
    .ptp-payout-settings {
        padding: 16px;
    }

    .ptp-payout-header h3 {
        font-size: 20px;
    }

    .ptp-earnings-cards {
        grid-template-columns: 1fr;
    }

    .ptp-stripe-card {
        padding: 24px 16px;
    }

    .ptp-earnings-value {
        font-size: 24px;
    }

    .ptp-stripe-actions {
        flex-direction: column;
    }

    .ptp-btn {
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    const $status = $('#payout-status');
    const $section = $('#stripe-section');
    const $notConnected = $('#stripe-not-connected');
    const $connected = $('#stripe-connected');
    const $pending = $('#stripe-pending');

    // Load payout info
    function loadPayoutInfo() {
        $.ajax({
            url: ptp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ptp_get_trainer_payout_info',
                nonce: ptp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateUI(response.data);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError('Failed to load payout information');
            }
        });
    }

    function updateUI(data) {
        $status.hide();
        $section.show();

        // Hide all states
        $notConnected.hide();
        $connected.hide();
        $pending.hide();

        if (data.onboarding_complete) {
            // Fully connected
            $connected.show();
            $('#total-earnings').text('$' + (data.total_earnings || 0).toFixed(2));
            $('#pending-balance').text('$' + (data.pending_balance || 0).toFixed(2));

            // Show recent payouts
            if (data.recent_payouts && data.recent_payouts.length > 0) {
                let html = '';
                data.recent_payouts.forEach(function(payout) {
                    const date = new Date(payout.processed_at || payout.created_at);
                    html += `
                        <div class="ptp-payout-item">
                            <span class="ptp-payout-date">${date.toLocaleDateString()}</span>
                            <span class="ptp-payout-amount">+$${parseFloat(payout.amount).toFixed(2)}</span>
                        </div>
                    `;
                });
                $('#payouts-list').html(html);
            }
        } else if (data.stripe_account_id) {
            // Account exists but onboarding incomplete
            $pending.show();
        } else {
            // Not connected
            $notConnected.show();
        }
    }

    function showError(message) {
        $status.html(`
            <div style="color: #DC2626; display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                ${message}
            </div>
        `);
    }

    // Start Stripe onboarding
    $('#start-stripe-onboarding, #continue-stripe-onboarding').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="ptp-spinner" style="width:16px;height:16px;border-width:2px;"></span> Connecting...');

        $.ajax({
            url: ptp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ptp_start_stripe_onboarding',
                nonce: ptp_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    window.location.href = response.data.url;
                } else {
                    alert(response.data.message || 'Failed to start Stripe setup');
                    $btn.prop('disabled', false).text('Set Up Payouts');
                }
            },
            error: function() {
                alert('Failed to connect to Stripe. Please try again.');
                $btn.prop('disabled', false).text('Set Up Payouts');
            }
        });
    });

    // View Stripe dashboard
    $('#view-stripe-dashboard').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: ptp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ptp_get_stripe_dashboard_link',
                nonce: ptp_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    window.open(response.data.url, '_blank');
                } else {
                    alert(response.data.message || 'Failed to get dashboard link');
                }
                $btn.prop('disabled', false);
            },
            error: function() {
                alert('Failed to load dashboard. Please try again.');
                $btn.prop('disabled', false);
            }
        });
    });

    // Check for return from Stripe
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('stripe_connected') === '1') {
        // Refresh to show updated status
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Initialize
    loadPayoutInfo();
});
</script>
