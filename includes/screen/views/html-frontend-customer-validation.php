<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$customer = wc_crm_get_customer(get_current_user_id(), 'user_id');
if(!$customer){
    return;
}

$validations = get_posts(array(
    'post_type' => 'wc_crm_validations',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => 'validation_customer',
            'value' => $customer->c_id,
            'compare' => '='
        )
    )
));

?>
    <div id="validation_upload_container">
        <?php
        $instructions = get_option('wc_crm_instruction_validation');
        if(!empty($instructions))
            echo '<p>' . $instructions . '</p>';
        ?>

        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="validation_type"><?php _e("Document Type:", "wc_crm") ?><span class="required">*</span></label>
            <select name="validation_type" id="validation_type">
                <option value="passport"><?php _e("Passport ", "wc_crm") ?></option>
                <option value="driver-license"><?php _e("Drivers License ", "wc_crm") ?></option>
                <option value="utility-bill"><?php _e("Utility Bill ", "wc_crm") ?></option>
                <option value="utility-bill"><?php _e("Flight Ticket ", "wc_crm") ?></option>
                <option value="utility-bill"><?php _e("Insurance Certificate ", "wc_crm") ?></option>
                <option value="other"><?php _e("Other ", "wc_crm") ?></option>
            </select>
        </p>
        <div id="validation-upload" class="dropzone"></div>
        <div class="btn-container">
            <button id="submit-validation" class="button button-primary"><?php _e('Submit', 'wc_crm'); ?></button>
        </div>
    </div>

<?php if(count($validations)) : ?>
    <table id="validation_table">
        <thead>
        <tr>
            <th style="display:none;"><?php _e('ID', 'wc_crm'); ?></th>
            <th><?php _e('Date', 'wc_crm'); ?></th>
            <th><?php _e('File', 'wc_crm'); ?></th>
            <th><?php _e('Status', 'wc_crm'); ?></th>
            <th><?php _e('Actions', 'wc_crm'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($validations as $validation){
            $status = array(
                0 => 'Cancelled',
                1 => 'Awaiting Confirmation',
                2 => 'Confirmed'
            );
            $file = get_post_meta($validation->ID, 'validation_file', true);
            $v_status = get_post_meta($validation->ID, 'validation_status', true);
            ?>
            <tr>
                <td style="display: none"><?php echo $validation->ID; ?></td>
                <td><?php echo date('d-m-Y', strtotime($validation->post_date)); ?></td>
                <td><a href="<?php echo $file['url'] ?>" target="_blank"> <?php echo basename($file['file']); ?> </a></td>
                <td><?php echo __($status[$v_status], 'wc_crm'); ?></td>
                <td width="110">
                    <ul class="validation-actions">
                        <li><a class="view-file" href="<?php $file['url'] ?>" target="_blank"><i class="fa fa-eye"></i></a></li>
                        <?php if($v_status == 1) : ?>
                        <li><a class="approve-file"><i class="fa fa-trash-alt"></i></a></li>
                        <?php endif; ?>
                    </ul>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
<?php endif; ?>
