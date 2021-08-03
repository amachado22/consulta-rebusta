<?php
/**
 * Class for Customer
 *
  * @author     Adailton Machado
 * @package  WC_CRM
 * @since    1.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WC_CRM_Customer
{

    /** @public int Customer (wc_crm_customer_list) ID. */
    public $customer_id = 0;

    /** @public int Customer (wc_crm_customer_list) ID. */
    public $id = 0;

    /** @public int User (user) ID. */
    public $user_id = 0;
    
    /** @public string customer cpf. */
    public $cpf = '';
    
    /** @public string customer cnpj. */
    public $cnpj ='' ;

    /** @public string Customer status. */
    public $status = '';

    /** @public string Customer first name. */
    public $first_name = '';

    /** @public string Customer last name. */
    public $last_name = '';

    /** @public string Customer astate. */
    public $state = '';

    /** @public string Customer city. */
    public $city = '';

    /** @public string Customer country. */
    public $country = '';

    /** @public string Date of last customer purchases. */
    public $last_purchase = '';

    /** @public int Order value. */
    public $order_value = 0;

    /** @public int Num orders. */
    public $num_orders = 0;

    /** @public array/bool Customer capabilities. */
    public $capabilities = false;

    /** @public array General fields. */
    public $general_fields = array();

    /** @public array Billing fields. */
    public $billing_fields = array();

    /** @public array Shipping fields. */
    public $shipping_fields;

    /** @public int Last customer order ID. */
    public $order_id = 0;

    /** @public array Settings regarding the display of customer data. */
    private $options = array();

    /** @protected string Formatted address. Accessed via get_formatted_billing_address() */
    protected $formatted_billing_address = '';

    /** @protected string Formatted address. Accessed via get_formatted_shipping_address() */
    protected $formatted_shipping_address = '';

    /** @var $user WP_User. */
    public $user = null;

    /** @public $customer stdClass */
    public $customer = null;

    /**
     * __isset function.
     *
     * @param mixed $key
     * @return bool
     */
    public function __isset($key)
    {
        $result = isset($this->customer->$key);
        if (!$result) {
            if ($this->user_id > 0) {
                $result = metadata_exists('user', $this->user_id, '_' . $key);
                if (!$result) {
                    $result = metadata_exists('user', $this->user_id, $key);
                }
            } else if ($this->order_id > 0) {
                $result = metadata_exists('post', $this->order_id, '_' . $key);
                if (!$result) {
                    $result = metadata_exists('post', $this->order_id, $key);
                }
                if (!$result) {
                    $result = metadata_exists('wc_crm_customer', $this->customer_id, $key);
                }
            }
        }
        return $result;
    }

    /**
     * __get function.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        switch ($key) {
            case 'email':
                if ($this->user_id == 0) {
                    $key = 'billing_email';
                }
                break;
            case 'preferred_payment_method':
            case 'payment_method':
                if ($this->user_id > 0) {
                    $key = 'preferred_payment_method';
                } else {
                    $key = 'payment_method';
                }
                break;
            case 'phone':
                $key = 'billing_phone';
                break;
        }
        $value = isset($this->customer->$key) ? $this->customer->$key : '';

        switch ($key) {
            case 'name':
                if ($this->options && $this->options->customer_name == 'lf') {
                    $n = array();
                    if (!empty($this->first_name))
                        $n[] = $this->last_name;
                    if (!empty($this->last_name))
                        $n[] = $this->first_name;
                    $value = trim($this->last_name) . ' ' .trim ($this->first_name);
                } else {
                    $value = trim($this->first_name . ' ' . $this->last_name);
                }
                break;
        }

        if (empty($value)) {
            if ($this->user_id > 0) {
                $value = get_user_meta($this->user_id, $key, true);
                if (empty($value)) {
                    $value = get_user_meta($this->user_id, '_' . $key, true);
                }
            } else if ($this->order_id > 0) {
                $value = wc_crm_get_cmeta($this->customer_id, $key, true);

                if (empty($value)) {
                    $value = get_post_meta($this->order_id, $key, true);
                }
                if (empty($value)) {
                    $value = get_post_meta($this->order_id, '_' . $key, true);
                }
            }
        }


        if (!empty($value)) {
            $this->$key = $value;
        }

        return $value;
    }

    public function __construct($customer = 0)
    {
        $this->init($customer);
    }

    /**
     * Init/load the customer object. Called from the constructor.
     *
     * @param  int|object|WC_CRM_Customer $customer Customer to init.
     */
    protected function init($customer)
    {
        if (is_numeric($customer)) {
            $this->id = absint($customer);
            $this->customer_id = absint($customer);
            $this->customer = wc_crm_get_customer($customer);
            $this->get_customer($this->id);
        } elseif ($customer instanceof WC_CRM_Customer) {
            $this->id = absint($customer->id);
            $this->customer_id = absint($customer->id);
            $this->customer = $customer->customer;
            $this->get_customer($this->id);
        } elseif (isset($customer->ID)) {
            $this->customer = wc_crm_get_customer($customer->ID, 'user_id');
            $this->id = absint(!is_null($this->customer) ? absint($this->customer->c_id) : 0);
            $this->customer_id = $this->id;
            $this->get_customer($this->id);
        }
    }

    /**
     * Gets an call from the database.
     *
     * @param int $id (default: 0).
     * @return bool
     */
    public function get_customer($id = 0)
    {

        if (!$id) {
            return false;
        }

        if ($result = wc_crm_get_customer($id)) {
            $this->populate($result);
            return true;
        }

        return false;
    }

    /**
     * Populates an call from the loaded post data.
     *
     * @param mixed $result
     */
    public function populate($result)
    {
        $this->id = $result->c_id;
        $this->customer_id = $result->c_id;
        foreach ($result as $key => $value) {
            $this->$key = $value;
        }
        $this->options = (object)array(
            'total_value' => get_option('wc_crm_total_value', array('wc-completed')),
            'user_roles' => get_option('wc_crm_user_roles', array('customer')),
            'guest_customers' => get_option('wc_crm_guest_customers', 'no'),
            'customer_name' => get_option('wc_crm_customer_name', 'fl'),
        );
        if ($this->user_id > 0) {
            $data = get_user_by('id', $this->user_id);
            if ($data) {
                $this->user = $data;
                $this->user_login = $data->user_login;
                $this->user_email = $data->user_email;
                $this->user_url = $data->user_url;
            }
        } else {
            $this->user_login = __('Guest', 'wc_crm');
            $this->user_email = $this->email;
        }
        $this->get_name();
    }

    public function get_name()
    {
        return $this->name;
    }

    public function get_name_secondary_label()
    {
        $secondary_label = get_option('wc_crm_username_secondary_label');
        switch ($secondary_label) {
            case 'company':
                return $this->billing_company;
                break;

            default:
                return $this->user_login;
                break;
        }
    }

    public function get_email()
    {
        return $this->email;
    }

    public function get_groups()
    {
        global $wpdb;

        if (!empty($this->groups))
            return $this->groups;

        $group_data = array();

        $group_data = $wpdb->get_results("SELECT group_id FROM {$wpdb->prefix}wc_crm_groups_relationships WHERE c_id = '{$this->customer_id}'", ARRAY_N);
        if ($group_data) {
            foreach ($group_data as $key => $groupd) {
                $group_data[] = $groupd[0];
            }
        }
        $this->groups = $group_data;
        return $group_data;
    }

    public function get_account()
    {
        global $wpdb;
        $sql = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wc_crm_customer_id' AND meta_value = {$this->customer_id} order by meta_id DESC LIMIT 1";
        $result = $wpdb->get_var($sql);
        return $result;
    }

    public function get_orders()
    {
        $orders = array();
        if ($this->email == '' && $this->user_id == 0) {
            return $orders;
        }
        $key = '_billing_email';
        $email = $this->email;

        if ($this->user_id > 0) {
            $key = '_customer_user';
            $email = $this->user_id;
        }

        $args = array(
            'numberposts' => -1,
            'post_type' => 'shop_order',
            'post_status' => array_keys(wc_get_order_statuses()),
            'meta_query' => array(
                array(
                    'key' => $key,
                    'value' => $email
                )
            ),
        );
        $orders = new WP_Query($args);
        return $orders->posts;
    }

    public function get_calls()
    {
        $calls = array();
        $args = array(
            'numberposts' => -1,
            'post_type' => 'wc_crm_calls',
            'meta_query' => array(
                array(
                    'key' => '_customer_id',
                    'value' => $this->id
                )
            ),
        );
        $calls = new WP_Query($args);
        return $calls->posts;
    }

    public function get_tasks()
    {
        $tasks = array();
        $args = array(
            'numberposts' => -1,
            'post_type' => 'wc_crm_tasks',
            'meta_query' => array(
                array(
                    'key' => '_customer_id',
                    'value' => $this->id
                )
            ),
        );
        $tasks = new WP_Query($args);
        return $tasks->posts;
    }

    public function get_activity()
    {
        global $wpdb;
        if ($this->user_id > 0) {
            $logs = get_user_meta($this->user_id, 'wc_crm_log_id');
        } else {
            $logs = wc_crm_get_cmeta($this->customer_id, 'wc_crm_log_id');
        }
        $data = array();
        if (!empty($logs)) {
            $logs = implode(', ', $logs);
            $filter = "WHERE ID IN({$logs})";

            if (!empty($_REQUEST['activity_types'])) {
                $filter .= ' AND ';
                $filter .= 'activity_type = "' . $_REQUEST['activity_types'] . '"';
            }
            if (isset($_GET['log_status']) && $_GET['log_status'] == 'trash') {
                $filter .= ' AND ';
                $filter .= 'log_status = \'trash\' ';
            } else {
                $filter .= ' AND ';
                $filter .= 'log_status <> \'trash\' ';
            }
            if (!empty($_REQUEST['log_users'])) {
                $filter .= ' AND ';
                $filter .= 'user_id = ' . $_REQUEST['log_users'];
            }
            $filter_m = '';
            if (!empty($_REQUEST['created_date'])) {
                $month = substr($_REQUEST['created_date'], -2);
                if ($month[0] == 0) $month = substr($month, -1);
                $year = substr($_REQUEST['created_date'], 0, 4);
                $filter_m .= ' AND ';
                $filter_m .= 'YEAR( created ) = ' . $year . ' AND MONTH( created ) = ' . $month;
            }
            $orderby = !empty($_GET['orderby']) ? 'ORDER BY ' . $_GET['orderby'] : 'ORDER BY created';
            $order = !empty($_GET['order']) ? $_GET['order'] : 'ASC';

            $table_name = $wpdb->prefix . "wc_crm_log";
            $db_data = $wpdb->get_results("SELECT * FROM $table_name $filter $filter_m $orderby $order");

            if ($db_data) {
                foreach ($db_data as $value) {
                    $data[] = get_object_vars($value);
                }
            }
        }
        return $data;
    }

    public function init_general_fields()
    {
        $email = $this->user_email;
        $url = '';
        $user_info = get_userdata($this->user_id);
        if ($user_info) {
            $email = $user_info->user_email;
            $url = $user_info->user_url;
        }
        $accounts = $this->get_account();

        if (isset($_GET['page']) && $_GET['page'] == 'wc_crm-new-customer' && isset($_GET['account']) && !empty($_GET['account'])) {
            $accounts = $_GET['account'];
        }

        $cat = $this->customer_categories;
        $brands = $this->customer_brands;
        if (!is_array($cat))
            $cat = array();
        if (!is_array($brands))
            $brands = array();

        $customer_agent = (int)$this->customer_agent;
        $user_string = '';

        if ($this->customer_id == 0) {
            $customer_agent = wc_crm_get_customer(get_current_user_id(), 'user_id')->c_id;
        }

        if ($customer_agent) {
            $user = new WC_CRM_Customer($customer_agent);
            if ($user) {
                $user_string = esc_html($user->name) . ' (#' . absint($user->id) . ' - ' . esc_html($user->email);
            }
        } else {
            $customer_agent = '';
        }

        $this->general_fields = apply_filters('wcrm_admin_general_fields', array(
            'first_name' => array(
                'meta_key' => 'first_name',
                'label' => __('Primeiro nome', 'wc_crm'),
                'value' => $this->first_name
            ),
            'last_name' => array(
                'meta_key' => 'last_name',
                'label' => __('Sobrenome', 'wc_crm'),
                'value' => $this->last_name
            ),
            'customer_status' => array(
                'meta_key' => 'customer_status',
                'label' => __('Status do cliente', 'wc_crm'),
                'value' => $this->status,
                'class' => 'wc-enhanced-select',
                'type' => 'select',
                'options' => wc_crm_get_statuses_options()
            ),
            'account_name' => array(
                'meta_key' => 'account_name',
                'label' => __('Nome da conta', 'wc_crm'),
                'value' => $accounts,
                'class' => 'wc-enhanced-select',
                'type' => 'select',
                'custom_attributes' => array(
                    'data-allow_clear' => true,
                    'data-placeholder' => __('Select an account&hellip;', 'wc_crm'),
                ),
                'options' => array('' => '') + wc_crm_get_accounts(true)
            ),
            'customer_title' => array(
                'meta_key' => 'title',
                'label' => __('Titulo', 'wc_crm'),
                'value' => $this->title
            ),
            'user_email' => array(
                'meta_key' => 'user_email',
                'label' => __('Email', 'wc_crm'),
                'value' => $email,
                'custom_attributes' => array(
                    'required' => true,
                ),
            ),
            'customer_billing_cpf' => array(
                'meta_key' => 'billing_cpf',
                'label' => __('CPF', 'wc_crm'),
                'value' => $this->cpf
                
            ),
            'customer_billing_cnpj' => array(
                'meta_key' => 'billing_cnpj',
                'label' => __('CNPJ', 'wc_crm'),
                'value' => $this->cnpj
                
            ),
            'customer_department' => array(
                'meta_key' => 'department',
                'label' => __('Departamento', 'wc_crm'),
                'value' => $this->department
            ),
            'customer_mobile' => array(
                'meta_key' => 'mobile',
                'label' => __('Celular', 'wc_crm'),
                'value' => $this->mobile
            ),
            'customer_fax' => array(
                'meta_key' => 'fax',
                'label' => __('Fax', 'wc_crm'),
                'value' => $this->fax
            ),
            'customer_site' => array(
                'meta_key' => 'url',
                'label' => __('Website', 'wc_crm'),
                'value' => $this->user_url
            ),
            'customer_lead_source' => array(
                'meta_key' => 'lead_source',
                'label' => __('Lead Source', 'wc_crm'),
                'value' => $this->lead_source,
                'class' => 'wc-enhanced-select',
                'type' => 'select',
                'custom_attributes' => array(
                    'data-allow_clear' => true,
                    'data-placeholder' => __('Select a Lead Source&hellip;', 'wc_crm'),
                ),
                'options' => array('' => '') + wc_crm_get_lead_source()
            ),
            'customer_lead_status' => array(
                'meta_key' => 'lead_status',
                'label' => __('Lead Status', 'wc_crm'),
                'value' => $this->lead_status,
                'class' => 'wc-enhanced-select',
                'type' => 'select',
                'custom_attributes' => array(
                    'data-allow_clear' => true,
                    'data-placeholder' => __('Select a Lead Status&hellip;', 'wc_crm'),
                ),
                'options' => array('' => '') + wc_crm_get_lead_status()
            ),
            'customer_industry' => array(
                'meta_key' => 'industry',
                'label' => __('Industria', 'wc_crm'),
                'value' => $this->industry,
                'class' => 'wc-enhanced-select',
                'type' => 'select',
                'custom_attributes' => array(
                    'data-allow_clear' => true,
                    'data-placeholder' => __('Select an Industry&hellip;', 'wc_crm'),
                ),
                'options' => array("" => "") + wc_crm_get_industries()
            ),
            'customer_agent' => array(
                'meta_key' => 'customer_agent',
                'label' => __('Agent', 'wc_crm'),
                'value' => $customer_agent,
                'class' => 'wc-crm-agent-search',
                'type' => 'select',
                'options' => array(
                    $customer_agent => htmlspecialchars($user_string),
                ),
                'custom_attributes' => array(
                    'data-allow-clear' => wp_get_current_user()->has_cap('agent') ? "false" : "true",
                    'data-placeholder' => __('Select an Agent&hellip;', 'wc_crm'),
                ),
            ),
            'date_of_birth' => array(
                'meta_key' => 'date_of_birth',
                'label' => __('Data de Aniversario', 'wc_crm'),
                'value' => $this->date_of_birth,
                'custom_attributes' => array(
                    'pattern' => "[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])",
                    'maxlength' => 10,
                )
            ),
            'customer_assistant' => array(
                'meta_key' => 'assistant',
                'label' => __('Assistante', 'wc_crm'),
                'value' => $this->assistant
            ),
            'customer_skype' => array(
                'meta_key' => 'skype',
                'label' => __('Skype', 'wc_crm'),
                'value' => $this->skype
            ),
            'customer_twitter' => array(
                'meta_key' => 'twitter',
                'label' => __('Twitter', 'wc_crm'),
                'value' => '@' . $this->twitter,
            ),
            'customer_categories' => array(
                'meta_key' => 'customer_categories',
                'label' => __('Categorias', 'wc_crm'),
                'value' => $cat,
                'class' => 'wc-enhanced-select',
                'type' => 'multiselect',
                'custom_attributes' => array(
                    'data-placeholder' => __('Pesquise uma categoria&hellip;', 'wc_crm')
                ),
                'options' => wc_crm_get_taxonomies()
            ),
            'customer_brands' => array(
                'meta_key' => 'customer_brands',
                'label' => __('Marcas', 'wc_crm'),
                'value' => $brands,
                'class' => 'wc-enhanced-select',
                'type' => 'multiselect',
                'custom_attributes' => array(
                    'data-placeholder' => __('Pesquise uma marca&hellip;', 'wc_crm')
                ),
                'options' => wc_crm_get_taxonomies('product_brand')
            )
        ));
    }

    public function init_address_fields($show_country = true)
    {
        $b_country = $this->billing_country;
        $s_country = $this->shipping_country;
        $this->billing_fields = apply_filters('woocommerce_admin_billing_fields', array(
            'first_name' => array(
                'label' => __('Primeiro nome', 'woocommerce'),
                'show' => false
            ),
            'last_name' => array(
                'label' => __('sobrenome', 'woocommerce'),
                'show' => false
            ),
            'company' => array(
                'label' => __('Empresa', 'woocommerce'),
                'show' => false
            ),
            'address_1' => array(
                'label' => __('Endereço 1', 'woocommerce'),
                'show' => false
            ),
            'address_2' => array(
                'label' => __('Endereço 2', 'woocommerce'),
                'show' => false
            ),
            'city' => array(
                'label' => __('Cidade', 'woocommerce'),
                'show' => false
            ),
            'postcode' => array(
                'label' => __('CEP', 'woocommerce'),
                'show' => false
            ),
            'country' => array(
                'label' => __('País', 'woocommerce'),
                'show' => false,
                'class' => 'js_field-country wc-enhanced-select short',
                'type' => 'select',
                'options' => array('' => __('Select a country&hellip;', 'woocommerce')) + WC()->countries->get_allowed_countries()
            ),
            'state' => array(
                'label' => __('Cidade/País', 'woocommerce'),
                'class' => 'js_field-state select short',
                'show' => false
            ),
            'email' => array(
                'label' => __('Email', 'woocommerce'),
            ),
            'phone' => array(
                'label' => __('Telefone', 'woocommerce'),
            ),
        ));


        $this->shipping_fields = apply_filters('woocommerce_admin_shipping_fields', array(
            'first_name' => array(
                'label' => __('Primeiro nome', 'woocommerce'),
                'show' => false
            ),
            'last_name' => array(
                'label' => __('Sobrenome', 'woocommerce'),
                'show' => false
            ),
            'company' => array(
                'label' => __('Empresa', 'woocommerce'),
                'show' => false
            ),
            'address_1' => array(
                'label' => __('Endereço 1', 'woocommerce'),
                'show' => false
            ),
            'address_2' => array(
                'label' => __('Endereço 2', 'woocommerce'),
                'show' => false
            ),
            'city' => array(
                'label' => __('Cidade', 'woocommerce'),
                'show' => false
            ),
            'postcode' => array(
                'label' => __('CEP', 'woocommerce'),
                'show' => false
            ),
            'country' => array(
                'label' => __('País', 'woocommerce'),
                'show' => false,
                'type' => 'select',
                'class' => 'js_field-country wc-enhanced-select short',
                'options' => array('' => __('Select a country&hellip;', 'woocommerce')) + WC()->countries->get_shipping_countries()
            ),
            'state' => array(
                'label' => __('Cidade/País', 'woocommerce'),
                'class' => 'js_field-state select short',
                'show' => false
            ),
        ));
        if (!empty($b_country) || !empty($s_country)) {
            $countries = new WC_Countries();
            $locale = $countries->get_country_locale();
            $state_arr = WC()->countries->get_allowed_country_states();
        }
        if (!empty($b_country)) {

            if (isset($locale[$b_country])) {

                $this->billing_fields = wc_array_overlay($this->billing_fields, $locale[$b_country]);

                // If default country has postcode_before_city switch the fields round.
                // This is only done at this point, not if country changes on checkout.
                if (isset($locale[$b_country]['postcode_before_city'])) {
                    if (isset($this->billing_fields['postcode'])) {
                        $this->billing_fields['postcode']['class'] = array('form-row-wide', 'address-field');

                        $switch_fields = array();

                        foreach ($this->billing_fields as $key => $value) {
                            if ($key == 'city') {
                                // Place postcode before city
                                $switch_fields['postcode'] = '';
                            }
                            $switch_fields[$key] = $value;
                        }

                        $this->billing_fields = $switch_fields;
                    }
                }

                if (isset($state_arr[$b_country]) && !empty($state_arr[$b_country])) {
                    $this->billing_fields['state']['type'] = 'select';
                    $this->billing_fields['state']['class'] = array('form-row-left', 'address-field', 'chosen_select');
                    $this->billing_fields['state']['options'] = isset($state_arr[$b_country]) ? $state_arr[$b_country] : '';
                }

            }
        }
        if (!empty($s_country)) {
            if (isset($locale[$s_country])) {

                $this->shipping_fields = wc_array_overlay($this->shipping_fields, $locale[$s_country]);

                // If default country has postcode_before_city switch the fields round.
                // This is only done at this point, not if country changes on checkout.
                if (isset($locale[$s_country]['postcode_before_city'])) {
                    if (isset($this->shipping_fields['postcode'])) {
                        $this->shipping_fields['postcode']['class'] = array('form-row-wide', 'address-field');

                        $switch_fields = array();

                        foreach ($this->shipping_fields as $key => $value) {
                            if ($key == 'city') {
                                // Place postcode before city
                                $switch_fields['postcode'] = '';
                            }
                            $switch_fields[$key] = $value;
                        }

                        $this->shipping_fields = $switch_fields;
                    }
                }
                if (isset($state_arr[$s_country]) && !empty($state_arr[$s_country])) {
                    $this->shipping_fields['state']['type'] = 'select';
                    $this->shipping_fields['state']['class'] = array('form-row-left', 'address-field', 'chosen_select');
                    $this->shipping_fields['state']['options'] = isset($state_arr[$b_country]) ? $state_arr[$b_country] : '';
                }
            }
        }
    }

    public function get_formatted_billing_address($map = false)
    {

        if (!$this->formatted_billing_address) {

            // Formatted Addresses.
            $address = apply_filters('wc_crm_order_formatted_billing_address', array(
                'first_name' => $this->billing_first_name,
                'last_name' => $this->billing_last_name,
                'company' => $this->billing_company,
                'address_1' => $this->billing_address_1,
                'address_2' => $this->billing_address_2,
                'city' => $this->billing_city,
                'state' => $this->billing_state,
                'postcode' => $this->billing_postcode,
                'country' => $this->billing_country,
                'cpf' => $this->billing_cpf,
                'cnpj' => $this->billing_cpnj

            ), $this);

            $this->formatted_billing_address = WC()->countries->get_formatted_address($address);
        }

        return $this->formatted_billing_address;

    }

    /**
     * Get a formatted shipping address for the order.
     *
     * @return string
     */
    public function get_billing_address_map_address()
    {
        $address = apply_filters('wc_crm_billing_address_map_url_parts', array(
            'address_1' => $this->billing_address_1,
            'address_2' => $this->billing_address_2,
            'city' => $this->billing_city,
            'state' => $this->billing_state,
            'postcode' => $this->billing_postcode,
            'country' => $this->billing_country
        ), $this);

        return apply_filters('get_billing_address_map_address', implode(', ', $address), $this);
    }

    /**
     * Get a formatted shipping address for the order.
     *
     * @return string
     */
    public function get_shipping_address_map_address()
    {
        $address = apply_filters('wc_crm_billing_address_map_url_parts', array(
            'address_1' => $this->shipping_address_1,
            'address_2' => $this->shipping_address_2,
            'city' => $this->shipping_city,
            'state' => $this->shipping_state,
            'postcode' => $this->shipping_postcode,
            'country' => $this->shipping_country
        ), $this);

        return apply_filters('get_billing_address_map_address', implode(', ', $address), $this);
    }

    public function get_formatted_shipping_address()
    {
        if (!$this->formatted_shipping_address) {
            $address = array();
            foreach ($this->shipping_fields as $key => $field) {
                $name_var = 'shipping_' . $key;
                $address[$key] = $this->$name_var;
            }
            $this->formatted_shipping_address = WC()->countries->get_formatted_address($address);

        }
        return $this->formatted_shipping_address;
    }

    public function get_customer_orders()
    {
        require_once('wc_crm_order_list.php');
        $wc_crm_order_list = new WC_Crm_Order_List();
        $wc_crm_order_list->prepare_items();
        $wc_crm_order_list->display();
    }

    public function get_customer_activity()
    {
        require_once('wc_crm_logs.php');
        $logs = new WC_Crm_Logs();
        $logs->prepare_items();
        $logs->display();
    }

    public function get_products_purchased()
    {
        return wcrm_customer_bought_products($this->email, $this->user_id);
    }


    public function get_last_note()
    {
        global $woocommerce, $post;
        $notes = 'No Customer Notes';
        $notes_array = $this->get_customer_notes();
        $count_notes = count($notes_array);
        #print_R($notes_array);
        #die;
        if ($count_notes == 0) return $notes;
        $count_notes--;
        if ($count_notes == 0) {
            $notes = esc_attr($notes_array[0]->comment_content);
        } elseif ($count_notes == 1) {
            $notes = esc_attr($notes_array[0]->comment_content . '<small style="display:block">plus ' . $count_notes . ' other note</small>');
        } else {
            $notes = esc_attr($notes_array[0]->comment_content . '<small style="display:block">plus ' . $count_notes . ' other notes</small>');
        }
        return $notes;
    }

    /**
     * List customer notes (public)
     *
     * @access public
     * @return array
     */
    public function get_customer_notes()
    {
        global $wpdb;
        $notes = array();

        $query = "SELECT * FROM {$wpdb->comments} as comments LEFT JOIN {$wpdb->commentmeta} commentmeta ON (commentmeta.comment_id = comments.comment_ID AND commentmeta.meta_key = 'customer_id') WHERE commentmeta.meta_value = {$this->customer_id} ORDER BY comments.comment_ID DESC";

        $comments = $wpdb->get_results($query);

        foreach ($comments as $comment) {
            $comment->comment_content = make_clickable($comment->comment_content);
            $notes[] = $comment;
        }
        return (array)$notes;

    }

    public function update_groups($group_ids = array())
    {
        if ($this->customer_id <= 0) return false;
        global $wpdb;

        if (is_array($group_ids) && !empty($group_ids)) {
            $groups_array = wc_get_static_groups_ids_array();
            $table = $wpdb->prefix . 'wc_crm_groups_relationships';
            $wpdb->hide_errors();
            $wpdb->query("DELETE FROM $table WHERE c_id = '{$this->customer_id}';");

            foreach ($group_ids as $group_id) {
                if (!in_array($group_id, $groups_array)) continue;

                $data = array(
                    'group_id' => $group_id,
                    'c_id' => $this->customer_id
                );
                $wpdb->insert($table, $data, array('%d', '%d'));
            }
        }
    }

    public function add_note($note = '', $note_type = '')
    {
        if (is_user_logged_in() && current_user_can('manage_woocommerce')) {
            $author = get_user_by('id', get_current_user_id());
            $comment_author = $author->display_name;
            $comment_author_email = $author->user_email;
        } else {
            $comment_author = __('WC_CRM', 'wc_crm');
            $comment_author_email = strtolower(__('WC_CRM', 'wc_crm')) . '@';
            $comment_author_email .= isset($_SERVER['HTTP_HOST']) ? str_replace('www.', '', $_SERVER['HTTP_HOST']) : 'noreply.com';
            $comment_author_email = sanitize_email($comment_author_email);
        }

        $comment_post_ID = 0;
        $comment_author_url = '';
        $comment_content = $note;
        $comment_agent = 'WC_CRM';
        $comment_type = 'customer_note';
        $comment_parent = 0;
        $comment_approved = 1;
        $commentdata = apply_filters('wc_crm_new_customer_note_data', compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_agent', 'comment_type', 'comment_parent', 'comment_approved'));

        $comment_id = wp_insert_comment($commentdata);

        add_comment_meta($comment_id, 'customer_id', $this->customer_id);
        add_comment_meta($comment_id, 'customer_note_type', $note_type);

        if ($comment_id) {
            $mailer = WC()->mailer();
            do_action('wc_crm_send_customer_note_notification', array('comment_id' => $comment_id, 'customer_id' => $this->customer_id, 'note_type' => $_POST['note_type']));
        }

        return $comment_id;
    }
}