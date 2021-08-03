<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$statuses   = wc_crm_get_task_statuses();
$priorities = wc_crm_get_task_priorities();
?>
<div id="submitpost" class="submitbox">
	<div id="minor-publishing">
		<div id="misc-publishing-actions">
			<div class="misc-pub-section misc-pub-post-status"><label for="post_status"><?php _e('Status:') ?></label>
				<span id="post-status-display">
				<?php
				if(isset($statuses[$task->task_status])){
					echo $statuses[$task->task_status];
				}else{
					echo $task->task_status;
				}
				?>
				</span>
				<?php if ( $can_publish ) { ?>
					<a href="#post_status" class="edit-task-status hide-if-no-js">
						<span aria-hidden="true"><?php _e( 'Edit' ); ?></span>
						<span class="screen-reader-text"><?php _e( 'Edit status' ); ?></span>
					</a>

					<div id="post-status-select" class="hide-if-js">
					<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo $task->task_status; ?>" />
					<select name='post_status' id='post_status'>
						<?php if ( !empty($statuses) ) : ?>
						<?php foreach ($statuses as $status => $label) : ?>
							<option<?php selected( $task->task_status, $status ); ?> value='<?php echo $status; ?>'><?php echo $label; ?></option>
						<?php endforeach; ?>
						<?php else : ?>
							<option<?php selected( $task->task_status, 'publish' ); ?> value='publish'><?php _e('Published') ?></option>
						<?php endif; ?>
					</select>
					 <a href="#post_status" class="save-task-status hide-if-no-js button"><?php _e('OK'); ?></a>
					 <a href="#post_status" class="cancel-task-status hide-if-no-js button-cancel"><?php _e('Cancel'); ?></a>
					</div>

				<?php } ?>
			</div><!-- .misc-pub-section -->


			<?php
			/* translators: Publish box date format, see http://php.net/date */
			$datef = __( 'M j, Y @ H:i' );
			$stamp = __('Due Date: <b>%1$s</b>', 'wc_crm');
			$date = '&dash;';
			if ( 0 != $post->ID && !empty($task->due_date) && $task->due_date != '0000-00-00 00:00:00' ) {
				$date = date_i18n( $datef, strtotime( $task->due_date ) );
			} else if( !$post->post_date_gmt || '0000-00-00 00:00:00' == $post->post_date_gmt) {
				$current_time = strtotime(current_time('mysql'));
				$date = date_i18n( $datef, $current_time );
				#$date = date_i18n( $datef, strtotime( '+2 hours', $current_time ) );
			}			

			if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>
			<div class="misc-pub-section curtime misc-pub-curtime">
				<span id="due_date">
				<?php printf($stamp, $date); ?></span>
				<a href="#edit_due_date" class="edit-due_date hide-if-no-js"><span aria-hidden="true"><?php _e( 'Edit' ); ?></span> <span class="screen-reader-text"><?php _e( 'Edit date and time' ); ?></span></a>
				<fieldset id="due_datediv" class="hide-if-js">
				<legend class="screen-reader-text"><?php _e( 'Date and time' ); ?></legend>
				<?php WC_CRM_Screen_Task::touch_time( ( $action === 'edit' ), 1 ); ?>
				</fieldset>
			</div><?php // /misc-pub-section ?>
			<?php endif; ?>
			<div class="misc-pub-section misc-pub-post-priority"><label for="task_priority"><?php _e( 'Priority:', 'wc_crm' ); ?></label>
				<span id="task-priority-display">
				<?php
				if(isset($priorities[$task->priority])){
					echo $priorities[$task->priority];
				}else{
					echo $task->priority;
				}
				?>
				</span>
				<?php if ( $can_publish ) { ?>
					<a href="#task_priority" class="edit-task-priority hide-if-no-js">
						<span aria-hidden="true"><?php _e( 'Edit' ); ?></span>
						<span class="screen-reader-text"><?php _e( 'Edit priority' ); ?></span>
					</a>

					<div id="task-priority-select" class="hide-if-js">
					<input type="hidden" name="hidden_task_priority" id="hidden_task_priority" value="<?php echo $task->priority; ?>" />
					<select name='priority' id='task_priority'>
						<?php if ( !empty($priorities) ) : ?>
						<?php foreach ($priorities as $priority => $label) : ?>
							<option<?php selected( $task->priority, $priority ); ?> value='<?php echo $priority; ?>'><?php echo $label; ?></option>
						<?php endforeach; ?>
						<?php endif; ?>
					</select>
					 <a href="#task_priority" class="save-task-priority hide-if-no-js button"><?php _e('OK'); ?></a>
					 <a href="#task_priority" class="cancel-task-priority hide-if-no-js button-cancel"><?php _e('Cancel'); ?></a>
					</div>

				<?php } ?>
			</div><!-- .misc-pub-section -->








		</div>
		<div class="clear"></div>
	</div>
	<div id="major-publishing-actions">
		<div id="delete-action">
		<?php
		if ( current_user_can( "delete_post", $post->ID ) ) {
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
					<?php submit_button( __( 'Create Task', 'wc_crm' ), 'primary button-large', 'publish', false ); ?>
			<?php
				endif;
			} else { ?>
					<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update') ?>" />
					<input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_attr_e( 'Save Task', 'wc_crm' ) ?>" />
			<?php
			} ?>
		</div>
		<div class="clear"></div>
	</div>
</div>