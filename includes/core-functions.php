<?php
/**
 * General global functions.
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */
use DrewM\MailChimp\MailChimpAPI;

/**
 * Gets the data about logs.
 */
function woocommerce_crm_get_logs_data()
{
    global $logs_data, $activity_types, $created_dates, $log_users;

    $activity_types = array();
    $created_dates = array();
    $log_users = array();
    foreach ($logs_data as $key => $value) {
        ############# TYPES #########################
        if (!in_array($value['activity_type'], array_keys($activity_types))) {
            $activity_types[$value['activity_type']] = 1;
        } else {
            $activity_types[$value['activity_type']]++;
        }
        ############ END OF TYPES ####################

        ############# TYPES #########################
        if (!in_array($value['created'], array_keys($created_dates))) {
            $created_dates[$value['created']] = 1;
        } else {
            $created_dates[$value['created']]++;
        }
        ############ END OF TYPES ####################

        ############# USERS #########################
        if (!in_array($value['user_id'], array_keys($log_users))) {
            $log_users[$value['user_id']] = 1;
        } else {
            $log_users[$value['user_id']]++;
        }
        ############ END OF USERS ####################
    }


}


/**
 * Obtains list of MailChimp registered users
 *
 * @return array
 */
function woocommerce_crm_get_members()
{
    $retval = get_transient('woocommerce_crm_mailchimp_members');
    $members = array();
    if (!$retval) {
        $mc_api = wc_crm_mailchimp_init(get_option('wc_crm_mailchimp_api_key'));

        if(!$mc_api) return $members;

        $list_id = get_option('wc_crm_mailchimp_list', '');
        $retval = $mc_api->get("lists/$list_id/members");

        if($mc_api->success()){
            set_transient('woocommerce_crm_mailchimp_members', $retval, 60 * 60 * 1);
        }
    }

    if (!empty($retval['members'])) {
        foreach ($retval['members'] as $item) {
            array_push($members, $item['email_address']);
        }
    }
    return $members;
}

/**
 * Determine if MailChimp integration is enabled and set up.
 *
 * @return bool
 */
function woocommerce_crm_mailchimp_enabled()
{
    return (get_option('wc_crm_mailchimp', 'no') == 'yes' && strlen(get_option('wc_crm_mailchimp_api_key')) > 0 && strlen(get_option('wc_crm_mailchimp_list')) > 0) ? true : false;
}

/**
 * Obtain better date/time formatting. Snippet borrowed from WooCommerce plugin.
 *
 * @param $post_id
 * @return string
 */
function woocommerce_crm_get_pretty_time($post_id, $plain = false)
{
    $post = get_post($post_id);
    if (!$post) return '';
    if ('0000-00-00 00:00:00' == $post->date) {
        $t_time = $h_time = __('Unpublished', 'woocommerce');
    } else {
        $t_time = get_the_time(__('Y/m/d g:i:s A', 'woocommerce'), $post);

        $gmt_time = strtotime($post->post_date_gmt . ' UTC');
        $time_diff = current_time('timestamp', 1) - $gmt_time;

        if ($time_diff > 0 && $time_diff < 24 * 60 * 60)
            $h_time = sprintf(__('%s ago', 'woocommerce'), human_time_diff($gmt_time, current_time('timestamp', 1)));
        else
            $h_time = get_the_time(wc_date_format(), $post);
    }
    if ($plain) {
        return esc_attr($t_time);
    } else {
        return '<abbr title="' . esc_attr($t_time) . '">' . esc_html(apply_filters('post_date_column_time', $h_time, $post)) . '</abbr>';
    }
}

function wc_crm_get_customer_pretty_time($date = false)
{
    if (!$date) return '';

    $t_time = date(__('Y/m/d g:i:s A', 'woocommerce'), strtotime($date));

    $gmt_time = strtotime($date . ' UTC');
    $time_diff = current_time('timestamp', 1) - $gmt_time;

    if ($time_diff > 0 && $time_diff < 24 * 60 * 60)
        $h_time = sprintf(__('%s ago', 'woocommerce'), human_time_diff($gmt_time, current_time('timestamp', 1)));
    else
        $h_time = date(__('Y/m/d', 'woocommerce'), strtotime($date));

    return '<abbr title="' . esc_attr($t_time) . '">' . esc_html(apply_filters('wc_crm_customer_created_time', $h_time, $date)) . '</abbr>';
}

/**
 * Obtains MailChimp lists for given API key.
 *
 * @param $api_key
 * @return array|bool
 */
function woocommerce_crm_get_mailchimp_lists($api_key)
{
    $mailchimp_lists = array();
    if (!$mailchimp_lists = get_transient('woocommerce_crm_mailchimp_lists')) {

        $mailchimp = wc_crm_mailchimp_init($api_key);
        if(!$mailchimp){
            return false;
        }
        $retval = $mailchimp->get('lists');
        if (!$mailchimp->success()) {

            echo '<div class="error"><p>' . sprintf(__('Unable to load lists() from MailChimp: (%s) %s', 'wc_crm'), $mailchimp->errorCode, $mailchimp->errorMessage) . '</p></div>';

            return false;

        } else {
            foreach ($retval['lists'] as $list){
                $mailchimp_lists[$list['id']] = $list['name'];
            }

            if (count($mailchimp_lists))
                set_transient('woocommerce_crm_mailchimp_lists', $mailchimp_lists, 60 * 60 * 1);
        }
    }

    if (!is_array($mailchimp_lists))
        $mailchimp_lists = array();

    return $api_key ? array_merge(array('' => __('Select a list...', 'wc_crm')), $mailchimp_lists) : array('' => __('Enter your key and save to see your lists', 'wc_crm'));

}

function wc_crm_get_statuses($arr = false, $without = false)
{
    global $wpdb;
    $sql = "SELECT * FROM {$wpdb->prefix}wc_crm_statuses";

    if ($arr === true) {
        $result = $wpdb->get_results($sql, ARRAY_A);
        if (!$without) {
            $statuses = WC_CRM()->statuses;
            foreach ($statuses as $key => $value) {
                $object = array();
                $object['status_name'] = $key;
                $object['status_slug'] = $value;
                $result[] = $object;
            }
        }
    } else {
        $result = $wpdb->get_results($sql);
        if (!$without) {
            $statuses = WC_CRM()->statuses;
            foreach ($statuses as $key => $value) {
                $object = new stdClass();
                $object->status_name = $key;
                $object->status_slug = $value;
                $result[] = $object;
            }
        }
    }
    return $result;
}

function wc_crm_get_statuses_options()
{
    global $wpdb;
    $sql = "SELECT * FROM {$wpdb->prefix}wc_crm_statuses";
    $statuses = WC_CRM()->statuses;
    $result = $wpdb->get_results($sql);
    if ($result) {
        foreach ($result as $r) {
            $statuses[$r->status_slug] = $r->status_name;
        }
    }
    return $statuses;
}

function wc_crm_get_statuses_slug()
{
    $st = wc_crm_get_statuses();
    $new_st = array();
    if (!empty($st)) {
        foreach ($st as $s) {
            $new_st[$s->status_slug] = $s->status_name;
        }
    }
    return $new_st;
}

function wc_crm_get_status($id = 0)
{
    if ($id) {
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}wc_crm_statuses WHERE status_id = {$id} LIMIT 1";
        $result = $wpdb->get_results($sql, ARRAY_A);
        if ($result)
            return $result[0];
        else
            return false;
    } else {
        return false;
    }
}

function wc_crm_get_status_by_slug($slug = '')
{
    if ($slug) {
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}wc_crm_statuses WHERE status_slug = '{$slug}' LIMIT 1";
        $result = $wpdb->get_results($sql, ARRAY_A);
        if ($result) {
            return $result[0];
        }
    }
    return false;
}

function wc_crm_get_status_icon_code($number)
{
    $d = intval($number, 10);
    $s = '';
    if ($d) {
        $s = base_convert($d, 10, 16);
        $s = '&#x' . $s . ';';
    }
    return $s;
}

function wc_crm_get_num_orders($val = '', $field = '_customer_user', $string = false)
{

    $num_orders_status = get_option('wc_crm_total_value');
    if (!$num_orders_status || empty($num_orders_status)) {
        $num_orders_status[] = 'wc-completed';
    }
    $orders_statuses = "'" . implode("','", $num_orders_status) . "'";
    $order_types = "'" . implode("','", wc_get_order_types('order-count')) . "'";

    global $wpdb;

    if ($field != '' && $val != '') {
        if ($string)
            $val = "'" . $val . "'";

        $count = $wpdb->get_var("SELECT COUNT(*)
						FROM $wpdb->posts as posts

						LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id

						WHERE   meta.meta_key       = '{$field}'
						AND     posts.post_type     IN ({$order_types})
						AND     posts.post_status   IN ({$orders_statuses})
						AND     meta.meta_value       = {$val}
					");
        return (int)$count;
    }
}

function wc_crm_get_order_value($val = '', $field = '_customer_user', $string = false)
{

    $num_orders_status = get_option('wc_crm_total_value');
    if (!$num_orders_status || empty($num_orders_status)) {
        $num_orders_status[] = 'wc-completed';
    }
    $orders_statuses = "'" . implode("','", $num_orders_status) . "'";
    $order_types = "'" . implode("','", wc_get_order_types('order-count')) . "'";

    global $wpdb;

    if ($field != '' && $val != '') {
        if ($string)
            $val = "'" . $val . "'";

        $sql = "SELECT SUM(meta.meta_value) as order_total

  					FROM {$wpdb->posts} AS post

						LEFT JOIN {$wpdb->postmeta} AS meta ON (post.ID = meta.post_id AND meta.meta_key = '_order_total')

						LEFT JOIN {$wpdb->postmeta} AS user ON (post.ID = user.post_id AND user.meta_key = '{$field}')

						WHERE   user.meta_value  = {$val} 
						AND     post.post_status IN({$orders_statuses})
            AND     post.post_type   IN ({$order_types})
						";

        $summ = $wpdb->get_var($sql);

        return $summ;
    }
}

function wc_crm_price_num_decimals($summ)
{
    $summ = (float)$summ;
    $num_decimals = absint(get_option('woocommerce_price_num_decimals'));
    $decimal_sep = wp_specialchars_decode(stripslashes(get_option('woocommerce_price_decimal_sep')), ENT_QUOTES);
    $thousands_sep = wp_specialchars_decode(stripslashes(get_option('woocommerce_price_thousand_sep')), ENT_QUOTES);

    if ($summ < 0) {
        $summ = $summ * -1;
        $negative = true;
    } else {
        $negative = false;
    }

    $summ = apply_filters('raw_woocommerce_price', floatval($summ));
    $summ = apply_filters('formatted_woocommerce_price', number_format($summ, $num_decimals, $decimal_sep, $thousands_sep), $summ, $num_decimals, $decimal_sep, $thousands_sep);

    if (apply_filters('woocommerce_price_trim_zeros', false) && $num_decimals > 0) {
        $summ = wc_trim_zeros($summ);
    }

    return $summ;
}

function wc_crm_get_existing_order_statuses()
{
    global $wpdb;
    $sql = "SELECT post_status FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status != 'trash' AND post_status != 'auto-draft'  GROUP BY post_status";
    $result = $wpdb->get_results($sql);
    return $result;
}

function wc_crm_get_accounts($option = false)
{
    global $wpdb;
    $sql = "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'wc_crm_accounts' AND post_status = 'publish' ";
    $result = $wpdb->get_results($sql);
    if ($option && $result) {
        $ac = array();
        foreach ($result as $value) {
            $ac[$value->ID] = $value->post_title;
        }
        return $ac;
    }
    return $result;
}


function wc_crm_get_customer_account($customer_id)
{
    global $wpdb;
    $sql = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wc_crm_customer_id' AND meta_value = {$customer_id} LIMIT 1";
    $result = $wpdb->get_var($sql);
    return $result;
}

function wc_crm_get_exclude_comments_post_types()
{
    return array(
        'wc_crm_accounts'
    );
}

function wc_crm_get_taxonomies($taxonomy = array('product_cat'))
{
    $categories = array();
    if (is_string($taxonomy))
        $taxonomy = array($taxonomy);

    $all_cat = get_terms($taxonomy, array('orderby' => 'name', 'order' => 'ASC'));
    if (!empty($all_cat) && !is_wp_error($all_cat)) {
        foreach ($all_cat as $key => $value) {
            $categories[$value->term_id] = $value->name;
        }
    }
    return $categories;
}

function wc_crm_get_lead_source()
{
    return apply_filters('wc_crm_lead_source', array(
        'advertisement' => __('Advertisement', 'wc_crm'),
        'cold_call' => __('Cold Call', 'wc_crm'),
        'employee_referral' => __('Employee Referral', 'wc_crm'),
        'external_referral' => __('External Referral', 'wc_crm'),
        'online_store' => __('Online Store', 'wc_crm'),
        'partner' => __('Partner', 'wc_crm'),
        'public_relations' => __('Public Relations', 'wc_crm'),
        'sales_mail_alias' => __('Sales Mail Alias', 'wc_crm'),
        'seminar_partner' => __('Seminar Partner', 'wc_crm'),
        'seminar_internal' => __('Seminar Internal', 'wc_crm'),
        'trade_show' => __('Trade Show', 'wc_crm'),
        'web_download' => __('Web Download', 'wc_crm'),
        'web_research' => __('Web Research', 'wc_crm'),
        'web_cases' => __('Web Cases', 'wc_crm'),
        'web_mail' => __('Web Mail', 'wc_crm'),
        'chat' => __('Chat', 'wc_crm'),
    ));
}

function wc_crm_get_lead_status()
{
    return apply_filters('wc_crm_lead_status', array(
        'attempted_to_contact' => __('Attempted to Contact', 'wc_crm'),
        'contact_in_future' => __('Contact in Future', 'wc_crm'),
        'contacted' => __('Contacted', 'wc_crm'),
        'junk_lead' => __('Junk Lead', 'wc_crm'),
        'lost_lead' => __('Lost Lead', 'wc_crm'),
        'not_contacted' => __('Not Contacted', 'wc_crm'),
        'pre_qualified' => __('Pre Qualified', 'wc_crm'),
    ));
}

function wc_crm_get_account_ownerships()
{
    return apply_filters('wc_crm_account_ownership', array(
        'other' => __('Other', 'wc_crm'),
        'private' => __('Private', 'wc_crm'),
        'public' => __('Public', 'wc_crm'),
        'subsidiary' => __('Subsidiary', 'wc_crm'),
    ));
}

function wc_crm_get_account_types()
{
    return apply_filters('wc_crm_account_types', array(
        'analyst' => __('Analyst', 'wc_crm'),
        'competitor' => __('Competitor', 'wc_crm'),
        'customer' => __('Customer', 'wc_crm'),
        'distributor' => __('Distributor', 'wc_crm'),
        'integrator' => __('Integrator', 'wc_crm'),
        'investor' => __('Investor', 'wc_crm'),
        'other' => __('Other', 'wc_crm'),
        'partner' => __('Partner', 'wc_crm'),
        'press' => __('Press', 'wc_crm'),
        'prospect' => __('Prospect', 'wc_crm'),
        'reseller' => __('Reseller', 'wc_crm'),
        'supplier' => __('Supplier', 'wc_crm'),
        'vendor' => __('Vendor', 'wc_crm')
    ));
}

function wc_crm_get_industries()
{
    $industries = array(
        "accounting" => __("Accounting", "wc_customer_relationship_manager"),
        "airlines_aviation" => __("Airlines/Aviation", "wc_customer_relationship_manager"),
        "alternative_dispute_resolution" => __("Alternative Dispute Resolution", "wc_customer_relationship_manager"),
        "alternative_medicine" => __("Alternative Medicine", "wc_customer_relationship_manager"),
        "animation" => __("Animation", "wc_customer_relationship_manager"),
        "apparel_n_fashion" => __("Apparel & Fashion", "wc_customer_relationship_manager"),
        "architecture_n_planning" => __("Architecture & Planning", "wc_customer_relationship_manager"),
        "arts_and_crafts" => __("Arts and Crafts", "wc_customer_relationship_manager"),
        "automotive" => __("Automotive", "wc_customer_relationship_manager"),
        "aviation_n_aerospace" => __("Aviation & Aerospace", "wc_customer_relationship_manager"),
        "banking" => __("Banking", "wc_customer_relationship_manager"),
        "biotechnology" => __("Biotechnology", "wc_customer_relationship_manager"),
        "broadcast_media" => __("Broadcast Media", "wc_customer_relationship_manager"),
        "building_materials" => __("Building Materials", "wc_customer_relationship_manager"),
        "business_supplies_and_equipment" => __("Business Supplies and Equipment", "wc_customer_relationship_manager"),
        "capital_markets" => __("Capital Markets", "wc_customer_relationship_manager"),
        "chemicals" => __("Chemicals", "wc_customer_relationship_manager"),
        "civic_n_social_organization" => __("Civic & Social Organization", "wc_customer_relationship_manager"),
        "civil_engineering" => __("Civil Engineering", "wc_customer_relationship_manager"),
        "commercial_real_estate" => __("Commercial Real Estate", "wc_customer_relationship_manager"),
        "computer_n_network_security" => __("Computer & Network Security", "wc_customer_relationship_manager"),
        "computer_games" => __("Computer Games", "wc_customer_relationship_manager"),
        "computer_hardware" => __("Computer Hardware", "wc_customer_relationship_manager"),
        "computer_networking" => __("Computer Networking", "wc_customer_relationship_manager"),
        "computer_software" => __("Computer Software", "wc_customer_relationship_manager"),
        "construction" => __("Construction", "wc_customer_relationship_manager"),
        "consumer_electronics" => __("Consumer Electronics", "wc_customer_relationship_manager"),
        "consumer_goods" => __("Consumer Goods", "wc_customer_relationship_manager"),
        "consumer_services" => __("Consumer Services", "wc_customer_relationship_manager"),
        "cosmetics" => __("Cosmetics", "wc_customer_relationship_manager"),
        "dairy" => __("Dairy", "wc_customer_relationship_manager"),
        "defense_n_space" => __("Defense & Space", "wc_customer_relationship_manager"),
        "design" => __("Design", "wc_customer_relationship_manager"),
        "education_management" => __("Education Management", "wc_customer_relationship_manager"),
        "e-learning" => __("E-Learning", "wc_customer_relationship_manager"),
        "electrical_electronic_manufacturing" => __("Electrical/Electronic Manufacturing", "wc_customer_relationship_manager"),
        "entertainment" => __("Entertainment", "wc_customer_relationship_manager"),
        "environmental_services" => __("Environmental Services", "wc_customer_relationship_manager"),
        "events_services" => __("Events Services", "wc_customer_relationship_manager"),
        "executive_office" => __("Executive Office", "wc_customer_relationship_manager"),
        "facilities_services" => __("Facilities Services", "wc_customer_relationship_manager"),
        "farming" => __("Farming", "wc_customer_relationship_manager"),
        "financial_services" => __("Financial Services", "wc_customer_relationship_manager"),
        "fine_art" => __("Fine Art", "wc_customer_relationship_manager"),
        "fishery" => __("Fishery", "wc_customer_relationship_manager"),
        "food_n_beverages" => __("Food & Beverages", "wc_customer_relationship_manager"),
        "food_production" => __("Food Production", "wc_customer_relationship_manager"),
        "fund-raising" => __("Fund-Raising", "wc_customer_relationship_manager"),
        "furniture" => __("Furniture", "wc_customer_relationship_manager"),
        "gambling_n_casinos" => __("Gambling & Casinos", "wc_customer_relationship_manager"),
        "glass__ceramics_n_concrete" => __("Glass, Ceramics & Concrete", "wc_customer_relationship_manager"),
        "government_administration" => __("Government Administration", "wc_customer_relationship_manager"),
        "government_relations" => __("Government Relations", "wc_customer_relationship_manager"),
        "graphic_design" => __("Graphic Design", "wc_customer_relationship_manager"),
        "health__wellness_and_fitness" => __("Health, Wellness and Fitness", "wc_customer_relationship_manager"),
        "higher_education" => __("Higher Education", "wc_customer_relationship_manager"),
        "hospital_n_health_care" => __("Hospital & Health Care", "wc_customer_relationship_manager"),
        "hospitality" => __("Hospitality", "wc_customer_relationship_manager"),
        "human_resources" => __("Human Resources", "wc_customer_relationship_manager"),
        "import_and_export" => __("Import and Export", "wc_customer_relationship_manager"),
        "individual_n_family_services" => __("Individual & Family Services", "wc_customer_relationship_manager"),
        "industrial_automation" => __("Industrial Automation", "wc_customer_relationship_manager"),
        "information_services" => __("Information Services", "wc_customer_relationship_manager"),
        "information_technology_and_services" => __("Information Technology and Services", "wc_customer_relationship_manager"),
        "insurance" => __("Insurance", "wc_customer_relationship_manager"),
        "international_affairs" => __("International Affairs", "wc_customer_relationship_manager"),
        "international_trade_and_development" => __("International Trade and Development", "wc_customer_relationship_manager"),
        "internet" => __("Internet", "wc_customer_relationship_manager"),
        "investment_banking" => __("Investment Banking", "wc_customer_relationship_manager"),
        "investment_management" => __("Investment Management", "wc_customer_relationship_manager"),
        "judiciary" => __("Judiciary", "wc_customer_relationship_manager"),
        "law_enforcement" => __("Law Enforcement", "wc_customer_relationship_manager"),
        "law_practice" => __("Law Practice", "wc_customer_relationship_manager"),
        "legal_services" => __("Legal Services", "wc_customer_relationship_manager"),
        "legislative_office" => __("Legislative Office", "wc_customer_relationship_manager"),
        "leisure__travel_n_tourism" => __("Leisure, Travel & Tourism", "wc_customer_relationship_manager"),
        "libraries" => __("Libraries", "wc_customer_relationship_manager"),
        "logistics_and_supply_chain" => __("Logistics and Supply Chain", "wc_customer_relationship_manager"),
        "luxury_goods_n_jewelry" => __("Luxury Goods & Jewelry", "wc_customer_relationship_manager"),
        "machinery" => __("Machinery", "wc_customer_relationship_manager"),
        "management_consulting" => __("Management Consulting", "wc_customer_relationship_manager"),
        "maritime" => __("Maritime", "wc_customer_relationship_manager"),
        "market_research" => __("Market Research", "wc_customer_relationship_manager"),
        "marketing_and_advertising" => __("Marketing and Advertising", "wc_customer_relationship_manager"),
        "mechanical_or_industrial_engineering" => __("Mechanical or Industrial Engineering", "wc_customer_relationship_manager"),
        "media_production" => __("Media Production", "wc_customer_relationship_manager"),
        "medical_devices" => __("Medical Devices", "wc_customer_relationship_manager"),
        "medical_practice" => __("Medical Practice", "wc_customer_relationship_manager"),
        "mental_health_care" => __("Mental Health Care", "wc_customer_relationship_manager"),
        "military" => __("Military", "wc_customer_relationship_manager"),
        "mining_n_metals" => __("Mining & Metals", "wc_customer_relationship_manager"),
        "motion_pictures_and_film" => __("Motion Pictures and Film", "wc_customer_relationship_manager"),
        "museums_and_institutions" => __("Museums and Institutions", "wc_customer_relationship_manager"),
        "music" => __("Music", "wc_customer_relationship_manager"),
        "nanotechnology" => __("Nanotechnology", "wc_customer_relationship_manager"),
        "newspapers" => __("Newspapers", "wc_customer_relationship_manager"),
        "non-profit_organization_management" => __("Non-Profit Organization Management", "wc_customer_relationship_manager"),
        "oil_n_energy" => __("Oil & Energy", "wc_customer_relationship_manager"),
        "online_media" => __("Online Media", "wc_customer_relationship_manager"),
        "outsourcing_offshoring" => __("Outsourcing/Offshoring", "wc_customer_relationship_manager"),
        "package_freight_delivery" => __("Package/Freight Delivery", "wc_customer_relationship_manager"),
        "packaging_and_containers" => __("Packaging and Containers", "wc_customer_relationship_manager"),
        "paper_n_forest_products" => __("Paper & Forest Products", "wc_customer_relationship_manager"),
        "performing_arts" => __("Performing Arts", "wc_customer_relationship_manager"),
        "pharmaceuticals" => __("Pharmaceuticals", "wc_customer_relationship_manager"),
        "philanthropy" => __("Philanthropy", "wc_customer_relationship_manager"),
        "photography" => __("Photography", "wc_customer_relationship_manager"),
        "plastics" => __("Plastics", "wc_customer_relationship_manager"),
        "political_organization" => __("Political Organization", "wc_customer_relationship_manager"),
        "primary_secondary_education" => __("Primary/Secondary Education", "wc_customer_relationship_manager"),
        "printing" => __("Printing", "wc_customer_relationship_manager"),
        "professional_training_n_coaching" => __("Professional Training & Coaching", "wc_customer_relationship_manager"),
        "program_development" => __("Program Development", "wc_customer_relationship_manager"),
        "public_policy" => __("Public Policy", "wc_customer_relationship_manager"),
        "public_relations_and_communications" => __("Public Relations and Communications", "wc_customer_relationship_manager"),
        "public_safety" => __("Public Safety", "wc_customer_relationship_manager"),
        "publishing" => __("Publishing", "wc_customer_relationship_manager"),
        "railroad_manufacture" => __("Railroad Manufacture", "wc_customer_relationship_manager"),
        "ranching" => __("Ranching", "wc_customer_relationship_manager"),
        "real_estate" => __("Real Estate", "wc_customer_relationship_manager"),
        "recreational_facilities_and_services" => __("Recreational Facilities and Services", "wc_customer_relationship_manager"),
        "religious_institutions" => __("Religious Institutions", "wc_customer_relationship_manager"),
        "renewables_n_environment" => __("Renewables & Environment", "wc_customer_relationship_manager"),
        "research" => __("Research", "wc_customer_relationship_manager"),
        "restaurants" => __("Restaurants", "wc_customer_relationship_manager"),
        "retail" => __("Retail", "wc_customer_relationship_manager"),
        "security_and_investigations" => __("Security and Investigations", "wc_customer_relationship_manager"),
        "semiconductors" => __("Semiconductors", "wc_customer_relationship_manager"),
        "shipbuilding" => __("Shipbuilding", "wc_customer_relationship_manager"),
        "sporting_goods" => __("Sporting Goods", "wc_customer_relationship_manager"),
        "sports" => __("Sports", "wc_customer_relationship_manager"),
        "staffing_and_recruiting" => __("Staffing and Recruiting", "wc_customer_relationship_manager"),
        "supermarkets" => __("Supermarkets", "wc_customer_relationship_manager"),
        "telecommunications" => __("Telecommunications", "wc_customer_relationship_manager"),
        "textiles" => __("Textiles", "wc_customer_relationship_manager"),
        "think_tanks" => __("Think Tanks", "wc_customer_relationship_manager"),
        "tobacco" => __("Tobacco", "wc_customer_relationship_manager"),
        "translation_and_localization" => __("Translation and Localization", "wc_customer_relationship_manager"),
        "transportation_trucking_railroad" => __("Transportation/Trucking/Railroad", "wc_customer_relationship_manager"),
        "utilities" => __("Utilities", "wc_customer_relationship_manager"),
        "venture_capital_n_private_equity" => __("Venture Capital & Private Equity", "wc_customer_relationship_manager"),
        "veterinary" => __("Veterinary", "wc_customer_relationship_manager"),
        "warehousing" => __("Warehousing", "wc_customer_relationship_manager"),
        "wholesale" => __("Wholesale", "wc_customer_relationship_manager"),
        "wine_and_spirits" => __("Wine and Spirits", "wc_customer_relationship_manager"),
        "wireless" => __("Wireless", "wc_customer_relationship_manager"),
        "writing_and_editing" => __("Writing and Editing", "wc_customer_relationship_manager")
    );
    return apply_filters('wc_crm_industries', $industries);
}

function wc_crm_wp_multiselect($field)
{
    global $thepostid, $post;

    $thepostid = empty($thepostid) ? $post->ID : $thepostid;
    $field['class'] = isset($field['class']) ? $field['class'] : 'select short';
    $field['style'] = isset($field['style']) ? $field['style'] : '';
    $field['wrapper_class'] = isset($field['wrapper_class']) ? $field['wrapper_class'] : '';
    $field['value'] = isset($field['value']) ? $field['value'] : get_post_meta($thepostid, $field['id'], true);

    // Custom attribute handling
    $custom_attributes = array();

    if (!empty($field['custom_attributes']) && is_array($field['custom_attributes'])) {

        foreach ($field['custom_attributes'] as $attribute => $value) {
            $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($value) . '"';
        }
    }
    $name = esc_attr($field['id']) . '[]';

    echo '<p class="form-field ' . esc_attr($field['id']) . '_field ' . esc_attr($field['wrapper_class']) . '"><label for="' . esc_attr($field['id']) . '">' . wp_kses_post($field['label']) . '</label><select multiple="multiple" id="' . esc_attr($field['id']) . '" name="' . $name . '" class="' . esc_attr($field['class']) . '" style="' . esc_attr($field['style']) . '" ' . implode(' ', $custom_attributes) . '>';

    foreach ($field['options'] as $key => $value) {
        echo '<option value="' . esc_attr($key) . '" ' . selected(in_array(esc_attr($key), $field['value']), true, false) . '>' . esc_html($value) . '</option>';
    }

    echo '</select> ';

    if (!empty($field['description'])) {

        if (isset($field['desc_tip']) && false !== $field['desc_tip']) {
            echo '<img class="help_tip" data-tip="' . esc_attr($field['description']) . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
        } else {
            echo '<span class="description">' . wp_kses_post($field['description']) . '</span>';
        }
    }
    echo '</p>';
}


function wc_crm_automatic_emails_new_customer($email_actions)
{
    $new_actions = array();
    $aenc = get_option('wc_crm_automatic_emails_new_customer');
    if ($aenc == '') $aenc = 'yes';

    foreach ($email_actions as $action) {
        if ($action == 'woocommerce_created_customer' && $aenc == 'no')
            continue;

        $new_actions[] = $action;
    }
    return $new_actions;
}

function wcrm_customer_bought_products($customer_email, $user_id)
{
    global $wpdb;

    $emails = array();

    if ($user_id) {
        $user = get_user_by('id', $user_id);

        if (isset($user->user_email)) {
            $emails[] = $user->user_email;
        }
    }

    if (is_email($customer_email)) {
        $emails[] = $customer_email;
    }

    if (sizeof($emails) == 0) {
        return array();
    }

    $sql = $wpdb->prepare("SELECT itemmeta.meta_value as ID, line_qty.meta_value as items_count, SUM(line_total.meta_value) as line_total
			FROM {$wpdb->prefix}woocommerce_order_items as order_items
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS itemmeta ON ( order_items.order_item_id = itemmeta.order_item_id  AND itemmeta.meta_key = '_product_id')
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS line_qty ON ( order_items.order_item_id = line_qty.order_item_id  AND line_qty.meta_key = '_qty')
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS line_total ON ( order_items.order_item_id = line_total.order_item_id AND line_total.meta_key = '_line_total')
			LEFT JOIN {$wpdb->postmeta} AS e_postmeta ON ( order_items.order_id = e_postmeta.post_id AND e_postmeta.meta_key = '_billing_email')
			LEFT JOIN {$wpdb->postmeta} AS c_postmeta ON ( order_items.order_id = c_postmeta.post_id AND c_postmeta.meta_key = '_customer_user')
			LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
			LEFT JOIN {$wpdb->posts} AS products ON itemmeta.meta_value = products.ID
			WHERE
				order_items.order_item_type = 'line_item'
				AND itemmeta.meta_key = '_product_id'
				AND products.post_status = 'publish'
				AND (
						e_postmeta.meta_value  IN ( '" . implode("','", array_map('esc_sql', array_unique($emails))) . "' )
						OR
						(
							c_postmeta.meta_value = %d AND
							c_postmeta.meta_value > 0
						)
					)
					AND posts.post_status NOT LIKE 'trash'
			GROUP BY itemmeta.meta_value
			", $user_id
    );

    return $wpdb->get_results($sql);
}

function wcrm_enable_generate_password($value)
{
    return 'yes';
}

function wc_crm_clear_transient()
{
    global $wpdb;

    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wc_crm%';");
}

function wc_crm_convertToHoursMins($time)
{
    $new_time = '';
    $time_arr = explode(':', $time);
    $h = intval($time_arr[0]);
    $m = intval($time_arr[1]);
    $s = intval($time_arr[2]);
    $new_time .= ($h > 0) ? $h . 'h ' : '';
    $new_time .= ($m > 0) ? $m . 'm ' : '';
    $new_time .= ($s > 0) ? $s . 's' : '';
    return $new_time;
}

function wc_crm_get_acf_fields()
{
    add_filter('acf/location/rule_match/ef_crm_customers', '__return_true');

    $acf_fields = array();
    $acfs = apply_filters('acf/get_field_groups', false);
    if ($acfs) {
        $filter = array(
            'ef_user' => true,
            'ef_crm_customers' => true,
            'post_type' => 'shop_order'
        );
        $field_groups = apply_filters('acf/location/match_field_groups', array(), $filter);

        foreach ($acfs as $acf) {
            if (in_array($acf['id'], $field_groups)) {
                $fields = apply_filters('acf/field_group/get_fields', array(), $acf['id']);
                foreach ($fields as $field) {
                    if (!empty($field['name'])) {
                        $acf_fields[$field['name']] = $field['label'];
                    }
                }
            }
        }
    }
    remove_filter('acf/location/rule_match/ef_crm_customers', '__return_true');
    return $acf_fields;
}

function get_acf_meta_fields()
{
    global $wpdb;

    return $wpdb->get_results("SELECT DISTINCT DISTINCT p.post_excerpt, p.post_title FROM {$wpdb->prefix}usermeta um
                INNER JOIN {$wpdb->prefix}posts p
                ON p.post_excerpt = um.meta_key");

}

function get_acf_fields_array()
{
    $acf_options = array();
    $custom_fields = get_acf_meta_fields();
    foreach ($custom_fields as $key => $field) {
        $acf_options["acf_" . $field->post_excerpt] = $field->post_title;
    }
    return $acf_options;
}

function wc_crm_mailchimp_init($api_key){
    $instance = null;
    try{
        $instance = new MailChimpAPI($api_key);
    }catch (Exception $e ){
        $instance = null;
    }

    return $instance;
}