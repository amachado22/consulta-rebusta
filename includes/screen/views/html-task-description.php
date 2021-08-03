<div id="postdivrich" class="postarea<?php if ( $_wp_editor_expand ) { echo ' wp-editor-expand'; } ?>">

		<?php wp_editor( $post->post_content, 'content', array(
			'_content_editor_dfw' => $_content_editor_dfw,
			'drag_drop_upload' => true,
			'tabfocus_elements' => 'content-html,save-post',
			'editor_height' => 300,
			'tinymce' => array(
				'resize' => false,
				'wp_autoresize_on' => $_wp_editor_expand,
				'add_unload_trigger' => false,
			),
		) ); ?>
		<table id="post-status-info"><tbody><tr>
			<td id="wp-word-count" class="hide-if-no-js"><?php printf( __( 'Word count: %s' ), '<span class="word-count">0</span>' ); ?></td>
			<td class="autosave-info">
			<span class="autosave-message">&nbsp;</span>
		<?php
			if ( 'auto-draft' != $post->post_status ) {
				echo '<span id="last-edit">';
				if ( $last_user = get_userdata( get_post_meta( $post->ID, '_edit_last', true ) ) ) {
					printf(__('Last edited by %1$s on %2$s at %3$s'), esc_html( $last_user->display_name ), mysql2date(get_option('date_format'), $post->post_modified), mysql2date(get_option('time_format'), $post->post_modified));
				} else {
					printf(__('Last edited on %1$s at %2$s'), mysql2date(get_option('date_format'), $post->post_modified), mysql2date(get_option('time_format'), $post->post_modified));
				}
				echo '</span>';
			} ?>
			</td>
			<td id="content-resize-handle" class="hide-if-no-js"><br /></td>
		</tr></tbody></table>

</div>