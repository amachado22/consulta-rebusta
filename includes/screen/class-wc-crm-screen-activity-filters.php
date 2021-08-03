<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_CRM_Screen_Activity_Filters' ) ) :

/**
 * WC_CRM_Screen_Activity_Filters Class
 */
class WC_CRM_Screen_Activity_Filters {

	public static function restrict_list_logs()
	{
		$filters = array(
        'created_date',
        'log_username'
      );
      ?>
      <div class="alignleft actions">
      <?php
        foreach ($filters as $key => $value) {
          $method_name = $value.'_filter';
          if(method_exists(__CLASS__, $method_name)){
            self::$method_name();
          }
        }
        do_action( 'wc_crm_add_filters_log');
      ?>
      <input type="submit" id="post-query-submit" class="button action" value="Filter"/>
      <?php
      if(isset($_GET['log_status']) && $_GET['log_status'] == 'trash'){ ?>
        <input type="submit" value="<?php echo _e('Empty Trash', 'wc_crm'); ?>" class="button apply" id="delete_all" name="delete_all">
        <?php
      }
      ?>
    </div>
  <?php
	}

  public static function created_date_filter() {
      global $wp_locale, $wpdb;

      $table_name = $wpdb->prefix . "wc_crm_log";
      $months = $wpdb->get_results("
          SELECT DISTINCT YEAR( created ) AS year, MONTH( created ) AS month
          FROM $table_name
          ORDER BY created DESC
        " );
      $month_count = count( $months );

      if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
        return;

      $m = isset( $_GET['created_date'] ) ? (int) $_GET['created_date'] : 0;
      $m = isset( $_POST['created_date'] ) ? (int) $_POST['created_date'] : $m;
        ?>
        <select name='created_date' id="created_date">
          <option<?php selected( $m, 0 ); ?> value='0'><?php _e( 'Show all dates' ); ?></option>
          <?php
            foreach ( $months as $arc_row ) {
              if ( 0 == $arc_row->year )
                continue;

              $month = zeroise( $arc_row->month, 2 );
              $year = $arc_row->year;

              printf( "<option %s value='%s'>%s</option>\n",
                selected( $m, $year . $month, false ),
                esc_attr( $arc_row->year . $month ),
                /* translators: 1: month name, 2: 4-digit year */
                sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
              );
            }
        ?>
        </select>
      <?php
    }
    public static  function log_username_filter() {
      global $wpdb;
      $table_name = $wpdb->prefix . "wc_crm_log";
      $log_users = $wpdb->get_results("SELECT user_id
          FROM $table_name
          GROUP BY user_id
        " );
      ?>
      <select name='log_users' id='dropdown_log_users'>
        <option value=""><?php _e( 'Show all authors', 'woocommerce' ); ?></option>
        <?php
          foreach ( $log_users as $user ) {
            $userdata = get_userdata( $user->user_id );
            echo '<option value="' . absint( $user->user_id ) . '"';

            if ( isset( $_REQUEST['log_users'] ) ) {
              selected( $user->user_id, $_REQUEST['log_users'] );
            }
            echo '>' . $userdata->first_name.' '.$userdata->last_name  . '</option>';
          }
        ?>
      </select>
      <?php
    }
}

endif;