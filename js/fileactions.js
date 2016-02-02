
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

(function() {

    if (!OCA.B2shareBridge) {
        OCA.B2shareBridge = {};
    }

    OCA.B2shareBridge.Publish = {
        attach:function(fileList) {
            var fileActions = fileList.fileActions;
            fileActions.registerAction({
                name: "B2SHARE",
                displayName: t('files', 'B2SHARE'),
                mime: 'all',
                permissions: OC.PERMISSION_READ,
                icon:OC.imagePath('b2sharebridge', 'filelisticon'),
                actionHandler:function(filename, context) {
                    publishToken = getCookie('publishToken');
                    if (publishToken == '') {
                        OC.dialogs.prompt(
                            t(
                                'b2sharebridge',
                                'You are publishing for the first time during this browser session, please provide the token for B2SHARE and then restart the publish action.'
                            ),
                            t('b2sharebridge', 'B2SHARE API auth token'),
                            function (decision, password) {
                                if (decision) {
                                    document.cookie = "publishToken=" + password;
                                    publishToken = password;
                                }
                            },
                            true,
                            t('b2sharebridge', 'B2SHARE API auth token'),
                            true
                        );
                    }
                    if (publishToken != '') {
                        $.post(OC.generateUrl('/apps/b2sharebridge/publish'), {id: context.$file.data('id'), token: publishToken}, function (result) {
                            if (result && result.status === 'success') {
                                OC.dialogs.info(t('b2sharebridge', result.message), t('b2sharebridge', 'Info'));
                            }
                            else {
                                OC.dialogs.alert(t('b2sharebridge', result.message), t('b2sharebridge', 'Error'));
                            }
                        }
                        );
                    }
                }
            });
        }
    };

})();

OC.Plugins.register('OCA.Files.FileList', OCA.B2shareBridge.Publish);
