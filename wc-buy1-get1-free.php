<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://gurukullab.com/
 * @since             1.0.0
 * @package           Wc_Buy1_Get1_Free
 *
 * @wordpress-plugin
 * Plugin Name:       WC Buy One Get One Free
 * Plugin URI:        https://gurukullab.com/
 * Description:       This plugin adds a concept of <strong>buy one get one</strong> to woocommerce products.
 * Version:           1.0.0
 * Author:            Gurukullab
 * Author URI:        https://gurukullab.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wcbogo
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WCBOGO_PLUGIN_VERSION', '1.0.0' );

// Plugin path.
if ( ! defined( 'WCBOGO_PLUGIN_PATH' ) ) {
	define( 'WCBOGO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

// Plugin URL.
if ( ! defined( 'WCBOGO_PLUGIN_URL' ) ) {
	define( 'WCBOGO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wc-buy1-get1-free-activator.php
 */
function wcbogo_activate_wc_buy1_get1_free() {
	require_once WCBOGO_PLUGIN_PATH . 'includes/class-wc-buy1-get1-free-activator.php';
	Wc_Buy1_Get1_Free_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wc-buy1-get1-free-deactivator.php
 */
function wcbogo_deactivate_wc_buy1_get1_free() {
	require_once WCBOGO_PLUGIN_PATH . 'includes/class-wc-buy1-get1-free-deactivator.php';
	Wc_Buy1_Get1_Free_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'wcbogo_activate_wc_buy1_get1_free' );
register_deactivation_hook( __FILE__, 'wcbogo_deactivate_wc_buy1_get1_free' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function wcbogo_run_wc_buy1_get1_free() {

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require WCBOGO_PLUGIN_PATH . 'includes/class-wc-buy1-get1-free.php';
	$plugin = new Wc_Buy1_Get1_Free();
	$plugin->run();

}

/**
 * This initiates the plugin.
 * Checks for the required plugins to be installed and active.
 */
function wcbogo_plugins_loaded_callback() {
	$active_plugins = get_option( 'active_plugins' );
	$is_wc_active   = in_array( 'woocommerce/woocommerce.php', $active_plugins, true );

	if ( current_user_can( 'activate_plugins' ) && false === $is_wc_active ) {
		add_action( 'admin_notices', 'wcbogo_admin_notices_callback' );
	} else {
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wcbogo_plugin_actions_callback' );
		wcbogo_run_wc_buy1_get1_free();
	}
}

add_action( 'plugins_loaded', 'wcbogo_plugins_loaded_callback' );

/**
 * This function is called to show admin notices for any required plugin not active || installed.
 */
function wcbogo_admin_notices_callback() {
	$this_plugin_data = get_plugin_data( __FILE__ );
	$this_plugin      = $this_plugin_data['Name'];
	$wc_plugin        = 'WooCommerce';
	?>
	<div class="error">
		<p>
			<?php
			/* translators: 1: %s: string tag open, 2: %s: strong tag close, 3: %s: this plugin, 4: %s: woocommerce plugin */
			echo wp_kses_post( sprintf( __( '%1$s%3$s%2$s is ineffective as it requires %1$s%4$s%2$s to be installed and active. Click %5$shere%6$s to install or activate it.', 'wcbogo' ), '<strong>', '</strong>', esc_html( $this_plugin ), esc_html( $wc_plugin ), '<a target="_blank" href="' . admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) . '">', '</a>' ) );
			?>
		</p>
	</div>
	<?php
}

/**
 * This function adds custom plugin actions.
 *
 * @param array $links Plugin links array.
 * @return array
 */
function wcbogo_plugin_actions_callback( $links ) {
	$this_plugin_links = array(
		'<a title="' . __( 'Settings', 'wcbogo' ) . '" href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=products&section=wcbogo' ) ) . '">' . __( 'Settings', 'wcbogo' ) . '</a>',
	);

	return array_merge( $this_plugin_links, $links );
}
