<?php

/*
 * Plugin Name:  WooCommerce Gravity Forms Product Add-Ons Cart Item Weight
 * Plugin URI: https://github.com/lucasstark/woocommerce-gravityforms-product-addons-cart-item-weight
 * Description: This plugin will allow you to use gravity form's fields to set a cart item's weight.  Requires the Gravity Forms Product Addons plugin.
 * Version: 1.0.0
 * Author: Lucas Stark
 * Author URI: https://www.elementstark.com/
 * Requires at least: 3.1
 * Tested up to: 6.0

 * Copyright: © 2009-2022 Lucas Stark.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html

 * WC requires at least: 6.6
 * WC tested up to: 6.6
 */

class ES_GFPA_CartItemWeight_Main {
	private static $instance = null;

	public static function register() {
		if ( self::$instance == null ) {
			self::$instance = new ES_GFPA_CartItemWeight_Main();
		}
	}

	public static $scripts_version = '1.0.1';

	public function __construct() {
		require 'ES_GFPA_CartItemWeight.php';

		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ), 100 );
		add_action( 'wp_ajax_wc_gravityforms_get_weight_fields', array( $this, 'get_fields' ) );
		add_filter( 'woocommerce_gravityforms_before_save_metadata', [ $this, 'on_before_save_metadata' ] );
		add_action( 'woocommerce_gforms_after_field_groups', [ $this, 'render_field_group' ], 10, 2 );
	}

	public function on_admin_enqueue_scripts() {
		wp_enqueue_script( 'esgfpa_weight', self::plugin_url() . '/assets/js/admin.js', [ 'jquery' ], self::$scripts_version, true );
	}

	public function on_before_save_metadata( $gravity_form_data ) {

		if ( isset( $_POST['cart_weight_field'] ) ) {
			$gravity_form_data['cart_weight_field'] = $_POST['cart_weight_field'];
		}

		if ( isset( $_POST['enable_cart_weight_management'] ) ) {
			$gravity_form_data['enable_cart_weight_management'] = $_POST['enable_cart_weight_management'];
		}

		if ( isset( $_POST['enable_cart_weight_display'] ) ) {
			$gravity_form_data['enable_cart_weight_display'] = $_POST['enable_cart_weight_display'];
		}

		return $gravity_form_data;
	}

	public function render_field_group( $gravity_form_data, $product_id ) {
		$gravity_form_data = $gravity_form_data; // make it available for the view.
		$product           = wc_get_product( $product_id );
		include 'weight-calculation-meta-box.php';
	}


	/** Ajax Handling */
	public function get_fields() {
		check_ajax_referer( 'wc_gravityforms_get_products', 'wc_gravityforms_security' );

		$form_id = $_POST['form_id'] ?? 0;
		if ( empty( $form_id ) ) {
			wp_send_json_error( array(
				'status'  => 'error',
				'message' => __( 'No Form ID', 'wc_gf_addons' ),
			) );
			die();
		}

		$product_id     = $_POST['product_id'] ?? 0;
		$selected_field = '';
		if ( $product_id ) {
			$gravity_form_data = wc_gfpa()->get_gravity_form_data( $product_id );
			if ( $gravity_form_data && isset( $gravity_form_data['enable_cart_weight_management'] ) ) {
				if ( isset( $gravity_form_data['cart_weight_field'] ) ) {
					$selected_field = $gravity_form_data['cart_weight_field'];
				}
			}
		}

		$markup = ES_GFPA_CartItemWeight_Main::get_field_markup( $form_id, $selected_field, $gravity_form_data['enable_cart_weight_display'] ?? 'no' );

		$response = array(
			'status'  => 'success',
			'message' => '',
			'markup'  => $markup
		);

		wp_send_json_success( $response );
		die();
	}

	/** Helper functions ***************************************************** */

	/**
	 * Get the plugin url.
	 *
	 * @access public
	 * @return string
	 */
	public static function plugin_url() {
		return plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) );
	}


	public static function get_field_markup( $form_id, $selected_field = '', $show_weight = 'no' ) {
		$form   = GFAPI::get_form( $form_id );
		$fields = GFAPI::get_fields_by_type( $form, array( 'quantity', 'number', 'singleproduct' ), false );

		if ( $fields ) {
			$options = array();
			foreach ( $fields as $field ) {
				if ( $field['disableQuantity'] !== true ) {
					$options[ $field['id'] ] = $field['label'];
				}
			}

			ob_start();
			woocommerce_wp_select(
				array(
					'id'          => 'cart_weight_field',
					'label'       => __( 'Weight Field', 'wc_gf_addons' ),
					'value'       => $selected_field,
					'options'     => $options,
					'description' => __( 'A field to use to control cart item weight.', 'wc_gf_addons' )
				)
			);

			woocommerce_wp_select( array(
				'id'          => 'enable_cart_weight_display',
				'label'       => __( 'Show Weight?', 'wc_gf_addons' ),
				'value'       => $show_weight,
				'options'     => array(
					'no'  => __( 'No', 'wc_gf_addons' ),
					'yes' => __( 'Yes', 'wc_gf_addons' )
				),
				'description' => __( 'Choose to show the the cart item\'s weight differences.', 'wc_gf_addons' )
			) );

			$markup = ob_get_clean();
		} else {
			$markup = '<p class="form-field">' . __( 'No suitable quantity fields found.', 'wc_gf_addons' ) . '</p>';
		}

		return $markup;
	}
}

ES_GFPA_CartItemWeight_Main::register();
