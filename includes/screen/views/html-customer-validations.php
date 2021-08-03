<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_thickbox();

$validations = WC_CRM_VALIDATION::get_validations('customer', $the_customer->customer_id, '=', false);

?>
<div class="postbox " id="wc_crm-valdiations">
	<button class="handlediv button-link" aria-expanded="true" type="button">
		<span class="toggle-indicator" aria-hidden="true"></span>
	</button>
	<h3 class="hndle"><span><?php _e( 'Customer Documents', 'wc_crm' ) ?></span></h3>
    <div id="validations" class="inside" style="padding:0px;">
        <ul class="validation_files">
        <?php  $notes = $the_customer->get_customer_notes(); ?>
        <?php if ( $validations ) {
            foreach( $validations as $validation ) {
                $attachment = get_post_meta($validation->ID, 'validation_file', true);
                $status = get_post_meta($validation->ID, 'validation_status', true);
                $attachment_name = basename( $attachment['file'] );
                $path = $attachment['type'] == 'application/pdf' ? includes_url('images/media/document.png') : $attachment['url'];
                ?>
                <li>
                    <a href="#TB_inline?height=300&width=300&inlineId=validationModal" class="validation-thickbox" title="Validation Approval"
                       data-post="<?php echo $validation->ID  ?>"
                       data-status="<?php echo $status ?>"
                       data-file=<?php echo $attachment['url'] ?> >
                        <img src="<?php echo $path ?>" width="40px" height="40px  ">
                        <span class="file-name"><?php echo $attachment_name; ?></span>
                    </a>
                </li>
                <?php
            }
        } else {
            echo '<li>' . __( 'There are no documents yet.', 'wc_crm' ) . '</li>';
        } ?>
        </ul>
    </div>
</div>
<div id="validationModal" style="display:none">
    <h2></h2>
    <div class="file-frame">
        <img src="<?php echo WC()->plugin_url() . '/assets/images/placeholder.png' ?>"  width="150" height = "150"/>
        <a href="" class="button-primary upload-preview" target="_blank"><?php _e( 'Preview', 'wc_crm' ) ?></a>
    </div>
    <div id="validationForm">
        <input type="hidden" name="post_id" id="post_id" value="0">
        <p class="form-row">
            <label for="validation_status"><?php _e( 'Validation Status', 'wc_crm' ) ?></label>
            <select name="validation_status" id="validation_status">
                <option value="1"><?php _e( 'Awaiting Confirmation', 'wc_crm' ) ?></option>
                <option value="2"><?php _e( 'Confirmed', 'wc_crm' ) ?></option>
                <option value="0"><?php _e( 'Cancelled', 'wc_crm' ) ?></option>
            </select>
        </p>
        <p class="form-row">
            <button id="submitValidation" class="button-primary"><?php _e( 'Change Status', 'wc_crm' ) ?></button>
        </p>
    </div>
</div>