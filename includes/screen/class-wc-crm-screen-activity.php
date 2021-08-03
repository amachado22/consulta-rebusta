<?php
/**
 * Class for E-mail handling.
 *
  * @author     Adailton Machado
 * @package  WC_CRM
 * @since    1.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WC_CRM_Screen_Activity
{
    //Email sorcodes vars. MUST compare with shortcodes-button.js
    public static $email_vars = array(
        'customer_first_name' => 'first_name',
        'customer_last_name' => 'last_name',
        'customer_email' => 'email',
        'customer_status' => 'status',
        'customer_account_name' => 'account_name',
        'customer_title' => 'title',
        'customer_department' => 'department',
        'customer_mobile' => 'mobile',
        'customer_fax' => 'fax',
        'customer_website' => 'site',
        'customer_lead_source' => 'lead_source',
        'customer_lead_status' => 'lead_status',
        'customer_industry' => 'industry',
        'customer_agent' => 'agent', //todo
        'customer_date_of_birth' => 'date_of_birth',
        'customer_assistant' => 'assistant',
        'customer_skype' => 'skype',
        'customer_twitter' => 'twitter',
        //Billing
        'customer_billing_first_name' => 'billing_first_name',
        'customer_billing_last_name' => 'billing_last_name',
        'customer_billing_company' => 'billing_company',
        'customer_billing_address_1' => 'billing_address_1',
        'customer_billing_address_2' => 'billing_address_2',
        'customer_billing_city' => 'billing_city',
        'customer_billing_postcode' => 'billing_postcode',
        'customer_billing_country' => 'billing_country',
        'customer_billing_state' => 'billing_state',
        'customer_billing_email' => 'billing_email',
        'customer_billing_cpf' => 'billing_cpf',
        'customer_billing_cnpj' => 'billing_cnpj',
        //Shipping
        'customer_shipping_first_name' => 'shipping_first_name',
        'customer_shipping_last_name' => 'shipping_last_name',
        'customer_shipping_company' => 'shipping_company',
        'customer_shipping_address_1' => 'shipping_address_1',
        'customer_shipping_address_2' => 'shipping_address_2',
        'customer_shipping_city' => 'shipping_city',
        'customer_shipping_postcode' => 'shipping_postcode',
        'customer_shipping_country' => 'shipping_country',
        'customer_shipping_state' => 'shipping_state',
    );

    public static function output()
    {
        add_action('wc_crm_restrict_list_logs', 'WC_CRM_Screen_Activity_Filters::restrict_list_logs');
        ?>
        <div class="wrap wc-crm-page-logs-table" id="wc-crm-page">
            <h2><?php _e('Emails', 'wc_crm'); ?></h2>
            <?php wc_crm_print_notices(); ?>
            <form method="post">
                <input type="hidden" name="page" value="<?php echo WC_CRM_TOKEN; ?>">
                <?php
                $customers_table = WC_CRM()->tables['activity'];
                $customers_table->views();
                $customers_table->prepare_items();
                $customers_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    public static function display_activity_data()
    {
        $the_activity = new WC_CRM_Activity($_REQUEST['log_id']);
        $date = date("d F Y", strtotime($the_activity->created));
        $time = date("H:i:s", strtotime($the_activity->created));
        ?>
        <div class="wrap wc-crm-page-logs-view" id="wc-crm-page">
            <h2><?php _e('Email Details', 'wc_crm'); ?></h2>
            <?php wc_crm_print_notices(); ?>
            <form method="post">
                <input type="hidden" name="page" value="<?php echo WC_CRM_TOKEN; ?>">
                <?php
                include_once 'views/html-activity-email.php';
                ?>
            </form>
        </div>
        <?php
    }


    /**
     * Displays form with e-mail editor.
     */
    public static function display_email_form()
    {

        $ids = array();
        $recipients = array();


        if (isset($_REQUEST['c_id'])) {
            if (!is_array($_REQUEST['c_id']))
                $ids[] = $_REQUEST['c_id'];
            else
                $ids = $_REQUEST['c_id'];
        }
        if (!empty($ids)) {
            $ids = implode(', ', $ids);
            global $wpdb;
            $result = $wpdb->get_results("SELECT email FROM {$wpdb->prefix}wc_crm_customer_list WHERE c_id IN({$ids})");
            if ($result) {
                foreach ($result as $customer) {
                    $recipients[] = $customer->email;
                }
            }
        }
        $mailer = WC()->mailer();
        include_once 'views/html-email-form.php';
    }

    /**
     * Processes the form data.
     */
    public static function process_email_form()
    {
        global $wpdb;

        wc_crm_clear_notices();
        $recipients = explode(',', $_POST['recipients']);

        $text = wpautop($_POST['emaileditor']);
        $subject = $_POST['subject'];
        if (!empty($_POST['from_email']) && filter_var($_POST['from_email'], FILTER_VALIDATE_EMAIL)) {
            add_filter('wp_mail_from', __CLASS__ . '::change_from_email', 9999);
        }
        if (!empty($_POST['from_name'])) {
            add_filter('wp_mail_from_name', __CLASS__ . '::change_from_name', 9999);
        }
        $mailer = WC()->mailer();
        ob_start();
        wc_crm_custom_woocommerce_get_template('emails/customer-send-email.php', array(
            'email_heading' => $subject,
            'email_message' => $text
        ));
        $message = ob_get_clean();
        $order_ID = '';
        if (isset($_GET['order_id']) && $_GET['order_id'] != '') {
            $order_ID = $_GET['order_id'];
        }
        //save log
        $emails_ = $_POST['recipients'];
        $type = "email";
        $table_name = $wpdb->prefix . "wc_crm_log";
        $created = current_time('mysql');
        $created_gmt = get_gmt_from_date($created);

        $insert = $wpdb->prepare("(%s, %s, %s, %s, %s, %d)", $created, $created_gmt, $subject, $text, $type, get_current_user_id());

        $wpdb->query("INSERT INTO $table_name (created, created_gmt, subject, message, activity_type, user_id) VALUES " . $insert);
        $log_id = $wpdb->insert_id;

        foreach ($recipients as $r) {
            $customer_message = self::replace_email_vars($message, $r);
            $mailer->send($r, stripslashes($subject), stripslashes($customer_message));
            $result = $wpdb->get_results("SELECT c_id, user_id FROM {$wpdb->prefix}wc_crm_customer_list WHERE email = '{$r}' LIMIT 1");
            if ($result) {
                $customer = $result[0];
                if ($customer->user_id > 0) {
                    add_user_meta($customer->user_id, 'wc_crm_log_id', $log_id);
                } else {
                    wc_crm_add_cmeta($customer->c_id, 'wc_crm_log_id', $log_id);
                }
            }
        }
        wc_crm_add_notice(__("Email sent.", 'wc_crm'), 'success');

    }

    public static function change_from_email($email)
    {
        return $_POST['from_email'];
    }

    public static function change_from_name($name)
    {
        return $_POST['from_name'];
    }


    public static function get_activity()
    {
        global $wpdb;
        $filter = '';

        if (!empty($_REQUEST['activity_types'])) {
            if ($filter == '') $filter .= 'WHERE ';
            else  $filter .= ' AND ';
            $filter .= 'activity_type = "' . $_REQUEST['activity_types'] . '"';
        }
        if (isset($_REQUEST['log_status']) && $_REQUEST['log_status'] == 'trash') {
            if ($filter == '') $filter .= 'WHERE ';
            else  $filter .= ' AND ';
            $filter .= 'log_status = \'trash\' ';
        } else {
            if ($filter == '') $filter .= 'WHERE ';
            else  $filter .= ' AND ';
            $filter .= 'log_status <> \'trash\' ';
        }
        if (!empty($_REQUEST['log_users'])) {
            if ($filter == '') $filter .= 'WHERE ';
            else  $filter .= ' AND ';
            $filter .= 'user_id = ' . $_REQUEST['log_users'];
        }
        $filter_m = '';
        if (!empty($_REQUEST['created_date'])) {
            $month = substr($_REQUEST['created_date'], -2);
            if ($month{0} == 0) $month = substr($month, -1);
            $year = substr($_REQUEST['created_date'], 0, 4);
            if ($filter == '') $filter_m .= 'WHERE ';
            else  $filter_m .= ' AND ';
            $filter_m .= 'YEAR( created ) = ' . $year . ' AND MONTH( created ) = ' . $month;
        }
        $orderby = (!empty($_GET['orderby'])) ? 'ORDER BY ' . $_GET['orderby'] : 'ORDER BY created';
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'DESC';

        $table_name = $wpdb->prefix . "wc_crm_log";
        $db_data = $wpdb->get_results("SELECT * FROM $table_name $filter $filter_m $orderby $order");
        $data = array();

        foreach ($db_data as $value) {
            $data[] = get_object_vars($value);
        }

        $logs_data = $data;


        return $data;
    }

    public static function replace_email_vars($message, $customer_email)
    {
        $cust = wc_crm_get_customer($customer_email, 'email');
        $customer = new WC_CRM_Customer($cust->c_id);
        foreach (self::$email_vars as $search => $replace) {
            $message = str_replace('{' . $search . '}', $customer->$replace, $message);
        }
        return $message;
    }

}
