<?php
/**
 * Class for E-mail handling.
 *
  * @author     Adailton Machado
 * @package  WC_CRM
 * @since    1.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WC_CRM_Screen_Task
{
    private static $saved_meta_boxes = false;

    public static function output_info($post)
    {
        global $post_type;
        wp_nonce_field('wc_crm_save_data', 'wc_crm_meta_nonce');
        $fields_info = self::get_info_fields($post->ID);
        include_once 'views/html-task-info.php';
    }

    public static function output_actions($post)
    {
        global $action;

        $post_type = $post->post_type;
        $post_type_object = get_post_type_object($post_type);
        $can_publish = current_user_can($post_type_object->cap->publish_posts);
        $task = new WC_CRM_Task($post->ID);

        include_once 'views/html-task-actions.php';
        ?>
        <?php
    }

    public static function output_description($post)
    {
        global $is_IE, $post_type;
        $_wp_editor_expand = $_content_editor_dfw = false;
        if (!wp_is_mobile() &&
            !($is_IE && preg_match('/MSIE [5678]/', $_SERVER['HTTP_USER_AGENT'])) &&
            apply_filters('wp_editor_expand', true, $post_type)
        ) {

            wp_enqueue_script('editor-expand');
            $_content_editor_dfw = true;
            $_wp_editor_expand = (get_user_setting('editor_expand', 'on') === 'on');
        }
        include_once 'views/html-task-description.php';
    }

    public static function get_info_fields($id)
    {
        $task = new WC_CRM_Task($id);

        $customer = (int)$task->customer_id;
        $edit = !(!$task->post->post_date_gmt || '0000-00-00 00:00:00' == $task->post->post_date_gmt);
        if (!$edit && isset($_GET['c_id']) & !empty($_GET['c_id'])) {
            $the_customer = new WC_CRM_Customer($_GET['c_id']);
            $customer = $the_customer->id;
            $task->customer_id = $customer;
            $task->account = $the_customer->get_account();
        }

        $user_string = '';

        if ($customer) {
            $the_customer = new WC_CRM_Customer($customer);
            $name = $the_customer->get_name();
            $user_string = esc_html($name) . ' (#' . absint($customer) . ' - ' . sanitize_email($the_customer->email) . ')';
        } else {
            $customer = '';
        }

        return apply_filters('wcrm_tasks_info_fields', array(
            'post_title' => array(
                'label' => __('Subject:', 'wc_crm'),
                'value' => $task->subject,
                'type' => 'text',
                'custom_attributes' => array(
                    'size' => '30',
                    'autocomplete' => 'off'
                ),
            ),
            'task_owner_name' => array(
                'label' => __('Task Owner:', 'wc_crm'),
                'value' => $task->get_owner_name(),
                'type' => 'text',
                'custom_attributes' => array(
                    'autocomplete' => 'off',
                    'disabled' => 'disabled',
                ),
            ),
            'dropdown_customers' => array(
                'name' => 'customer_id',
                'label' => __('Customer:', 'wc_crm'),
                'value' => $customer,
                'class' => 'wc-crm-customer-search',
                'type' => 'select',
                'options' => array(
                    $customer => htmlspecialchars($user_string)
                ),
                'custom_attributes' => array(
                    'data-allow_clear' => 'true',
                    'data-action' => 'wc_crm_json_search_customers',
                    'data-placeholder' => __('Select a Customer&hellip;', 'wc_crm'),
                ),
            ),
            'account_name' => array(
                'name' => 'account',
                'label' => __('Account:', 'wc_crm'),
                'value' => $task->account,
                'class' => 'wc-enhanced-select',
                'type' => 'select',
                'custom_attributes' => array(
                    'data-allow_clear' => 'true',
                    'data-placeholder' => __('Select an account&hellip;', 'wc_crm'),
                ),
                'options' => array('' => '') + wc_crm_get_accounts(true)
            ),

            'notification_email' => array(
                'label' => __('Send Notification Email: ', 'wc_crm'),
                'value' => $task->notification_email,
                'type' => 'checkbox',
            ),
            'repeat' => array(
                'label' => __('Repeat: ', 'wc_crm'),
                'value' => $task->repeat,
                'type' => 'checkbox',
            ),
            'srart_date' => array(
                'label' => __('Start Date:', 'wc_crm'),
                'wrapper_class' => 'show_repeat_options',
                'class' => 'wc_crm_date',
                'style' => 'width: 150px;',
                'value' => $task->srart_date,
                'custom_attributes' => array(
                    'pattern' => "[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])",
                    'maxlength' => 10,
                )
            ),
            'end_date' => array(
                'label' => __('End Date:', 'wc_crm'),
                'wrapper_class' => 'show_repeat_options',
                'class' => 'wc_crm_date',
                'style' => 'width: 150px;',
                'value' => $task->end_date,
                'custom_attributes' => array(
                    'pattern' => "[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])",
                    'maxlength' => 10,
                )
            ),
            'repeat_type' => array(
                'label' => __('Repeat Type:', 'wc_crm'),
                'value' => $task->repeat_type,
                'wrapper_class' => 'show_repeat_options',
                'class' => 'wc-enhanced-select',
                'type' => 'select',
                'options' => array(
                    'none' => __('None', 'wc_crm'),
                    'daily' => __('Daily', 'wc_crm'),
                    'weekly' => __('Weekly', 'wc_crm'),
                    'monthly' => __('Monthly', 'wc_crm'),
                    'yearly' => __('Yearly', 'wc_crm')
                )
            ),
        ));
    }

    public static function save_meta_boxes($post_id, $post)
    {
        // $post_id and $post are required
        if (empty($post_id) || empty($post) || self::$saved_meta_boxes) {
            return;
        }

        // Dont' save meta boxes for revisions or autosaves
        if (defined('DOING_AUTOSAVE') || is_int(wp_is_post_revision($post)) || is_int(wp_is_post_autosave($post))) {
            return;
        }

        // Check the nonce
        if (empty($_POST['wc_crm_meta_nonce']) || !wp_verify_nonce($_POST['wc_crm_meta_nonce'], 'wc_crm_save_data')) {
            return;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if (empty($_POST['post_ID']) || $_POST['post_ID'] != $post_id) {
            return;
        }

        // Check user has permission to edit
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        self::$saved_meta_boxes = true;

        // Check the post type
        if ($post->post_type == 'wc_crm_tasks') {


            $keys = wc_crm_get_task_populate_fields();
            foreach ($keys as $key) {

                switch ($key) {
                    case 'post_title':
                    case 'post_status':
                    case 'due_date_gmt':
                        continue;
                        break;
                    case 'due_date':
                        $due_date = '0000-00-00 00:00:00';
                        $due_date_gmt = '0000-00-00 00:00:00';

                        $aa = $_POST['due_date_aa'];
                        $mm = $_POST['due_date_mm'];
                        $jj = $_POST['due_date_jj'];
                        $hh = $_POST['due_date_hh'];
                        $mn = $_POST['due_date_mn'];
                        $ss = $_POST['due_date_ss'];
                        if (!empty($jj) && !empty($aa)) {
                            $aa = ($aa <= 0) ? date('Y') : $aa;
                            $mm = ($mm <= 0) ? date('n') : $mm;
                            $jj = ($jj > 31) ? 31 : $jj;
                            $jj = ($jj <= 0) ? date('j') : $jj;
                            $hh = ($hh > 23) ? $hh - 24 : $hh;
                            $mn = ($mn > 59) ? $mn - 60 : $mn;
                            $ss = ($ss > 59) ? $ss - 60 : $ss;
                            $due_date = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss);
                            $valid_date = wp_checkdate($mm, $jj, $aa, $due_date);
                            if (!$valid_date) {
                                return new WP_Error('invalid_date', __('Whoops, the provided date is invalid.'));
                            }
                            $due_date_gmt = get_gmt_from_date($due_date);
                        }
                        update_post_meta($post_id, '_due_date', $due_date);
                        update_post_meta($post_id, '_due_date_gmt', $due_date_gmt);
                        break;
                    default:
                        if (isset($_POST[$key])) {
                            update_post_meta($post_id, '_' . $key, $_POST[$key]);
                        } else {
                            delete_post_meta($post_id, '_' . $key);
                        }
                        break;
                }
            }
            $noMonths_key = 'repeat_monthly_noMonths1';
            $months_key = 'repeat_yearly_months';
            if (isset($_POST['repeat_monthly_type']) && (int)$_POST['repeat_monthly_type'] == 2) {
                $noMonths_key = 'repeat_monthly_noMonths2';
            }
            if (isset($_POST['repeat_yearly_type']) && (int)$_POST['repeat_yearly_type'] == 2) {
                $months_key = 'repeat_yearly_months2';
            }
            $repeat_options = array(
                'srart_date' => isset($_POST['srart_date']) ? $_POST['srart_date'] : '',
                'end_date' => isset($_POST['end_date']) ? $_POST['end_date'] : '',
                'type' => isset($_POST['repeat_type']) ? $_POST['repeat_type'] : 'none',
                'daily' => array(
                    'type' => isset($_POST['repeat_daily_type']) ? $_POST['repeat_daily_type'] : 1,
                    'days' => isset($_POST['repeat_daily_days']) ? $_POST['repeat_daily_days'] : '',
                ),
                'weekly' => array(
                    'weeks' => isset($_POST['repeat_weekly_weeks']) ? $_POST['repeat_weekly_weeks'] : '',
                    'weekdays' => isset($_POST['repeat_weekly_weekdays']) ? $_POST['repeat_weekly_weekdays'] : array(),
                ),
                'monthly' => array(
                    'type' => isset($_POST['repeat_monthly_type']) ? $_POST['repeat_monthly_type'] : 1,
                    'day' => isset($_POST['repeat_monthly_day']) ? $_POST['repeat_monthly_day'] : 1,
                    'noMonths' => isset($_POST[$noMonths_key]) ? $_POST[$noMonths_key] : '',
                    'weeksequence' => isset($_POST['repeat_monthly_weeksequence']) ? $_POST['repeat_monthly_weeksequence'] : 1,
                    'weekday' => isset($_POST['repeat_monthly_weekday']) ? $_POST['repeat_monthly_weekday'] : 1,
                ),
                'yearly' => array(
                    'type' => isset($_POST['repeat_yearly_type']) ? $_POST['repeat_yearly_type'] : 1,
                    'months' => isset($_POST[$months_key]) ? $_POST[$months_key] : 1,
                    'days' => isset($_POST['repeat_yearly_day']) ? $_POST['repeat_yearly_day'] : 1,
                    'weeksequence' => isset($_POST['repeat_yearly_weeksequence']) ? $_POST['repeat_yearly_weeksequence'] : 1,
                    'weekday' => isset($_POST['repeat_yearly_weekday']) ? $_POST['repeat_yearly_weekday'] : 1,
                )
            );

            update_post_meta($post_id, '_repeat_options', $repeat_options);

            // Load mailer
            if (isset($_POST['notification_email'])) {
                $mailer = WC()->mailer();
                do_action('wc_crm_send_task_notification', $post_id);
            }

            do_action('wc_crm_process_tasks_meta', $post_id, $post);
        }
    }


    /**
     * Print out HTML form date elements for editing post or comment publish date.
     *
     * @global WP_Locale $wp_locale
     *
     * @param int|bool $edit Accepts 1|true for editing the date, 0|false for adding the date.
     * @param int|bool $for_post Accepts 1|true for applying the date to a post, 0|false for a comment.
     * @param int $tab_index The tabindex attribute to add. Default 0.
     * @param int|bool $multi Optional. Whether the additional fields and buttons should be added.
     *                            Default 0|false.
     */
    public static function touch_time($edit = 1, $tab_index = 0, $multi = 0)
    {
        global $wp_locale;
        $post = get_post();
        $task = new WC_CRM_Task($post);

        $edit = !(!$post->post_date_gmt || '0000-00-00 00:00:00' == $post->post_date_gmt);
        $has_due_date = !(!$task->due_date_gmt || '0000-00-00 00:00:00' == $task->due_date_gmt);


        $tab_index_attribute = '';
        if ((int)$tab_index > 0)
            $tab_index_attribute = " tabindex=\"$tab_index\"";

        $post_date = $task->due_date;
        $time_adj = current_time('timestamp');
        #$time_adj = strtotime( '+2 hours', $time_adj );

        $jj = $has_due_date ? mysql2date('d', $post_date, false) : (!$edit ? gmdate('d', $time_adj) : '');
        $mm = $has_due_date ? mysql2date('m', $post_date, false) : (!$edit ? gmdate('m', $time_adj) : '01');
        $aa = $has_due_date ? mysql2date('Y', $post_date, false) : (!$edit ? gmdate('Y', $time_adj) : '');
        $hh = $has_due_date ? mysql2date('H', $post_date, false) : (!$edit ? gmdate('H', $time_adj) : '');
        $mn = $has_due_date ? mysql2date('i', $post_date, false) : (!$edit ? gmdate('i', $time_adj) : '');
        $ss = $has_due_date ? mysql2date('s', $post_date, false) : (!$edit ? gmdate('s', $time_adj) : '');


        $month = '<label><span class="screen-reader-text">' . __('Month') . '</span><select ' . ($multi ? '' : 'id="due_date_mm" ') . 'name="due_date_mm"' . $tab_index_attribute . ">\n";
        for ($i = 1; $i < 13; $i = $i + 1) {
            $monthnum = zeroise($i, 2);
            $monthtext = $wp_locale->get_month_abbrev($wp_locale->get_month($i));
            $month .= "\t\t\t" . '<option value="' . $monthnum . '" data-text="' . $monthtext . '" ' . selected($monthnum, $mm, false) . '>';
            /* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
            $month .= sprintf(__('%1$s-%2$s'), $monthnum, $monthtext) . "</option>\n";
        }
        $month .= '</select></label>';

        $day = '<label><span class="screen-reader-text">' . __('Day') . '</span><input type="text" ' . ($multi ? '' : 'id="due_date_jj" ') . 'name="due_date_jj" value="' . $jj . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
        $year = '<label><span class="screen-reader-text">' . __('Year') . '</span><input type="text" ' . ($multi ? '' : 'id="due_date_aa" ') . 'name="due_date_aa" value="' . $aa . '" size="4" maxlength="4"' . $tab_index_attribute . ' autocomplete="off" /></label>';
        $hour = '<label><span class="screen-reader-text">' . __('Hour') . '</span><input type="text" ' . ($multi ? '' : 'id="due_date_hh" ') . 'name="due_date_hh" value="' . $hh . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
        $minute = '<label><span class="screen-reader-text">' . __('Minute') . '</span><input type="text" ' . ($multi ? '' : 'id="due_date_mn" ') . 'name="due_date_mn" value="' . $mn . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';

        echo '<div class="due_date-wrap">';
        /* translators: 1: month, 2: day, 3: year, 4: hour, 5: minute */
        printf(__('%1$s %2$s, %3$s @ %4$s:%5$s'), $month, $day, $year, $hour, $minute);

        echo '</div><input type="hidden" id="due_date_ss" name="due_date_ss" value="' . $ss . '" />';

        if ($multi) return;

        echo "\n\n";
        $map = array(
            'mm' => $mm,
            'jj' => $jj,
            'aa' => $aa,
            'hh' => $hh,
            'mn' => $mn,
        );
        foreach ($map as $timeunit => $unit) {

            echo '<input type="hidden" id="hidden_due_date_' . $timeunit . '" name="hidden_due_date_' . $timeunit . '" value="' . $unit . '" />' . "\n";
        }
        ?>

        <p>
            <a href="#edit_due_date" class="save-due_date hide-if-no-js button"><?php _e('OK'); ?></a>
            <a href="#edit_due_date" class="cancel-due_date hide-if-no-js button-cancel"><?php _e('Cancel'); ?></a>
        </p>
        <?php
    }
}
