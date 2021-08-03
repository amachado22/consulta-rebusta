<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$statuses   = wc_crm_get_call_statuses();
?>
<div id="submitpost" class="submitbox">
	<div id="minor-publishing">
		<div id="misc-publishing-actions">
			<div class="misc-pub-section misc-pub-post-status"><label for="call_status"><?php _e( 'Call Details:', 'wc_crm' ); ?></label>
				<span id="post-status-display">
				<?php
				if(isset($statuses[$call->call_status])){
					echo $statuses[$call->call_status];
				}else{
					echo $call->call_status;
				}
				?>
				</span>
				<?php if ( $can_publish ) { ?>
					<a href="#post_status" class="edit-post-status hide-if-no-js">
						<span aria-hidden="true"><?php _e( 'Edit' ); ?></span>
						<span class="screen-reader-text"><?php _e( 'Edit status' ); ?></span>
					</a>

					<div id="post-status-select" class="hide-if-js">
					<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo $call->call_status; ?>" />
					<select name='post_status' id='post_status'>
						<?php if ( !empty($statuses) ) : ?>
						<?php foreach ($statuses as $status => $label) : ?>
							<option<?php selected( $call->call_status, $status ); ?> value='<?php echo $status; ?>'><?php echo $label; ?></option>
						<?php endforeach; ?>
						<?php else : ?>
							<option<?php selected( $call->call_status, 'publish' ); ?> value='publish'><?php _e('Published') ?></option>
						<?php endif; ?>
					</select>
					 <a href="#post_status" class="save-post-status hide-if-no-js button"><?php _e('OK'); ?></a>
					 <a href="#post_status" class="cancel-post-status hide-if-no-js button-cancel"><?php _e('Cancel'); ?></a>
					</div>

				<?php } ?>
			</div><!-- .misc-pub-section -->


			<?php
			/* translators: Publish box date format, see http://php.net/date */
			$datef = __( 'M j, Y @ H:i' );
			$stamp = __('Call Date: <b>%1$s</b>', 'wc_crm');
			$date = '&dash;';
			if ( time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { // draft, 1 or more saves, future date specified
				$stamp = __('Schedule for: <b>%1$s</b>');
			}
			if ( 0 != $call->id ) {
				$date = date_i18n( $datef, strtotime( $call->call_date ) );
			}			

			if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>
			<div class="misc-pub-section curtime misc-pub-curtime">
				<span id="timestamp">
				<?php printf($stamp, $date); ?></span>
				<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js"><span aria-hidden="true"><?php _e( 'Edit' ); ?></span> <span class="screen-reader-text"><?php _e( 'Edit date and time' ); ?></span></a>
				<fieldset id="timestampdiv" class="hide-if-js">
				<legend class="screen-reader-text"><?php _e( 'Date and time' ); ?></legend>
				<?php touch_time( ( $action === 'edit' ), 1 ); ?>
				</fieldset>
			</div><!-- .misc-pub-section -->
			<?php endif; ?>

			<div class="misc-pub-section misc-pub-duration" <?php echo ($call->call_status == 'future') ? 'style="display:none;"' : ''; ?>>

				<label for="call_duration"><?php _e( 'Call Duration:', 'wc_crm' ); ?></label>
				<span id="call_duration" <?php echo ($call->call_status == 'wcrm-current') ? 'style="display:none;"' : ''; ?>><?php echo $call->get_formated_call_duration(); ?></span>
				<a href="#call_duration" class="edit-call-duration hide-if-no-js" <?php echo ($call->call_status == 'wcrm-current') ? 'style="display:none;"' : ''; ?>>
					<span aria-hidden="true"><?php _e( 'Edit' ); ?></span>
				</a>
				<div id="call-duration-input" class="hide-if-js wrap_disabled" <?php echo ($call->call_status == 'wcrm-current') ? 'style="display:none;"' : ''; ?>>
					<input type="number" name="call_duration_h" id="call_duration_h" class="call_time" value="<?php echo $call->call_duration[0]; ?>"> <?php _e('h', 'wc_crm'); ?>
					<input type="number" name="call_duration_m" id="call_duration_m" class="call_time" value="<?php echo $call->call_duration[1]; ?>"> <?php _e('m', 'wc_crm'); ?>
					<input type="number" name="call_duration_s" id="call_duration_s" class="call_time" value="<?php echo $call->call_duration[2]; ?>"> <?php _e('s', 'wc_crm'); ?>

					<input type="hidden" name="hidden_call_duration_h" id="hidden_call_duration_h" value="<?php echo $call->call_duration[0]; ?>">
					<input type="hidden" name="hidden_call_duration_m" id="hidden_call_duration_m" value="<?php echo $call->call_duration[1]; ?>">
					<input type="hidden" name="hidden_call_duration_s" id="hidden_call_duration_s" value="<?php echo $call->call_duration[2]; ?>">
					<p>
						<a href="#call_duration" class="save-call-duration hide-if-no-js button"><?php _e('OK'); ?></a>
					 	<a href="#call_duration" class="cancel-call-duration hide-if-no-js button-cancel"><?php _e('Cancel'); ?></a>
					</p>
				</div>
				<div class="hide-if-no-js" id="call-duration-timer" <?php echo ($call->call_status != 'wcrm-current') ? 'style="display:none;"' : ''; ?>>
					<?php 
					$duration = wc_crm_get_cookie_call_duration($post);
					?>
					<div class="display_time"><?php echo $duration ? wc_crm_formatTime($duration) : '00:00:00:00'; ?></div>
					<a href="#" class="button tips" id="start_timer" data-tip="<?php _e('Start', 'wc_crm'); ?>"></a>
					<a href="#" class="button tips" id="stop_timer" data-tip="<?php _e('Stop', 'wc_crm'); ?>"><i class="ico_stop"></i></a>
					<a href="#" class="button tips" id="pause_timer" data-tip="<?php _e('Pause/Resume', 'wc_crm'); ?>"><i class="ico_pause"></i></a>
					<a href="#" class="button tips" id="save_timer" data-tip="<?php _e('Save', 'wc_crm'); ?>"><i class="ico_save"></i></a>
					<a href="#" class="button tips" id="reset_timer" data-tip="<?php _e('Reset', 'wc_crm'); ?>"><i class="ico_reset"></i></a>

				</div>
			</div><!-- .misc-pub-section -->




		</div>
		<div class="clear"></div>
	</div>
	<div id="major-viewing-actions" class="hide-if-no-js">		
		<?php 
			$c_url = !empty($call->customer_id) ? sprintf(get_admin_url().'admin.php?page='.WC_CRM_TOKEN.'&c_id=%d', $call->customer_id) : '#' ;
			$p_url = !empty($call->product) ? sprintf(get_admin_url().'post.php?post=%d&action=edit', $call->product) : '#' ;
			$oa_url = !empty($call->order) ? sprintf(get_admin_url().'post.php?post=%d&action=edit', $call->order) : '#' ;
		?>
		<a href="tel:<?php echo $call->phone_number; ?>" data-tip="<?php _e('Place Call', 'wc_crm'); ?>" class="button tips place_call <?php echo empty($call->phone_number) ? 'disabled' : ''; ?>">
		</a>
		<a href="<?php echo $c_url; ?>" target="_blank" data-tip="<?php _e('View Customer', 'wc_crm'); ?>" class="button tips view_customer <?php echo empty($call->customer_id) ? 'disabled' : ''; ?>">
		</a>
		<a href="<?php echo $oa_url; ?>" target="_blank" data-tip="<?php _e('View Order', 'wc_crm'); ?>" class="button tips view_order <?php echo empty($call->order) ? 'disabled' : ''; ?>">
		</a>
		<a href="<?php echo $p_url; ?>" target="_blank" data-tip="<?php _e('View Product', 'wc_crm'); ?>" class="button tips view_product <?php echo empty($call->product) ? 'disabled' : ''; ?>">
		</a>
		<div class="clear"></div>
	</div>
	<div id="major-publishing-actions">
		<div id="delete-action">
		<?php
		if ( current_user_can( "delete_post", $post->ID ) &&  $action == 'edit' ) {
			if ( !EMPTY_TRASH_DAYS )
				$delete_text = __('Delete Permanently');
			else
				$delete_text = __('Move to Trash');
			?>
		<a class="submitdelete deletion" href="<?php echo get_delete_post_link($post->ID); ?>"><?php echo $delete_text; ?></a><?php
		} ?>
		</div>
		<div id="publishing-action">
			<span class="spinner"></span>
			<?php
			if ( $action !== 'edit' ) {
				if ( $can_publish ) : ?>
					<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Publish') ?>" />
					<?php submit_button( __( 'Create Call', 'wc_crm' ), 'primary button-large', 'publish', false ); ?>
			<?php
				endif;
			} else { ?>
				<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update') ?>" />
				<input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_attr_e( 'Save Call', 'wc_crm' ) ?>" />
			<?php
			} ?>
		</div>
		<div class="clear"></div>
	</div>
</div>