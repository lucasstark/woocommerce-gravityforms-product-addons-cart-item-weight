<div class="wc-product-data-metabox-group-field">
    <div class="wc-product-data-metabox-group-field-title">
        <a href="javascript:;"><?php _e( 'Weight Calculation Field', 'wc_gf_addons' ); ?></a>
    </div>

    <div id="gforms_weight_field_group" class="wc-product-data-metabox-group-field-content"
         style="display:none;">

        <div class="gforms-panel options_group" <?php echo empty( $gravity_form_data['id'] ) ? "style='display:none;'" : ''; ?>>
            <div class="wc-product-data-metabox-option-group-label">
				<?php _e( 'Weight Calculation Field Options', 'wc_gf_addons' ); ?>
                <p style="font-weight: normal;">
					<?php _e( 'Options for setting a field to use as a cart items weight', 'wc_gf_addons' ); ?>
                </p>
            </div>

			<?php
			woocommerce_wp_select( array(
				'id'          => 'enable_cart_weight_management',
				'label'       => __( 'Set Weight?', 'wc_gf_addons' ),
				'value'       => isset( $gravity_form_data['enable_cart_weight_management'] ) ? $gravity_form_data['enable_cart_weight_management'] : 'no',
				'options'     => array(
					'no'  => __( 'No', 'wc_gf_addons' ),
					'yes' => __( 'Yes', 'wc_gf_addons' )
				),
				'description' => __( 'Choose to control the cart item\'s weight.', 'wc_gf_addons' )
			) );
			?>

            <div id="gforms_weight_field_section">
				<?php if ( isset($gravity_form_data['enable_cart_weight_management']) && $gravity_form_data['enable_cart_weight_management'] == 'yes' ): ?>
					<?php echo ES_GFPA_CartItemWeight_Main::get_field_markup( $gravity_form_data['id'], $gravity_form_data['cart_weight_field'] ); ?>
				<?php endif; ?>
            </div>

        </div>
    </div>
</div>
