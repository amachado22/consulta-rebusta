<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="postbox " id="woocommerce-customer-groups" style="display: block;">
	<button class="handlediv button-link" aria-expanded="true" type="button">
		<span class="toggle-indicator" aria-hidden="true"></span>
	</button>
	<h3 class="hndle"><span><?php _e( 'Customer Groups', 'wc_crm' ); ?></span></h3>
	<div class="inside">
	<?php 
	global $wpdb;
	$static_groups   = wc_crm_get_static_groups();
	$customer_groups = $the_customer->get_groups();
	
	?>
			<ul class="customer_groups submitbox">
				<li id="actions" class="wide">
					<select name="wc_crm_customer_groups[]" id="wc_crm_customer_groups" multiple="true" class="chosen_select" data-placeholder="<?php _e( 'Search for a group…', 'wc_crm' ); ?>">
					<?php
					if($static_groups):
						foreach ($static_groups as $group) :
						?>
							<option value="<?php echo $group->ID; ?>" <?php selected(true, in_array($group->ID, $customer_groups), true ); ?> ><?php echo $group->group_name; ?></option>
						<?php
						endforeach;
					endif;
				    ?>
					</select>
				</li>
			</ul>
	</div>
</div>