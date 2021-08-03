jQuery(function ($) {
       if( $('#group_last_order_to').length > 0 ){
            $( "#group_last_order_to, #group_last_order_from" ).datepicker({
                dateFormat: "yy-mm-dd",
                numberOfMonths: 1,
                showButtonPanel: true,
                changeMonth: true,
                changeYear: true
            });
        }

        if( $('#f_group_type').length > 0){
            $('#f_group_type').change(function(){
                if( $(this).val() == 'dynamic'){
                    $('.dynamic_group_type').show();
                }else{
                    $('.dynamic_group_type').hide();
                }
            }).change();
       }
       if( $('#group_last_order').length > 0){
            $('#group_last_order').change(function(){
                if( $(this).val() == 'between'){
                    $('.group_last_order_between').show();
                }else{
                    $('.group_last_order_between').hide();
                }
            }).change();
       }
});