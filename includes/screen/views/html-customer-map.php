<?php

if (!defined('ABSPATH')) {
    exit;
}

$is_map_enabled = get_option('wc_crm_enable_google_map', 'no');
if( $is_map_enabled == 'yes')
    $google_map_key = get_option('wc_crm_google_map_api_key');

?>
<div class="postbox " id="woocommerce-customer-maps" style="display: block;">
    <button class="handlediv button-link" aria-expanded="true" type="button">
        <span class="toggle-indicator" aria-hidden="true"></span>
    </button>
    <h3 class="hndle"><span><?php _e('Customer Location', 'wc_crm'); ?></span></h3>
    <div class="inside">
        <?php
        if( $is_map_enabled == 'yes' && !empty($google_map_key)) :
        ?>
            <div id="customer_address_map_canvas">
            <div class="acf-google-map active" data-zoom="14" data-zoom="14" data-lng="144.96328"
                 data-lat="-37.81411" data-id="map-crm">

                <div style="display:none;">
                    <div style="display:none;">
                        <input class="input-address" type="hidden" value="<?php echo isset($address_l) ? $address_l : ''; ?>">
                        <input class="input-lat" type="hidden" value="">
                        <input class="input-lng" type="hidden" value="">
                    </div>
                </div>
                <div class="title" style="display:none;">
                    <div class="no-value">
                        <a title="Find current location" class="acf-sprite-locate ir" href="#">Locate</a>
                        <input type="text" class="search" placeholder="Search for address..."
                               value="<?php echo isset($address_l) ? $address_l : ''; ?>">
                    </div>
                </div>
                <div class="canvas" style="height: 255px"></div>

            </div>
        </div>
        <?php
        else :
            $the_customer->init_address_fields();
            $map_address = get_option('wc_crm_google_map_address', 'billing');

            $address = $map_address == 'billing' ? $the_customer->get_billing_address_map_address() : $the_customer->get_shipping_address_map_address();

        ?>
            <div class="inside-padding">
                <a href="https://www.google.com/maps?q=<?php echo urlencode($address); ?>" target="_blank" class="button"><?php _e('Open in Google Maps', 'wc_crm'); ?></a>
                <?php if( isset($google_map_key) ) : ?>
                    <p style="margin-bottom: 0">
                    <?php echo __("Get a Google Map API key from ", "wc_crm") . '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">'. __("here", "wc_crm") . '</a>'; ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>