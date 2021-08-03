<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="postbox" id="woocommerce-customer-calls">
	<button class="handlediv button-link" aria-expanded="true" type="button">
		<span class="toggle-indicator" aria-hidden="true"></span>
	</button>
	<h3 class="hndle ui-sortable-handle"><span><?php _e( 'Customer Calls', 'wc_crm' ) ?></span></h3>
	<div class="inside" style="margin:0px; padding:0px;">
		<?php 
		$calls = $the_customer->get_calls();
		$calls_list = new WC_CRM_Table_Customer_Calls($calls);
		$calls_list->prepare_items();
		$calls_list->display();
		?>
	</div>
</div>