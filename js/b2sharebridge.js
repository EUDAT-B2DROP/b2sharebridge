$(document).ready(function(){
    if (OCA.Files) {
        // Add b2share button to 'files/index.php'
        OCA.Files.fileActions.register('file', t('eudat', 'B2SHARE_old'), OC.PERMISSION_READ,
            function() {
            },
            function(filename, context){
                var createDropDown = true;
                // Check if drop down is already visible
                if ($('#dropdown').length > 0) {
                    //is a b2share dropdown visible for the current file?
                    if ( $('#dropdown').hasClass('drop-b2drop') && filename == $('#dropdown').data('file')) {
                        createDropDown = false;
                    }
                    $('#dropdown').slideUp(OC.menuSpeed);
                    $('#dropdown').remove();
                }
                if(createDropDown === true) {
                    showDropDown(filename, context.fileList);
                }
            }
        );
        $(this).click(
            function(event) {
                if ($('#dropdown').has(event.target).length === 0 && $('#dropdown').hasClass('drop-b2drop')) {
                    $('#dropdown').slideUp(OC.menuSpeed);
                    $('#dropdown').remove();
                }
            }
        );
    }
});

function showDropDown(filename, fileList) {
    var html = '<div id="dropdown" class="drop-b2drop" data-item="' + escapeHTML(filename) + '">';
    html += '<a href="https://b2share.eudat.eu/account/settings/applications/" class="b2shareLink" target="_blank">Token:</a>';
    html += '<input id="b2share_token" type="text" value="" autofocus />';
    html += '<input id="b2share_submit" type="submit" value="publish" />';

    if (filename) {
        fileEl = fileList.findFileEl(filename);
        fileEl.addClass('mouseOver');
        $(html).appendTo(fileEl.find('td.filename'));
    }
    $('#dropdown').slideDown(OC.menuSpeed);
};