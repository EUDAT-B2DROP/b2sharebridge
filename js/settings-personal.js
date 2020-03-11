
function saveAPIToken(event)
{
    const server_id = event.target.id.split('_')[3];
    OC.msg.startSaving('#lostpassword_'+server_id + ' .msg','Saving API token');
    result = {};
    const post = $("#b2share_apitoken_"+server_id).serializeArray();
    $.ajax(
        {
            type: 'POST',
            url: OC.generateUrl('/apps/b2sharebridge/apitoken'),
            data: {
                requesttoken: oc_requesttoken,
                token: post[0].value,
                serverid: server_id
            }
        }
    ).done(
        function (result) {
            OC.msg.startAction('#lostpassword_'+server_id + ' .msg', 'Saved!');
        }
    ).fail(
        function (result) {
            OC.msg.startAction('#lostpassword_'+server_id + ' .msg', 'Something went wrong');
        }
    );
}

function deleteAPIToken(event)
{
    const server_id = event.target.id.split('_')[3];
    OC.msg.startAction('#lostpassword_'+server_id + ' .msg','Deleting API token');
    $.ajax(
        {
            type: 'DELETE',
            url: OC.generateUrl('/apps/b2sharebridge/apitoken/'+server_id)
        }
    ).done(
        function (result) {
            OC.msg.startAction('#lostpassword_'+server_id + ' .msg', "Deleted");
            $("#b2share_apitoken_"+server_id).val('');
        }
    ).fail(
        function (result) {
            OC.msg.startAction('#lostpassword_'+server_id + ' .msg', result.responseJSON);
        }
    );
}

$(document).ready(
    function () {
        const saveButtons = $('[id^=b2share_save_apitoken');
        const deleteButtons = $('[id^=b2share_delete_apitoken');
        for (let i=0; i<saveButtons.length; i++) {
            $('#'+saveButtons[i].id).click(saveAPIToken);
            $('#'+deleteButtons[i].id).click(deleteAPIToken);
        }
    }
);