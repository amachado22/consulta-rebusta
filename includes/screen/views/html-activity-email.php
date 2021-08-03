<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="panel-wrap woocommerce activity-email">
    <div id="log_data" class="panel">
	    <h2><?php _e('Activity #', 'wc_crm'); ?><?php echo $the_activity->ID; ?><?php _e(' details', 'wc_crm'); ?></h2>
	    <p class="email_date_time"><?php _e('Email sent on ', 'wc_crm'); ?><?php echo $date; ?> <?php _e('at', 'wc_crm'); ?> <?php echo $time; ?></p>
    <table class="view-activity">
      <tr>
        <th width="200"><strong><?php _e('From', 'wc_crm'); ?></strong></th>
        <td>
        <?php 
          $userdata = get_userdata( $the_activity->user_id ); 
          echo '<a href="mailto:'.$userdata->user_email.'">'.$userdata->user_email.'</a> (<a href="user-edit.php?user_id='.$the_activity->user_id.'">'.$userdata->user_firstname.' '.$userdata->user_lastname.'</a>)';
        ?>
        </td>
      </tr>
      <tr>
        <th width="200"><strong><?php _e('To', 'wc_crm'); ?></strong></th>
        <td>
          <?php
            $recipients = $the_activity->get_recipients();
            if($recipients){
              $users = array();
              foreach ($recipients as $customer) {
                $users[] = '<a href="admin.php?page='.WC_CRM_TOKEN.'&screen=email&c_id='.$customer->c_id.'">'.$customer->email.'</a> (<a href="admin.php?page='.WC_CRM_TOKEN.'&c_id='.$customer->c_id.'">'.$customer->first_name.' '.$customer->last_name.'</a>)';
              }
              echo implode(', ', $users);
            }
           ?>
        </td>
      </tr>
      <tr>
        <th width="200"><strong><?php _e('Subject', 'wc_crm'); ?></strong></th>
        <td><?php echo stripslashes($the_activity->subject); ?></td>
      </tr>
      <tr>
        <th width="200"><strong><?php _e('Message', 'wc_crm'); ?></strong></th>
        <td><?php echo stripslashes($the_activity->message); ?></td>
      </tr>
    </table>

  </div>
</div>