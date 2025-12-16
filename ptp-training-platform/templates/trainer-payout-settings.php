<?php
/**
 * Trainer Payout Settings Template
 * Allows trainers to choose and configure their payout method
 */

defined('ABSPATH') || exit;
?>

<div id="ptp-payout-settings" class="ptp-payout-settings">
    <div class="ptp-payout-header">
        <h3>💰 How do you want to get paid?</h3>
        <p class="ptp-subtitle">Choose how you'd like to receive your earnings after each session. You can change this anytime.</p>
    </div>

    <!-- Current Status -->
    <div class="ptp-payout-status" id="payout-status">
        <div class="ptp-status-loading">Loading...</div>
    </div>

    <!-- Payout Method Selection -->
    <div class="ptp-payout-methods">
        <div class="ptp-method-grid">
            <!-- Venmo -->
            <label class="ptp-method-card" data-method="venmo">
                <input type="radio" name="payout_method" value="venmo">
                <div class="ptp-method-content">
                    <div class="ptp-method-icon">
                        <svg viewBox="0 0 24 24" width="32" height="32"><path fill="#3D95CE" d="M19.5 3.5c.6 1.1.9 2.2.9 3.6 0 4.5-3.8 10.3-6.9 14.4H6.8L4 4.5l5.3-.5 1.5 11.9c1.4-2.3 3.1-5.8 3.1-8.2 0-1.3-.2-2.2-.5-2.9l5.1-1.3z"/></svg>
                    </div>
                    <div class="ptp-method-info">
                        <span class="ptp-method-name">Venmo</span>
                        <span class="ptp-method-desc">Same day payouts</span>
                    </div>
                    <div class="ptp-method-check">✓</div>
                </div>
            </label>

            <!-- PayPal -->
            <label class="ptp-method-card" data-method="paypal">
                <input type="radio" name="payout_method" value="paypal">
                <div class="ptp-method-content">
                    <div class="ptp-method-icon">
                        <svg viewBox="0 0 24 24" width="32" height="32"><path fill="#003087" d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 2.65A.765.765 0 0 1 5.7 2h7.314c2.447 0 4.265.603 5.243 1.741.918 1.067 1.186 2.374.826 4.013-.02.1-.043.2-.067.3-.014.062-.028.124-.043.186-.014.06-.028.12-.044.178l-.023.092a8.023 8.023 0 0 1-.168.582c-.62 1.887-1.672 3.208-3.084 3.917-1.322.663-3.01.974-5.139.974h-.296a1.146 1.146 0 0 0-1.126.958l-.014.086-.517 3.27-.013.087a.573.573 0 0 1-.563.487z"/></svg>
                    </div>
                    <div class="ptp-method-info">
                        <span class="ptp-method-name">PayPal</span>
                        <span class="ptp-method-desc">Same day payouts</span>
                    </div>
                    <div class="ptp-method-check">✓</div>
                </div>
            </label>

            <!-- Zelle -->
            <label class="ptp-method-card" data-method="zelle">
                <input type="radio" name="payout_method" value="zelle">
                <div class="ptp-method-content">
                    <div class="ptp-method-icon">
                        <svg viewBox="0 0 24 24" width="32" height="32"><path fill="#6D1ED4" d="M13.559 24h-3.118c-.659 0-1.213-.476-1.325-1.122l-.728-4.156H4.08c-.653 0-1.159-.473-1.262-1.12l-.516-3.232a1.263 1.263 0 0 1 1.248-1.467h5.075l2.23-6.386H6.09a1.34 1.34 0 0 1-1.107-2.089l.515-.773A1.339 1.339 0 0 1 6.605 3h4.283l.742-1.87A1.34 1.34 0 0 1 12.877 0h2.99c.99 0 1.623 1.047 1.15 1.918L15.64 3h2.27c.99 0 1.623 1.047 1.15 1.918l-5.63 10.608h3.318c.99 0 1.624 1.047 1.15 1.918l-.514.968a1.34 1.34 0 0 1-1.182.706h-3.87l-.729 4.156c-.112.646-.666 1.122-1.325 1.122z"/></svg>
                    </div>
                    <div class="ptp-method-info">
                        <span class="ptp-method-name">Zelle</span>
                        <span class="ptp-method-desc">Direct to your bank</span>
                    </div>
                    <div class="ptp-method-check">✓</div>
                </div>
            </label>

            <!-- Cash App -->
            <label class="ptp-method-card" data-method="cashapp">
                <input type="radio" name="payout_method" value="cashapp">
                <div class="ptp-method-content">
                    <div class="ptp-method-icon">
                        <svg viewBox="0 0 24 24" width="32" height="32"><path fill="#00D632" d="M23.59 3.47A5.1 5.1 0 0 0 20.53.41C19.11.08 17.58-.04 16.05.01c-2.31.07-4.6.43-6.82 1.1-2.11.64-4.16 1.55-5.96 2.82A8.64 8.64 0 0 0 .86 6.72a6.11 6.11 0 0 0-.53 1.96c-.08.63-.08 1.27 0 1.9.15 1.12.52 2.2 1.08 3.17a9.72 9.72 0 0 0 2.4 2.87c.97.79 2.04 1.46 3.18 1.98.78.36 1.59.66 2.41.9l-.36 1.84c-.1.52.06 1.06.42 1.44.36.39.88.6 1.42.58h1.23c.54.02 1.07-.18 1.45-.57.37-.38.56-.91.5-1.45l-.34-1.74c.91-.16 1.8-.38 2.67-.67 1.35-.46 2.64-1.1 3.82-1.9a8.4 8.4 0 0 0 2.76-2.96c.6-1.01.96-2.14 1.05-3.3.09-1.15-.09-2.3-.53-3.36z"/><path fill="#FFF" d="M15.25 9.6c-.5-.64-1.18-1.08-1.94-1.3-.78-.22-1.6-.21-2.38.04-.51.16-.98.45-1.36.85-.38.4-.65.88-.8 1.41-.14.54-.14 1.1 0 1.64.14.53.4 1.01.77 1.42.47.52 1.07.9 1.74 1.1l.72.22c.23.07.43.19.58.35.15.17.24.37.25.59.02.21-.05.41-.18.58-.14.18-.33.31-.55.37-.42.12-.87.08-1.27-.11a2.09 2.09 0 0 1-.69-.54l-.09-.1-1.33 1.37.12.12c.34.38.75.68 1.2.88.47.21.97.32 1.48.34v1.12h1.17v-1.15c.61-.09 1.18-.33 1.66-.71a2.61 2.61 0 0 0 .98-1.52c.13-.6.09-1.23-.12-1.81a2.93 2.93 0 0 0-1.1-1.4 4.23 4.23 0 0 0-1.48-.71l-.72-.22a1.4 1.4 0 0 1-.57-.33.84.84 0 0 1-.22-.57c0-.19.07-.38.21-.54.13-.15.32-.27.53-.33.36-.11.74-.07 1.08.12.22.12.42.29.56.5l.08.11 1.26-1.41-.1-.1z"/></svg>
                    </div>
                    <div class="ptp-method-info">
                        <span class="ptp-method-name">Cash App</span>
                        <span class="ptp-method-desc">Same day payouts</span>
                    </div>
                    <div class="ptp-method-check">✓</div>
                </div>
            </label>

            <!-- Direct Deposit -->
            <label class="ptp-method-card" data-method="direct_deposit">
                <input type="radio" name="payout_method" value="direct_deposit">
                <div class="ptp-method-content">
                    <div class="ptp-method-icon">
                        <svg viewBox="0 0 24 24" width="32" height="32"><path fill="#4A5568" d="M12 2L2 7v2h20V7L12 2zm0 2.5L17.5 7h-11L12 4.5zM4 11v9h3v-6h2v6h3v-7h2v7h3v-6h2v6h3v-9H4zm-2 11h20v2H2v-2z"/></svg>
                    </div>
                    <div class="ptp-method-info">
                        <span class="ptp-method-name">Direct Deposit</span>
                        <span class="ptp-method-desc">2-3 business days</span>
                    </div>
                    <div class="ptp-method-check">✓</div>
                </div>
            </label>
        </div>
    </div>

    <!-- Method Details Form -->
    <div class="ptp-method-details" id="method-details" style="display: none;">
        <!-- Venmo Form -->
        <div class="ptp-method-form" data-for="venmo" style="display: none;">
            <div class="ptp-form-group">
                <label>Venmo Username or Phone</label>
                <div class="ptp-input-with-prefix">
                    <span class="ptp-prefix">@</span>
                    <input type="text" name="venmo" placeholder="yourname or 215-555-0123" class="ptp-input">
                </div>
                <p class="ptp-help">Enter your Venmo username (without @) or the phone number linked to your Venmo</p>
            </div>
        </div>

        <!-- PayPal Form -->
        <div class="ptp-method-form" data-for="paypal" style="display: none;">
            <div class="ptp-form-group">
                <label>PayPal Email</label>
                <input type="email" name="paypal" placeholder="email@example.com" class="ptp-input">
                <p class="ptp-help">The email address linked to your PayPal account</p>
            </div>
        </div>

        <!-- Zelle Form -->
        <div class="ptp-method-form" data-for="zelle" style="display: none;">
            <div class="ptp-form-group">
                <label>Zelle Email or Phone</label>
                <input type="text" name="zelle" placeholder="email@example.com or 215-555-0123" class="ptp-input">
                <p class="ptp-help">The email or phone number registered with Zelle through your bank</p>
            </div>
        </div>

        <!-- Cash App Form -->
        <div class="ptp-method-form" data-for="cashapp" style="display: none;">
            <div class="ptp-form-group">
                <label>Cash App $Cashtag</label>
                <div class="ptp-input-with-prefix">
                    <span class="ptp-prefix">$</span>
                    <input type="text" name="cashapp" placeholder="yourcashtag" class="ptp-input">
                </div>
                <p class="ptp-help">Your Cash App $cashtag (without the $)</p>
            </div>
        </div>

        <!-- Direct Deposit Form -->
        <div class="ptp-method-form" data-for="direct_deposit" style="display: none;">
            <div class="ptp-form-group">
                <label>Bank Name</label>
                <input type="text" name="bank_name" placeholder="Chase, Bank of America, etc." class="ptp-input">
            </div>
            <div class="ptp-form-row">
                <div class="ptp-form-group">
                    <label>Routing Number</label>
                    <input type="text" name="routing" placeholder="9 digits" maxlength="9" pattern="[0-9]{9}" class="ptp-input">
                </div>
                <div class="ptp-form-group">
                    <label>Account Number</label>
                    <input type="text" name="account" placeholder="Your account number" class="ptp-input">
                </div>
            </div>
            <div class="ptp-form-group">
                <label>Account Type</label>
                <div class="ptp-radio-group">
                    <label><input type="radio" name="account_type" value="checking" checked> Checking</label>
                    <label><input type="radio" name="account_type" value="savings"> Savings</label>
                </div>
            </div>
            <p class="ptp-help ptp-secure-notice">
                🔒 Your bank information is encrypted and stored securely. Only used for payouts.
            </p>
        </div>

        <!-- Save Button -->
        <button type="button" id="save-payout-method" class="ptp-btn ptp-btn-primary">
            Save Payment Method
        </button>
    </div>
</div>

<style>
.ptp-payout-settings {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.ptp-payout-header h3 {
    margin: 0 0 8px 0;
    font-size: 1.5rem;
    font-weight: 700;
}

.ptp-subtitle {
    color: #666;
    margin: 0 0 24px 0;
}

.ptp-payout-status {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
}

.ptp-payout-status.configured {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border: 1px solid #28a745;
}

.ptp-payout-status.not-configured {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
    border: 1px solid #ffc107;
}

.ptp-status-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ptp-status-icon {
    font-size: 24px;
}

.ptp-status-text {
    flex: 1;
}

.ptp-status-title {
    font-weight: 600;
    margin: 0 0 4px 0;
}

.ptp-status-detail {
    color: #666;
    margin: 0;
    font-size: 0.9rem;
}

.ptp-method-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
}

.ptp-method-card {
    cursor: pointer;
    position: relative;
}

.ptp-method-card input {
    position: absolute;
    opacity: 0;
}

.ptp-method-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    background: white;
    transition: all 0.2s ease;
}

.ptp-method-card:hover .ptp-method-content {
    border-color: #FCB900;
    background: #fffdf5;
}

.ptp-method-card input:checked + .ptp-method-content {
    border-color: #FCB900;
    background: #FCB900;
    color: #0E0F11;
}

.ptp-method-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ptp-method-name {
    font-weight: 600;
    font-size: 0.95rem;
}

.ptp-method-desc {
    font-size: 0.8rem;
    color: #666;
}

.ptp-method-card input:checked + .ptp-method-content .ptp-method-desc {
    color: #333;
}

.ptp-method-check {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    background: #FCB900;
    color: #0E0F11;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    opacity: 0;
    transform: scale(0.5);
    transition: all 0.2s ease;
}

.ptp-method-card input:checked ~ .ptp-method-check {
    opacity: 1;
    transform: scale(1);
}

.ptp-method-details {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 24px;
}

.ptp-form-group {
    margin-bottom: 16px;
}

.ptp-form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 0.9rem;
}

.ptp-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.ptp-input:focus {
    outline: none;
    border-color: #FCB900;
}

.ptp-input-with-prefix {
    display: flex;
    align-items: stretch;
}

.ptp-input-with-prefix .ptp-prefix {
    background: #e2e8f0;
    padding: 12px 14px;
    border: 2px solid #e2e8f0;
    border-right: none;
    border-radius: 8px 0 0 8px;
    font-weight: 600;
    color: #666;
}

.ptp-input-with-prefix .ptp-input {
    border-radius: 0 8px 8px 0;
}

.ptp-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.ptp-radio-group {
    display: flex;
    gap: 24px;
}

.ptp-radio-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: normal;
    cursor: pointer;
}

.ptp-help {
    font-size: 0.85rem;
    color: #666;
    margin-top: 6px;
}

.ptp-secure-notice {
    background: #e8f5e9;
    padding: 12px;
    border-radius: 8px;
    color: #2e7d32;
}

.ptp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 28px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ptp-btn-primary {
    background: #FCB900;
    color: #0E0F11;
}

.ptp-btn-primary:hover {
    background: #e5a800;
    transform: translateY(-1px);
}

.ptp-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 480px) {
    .ptp-method-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .ptp-form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    const $settings = $('#ptp-payout-settings');
    const $status = $('#payout-status');
    const $details = $('#method-details');
    
    let currentInfo = null;
    
    // Load current payout info
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
                    currentInfo = response.data;
                    updateStatusDisplay(response.data);
                    
                    // Select current method
                    if (response.data.method) {
                        $('input[name="payout_method"][value="' + response.data.method + '"]').prop('checked', true).trigger('change');
                    }
                }
            }
        });
    }
    
    // Update status display
    function updateStatusDisplay(info) {
        let html = '';
        
        if (info.is_configured) {
            $status.addClass('configured').removeClass('not-configured');
            html = `
                <div class="ptp-status-content">
                    <div class="ptp-status-icon">✅</div>
                    <div class="ptp-status-text">
                        <p class="ptp-status-title">Payouts enabled via ${info.method_name}</p>
                        <p class="ptp-status-detail">${info.payout_destination} • ${info.processing_time}</p>
                    </div>
                </div>
            `;
        } else {
            $status.addClass('not-configured').removeClass('configured');
            html = `
                <div class="ptp-status-content">
                    <div class="ptp-status-icon">⚠️</div>
                    <div class="ptp-status-text">
                        <p class="ptp-status-title">Set up your payout method</p>
                        <p class="ptp-status-detail">Choose how you want to receive earnings from your sessions</p>
                    </div>
                </div>
            `;
        }
        
        $status.html(html);
    }
    
    // Handle method selection
    $('input[name="payout_method"]').on('change', function() {
        const method = $(this).val();
        
        // Show details section
        $details.show();
        
        // Hide all forms
        $('.ptp-method-form').hide();
        
        // Show selected form
        $(`.ptp-method-form[data-for="${method}"]`).show();
        
        // Pre-fill if we have data
        if (currentInfo && currentInfo.method === method && currentInfo.payout_destination) {
            switch (method) {
                case 'venmo':
                    $('input[name="venmo"]').val(currentInfo.payout_destination.replace('@', ''));
                    break;
                case 'paypal':
                    $('input[name="paypal"]').val(currentInfo.payout_destination);
                    break;
                case 'zelle':
                    $('input[name="zelle"]').val(currentInfo.payout_destination);
                    break;
                case 'cashapp':
                    $('input[name="cashapp"]').val(currentInfo.payout_destination.replace('$', ''));
                    break;
            }
        }
    });
    
    // Save payout method
    $('#save-payout-method').on('click', function() {
        const $btn = $(this);
        const method = $('input[name="payout_method"]:checked').val();
        
        if (!method) {
            alert('Please select a payout method');
            return;
        }
        
        const $form = $(`.ptp-method-form[data-for="${method}"]`);
        const data = {
            action: 'ptp_save_payout_method',
            nonce: ptp_ajax.nonce,
            method: method
        };
        
        // Collect form data
        $form.find('input, select, textarea').each(function() {
            const name = $(this).attr('name');
            if (name && name !== 'payout_method') {
                if ($(this).attr('type') === 'radio') {
                    if ($(this).is(':checked')) {
                        data[name] = $(this).val();
                    }
                } else {
                    data[name] = $(this).val();
                }
            }
        });
        
        $btn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: ptp_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    currentInfo = response.data.info;
                    updateStatusDisplay(currentInfo);
                    
                    // Show success
                    $btn.text('✓ Saved!');
                    setTimeout(() => {
                        $btn.prop('disabled', false).text('Save Payment Method');
                    }, 2000);
                } else {
                    alert(response.data.message || 'Failed to save');
                    $btn.prop('disabled', false).text('Save Payment Method');
                }
            },
            error: function() {
                alert('Error saving payout method');
                $btn.prop('disabled', false).text('Save Payment Method');
            }
        });
    });
    
    // Initialize
    loadPayoutInfo();
});
</script>
