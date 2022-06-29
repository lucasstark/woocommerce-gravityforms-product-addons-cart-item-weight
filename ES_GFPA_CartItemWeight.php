<?php

/**
 * Class ES_GFPA_CartItemWeight
 *
 * Allows for a product to use a set of Gravity Forms fields to set the cart item's weight
 *
 */
class ES_GFPA_CartItemWeight {

	private static $instance;

	/**
	 * @param int $form_id The ID of the form to register.
	 * @param array $fields The array of field ID's to register.
	 * @param bool $display Flag to show the original / new weight as part of the cart item data.
	 *
	 * @return void
	 */
	public static function register() {
		if ( self::$instance == null ) {
			self::$instance = new ES_GFPA_CartItemWeight();
		}
	}

	protected $form_id;
	protected $fields;
	protected $display;

	protected function __construct() {

		//Add these filter after the Gravity Forms Product Addons, which is priority 10.
		add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), 11, 1 );

		add_filter( 'woocommerce_get_cart_item_from_session', array(
			$this,
			'get_cart_item_from_session'
		), 11, 2 );

		add_filter( 'woocommerce_get_item_data', [ $this, 'display_cart_item_weight' ], 11, 2 );

		add_action( 'woocommerce_before_calculate_totals', [ $this, 'set_custom_cart_item_weight' ], 25, 1 );
	}

	public function add_cart_item( $cart_item ) {

		//Adjust weight if required based on the gravity form data
		if ( isset( $cart_item['_gravity_form_lead'] ) && isset( $cart_item['_gravity_form_data'] ) ) {
			$the_product_id = $cart_item['data']->get_id();
			if ( $cart_item['data']->is_type( 'variation' ) ) {
				$the_product_id = $cart_item['data']->get_parent_id();
			}

			$product           = wc_get_product( $the_product_id );
			$gravity_form_data = wc_gfpa()->get_gravity_form_data( $product->get_id() );
			$gravity_form_lead = $cart_item['_gravity_form_lead'];

			if ( ! isset( $gravity_form_data['enable_cart_weight_management'] ) || $gravity_form_data['enable_cart_weight_management'] != 'yes' ) {

				if ( isset( $cart_item['weight'] ) ) {
					$cart_item['data']->set_weight( $cart_item['weight']['default'] );
				}

				return $cart_item;
			}

			//Store the original weight
			$cart_item['weight']['default'] = $product->get_weight();

			$form_meta = RGFormsModel::get_form_meta( $gravity_form_data['id'] );

			//Something wrong with the form, just return the cart item.
			if ( empty( $form_meta ) ) {
				return $cart_item;
			}

			$weight       = 0;
			$weight_field = $gravity_form_data['cart_weight_field'] ?? false;
			if ( $weight_field !== false ) {
				if ( isset( $gravity_form_lead[ $weight_field ] ) ) {
					$weight += floatval( $gravity_form_lead[ $weight_field ] );
				}
			}

			// Set the new calculated weight
			$cart_item['weight']['new'] = $weight;
			$cart_item['data']->set_weight( $weight );
		}

		return $cart_item;
	}

	/**
	 * When the item is being restored from the session, call the add_cart_item function to re-calculate the cart item price.
	 *
	 * @param $cart_item
	 * @param $values
	 *
	 * @return mixed
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {

		if ( isset( $cart_item['_gravity_form_lead'] ) && isset( $cart_item['_gravity_form_data'] ) ) {
			return $this->add_cart_item( $cart_item );
		} else {
			return $cart_item;
		}

	}


	public function display_cart_item_weight( $item_data, $cart_item ) {
		if ( isset( $cart_item['weight'] ) ) {
			// Display original weight
			if ( isset( $cart_item['weight']['default'] ) ) {
				$item_data[] = array(
					'key'   => __( 'Weight (original)', 'woocommerce' ),
					'value' => wc_format_weight( $cart_item['weight']['default'] ),
				);
			}

			// Display calculated weight
			if ( isset( $cart_item['weight']['new'] ) ) {
				$item_data[] = array(
					'key'   => __( 'Weight (new)', 'woocommerce' ),
					'value' => wc_format_weight( $cart_item['weight']['new'] ),
				);
			}
		}

		return $item_data;
	}

	public function set_custom_cart_item_weight( $cart ) {

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			$the_product_id = $cart_item['data']->get_id();
			if ( $cart_item['data']->is_type( 'variation' ) ) {
				$the_product_id = $cart_item['data']->get_parent_id();
			}

			$gravity_form_data = wc_gfpa()->get_gravity_form_data( $the_product_id );

			if ( isset( $gravity_form_data['enable_cart_weight_management'] ) && $gravity_form_data['enable_cart_weight_management'] == 'yes' ) {
				if ( isset( $cart_item['weight']['new'] ) ) {
					$cart_item['data']->set_weight( $cart_item['weight']['new'] );
				}
			} else {
				if ( isset( $cart_item['weight']['default'] ) ) {
					$cart_item['data']->set_weight( $cart_item['weight']['default'] );
				}
			}
		}
	}
}

ES_GFPA_CartItemWeight::register();
