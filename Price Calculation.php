<?php
/*
Plugin Name: Custom Price Calculation
Plugin URI: #
Description: Add a price modification input box on the edit page of each product to facilitate the calculation of product profits. Please note that commodity prices are divided into Regular price and Sale price. The calculation result is first based on the value in Sale price. When the value in Sale price is empty, the value in Regular price will be used to calculate the final display in the store.At the same time, a bulk action function has also been added. Check the items that need to be modified in price, select the edit function and apply, and you can also modify the price in bulk.
Version: 1.0.0
Author: Dbryge
Text Domain: custom-price-calculation
*/

// Add custom fields to the product data meta box
function custom_price_calculation_fields() {
    global $post;

    echo '<div class="options_group">';

    // Custom dropdown field
    woocommerce_wp_select(
        array(
            'id' => '_custom_price_type',
            'label' => __( 'Price Calculation Type', 'custom-price-calculation' ),
            'options' => array(
                'fixed' => __( 'Fixed', 'custom-price-calculation' ),
                'percentage' => __( 'Percentage', 'custom-price-calculation' ),
            ),
            'desc_tip' => true,
            'description' => __( 'Select the price calculation type.', 'custom-price-calculation' ),
        )
    );

    // Custom text field for Markup
    woocommerce_wp_text_input(
        array(
            'id' => '_custom_profit',
            'label' => __( 'Markup', 'custom-price-calculation' ),
            'desc_tip' => true,
            'description' => __( 'If there is a value in the input box of sale price, the input box shall prevail; Otherwise, the value in the input box of regular price shall prevail.', 'custom-price-calculation' ),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '0',
            ),
        )
    );

    // Custom text field for Price on the front end
    woocommerce_wp_text_input(
        array(
            'id' => '_custom_price_frontend',
            'label' => __( 'Price on the front end', 'custom-price-calculation' ),
            'desc_tip' => true,
            'description' => __( 'Calculated price to display on the front end.', 'custom-price-calculation' ),
            'type' => 'text',
            'custom_attributes' => array(
                'readonly' => 'readonly',
            ),
        )
    );

    // Custom button
    echo '<button type="button" class="button" id="custom_price_calculation_button">' . __( 'Confirmed Price', 'custom-price-calculation' ) . '</button>';

    echo '</div>';
}
add_action( 'woocommerce_product_options_pricing', 'custom_price_calculation_fields' );

function custom_price_calculation_button_script() {
    ?>
    <script>
        jQuery(function($){
            $('#custom_price_calculation_button').click(function(){
                var salePrice = parseFloat($('#_sale_price').val());
                var regularPrice = parseFloat($('#_regular_price').val());
                var profit = parseFloat($('#_custom_profit').val());
                var calculationType = $('#_custom_price_type').val();
                var calculatedPrice = '';

                if (salePrice && calculationType === 'fixed') {
                    calculatedPrice = salePrice + profit;
                } else if (salePrice && calculationType === 'percentage') {
                    calculatedPrice = salePrice + (salePrice * profit / 100);
                } else if (calculationType === 'fixed') {
                    calculatedPrice = regularPrice + profit;
                } else if (calculationType === 'percentage') {
                    calculatedPrice = regularPrice + (regularPrice * profit / 100);
                }

                // Display calculated price
                var displayHtml = '<strong>' + calculatedPrice.toFixed(2) + '</strong>';
                $('#custom_price_display').html(displayHtml);

                // Set the calculated price in the "Price on the front end" field
                $('#_custom_price_frontend').val(calculatedPrice.toFixed(2));
            });
        });
    </script>
    <?php
}
add_action( 'admin_footer', 'custom_price_calculation_button_script' );

// Save custom fields data
function save_custom_price_calculation_fields( $product_id ) {
    $custom_price_type = isset( $_POST['_custom_price_type'] ) ? sanitize_text_field( $_POST['_custom_price_type'] ) : '';
    $custom_profit = isset( $_POST['_custom_profit'] ) ? wc_format_decimal( $_POST['_custom_profit'] ) : '';
    $custom_price_frontend = isset( $_POST['_custom_price_frontend'] ) ? wc_format_decimal( $_POST['_custom_price_frontend'] ) : '';

    // Save custom fields
    if ( ! empty( $custom_price_type ) ) {
        update_post_meta( $product_id, '_custom_price_type', $custom_price_type );
    } else {
        delete_post_meta( $product_id, '_custom_price_type' );
    }

    if ( ! empty( $custom_profit ) ) {
        update_post_meta( $product_id, '_custom_profit', $custom_profit );
    } else {
        delete_post_meta( $product_id, '_custom_profit' );
    }

    // Update the "Price on the front end" field
    if ( ! empty( $custom_price_frontend ) ) {
        update_post_meta( $product_id, '_custom_price_frontend', $custom_price_frontend );
    }
}
add_action( 'woocommerce_process_product_meta_simple', 'save_custom_price_calculation_fields', 10, 1 );

// Replace regular price with custom front end price
function replace_regular_price_with_custom_price( $price, $product ) {
    $custom_price_frontend = $product->get_meta( '_custom_price_frontend' );

    if ( ! empty( $custom_price_frontend ) ) {
        $price = wc_price( $custom_price_frontend );
    }

    return $price;
}
add_filter( 'woocommerce_get_price_html', 'replace_regular_price_with_custom_price', 10, 2 );

//Set up Variable product

// Add custom fields to the product variation data meta box
function custom_price_calculation_variation_fields( $loop, $variation_data, $variation ) {
    echo '<div class="options_group">';

    // Custom dropdown field for Price Calculation Type
    woocommerce_wp_select(
        array(
            'id' => '_custom_price_type[' . $loop . ']',
            'label' => __( 'Price Calculation Type', 'custom-price-calculation' ),
            'options' => array(
                'fixed' => __( 'Fixed', 'custom-price-calculation' ),
                'percentage' => __( 'Percentage', 'custom-price-calculation' ),
            ),
            'desc_tip' => true,
            'description' => __( 'Select the price calculation type.', 'custom-price-calculation' ),
            'value' => get_post_meta( $variation->ID, '_custom_price_type', true ),
        )
    );

    // Custom text field for Markup
    woocommerce_wp_text_input(
        array(
            'id' => '_custom_profit[' . $loop . ']',
            'label' => __( 'Markup', 'custom-price-calculation' ),
            'desc_tip' => true,
            'description' => __( 'Enter the markup amount.', 'custom-price-calculation' ),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '0',
            ),
            'value' => get_post_meta( $variation->ID, '_custom_profit', true ),
        )
    );

    // Custom text field for Price on the front end
    woocommerce_wp_text_input(
        array(
            'id' => '_custom_price_frontend[' . $loop . ']',
            'label' => __( 'Price on the front end', 'custom-price-calculation' ),
            'desc_tip' => true,
            'description' => __( 'Calculated price to display on the front end.', 'custom-price-calculation' ),
            'type' => 'text',
            'custom_attributes' => array(
                'readonly' => 'readonly',
            ),
            'value' => get_post_meta( $variation->ID, '_custom_price_frontend', true ),
        )
    );

    // Custom button
    echo '<button type="button" class="button custom_price_calculation_variation_button">' . __( 'Confirmed Price', 'custom-price-calculation' ) . '</button>';

    echo '</div>';
}
add_action( 'woocommerce_variation_options_pricing', 'custom_price_calculation_variation_fields', 10, 3 );

function custom_price_calculation_variation_button_script() {
    global $thepostid;

    ?>
    <script>
        jQuery(function($){
            $(document).on('click', '.custom_price_calculation_variation_button', function(){
                var variationRow = $(this).closest('.woocommerce_variation');
                var salePrice = parseFloat(variationRow.find('input[name^="variable_sale_price"]').val());
                var regularPrice = parseFloat(variationRow.find('input[name^="variable_regular_price"]').val());
                var profit = parseFloat(variationRow.find('input[name^="_custom_profit"]').val());
                var calculationType = variationRow.find('select[name^="_custom_price_type"]').val();

                var calculatedPrice = '';

                if (salePrice && calculationType === 'fixed') {
                    calculatedPrice = salePrice + profit;
                } else if (salePrice && calculationType === 'percentage') {
                    calculatedPrice = salePrice + (salePrice * profit / 100);
                } else if (calculationType === 'fixed') {
                    calculatedPrice = regularPrice + profit;
                } else if (calculationType === 'percentage') {
                    calculatedPrice = regularPrice + (regularPrice * profit / 100);
                }

                // Display calculated price
                var displayField = variationRow.find('input[name^="_custom_price_frontend"]');
                displayField.val(calculatedPrice.toFixed(2));

                // Update the variation price display
                variationRow.find('.woocommerce_variation_price').html('<span class="price">' + calculatedPrice.toFixed(2) + '</span>');
            });
        });
    </script>
    <?php
}
add_action( 'admin_footer', 'custom_price_calculation_variation_button_script' );

// Add the logic to update the markup input box in the save_custom_price_calculation_variation_fields function
function save_custom_price_calculation_variation_fields( $variation_id, $loop ) {
    if ( isset( $_POST['_custom_price_type'][ $loop ] ) ) {
        $custom_price_type = sanitize_text_field( $_POST['_custom_price_type'][ $loop ] );
        update_post_meta( $variation_id, '_custom_price_type', $custom_price_type );
    }

    if ( isset( $_POST['_custom_profit'][ $loop ] ) ) {
        $custom_profit = wc_format_decimal( $_POST['_custom_profit'][ $loop ] );
        update_post_meta( $variation_id, '_custom_profit', $custom_profit );

        // Update the markup input field on the variation data meta box
        ?>
        <script>
            jQuery(function($){
                var variationRow = $('.woocommerce_variation[data-row="' + <?php echo $loop; ?> + '"]');
                variationRow.find('input[name^="_custom_profit"]').val(<?php echo $custom_profit; ?>);
            });
        </script>
        <?php
    }

    // Calculate and update the "Price on the front end" field
    
    $sale_price = (float) $_POST['variable_sale_price'][ $loop ];
    $regular_price = (float) $_POST['variable_regular_price'][ $loop ];
    $profit = (float) $_POST['_custom_profit'][ $loop ];
    $calculated_price = '';

    if ( $sale_price && $_POST['_custom_price_type'][ $loop ] === 'fixed' ) {
        $calculated_price = bcadd( $sale_price, $profit, 2 );
    } elseif ( $sale_price && $_POST['_custom_price_type'][ $loop ] === 'percentage' ) {
        $calculated_price = bcadd( $sale_price, bcmul( $sale_price, bcdiv( $profit, 100, 2 ), 2 ), 2 );
    } elseif ( $_POST['_custom_price_type'][ $loop ] === 'fixed' ) {
        $calculated_price = bcadd( $regular_price, $profit, 2 );
    } elseif ( $_POST['_custom_price_type'][ $loop ] === 'percentage' ) {
        $calculated_price = bcadd( $regular_price, bcmul( $regular_price, bcdiv( $profit, 100, 2 ), 2 ), 2 );
    }

    // Update the calculated price for the "Price on the front end" field
    update_post_meta( $variation_id, '_custom_price_frontend', $calculated_price );

    // Update the variation price display on the front end
    update_post_meta( $variation_id, '_price', $calculated_price );
    update_post_meta( $variation_id, '_regular_price', $calculated_price );
}
add_action( 'woocommerce_save_product_variation', 'save_custom_price_calculation_variation_fields', 10, 2 );



// Bulk Action
// Add custom fields to the product bulk edit options
function custom_price_calculation_bulk_edit_fields() {
    global $post;

    echo '<div class="options_group">';

    // Custom dropdown field for Price Calculation Type
    woocommerce_wp_select(
        array(
            'id' => '_custom_price_type_bulk',
            'label' => __( 'Price Calculation Type', 'custom-price-calculation' ),
            'options' => array(
                'fixed' => __( 'Fixed', 'custom-price-calculation' ),
                'percentage' => __( 'Percentage', 'custom-price-calculation' ),
            ),
            'desc_tip' => true,
            'description' => __( 'Select the price calculation type.', 'custom-price-calculation' ),
        )
    );

    // Custom text field for Markup
    woocommerce_wp_text_input(
        array(
            'id' => '_custom_profit_bulk',
            'label' => __( 'Markup', 'custom-price-calculation' ),
            'desc_tip' => true,
            'description' => __( 'Enter the markup amount.', 'custom-price-calculation' ),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '0',
            ),
        )
    );

    echo '</div>';

    ?>
    <script>
        jQuery(function($){
            // Update the function of the markup input box
            function updateMarkupInputFields(calculatedPrice) {
                $('.inline-edit-row .input-text[name^="_custom_profit_bulk"]').val(calculatedPrice.toFixed(2));
            }

            // Listen to the "Save" button click event of batch editing
            $(document).on('click', '#doaction, #doaction2', function(){
                if ($('select[name^="action"]').val() === 'edit') {
                
                    setTimeout(function() {
                        var calculatedPrice = parseFloat($('#_custom_price_frontend_bulk').val());
                        updateMarkupInputFields(calculatedPrice);
                    }, 1000);
                }
            });
        });
    </script>
    <?php
}
add_action( 'woocommerce_product_bulk_edit_start', 'custom_price_calculation_bulk_edit_fields' );


// Bulk Action - Save custom fields data for variations
function save_custom_price_calculation_bulk_fields( $product ) {
    if ( isset( $_REQUEST['_custom_price_type_bulk'] ) ) {
        $custom_price_type_bulk = sanitize_text_field( $_REQUEST['_custom_price_type_bulk'] );
        update_post_meta( $product->get_id(), '_custom_price_type', $custom_price_type_bulk );

        // Update Price Calculation Type for variations
        if ( $product->is_type( 'variable' ) ) {
            $variations = $product->get_available_variations();

            foreach ( $variations as $variation ) {
                $variation_id = $variation['variation_id'];
                update_post_meta( $variation_id, '_custom_price_type', $custom_price_type_bulk );
            }
        }
    }

    if ( isset( $_REQUEST['_custom_profit_bulk'] ) ) {
        $custom_profit_bulk = wc_format_decimal( $_REQUEST['_custom_profit_bulk'] );
        update_post_meta( $product->get_id(), '_custom_profit', $custom_profit_bulk );

        // Update markup input fields for variations
        if ( $product->is_type( 'variable' ) ) {
            $variations = $product->get_available_variations();

            foreach ( $variations as $variation ) {
                $variation_id = $variation['variation_id'];
                update_post_meta( $variation_id, '_custom_profit', $custom_profit_bulk );
            }
        }
    }

    // Update the "Price on the front end" field for variations
    if ( $product->is_type( 'variable' ) ) {
        $variations = $product->get_available_variations();

        foreach ( $variations as $variation ) {
            $variation_id = $variation['variation_id'];
            $sale_price = (float) $variation['display_price'];
            $regular_price = (float) $variation['display_regular_price'];
            $profit = (float) get_post_meta( $variation_id, '_custom_profit', true );

            if ( $sale_price && $custom_price_type_bulk === 'fixed' ) {
                $calculated_price = bcadd( $sale_price, $profit, 2 ); // Use bcadd function for high-precision addition, keeping 2 decimal places
            } elseif ( $sale_price && $custom_price_type_bulk === 'percentage' ) {
                $calculated_price = bcadd( $sale_price, bcmul( $sale_price, bcdiv( $profit, 100, 2 ), 2 ), 2 ); // Use bcadd, bcmul, and bcdiv functions for high-precision percentage calculation, keeping 2 decimal places
            } elseif ( $custom_price_type_bulk === 'fixed' ) {
                $calculated_price = bcadd( $regular_price, $profit, 2 ); // Use bcadd function for high-precision addition, keeping 2 decimal places
            } elseif ( $custom_price_type_bulk === 'percentage' ) {
                $calculated_price = bcadd( $regular_price, bcmul( $regular_price, bcdiv( $profit, 100, 2 ), 2 ), 2 ); // Use bcadd, bcmul, and bcdiv functions for high-precision percentage calculation, keeping 2 decimal places
            } else {
                $calculated_price = '';
            }

            update_post_meta( $variation_id, '_custom_price_frontend', $calculated_price );
        }
    } else {
        // For simple and other product types, perform price calculation and update the "Price on the front end" field
        $sale_price = (float) $product->get_sale_price();
        $regular_price = (float) $product->get_regular_price();
        $profit = (float) $custom_profit_bulk;

        if ( $sale_price && $custom_price_type_bulk === 'fixed' ) {
            $calculated_price = bcadd( $sale_price, $profit, 2 ); // Use bcadd function for high-precision addition, keeping 2 decimal places
        } elseif ( $sale_price && $custom_price_type_bulk === 'percentage' ) {
            $calculated_price = bcadd( $sale_price, bcmul( $sale_price, bcdiv( $profit, 100, 2 ), 2 ), 2 ); // Use bcadd, bcmul, and bcdiv functions for high-precision percentage calculation, keeping 2 decimal places
        } elseif ( $custom_price_type_bulk === 'fixed' ) {
            $calculated_price = bcadd( $regular_price, $profit, 2 ); // Use bcadd function for high-precision addition, keeping 2 decimal places
        } elseif ( $custom_price_type_bulk === 'percentage' ) {
            $calculated_price = bcadd( $regular_price, bcmul( $regular_price, bcdiv( $profit, 100, 2 ), 2 ), 2 ); // Use bcadd, bcmul, and bcdiv functions for high-precision percentage calculation, keeping 2 decimal places
        } else {
            $calculated_price = '';
        }

        update_post_meta( $product->get_id(), '_custom_price_frontend', $calculated_price );
    }
}
add_action( 'woocommerce_product_bulk_edit_save', 'save_custom_price_calculation_bulk_fields', 10, 1 );






