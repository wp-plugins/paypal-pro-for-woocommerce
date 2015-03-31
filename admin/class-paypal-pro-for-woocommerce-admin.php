<?php

/**
 * @class       MBJ_PayPal_Pro_WooCommerce_Admin
 * @version	1.0.0
 * @package	paypal-pro-for-woocommerce
 * @category	Class
 * @author      johnny manziel <phpwebcreators@gmail.com>
 */
class MBJ_PayPal_Pro_WooCommerce_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function load_plugin_extend_lib() {
        if (!class_exists('WC_Payment_Gateway'))
            return;

        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/class-paypal-pro-for-woocommerce-pro-payflow.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/class-paypal-pro-for-woocommerce-pro.php';
    }

    public function register_gateway($methods) {
        $methods[] = 'MBJ_PayPal_Pro_WooCommerce_Pro';
        $methods[] = 'MBJ_PayPal_Pro_WooCommerce_Pro_Payflow';

        return $methods;
    }

    public function ssl_check() {
        $settings = get_option('woocommerce_paypal_pro_settings', array());

        // Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected
        if (get_option('woocommerce_force_ssl_checkout') === 'no' && !class_exists('WordPressHTTPS') && isset($settings['enabled']) && $settings['enabled'] === 'yes' && $settings['testmode'] !== 'yes'
        ) {
            echo '<div class="error"><p>' . sprintf(__('PayPal Pro requires that the <a href="%s">Force secure checkout</a> option is enabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - PayPal Pro will only work in test mode.', 'paypal-pro-for-woocommerce'), admin_url('admin.php?page=woocommerce')) . '</p></div>';
        }

        return true;
    }

    public function capture_payment($order_id) {
        $order = new WC_Order($order_id);

        $txn_id = get_post_meta($order_id, '_transaction_id', true);
        $captured = get_post_meta($order_id, '_paypalpro_charge_captured', true);

        if ($order->payment_method === 'paypal_pro' && $txn_id && $captured === 'no') {

            $paypalpro = new MBJ_PayPal_Pro_WooCommerce_Pro();

            $url = $paypalpro->testmode ? $paypalpro->testurl : $paypalpro->liveurl;

            $post_data = array(
                'VERSION' => $paypalpro->api_version,
                'SIGNATURE' => $paypalpro->api_signature,
                'USER' => $paypalpro->api_username,
                'PWD' => $paypalpro->api_password,
                'METHOD' => 'DoCapture',
                'AUTHORIZATIONID' => $txn_id,
                'AMT' => $order->get_total(),
                'COMPLETETYPE' => 'Complete'
            );

            if ($paypalpro->soft_descriptor) {
                $post_data['SOFTDESCRIPTOR'] = $paypalpro->soft_descriptor;
            }

            $response = wp_remote_post($url, array(
                'method' => 'POST',
                'headers' => array(
                    'PAYPAL-NVP' => 'Y'
                ),
                'body' => $post_data,
                'timeout' => 70,
                'sslverify' => false,
                'user-agent' => 'WooCommerce',
                'httpversion' => '1.1'
            ));

            if (is_wp_error($response)) {
                $order->add_order_note(__('Unable to capture charge!', 'paypal-pro-for-woocommerce') . ' ' . $response->get_error_message());
            } else {
                parse_str($response['body'], $parsed_response);

                $order->add_order_note(sprintf(__('PayPal Pro charge complete (Transaction ID: %s)', 'paypal-pro-for-woocommerce'), $parsed_response['TRANSACTIONID']));

                update_post_meta($order->id, '_paypalpro_charge_captured', 'yes');

                // update the transaction ID of the capture
                update_post_meta($order->id, '_transaction_id', $parsed_response['TRANSACTIONID']);
            }
        }

        if ($order->payment_method === 'paypal_pro_payflow' && $txn_id && $captured === 'no') {

            $paypalpro_payflow = new MBJ_PayPal_Pro_WooCommerce_Pro_PayFlow();

            $url = $paypalpro_payflow->testmode ? $paypalpro_payflow->testurl : $paypalpro_payflow->liveurl;

            $post_data = array();
            $post_data['USER'] = $paypalpro_payflow->paypal_user;
            $post_data['VENDOR'] = $paypalpro_payflow->paypal_vendor;
            $post_data['PARTNER'] = $paypalpro_payflow->paypal_partner;
            $post_data['PWD'] = $paypalpro_payflow->paypal_password;
            $post_data['TRXTYPE'] = 'D'; // payflow only allows delayed capture for authorized only transactions
            $post_data['ORIGID'] = $txn_id;

            if ($paypalpro_payflow->soft_descriptor) {
                $post_data['MERCHDESCR'] = $paypalpro_payflow->soft_descriptor;
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
                $order->add_order_note(__('Unable to capture charge!', 'paypal-pro-for-woocommerce') . ' ' . $response->get_error_message());
            } elseif ($parsed_response['RESULT'] !== '0') {
                $order->add_order_note(__('Unable to capture charge!', 'paypal-pro-for-woocommerce'));

                // log it
                $paypalpro_payflow->log('Parsed Response ' . print_r($parsed_response, true));
            } else {

                $order->add_order_note(sprintf(__('PayPal Pro (Payflow) delay charge complete (PNREF: %s)', 'paypal-pro-for-woocommerce'), $parsed_response['PNREF']));

                update_post_meta($order->id, '_paypalpro_charge_captured', 'yes');

                // update the transaction ID of the capture
                update_post_meta($order->id, '_transaction_id', $parsed_response['PNREF']);
            }
        }

        return true;
    }

    public function cancel_payment($order_id) {
        $order = new WC_Order($order_id);

        $txn_id = get_post_meta($order_id, '_transaction_id', true);
        $captured = get_post_meta($order_id, '_paypalpro_charge_captured', true);

        if ($order->payment_method === 'paypal_pro' && $txn_id && $captured === 'no') {

            $paypalpro = new MBJ_PayPal_Pro_WooCommerce_Pro();

            $url = $paypalpro->testmode ? $paypalpro->testurl : $paypalpro->liveurl;

            $post_data = array(
                'VERSION' => $paypalpro->api_version,
                'SIGNATURE' => $paypalpro->api_signature,
                'USER' => $paypalpro->api_username,
                'PWD' => $paypalpro->api_password,
                'METHOD' => 'DoVoid',
                'AUTHORIZATIONID' => $txn_id
            );

            $response = wp_remote_post($url, array(
                'method' => 'POST',
                'headers' => array(
                    'PAYPAL-NVP' => 'Y'
                ),
                'body' => $post_data,
                'timeout' => 70,
                'sslverify' => false,
                'user-agent' => 'WooCommerce',
                'httpversion' => '1.1'
            ));

            if (is_wp_error($response)) {
                $order->add_order_note(__('Unable to void charge!', 'paypal-pro-for-woocommerce') . ' ' . $response->get_error_message());
            } else {
                parse_str($response['body'], $parsed_response);

                $order->add_order_note(sprintf(__('PayPal Pro void complete (Authorization ID: %s)', 'paypal-pro-for-woocommerce'), $parsed_response['AUTHORIZATIONID']));

                delete_post_meta($order->id, '_paypalpro_charge_captured');
                delete_post_meta($order->id, '_transaction_id');
            }
        }

        if ($order->payment_method === 'paypal_pro_payflow' && $txn_id && $captured === 'no') {

            $paypalpro_payflow = new MBJ_PayPal_Pro_WooCommerce_Pro_Payflow();

            $url = $paypalpro_payflow->testmode ? $paypalpro_payflow->testurl : $paypalpro_payflow->liveurl;

            $post_data = array();
            $post_data['USER'] = $paypalpro_payflow->paypal_user;
            $post_data['VENDOR'] = $paypalpro_payflow->paypal_vendor;
            $post_data['PARTNER'] = $paypalpro_payflow->paypal_partner;
            $post_data['PWD'] = $paypalpro_payflow->paypal_password;
            $post_data['TRXTYPE'] = 'V'; // void
            $post_data['ORIGID'] = $txn_id;

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
                $order->add_order_note(__('Unable to void charge!', 'paypal-pro-for-woocommerce') . ' ' . $response->get_error_message());
            } elseif ($parsed_response['RESULT'] !== '0') {
                $order->add_order_note(__('Unable to void charge!', 'paypal-pro-for-woocommerce') . ' ' . $response->get_error_message());

                // log it
                $paypalpro_payflow->log('Parsed Response ' . print_r($parsed_response, true));
            } else {
                $order->add_order_note(sprintf(__('PayPal Pro (Payflow) void complete (PNREF: %s)', 'paypal-pro-for-woocommerce'), $parsed_response['PNREF']));

                delete_post_meta($order->id, '_paypalpro_charge_captured');
                delete_post_meta($order->id, '_transaction_id');
            }
        }
    }

}
