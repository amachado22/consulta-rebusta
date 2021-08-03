jQuery(document).ready(function($) {  

    var callTimer = new (function() {

        // Stopwatch element on the page
        var $stopwatch;

        // Timer speed in milliseconds
        var incrementTime = 60;

        // Current timer position in milliseconds
        var currentTime = 0;

        var play  = false;

        // Start the timer
        $(function() {
            $stopwatch = $('#current_call_time_bar');
            callTimer.Timer = $.timer(updateTimer, incrementTime, false);

            /******/
            var d = 0;
            
            var cookie    = getCookie('wc_crm_current_call');
            if( cookie != '' ) {
                    var call_data = JSON.parse(cookie), play = false;

                    if(call_data.pause_stamp > 0 ){
                        call_data.call_stop = call_data.pause_stamp;
                    }else{
                        play = true;
                    }
                    if(call_data.call_stop <= 0 ){
                        call_data.call_stop = Math.floor(Date.now() / 1000);
                    }else{
                        play = false;
                    }

                    var _duration = call_data.call_stop - call_data.call_start - call_data.pause_duration;            

                    if( play === true  ){
                        currentTime = _duration*1000;
                        callTimer.Timer.play();                
                    }

            }

        });
                
            


        // Output time and increment
        function updateTimer() {
            var timeString = formatTime(currentTime);
            $stopwatch.html(timeString);
            currentTime += incrementTime;
        }
    }); 

    
});

// Common functions
function pad(number, length) {
    var str = '' + number;
    while (str.length < length) {str = '0' + str;}
    return str;
}
function formatTime(time) {
    time = time / 10;
    var h   = parseInt(time / 360000),
        min = parseInt(time / 6000) - (h * 60),
        sec = parseInt(time / 100) - (h*60*60+min*60);
        hundredths = pad(time - (sec * 100) - (min * 6000), 2);
    return (h > 0 ? pad(h, 2) : "00") + ":" + ((min > 0 && min < 60) ? pad(min, 2) : "00") + ":" + pad(sec, 2) + ':' + hundredths;
}


function isInt(n) {
   return typeof n === 'number' && n % 1 == 0;
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}
function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
    }
    return "";
}
if (!Date.now) {
    Date.now = function() { return new Date().getTime(); }
}
