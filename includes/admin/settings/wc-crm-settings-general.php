<?php
/**
 * WooCommerce General Settings
 *
 * @author        WooThemes
 * @category    Admin
 * @package    WooCommerce/Admin
 * @version     2.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_Crm_Settings_General')) :

    /**
     * WC_Crm_Settings_General
     */
    class WC_Crm_Settings_General extends WC_Settings_Page
    {

        /**
         * Constructor.
         */
        public function __construct()
        {
            $this->id = 'general_crm';
            $this->label = __('General', 'wc_crm');

            add_filter('wc_crm_settings_tabs_array', array($this, 'add_settings_page'), 20);
            add_action('wc_crm_settings_' . $this->id, array($this, 'output'));
            add_action('wc_crm_settings_save_' . $this->id, array($this, 'save'));

        }

        /**
         * Get settings array
         *
         * @return array
         */
        public function get_settings()
        {
            global $woocommerce, $wp_roles;
            $options = array(
                'user_agents' => __('Agent', 'wc_crm'),
                'user_roles' => __('Funções do usuário', 'wc_crm'),
                'last_order' => __('Último pedido', 'wc_crm'),
                'state' => __('Estado', 'wc_crm'),
                'city' => __('Cidade', 'wc_crm'),
                'country' => __('País', 'wc_crm'),
                'customer_name' => __('Nome do Cliente', 'wc_crm'),
                'products' => __('Produtos', 'wc_crm'),
                'products_variations' => __('Produtos Variados', 'wc_crm'),
                'order_status' => __('Status do pedido', 'wc_crm'),
                'customer_status' => __('Status do Cliente', 'wc_crm'),
                'products_categories' => __('Categorias Produtos', 'wc_crm'));
            if (class_exists('acf')) {
                $acf_options = get_acf_fields_array();
                $options = array_merge($options, $acf_options);
            }
            $statuses = wc_crm_get_statuses_slug();
            $settings = array();
            $filters = array(
                'name' => __('Filters', 'wc_crm'),
                'desc_tip' => 'Escolha quais filtros você gostaria de exibir na página Clientes.',
                'id' => 'wc_crm_filters',
                'class' => 'chosen_select',
                'type' => 'multiselect',
                'options' => $options
            );

            $search_options = array(
                'name' => __('Parâmetros de pesquisa', 'wc_crm'),
                'desc_tip' => 'Defina quais parâmetros do registro de clientes devem ser pesquisados.',
                'id' => 'wc_crm_search_options',
                'class' => 'chosen_select',
                'type' => 'multiselect',
                'options' => array(
                    'billing_first_name' => __('Nome de cobrança', 'wc_crm'),
                    'billing_last_name' => __('Sobrenome', 'wc_crm'),
                    'billing_company' => __('Empresa de cobrança', 'wc_crm'),
                    'billing_address_1' => __('Endereço de cobrança 1', 'wc_crm'),
                    'billing_address_2' => __('Endereço de cobrança 2', 'wc_crm'),
                    'billing_city' => __('Cidade de Faturamento', 'wc_crm'),
                    'billing_postcode' => __('CEP', 'wc_crm'),
                    'billing_country' => __('Pais', 'wc_crm'),
                    'billing_state' => __('Cidade', 'wc_crm'),
                    'billing_email' => __('Email', 'wc_crm'),
                    'billing_phone' => __('Telefone', 'wc_crm'),
                    'billing_cpf' => __('CPF', 'wc_crm'),
                    'billing_cnpj' => __('CPF','wc_crm'),
                    //'order_items' => __('Order items', 'wc_crm'),
                    'shipping_first_name' => __('Nome de envio', 'wc_crm'),
                    'shipping_last_name' => __('Sobrenome', 'wc_crm'),
                    'shipping_company' => __('Empresa de envio', 'wc_crm'),
                    'shipping_address_1' => __('Endereço 1 de envio', 'wc_crm'),
                    'shipping_address_2' => __('Endereço 2 de envio', 'wc_crm'),
                    'shipping_city' => __('Cidade de envio', 'wc_crm'),
                    'shipping_postcode' => __('CEP', 'wc_crm'),
                    'shipping_country' => __('País', 'wc_crm'),
                    'shipping_state' => __('Estado', 'wc_crm'),
                    'first_name' => __('Primeiro nome', 'wc_crm'),
                    'last_name' => __('Sobrenome', 'wc_crm'),
                    'email' => __('Email', 'wc_crm'),
                    'phone' => __('Celular', 'wc_crm'),
                    'fax' => __('Fax', 'wc_crm'),
                    'twitter' => __('Twitter', 'wc_crm'),
                    'skype' => __('Skype', 'wc_crm'),
                    'username' => __('Nome de usuario', 'wc_crm'),
                ),
            );

            if (class_exists('WC_Brands_Admin')) {
                $filters['options']['products_brands'] = __('Marcas de Produtos', 'wc_crm');
            }

            $settings[] = array('title' => __('Opções gerais', 'woocommerce'), 'type' => 'title', 'desc' => '', 'id' => 'general_crm_options');
            $settings[] = array(
                'name' => __('Nome de usuario', 'wc_crm'),
                'desc_tip' => __('Escolha o nome de usuário quando os clientes forem adicionados.', 'wc_crm'),
                'id' => 'wc_crm_username_add_customer',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => array(
                    1 => __('Nome e sobrenome, por exemplo joão maria', 'wc_crm'),
                    2 => __('Separado por hífen, por ex. joao-maria', 'wc_crm'),
                    3 => __('Email', 'wc_crm')
                ),
                'autoload' => true
            );
            $settings[] = array(
                'name' => __('Etiqueta Secundária', 'wc_crm'),
                'desc_tip' => __('Escolha o campo do cliente que deseja que apareça abaixo do nome do cliente na tabela de clientes.', 'wc_crm'),
                'id' => 'wc_crm_username_secondary_label',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'default' => 'username',
                'options' => array(
                    'username' => __('Nome de usuario', 'wc_crm'),
                    'company' => __('Empresa', 'wc_crm'),
                ),
                'autoload' => true
            );
            $settings[] = $filters;
            $settings[] = $search_options;
            $settings[] = array(
                'name' => __('Dinheiro gasto', 'wc_crm'),
                'desc_tip' => __('Escolha quais status os pedidos devem ter antes de contar para o valor do dinheiro gasto.', 'wc_crm'),
                'id' => 'wc_crm_total_value',
                'class' => 'wc-enhanced-select',
                'type' => 'multiselect',
                'options' => wc_get_order_statuses(),
            );
            $settings[] = array(
                'title' => __('Email Automatico', 'wc_crm'),
                'desc' => __('Marque esta caixa para enviar um e-mail com nome de usuário e senha ao criar um novo cliente.', 'wc_crm'),
                'id' => 'wc_crm_automatic_emails_new_customer',
                'default' => 'yes',
                'type' => 'checkbox',
                'checkboxgroup' => 'start'
            );

            $settings[] = array(
                'name' => __('Ação de Email', 'wc_crm'),
                'desc_tip' => __('Escolha como abrir a composição de e-mail na página do cliente.', 'wc_crm'),
                'id' => 'wc_crm_email_composition',
                'class' => 'wc-enhanced-select',
                'default' => 'new',
                'type' => 'select',
                'options' => array(
                    'new' => __('Nova aba ', 'wc_crm'),
                    'existing' => __('Existing Window', 'wc_crm'),
                )
            );

            $settings[] = array(
                'name' => __('Ordem de Ação', 'wc_crm'),
                'desc_tip' => __('Escolha como criar um novo pedido na página do cliente', 'wc_crm'),
                'id' => 'wc_crm_new_order_composition',
                'class' => 'wc-enhanced-select',
                'default' => 'new',
                'type' => 'select',
                'options' => array(
                    'new' => __('Nova aba ', 'wc_crm'),
                    'existing' => __('Existing Window', 'wc_crm'),
                )
            );

            if (class_exists('WC_Subscriptions')) {
                $settings[] = array(
                    'title' => __('Assinantes', 'wc_crm'),
                    'desc' => __('Marque esta caixa para mostrar a coluna que indica se o cliente é um assinante ativo.', 'wc_crm'),
                    'id' => 'wc_crm_show_subscribers_column',
                    'default' => 'no',
                    'type' => 'checkbox',
                    'checkboxgroup' => 'start'
                );
            }
            if (class_exists('Groups_WordPress') && class_exists('Groups_WS')) {
                $settings[] = array(
                    'title' => __('Integração de Grupos', 'wc_crm'),
                    'desc' => __('Marque esta caixa para mostrar a coluna que indica de qual grupo o cliente é membro.', 'wc_crm'),
                    'id' => 'wc_crm_show_groups_wc_column',
                    'default' => 'no',
                    'type' => 'checkbox',
                    'checkboxgroup' => 'start'
                );
            }

            $settings[] = array('type' => 'sectionend', 'id' => 'general_crm_options');
            $settings[] = array('title' => __('Google Maps', 'wc_crm'), 'type' => 'title', 'desc' => __('The following options affects how the Google Maps settings are loaded.', 'wc_crm'), 'id' => 'general_crm_options');

            $settings[] = array(
                'title' => __('Enable Google Maps', 'wc_crm'),
                'desc' => __('Marque esta caixa para ativar o Google Maps.', 'wc_crm'),
                'id' => 'wc_crm_enable_google_map',
                'default' => 'no',
                'type' => 'checkbox'
            );

            $settings[] = array(
                'title' => __('Google Map API', 'wc_crm'),
                'desc' => sprintf(__('Insira sua chave de API do Google Maps aqui, você pode obter uma chave em %shere%s.', 'wc_crm'),
                    '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">', '</a>'),
                'id' => 'wc_crm_google_map_api_key',
                'type' => 'text'
            );

            $settings[] = array(
                'name' => __('Google Map Address', 'wc_crm'),
                'id' => 'wc_crm_google_map_address',
                'desc_tip' => __('Marque esta caixa para enviar um e-mail com nome de usuário e senha ao criar um novo cliente.', 'wc_crm'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'default' => 'billing',
                'options' => array(
                    'billing' => __('Billing', 'wc_crm'),
                    'shipping' => __('Shipping', 'wc_crm'),
                ),
                'autoload' => true
            );

            $settings[] = array('type' => 'sectionend', 'id' => 'general_crm_options');
            $settings[] = array('title' => __('Buscar clientes', 'wc_crm'), 'type' => 'title', 'desc' => __('As opções a seguir afetam como os clientes na tabela de clientes devem ser buscados.', 'wc_crm'), 'id' => 'crm_fetch_customers');
            $settings[] = array(
                'name' => __('Funções do usuário', 'wc_crm'),
                'desc_tip' => 'Escolha quais funções de usuário dos clientes / usuários serão mostradas na tabela de clientes.',
                'id' => 'wc_crm_user_roles',
                'type' => 'multiselect',
                'class' => 'chosen_select',
                'options' => $wp_roles->role_names,
            );
            $settings[] = array(
                'title' => __('Clientes Convidados', 'woocommerce'),
                'desc' => 'Selecione se os clientes convidados aparecem na mesa de clientes.',
                'id' => 'wc_crm_guest_customers',
                'default' => 'no',
                'type' => 'checkbox',
                'checkboxgroup' => 'start'
            );
            $settings[] = array(
                'name' => __('Formato do nome do cliente', 'wc_crm'),
                'desc_tip' => __('Escolha o formato dos nomes exibidos na página Clientes.', 'wc_crm'),
                'id' => 'wc_crm_customer_name',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'default' => 'fl',
                'options' => array(
                    'fl' => __('Primeiro Último', 'wc_crm'),
                    'lf' => __('Último, Primeiro', 'wc_crm'),
                ),
            );

            $settings[] = array(
                'name' => __('Visibilidade do Agente', 'wc_crm'),
                'desc_tip' => __('Escolha quais clientes o agente pode ver.', 'wc_crm'),
                'id' => 'wc_crm_agent_customer_view',
                'class' => 'wc-enhanced-select',
                'default' => 'only_allowed',
                'type' => 'select',
                'options' => array(
                    'all' => __('Todos Clientes', 'wc_crm'),
                    'only_allowed' => __('Apenas clientes atribuídos', 'wc_crm'),
                )
            );
            $settings[] = array('type' => 'sectionend', 'id' => 'crm_fetch_customers');
            $settings[] = array('title' => __('Status padrão', 'wc_crm'), 'type' => 'title', 'desc' => __('As opções a seguir determinam o status padrão para os clientes quando adicionados a este site.', 'wc_crm'), 'id' => 'crm_default_customer_status');
            $settings[] = array(
                'name' => __('Adicionado manualmente', 'wc_crm'),
                'desc_tip' => __('Adicionado manualmente por meio deste plugin.', 'wc_crm'),
                'id' => 'wc_crm_default_status_crm',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'default' => 'Lead',
                'options' => $statuses,
            );
            $settings[] = array(
                'name' => __('Clientes Comprados', 'wc_crm'),
                'desc_tip' => __('Adicionado automaticamente por meio de compras feitas.', 'wc_crm'),
                'id' => 'wc_crm_default_status_store',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'default' => 'Customer',
                'options' => $statuses,
            );

            $settings[] = array(
                'name' => __('Página de registro', 'wc_crm'),
                'desc_tip' => __('Adicionado por meio da página de registro da conta.', 'wc_crm'),
                'id' => 'wc_crm_default_status_account',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'default' => 'Prospect',
                'options' => $statuses,
            );
            $settings[] = array('type' => 'sectionend', 'id' => 'crm_default_customer_status');
            $settings[] = array('title' => __('Página de pedidos', 'wc_crm'), 'type' => 'title','desc' => __('As opções a seguir determinam as opções do cliente disponíveis no gerenciamento de pedidos.', 'wc_crm'), 'id' => 'crm_orders_page');
            $settings[] = array(
                'title' => __('Status do Cliente', 'woocommerce'),
                'desc' => __('Mostrar opção de status do cliente nos detalhes do pedido', 'wc_crm'),
                'desc_tip' => __('Permite que você altere o status do cliente na página de detalhes do pedido.', 'wc_crm'),
                'id' => 'wc_crm_orders_customer',
                'type' => 'checkbox',
                'default' => 'no'
            );
            $settings[] = array(
                'name' => __('Link do cliente', 'wc_crm'),
                'desc_tip' => __('Escolha o link do cliente na página Pedidos, cliente ou perfil de usuário.', 'wc_crm'),
                'id' => 'wc_crm_customer_link',
                'css' => '',
                'std' => '',
                'class' => 'wc-enhanced-select',
                'type' => 'select',
                'options' => array(
                    'customer' => __('Clientes ', 'wc_crm'),
                    'user_profile' => __('Perfil do usuario', 'wc_crm'),
                )
            );
            $settings[] = array('type' => 'sectionend', 'id' => 'crm_orders_page');

            return apply_filters('woocommerce_customer_relationship_general_settings_fields', $settings);

        }

        /**
         * Save settings
         */
        public function save()
        {
            $settings = $this->get_settings();

            WC_CRM_Admin_Settings::save_fields($settings);
        }

    }

endif;

return new WC_Crm_Settings_General();
