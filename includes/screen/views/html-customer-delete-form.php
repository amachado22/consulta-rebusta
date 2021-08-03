<div id="delete_customer_popup" class="overlay_media_popup">
    <div class="media-modal wp-core-ui">
    	<a href="#" class="media-modal-close"><span class="media-modal-icon"><span class="screen-reader-text"><?php _e( 'Close', 'wc_crm' ); ?></span></span></a>
    	<div class="media-modal-content">
    		<div class="media-frame mode-select wp-core-ui hide-menu">
    			<div class="media-frame-title"><h1><?php _e( 'Customer Orders', 'wc_crm' ); ?></h1></div>
    			<form id="delete_customer_form" method="post">    				
	    			<div class="media-frame-content">
	    				<p>
	    					<input type="radio" id="retain_order_details" name="delete_type" class="delete_customer_input" value="1">
	    					<label for="retain_order_details"><?php _e( 'Retain orders and convert customer to Guest', 'wc_crm' ); ?></label>
	    				</p>
	    				<p>
	    					<input type="radio" id="delete_related_orders" name="delete_type" class="delete_customer_input" value="2">
	    					<label for="delete_related_orders"><?php _e( 'Delete related orders and WP user profile', 'wc_crm' ); ?></label>
	    				</p>
	    			</div>
    				
    				<div class="media-frame-toolbar">
	    				<div class="media-toolbar">
		    				<div class="media-toolbar-secondary"></div>
		    				<div class="media-toolbar-primary search-form">
		    					<input type="hidden" name="wc_crm_customer_action" value="delete">
		    					<input type="hidden" name="ids" value="" id="wc_crm_delete_customer_ids">
		    					<button type="submit" class="button media-button button-primary button-large accept_delete_customer"><?php _e( 'Delete', 'wc_crm' ); ?></button>
		    				</div>
	    				</div>
    				</div>
				</form>
    		</div>
    	</div>
    </div>
    <div class="media-modal-backdrop"></div>
</div>