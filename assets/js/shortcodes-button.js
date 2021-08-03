//Email sorcodes vars. MUST compare with WC_CRM_Screen_Activity::$email_vars
(function () {
    function get_menu(editor, url) {
        var menu = [
            {
                text: 'General',
                menu: [
                    {
                        text: 'customer_first_name',
                        value: '{customer_first_name}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_last_name',
                        value: '{customer_last_name}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_email',
                        value: '{customer_email}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_status',
                        value: '{customer_status}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_account_name',
                        value: '{customer_account_name}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_title',
                        value: '{customer_title}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_department',
                        value: '{customer_department}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_mobile',
                        value: '{customer_mobile}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_fax',
                        value: '{customer_fax}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_website',
                        value: '{customer_website}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_lead_source',
                        value: '{customer_lead_source}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_lead_status',
                        value: '{customer_lead_status}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_industry',
                        value: '{customer_industry}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_agent',
                        value: '{customer_agent}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_date_of_birth',
                        value: '{customer_date_of_birth}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_assistant',
                        value: '{customer_assistant}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_skype',
                        value: '{customer_skype}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_twitter',
                        value: '{customer_twitter}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    }
                ]
            },
            {
                text: 'Billing',
                menu: [
                    {
                        text: 'customer_billing_first_name',
                        value: '{customer_billing_first_name}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_billing_last_name',
                        value: '{customer_billing_last_name}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_billing_company',
                        value: '{customer_billing_company}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_billing_address_1',
                        value: '{customer_billing_address_1}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_billing_address_2',
                        value: '{customer_billing_address_2}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_billing_city',
                        value: '{customer_billing_city}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_billing_postcode',
                        value: '{customer_billing_postcode}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_billing_country',
                        value: '{customer_billing_country}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_billing_state',
                        value: '{customer_billing_state}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_billing_email',
                        value: '{customer_billing_email}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                    },
                    {
                        text: 'customer_billing_cpf',
                        value: '{customer_billing_cpf}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                    },
                    {
                        text: 'customer_billing_cnpj',
                        value: '{customer_billing_cnpj}',
                        onclick: function (e) {
                                e.stopPropagation();
                                editor.insertContent(this.value());
                                }
                    }
                ]
            },
            {
                text: 'Shipping',
                menu: [
                    {
                        text: 'customer_shipping_first_name',
                        value: '{customer_shipping_first_name}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_shipping_last_name',
                        value: '{customer_shipping_last_name}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_shipping_company',
                        value: '{customer_shipping_company}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_shipping_address_1',
                        value: '{customer_shipping_address_1}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_shipping_address_2',
                        value: '{customer_shipping_address_2}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_shipping_city',
                        value: '{customer_shipping_city}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_shipping_postcode',
                        value: '{customer_shipping_postcode}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_shipping_country',
                        value: '{customer_shipping_country}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    },
                    {
                        text: 'customer_shipping_state',
                        value: '{customer_shipping_state}',
                        onclick: function (e) {
                            e.stopPropagation();
                            editor.insertContent(this.value());
                        }
                    }
                ]
            }
        ];
        return menu;
    }

    tinymce.PluginManager.add('wc_crm_shorcodes_button', function (editor, url) {
        editor.addButton('wc_crm_shorcodes_button', {
            title: 'Shortcodes',
            icon: 'icon dashicons dashicons-admin-users',
            type: 'menubutton',
            menu: get_menu(editor, url)
        });
    });
})();