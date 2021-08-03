<?php
/** Display verbose errors */
define('IMPORT_DEBUG', false);

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if (!class_exists('WP_Importer')) {
    $class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
    if (file_exists($class_wp_importer))
        require $class_wp_importer;
}


/**
 * WordPress Importer class for managing the import process nof a WXR file
 *
 * @package WordPress
 * @subpackage Importer
 */
if (class_exists('WP_Importer')) {
    class WC_CRM_Importer extends WP_Importer
    {
        var $max_wxr_version = 1.2; // max. supported WXR version

        var $id; // WXR attachment ID

        // information to import from WXR file
        var $version;
        var $import_data = false;
        var $key_email = null;
        var $key_fname = null;
        var $key_lname = null;
        var $key_nice = '';
        var $key_role = '';
        var $key_status = '';
        var $key_cpf = null;
        var $key_cnpj = null;

        var $row = 0;
        var $not_import = array();
        var $groups_added = array();
        var $statuses_added = array();

        function __construct()
        { /* nothing */
        }

        /**
         * Registered callback function for the WordPress Importer
         *
         * Manages the three separate stages of the WXR import process
         */
        function dispatch()
        {

            $step = empty($_GET['step']) ? 0 : (int)$_GET['step'];

            switch ($step) {
                case 0:
                    $this->greet();
                    break;
                case 1:
                    if ($this->handle_upload())
                        $this->import_options();
                    break;
                case 2:
                    $this->id = (int)$_POST['import_id'];
                    $file = get_attached_file($this->id);
                    set_time_limit(0);
                    $this->import($file);
                    break;
            }
        }

        /**
         * The main controller for the actual import stage.
         *
         * @param string $file Path to the WXR file for importing
         */
        function import($file)
        {
            add_filter('import_post_meta_key', array($this, 'is_valid_meta_key'));
            add_filter('http_request_timeout', array(&$this, 'bump_request_timeout'));

            $this->import_start($file);
            $this->import_end();
        }

        /**
         * Parses the WXR file and prepares us for the task of processing parsed data
         *
         * @param string $file Path to the WXR file for importing
         */
        function import_start($file)
        {
            global $wpdb;
            if (!is_file($file)) {
                echo '<p class="wc-crm-message error"><strong>' . __('Sorry, there has been an error.', 'wc_crm') . '</strong><br />';
                echo __('The file does not exist, please try again.', 'wc_crm') . '</p>';
                die();
            }

            if (in_array('user_email', $_POST['import_options']))
                $this->key_email = array_search('user_email', $_POST['import_options']);

            if ($this->key_email === null && in_array('billing_email', $_POST['import_options']))
                $this->key_email = array_search('billing_email', $_POST['import_options']);

            if ($this->key_email === null) {
                echo '<p class="wc-crm-message error"><strong>' . __('Sorry, there has been an error.', 'wc_crm') . '</strong><br />';
                echo __('Please select user email and please try again.', 'wc_crm') . '</p>';
                wp_import_cleanup($this->id);
                wp_cache_flush();
                die();
            }

            $import_data = $this->parse($file);

            if (is_wp_error($import_data)) {
                echo '<p class="wc-crm-message error"><strong>' . __('Sorry, there has been an error.', 'wc_crm') . '</strong><br />';
                echo esc_html($import_data->get_error_message()) . '</p>';
                wp_import_cleanup($this->id);
                wp_cache_flush();
                die();
            }

            if (in_array('first_name', $_POST['import_options']))
                $this->key_fname = array_search('first_name', $_POST['import_options']);


            if ($this->key_fname === null && in_array('billing_first_name', $_POST['import_options']))
                $this->key_fname = array_search('billing_first_name', $_POST['import_options']);

            if (in_array('last_name', $_POST['import_options']))
                $this->key_lname = array_search('last_name', $_POST['import_options']);

            if ($this->key_lname === null && in_array('billing_last_name', $_POST['import_options']))
                $this->key_lname = array_search('billing_last_name', $_POST['import_options']);

            if (in_array('user_nicename', $_POST['import_options']))
                $this->key_nice = array_search('user_nicename', $_POST['import_options']);

            if (in_array('user_role', $_POST['import_options']))
                $this->key_role = array_search('user_role', $_POST['import_options']);

            if (in_array('customer_status', $_POST['import_options']))
                $this->key_status = array_search('customer_status', $_POST['import_options']);

            $skiped = false;
            while (($data = fgetcsv($import_data, 1000, ",")) !== FALSE) {

                if (isset($_POST['skip_first']) && $_POST['skip_first'] == 'yes' && !$skiped) {
                    $skiped = true;
                    continue;
                }

                $user_email = trim($data[$this->key_email]);

                if (empty($user_email) || email_exists($user_email)) {
                    $this->not_import[] = $data;
                    continue;
                }

                $nickname = '';
                if (empty($this->key_nice)) {

                    if (isset($data[$this->key_fname]))
                        $nickname .= sanitize_title($data[$this->key_fname]);
                    if (isset($data[$this->key_lname]))
                        $nickname .= '_' . sanitize_title($data[$this->key_lname]);

                } else {
                    $nickname .= sanitize_title($data[$this->key_nice]);
                }

                $user_login = '';
                if (in_array('user_login', $_POST['import_options'])) {
                    $key = array_search('user_login', $_POST['import_options']);
                    $user_login = $data[$key];
                } else {
                    $user_login = $this->get_user_login($user_email, $nickname);
                }
                //$password = wp_generate_password();
                add_filter('pre_option_woocommerce_registration_generate_password', 'wcrm_enable_generate_password');
                $user_id = wc_create_new_customer($user_email, $user_login);
                remove_filter('pre_option_woocommerce_registration_generate_password', 'wcrm_enable_generate_password');

                if (!empty($user_id) && !is_wp_error($user_id)) {

                    if (empty($this->key_role) && isset($_POST['customer_role']))
                        wp_update_user(array('ID' => $user_id, 'role' => $_POST['customer_role']));

                    if (empty($this->key_status) && isset($_POST['customer_status'])) {
                        $status = $_POST['customer_status'];
                        wc_crm_change_customer_status($status, array($user_id));
                    }

                    foreach ($_POST['import_options'] as $f_key => $meta_key) {
                        if (empty($meta_key))
                            continue;

                        if ($meta_key == 'user_login' || $meta_key == 'user_email')
                            continue;

                        if ($meta_key == 'url') {
                            wp_update_user(array('ID' => $user_id, 'user_url' => $data[$f_key]));
                            continue;
                        }
                        if ($meta_key == 'display_name') {
                            wp_update_user(array('ID' => $user_id, 'display_name' => $data[$f_key]));
                            continue;
                        }
                        if ($meta_key == 'wcrm_custom_meta') {
                            $custom_meta_key = $_POST['import_options_custom_meta'][$f_key];
                            update_user_meta($user_id, $custom_meta_key, $data[$f_key]);
                            continue;
                        }
                        if ($meta_key == 'user_nicename') {
                            wp_update_user(array('ID' => $user_id, 'user_nicename' => $data[$f_key]));
                            continue;
                        }
                        if ($meta_key == 'user_role') {
                            wp_update_user(array('ID' => $user_id, 'role' => $data[$f_key]));
                            continue;
                        }
                        if ($meta_key == 'customer_status') {
                            $status = $this->check_customer_status($data[$f_key]);
                            if (!$status)
                                $status = $_POST['customer_status'];

                            wc_crm_change_customer_status($status, array($user_id));

                            continue;
                        }
                        if ($meta_key == 'industry') {
                            $industries = wc_crm_get_industries();
                            if (!in_array($data[$f_key], $industries))
                                continue;
                        }
                        if ($meta_key == 'user_group') {
                            //global $wpdb
                            $groups = $data[$f_key];
                            $groups = explode(',', $groups);
                            if (!empty($groups)) {
                                $group_ids = array();
                                foreach ($groups as $group_name) {
                                    $group_slug = wc_sanitize_taxonomy_name(stripslashes($group_name));
                                    $group_exists = wc_crm_group_exists($group_slug);
                                    if (!$group_exists) {
                                        $group = array(
                                            'group_name' => $group_name,
                                            'group_slug' => $group_slug,
                                            'group_type' => 'static',
                                        );
                                        $wpdb->insert($wpdb->prefix . 'wc_crm_groups', $group);
                                        $group_ids[] = $wpdb->insert_id;
                                        $this->groups_added[] = $group_name;
                                        do_action('wc_crm_group_added', $wpdb->insert_id, $group);
                                    } else {
                                        $group_ids[] = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->prefix}wc_crm_groups WHERE group_slug = %s LIMIT 1", $group_slug));
                                    }
                                }
                                wc_crm_update_user_groups($group_ids, $user_email);
                            }
                            continue;
                        }

                        update_user_meta($user_id, $meta_key, $data[$f_key]);
                    }

                    if (!in_array('billing_first_name', $_POST['import_options']) && $this->key_fname !== null) {
                        update_user_meta($user_id, 'billing_first_name', $data[$this->key_fname]);
                    }
                    if (!in_array('billing_last_name', $_POST['import_options']) && $this->key_lname !== null) {
                        update_user_meta($user_id, 'billing_last_name', $data[$this->key_lname]);
                    }
                    if (!in_array('first_name', $_POST['import_options']) && $this->key_fname !== null) {
                        update_user_meta($user_id, 'first_name', $data[$this->key_fname]);
                    }
                    if (!in_array('last_name', $_POST['import_options']) && $this->key_lname !== null) {
                        update_user_meta($user_id, 'last_name', $data[$this->key_lname]);
                    }
                    if (!in_array('user_email', $_POST['import_options']) && $this->key_email !== null) {
                        update_user_meta($user_id, 'user_email', $data[$this->key_email]);
                    }
                    if (!in_array('billing_email', $_POST['import_options']) && $this->key_email !== null) {
                        update_user_meta($user_id, 'billing_email', $data[$this->key_email]);
                    }
                    if (!in_array('billing_cpf', $_POST['import_options']) && $this->key_cpf !== null) {
                        update_user_meta($user_id, 'billing_cpf', $data[$this->key_cpf]);
                    }
                    if (!in_array('billing_cnpj', $_POST['import_options']) && $this->key_cnpj !== null) {
                        update_user_meta($user_id, 'billing_cnpj', $data[$this->key_cnpj]);
                    }
                    $wpdb->update("{$wpdb->prefix}wc_crm_customer_list", array('first_name' => $data[$this->key_fname], 'last_name' => $data[$this->key_lname]), array('user_id' => $user_id));
                    $this->row++;
                }

            }

        }

        /**
         * Performs post-import cleanup of files and the cache
         */
        function import_end()
        {
            wp_import_cleanup($this->id);

            wp_cache_flush();

            wp_defer_term_counting(false);
            wp_defer_comment_counting(false);

            $this->render_steps(2);

            echo '<div class="wrap wc-crm-wrap">';

            echo '<section class="import-complete">';

            echo '<div class="import-complete-icon"></div>';

            echo '<p>' . __('Import complete!', 'wc_crm') . '</p>';

            echo '<p>' . sprintf(_n('%d customer imported.', '%d customers imported.', $this->row, 'wc_crm'), $this->row) . '</p>';

            if (!empty($this->groups_added)) {
                echo '<p>' . sprintf(_n('%d Group has been successfully added.', '%d Groups has been successfully added.', count($this->groups_added), 'wc_crm'), count($this->groups_added)) . ' (' . implode(', ', $this->groups_added) . ')</p>';
            }
            if (!empty($this->statuses_added)) {
                echo '<p>' . sprintf(_n('%d customers imported.', '%d customer imported.', count($this->statuses_added), 'wc_crm'), count($this->statuses_added)) . ' (' . implode(', ', $this->statuses_added) . ')</p>';
            }

            if (!empty($this->not_import)) {
                echo '<p>' . sprintf(_n('%d customer was not imported.', '%d customers was not imported.', count($this->not_import), 'wc_crm'), count($this->not_import)) . '</p>';
                echo '<code>';
                foreach ($this->not_import as $value) {
                    echo ( !empty($value[3]) ? $value[3] : 'email not found' ) . '<br>';
                }
                echo '</code>';
            }

            echo '</section>';

            echo '<footer><div class="submit"><a href="'.admin_url("admin.php?page=wc_crm").'" class="button button-primary">View Customers</a></div></footer>';

            echo '</div>';

            do_action('wcrm_import_end');
        }

        /**
         * Handles the WXR upload and initial parsing of the file to prepare for
         * displaying author import options
         *
         * @return bool False if error uploading or invalid file, true otherwise
         */
        function handle_upload()
        {
            $file = wp_import_handle_upload();

            if (isset($file['error'])) {
                echo '<p class="wc-crm-message error"><strong>' . __('Sorry, there has been an error.', 'wc_crm') . '</strong><br />';
                echo esc_html($file['error']) . '</p>';
                return false;
            } else if (!file_exists($file['file'])) {
                echo '<p class="wc-crm-message error"><strong>' . __('Sorry, there has been an error.', 'wc_crm') . '</strong><br />';
                printf(__('The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', 'wc_crm'), esc_html($file['file']));
                echo '</p>';
                return false;
            }

            $this->id = (int)$file['id'];
            $import_data = $this->parse($file['file']);
            if (is_wp_error($import_data)) {
                echo '<p class="wc-crm-message error"><strong>' . __('Sorry, there has been an error.', 'wc_crm') . '</strong><br />';
                echo esc_html($import_data->get_error_message()) . '</p>';
                return false;
            }
            $this->import_data = $import_data;

            return true;
        }

        /**
         * Retrieve authors from parsed WXR data
         *
         * Uses the provided author information from WXR 1.1 files
         * or extracts info from each post for WXR 1.0 files
         *
         * @param array $import_data Data returned by a WXR parser
         */
        function get_user_login($user_email, $nickname)
        {
            global $wpdb;

            $username_opt = get_option('woocommerce_crm_username_add_customer');

            $username = '';
            switch ($username_opt) {
                case 3:
                    $u = explode('@', $user_email);
                    $username = $u[0];
                    break;
                default:
                    $username = $nickname;
                    break;
            }

            if (empty($username)) {
                $u = explode('@', $user_email);
                $username = $u[0];
            }

            $username = sanitize_title($username);

            $username = _truncate_post_slug($username, 60);
            $check_sql = "SELECT user_login FROM {$wpdb->users} WHERE user_login = '%s' LIMIT 1";

            $user_name_check = $wpdb->get_var($wpdb->prepare($check_sql, $username));


            if ($user_name_check) {
                $suffix = 1;
                do {
                    $alt_user_name = _truncate_post_slug($username, 60 - (strlen($suffix) + 1)) . "-$suffix";
                    $user_name_check = $wpdb->get_var($wpdb->prepare($check_sql, $alt_user_name));
                    $suffix++;
                } while ($user_name_check);
                $username = $alt_user_name;
            }
            return $username;
        }

        public function get_import_meta_fields()
        {
            $customer_detail = new WC_CRM_Customer();
            $customer_detail->init_general_fields();
            $customer_detail->init_address_fields();
            $fields = $customer_detail->general_fields;
            $billing = $customer_detail->billing_fields;
            $shipping = $customer_detail->shipping_fields;
            $acf_fields = wc_crm_get_acf_fields();

            $general_fields = array(
                'wcrm_custom_meta' => __('Custom meta', 'wc_crm'),
                'user_role' => __('Role', 'wc_crm'),
                'user_login' => __('User Login', 'wc_crm'),
                'user_group' => __('Group', 'wc_crm'),
                'user_nicename' => __('Nicename', 'wc_crm'),
                'display_name' => __('Display name', 'wc_crm')
            );
            if ($fields) {
                foreach ($fields as $key => $value) {
                    if ($key == 'customer_brands') continue;
                    if ($key == 'customer_categories') continue;
                    $general_fields[$value['meta_key']] = $value['label'];
                }
            }
            $billing_fields = array();
            if ($billing) {
                foreach ($billing as $key => $value) {
                    $billing_fields['billing_' . $key] = 'Billing ' . $value['label'];
                }
            }
            $shipping_fields = array();
            if ($shipping) {
                foreach ($shipping as $key => $value) {
                    $shipping_fields['shipping_' . $key] = 'Shipping ' . $value['label'];
                }
            }

            $meta_fields = array(
                'general' => array(
                    'group' => __('General fields', 'wc_crm'),
                    'fields' => $general_fields
                )
            );
            if (!empty($billing_fields)) {
                $meta_fields['billing'] = array(
                    'group' => __('Billing fields', 'wc_crm'),
                    'fields' => $billing_fields
                );
            }
            if (!empty($shipping_fields)) {
                $meta_fields['shipping'] = array(
                    'group' => __('Shipping fields', 'wc_crm'),
                    'fields' => $shipping_fields
                );
            }
            if (!empty($acf_fields)) {
                $meta_fields['acf'] = array(
                    'group' => __('Advanced fields', 'wc_crm'),
                    'fields' => $acf_fields
                );
            }
            return apply_filters('wcrm_get_import_meta_fields', $meta_fields);
        }

        /**
         * Display pre-import options, author importing/mapping and option to
         * fetch attachments
         */

        function import_options()
        {
            $this->render_steps(1);
            ?>
            <div class="wrap wc-crm-wrap">
                <header>
                    <h2 class="title"> <?php _e('Map CSV fields to customers', 'wc_crm'); ?> </h2>
                    <p class="sub-title"><?php _e('Select fields from your CSV file to map against customer fields, or to ignore during import.', 'wc_crm'); ?></p>
                </header>
                <form action="<?php echo admin_url('admin.php?page=wc_crm_import&amp;step=2'); ?>" method="post">
                <input type="hidden" name="import_id" value="<?php echo $this->id; ?>"/>
                    <section>
                        <table class="form-table">
                            <tbody>
                            <tr class="form-field">
                                <th valign="top" scope="row">
                                    <label><?php _e('Default User Role ', 'wc_crm'); ?></label>
                                </th>
                                <td>
                                    <select name="customer_role">
                                        <?php
                                        $selected = 'customer';
                                        foreach (get_editable_roles() as $role_name => $role_info):
                                            echo '<option value="' . esc_attr($role_name) . '" ' . selected($role_name, $selected, false) . '>' . $role_info['name'] . '</option>';
                                        endforeach; ?>
                                    </select>
                                    <p class="description"><?php _e('Select what the default user role for the imported customers should be upon import.', 'wc_crm'); ?></p>
                                </td>
                            </tr>
                            <tr class="form-field">
                                <th valign="top" scope="row">
                                    <label><?php _e('Default Status ', 'wc_crm'); ?></label>
                                </th>
                                <td>
                                    <select name="customer_status">
                                        <?php
                                        $selected = 'Lead';
                                        $statuses = wc_crm_get_statuses_slug();
                                        foreach ($statuses as $key => $status) {
                                            echo '<option value="' . esc_attr($key) . '" ' . selected($key, $selected, false) . '>' . esc_html__($status, 'wc_crm') . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <p class="description"><?php _e('Select what the default customer status for the imported customers should be upon import.', 'wc_crm'); ?></p>
                                </td>
                            </tr>
                            <tr class="form-field">
                                <th valign="top" scope="row">
                                    <label><?php _e('Skip First Row', 'wc_crm'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" name="skip_first" value="yes" checked="checked">
                                    <p class="description"><?php _e('Check this box to skip the addition of the first row of the CSV file. You can see the values of the first row below on the left.', 'wc_crm'); ?></p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </section>
                    <?php

                    if ($this->import_data) {
                        //$select = $this->options_select();
                        ?>
                        <table class="import_fields wp-list-table widefat fixed striped posts">
                            <thead>
                            <th class="first_row"><?php _e('Column Name', 'wc_crm') ?></th>
                            <th class="mapped_fields"><?php _e('Field To Map', 'wc_crm') ?></th>
                            </thead>

                        <?php

                        $count = 0;
                        $heads = [];
                        $samples = [];
                        while ( ($data = fgetcsv($this->import_data, 1000, ",")) !== FALSE) {

                            if( $count == 0 ) $heads = $data;

                            if( $count == 1 ) $samples = $data;

                            $count++;

                            if( $count > 1) break;
                        }

                        foreach ($heads as $key => $value) {
                            echo '<tr>';
                            echo '<td>' . $value . $this->sample_text($samples[$key]) . '</td>';
                            echo '<td>' . $this->options_select($value) . '</td>';
                            echo '</tr>';
                        }

                        echo '</table>';
                    }
                    ?>
                    <footer>
                        <p class="submit">
                            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Import', 'wc_crm'); ?>"/>
                        </p>
                    </footer>
            </form>
                <script>
                    jQuery(function ($) {
                        if ($('.wcrm_import_meta').length > 0) {
                            $('.wcrm_import_meta').change(function () {
                                var meta_key = $(this).val();
                                if (meta_key == 'wcrm_custom_meta') {
                                    $(this).next('.wcrm_import_custom_meta').show();
                                } else {
                                    $(this).next('.wcrm_import_custom_meta').hide();
                                }
                            });
                        }
                    });
                </script>
            </div>
            <?php
        }

        /**
         * Display import options for an individual author. That is, either create
         * a new user based on import info or map to an existing user
         *
         * @param string $selected
         * @return string
         */
        function options_select($selected)
        {
            $fields = $this->get_import_meta_fields();
            $select = '<select name="import_options[]" class="wcrm_import_meta">';
            $select .= '<option value="default">' . __('Select fields to map', 'wc_crm') . '</option>';
            foreach ($fields as $group) {
                if (isset($group['group'])) {
                    $select .= '<optgroup label="' . $group['group'] . '">';
                }
                foreach ($group['fields'] as $key => $value) {
                    $is_selected = selected($key, $selected, false);
                    $select .= '<option value="' . $key . '" '. $is_selected .'>' . $value . '</option>';
                }
                if (isset($group['group'])) {
                    $select .= '</optgroup>';
                }
            }
            $select .= '</select>';
            $select .= '<div class="wcrm_import_custom_meta" >';
            $select .= '<input type="text" name="import_options_custom_meta[]" >';
            $select .= '<p class="description">' . __('Enter user meta key.', 'wc_crm') . '</p>';
            $select .= '</div>';
            return $select;
        }


        /**
         * Parse a WXR file
         *
         * @param string $file Path to WXR file for parsing
         * @return array Information gathered from the WXR file
         */
        function parse($file)
        {
            if (($handle = fopen($file, "r")) === FALSE)
                return new WP_Error('Parse_error', __('There was an error when reading this file', 'wc_crm'));
            return $handle;
        }

        /**
         * Display introductory text and file upload form
         */
        function greet()
        {
            $this->render_steps(0);
            ?>
            <div class="wrap wc-crm-wrap" id="wc-crm-page">
                <header>
                    <h2 class="title"><?php _e('Import customers from a CSV file', 'wc_crm'); ?></h2>
                    <p class="sub-title"><?php _e('This tool allows you to import (or merge) customer data to your store from a CSV file.', 'wc_crm'); ?></p>
                </header>
                <form enctype="multipart/form-data"
                      id="import-upload-form" method="post" class="wp-upload-form"
                      action="<?= wp_nonce_url(admin_url('admin.php?page=wc_crm_import&amp;step=1&amp;')) ?>"
                >
                    <section>
                        <table class="form-table">
                            <tbody>
                            <tr class="form-field">
                                <th scope="row">
                                    <label for="upload">Choose a CSV file from your computer:</label>
                                </th>
                                <td>
                                    <input type="file" id="upload" name="import" size="25">
                                    <input type="hidden" name="action" value="save">
                                    <input type="hidden" name="max_file_size" value="2097152">
                                    <br/>
                                    <small>Maximum size: <?php echo $this->formatBytes(wp_max_upload_size()); ?></small>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </section>
                    <footer>
                        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="continue" disabled=""></p>
                    </footer>
                </form>
            </div>

            <?php
        }

        function check_customer_status($status = '')
        {

            if (empty($status))
                return false;

            $def_statuses = WC_CRM()->statuses;
            if (in_array($status, $def_statuses))
                return $status;

            global $wpdb;
            $table = $wpdb->prefix . "wc_crm_statuses";

            $check_sql = "SELECT status_slug FROM {$table} WHERE status_slug = '$status' || status_name = '$status'  LIMIT 1";

            $slug_check = $wpdb->get_var($check_sql);
            if ($slug_check)
                $status = $slug_check;
            else
                $status = $this->add_customer_status($status);

            return $status;
        }

        function add_customer_status($status)
        {
            global $wpdb;
            $table = $wpdb->prefix . "wc_crm_statuses";

            $status_name = $status;
            $status_slug = sanitize_title($status);

            $check_sql = "SELECT status_slug FROM {$table} WHERE status_slug = '%s' LIMIT 1";

            $slug_check = $wpdb->get_var($wpdb->prepare($check_sql, $status_slug));


            if ($slug_check) {
                $suffix = 2;
                do {
                    $alt_slug = _truncate_post_slug($status_slug, 200 - (strlen($suffix) + 1)) . "-$suffix";
                    $slug_check = $wpdb->get_var($wpdb->prepare($check_sql, $alt_slug));
                    $suffix++;
                } while ($slug_check);
                $status_slug = $alt_slug;
            }
            $data = array(
                'status_name' => $status_name,
                'status_slug' => $status_slug,
                'status_icon' => '57545',
                'status_colour' => '#8224e3',
            );
            $wpdb->insert($table, $data);
            $this->statuses_added[] = $status_name;
            return $status_slug;
        }

        /**
         * Decide if the given meta key maps to information we will want to import
         *
         * @param string $key The meta key to check
         * @return string|bool The key if we do want to import, false if not
         */
        function is_valid_meta_key($key)
        {
            // skip attachment metadata since we'll regenerate it from scratch
            // skip _edit_lock as not relevant for import
            if (in_array($key, array('_wp_attached_file', '_wp_attachment_metadata', '_edit_lock')))
                return false;
            return $key;
        }


        /**
         * Added to http_request_timeout filter to force timeout at 60 seconds during import
         * @return int 60
         */
        function bump_request_timeout($time)
        {
            return 60;
        }


        /**
         * Render steps
         * @param int $step
         */
        function render_steps($step = 0){
            ?>
            <h1 class="page-heading">Import Customers</h1>
            <div class="wc-crm-progress-form-wrapper">
                <ol class="wc-crm-progress-steps">
                    <li class="<?php echo $this->get_css_class($step, 0); ?>">Upload CSV file</li>
                    <li class="<?php echo $this->get_css_class($step, 1); ?>">Column mapping</li>
                    <li class="<?php echo $this->get_css_class($step, 2); ?>">Complete!</li>
                </ol>
            </div>

            <?php
        }


        /**
         * Get css class based on current step
         * @param $step
         * @param $index
         * @return string
         */
        function get_css_class($step, $index){

            if($step == $index)
                return 'active';

            if($step > $index)
                return 'done';

            return '';

        }


        /**
         * Format the bytes to Mega bytes
         * @param $bytes
         * @param int $precision
         * @return string
         */
        function formatBytes($bytes, $precision = 2) {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');

            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);

            $bytes /= pow(1024, $pow);

            return round($bytes, $precision) . ' ' . $units[$pow];
        }


        function sample_text($value)
        {
            if(empty($value))
                return '';

            return '<span class="description"> Sample: ' . $value . '</span>';
        }

    }

} // class_exists( 'WP_Importer' )
