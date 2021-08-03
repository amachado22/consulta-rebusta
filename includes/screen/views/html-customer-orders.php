<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="postbox" id="woocommerce-customer-orders">
	<button class="handlediv button-link" aria-expanded="true" type="button">
		<span class="toggle-indicator" aria-hidden="true"></span>
	</button>
	<h3 class="hndle ui-sortable-handle"><span><?php _e( 'Customer Orders', 'wc_crm' ) ?></span></h3>
	<div class="inside" style="margin:0px; padding:0px;">
		<?php 
		$orders = $the_customer->get_orders();
		$order_list = new WC_CRM_Table_Customer_Orders($orders);
		$order_list->prepare_items();
		$order_list->display();
		?>
	</div>
</div>
<input type="hidden" id="count_customer_orders" value="<?php echo count($orders); ?>">