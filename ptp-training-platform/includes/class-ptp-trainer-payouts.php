<?php
/**
 * PTP Trainer Payouts - Flexible Payout Methods
 * Supports trainers with or without Stripe accounts
 * Version 25.1
 * 
 * Payout Methods:
 * - Stripe Connect (automatic, if trainer has account)
 * - Venmo (manual, most popular with college players)
 * - PayPal (manual)
 * - Zelle (manual)
 * - Cash App (manual)
 * - Direct Deposit/ACH (manual or automated via Stripe)
 * - Check (manual)
 */

defined('ABSPATH') || exit;

class PTP_Trainer_Payouts {
    
    /**
     * Available payout methods with display names
     */
    const PAYOUT_METHODS = array(
        'venmo' => array(
            'name' => 'Venmo',
            'icon' => 'venmo',
            'field' => 'payout_venmo',
            'placeholder' => '@username or phone',
            'description' => 'Most popular - receive payouts to your Venmo account',
            'processing_time' => 'Same day',
        ),
        'paypal' => array(
            'name' => 'PayPal',
            'icon' => 'paypal',
            'field' => 'payout_paypal',
            'placeholder' => 'email@example.com',
            'description' => 'Receive payouts to your PayPal email',
            'processing_time' => 'Same day',
        ),
        'zelle' => array(
            'name' => 'Zelle',
            'icon' => 'zelle',
            'field' => 'payout_zelle',
            'placeholder' => 'email or phone',
            'description' => 'Direct bank transfer via Zelle',
            'processing_time' => 'Same day',
        ),
        'cashapp' => array(
            'name' => 'Cash App',
            'icon' => 'cashapp',
            'field' => 'payout_cashapp',
            'placeholder' => '$cashtag',
            'description' => 'Receive payouts to your Cash App',
            'processing_time' => 'Same day',
        ),
        'direct_deposit' => array(
            'name' => 'Direct Deposit',
            'icon' => 'bank',
            'field' => 'bank_info',
            'placeholder' => '',
            'description' => 'ACH transfer directly to your bank account',
            'processing_time' => '2-3 business days',
        ),
        'stripe' => array(
            'name' => 'Stripe Connect',
            'icon' => 'stripe',
            'field' => 'stripe_account_id',
            'placeholder' => '',
            'description' => 'Automatic instant payouts (requires Stripe account setup)',
            'processing_time' => 'Instant after session',
        ),
        'check' => array(
            'name' => 'Check',
            'icon' => 'check',
            'field' => 'payout_check_address',
            'placeholder' => 'Mailing address',
            'description' => 'Physical check mailed to your address',
            'processing_time' => '5-7 business days',
        ),
    );
    
    /**
     * Minimum payout amounts by method
     */
    const MIN_PAYOUT = array(
        'venmo' => 1,
        'paypal' => 1,
        'zelle' => 1,
        'cashapp' => 1,
        'direct_deposit' => 25,
        'stripe' => 10,
        'check' => 50,
    );
    
    public static function init() {
        // AJAX handlers for trainer payout settings
        add_action('wp_ajax_ptp_get_payout_methods', array(__CLASS__, 'ajax_get_payout_methods'));
        add_action('wp_ajax_ptp_save_payout_method', array(__CLASS__, 'ajax_save_payout_method'));
        add_action('wp_ajax_ptp_get_trainer_payout_info', array(__CLASS__, 'ajax_get_trainer_payout_info'));
        
        // Admin AJAX for processing payouts
        add_action('wp_ajax_ptp_admin_process_manual_payout', array(__CLASS__, 'ajax_admin_process_payout'));
        add_action('wp_ajax_ptp_admin_bulk_process_payouts', array(__CLASS__, 'ajax_admin_bulk_process'));
        add_action('wp_ajax_ptp_admin_get_payout_queue', array(__CLASS__, 'ajax_admin_get_payout_queue'));
    }
    
    /**
     * Get available payout methods
     */
    public static function get_payout_methods() {
        return self::PAYOUT_METHODS;
    }
    
    /**
     * Get trainer's payout configuration
     */
    public static function get_trainer_payout_info($trainer_id) {
        global $wpdb;
        
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_trainers WHERE id = %d",
            $trainer_id
        ));
        
        if (!$trainer) {
            return new WP_Error('not_found', 'Trainer not found');
        }
        
        $method = $trainer->payout_method ?: 'venmo';
        $method_info = self::PAYOUT_METHODS[$method] ?? self::PAYOUT_METHODS['venmo'];
        
        $info = array(
            'method' => $method,
            'method_name' => $method_info['name'],
            'processing_time' => $method_info['processing_time'],
            'min_payout' => self::MIN_PAYOUT[$method] ?? 10,
            'is_configured' => false,
            'payout_destination' => '',
        );
        
        // Get the payout destination based on method
        switch ($method) {
            case 'venmo':
                $info['payout_destination'] = $trainer->payout_venmo ?? '';
                $info['is_configured'] = !empty($trainer->payout_venmo);
                break;
            case 'paypal':
                $info['payout_destination'] = $trainer->payout_paypal ?? '';
                $info['is_configured'] = !empty($trainer->payout_paypal);
                break;
            case 'zelle':
                $info['payout_destination'] = $trainer->payout_zelle ?? '';
                $info['is_configured'] = !empty($trainer->payout_zelle);
                break;
            case 'cashapp':
                $info['payout_destination'] = $trainer->payout_cashapp ?? '';
                $info['is_configured'] = !empty($trainer->payout_cashapp);
                break;
            case 'direct_deposit':
                $info['payout_destination'] = !empty($trainer->payout_bank_account) 
                    ? '****' . substr($trainer->payout_bank_account, -4)
                    : '';
                $info['is_configured'] = !empty($trainer->payout_bank_routing) && !empty($trainer->payout_bank_account);
                $info['bank_name'] = $trainer->payout_bank_name ?? '';
                break;
            case 'stripe':
                $info['payout_destination'] = $trainer->stripe_account_id ?? '';
                $info['is_configured'] = !empty($trainer->stripe_account_id) && $trainer->stripe_payouts_enabled;
                break;
            case 'check':
                $info['payout_destination'] = $trainer->payout_check_address ?? '';
                $info['is_configured'] = !empty($trainer->payout_check_address);
                break;
        }
        
        return $info;
    }
    
    /**
     * Save trainer's payout method
     */
    public static function save_payout_method($trainer_id, $method, $data) {
        global $wpdb;
        
        if (!isset(self::PAYOUT_METHODS[$method])) {
            return new WP_Error('invalid_method', 'Invalid payout method');
        }
        
        $update_data = array(
            'payout_method' => $method,
        );
        
        // Validate and save method-specific data
        switch ($method) {
            case 'venmo':
                $venmo = sanitize_text_field($data['venmo'] ?? '');
                if (empty($venmo)) {
                    return new WP_Error('missing_venmo', 'Please enter your Venmo username or phone number');
                }
                // Clean up Venmo handle
                $venmo = ltrim($venmo, '@');
                $update_data['payout_venmo'] = $venmo;
                break;
                
            case 'paypal':
                $paypal = sanitize_email($data['paypal'] ?? '');
                if (empty($paypal) || !is_email($paypal)) {
                    return new WP_Error('invalid_paypal', 'Please enter a valid PayPal email address');
                }
                $update_data['payout_paypal'] = $paypal;
                break;
                
            case 'zelle':
                $zelle = sanitize_text_field($data['zelle'] ?? '');
                if (empty($zelle)) {
                    return new WP_Error('missing_zelle', 'Please enter your Zelle email or phone number');
                }
                $update_data['payout_zelle'] = $zelle;
                break;
                
            case 'cashapp':
                $cashapp = sanitize_text_field($data['cashapp'] ?? '');
                if (empty($cashapp)) {
                    return new WP_Error('missing_cashapp', 'Please enter your Cash App $cashtag');
                }
                // Ensure it starts with $
                $cashapp = ltrim($cashapp, '$');
                $update_data['payout_cashapp'] = '$' . $cashapp;
                break;
                
            case 'direct_deposit':
                $bank_name = sanitize_text_field($data['bank_name'] ?? '');
                $routing = preg_replace('/[^0-9]/', '', $data['routing'] ?? '');
                $account = preg_replace('/[^0-9]/', '', $data['account'] ?? '');
                $account_type = in_array($data['account_type'] ?? '', ['checking', 'savings']) 
                    ? $data['account_type'] 
                    : 'checking';
                
                if (strlen($routing) !== 9) {
                    return new WP_Error('invalid_routing', 'Routing number must be 9 digits');
                }
                if (strlen($account) < 4 || strlen($account) > 17) {
                    return new WP_Error('invalid_account', 'Please enter a valid account number');
                }
                
                $update_data['payout_bank_name'] = $bank_name;
                $update_data['payout_bank_routing'] = $routing;
                $update_data['payout_bank_account'] = $account;
                $update_data['payout_bank_account_type'] = $account_type;
                break;
                
            case 'check':
                $address = sanitize_textarea_field($data['address'] ?? '');
                if (empty($address)) {
                    return new WP_Error('missing_address', 'Please enter your mailing address');
                }
                $update_data['payout_check_address'] = $address;
                break;
                
            case 'stripe':
                // Stripe Connect is handled separately
                break;
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'ptp_trainers',
            $update_data,
            array('id' => $trainer_id)
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to save payout settings');
        }
        
        return true;
    }
    
    /**
     * Check if trainer can receive payouts
     */
    public static function trainer_can_receive_payouts($trainer_id) {
        $info = self::get_trainer_payout_info($trainer_id);
        
        if (is_wp_error($info)) {
            return false;
        }
        
        return $info['is_configured'];
    }
    
    /**
     * Create a payout request for a trainer
     */
    public static function create_payout_request($trainer_id, $amount, $booking_ids = array()) {
        global $wpdb;
        
        $info = self::get_trainer_payout_info($trainer_id);
        
        if (is_wp_error($info)) {
            return $info;
        }
        
        if (!$info['is_configured']) {
            return new WP_Error('not_configured', 'Trainer has not configured their payout method');
        }
        
        $min_payout = $info['min_payout'];
        if ($amount < $min_payout) {
            return new WP_Error('below_minimum', sprintf(
                'Payout amount ($%.2f) is below minimum ($%.2f) for %s',
                $amount,
                $min_payout,
                $info['method_name']
            ));
        }
        
        // Create payout record
        $payout_id = $wpdb->insert(
            $wpdb->prefix . 'ptp_payouts',
            array(
                'trainer_id' => $trainer_id,
                'amount' => $amount,
                'status' => $info['method'] === 'stripe' ? 'processing' : 'pending',
                'payout_method' => $info['method'],
                'payout_reference' => $info['payout_destination'],
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%f', '%s', '%s', '%s', '%s')
        );
        
        if (!$payout_id) {
            return new WP_Error('db_error', 'Failed to create payout request');
        }
        
        $payout_id = $wpdb->insert_id;
        
        // Link booking IDs to this payout
        if (!empty($booking_ids)) {
            foreach ($booking_ids as $booking_id) {
                $wpdb->insert(
                    $wpdb->prefix . 'ptp_payout_items',
                    array(
                        'payout_id' => $payout_id,
                        'booking_id' => $booking_id,
                        'amount' => 0, // Will be calculated
                    )
                );
            }
        }
        
        // If Stripe, process automatically
        if ($info['method'] === 'stripe' && class_exists('PTP_Stripe')) {
            $trainer = $wpdb->get_row($wpdb->prepare(
                "SELECT stripe_account_id FROM {$wpdb->prefix}ptp_trainers WHERE id = %d",
                $trainer_id
            ));
            
            if ($trainer && !empty($trainer->stripe_account_id)) {
                $transfer = PTP_Stripe::create_transfer($amount, $trainer->stripe_account_id, array(
                    'payout_id' => $payout_id
                ));
                
                if (!is_wp_error($transfer)) {
                    $wpdb->update(
                        $wpdb->prefix . 'ptp_payouts',
                        array(
                            'status' => 'completed',
                            'payout_reference' => $transfer['id'],
                            'processed_at' => current_time('mysql'),
                        ),
                        array('id' => $payout_id)
                    );
                }
            }
        }
        
        return $payout_id;
    }
    
    /**
     * Get pending payouts for admin queue
     */
    public static function get_payout_queue($method = null) {
        global $wpdb;
        
        $where = "p.status = 'pending'";
        if ($method && $method !== 'all') {
            $where .= $wpdb->prepare(" AND p.payout_method = %s", $method);
        }
        
        return $wpdb->get_results("
            SELECT 
                p.*,
                t.display_name as trainer_name,
                t.email as trainer_email,
                t.phone as trainer_phone,
                t.payout_venmo,
                t.payout_paypal,
                t.payout_zelle,
                t.payout_cashapp,
                t.payout_bank_name,
                t.payout_bank_routing,
                t.payout_bank_account,
                t.payout_check_address
            FROM {$wpdb->prefix}ptp_payouts p
            LEFT JOIN {$wpdb->prefix}ptp_trainers t ON p.trainer_id = t.id
            WHERE {$where}
            ORDER BY p.created_at ASC
        ");
    }
    
    /**
     * Mark a payout as completed (admin action)
     */
    public static function mark_payout_completed($payout_id, $reference = '', $notes = '') {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'ptp_payouts',
            array(
                'status' => 'completed',
                'payout_reference' => $reference,
                'notes' => $notes,
                'processed_at' => current_time('mysql'),
            ),
            array('id' => $payout_id)
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to update payout');
        }
        
        // Send notification to trainer
        $payout = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_payouts WHERE id = %d",
            $payout_id
        ));
        
        if ($payout && class_exists('PTP_Email')) {
            PTP_Email::send_payout_completed($payout_id);
        }
        
        return true;
    }
    
    /**
     * AJAX: Get payout methods for frontend
     */
    public static function ajax_get_payout_methods() {
        $methods = array();
        
        foreach (self::PAYOUT_METHODS as $key => $method) {
            // Skip Stripe for the simple selector (it has its own flow)
            if ($key === 'stripe') continue;
            
            $methods[$key] = array(
                'name' => $method['name'],
                'description' => $method['description'],
                'processing_time' => $method['processing_time'],
                'min_payout' => self::MIN_PAYOUT[$key] ?? 10,
                'placeholder' => $method['placeholder'],
            );
        }
        
        wp_send_json_success($methods);
    }
    
    /**
     * AJAX: Save trainer's payout method
     */
    public static function ajax_save_payout_method() {
        check_ajax_referer('ptp_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Not logged in'));
        }
        
        global $wpdb;
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ptp_trainers WHERE user_id = %d",
            $user_id
        ));
        
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer profile not found'));
        }
        
        $method = sanitize_text_field($_POST['method'] ?? '');
        
        $result = self::save_payout_method($trainer->id, $method, $_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => 'Payout method saved successfully',
            'info' => self::get_trainer_payout_info($trainer->id),
        ));
    }
    
    /**
     * AJAX: Get trainer's payout info
     */
    public static function ajax_get_trainer_payout_info() {
        check_ajax_referer('ptp_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Not logged in'));
        }
        
        global $wpdb;
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ptp_trainers WHERE user_id = %d",
            $user_id
        ));
        
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Trainer profile not found'));
        }
        
        $info = self::get_trainer_payout_info($trainer->id);
        
        if (is_wp_error($info)) {
            wp_send_json_error(array('message' => $info->get_error_message()));
        }
        
        wp_send_json_success($info);
    }
    
    /**
     * AJAX: Admin process manual payout
     */
    public static function ajax_admin_process_payout() {
        check_ajax_referer('ptp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $payout_id = intval($_POST['payout_id'] ?? 0);
        $reference = sanitize_text_field($_POST['reference'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (!$payout_id) {
            wp_send_json_error(array('message' => 'Invalid payout ID'));
        }
        
        $result = self::mark_payout_completed($payout_id, $reference, $notes);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => 'Payout marked as completed'));
    }
    
    /**
     * AJAX: Admin get payout queue
     */
    public static function ajax_admin_get_payout_queue() {
        check_ajax_referer('ptp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $method = sanitize_text_field($_POST['method'] ?? 'all');
        
        $queue = self::get_payout_queue($method);
        
        wp_send_json_success(array(
            'payouts' => $queue,
            'count' => count($queue),
        ));
    }
    
    /**
     * Get payout instructions for admin
     */
    public static function get_payout_instructions($payout) {
        $method = $payout->payout_method;
        
        switch ($method) {
            case 'venmo':
                return array(
                    'platform' => 'Venmo',
                    'recipient' => '@' . ltrim($payout->payout_venmo, '@'),
                    'amount' => '$' . number_format($payout->amount, 2),
                    'instructions' => "1. Open Venmo app\n2. Search for {$payout->payout_venmo}\n3. Send \${$payout->amount}\n4. Note: PTP Payout #{$payout->id}",
                );
                
            case 'paypal':
                return array(
                    'platform' => 'PayPal',
                    'recipient' => $payout->payout_paypal,
                    'amount' => '$' . number_format($payout->amount, 2),
                    'instructions' => "1. Open PayPal\n2. Send to {$payout->payout_paypal}\n3. Amount: \${$payout->amount}\n4. Note: PTP Payout #{$payout->id}",
                );
                
            case 'zelle':
                return array(
                    'platform' => 'Zelle',
                    'recipient' => $payout->payout_zelle,
                    'amount' => '$' . number_format($payout->amount, 2),
                    'instructions' => "1. Open bank app or Zelle\n2. Send to {$payout->payout_zelle}\n3. Amount: \${$payout->amount}\n4. Memo: PTP Payout #{$payout->id}",
                );
                
            case 'cashapp':
                return array(
                    'platform' => 'Cash App',
                    'recipient' => $payout->payout_cashapp,
                    'amount' => '$' . number_format($payout->amount, 2),
                    'instructions' => "1. Open Cash App\n2. Send to {$payout->payout_cashapp}\n3. Amount: \${$payout->amount}\n4. Note: PTP Payout #{$payout->id}",
                );
                
            case 'direct_deposit':
                return array(
                    'platform' => 'Direct Deposit',
                    'recipient' => $payout->payout_bank_name . ' ****' . substr($payout->payout_bank_account, -4),
                    'amount' => '$' . number_format($payout->amount, 2),
                    'instructions' => "Bank: {$payout->payout_bank_name}\nRouting: {$payout->payout_bank_routing}\nAccount: {$payout->payout_bank_account}",
                    'routing' => $payout->payout_bank_routing,
                    'account' => $payout->payout_bank_account,
                );
                
            case 'check':
                return array(
                    'platform' => 'Check',
                    'recipient' => $payout->trainer_name,
                    'amount' => '$' . number_format($payout->amount, 2),
                    'instructions' => "Mail check to:\n{$payout->trainer_name}\n{$payout->payout_check_address}",
                    'address' => $payout->payout_check_address,
                );
                
            default:
                return array(
                    'platform' => 'Unknown',
                    'recipient' => $payout->payout_reference,
                    'amount' => '$' . number_format($payout->amount, 2),
                    'instructions' => 'Contact trainer for payment details',
                );
        }
    }
}

// Initialize
PTP_Trainer_Payouts::init();
