<?php

/**
 * @class       MBJ_PayPal_Pro_WooCommerce
 * @version	1.0.0
 * @package	paypal-pro-for-woocommerce
 * @category	Class
 * @author      johnny manziel <phpwebcreators@gmail.com>
 */
class MBJ_PayPal_Pro_WooCommerce {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      MBJ_PayPal_Pro_WooCommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the Dashboard and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {

        $this->plugin_name = 'paypal-pro-for-woocommerce';
        $this->version = '1.0.0';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - MBJ_PayPal_Pro_WooCommerce_Loader. Orchestrates the hooks of the plugin.
     * - MBJ_PayPal_Pro_WooCommerce_i18n. Defines internationalization functionality.
     * - MBJ_PayPal_Pro_WooCommerce_Admin. Defines all hooks for the dashboard.
     * - MBJ_PayPal_Pro_WooCommerce_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-paypal-pro-for-woocommerce-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-paypal-pro-for-woocommerce-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the Dashboard.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-paypal-pro-for-woocommerce-admin.php';


        $this->loader = new MBJ_PayPal_Pro_WooCommerce_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the MBJ_PayPal_Pro_WooCommerce_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new MBJ_PayPal_Pro_WooCommerce_i18n();
        $plugin_i18n->set_domain($this->get_plugin_name());

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the dashboard functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new MBJ_PayPal_Pro_WooCommerce_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('plugins_loaded', $plugin_admin, 'load_plugin_extend_lib');
        $this->loader->add_filter('woocommerce_payment_gateways', $plugin_admin, 'register_gateway');
        if (is_admin()) {
            $this->loader->add_action('admin_notices', $plugin_admin, 'ssl_check');
        }

        $this->loader->add_action('woocommerce_order_status_on-hold_to_processing', $plugin_admin, 'capture_payment');
        $this->loader->add_action('woocommerce_order_status_on-hold_to_completed', $plugin_admin, 'capture_payment');
        $this->loader->add_action('woocommerce_order_status_on-hold_to_cancelled', $plugin_admin, 'cancel_payment');
        $this->loader->add_action('woocommerce_order_status_on-hold_to_refunded', $plugin_admin, 'cancel_payment');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    MBJ_PayPal_Pro_WooCommerce_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}
