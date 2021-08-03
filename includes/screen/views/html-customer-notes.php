<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="postbox " id="woocommerce-customer-notes">
	<button class="handlediv button-link" aria-expanded="true" type="button">
		<span class="toggle-indicator" aria-hidden="true"></span>
	</button>
	<h3 class="hndle"><span><?php _e( 'Customer Notes', 'wc_crm' ) ?></span></h3>
		<div class="inside" style="padding:0px;">
			<ul class="order_notes">
			<?php  $notes = $the_customer->get_customer_notes(); ?>
			<?php if ( $notes ) {
				foreach( $notes as $note ) {
					?>
					<li style="padding: 0 10px;"rel="<?php echo absint( $note->comment_ID ) ; ?>">
						<div class="note_content <?php echo get_comment_meta($note->comment_ID, 'customer_note_type', true) ?>">
							<?php echo wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ); ?>
						</div>
						<p class="meta">
							<abbr class="exact-date" title="<?php echo $note->comment_date_gmt; ?> GMT"><?php printf( __( 'added %s ago', 'wc_crm' ), human_time_diff( strtotime( $note->comment_date_gmt ), current_time( 'timestamp', 1 ) ) ); ?></abbr>
							<?php if ( $note->comment_author !== __( 'WooCommerce', 'wc_crm' ) ) printf( ' ' . __( 'by %s', 'wc_crm' ), $note->comment_author ); ?>
							<a href="#" class="delete_customer_note"><?php _e( 'Delete note', 'wc_crm' ); ?></a>
						</p>
					</li>
					<?php
				}
			} else {
				echo '<li>' . __( 'There are no notes yet.', 'wc_crm' ) . '</li>';
			} ?>
			</ul>
			<div class="add_note">
				<h4><?php _e( 'Add note', 'wc_crm' ) ?></h4>
			<p>
				<textarea rows="5" cols="20" class="input-text" id="add_order_note" name="order_note" type="text"></textarea>
			</p>
			<p>
                <select name="customer_note_type" id="customer_note_type">
                    <option value="private"><?php _e( 'Private note', 'woocommerce' ); ?></option>
                    <option value="customer"><?php _e( 'Note to customer', 'woocommerce' ); ?></option>
                    <option value="agent"><?php _e( 'Note to agent', 'wc_crm' ); ?></option>
                    <option value="all"><?php _e( 'Note to agent & customer', 'wc_crm' ); ?></option>
                </select>
				<a class="add_note_customer button" href="#"><?php _e( 'Add', 'wc_crm' ) ?></a>
			</p>
		</div>
	</div>
</div>