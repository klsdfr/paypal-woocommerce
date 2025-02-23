<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Seller_Onboarding {

    public $dcc_applies;
    public $ppcp_host;
    public $testmode;
    public $setting_obj;
    public $host;
    public $partner_merchant_id;
    public $sandbox_partner_merchant_id;
    public $api_request;
    public $result;
    protected static $_instance = null;
    public $api_log;
    public $is_sandbox;
    public $ppcp_migration;
    public $angelleye_ppcp_migration_wizard_notice_key = 'angelleye_ppcp_migration_wizard_notice_key';
    public $angelleye_ppcp_migration_wizard_notice_data = array();
    public $ppcp_paypal_country;
    public $subscription_support_enabled;
    public $is_vaulting_enable;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        try {
            if (is_angelleye_aws_down() == false) {
                $this->ppcp_host = PAYPAL_FOR_WOOCOMMERCE_PPCP_AWS_WEB_SERVICE;
            } else {
                $this->ppcp_host = PAYPAL_FOR_WOOCOMMERCE_PPCP_ANGELLEYE_WEB_SERVICE;
            }
            $this->angelleye_ppcp_load_class();
            $this->sandbox_partner_merchant_id = PAYPAL_PPCP_SANDBOX_PARTNER_MERCHANT_ID;
            $this->partner_merchant_id = PAYPAL_PPCP_PARTNER_MERCHANT_ID;
            //add_action('wc_ajax_ppcp_login_seller', array($this, 'angelleye_ppcp_login_seller'));
            add_action('admin_init', array($this, 'angelleye_ppcp_listen_for_merchant_id'));
            add_action('wp_ajax_angelleye_ppcp_onboard_email_sendy_subscription', array($this, 'angelleye_ppcp_onboard_email_sendy_subscription'));
            $this->ppcp_paypal_country = $this->dcc_applies->country();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function setTestMode($testMode = 'no') {
        $this->is_sandbox = $testMode === 'yes';
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('AngellEYE_PayPal_PPCP_DCC_Validate')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-dcc-validate.php');
            }
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Request')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-request.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Apple_Pay_Configurations')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/admin/class-angelleye-paypal-ppcp-apple-pay-configurations.php';
            }
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->dcc_applies = AngellEYE_PayPal_PPCP_DCC_Validate::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
            AngellEYE_PayPal_PPCP_Apple_Pay_Configurations::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function nonce() {
        return 'a1233wtergfsdt4365tzrshgfbaewa36AGa1233wtergfsdt4365tzrshgfbaewa36AG';
    }

    public function angelleye_generate_signup_link($testmode, $page) {
        $this->is_sandbox = ( $testmode === 'yes' ) ? true : false;
        $body = $this->default_data();
        if ($page === 'gateway_settings') {
            $body['return_url'] = add_query_arg(array('place' => 'gateway_settings', 'utm_nooverride' => '1'), untrailingslashit($body['return_url']));
        } else {
            $body['return_url'] = add_query_arg(array('place' => 'admin_settings_onboarding', 'utm_nooverride' => '1'), untrailingslashit($body['return_url']));
        }
        if ($this->is_sandbox) {
            $tracking_id = angelleye_key_generator();
            $body['tracking_id'] = $tracking_id;
            update_option('angelleye_ppcp_sandbox_tracking_id', $tracking_id);
        } else {
            $tracking_id = angelleye_key_generator();
            $body['tracking_id'] = $tracking_id;
            update_option('angelleye_ppcp_live_tracking_id', $tracking_id);
        }
        $host_url = $this->ppcp_host . 'generate-signup-link';
        $args = array(
            'method' => 'POST',
            'body' => wp_json_encode($body),
            'headers' => array('Content-Type' => 'application/json'),
        );
        return $this->api_request->request($host_url, $args, 'generate_signup_link');
    }

    public function angelleye_generate_signup_link_with_feature($testmode, $page, $body) {
        $this->is_sandbox = ( $testmode === 'yes' ) ? true : false;
        if ($page === 'gateway_settings') {
            $body['return_url'] = add_query_arg(array('place' => 'gateway_settings', 'utm_nooverride' => '1'), untrailingslashit($body['return_url']));
        } else {
            $body['return_url'] = add_query_arg(array('place' => 'admin_settings_onboarding', 'utm_nooverride' => '1'), untrailingslashit($body['return_url']));
        }
        if ($this->is_sandbox) {
            $tracking_id = angelleye_key_generator();
            $body['tracking_id'] = $tracking_id;
            update_option('angelleye_ppcp_sandbox_tracking_id', $tracking_id);
        } else {
            $tracking_id = angelleye_key_generator();
            $body['tracking_id'] = $tracking_id;
            update_option('angelleye_ppcp_live_tracking_id', $tracking_id);
        }
        $host_url = $this->ppcp_host . 'generate-signup-link';
        $args = array(
            'method' => 'POST',
            'body' => wp_json_encode($body),
            'headers' => array('Content-Type' => 'application/json'),
        );
        return $this->api_request->request($host_url, $args, 'generate_signup_link');
    }

    public function angelleye_generate_signup_link_for_migration($testmode, $products) {
        $this->is_sandbox = ( $testmode === 'yes' ) ? true : false;
        if ($this->ppcp_paypal_country === 'US' && angelleye_ppcp_is_subscription_support_enabled() === true) {
            $body = $this->ppcp_vault_data();
        } else {
            $body = $this->default_data();
        }
        $return_url = add_query_arg(array('place' => 'admin_settings_onboarding', 'utm_nooverride' => '1', 'products' => $products, 'is_migration' => 'yes'), untrailingslashit($body['return_url']));
        if(isset($_GET['is_found_diffrent_account'])) {
            $return_url = add_query_arg(array('do_not_check_diffrent_account' => 'yes'), untrailingslashit($return_url));
        }
        $body['return_url'] = $return_url;
        if ($this->is_sandbox) {
            $tracking_id = angelleye_key_generator();
            $body['tracking_id'] = $tracking_id;
            update_option('angelleye_ppcp_sandbox_tracking_id', $tracking_id);
        } else {
            $tracking_id = angelleye_key_generator();
            $body['tracking_id'] = $tracking_id;
            update_option('angelleye_ppcp_live_tracking_id', $tracking_id);
        }
        $host_url = $this->ppcp_host . 'generate-signup-link';
        $args = array(
            'method' => 'POST',
            'body' => wp_json_encode($body),
            'headers' => array('Content-Type' => 'application/json'),
        );
        return $this->api_request->request($host_url, $args, 'generate_signup_link');
    }

    private function default_data() {
        $testmode = ($this->is_sandbox) ? 'yes' : 'no';
        return array(
            'testmode' => $testmode,
            'return_url' => admin_url(
                    'admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp&testmode=' . $testmode
            ),
            'return_url_description' => __(
                    'Return to your shop.', 'paypal-for-woocommerce'
            ),
            'products' => array(
                $this->dcc_applies->for_country_currency() ? 'PPCP' : 'EXPRESS_CHECKOUT'
        ));
    }

    public function ppcp_apple_pay_data() {
        $testmode = ($this->is_sandbox) ? 'yes' : 'no';
        return array(
            'testmode' => $testmode,
            'return_url' => admin_url(
                    'admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp&feature_activated=applepay&testmode=' . $testmode
            ),
            'return_url_description' => __(
                    'Return to your shop.', 'paypal-for-woocommerce'
            ),
            'capabilities' => array(
                'APPLE_PAY'
            ),
            'third_party_features' => array('VAULT', 'BILLING_AGREEMENT'),
            'products' => array(
                $this->dcc_applies->for_country_currency() ? 'PPCP' : 'EXPRESS_CHECKOUT',
                'PAYMENT_METHODS'
        ));
    }

    public function ppcp_google_pay_data() {
        $testmode = ($this->is_sandbox) ? 'yes' : 'no';
        return array(
            'testmode' => $testmode,
            'return_url' => admin_url(
                'admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp&feature_activated=googlepay&testmode=' . $testmode
            ),
            'return_url_description' => __(
                'Return to your shop.', 'paypal-for-woocommerce'
            ),
            'capabilities' => array(
                'GOOGLE_PAY'
            ),
            'third_party_features' => array('VAULT', 'BILLING_AGREEMENT'),
            'products' => array(
                $this->dcc_applies->for_country_currency() ? 'PPCP' : 'EXPRESS_CHECKOUT',
                'PAYMENT_METHODS'
            ));
    }

    public function ppcp_vault_data() {
        $testmode = ($this->is_sandbox) ? 'yes' : 'no';
        return array(
            'testmode' => $testmode,
            'return_url' => admin_url(
                    'admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp&testmode=' . $testmode
            ),
            'return_url_description' => __(
                    'Return to your shop.', 'paypal-for-woocommerce'
            ),
            'capabilities' => array(
                'PAYPAL_WALLET_VAULTING_ADVANCED'
            ),
            'third_party_features' => array('VAULT', 'BILLING_AGREEMENT'),
            'products' => array(
                $this->dcc_applies->for_country_currency() ? 'PPCP' : 'EXPRESS_CHECKOUT',
                'ADVANCED_VAULTING'
        ));
    }

    public function angelleye_ppcp_login_seller() {
        try {
            $posted_raw = angelleye_ppcp_get_raw_data();
            $this->api_log->log('angelleye_ppcp_login_seller', 'error');
            $this->api_log->log(print_r($posted_raw, true), 'error');
            if (empty($posted_raw)) {
                return false;
            }
            $data = json_decode($posted_raw, true);
            $this->angelleye_ppcp_get_credentials($data);
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_credentials($data) {
        try {
            $this->is_sandbox = isset($data['env']) && 'sandbox' === $data['env'];
            $this->host = ($this->is_sandbox) ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
            $this->setting_obj->set('testmode', ($this->is_sandbox) ? 'yes' : 'no');
            $this->setting_obj->persist();
            if ($this->is_sandbox) {
                $this->setting_obj->set('enabled', 'yes');
            } else {
                $this->setting_obj->set('enabled', 'yes');
            }
            $this->setting_obj->persist();
            if ($this->is_sandbox) {
                set_transient('angelleye_ppcp_sandbox_seller_onboarding_process_done', 'yes', 29000);
            } else {
                set_transient('angelleye_ppcp_live_seller_onboarding_process_done', 'yes', 29000);
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_listen_for_merchant_id() {
        try {
            $this->is_sandbox = false;
            if (!$this->is_valid_site_request()) {
                return;
            }
            if (!isset($_GET['merchantIdInPayPal'])) {
                return;
            }
            if (!isset($_GET['testmode'])) {
                return;
            }
            if (isset($_GET['testmode']) && 'yes' === $_GET['testmode']) {
                $this->is_sandbox = true;
            }
            $this->setting_obj->set('enabled', 'yes');
            $this->setting_obj->set('testmode', ($this->is_sandbox) ? 'yes' : 'no');
            $this->host = ($this->is_sandbox) ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
            $merchant_id = sanitize_text_field(wp_unslash($_GET['merchantIdInPayPal']));
            if (isset($_GET['merchantId'])) {
                $merchant_email = sanitize_text_field(wp_unslash($_GET['merchantId']));
            } else {
                $merchant_email = '';
            }

            // Delete the transient so that system fetches the latest status after connecting the account
            delete_transient('ae_seller_onboarding_status');
            delete_option('ae_ppcp_account_reconnect_notice');

            $move_to_location = 'tokenization_subscriptions';
            if (isset($_GET['feature_activated'])) {
                switch ($_GET['feature_activated']) {
                    case 'applepay':
                        set_transient('angelleye_ppcp_applepay_onboarding_done', 'yes', 29000);
                        delete_transient('angelleye_apple_pay_domain_list_cache');
                        $move_to_location = 'apple_pay_authorizations';
                        break;
                    case 'googlepay':
                        set_transient('angelleye_ppcp_googlepay_onboarding_done', 'yes', 29000);
                        $move_to_location = 'google_pay_authorizations';
                        break;
                }
            }

            if ($this->is_sandbox) {
                $this->setting_obj->set('sandbox_merchant_id', $merchant_id);
                set_transient('angelleye_ppcp_sandbox_seller_onboarding_process_done', 'yes', 29000);
                $this->api_log->log("sandbox_merchant_id: " . $merchant_id, 'error');
            } else {
                $this->setting_obj->set('live_merchant_id', $merchant_id);
                set_transient('angelleye_ppcp_live_seller_onboarding_process_done', 'yes', 29000);
            }
            $this->setting_obj->set('enabled', 'yes');
            $this->setting_obj->persist();
            $seller_onboarding_status = $this->angelleye_get_seller_onboarding_status();
            if (isset($_GET['place']) && $_GET['place'] === 'gateway_settings') {
                $redirect_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp&move=' . $move_to_location);
            } else {
                $redirect_url = admin_url('options-general.php?page=paypal-for-woocommerce');
            }
            if (isset($_GET['is_migration']) && 'yes' === $_GET['is_migration'] && isset($_GET['products'])) {
                if (angelleye_ppcp_is_subscription_support_enabled() === true) {
                    $this->subscription_support_enabled = true;
                } else {
                    $this->subscription_support_enabled = false;
                }
                $this->is_vaulting_enable = angelleye_is_vaulting_enable($seller_onboarding_status);
                if ($this->subscription_support_enabled === false || ($this->subscription_support_enabled === true && $this->is_vaulting_enable === true )) {
                    $products = json_decode(stripslashes($_GET['products']), true);
                    if (!empty($products) && is_array($products)) {
                        if (!class_exists('AngellEYE_PayPal_PPCP_Migration')) {
                            include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-migration.php');
                        }
                        $this->ppcp_migration = AngellEYE_PayPal_PPCP_Migration::instance();
                        foreach ($products as $key => $product) {
                            switch ($product) {
                                case 'paypal_express':
                                    $existing_paypal_account_details = angelleye_ppcp_get_classic_paypal_details($product);
                                    if (isset($_GET['do_not_check_diffrent_account']) || (!empty($existing_paypal_account_details) && $existing_paypal_account_details === $merchant_id) || empty($existing_paypal_account_details)) {
                                        $this->ppcp_migration->angelleye_ppcp_paypal_express_to_ppcp($seller_onboarding_status);
                                        if ($this->subscription_support_enabled === true && $this->is_vaulting_enable === true) {
                                            $this->ppcp_migration->angelleye_ppcp_subscription_order_migration('paypal_express', 'angelleye_ppcp');
                                        }
                                    } else {
                                        if ($this->is_sandbox) {
                                            $this->setting_obj->set('sandbox_merchant_id', '');
                                            delete_transient('angelleye_ppcp_sandbox_seller_onboarding_process_done');
                                        } else {
                                            $this->setting_obj->set('live_merchant_id', '');
                                            delete_transient('angelleye_ppcp_live_seller_onboarding_process_done');
                                        }
                                        $this->setting_obj->set('enabled', 'no');
                                        $this->setting_obj->persist();
                                        unset($_GET);
                                        wp_redirect(add_query_arg(array('is_found_diffrent_account' => 'yes'), untrailingslashit($redirect_url)));
                                        exit();
                                    }
                                    break;
                                case 'paypal_pro':
                                    $existing_paypal_account_details = angelleye_ppcp_get_classic_paypal_details($product);
                                    if (isset($_GET['do_not_check_diffrent_account']) || (!empty($existing_paypal_account_details) && $existing_paypal_account_details === $merchant_id) || empty($existing_paypal_account_details)) {
                                        if (angelleye_is_acdc_payments_enable($seller_onboarding_status)) {
                                            $this->ppcp_migration->angelleye_ppcp_paypal_pro_to_ppcp($seller_onboarding_status);
                                            if ($this->subscription_support_enabled === true && $this->is_vaulting_enable === true) {
                                                $this->ppcp_migration->angelleye_ppcp_subscription_order_migration('paypal_pro', 'angelleye_ppcp_cc');
                                            }
                                        }
                                    } else {
                                        if ($this->is_sandbox) {
                                            $this->setting_obj->set('sandbox_merchant_id', '');
                                            delete_transient('angelleye_ppcp_sandbox_seller_onboarding_process_done');
                                        } else {
                                            $this->setting_obj->set('live_merchant_id', '');
                                            delete_transient('angelleye_ppcp_live_seller_onboarding_process_done');
                                        }
                                        $this->setting_obj->set('enabled', 'no');
                                        $this->setting_obj->persist();
                                        unset($_GET);
                                        wp_redirect(add_query_arg(array('is_found_diffrent_account' => 'yes'), untrailingslashit($redirect_url)));
                                        exit();
                                    }
                                    break;
                                case 'paypal_pro_payflow':
                                    if (angelleye_is_acdc_payments_enable($seller_onboarding_status)) {
                                        $this->ppcp_migration->angelleye_ppcp_paypal_pro_payflow_to_ppcp($seller_onboarding_status);
                                        if ($this->subscription_support_enabled === true && $this->is_vaulting_enable === true) {
                                            $this->ppcp_migration->angelleye_ppcp_subscription_order_migration('paypal_pro_payflow', 'angelleye_ppcp');
                                        }
                                    }
                                    break;
                                case 'paypal_advanced':
                                    if (angelleye_is_acdc_payments_enable($seller_onboarding_status)) {
                                        $this->ppcp_migration->angelleye_ppcp_paypal_advanced_to_ppcp($seller_onboarding_status);
                                        if ($this->subscription_support_enabled === true && $this->is_vaulting_enable === true) {
                                            $this->ppcp_migration->angelleye_ppcp_subscription_order_migration('paypal_advanced', 'angelleye_ppcp');
                                        }
                                    }
                                    break;
                                case 'paypal_credit_card_rest':
                                    if (angelleye_is_acdc_payments_enable($seller_onboarding_status)) {
                                        $this->ppcp_migration->angelleye_ppcp_paypal_credit_card_rest_to_ppcp($seller_onboarding_status);
                                    }
                                    break;
                                case 'paypal':
                                    $this->ppcp_migration->angelleye_ppcp_paypal_to_ppcp();
                                    if ($this->subscription_support_enabled === true && $this->is_vaulting_enable === true) {
                                        $this->ppcp_migration->angelleye_ppcp_subscription_order_migration('paypal', 'angelleye_ppcp');
                                    }
                                    break;
                                case 'ppec_paypal':
                                    $this->ppcp_migration->angelleye_ppcp_ppec_paypal_to_ppcp();
                                    if ($this->subscription_support_enabled === true && $this->is_vaulting_enable === true) {
                                        $this->ppcp_migration->angelleye_ppcp_subscription_order_migration('ppec_paypal', 'angelleye_ppcp');
                                    }
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                } else {
                    if (isset($seller_onboarding_status['country']) && 'US' === $seller_onboarding_status['country']) {
                        $this->angelleye_ppcp_migration_wizard_notice_data['error'][] = __('We see that you are running WooCommerce Subscriptions.<br>
                            Unfortunately, your PayPal account was not instantly approved for the PayPal Vault, which is required for Subscriptions functionality.<br>
                            As such, you will not be able to update to PayPal Commerce until your account is fully approved for the PayPal Vault feature.<br>
                            No changes have been made, and you can continue running PayPal Classic.<br>
                            Please try this wizard again in 2 - 3 business days.  If the problem persists, please submit a <a target="_blank" href="https://angelleye.atlassian.net/servicedesk/customer/portal/1/group/1/create/1">support ticket</a> and we can help you get it approved.');
                    } else {
                        $country = WC()->countries->countries[$country];
                        $this->angelleye_ppcp_migration_wizard_notice_data['error'][] = __('You are currently running WooCommerce Subscriptions.<br>
                            Unfortunately, the PayPal Vault feature (which is required for Subscriptions / Token Payments) is only currently available for United States PayPal accounts.<br>
                            Your PayPal account is based in ' . $country . '.  As such, you cannot upgrade to PayPal Commerce Platform at this time.<br>
                            Please look for future updates and details about when the PayPal Vault is available in your country.<br>
                            For now, you will remain on PayPal Classic.<br>
                            Feel free to submit a <a target="_blank" href="https://angelleye.atlassian.net/servicedesk/customer/portal/1/group/1/create/1">support ticket</a> if you have any questions or concerns.');
                    }
                    update_option($this->angelleye_ppcp_migration_wizard_notice_key, $this->angelleye_ppcp_migration_wizard_notice_data);
                }
                unset($_GET);
                wp_safe_redirect($redirect_url, 302);
                exit();
            }
            unset($_GET);
            wp_safe_redirect($redirect_url, 302);
            exit();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_get_seller_onboarding_status() {
        try {
            if ($this->is_sandbox) {
                $tracking_id = get_option('angelleye_ppcp_sandbox_tracking_id', '');
                $body['tracking_id'] = $tracking_id;
                $body['testmode'] = ($this->is_sandbox) ? 'yes' : 'no';
            } else {
                $tracking_id = get_option('angelleye_ppcp_live_tracking_id', '');
                $body['tracking_id'] = $tracking_id;
                $body['testmode'] = ($this->is_sandbox) ? 'yes' : 'no';
            }
            $args = array(
                'method' => 'POST',
                'body' => wp_json_encode($body),
                'headers' => array('Content-Type' => 'application/json'),
            );
            $host_url = $this->ppcp_host . 'get-tracking-status';
            $seller_onboarding_status = $this->api_request->request($host_url, $args, 'get_tracking_status');
            if (!isset($seller_onboarding_status['merchant_id'])) {
                $seller_onboarding_status['merchant_id'] = sanitize_text_field(wp_unslash($_GET['merchantIdInPayPal']));
            }
            if (!empty($seller_onboarding_status['merchant_id'])) {
                if ($this->is_sandbox) {
                    $this->setting_obj->set('sandbox_client_id', '');
                    $this->setting_obj->set('sandbox_api_secret', '');
                    $this->setting_obj->set('sandbox_merchant_id', $seller_onboarding_status['merchant_id']);
                    $this->setting_obj->set('enabled', 'yes');
                } else {
                    $this->setting_obj->set('api_client_id', '');
                    $this->setting_obj->set('api_secret', '');
                    $this->setting_obj->set('live_merchant_id', $seller_onboarding_status['merchant_id']);
                    $this->setting_obj->set('enabled', 'yes');
                }
                $this->setting_obj->persist();
                $this->result = $this->angelleye_track_seller_onboarding_status($seller_onboarding_status['merchant_id']);
                if (!empty($this->result['primary_email'])) {
                    own_angelleye_sendy_list($this->result['primary_email']);
                }
                if (angelleye_is_acdc_payments_enable($this->result)) {
                    $this->setting_obj->set('enable_advanced_card_payments', 'yes');
                } else {
                    $this->setting_obj->set('enable_advanced_card_payments', 'no');
                }
                if (angelleye_is_vaulting_enable($this->result)) {
                    $this->setting_obj->set('enable_tokenized_payments', 'yes');
                } else {
                    $this->setting_obj->set('enable_tokenized_payments', 'no');
                }

                // Enable these features only when someone returns from on-boarding, otherwise if someone will enable
                // any other feature then these will be activated based on on-boarding status, while user may don't want
                // to enable these
                if (isset($_GET['feature_activated'])) {
                    if ($this->angelleye_is_apple_pay_approved($this->result)) {
                        $_GET['feature_activated'] == 'applepay' && $this->setting_obj->set('enable_apple_pay', 'yes');
                    } else {
                        $this->setting_obj->set('enable_apple_pay', 'no');
                    }
                    if ($this->angelleye_is_google_pay_approved($this->result)) {
                        $_GET['feature_activated'] == 'googlepay' && $this->setting_obj->set('enable_google_pay', 'yes');
                    } else {
                        $this->setting_obj->set('enable_google_pay', 'no');
                    }
                }
                $this->setting_obj->persist();
                if ($this->angelleye_ppcp_is_fee_enable($this->result)) {
                    set_transient(AE_FEE, 'yes', 24 * DAY_IN_SECONDS);
                } else {
                    set_transient(AE_FEE, 'no', 24 * DAY_IN_SECONDS);
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return false;
        }
        return $this->result;
    }

    public function angelleye_track_seller_onboarding_status_from_cache($merchant_id, $force_refresh = false) {
        $seller_onboarding_status_transient = false;
        if (!$force_refresh) {
            $seller_onboarding_status_transient = get_transient('ae_seller_onboarding_status');
        }
        if (!$seller_onboarding_status_transient) {
            $this->is_sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
            $this->host = ($this->is_sandbox) ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
            if ($this->is_sandbox) {
                $partner_merchant_id = $this->sandbox_partner_merchant_id;
            } else {
                $partner_merchant_id = $this->partner_merchant_id;
            }
            try {
                $this->api_request = new AngellEYE_PayPal_PPCP_Request();
                $url = trailingslashit($this->host) .
                    'v1/customer/partners/' . $partner_merchant_id .
                    '/merchant-integrations/' . $merchant_id;
                $args = array(
                    'method' => 'GET',
                    'headers' => array(
                        'Authorization' => '',
                        'Content-Type' => 'application/json',
                    ),
                );
                $this->result = $this->api_request->request($url, $args, 'seller_onboarding_status');
                $seller_onboarding_status_transient = $this->result;
            } catch (Exception $ex) {
                $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
                $this->api_log->log($ex->getMessage(), 'error');
                $seller_onboarding_status_transient = [];
            }
        }
        set_transient('ae_seller_onboarding_status', $seller_onboarding_status_transient, DAY_IN_SECONDS);
        return $seller_onboarding_status_transient;
    }

    public function angelleye_track_seller_onboarding_status($merchant_id) {
        return $this->angelleye_track_seller_onboarding_status_from_cache($merchant_id, true);
    }

    public function is_valid_site_request() {
        if (!isset($_REQUEST['section']) || !in_array(sanitize_text_field(wp_unslash($_REQUEST['section'])), array('angelleye_ppcp'), true)) {
            return false;
        }
        if (!current_user_can('manage_options')) {
            return false;
        }
        return true;
    }

    public function angelleye_is_apple_pay_approved($result) {
        if (isset($result['products']) && isset($result['capabilities']) && !empty($result['products'])) {
            foreach ($result['products'] as $product) {
                if (isset($product['vetting_status']) && ('SUBSCRIBED' === $product['vetting_status'] || 'APPROVED' === $product['vetting_status']) && isset($product['capabilities']) && is_array($product['capabilities']) && in_array('APPLE_PAY', $product['capabilities'])) {
                    foreach ($result['capabilities'] as $key => $capabilities) {
                        if (isset($capabilities['name']) && 'APPLE_PAY' === $capabilities['name'] && 'ACTIVE' === $capabilities['status']) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function angelleye_is_google_pay_approved($result) {
        if (isset($result['products']) && isset($result['capabilities']) && !empty($result['products'])) {
            foreach ($result['products'] as $key => $product) {
                if (isset($product['vetting_status']) && ('SUBSCRIBED' === $product['vetting_status'] || 'APPROVED' === $product['vetting_status']) && isset($product['capabilities']) && is_array($product['capabilities']) && in_array('GOOGLE_PAY', $product['capabilities'])) {
                    foreach ($result['capabilities'] as $capabilities) {
                        if (isset($capabilities['name']) && 'GOOGLE_PAY' === $capabilities['name'] && 'ACTIVE' === $capabilities['status']) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function angelleye_ppcp_is_fee_enable($response) {
        try {
            if (!empty($response)) {
                if (isset($response['oauth_integrations']['0']['integration_type']) && 'OAUTH_THIRD_PARTY' === $response['oauth_integrations']['0']['integration_type']) {
                    if (isset($response['oauth_integrations']['0']['oauth_third_party']['0']['scopes']) && is_array($response['oauth_integrations']['0']['oauth_third_party']['0']['scopes'])) {
                        foreach ($response['oauth_integrations']['0']['oauth_third_party']['0']['scopes'] as $key => $scope) {
                            if (strpos($scope, 'payments/partnerfee') !== false) {
                                return true;
                            }
                        }
                    }
                }
            }
            return false;
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_onboard_email_sendy_subscription() {
        global $wp;
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $current_url = $_SERVER['HTTP_REFERER'];
        } else {
            $current_url = home_url(add_query_arg(array(), $wp->request));
        }
        $url = 'https://sendy.angelleye.com/subscribe';
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array('list' => 'oV0I12rDwJdMDL2jYzvwPQ',
                'boolean' => 'true',
                'email' => $_POST['email'],
                'gdpr' => 'true',
                'silent' => 'true',
                'api_key' => 'qFcoVlU2uG3AMYabNTrC',
                'referrer' => $current_url
            ),
            'cookies' => array()
                )
        );
        if (is_wp_error($response)) {
            wp_send_json(wp_remote_retrieve_body($response));
        } else {
            $body = wp_remote_retrieve_body($response);
            $apiResponse = strval($body);
            switch ($apiResponse) {
                case 'true':
                case '1':
                    prepareResponse("true", 'Thank you for subscribing!');
                case 'Already subscribed.':
                    prepareResponse("true", 'Already subscribed!');
                default:
                    prepareResponse("false", $apiResponse);
            }
        }
    }
}
