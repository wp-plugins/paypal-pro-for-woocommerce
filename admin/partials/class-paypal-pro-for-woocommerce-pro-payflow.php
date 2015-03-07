<?php

/**
 * @class       MBJ_PayPal_Pro_WooCommerce_Pro_Payflow
 * @version	1.0.0
 * @package	paypal-pro-for-woocommerce
 * @category	Class
 * @author      johnny manziel <phpwebcreators@gmail.com>
 */
class MBJ_PayPal_Pro_WooCommerce_Pro_Payflow extends WC_Payment_Gateway {

    /**
     * @since    1.0.0
     */
    public function __construct() {
        $this->id = 'paypal_pro_payflow';
        $this->method_title = __('PayPal Pro PayFlow', 'paypal-pro-for-woocommerce');
        $this->method_description = __('PayPal Pro PayFlow Edition works by adding credit card fields on the checkout and then sending the details to PayPal for verification.', 'paypal-pro-for-woocommerce');
        $this->icon = apply_filters('woocommerce_paypal_pro_payflow_icon', WP_PLUGIN_URL . "/" . plugin_basename(dirname(dirname(__FILE__))) . '/admin/assets/images/cards.png');
        $this->has_fields = true;
        $this->supports = array(
            'products',
            'refunds'
        );
        $this->liveurl = 'https://payflowpro.paypal.com';
        $this->testurl = 'https://pilot-payflowpro.paypal.com';
        $this->allowed_currencies = apply_filters('woocommerce_paypal_pro_allowed_currencies', array('USD', 'EUR', 'GBP', 'CAD', 'JPY', 'AUD'));


        $this->init_form_fields();


        $this->init_settings();


        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->paypal_vendor = $this->get_option('paypal_vendor');
        $this->paypal_partner = $this->get_option('paypal_partner', 'PayPal');
        $this->paypal_password = trim($this->get_option('paypal_password'));
        $this->paypal_user = $this->get_option('paypal_user', $this->paypal_vendor);
        $this->testmode = $this->get_option('testmode') === "yes" ? true : false;
        $this->debug = $this->get_option('debug', "no") === "yes" ? true : false;
        $this->transparent_redirect = $this->get_option('transparent_redirect') === "yes" ? true : false;
        $this->soft_descriptor = str_replace(' ', '-', preg_replace('/[^A-Za-z0-9\-\.]/', '', $this->get_option('soft_descriptor', "")));
        $this->paymentaction = $this->get_option('paypal_pro_payflow_paymentaction', 'S');

        if ($this->transparent_redirect) {
            $this->order_button_text = __('Enter payment details', 'paypal-pro-for-woocommerce');
        }


        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_paypal_pro_payflow', array($this, 'receipt_page'));
        add_action('woocommerce_api_wc_gateway_paypal_pro_payflow', array($this, 'return_handler'));
    }

    /**
     * @since    1.0.0
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paypal-pro-for-woocommerce'),
                'label' => __('Enable PayPal Pro Payflow Edition', 'paypal-pro-for-woocommerce'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'paypal-pro-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'paypal-pro-for-woocommerce'),
                'default' => __('Credit card (PayPal)', 'paypal-pro-for-woocommerce'),
                'desc_tip' => true
            ),
            'description' => array(
                'title' => __('Description', 'paypal-pro-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-pro-for-woocommerce'),
                'default' => __('Pay with your credit card.', 'paypal-pro-for-woocommerce'),
                'desc_tip' => true
            ),
            'soft_descriptor' => array(
                'title' => __('Soft Descriptor', 'paypal-pro-for-woocommerce'),
                'type' => 'text',
                'description' => __('(Optional) Information that is usually displayed in the account holder\'s statement, for example your website name. Only 23 alphanumeric characters can be included, including the special characters dash (-) and dot (.) . Asterisks (*) and spaces ( ) are NOT permitted.', 'paypal-pro-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'custom_attributes' => array(
                    'maxlength' => 23,
                    'pattern' => '[a-zA-Z0-9.-]+'
                )
            ),
            'testmode' => array(
                'title' => __('Test Mode', 'paypal-pro-for-woocommerce'),
                'label' => __('Enable PayPal Sandbox/Test Mode', 'paypal-pro-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in development mode.', 'paypal-pro-for-woocommerce'),
                'default' => 'no',
                'desc_tip' => true
            ),
            'transparent_redirect' => array(
                'title' => __('Transparent Redirect', 'paypal-pro-for-woocommerce'),
                'label' => __('Enable Transparent Redirect', 'paypal-pro-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Rather than showing a credit card form on your checkout, this shows the form on it\'s own page and posts straight to PayPal, thus making the process more secure and more PCI friendly. "Enable Secure Token" needs to be enabled on your PayFlow account to work.', 'paypal-pro-for-woocommerce'),
                'default' => 'no',
                'desc_tip' => true
            ),
            'paypal_vendor' => array(
                'title' => __('PayPal Vendor', 'paypal-pro-for-woocommerce'),
                'type' => 'text',
                'description' => __('Your merchant login ID that you created when you registered for the account.', 'paypal-pro-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'paypal_password' => array(
                'title' => __('PayPal Password', 'paypal-pro-for-woocommerce'),
                'type' => 'password',
                'description' => __('The password that you defined while registering for the account.', 'paypal-pro-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'paypal_user' => array(
                'title' => __('PayPal User', 'paypal-pro-for-woocommerce'),
                'type' => 'text',
                'description' => __('If you set up one or more additional users on the account, this value is the ID
			of the user authorized to process transactions. Otherwise, leave this field blank.', 'paypal-pro-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'paypal_partner' => array(
                'title' => __('PayPal Partner', 'paypal-pro-for-woocommerce'),
                'type' => 'text',
                'description' => __('The ID provided to you by the authorized PayPal Reseller who registered you
			for the Payflow SDK. If you purchased your account directly from PayPal, use PayPal or leave blank.', 'paypal-pro-for-woocommerce'),
                'default' => 'PayPal',
                'desc_tip' => true
            ),
            'paypal_pro_payflow_paymentaction' => array(
                'title' => __('Payment Action', 'paypal-pro-for-woocommerce'),
                'type' => 'select',
                'description' => __('Choose whether you wish to capture funds immediately or authorize payment only.', 'paypal-pro-for-woocommerce'),
                'default' => 'sale',
                'desc_tip' => true,
                'options' => array(
                    'S' => __('Capture', 'paypal-pro-for-woocommerce'),
                    'A' => __('Authorize', 'paypal-pro-for-woocommerce')
                )
            ),
            'debug' => array(
                'title' => __('Debug Log', 'paypal-pro-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'paypal-pro-for-woocommerce'),
                'default' => 'no',
                'desc_tip' => true,
                'description' => __('Log PayPal Pro (Payflow) events inside <code>woocommerce/logs/paypal-pro-payflow.txt</code>', 'paypal-pro-for-woocommerce'),
            )
        );
    }

    /**
     * @since    1.0.0
     */
    public function is_available() {
        if ($this->enabled === "yes") {

            if (!is_ssl() && !$this->testmode) {
                return false;
            }


            if (!in_array(get_option('woocommerce_currency'), $this->allowed_currencies)) {
                return false;
            }


            if (!$this->paypal_vendor || !$this->paypal_password) {
                return false;
            }

            return true;
        }
        return false;
    }

    /**
     * @since    1.0.0
     */
    public function process_payment($order_id) {
        $order = new WC_Order($order_id);

        $this->log('Processing order #' . $order_id);

        if ($this->transparent_redirect) {

            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        } else {
            $card_number = isset($_POST['paypal_pro_payflow-card-number']) ? wc_clean($_POST['paypal_pro_payflow-card-number']) : '';
            $card_cvc = isset($_POST['paypal_pro_payflow-card-cvc']) ? wc_clean($_POST['paypal_pro_payflow-card-cvc']) : '';
            $card_expiry = isset($_POST['paypal_pro_payflow-card-expiry']) ? wc_clean($_POST['paypal_pro_payflow-card-expiry']) : '';


            $card_number = str_replace(array(' ', '-'), '', $card_number);
            $card_expiry = array_map('trim', explode('/', $card_expiry));
            $card_exp_month = str_pad($card_expiry[0], 2, "0", STR_PAD_LEFT);
            $card_exp_year = $card_expiry[1];

            if (strlen($card_exp_year) == 4) {
                $card_exp_year = $card_exp_year - 2000;
            }
            return $this->do_payment($order, $card_number, $card_exp_month . $card_exp_year, $card_cvc);
        }
    }

    /**
     * @since    1.0.0
     */
    public function receipt_page($order_id) {
        if ($this->transparent_redirect) {

            wp_enqueue_script('jquery-payment');
            $order = new WC_Order($order_id);
            $url = $this->testmode ? 'https://pilot-payflowlink.paypal.com' : 'https://payflowlink.paypal.com';
            $post_data = $this->get_post_data($order);
            $token = $this->get_token($order, $post_data);

            if (!$token) {
                wc_print_notices();
                return;
            }

            echo wpautop(__('Enter your payment details below and click "Confirm and pay" to securely pay for your order.', 'paypal-pro-for-woocommerce'));
            ?>
            <form method="POST" action="<?php echo $url; ?>">
                <div id="payment">
                    <label style="padding:10px 0 0 10px;display:block;"><?php echo $this->title . ' ' . '<div style="vertical-align:middle;display:inline-block;margin:2px 0 0 .5em;">' . $this->get_icon() . '</div>'; ?></label>
                    <div class="payment_box">
                        <p><?php echo $this->description . ( $this->testmode ? ' ' . __('TEST/SANDBOX MODE ENABLED. In test mode, you can use the card number 4111111111111111 with any CVC and a valid expiration date.', 'paypal-pro-for-woocommerce') : '' ); ?></p>

                        <fieldset id="paypal_pro_payflow-cc-form">
                            <p class="form-row form-row-wide">
                                <label for="paypal_pro_payflow-card-number"><?php _e('Card Number ', 'paypal-pro-for-woocommerce'); ?><span class="required">*</span></label>
                                <input type="text" id="paypal_pro_payflow-card-number" class="input-text wc-credit-card-form-card-number" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="CARDNUM" />
                            </p>

                            <p class="form-row form-row-first">
                                <label for="paypal_pro_payflow-card-expiry"><?php _e('Expiry (MM/YY) ', 'paypal-pro-for-woocommerce'); ?><span class="required">*</span></label>
                                <input type="text" id="paypal_pro_payflow-card-expiry" class="input-text wc-credit-card-form-card-expiry" autocomplete="off" placeholder="MM / YY" name="EXPDATE" />
                            </p>

                            <p class="form-row form-row-last">
                                <label for="paypal_pro_payflow-card-cvc"><?php _e('Card Code ', 'paypal-pro-for-woocommerce'); ?><span class="required">*</span></label>
                                <input type="text" id="paypal_pro_payflow-card-cvc" class="input-text wc-credit-card-form-card-cvc" autocomplete="off" placeholder="CVC" name="CVV2" />
                            </p>

                            <input type="hidden" name="SECURETOKEN" value="<?php echo esc_attr($token['SECURETOKEN']); ?>" />
                            <input type="hidden" name="SECURETOKENID" value="<?php echo esc_attr($token['SECURETOKENID']); ?>" />
                            <input type="hidden" name="SILENTTRAN" value="TRUE" />					
                        </fieldset>
                    </div>
                    <input type="submit" value="<?php _e('Confirm and pay', 'paypal-pro-for-woocommerce'); ?>" class="submit buy button" style="float:right;"/>
                </div>
                <script type="text/javascript">
                    jQuery(function($) {
                        $('.wc-credit-card-form-card-number').payment('formatCardNumber');
                        $('.wc-credit-card-form-card-expiry').payment('formatCardExpiry');
                        $('.wc-credit-card-form-card-cvc').payment('formatCardCVC');
                    });
                </script>
            </form>
            <?php
        }
    }

    /**
     * @since    1.0.0
     */
    public function return_handler() {

        @ob_clean();

        header('HTTP/1.1 200 OK');

        $result = isset($_POST['RESULT']) ? absint($_POST['RESULT']) : null;
        $order_id = isset($_POST['INVOICE']) ? absint(ltrim($_POST['INVOICE'], '#')) : 0;

        if (is_null($result) || empty($order_id)) {
            echo "Invalid request.";
            exit;
        }

        $order = new WC_Order($order_id);

        switch ($result) {

            case 0 :
            case 127 :
                $txn_id = (!empty($_POST['PNREF']) ) ? wc_clean($_POST['PNREF']) : '';

                $details = $this->get_transaction_details($txn_id);
                if ($details && strtolower($details['TRANSSTATE']) === '3') {

                    update_post_meta($order->id, '_paypalpro_charge_captured', 'no');
                    add_post_meta($order->id, '_transaction_id', $txn_id, true);
                    $order->update_status('on-hold', sprintf(__('PayPal Pro (PayFlow) charge authorized (Charge ID: %s). Process order to take payment, or cancel to remove the pre-authorization.', 'paypal-pro-for-woocommerce'), $txn_id));
                    $order->reduce_order_stock();
                } else {
                    $order->add_order_note(sprintf(__('PayPal Pro (Payflow) payment completed (PNREF: %s)', 'paypal-pro-for-woocommerce'), $parsed_response['PNREF']));
                    $order->payment_complete($txn_id);
                }

                WC()->cart->empty_cart();
                $redirect = $order->get_checkout_order_received_url();
                break;

            case 126 :
                $order->add_order_note($_POST['RESPMSG']);
                $order->add_order_note($_POST['PREFPSMSG']);
                $order->update_status('on-hold', __('The payment was flagged by a fraud filter. Please check your PayPal Manager account to review and accept or deny the payment and then mark this order "processing" or "cancelled".', 'paypal-pro-for-woocommerce'));
                WC()->cart->empty_cart();
                $redirect = $order->get_checkout_order_received_url();
                break;
            default :

                $order->update_status('failed', $_POST['RESPMSG']);

                $redirect = $order->get_checkout_payment_url(true);
                $redirect = add_query_arg('wc_error', urlencode(wp_kses_post($_POST['RESPMSG'])), $redirect);

                if (is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes') {
                    $redirect = str_replace('http:', 'https:', $redirect);
                }
                break;
        }

        wp_redirect($redirect);
        exit;
    }

    /**
     * @since    1.0.0
     */
    public function get_token($order, $post_data, $force_new_token = false) {
        if (!$force_new_token && get_post_meta($order->id, '_SECURETOKENHASH', true) == md5(json_encode($post_data))) {
            return array(
                'SECURETOKEN' => get_post_meta($order->id, '_SECURETOKEN', true),
                'SECURETOKENID' => get_post_meta($order->id, '_SECURETOKENID', true)
            );
        }
        $post_data['SECURETOKENID'] = uniqid() . md5($order->order_key);
        $post_data['CREATESECURETOKEN'] = 'Y';
        $post_data['SILENTTRAN'] = 'TRUE';
        $post_data['ERRORURL'] = WC()->api_request_url(get_class());
        $post_data['RETURNURL'] = WC()->api_request_url(get_class());
        $post_data['URLMETHOD'] = 'POST';

        $response = wp_remote_post($this->testmode ? $this->testurl : $this->liveurl, array(
            'method' => 'POST',
            'body' => urldecode(http_build_query(apply_filters('paypal-pro-for-woocommerce_payflow_request', $post_data, $order), null, '&')),
            'timeout' => 70,
            'sslverify' => false,
            'user-agent' => 'WooCommerce',
            'httpversion' => '1.1'
        ));

        if (is_wp_error($response)) {
            wc_add_notice(__('There was a problem connecting to the payment gateway.', 'paypal-pro-for-woocommerce'));
            return false;
        }

        if (empty($response['body'])) {
            wc_add_notice(__('Empty Paypal response.', 'paypal-pro-for-woocommerce'));
            return false;
        }

        parse_str($response['body'], $parsed_response);

        if (isset($parsed_response['RESULT']) && in_array($parsed_response['RESULT'], array(160, 161, 162))) {
            return $this->get_token($order, $post_data, $force_new_token);
        } elseif (isset($parsed_response['RESULT']) && $parsed_response['RESULT'] == 0 && !empty($parsed_response['SECURETOKEN'])) {
            update_post_meta($order->id, '_SECURETOKEN', $parsed_response['SECURETOKEN']);
            update_post_meta($order->id, '_SECURETOKENID', $parsed_response['SECURETOKENID']);
            update_post_meta($order->id, '_SECURETOKENHASH', md5(json_encode($post_data)));

            return array(
                'SECURETOKEN' => $parsed_response['SECURETOKEN'],
                'SECURETOKENID' => $parsed_response['SECURETOKENID']
            );
        } else {
            $order->update_status('failed', __('PayPal Pro (Payflow) token generation failed: ', 'paypal-pro-for-woocommerce') . '(' . $parsed_response['RESULT'] . ') ' . '"' . $parsed_response['RESPMSG'] . '"');

            wc_add_notice(__('Payment error:', 'paypal-pro-for-woocommerce') . ' ' . $parsed_response['RESPMSG'], 'error');

            return false;
        }
    }

    /**
     * @since    1.0.0
     */
    public function get_post_data($order) {
        $post_data = array();
        $post_data['USER'] = $this->paypal_user;
        $post_data['VENDOR'] = $this->paypal_vendor;
        $post_data['PARTNER'] = $this->paypal_partner;
        $post_data['PWD'] = $this->paypal_password;
        $post_data['TENDER'] = 'C';
        $post_data['TRXTYPE'] = $this->paymentaction;
        $post_data['AMT'] = $order->get_total();
        $post_data['CURRENCY'] = $order->get_order_currency();
        $post_data['CUSTIP'] = $this->get_user_ip();
        $post_data['EMAIL'] = $order->billing_email;
        $post_data['INVNUM'] = $order->get_order_number();
        $post_data['BUTTONSOURCE'] = 'mbjtechnolabs_SP';

        if ($this->soft_descriptor) {
            $post_data['MERCHDESCR'] = $this->soft_descriptor;
        }


        $item_loop = 0;

        if (sizeof($order->get_items()) > 0) {

            $ITEMAMT = 0;

            foreach ($order->get_items() as $item) {
                $_product = $order->get_product_from_item($item);
                if ($item['qty']) {
                    $post_data['L_NAME' . $item_loop] = $item['name'];
                    $post_data['L_COST' . $item_loop] = $order->get_item_total($item, true);
                    $post_data['L_QTY' . $item_loop] = $item['qty'];

                    if ($_product->get_sku()) {
                        $post_data['L_SKU' . $item_loop] = $_product->get_sku();
                    }

                    $ITEMAMT += $order->get_item_total($item, true) * $item['qty'];

                    $item_loop++;
                }
            }


            if (( $order->get_total_shipping() + $order->get_shipping_tax() ) > 0) {
                $post_data['L_NAME' . $item_loop] = 'Shipping';
                $post_data['L_DESC' . $item_loop] = 'Shipping and shipping taxes';
                $post_data['L_COST' . $item_loop] = $order->get_total_shipping() + $order->get_shipping_tax();
                $post_data['L_QTY' . $item_loop] = 1;

                $ITEMAMT += $order->get_total_shipping() + $order->get_shipping_tax();

                $item_loop++;
            }


            if ($order->get_order_discount() > 0) {
                $post_data['L_NAME' . $item_loop] = 'Order Discount';
                $post_data['L_DESC' . $item_loop] = 'Discounts after tax';
                $post_data['L_COST' . $item_loop] = '-' . $order->get_order_discount();
                $post_data['L_QTY' . $item_loop] = 1;

                $item_loop++;
            }

            $ITEMAMT = round($ITEMAMT, 2);

            if (absint($order->get_total() * 100) !== absint($ITEMAMT * 100)) {
                $post_data['L_NAME' . $item_loop] = 'Rounding amendment';
                $post_data['L_DESC' . $item_loop] = 'Correction if rounding is off (this can happen with tax inclusive prices)';
                $post_data['L_COST' . $item_loop] = ( absint($order->get_total() * 100) - absint($ITEMAMT * 100) ) / 100;
                $post_data['L_QTY' . $item_loop] = 1;
            }

            $post_data['ITEMAMT'] = $order->get_total();
        }

        $post_data['ORDERDESC'] = 'Order ' . $order->get_order_number() . ' on ' . get_bloginfo('name');
        $post_data['FIRSTNAME'] = $order->billing_first_name;
        $post_data['LASTNAME'] = $order->billing_last_name;
        $post_data['STREET'] = $order->billing_address_1 . ' ' . $order->billing_address_2;
        $post_data['CITY'] = $order->billing_city;
        $post_data['STATE'] = $order->billing_state;
        $post_data['COUNTRY'] = $order->billing_country;
        $post_data['ZIP'] = $order->billing_postcode;

        if ($order->shipping_address_1) {
            $post_data['SHIPTOFIRSTNAME'] = $order->shipping_first_name;
            $post_data['SHIPTOLASTNAME'] = $order->shipping_last_name;
            $post_data['SHIPTOSTREET'] = $order->shipping_address_1;
            $post_data['SHIPTOCITY'] = $order->shipping_city;
            $post_data['SHIPTOSTATE'] = $order->shipping_state;
            $post_data['SHIPTOCOUNTRY'] = $order->shipping_country;
            $post_data['SHIPTOZIP'] = $order->shipping_postcode;
        }

        return $post_data;
    }

    /**
     * @since    1.0.0
     */
    public function do_payment($order, $card_number, $card_exp, $card_cvc) {


        try {
            $url = $this->testmode ? $this->testurl : $this->liveurl;
            $post_data = $this->get_post_data($order);
            $post_data['ACCT'] = $card_number;
            $post_data['EXPDATE'] = $card_exp;
            $post_data['CVV2'] = $card_cvc;

            if ($this->debug) {
                $log = $post_data;
                $log['ACCT'] = '****';
                $log['CVV2'] = '****';
                $this->log('Do payment request ' . print_r($log, true));
            }

            $response = wp_remote_post($url, array(
                'method' => 'POST',
                'body' => urldecode(http_build_query(apply_filters('paypal-pro-for-woocommerce_payflow_request', $post_data, $order), null, '&')),
                'timeout' => 70,
                'sslverify' => false,
                'user-agent' => 'WooCommerce',
                'httpversion' => '1.1'
            ));

            if (is_wp_error($response)) {
                $this->log('Error ' . print_r($response->get_error_message(), true));

                throw new Exception(__('There was a problem connecting to the payment gateway.', 'paypal-pro-for-woocommerce'));
            }

            if (empty($response['body'])) {
                $this->log('Empty response!');

                throw new Exception(__('Empty Paypal response.', 'paypal-pro-for-woocommerce'));
            }

            parse_str($response['body'], $parsed_response);

            $this->log('Parsed Response ' . print_r($parsed_response, true));

            if (isset($parsed_response['RESULT']) && in_array($parsed_response['RESULT'], array(0, 126, 127))) {

                switch ($parsed_response['RESULT']) {

                    case 0 :
                    case 127 :
                        $txn_id = (!empty($parsed_response['PNREF']) ) ? wc_clean($parsed_response['PNREF']) : '';


                        $details = $this->get_transaction_details($txn_id);


                        if ($details && strtolower($details['TRANSSTATE']) === '3') {

                            update_post_meta($order->id, '_paypalpro_charge_captured', 'no');
                            add_post_meta($order->id, '_transaction_id', $txn_id, true);


                            $order->update_status('on-hold', sprintf(__('PayPal Pro (PayFlow) charge authorized (Charge ID: %s). Process order to take payment, or cancel to remove the pre-authorization.', 'paypal-pro-for-woocommerce'), $txn_id));


                            $order->reduce_order_stock();
                        } else {


                            $order->add_order_note(sprintf(__('PayPal Pro (Payflow) payment completed (PNREF: %s)', 'paypal-pro-for-woocommerce'), $parsed_response['PNREF']));


                            $order->payment_complete($txn_id);
                        }


                        WC()->cart->empty_cart();
                        break;

                    case 126 :
                        $order->add_order_note($parsed_response['RESPMSG']);
                        $order->add_order_note($parsed_response['PREFPSMSG']);
                        $order->update_status('on-hold', __('The payment was flagged by a fraud filter. Please check your PayPal Manager account to review and accept or deny the payment and then mark this order "processing" or "cancelled".', 'paypal-pro-for-woocommerce'));
                        break;
                }

                $redirect = $order->get_checkout_order_received_url();


                return array(
                    'result' => 'success',
                    'redirect' => $redirect
                );
            } else {


                $order->update_status('failed', __('PayPal Pro (Payflow) payment failed. Payment was rejected due to an error: ', 'paypal-pro-for-woocommerce') . '(' . $parsed_response['RESULT'] . ') ' . '"' . $parsed_response['RESPMSG'] . '"');

                wc_add_notice(__('Payment error:', 'paypal-pro-for-woocommerce') . ' ' . $parsed_response['RESPMSG'], 'error');
                return;
            }
        } catch (Exception $e) {
            wc_add_notice(__('Connection error:', 'paypal-pro-for-woocommerce') . ': "' . $e->getMessage() . '"', 'error');
            return;
        }
    }

    /**
     * @since    1.0.0
     */
    public function get_transaction_details($transaction_id = 0) {
        $url = $this->testmode ? $this->testurl : $this->liveurl;

        $post_data = array();
        $post_data['USER'] = $this->paypal_user;
        $post_data['VENDOR'] = $this->paypal_vendor;
        $post_data['PARTNER'] = $this->paypal_partner;
        $post_data['PWD'] = $this->paypal_password;
        $post_data['TRXTYPE'] = 'I';
        $post_data['ORIGID'] = $transaction_id;

        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'body' => urldecode(http_build_query($post_data, null, '&')),
            'timeout' => 70,
            'sslverify' => false,
            'user-agent' => 'WooCommerce',
            'httpversion' => '1.1'
        ));

        if (is_wp_error($response)) {
            $this->log('Error ' . print_r($response->get_error_message(), true));

            throw new Exception(__('There was a problem connecting to the payment gateway.', 'paypal-pro-for-woocommerce'));
        }

        parse_str($response['body'], $parsed_response);

        if ($parsed_response['RESULT'] === '0') {
            return $parsed_response;
        }

        return false;
    }

    /**
     * @since    1.0.0
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);

        $url = $this->testmode ? $this->testurl : $this->liveurl;

        if (!$order || !$order->get_transaction_id() || !$this->paypal_user || !$this->paypal_vendor || !$this->paypal_password) {
            return false;
        }


        $details = $this->get_transaction_details($order->get_transaction_id());


        if ($details && strtolower($details['TRANSSTATE']) === '3') {
            $order->add_order_note(__('This order cannot be refunded due to an authorized only transaction.  Please use cancel instead.', 'paypal-pro-for-woocommerce'));

            $this->log('Refund order # ' . $order_id . ': authorized only transactions need to use cancel/void instead.');

            throw new Exception(__('This order cannot be refunded due to an authorized only transaction.  Please use cancel instead.', 'paypal-pro-for-woocommerce'));
        }

        $post_data = array();
        $post_data['USER'] = $this->paypal_user;
        $post_data['VENDOR'] = $this->paypal_vendor;
        $post_data['PARTNER'] = $this->paypal_partner;
        $post_data['PWD'] = $this->paypal_password;
        $post_data['TRXTYPE'] = 'C'; // credit/refund
        $post_data['ORIGID'] = $order->get_transaction_id();

        if (!is_null($amount)) {
            $post_data['AMT'] = number_format($amount, 2, '.', '');
            $post_data['CURRENCY'] = $order->get_order_currency();
        }

        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }

            $post_data['COMMENT1'] = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }

        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'body' => urldecode(http_build_query($post_data, null, '&')),
            'timeout' => 70,
            'sslverify' => false,
            'user-agent' => 'WooCommerce',
            'httpversion' => '1.1'
        ));

        parse_str($response['body'], $parsed_response);

        if (is_wp_error($response)) {
            $this->log('Error ' . print_r($response->get_error_message(), true));

            throw new Exception(__('There was a problem connecting to the payment gateway.', 'paypal-pro-for-woocommerce'));
        } elseif ($parsed_response['RESULT'] !== '0') {
            // log it
            $this->log('Parsed Response (refund) ' . print_r($parsed_response, true));
        } else {

            $order->add_order_note(sprintf(__('Refunded %s - PNREF: %s', 'paypal-pro-for-woocommerce'), wc_price(number_format($amount, 2, '.', '')), $parsed_response['PNREF']));

            return true;
        }

        return false;
    }

    /**
     * @since    1.0.0
     */
    public function payment_fields() {
        if ($this->description) {
            if ($this->transparent_redirect) {
                echo '<p>' . $this->description . '</p>';
            } else {
                echo '<p>' . $this->description . ( $this->testmode ? ' ' . __('TEST/SANDBOX MODE ENABLED. In test mode, you can use the card number 4111111111111111 with any CVC and a valid expiration date.', 'paypal-pro-for-woocommerce') : '' ) . '</p>';
            }
        }
        if (!$this->transparent_redirect) {
            $this->credit_card_form();
        }
    }

    /**
     * @since    1.0.0
     */
    public function get_user_ip() {
        return !empty($_SERVER['HTTP_X_FORWARD_FOR']) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @since    1.0.0
     */
    public function log($message) {
        if ($this->debug) {
            if (!isset($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add('paypal-pro-payflow', $message);
        }
    }

}
