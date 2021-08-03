jQuery(document).ready(function ($) {
    if (typeof postL10n != 'undefined') {
        postL10n.publish = wc_crm_calls.call_save_txt;
        postL10n.publishOn = wc_crm_calls.call_date_txt;
        postL10n.publishOnPast = wc_crm_calls.call_date_txt;
    }

    var callTimer = new (function () {

        // Stopwatch element on the page
        var $stopwatch;

        // Timer speed in milliseconds
        var incrementTime = 60;

        // Current timer position in milliseconds
        var currentTime = 0;

        // Start the timer
        $(function () {
            $stopwatch = $('.display_time');
            callTimer.Timer = $.timer(updateTimer, incrementTime, false);

            /******/
            var d = 0;
            var play = false;
            var pause = false;
            var post_status = $('#post_status').val();
            if (post_status == 'wcrm-current') {
                var cookie = getCookie('wc_crm_current_call');
                var post_id = parseInt($('#post_ID').val());
                if (cookie == '' || post_id <= 0) return 0;

                var call_data = JSON.parse(cookie), play = false;

                if (post_id != call_data.post_id) return 0;

                if (call_data.pause_stamp > 0) {
                    pause = true;
                    call_data.call_stop = call_data.pause_stamp;
                } else {
                    play = true;
                }
                if (call_data.call_stop <= 0) {
                    call_data.call_stop = Math.floor(Date.now() / 1000);
                } else {
                    pause = true;
                    play = false;
                }

                var _duration = call_data.call_stop - call_data.call_start - call_data.pause_duration;

                if (play === true || pause === true) {
                    d = _duration * 1000;
                }
            }
            /******/
            currentTime = d;
            if (play) {
                $('#stop_timer, #pause_timer, #reset_timer').css({'display': 'inline-block'});
                $('#start_timer').hide();
                callTimer.Timer.play();
            } else if (pause) {
                $('#stop_timer, #pause_timer, #reset_timer').css({'display': 'inline-block'});
                $('#start_timer').hide();
                $('#pause_timer').toggleClass('play');
            }
        });

        // Output time and increment
        function updateTimer() {
            //console.log(currentTime);
            formatTimeDuration(currentTime);
            var timeString = formatTime(currentTime);
            $stopwatch.html(timeString);
            currentTime += incrementTime;
        }

        this.Stop = function () {
            var cookie = getCookie('wc_crm_current_call');
            if (cookie != '') {
                var call_data = JSON.parse(cookie);
                var up = false;

                if (call_data.call_stop <= 0) {
                    call_data.call_stop = Math.floor(Date.now() / 1000);
                    up = true;
                }
                if (call_data.pause_stamp > 0) {
                    call_data.call_stop = call_data.pause_stamp;
                    call_data.pause_stamp = 0;
                    up = true;
                }
                if (up === true) {
                    var call_data_str = JSON.stringify(call_data);
                    setCookie('wc_crm_current_call', call_data_str, 1);
                }

                var _duration = call_data.call_stop - call_data.call_start - call_data.pause_duration;
            }

            callTimer.Timer.stop();
            var h = $('#call_duration_h').val();
            var m = $('#call_duration_m').val();
            var s = $('#call_duration_s').val();
            h = h != '' ? parseInt(h) : 0;
            m = m != '' ? parseInt(m) : 0;
            s = s != '' ? parseInt(s) : 0;
            var duration = h > 0 ? h + ' h ' : '';
            duration += m + ' min ';
            duration += s + ' sec ';
            $('#call_duration').html(duration);
            $('#pause_timer').removeClass('play').hide();
            $('#stop_timer').hide();
        },

            // Reset timer
            this.saveStopwatch = function () {
                var times = formatTimeDuration(currentTime);
                var timeString = formatTime(currentTime);
                $stopwatch.html(timeString);
                $('#call_duration').html(times);
                setCookie('wc_crm_current_call', '', 0);
            };
        this.resetStopwatch = function () {
            currentTime = 0;
            var timeString = formatTime(currentTime);
            $stopwatch.html(timeString);
            callTimer.Timer.stop();

            var cookie = getCookie('wc_crm_current_call');
            if (cookie != '') {
                var call_data = JSON.parse(cookie);
                call_data.call_stop = 0;
                call_data.pause_stamp = 0;
                var call_data_str = JSON.stringify(call_data);
                setCookie('wc_crm_current_call', call_data_str, 1);
            }

            $('#stop_timer, #pause_timer, #reset_timer, #save_timer').hide().removeClass('play');
            $('.completed_call_wrap').hide();
            $('#start_timer').css({'display': 'inline-block'});
        };

    });

    function get_current_call() {
        var post_status = $('#post_status').val();
        if (post_status == 'wcrm-current') {
            var cookie = getCookie('wc_crm_current_call');
            var post_id = parseInt($('#post_ID').val());
            if (cookie == '' || post_id <= 0) return 0;

            var call_data = JSON.parse(cookie), play = false;

            if (post_id != call_data.post_id) return 0;

            if (call_data.pause_stamp > 0) {
                call_data.call_stop = call_data.pause_stamp;
            } else {
                play = true;
            }
            if (call_data.call_stop <= 0) {
                call_data.call_stop = Math.floor(Date.now() / 1000);
            } else {
                play = false;
            }

            var _duration = call_data.call_stop - call_data.call_start - call_data.pause_duration;

            if (play === true) {
                return _duration * 1000;
            }
        }
        return 0;
    }

    $('#start_timer').click(function () {

        var post_author = parseInt($('#post_author').val());
        var post_id = parseInt($('#post_ID').val());
        if (post_author > 0 && post_id > 0) {
            var stamp = Math.floor(Date.now() / 1000);
            var call_data = {
                post_author: post_author,
                post_id: post_id,
                call_start: stamp,
                call_stop: 0,
                pause_duration: 0,
                pause_stamp: 0
            };
            call_data_str = JSON.stringify(call_data);
            setCookie('wc_crm_current_call', call_data_str, 1);
        }
        $('#stop_timer, #pause_timer, #reset_timer').css({'display': 'inline-block'});
        $('#start_timer').hide();
        callTimer.Timer.play();

        $.ajax({
            url: wc_crm_params.ajax_url,
            data: {action: 'wc_crm_update_call_status', status: 'wcrm-current', post_id: post_id},
            type: 'POST',
            success: function (response) {
            }
        });

        return false;
    });
    $('#stop_timer').click(function () {
        callTimer.Stop();
        $('#save_timer').css({'display': 'inline-block'});

        return false;
    });
    $('#pause_timer').click(function () {
        var stamp = Math.floor(Date.now() / 1000);
        var cookie = getCookie('wc_crm_current_call');
        if (cookie != '') {
            var call_data = JSON.parse(cookie);

            if (!$(this).hasClass('play')) {
                call_data.pause_stamp = stamp;
            } else if (call_data.pause_stamp > 0) {
                call_data.pause_duration += stamp - call_data.pause_stamp;
                call_data.pause_stamp = 0;
            }
            var call_data_str = JSON.stringify(call_data);
            setCookie('wc_crm_current_call', call_data_str, 1);
        }

        $(this).toggleClass('play');
        callTimer.Timer.toggle();
        return false;
    });
    $('#save_timer').click(function () {
        callTimer.saveStopwatch();
        $('#post_status').val('wcrm-completed').trigger('change');
        change_post_status($('#post_status').val());
        return false;
    });
    $('#reset_timer').click(function () {
        setCookie('wc_crm_current_call', '', 0);
        callTimer.resetStopwatch();
        $('#call_duration').html('0 min 0 sec');
        return false;
    });
    $('.edit-call-duration').click(function () {
        $(this).hide();
        $('#call-duration-timer').hide();
        $('#call-duration-input').show();
        $('#reset_timer').trigger('click');
        return false;
    });
    $('.save-call-duration').click(function () {
        $('#call-duration-input').hide();
        $('.edit-call-duration').show();
        $('#reset_timer').trigger('click');
        return false;
    });
    $('.cancel-call-duration').click(function () {
        $('#call-duration-input').hide();
        $('.edit-call-duration').show();
        var h = $('#hidden_call_duration_h').val();
        var m = $('#hidden_call_duration_m').val();
        var s = $('#hidden_call_duration_s').val();
        $('#call_duration_h').val(h);
        $('#call_duration_m').val(m);
        $('#call_duration_s').val(s);

        $('#reset_timer').trigger('click');
        return false;
    });

    $('.cancel-post-status').click(function () {
        change_post_status($('#hidden_post_status').val());
    });
    $('.save-post-status').click(function (event) {
        change_post_status($('#post_status').val());
    });
    $('#timestampdiv').find('.save-timestamp').click(function (event) { // crazyhorse - multiple ok cancels
        var attemptedDate, originalDate, currentDate, publishOn, postStatus = $('#post_status'),
            aa = $('#aa').val(),
            mm = $('#mm').val(), jj = $('#jj').val(), hh = $('#hh').val(), mn = $('#mn').val();

        attemptedDate = new Date(aa, mm - 1, jj, hh, mn);
        originalDate = new Date($('#hidden_aa').val(), $('#hidden_mm').val() - 1, $('#hidden_jj').val(), $('#hidden_hh').val(), $('#hidden_mn').val());
        currentDate = new Date($('#cur_aa').val(), $('#cur_mm').val() - 1, $('#cur_jj').val(), $('#cur_hh').val(), $('#cur_mn').val());

        if (attemptedDate > currentDate) {
            postStatus.val('future').trigger('change');
        } else if (postStatus.val() == 'future') {
            postStatus.val('wcrm-current').trigger('change');
        }
        event.preventDefault();
    });

    $('#major-viewing-actions a').click(function () {
        if ($(this).hasClass('disabled')) {
            return false;
        }
    });
    $('#phone_number').change(function () {
        var phone = $(this).val();
        if (phone != '') {
            $('a.place_call').attr('href', 'tel:' + phone).removeClass('disabled');
        } else {
            $('a.place_call').attr('href', '#').addClass('disabled');
        }
    });

    $('#customer_id').change(function () {
        var customer_id = $(this).val();
        if (customer_id != '') {
            var url = wc_crm_calls.customer_url.replace('%d', customer_id);
            $('a.view_customer').attr('href', url).removeClass('disabled');
        } else {
            $('a.view_customer').attr('href', '#').addClass('disabled');
        }
    });

    $('#product').change(function () {
        var product_id = $(this).val();
        if (product_id != '') {
            var url = wc_crm_calls.post_url.replace('%d', product_id);
            $('a.view_product').attr('href', url).removeClass('disabled');
        } else {
            $('a.view_product').attr('href', '#').addClass('disabled');
        }
    });

    $('#order').change(function () {
        var order_id = $(this).val();
        if (order_id != '') {
            var url = wc_crm_calls.post_url.replace('%d', order_id);
            $('a.view_order').attr('href', url).removeClass('disabled');
        } else {
            $('a.view_order').attr('href', '#').addClass('disabled');
        }
    });

    function change_post_status(status) {
        /*if( status != 'wcrm-current' ){
         $('#reset_timer').trigger('click');
         }*/
        var attemptedDate, originalDate, currentDate, publishOn, postStatus = $('#post_status'),
            aa = $('#aa').val(), mm = $('#mm').val(), jj = $('#jj').val(), hh = $('#hh').val(), mn = $('#mn').val();

        attemptedDate = new Date(aa, mm - 1, jj, hh, mn);
        originalDate = new Date($('#hidden_aa').val(), $('#hidden_mm').val() - 1, $('#hidden_jj').val(), $('#hidden_hh').val(), $('#hidden_mn').val());
        currentDate = new Date($('#cur_aa').val(), $('#cur_mm').val() - 1, $('#cur_jj').val(), $('#cur_hh').val(), $('#cur_mn').val());

        if (status == 'future') {
            $('#call_duration, #call-duration-input, .edit-call-duration, .misc-pub-duration').hide();
        } else if (status == 'wcrm-current') {
            $('#call_duration, #call-duration-input, .edit-call-duration').hide();
            $('#call-duration-timer, .misc-pub-duration').show();
            var c_aa = $('#cur_aa').val(), c_mm = $('#cur_mm').val(), c_jj = $('#cur_jj').val(),
                c_hh = $('#cur_hh').val(), c_mn = $('#cur_mn').val();
            $('#aa').val(c_aa);
            $('#mm').val(c_mm);
            $('#jj').val(c_jj);
            $('#hh').val(c_hh);
            $('#mn').val(c_mn);
        } else {
            if (attemptedDate > currentDate) {
                var h_aa = $('#hidden_aa').val(), h_mm = $('#hidden_mm').val(), h_jj = $('#hidden_jj').val(),
                    h_hh = $('#hidden_hh').val(), h_mn = $('#hidden_mn').val();
                $('#aa').val(h_aa);
                $('#mm').val(h_mm);
                $('#jj').val(h_jj);
                $('#hh').val(h_hh);
                $('#mn').val(h_mn);
            }
            $('#call_duration, .edit-call-duration, .misc-pub-duration').show();
            $('#call-duration-timer').hide();

            /*if( typeof callTimer.Timer != 'undefined'){
             $('#reset_timer').trigger('click');
             }*/
        }
    }

    // jQuery('form[name="post"]').on('submit', function () {
    //     if (jQuery('#call_duration_h').val() == 0 && jQuery('#call_duration_m').val() == 0 && jQuery('#call_duration_s').val() == 0) {
    //         alert('Call duration required');
    //         return false;
    //     }
    // });

    jQuery('#dropdown_customers').on('change', function () {
        $.ajax({
            url: wc_crm_params.ajax_url,
            data: {action: 'wc_crm_get_customer_phone', customer_id: jQuery('#dropdown_customers').val()},
            type: 'POST',
            success: function (response) {
                jQuery('#phone_number').val(response);
            }
        });
    })
});

// Common functions
function pad(number, length) {
    var str = '' + number;
    while (str.length < length) {
        str = '0' + str;
    }
    return str;
}
function formatTime(time) {
    time = time / 10;
    var h = parseInt(time / 360000),
        min = parseInt(time / 6000) - (h * 60),
        sec = parseInt(time / 100) - (h * 60 * 60 + min * 60);
    hundredths = pad(time - (sec * 100) - (min * 6000), 2);
    return (h > 0 ? pad(h, 2) : "00") + ":" + ((min > 0 && min < 60) ? pad(min, 2) : "00") + ":" + pad(sec, 2) + ':' + hundredths;
}
function formatTimeDuration(time) {
    time = time / 10;
    var h = parseInt(time / 360000),
        min = parseInt(time / 6000) - (h * 60),
        sec = parseInt(time / 100) - (h * 60 * 60 + min * 60);
    document.getElementById("call_duration_h").value = h;
    document.getElementById("call_duration_m").value = min;
    document.getElementById("call_duration_s").value = sec;
    return h + ' h ' + min + ' min ' + sec + ' sec';
}

function isInt(n) {
    return typeof n === 'number' && n % 1 == 0;
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}
function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1);
        if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
    }
    return "";
}
if (!Date.now) {
    Date.now = function () {
        return new Date().getTime();
    }
}
