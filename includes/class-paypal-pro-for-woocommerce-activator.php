<?php

/**
 * @class       MBJ_PayPal_Pro_WooCommerce_Activator
 * @version	1.0.0
 * @package	paypal-pro-for-woocommerce
 * @category	Class
 * @author      johnny manziel <phpwebcreators@gmail.com>
 */
class MBJ_PayPal_Pro_WooCommerce_Activator {

    /**
     *
     * @since    1.0.0
     */
    public static function activate() {
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            deactivate_plugins(PPFW_PLUGIN_DIR . '/paypal-pro-for-woocommerce.php');
            wp_die("<strong>PayPal Pro for WooCommerce</strong> requires <strong>WooCommerce</strong> plugin to work normally. Please activate it or install it.<br /><br />Back to the WordPress <a href='" . get_admin_url(null, 'plugins.php') . "'>Plugins page</a>.");
        }
    }

}
