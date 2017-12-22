
function saveAPIToken()
{
    OC.msg.startSaving('#lostpassword .msg','Saving API token');
    result = {};
    var post = $("#b2share_apitoken").serializeArray();
    $.ajax(
        {
            type: 'POST',
            url: OC.generateUrl('/apps/b2sharebridge/apitoken'),
            data: {token: post[0].value, requesttoken:oc_requesttoken}
        
        }
    ).done(
        function (result) {
            OC.msg.startAction('#lostpassword .msg', 'Saved!');
        }
    ).fail(
        function (result) {
                OC.msg.startAction('#lostpassword .msg', 'Something went wrong');
        }
    );

}

function deleteAPIToken()
{
    OC.msg.startAction('#lostpassword .msg','Deleting API token');
    $.ajax(
        {
            type: 'DELETE',
            url: OC.generateUrl('/apps/b2sharebridge/apitoken')
        }
    ).done(
        function (result) {
            OC.msg.startAction('#lostpassword .msg', "Deleted");
            $("#b2share_apitoken").val('');
        }
    ).fail(
        function (result) {
                OC.msg.startAction('#lostpassword .msg', result.responseJSON);
        }
    );
}

$(document).ready(
    function () {
        $('#b2share_save_apitoken').click(saveAPIToken);
        $('#b2share_delete_apitoken').click(deleteAPIToken);
    }
);