<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="postbox mm" id="woocommerce-customer-tasks">
	<button class="handlediv button-link" aria-expanded="true" type="button">
		<span class="toggle-indicator" aria-hidden="true"></span>
	</button>
	<h3 class="hndle ui-sortable-handle"><span><?php _e( 'Customer Tasks', 'wc_crm' ) ?></span></h3>
	<div class="inside" style="margin:0px; padding:0px;">
		<?php
		$tasks = $the_customer->get_tasks();
		$tasks_list = new WC_CRM_Table_Customer_Tasks($tasks);
		$tasks_list->prepare_items();
		$tasks_list->display();
		?>
	</div>
</div>