jQuery(function ($) {
    var $taskPrioritySelect = $('#task-priority-select'),
        $taskStatusSelect   = $('#post-status-select'),
        $timestampdiv       = $('#due_datediv'),
        stamp               = $('#due_date').html();

    $taskPrioritySelect.siblings('a.edit-task-priority').click( function( event ) {
        if ( $taskPrioritySelect.is( ':hidden' ) ) {
            $taskPrioritySelect.slideDown( 'fast', function() {
                $taskPrioritySelect.find('select').focus();
            } );
            $(this).hide();
        }
        event.preventDefault();
    });

    $taskPrioritySelect.find('.save-task-priority').click( function( event ) {
        $taskPrioritySelect.slideUp( 'fast' ).siblings( 'a.edit-task-priority' ).show().focus();
        updateText();
        event.preventDefault();
    });

    $taskPrioritySelect.find('.cancel-task-priority').click( function( event ) {
        $taskPrioritySelect.slideUp( 'fast' ).siblings( 'a.edit-task-priority' ).show().focus();
        $('#task_priority').val( $('#hidden_task_priority').val() );
        updateText();
        event.preventDefault();
    });

    $timestampdiv.siblings('a.edit-due_date').click( function( event ) {
            if ( $timestampdiv.is( ':hidden' ) ) {
                $timestampdiv.slideDown( 'fast', function() {
                    $( 'input, select', $timestampdiv.find( '.due_date-wrap' ) ).first().focus();
                } );
                $(this).hide();
            }
            event.preventDefault();
        });

    $timestampdiv.find('.cancel-due_date').click( function( event ) {
        $timestampdiv.slideUp('fast').siblings('a.edit-due_date').show().focus();
        $('#due_date_mm').val($('#hidden_due_date_mm').val());
        $('#due_date_jj').val($('#hidden_due_date_jj').val());
        $('#due_date_aa').val($('#hidden_due_date_aa').val());
        $('#due_date_hh').val($('#hidden_due_date_hh').val());
        $('#due_date_mn').val($('#hidden_due_date_mn').val());
        updateText();
        event.preventDefault();
    });


    $timestampdiv.find('.save-due_date').click( function( event ) { // crazyhorse - multiple ok cancels
        if ( updateText() ) {
            $timestampdiv.slideUp('fast');
            $timestampdiv.siblings('a.edit-due_date').show().focus();
        }
        event.preventDefault();
    });

    $taskStatusSelect.siblings('a.edit-task-status').click( function( event ) {
        if ( $taskStatusSelect.is( ':hidden' ) ) {
            $taskStatusSelect.slideDown( 'fast', function() {
                $taskStatusSelect.find('select').focus();
            } );
            $(this).hide();
        }
        event.preventDefault();
    });

    $taskStatusSelect.find('.save-task-status').click( function( event ) {
        $taskStatusSelect.slideUp( 'fast' ).siblings( 'a.edit-task-status' ).show().focus();
        updateText();
        event.preventDefault();
    });

    $taskStatusSelect.find('.cancel-task-status').click( function( event ) {
        $taskStatusSelect.slideUp( 'fast' ).siblings( 'a.edit-task-status' ).show().focus();
        $('#post_status').val( $('#hidden_post_status').val() );
        updateText();
        event.preventDefault();
    });




    $('form#post').submit(function() {
        var errors = 0;
        $('.form-invalid').removeClass('form-invalid');
        if( $('#post_title').length && $('#post_title').val() == '' ){
            $('#post_title').closest('p').addClass('form-invalid');
            errors++;
        }
        if( $('#repeat').length && $('#repeat').is(':checked') ){
            if( $('#srart_date').val() == '' ){
                $('#srart_date').closest('p').addClass('form-invalid');
                errors++;
            }
            if( $('#end_date').val() == '' ){
                $('#end_date').closest('p').addClass('form-invalid');
                errors++;
            }
            var type = $('#repeat_type').val();
            switch (type) {
                case 'daily':
                    if( $('input[name="repeat_daily_type"]:checked').val() == 2 && $('#repeat_daily_days').val() == '' ){
                        $('#repeat_daily_days').closest('label').addClass('form-invalid');
                        errors++;
                    }
                    break;
                case 'weekly':
                    if( $('#repeat_weekly_weeks').val() == '' ){
                        $('#repeat_weekly_weeks').closest('li').addClass('form-invalid');                    
                        errors++;
                    }
                    if( !$('input[name="repeat_weekly_weekdays[]"]:checked').length ){
                        $('input[name="repeat_weekly_weekdays[]"]').closest('ul').addClass('form-invalid');                    
                        errors++;
                    }
                    break;
                case 'monthly':
                    if( $('input[name="repeat_monthly_type"]:checked').val() == 1 && $('#repeat_monthly_noMonths1').val() == '' ){
                        $('#repeat_monthly_noMonths1').closest('span').addClass('form-invalid');                    
                        errors++;
                    }
                    if( $('input[name="repeat_monthly_type"]:checked').val() == 2 && $('#repeat_monthly_noMonths2').val() == '' ){
                        $('#repeat_monthly_noMonths2').closest('span').addClass('form-invalid');                    
                        errors++;
                    }
                    break;
            }
        }
        if( errors > 0 ){
            return false;
        }
    });

    $('#post_title').change(function(event) {
        if( $(this).val() != '' ){
            $(this).closest('p').removeClass('form-invalid');
        }
    });


    $('#wc_crm_task_info #repeat').change(function(event) {
        if( $(this).is(':checked') ){
            $('.show_repeat_options').show();
        }else {
            $('.show_repeat_options').hide();
        }
        
    }).trigger('change');

    $('#wc_crm_task_info #repeat_type').change(function(event) {
        var type = $(this).val();
        $('.show_repeat_type_options').hide();
        if( type != 'none' ){
            $('.show_repeat_'+type+'_options').show();
        }
        
    }).trigger('change');

    function updateText() {
        var attemptedDate, originalDate, currentDate, taskPrioroty = $('#task_priority'), taskStatus = $('#post_status')
            aa = $('#due_date_aa').val(), mm = $('#due_date_mm').val(), jj = $('#due_date_jj').val(), hh = $('#due_date_hh').val(), mn = $('#due_date_mn').val();
        
        $('#post-status-display').html($('option:selected', taskStatus).text());
        $('#task-priority-display').html($('option:selected', taskPrioroty).text());

        if( aa == '' && mm == '01' && jj == '' && hh == '' && mn == ''){
            $('#due_date b').html('-');
            return true;
        }
        attemptedDate = new Date( aa, mm - 1, jj, hh, mn );
        originalDate  = new Date( $('#hidden_due_date_aa').val(), $('#hidden_due_date_mm').val() -1, $('#hidden_due_date_jj').val(), $('#hidden_due_date_hh').val(), $('#hidden_due_date_mn').val() );

        if ( attemptedDate.getFullYear() != aa || (1 + attemptedDate.getMonth()) != mm || attemptedDate.getDate() != jj || attemptedDate.getMinutes() != mn ) {
            $timestampdiv.find('.due_date-wrap').addClass('form-invalid');
            return false;
        } else {
            $timestampdiv.find('.due_date-wrap').removeClass('form-invalid');
        }

        if ( originalDate.toUTCString() == attemptedDate.toUTCString() ) { //hack
            $('#due_date').html(stamp);
        } else {
            $('#due_date b').html(
                postL10n.dateFormat
                    .replace( '%1$s', $( 'option[value="' + mm + '"]', '#due_date_mm' ).attr( 'data-text' ) )
                    .replace( '%2$s', parseInt( jj, 10 ) )
                    .replace( '%3$s', aa )
                    .replace( '%4$s', ( '00' + hh ).slice( -2 ) )
                    .replace( '%5$s', ( '00' + mn ).slice( -2 ) )
                );
        }
        return true;
    }
    
});