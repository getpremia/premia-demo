<?php

/**
 * Premia Demo
 *
 * @package   Premia Demo
 * @author    Marinus Klasen
 * @copyright 2021 Marinus Klasen
 *
 * @wordpress-plugin
 * Plugin Name:       Premia Demo
 * Plugin URI:        https://getpremia.com/premia-demo
 * Description:       Premia Demo
 * Version:           4.8.2.1.2
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Marinus Klasen
 * Author URI:        https://getpremia.com/premia-demo
 * Text Domain:       premia-demo
 */


require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * Premia Demo Class.
 */
class Premia_Demo {


	/**
	 * Set the plugin name
	 *
	 * @var string $plugin_name The Woocommerce product slug
	 */
	private $plugin_name = 'premia-demo';

	/**
	 * Set the plugin version
	 *
	 * @var string $plugin_name The Woocommerce product slug
	 */
	private $version = '4.8.2.1.2';

	/**
	 * Set the API url
	 *
	 * @var string $api_url The API url.
	 */
	private $api_url = 'https://getpremia.com/wp-json/license-updater/v1/';

	/**
	 * PUC
	 *
	 * @var object $puc The PUC object.
	 */
	private $puc;

	public function __construct() {
		$this->init();
	}

	public function init() {
		$this->setup_puc();
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_notices', array( $this, 'add_admin_notice' ) );
	}

	public function add_admin_notice() {
		$plugin_data = get_plugin_data( __FILE__ );
		echo '<div class="notice notice-error"><p>This is Premia Demo, version ' . $this->version . '</p></div>';
	}

	public function setup_puc() {
		add_filter( "puc_request_info_query_args-{$this->plugin_name}", array( $this, 'add_license_info' ) );

		$this->puc = Puc_v4_Factory::buildUpdateChecker(
			$this->api_url . 'check_updates',
			__FILE__,
			$this->plugin_name
		);
	}

	public function add_menu_page() {
		$plugin_data = get_plugin_data( __FILE__ );
		add_menu_page( $plugin_data['Name'], $plugin_data['Name'], 'manage_options', $this->plugin_name . '-settings', array( $this, 'settings_page' ) );
	}

	public function settings_page() {

		$license_verified = true;

		$option_name     = str_replace( '-', '_', $this->plugin_name ) . '_license_key';
		$tag_option_name = str_replace( '-', '_', $this->plugin_name ) . '_tag';

		if ( isset( $_POST[ $option_name ] ) ) {

			if ( wp_verify_nonce( $_POST['_wpnonce'] ) ) {

				$license = sanitize_text_field( $_POST[ $option_name ] );
				$tag     = sanitize_text_field( $_POST[ $tag_option_name ] );
				$action  = sanitize_text_field( $_POST['action'] );

				$activate = wp_remote_post(
					$this->api_url . 'activate',
					array(
						'body' => array(
							'license_key' => $license,
							'site_url'    => get_site_url(),
							'action'      => $action,
						),
					)
				);

				$status = wp_remote_retrieve_response_code( $activate );

				if ( $action === 'deactivate' ) {
					   $license = '';
				}

				if ( $status !== 200 && $action === 'activate' ) {
					echo '<div class="notice notice-error"><p>Failed to activate license.</p></div>';
				} else {
					if ( $action === 'activate' ) {
						$this->puc->checkForUpdates();
						$message = __( 'License activated!', 'premia-demo' );
					} else {
						$message = __( 'License deactivated!', 'premia-demo' );
					}

					echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>';
					update_option( $option_name, $license );
				}
			}
			update_option( $tag_option_name, $tag );
		}

		$current_license = get_option( $option_name );
		$current_tag     = get_option( $tag_option_name );

		if ( ! empty( $current_license ) ) {
			// Check if license is still active.
			$activate = wp_remote_post(
				$this->api_url . 'activate',
				array(
					'body' => array(
						'license_key' => $current_license,
						'site_url'    => get_site_url(),
						'action'      => 'status',
					),
				)
			);

			$status = wp_remote_retrieve_response_code( $activate );

			if ( $status !== 200 ) {
				$license_verified = false;
				echo '<div class="notice notice-error"><p>' . __( 'Please re-activate your license.', 'premia-demo' ) . '</p></div>';
			}
		}

		$plugin_data = get_plugin_data( __FILE__ );

		echo '<div class="wrap">';
		echo '<h1>' . $plugin_data['Name'] . ' ' . __( 'Settings', 'premia-demo' ) . '</h1>';
		echo '<form method="POST">';
		wp_nonce_field();
		echo '<div class="wrap">';
		echo '<p>';
		echo '<label for="' . esc_html( $option_name ) . '">' . __( 'License Key', 'premia-demo' ) . '</label><br/>';
		echo '<input  name="' . esc_html( $option_name ) . '" ' . ( ( ! empty( $current_license ) && $license_verified === true ) ? 'readonly="readonly"' : '' ) . ' value="' . esc_html( $current_license ) . '" placeholder="' . __( 'Enter License key', 'premia-demo' ) . '" type="text" />';
		echo '<input type="hidden" name="action" value="' . ( ( ! empty( $current_license ) && $license_verified === true ) ? 'deactivate' : 'activate' ) . '" />';
		echo '<input class="button-primary" type="submit" value="' . ( ( ! empty( $current_license ) && $license_verified === true ) ? 'Deactivate' : 'Activate' ) . '" />';
		echo '</p>';
		if ( WP_DEBUG ) {
			echo '<p>';
			echo '<label for="' . esc_html( $tag_option_name ) . '">' . __( 'Tag', 'premia-demo' ) . '</label><br/>';
			echo '<input  name="' . esc_html( $tag_option_name ) . '" value="' . esc_html( $current_tag ) . '" placeholder="' . __( 'Enter tag or leave empty for latest release', 'premia-demo' ) . '" type="text" />';
			echo '<input class="button-primary" type="submit" value="Update" />';
			echo '</p>';
		}
		echo '</div>';
		echo '</form></div>';
	}

	public function add_license_info( $args ) {
		$option_name         = str_replace( '-', '_', $this->plugin_name ) . '_license_key';
		$tag_option_name     = str_replace( '-', '_', $this->plugin_name ) . '_tag';
		$args['license_key'] = get_option( $option_name );
		$args['site_url']    = esc_url( get_site_url() );
		$args['plugin']      = $this->plugin_name;
		$args['tag']         = get_option( $tag_option_name );

		return $args;
	}
}

new Premia_Demo();
