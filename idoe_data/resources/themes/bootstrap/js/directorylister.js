$(document).ready(function() {
    // Hash button on click action
    $('.file-info-button').on("click",function(event) {
        // Get the file name and path
        var name = $(this).closest('li').attr('data-name');
        var path = $(this).closest('li').attr('data-href');

        // Set modal title value
        $('#file-info-modal .modal-title').text(name);

        $('#file-info .md5-hash').text('Loading...');
        $('#file-info .sha1-hash').text('Loading...');
        $('#file-info .filesize').text('Loading...');

        $.ajax({
            url:     '/idoe_data/index.php?hash=' + path,
            type:    'get',
            success: function(data) {

                // Parse the JSON data
                var obj = jQuery.parseJSON(data);


                // Set modal pop-up hash values
                $('#file-info .md5-hash').text(obj.md5);
                $('#file-info .sha1-hash').text(obj.sha1);
                $('#file-info .filesize').text(obj.size);

            }
        });

        // Show the modal
        $('#file-info-modal').modal('show');

        // Prevent default link action
        event.preventDefault();

    });

});

