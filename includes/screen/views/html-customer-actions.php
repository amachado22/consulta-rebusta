<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="postbox " id="woocommerce-order-actions" style="display: block;">
	<button class="handlediv button-link" aria-expanded="true" type="button">
		<span class="toggle-indicator" aria-hidden="true"></span>
	</button>
	<h3 class="hndle"><span><?php _e( 'Customer Actions', 'wc_crm' ); ?></span></h3>
	<div class="inside">
		<div id="submitpost" class="submitbox">
			<ul class="order_actions submitbox">
				<?php if($the_customer->customer_id > 0) { ?>
				<li id="actions" class="wide">
					<select name="wc_crm_customer_action" id="wc_crm_customer_action">
						<option value=""><?php _e( 'Actions', 'wc_crm' ) ?></option>
						<?php
						$order_url = 'post-new.php?post_type=shop_order&c_id=' . $the_customer->customer_id;
						$email_url = 'admin.php?page='.WC_CRM_TOKEN.'&screen=email&c_id=' . $the_customer->customer_id;
						$phone_url = 'post-new.php?post_type='.WC_CRM_TOKEN.'_calls&c_id=' . $the_customer->customer_id;
						$task_url  = 'post-new.php?post_type='.WC_CRM_TOKEN.'_tasks&c_id=' . $the_customer->customer_id;
						?>
						<option value="wc_crm_customer_action_new_order" data-url="<?php echo $order_url; ?>" ><?php _e( 'New order', 'wc_crm' ) ?></option>
						<option value="wc_crm_customer_action_send_email" data-url="<?php echo $email_url; ?>" ><?php _e( 'Send email', 'wc_crm' ) ?></option>
						<option value="wc_crm_customer_action_phone_call" data-url="<?php echo $phone_url; ?>" ><?php _e( 'Add a new call', 'wc_crm' ) ?></option>
						<option value="wc_crm_customer_action_new_task" data-url="<?php echo $task_url; ?>" ><?php _e( 'Add a new task', 'wc_crm' ) ?></option>
					</select>
					<button title="Apply" class="button wc-reload wc_crm_new_action"><span><?php _e( 'Apply', 'wc_crm' ) ?></span></button>
					<a href="" class="wc_crm_new_action_href" target="_blank" style="display: none;">_</a>
				</li>
				<?php }else{ ?>
				<input type="hidden" name="wc_crm_customer_action" value="create_customer">				
				<?php } ?>
			</ul>
			<div id="major-publishing-actions">
				<?php if( isset($_GET['c_id'])){ ?>
				<div id="delete-action">
					<a href="<?php echo add_query_arg('wc_crm_customer_action', 'trash') ; ?>" class="submitdelete deletion" onclick=" return confirm( '<?php echo esc_js(__('Are you sure you want to delete the customer?', 'wc_crm') ); ?>')" />
						<?php _e('Move to Trash') ?>
					</a>
				</div>
				<?php } ?>
				<div id="publishing-action">
					<span class="spinner"></span>
					<input type="submit" value="<?php _e( 'Save', 'wc_crm' ) ?>" name="save" style="float: right;" class="button save_customer button-primary wc_crm_new_action">
				</div>
				<div class="clear"></div>
			</div>
			
		</div>
	</div>
</div>
