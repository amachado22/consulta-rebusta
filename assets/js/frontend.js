(function($) {

    $(function() {

        Dropzone.autoDiscover = false;

        var files = JSON.parse(wc_crm_data.file_types).join();

        var uploader = $("#validation-upload").dropzone({
            url: wc_crm_data.ajax_url,
            autoProcessQueue: false,
            acceptedFiles: files.toString(),
            addRemoveLinks: true,
            parallelUploads : 1,
            maxFilesize: bytesToSize(wc_crm_data.max_upload_size),
            params: {
                action: 'validation_upload'
            }
        });

        if( uploader.length > 0){

            uploader = uploader.get(0).dropzone;

            uploader.on("error", function(file, errorMessage) {
                alert(errorMessage);
                uploader.removeFile(file);
            }).on("success" , function(file, response){

                if( response.success ) {
                    location.reload();
                    return;
                }
                uploader.removeFile(file);
                $("#validation_name").val("");
                alert(response.data);

            }).on("addedfile", function(file){
                // set only one upload at a time
                for( var i=0; i < uploader.files.length; i++ ){

                    if( file === uploader.files[i] ) return;
                    uploader.removeFile(uploader.files[i]);
                }
            });

            $("#submit-validation").on('click', function(){
                var val_name = $("#validation_name");
                var val_type = $("#validation_type");

                uploader.options.params.validation_type = val_type.val();
                uploader.options.params.validation_name = val_name.val();

                if(uploader.getAcceptedFiles().length < 1){
                    alert("Please select a valid file.");
                    return;
                }
                uploader.processQueue();
            });
        }

        $(".validation-actions").on('click', 'a', function (e) {

            var row = $(this).parents('tr'),
                table = row.parents('table');

            if($(this).is('.approve-file')){

                if(!confirm("Are you sure want to delete this file? This cannot be undone.")) return;

                var data = {
                    action: 'remove_validation',
                    post_id: row.find('td').first().text()
                };

                table.block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });

                $.post(wc_crm_data.ajax_url, data, function (response) {

                    row.hide("300", function(){
                        row.remove();
                    });

                    table.unblock();
                });

            }
        });

        var picker = $('#date_of_birth').datepicker({
            dateFormat: "yy-mm-dd",
            numberOfMonths: 1,
            showButtonPanel: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '-100y:c+nn',
            maxDate: '-1d'
        });

    });

    function bytesToSize(bytes) {
        var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        if (bytes == 0) return '0 Byte';
        var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
        return Math.round(bytes / Math.pow(1024, i), 2);
    };

}(jQuery));