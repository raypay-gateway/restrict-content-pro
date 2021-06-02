<?php
/**
 * Plugin Name: RayPay for Restrict Content Pro
 * Author: Saminray
 * Description: <a href="https://raypay.ir">RayPay</a> secure payment gateway for Restrict Content Pro
 * Version: 1.0
 * Author URI: https://saminray.com
 * Text Domain: raypay-for-rcp
 * Domain Path: languages
 */

// Exit, if access directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'RCP_RayPay' ) ):

/**
 * Main RCP_RayPay class.
 */
final class RCP_RayPay {
	/**
	 * @var RCP_RayPay The one true RCP_RayPay instance
	 */
	private static $instance;

	/**
	 * Main RCP_RayPay instance.
	 *
	 * @static
	 * @staticvar array $instance
	 * @uses RCP_RayPay::setup_constants() Setup the constants needed.
	 * @uses RCP_RayPay:includes() Include the required files.
	 * @uses RCP_RayPay::load_textdomain() Load the language files.
	 * @see RCP_RayPay()
	 * @return object|RCP_RayPay The one true RCP_RayPay
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof RCP_RayPay ) ) {
			self::$instance = new RCP_RayPay;
			self::$instance->setup_constants();

			add_action( 'plugins_loaded', [ self::$instance, 'load_textdomain' ] );

			self::$instance->includes();
		}

		return self::$instance;
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access private
	 * @return void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'RCP_RAYPAY_VERSION' ) ) {
			define( 'RCP_RAYPAY_VERSION', '1.0' );
		}

		// Plugin directory path.
		if ( ! defined( 'RCP_RAYPAY_PLUGIN_DIR' ) ) {
			define( 'RCP_RAYPAY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin root file.
		if ( ! defined( 'RCP_RAYPAY_PLUGIN_FILE' ) ) {
			define( 'RCP_RAYPAY_PLUGIN_FILE', __FILE__ );
		}
	}

	/**
	 * Include required files.
	 *
	 * @access private
	 * @return void
	 */
	private function includes() {
		require_once RCP_RAYPAY_PLUGIN_DIR . 'includes/functions.php';
		require_once RCP_RAYPAY_PLUGIN_DIR . 'includes/filters.php';
		require_once RCP_RAYPAY_PLUGIN_DIR . 'includes/admin/settings.php';
		require_once RCP_RAYPAY_PLUGIN_DIR . 'includes/actions.php';
	}

	/**
	 * Loads the plugin language files.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function load_textdomain() {
		global $wp_version;
		/*
		 * Due to the introduction of language packs through translate.wordpress.org, loading our textdomain is complex.
		 *
		 * To support existing translation files from before the change, we must look for translation files in several places and under several names.
		 *
		 * - wp-content/languages/plugins/raypay-for-rcp (introduced with language packs)
		 * - wp-content/languages/raypay-for-rcp/ (custom folder we have supported since 1.4)
		 * - wp-content/plugins/raypay-for-rcp/languages/
		 *
		 * In wp-content/languages/raypay-for-rcp/ we must look for "raypay-for-rcp-{lang}_{country}.mo"
		 * In wp-content/languages/plugins/raypay-for-rcp/ we only need to look for "raypay-for-rcp-{lang}_{country}.mo" as that is the new structure
		 * In wp-content/plugins/raypay-for-rcp/languages/, we must look for both naming conventions. This is done by filtering "load_textdomain_mofile"
		 *
		 */
		add_filter( 'load_textdomain_mofile', array( $this, 'load_old_textdomain' ), 10, 2 );
		// Set filter for plugin's languages directory.
		$plugin_lang_dir  = dirname( plugin_basename( RCP_RAYPAY_PLUGIN_FILE ) ) . '/languages/';

		// Traditional WordPress plugin locale filter.
		$locale = get_locale();
		if ( $wp_version >= 4.7 ) {
			$locale = get_user_locale();
		}
		/**
		 * Defines the plugin language locale used in Easy Digital Downloads.
		 *
		 * @var $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
		 *                  otherwise uses `get_locale()`.
		 */
		$mofile         = sprintf( '%1$s-%2$s.mo', 'raypay-for-rcp', $locale );
		// Look for wp-content/languages/raypay-for-rcp/raypay-for-rcp-{lang}_{country}.mo
		$mofile_global1 = WP_LANG_DIR . '/raypay-restrict-content-pro/raypay-for-rcp-' . $locale . '.mo';
		// Look for wp-content/languages/rcp-iday/{lang}_{country}.mo
		$mofile_global2 = WP_LANG_DIR . '/raypay-for-rcp/' . $locale . '.mo';
		// Look in wp-content/languages/plugins/raypay-for-rcp
		$mofile_global3 = WP_LANG_DIR . '/plugins/raypay-restrict-content-pro/' . $mofile;
		if ( file_exists( $mofile_global1 ) ) {
			load_textdomain( 'raypay-for-rcp', $mofile_global1 );
		} elseif ( file_exists( $mofile_global2 ) ) {
			load_textdomain( 'raypay-for-rcp', $mofile_global2 );
		} elseif ( file_exists( $mofile_global3 ) ) {
			load_textdomain( 'raypay-for-rcp', $mofile_global3 );
		} else {
			// Load the default language files.
			load_plugin_textdomain( 'raypay-for-rcp', false, $plugin_lang_dir );
		}
	}
	/**
	 * Load a .mo file for the old textdomain if one exists.
	 *
	 * h/t: https://github.com/10up/grunt-wp-plugin/issues/21#issuecomment-62003284
	 */
	function load_old_textdomain( $mofile, $textdomain ) {
		if ( $textdomain === 'raypay-for-rcp' && ! file_exists( $mofile ) ) {
			$mofile = dirname( $mofile ) . DIRECTORY_SEPARATOR . str_replace( $textdomain, 'raypay-for-rcp', basename( $mofile ) );
		}
		return $mofile;
	}
}
endif;

return RCP_RayPay::instance();
