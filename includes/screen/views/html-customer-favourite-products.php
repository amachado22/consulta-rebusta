<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="postbox" id="wc_crm-favourite-products">
	<button class="handlediv button-link" aria-expanded="true" type="button">
		<span class="toggle-indicator" aria-hidden="true"></span>
	</button>
	<h3 class="hndle ui-sortable-handle"><span><?php _e( 'Favourite Products', 'wc_crm' ) ?></span></h3>
	<div class="inside">
        <p class="form-field favourite_products_field ">
            <?php
            $products = wc_get_products(array('limit' => -1));
            $fav_products = get_user_meta($the_customer->user_id, 'favourite_products', true) ?: array();
            ?>
            <label for="favourite_products"><?php _e('Products', 'wc_crm') ?></label>
            <select name="favourite_products[]" id="favourite_products" class="wc-product-search" data-placeholder="Select favourite products" multiple>
                <?php foreach ($fav_products as $id): ?>
                    <?php $product = wc_get_product($id); ?>
                    <option value="<?php echo $product->get_id(); ?>" selected><?php echo $product->get_formatted_name(); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <button class="button" id="fav_new_order"><?php _e('Place Order', 'wc_crm') ?></button>
	</div>
</div>