$(document).ready(
    function () {
        var baseUrl = OC.generateUrl('/apps/b2sharebridge/servers')
        $('#maxB2shareUploads').change(
            function () {
                OC.AppConfig.setValue('b2sharebridge', $(this).attr('name'), $(this).val())
            }
        );
        $('#maxB2shareUploadFilesize').change(
            function () {
                OC.AppConfig.setValue('b2sharebridge', $(this).attr('name'), $(this).val())
            }
        );
        $('#checkSsl').change(
            function () {
                var value = '0';
                if (this.checked) {
                    value = '1';
                }
                OC.AppConfig.setValue('b2sharebridge', $(this).attr('name'), value);
            }
        );
        function saveChanges() {
            var names = $('[id^="name"]');
            var publishUrls = $('[id^="url"]');
            var data = [];
            for (i=0; i<names.length; i++) {
                data.push({name: names[i].value, publishUrl: publishUrls[i].value})
                id = names[i].id.split("_")[1]
                if (id && id.length) {
                    data[i].id = id;
                }
            }
            OC.msg.startSaving('#saving .msg','Saving servers...');

            $.ajax({
                url: baseUrl,
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({servers: data})
            }).done(function(response) {
                if (response.length) {
                    const id = response[response.length-1].id;
                    $('#b2share_server').prop('id', 'b2share_server_'+id);
                    ['url', 'name','delete'].forEach(function(s) {
                        $('#'+s).prop('id', s+'_'+id);
                    })
                    $('#add-server').show();
                }
                OC.msg.finishedSaving('#saving .msg', {
                    'status': 'success',
                    'data': {
                        'message': 'Saved'
                }});
            }).fail(function(response) {
                OC.msg.finishedSaving('#saving .msg', {
                    'status': 'failure',
                    'data': {
                        'message': 'Saving failed!'
                }});
            })
        }

        function deleteServer(event) {
            const id = event.target.id.split('_')[1];
            if (id) {
                if (!window.confirm("Are you sure you want to delete the server? This action is irreversible.")) {
                    return;
                }
                OC.msg.startSaving('#saving .msg','Deleting...');
                $.ajax({
                    url: baseUrl+'/'+id,
                    type: 'DELETE',
                }).done(function(response) {
                    $('#b2share_server_'+id).remove();
                    OC.msg.finishedSaving('#saving .msg', {
                        'status': 'success',
                        'data': {
                            'message': 'Server deleted'
                    }});
                }).fail(function(response) {
                    OC.msg.finishedSaving('#saving .msg', {
                        'status': 'failure',
                        'data': {
                            'message': 'Deleting failed!'
                    }});
                })
            } else { // no id == server has not been saved yet
                $('#b2share_server').remove();
                $('#add-server').show();
            }
        }

        $('#send').click(function() {saveChanges()});
        $("[id^='#delete_']").click(deleteServer);
        $('#add-server').click(function() {
            $('#add-server').before('<div id="b2share_server"><p id="b2shareUrlField">\
                <input title="publish_baseurl" type="text" name="publish_baseurl"\
                id="url" placeholder="https://b2share.eudat.eu" style="width: 400px"/>\
                   <em>Publish URL</em></p>\
                <p id="b2shareNameField>">\
                <input title="name" type="text" name="name"\
                id="name"\
                style="width: 400px"/>\
                <em>Server name</em></p>\
                <button id="delete">Delete server</button></div>'
            );
        $('#add-server').hide();
        $('#delete').click(deleteServer);
        })
    }
);