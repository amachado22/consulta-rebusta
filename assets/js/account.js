jQuery('document').ready(function($){

    if( $('a.add_account_note').length ){
        $( '#woocommerce-order-notes' ).on( 'click', 'a.add_account_note', function () {
            if ( ! $( 'textarea#add_order_note' ).val() ) {
                return;
            }

            $( '#woocommerce-order-notes' ).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            var data = {
                action:    'wc_crm_add_account_note',
                post_id:   wc_crm_params.post_id,
                note:      $( 'textarea#add_order_note' ).val(),
                note_type: $( 'select#order_note_type' ).val(),
                security:  wc_crm_params.add_order_note_nonce,
            };

            $.post( wc_crm_params.ajax_url, data, function( response ) {
                $( 'ul.order_notes' ).prepend( response );
                $( '#woocommerce-order-notes' ).unblock();
                $( '#add_order_note' ).val( '' );
            });

            return false;
        });
        $( '#woocommerce-order-notes' ).on( 'click', 'a.delete_note', function() {
            var note = $( this ).closest( 'li.note' );

            $( note ).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            var data = {
                action:   'woocommerce_delete_order_note',
                note_id:  $( note ).attr( 'rel' ),
                security: wc_crm_params.delete_order_note_nonce
            };

            $.post( wc_crm_params.ajax_url, data, function() {
                $( note ).remove();
            });

            return false;
        });
        
    }
});