<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $the_customer, $post_id;

$the_customer = new WC_CRM_Customer(get_post_meta($post_id, 'validation_customer', true));
$validation_notes = WC_CRM_VALIDATION::get_validation_notes($the_customer);
if(count($validation_notes)){
    echo "<ul class='order_notes'>";
    foreach ($validation_notes as $note){ ?>

        <li rel="<?php echo $note->comment_ID?>">
            <div class="note_content">
                <p><?php echo $note->comment_content ?></p>
            </div>
            <p class="meta">
                <abbr class="exact-date" title="<?php echo $note->comment_date_gmt ?>">
                    <?php printf( __( 'added %s ago', 'wc_crm' ), human_time_diff( strtotime( $note->comment_date_gmt ), current_time( 'timestamp', 1 ) ) ); ?>
                </abbr>
                <?php printf( '  ' . __('by %s', 'wc_crm' ), get_userdata($note->user_id)->user_nicename); ?>
            </p>
        </li>

    <?php }
    echo "</ul>";
}else{
    _e("No validation notes yet.", 'wc_crm');
}