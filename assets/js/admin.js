jQuery(function ($) {
    var customer_states = null;

    function init() {
        if (!( typeof wc_crm_params === 'undefined' || typeof wc_crm_params.countries === 'undefined' )) {
            /* State/Country select boxes */
            customer_states = $.parseJSON(wc_crm_params.countries.replace(/&quot;/g, '"'));
        }
        $('.overlay_media_popup .media-modal-close').click(function (event) {
            $(this).closest('.overlay_media_popup').hide();
            return false;
        });

        $('.open_c_notes').click(open_customer_notes_popup);

        if ($('#recipients').length > 0) {
            jQuery('#recipients').textboxlist({unique: true, bitsOptions: {editable: {addKeys: [188]}}});
        }
        if ($('#woocommerce-customer-notes').length > 0) {
            // Customer notes
            $('#woocommerce-customer-notes').on('click', 'a.add_note_customer', add_customer_note);
            $('#woocommerce-customer-notes').on('click', 'a.delete_customer_note', delete_customer_note);
        }
        $('.customerdelete').click(function (event) {
            $('#delete_customer_popup').show();
            var cid = $(this).data('cid');
            $('#wc_crm_delete_customer_ids').val(cid);
            return false;
        });
        $('#wc_crm_customers_form').submit(function (event) {
            if ($('#bulk-action-selector-top').val() == 'delete' || $('#bulk-action-selector-bottom').val() == 'delete') {
                $('#delete_customer_popup').show();
                var cids = [];
                $('#the-list .check-column input:checked').each(function (index, el) {
                    cids.push($(el).val());
                });
                var ids = cids.join(',');
                console.log(ids);

                $('#wc_crm_delete_customer_ids').val(ids);
                return false;
            }
        });
        $('#delete_customer_form').submit(function (event) {

            if (!$('.delete_customer_input:checked').length) {
                return false;
            }
        });
        if ($('#_billing_country').length > 0) {

            $('.js_field-country').change(change_country);
            $('.js_field-country').trigger('change', [true]);
            $(document.body).on('change', 'select.js_field-state', change_state);

            $('a.edit_address').click(edit_address);
            $('a.billing-same-as-shipping').on('click', copy_billing_to_shipping);
            $('a.load_customer_billing').on('click', load_billing);
            $('a.load_customer_shipping').on('click', load_shipping);

        }
        if ($('#customer_address_map_canvas').length > 0) {
            map_canvas();
        }

        if ($('#customer_data #excerpt').length > 0) {
            $('#customer_data #excerpt').closest('p').remove();
        }
        if ($('#wc_crm_edit_customer_form').length > 0) {
            $(document.body).on('click', '.show_order_items', function () {
                $(this).closest('td').find('table').toggle();
                return false;
            });
            $('#wc_crm_edit_customer_form').submit(function () {
                if ($('#wc_crm_customer_action').length) {
                    var action = $('#wc_crm_customer_action').val();
                    if (action != '') {
                        var url = $('#wc_crm_customer_action option:selected').data('url');
                        window.open(url, '_blank');
                        return false;
                    }
                }
            });
            $("#fav_new_order").on('click', function(e){
                $('#wc_crm_customer_action').val('wc_crm_customer_action_new_order');
            })
        }
        if ($('#date_of_birth').length > 0) {
            $('#date_of_birth').datepicker({
                dateFormat: "yy-mm-dd",
                numberOfMonths: 1,
                showButtonPanel: true,
                changeMonth: true,
                changeYear: true,
                yearRange: '-100y:c+nn',
                maxDate: '-1d'
            });

            $('.handlediv').click(function () {
                $(this).parent().toggleClass('closed');
            });
        }
        if ($('.wc_crm_date').length > 0) {
            $('.wc_crm_date').datepicker({
                dateFormat: "yy-mm-dd",
                numberOfMonths: 1,
                showButtonPanel: true,
                changeMonth: true,
                changeYear: true,
                minDate: '0d'
            });

            $('.handlediv').click(function () {
                $(this).parent().toggleClass('closed');
            });
        }

    }

    init();
    init_tiptip();

    function init_tiptip() {
        $('#tiptip_holder').removeAttr('style');
        $('#tiptip_arrow').removeAttr('style');
        $('.tips, .woocommerce-help-tip').tipTip({
            'attribute': 'data-tip',
            'fadeIn': 50,
            'fadeOut': 50,
            'delay': 200
        });
    }

    function edit_address(e) {
        e.preventDefault();
        $(this).hide();
        $(this).parent().find('a:not(.edit_address)').show();
        $(this).closest('.order_data_column').find('div.address').hide();
        $(this).closest('.order_data_column').find('div.edit_address').show();
    }

    function copy_billing_to_shipping(e) {
        if (window.confirm(wc_crm_params.copy_billing)) {
            $('input#_shipping_first_name').val($('input#_billing_first_name').val()).change();
            $('input#_shipping_last_name').val($('input#_billing_last_name').val()).change();
            $('input#_shipping_company').val($('input#_billing_company').val()).change();
            $('input#_shipping_address_1').val($('input#_billing_address_1').val()).change();
            $('input#_shipping_address_2').val($('input#_billing_address_2').val()).change();
            $('input#_shipping_city').val($('input#_billing_city').val()).change();
            $('input#_shipping_postcode').val($('input#_billing_postcode').val()).change();

            $('#_shipping_country').val($('#_billing_country').val()).change();
            $('#_shipping_state').val($('#_billing_state').val()).change();
        }
        return false;
    }

    function load_billing(force) {
        if (true === force || window.confirm(wc_crm_params.load_billing)) {

            // Get user ID to load data for
            var user_id = parseInt($('#user_id').val());
            var order_id = parseInt($('#order_id').val());

            var data = {
                order_id: order_id,
                type_to_load: 'billing',
                action: 'wc_crm_get_guest_details',
                security: wc_crm_params.get_customer_details_nonce
            };

            if (user_id > 0) {
                var data = {
                    user_id: user_id,
                    type_to_load: 'billing',
                    action: 'woocommerce_get_customer_details',
                    security: wc_crm_params.get_customer_details_nonce
                };
            }

            $(this).closest('div.order_data_column').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            $.ajax({
                url: wc_crm_params.ajax_url,
                data: data,
                type: 'POST',
                success: function (response) {
                    var info = response;

                    if (info) {
                        $('input#_billing_first_name').val(info.billing_first_name).change();
                        $('input#_billing_last_name').val(info.billing_last_name).change();
                        $('input#_billing_company').val(info.billing_company).change();
                        $('input#_billing_address_1').val(info.billing_address_1).change();
                        $('input#_billing_address_2').val(info.billing_address_2).change();
                        $('input#_billing_city').val(info.billing_city).change();
                        $('input#_billing_postcode').val(info.billing_postcode).change();
                        $('input#_billing_cpf').val(info.billing_cpf).change();
                        $('input#_billing_cnpj').val(info.billing_cnpj).change();
                        $('#_billing_country').val(info.billing_country).change();
                        $('#_billing_state').val(info.billing_state).change();
                        $('input#_billing_email').val(info.billing_email).change();
                        $('input#_billing_phone').val(info.billing_phone).change();
                    }

                    $('div.order_data_column').unblock();
                }
            });
        }
        return false;
    }

    function load_shipping(force) {
        if (true === force || window.confirm(wc_crm_params.load_shipping)) {

            // Get user ID to load data for
            var user_id = parseInt($('#user_id').val());
            var order_id = parseInt($('#order_id').val());

            var data = {
                order_id: order_id,
                type_to_load: 'shipping',
                action: 'wc_crm_get_guest_details',
                security: wc_crm_params.get_customer_details_nonce
            };

            if (user_id > 0) {
                var data = {
                    user_id: user_id,
                    type_to_load: 'shipping',
                    action: 'woocommerce_get_customer_details',
                    security: wc_crm_params.get_customer_details_nonce
                };
            }

            $(this).closest('div.order_data_column').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            $.ajax({
                url: wc_crm_params.ajax_url,
                data: data,
                type: 'POST',
                success: function (response) {
                    var info = response;

                    if (info) {
                        $('input#_shipping_first_name').val(info.shipping_first_name).change();
                        $('input#_shipping_last_name').val(info.shipping_last_name).change();
                        $('input#_shipping_company').val(info.shipping_company).change();
                        $('input#_shipping_address_1').val(info.shipping_address_1).change();
                        $('input#_shipping_address_2').val(info.shipping_address_2).change();
                        $('input#_shipping_city').val(info.shipping_city).change();
                        $('input#_shipping_postcode').val(info.shipping_postcode).change();
                        $('#_shipping_country').val(info.shipping_country).change();
                        $('#_shipping_state').val(info.shipping_state).change();
                    }

                    $('div.order_data_column').unblock();
                }
            });
        }
        return false;
    }

    function map_canvas() {
        if (typeof $().gmap != 'undefined') {
            $('#customer_address_map_canvas .canvas').gmap({
                zoom: 14,
                'zoomControl': true,
                'mapTypeControl': false,
                'navigationControl': false,
                'streetViewControl': false
            }).bind('init', function () {
                $('#customer_address_map_canvas .canvas').gmap('search', {'address': wc_pos_customer_formatted_billing_address}, function (results, status) {
                    if (status === 'OK') {
                        $('#customer_address_map_canvas .canvas').gmap('get', 'map').panTo(results[0].geometry.location);

                        $('#customer_address_map_canvas .canvas').gmap(
                            'addMarker', {'position': results[0].geometry.location, 'bounds': false});
                    }
                });
            });
        } else {
            setTimeout(function () {
                acf.fields.google_map.set({$el: jQuery('#customer_address_map_canvas input.search').closest('.acf-google-map')}).edit();

                var _this = acf.fields.google_map;
                var $el = _this.map.$el;

                var address = wc_pos_customer_formatted_billing_address;
                $el.find('.input-address').val(address);
                $el.find('.title h4').text(address);
                _this.geocoder.geocode({'address': address}, function (results, status) {

                    // validate
                    if (status != google.maps.GeocoderStatus.OK) {
                        console.log('Geocoder failed due to: ' + status);
                        return;
                    }

                    if (!results[0]) {
                        console.log('No results found');
                        return;
                    }


                    // get place
                    place = results[0];

                    var lat = place.geometry.location.lat(),
                        lng = place.geometry.location.lng();


                    _this.set({$el: $el}).update(lat, lng).center();

                });


            }, 2000);
        }
    }

    function change_country(e, stickValue) {
        // Check for stickValue before using it
        if (typeof stickValue === 'undefined') {
            stickValue = false;
        }

        // Prevent if we don't have the metabox data
        if (customer_states === null) {
            return;
        }

        var $this = $(this),
            country = $this.val(),
            $state = $this.parents('div.edit_address').find(':input.js_field-state'),
            $parent = $state.parent(),
            input_name = $state.attr('name'),
            input_id = $state.attr('id'),
            value = $this.data('woocommerce.stickState-' + country) ? $this.data('woocommerce.stickState-' + country) : $state.val(),
            placeholder = $state.attr('placeholder');

        if (stickValue) {
            $this.data('woocommerce.stickState-' + country, value);
        }

        // Remove the previous DOM element
        $parent.show().find('.select2-container').remove();

        if (!$.isEmptyObject(customer_states[country])) {
            var $states_select = $('<select name="' + input_name + '" id="' + input_id + '" class="js_field-state select short" placeholder="' + placeholder + '"></select>'),
                state = customer_states[country];

            $states_select.append($('<option value="">' + wc_crm_params.i18n_select_state_text + '</option>'));

            $.each(state, function (index) {
                $states_select.append($('<option value="' + index + '">' + state[index] + '</option>'));
            });

            $states_select.val(value);

            $state.replaceWith($states_select);

            $states_select.show().select2().hide().change();
        } else {
            $state.replaceWith('<input type="text" class="js_field-state" name="' + input_name + '" id="' + input_id + '" value="' + value + '" placeholder="' + placeholder + '" />');
        }

        $(document.body).trigger('contry-change.woocommerce', [country, $(this).closest('div')]);
    }

    function change_state(e) {
        // Here we will find if state value on a select has changed and stick it to the country data
        var $this = $(this),
            state = $this.val(),
            $country = $this.parents('div.edit_address').find(':input.js_field-country'),
            country = $country.val();

        $country.data('woocommerce.stickState-' + country, state);
    }

    function add_customer_note(e) {
        if (!$('textarea#add_order_note').val()) return;

        $('#woocommerce-customer-notes').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
        var data = {
            action: 'wc_crm_add_customer_note',
            customer_id: $('#customer_id').val(),
            note: $('textarea#add_order_note').val(),
            note_type: $('#customer_note_type').val()
        };

        $.post(wc_crm_params.ajax_url, data, function (response) {
            $('ul.order_notes').prepend(response);
            $('#woocommerce-customer-notes').unblock();
            $('#add_order_note').val('');
        });

        return false;
    }

    function delete_customer_note(e) {
        var note = $(this).closest('li');
        $(note).block({
            message: null,
            overlayCSS: {
                background: '#fff url(' + wc_crm_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
                opacity: 0.6
            }
        });

        var data = {
            action: 'wc_crm_delete_customer_note',
            note_id: $(note).attr('rel'),
        };

        $.post(wc_crm_params.ajax_url, data, function (response) {
            $(note).remove();
        });

        return false;
    }

    function open_customer_notes_popup(e) {
        $('#customer_notes_popup iframe').attr('src', '');
        $('#customer_notes_popup > .media-modal').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
        var href = $(this).attr('href');
        $('#customer_notes_popup iframe').attr('src', href);
        $('#customer_notes_popup').show();
        return false;
    }
});

jQuery(document).ready(function () {
    jQuery('#wc_crm_edit_customer_form #first_name').on('change', function () {
        var first_name = jQuery(this).val();
        first_name = first_name.slice(0, 1).toUpperCase() + first_name.slice(1);
        jQuery(this).val(first_name);
        jQuery('#_billing_first_name, #_shipping_first_name').val(first_name);
    });

    jQuery('#wc_crm_edit_customer_form #last_name').on('change', function () {
        var last_name = jQuery(this).val();
        last_name = last_name.slice(0, 1).toUpperCase() + last_name.slice(1);
        jQuery(this).val(last_name);
        jQuery('#_billing_last_name, #_shipping_last_name').val(last_name);
    });

    jQuery('#wc_crm_edit_customer_form #user_email').on('change', function () {
        jQuery('#_billing_email').val(jQuery(this).val());
    });

    jQuery('#account_name').select2({width: '30%'});

    jQuery('.select2-container').css({
        'max-width': '200px'
    });

    if (jQuery('table.customers').length > 0) {
        jQuery('#screen-options-apply').after('<a href="#" id="reset_default" class="button button-primary">'+wc_crm_params.reset_default+'</a>');

        jQuery('#reset_default').on('click', function (e) {
            e.preventDefault();

            jQuery('#customer_status-hide, #customer_name-hide, #email-hide, #customer_notes-hide, #last_purchase-hide, #order_value-hide,#crm_actions-hide').attr('checked', 'checked');
            jQuery('#location-hide, #date_of_birth-hide, #customer_agent-hide').prop('checked', false);

            jQuery('.hide-column-tog').each(function() {


                var $t = jQuery(this), column = $t.val();
                if ( $t.prop('checked') )
                    columns.checked(column);
                else
                    columns.unchecked(column);

            });

            columns.saveManageColumnsState();

        })
    }

    jQuery('.wc-crm-customer-search').select2({
        minimumInputLength: '3',
        ajax: {
            url: wc_crm_params.ajax_url,
            dataType:    'json',
            data: function (params) {
                return {
                    term: params.term,
                    action: 'wc_crm_json_search_customers',
                    security: wc_crm_params.search_customers_nonce
                };
            },
            processResults: function (data) {
                var terms = [];
                if (data) {
                    $.each(data, function (id, text) {
                        console.log(id, text);
                        terms.push({
                            id: id,
                            text: text
                        });
                    });
                }
                return {
                    results: terms
                };
            }
        }
    });

    jQuery('.wc-crm-agent-search').select2({
        minimumInputLength: '3',
        allowClear: true,
        ajax: {
            url: wc_crm_params.ajax_url,
            dataType:    'json',
            data: function (params) {
                return {
                    term: params.term,
                    action: 'wc_crm_json_search_customers',
                    role: 'agent',
                    security: wc_crm_params.search_customers_nonce
                };
            },
            processResults: function (data) {
                var terms = [];
                if (data) {
                    $.each(data, function (id, text) {
                        terms.push({
                            id: id,
                            text: text
                        });
                    });
                }
                return {
                    results: terms
                };
            }
        }
    }).on('select2:unselecting', function (e) {
        var remove = confirm("Are you sure want to remove the agent?");
        if(!remove)
            e.preventDefault();
    });

    var mapCheckbox = $('#wc_crm_enable_google_map');

    mapCheckbox.on('change', function (e) {
        $(this).parents('tr').next('tr').toggle($(this).is(':checked'));
    })
    .parents('tr').next('tr').toggle(mapCheckbox.is(':checked'));

    var placeHolder = $('.file-frame img').attr('href');
    $('body').on('click', '.validation-thickbox', function(e){

        e.preventDefault();

        var source = $(this);

        tb_show(
            source.prop('title'),
            source.prop('href'),
            false
        );

        $('#TB_window').addClass('validation-window')
            .find('#TB_ajaxContent').addClass('validation-container');

        var file = source.attr('data-file'),
            ext = file.substring(file.lastIndexOf('.') + 1),
            valid = ['png', 'jpg', 'jpeg'];

        if($.inArray(ext, valid) != -1){
            imgPath = file;
        }else{
            imgPath = source.children('img').attr('src');
        }

        $('.file-frame img').attr('src', imgPath);
        $('.upload-preview').attr('href', file);
        $("#validation_status").val(source.attr('data-status'));
        $("#post_id").val(source.attr('data-post'));

    }).on('click', '#submitValidation', function () {

        var TB_WINDOW = $('#TB_window');
        var post_id = TB_WINDOW.find('#post_id').val();

        TB_WINDOW.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        var data = {
            url         : ajaxurl,
            data        :  $("#validationForm").find('input, select').serialize(),
            action      : 'update_validation_status',
        };
        $.post(ajaxurl, data, function (response) {
            TB_WINDOW.unblock();

            $('a[data-post=' + post_id + ']').attr('data-status', response.data.status);

            tb_remove();
        });

    }).on('tb_unload', "#TB_window", function () {
        $(this).removeClass('validation-window')
            .children("#TB_ajaxContent").removeClass("validation-container");
    });

    $("#validation_expiry").datepicker({
        dateFormat: "yy-mm-dd"
    });

    $("#watch-button").on('click', function () {
        $("form#wc_crm_edit_customer_form").submit();
    });

});