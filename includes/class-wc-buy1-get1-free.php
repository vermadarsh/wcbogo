<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://gurukullab.com/
 * @since      1.0.0
 *
 * @package    Wc_Buy1_Get1_Free
 * @subpackage Wc_Buy1_Get1_Free/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wc_Buy1_Get1_Free
 * @subpackage Wc_Buy1_Get1_Free/includes
 * @author     Gurukul Lab <info@gurukullab.com>
 */
class Wc_Buy1_Get1_Free {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wc_Buy1_Get1_Free_Loader    $loader    Maintains and registers all hooks for the plugin.
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
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WCBOGO_VERSION' ) ) {
			$this->version = WCBOGO_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wc-buy1-get1-free';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wc_Buy1_Get1_Free_Loader. Orchestrates the hooks of the plugin.
	 * - Wc_Buy1_Get1_Free_I18n. Defines internationalization functionality.
	 * - Wc_Buy1_Get1_Free_Admin. Defines all hooks for the admin area.
	 * - Wc_Buy1_Get1_Free_Public. Defines all hooks for the public side of the site.
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
		require_once __DIR__ . '/class-wc-buy1-get1-free-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once __DIR__ . '/class-wc-buy1-get1-free-i18n.php';

		/**
		 * The class responsible for defining plugin custom functions.
		 */
		require_once __DIR__ . '/wc-buy1-get1-free-functions.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once __DIR__ . '/../admin/class-wc-buy1-get1-free-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once __DIR__ . '/../public/class-wc-buy1-get1-free-public.php';

		$this->loader = new Wc_Buy1_Get1_Free_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wc_Buy1_Get1_Free_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wc_Buy1_Get1_Free_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Wc_Buy1_Get1_Free_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'wcbogo_admin_enqueue_scripts_callback' );
		
		$this->loader->add_filter( 'woocommerce_get_sections_products', $plugin_admin, 'wcbogo_woocommerce_get_sections_products_callback' );
		$this->loader->add_filter( 'woocommerce_get_settings_products', $plugin_admin, 'wcbogo_woocommerce_get_settings_products_callback', 10, 2 );
		$this->loader->add_filter( 'woocommerce_product_data_tabs', $plugin_admin, 'wcbogo_woocommerce_product_data_tabs_callback' );
		$this->loader->add_action( 'woocommerce_product_data_panels', $plugin_admin, 'wcbogo_woocommerce_product_data_panels_callback' );
		$this->loader->add_action( 'wp_ajax_wcbogo_fetch_products', $plugin_admin, 'wcbogo_fetch_products_callback' );
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'wcbogo_woocommerce_process_product_meta_callback' );
		$this->loader->add_action( 'woocommerce_product_after_variable_attributes', $plugin_admin, 'wcbogo_woocommerce_product_after_variable_attributes_callback', 10, 3 );
		$this->loader->add_action( 'woocommerce_save_product_variation', $plugin_admin, 'wcbogo_woocommerce_save_product_variation_callback', 10, 2 );
		
		$this->loader->add_shortcode( 'wcbogo_offers', $plugin_admin, 'wcbogo_offers_shortcode_callback' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Wc_Buy1_Get1_Free_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'wcbogo_wp_enqueue_scripts_callback', 99 );

		// Hooks related to product pages, showing offer available on that products.
		$this->loader->add_action( 'woocommerce_before_add_to_cart_form', $plugin_public, 'wcbogo_woocommerce_before_add_to_cart_form_callback' );
		$this->loader->add_filter( 'woocommerce_available_variation', $plugin_public, 'wcbogo_woocommerce_available_variation_callback', 10, 3 );

		// Hooks to show the offer available.
		$this->loader->add_action( 'woocommerce_before_cart', $plugin_public, 'wcbogo_show_free_product_availability_callback' );
		$this->loader->add_action( 'woocommerce_before_checkout_form', $plugin_public, 'wcbogo_show_free_product_availability_callback' );
		$this->loader->add_action( 'woocommerce_before_main_content', $plugin_public, 'wcbogo_show_free_product_availability_callback' );

		// Hookss related to cart actions.
		$this->loader->add_action( 'wp_ajax_add_free_product_to_cart', $plugin_public, 'wcbogo_add_free_product_to_cart_callback' );
		$this->loader->add_action( 'wp_ajax_nopriv_add_free_product_to_cart', $plugin_public, 'wcbogo_add_free_product_to_cart_callback' );
		$this->loader->add_action( 'wp_ajax_update_mini_cart', $plugin_public, 'wcbogo_update_mini_cart_callback' );
		$this->loader->add_action( 'wp_ajax_nopriv_update_mini_cart', $plugin_public, 'wcbogo_update_mini_cart_callback' );
		$this->loader->add_filter( 'woocommerce_add_cart_item_data', $plugin_public, 'wcbogo_woocommerce_add_cart_item_data_callback', 20, 3 );
		$this->loader->add_action( 'woocommerce_remove_cart_item', $plugin_public, 'wcbogo_woocommerce_remove_cart_item_callback', 20, 2 );
		$this->loader->add_filter( 'woocommerce_coupons_enabled', $plugin_public, 'wcbogo_woocommerce_coupons_enabled_callback' );

		// Hooks related to cart, making the free product non-editable.
		$this->loader->add_filter( 'woocommerce_cart_item_class', $plugin_public, 'wcbogo_woocommerce_cart_item_class_callback', 20, 2 );
		$this->loader->add_filter( 'woocommerce_cart_item_remove_link', $plugin_public, 'wcbogo_woocommerce_cart_item_remove_link_callback', 20, 2 );
		$this->loader->add_filter( 'woocommerce_cart_item_quantity', $plugin_public, 'wcbogo_woocommerce_cart_item_quantity_callback', 20, 3 );
		$this->loader->add_action( 'woocommerce_before_calculate_totals', $plugin_public, 'wcbogo_woocommerce_before_calculate_totals_callback', 5 );
		$this->loader->add_filter( 'woocommerce_get_item_data', $plugin_public, 'wcbogo_woocommerce_get_item_data_callback', 20, 2 );
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
	 * @return    Wc_Buy1_Get1_Free_Loader    Orchestrates the hooks of the plugin.
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
