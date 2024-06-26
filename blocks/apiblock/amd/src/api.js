define(['jquery'], function($) {
    return {
        init: function() {
            $('#apiButton').on('click', function() {
                $('#apiResponse').html('<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>');
                $.ajax({
                    url: 'https://nm-api.vercel.app/api',
                    type: 'GET',
                    success: function(data) {
                        $('#apiResponse').html('<div class="alert alert-success" role="alert">' + data.message + '</div>');
                    },
                    error: function() {
                        $('#apiResponse').html('<div class="alert alert-danger" role="alert">Error retrieving data.</div>');
                    }
                });
            });
        }
    };
});
