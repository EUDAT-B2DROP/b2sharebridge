$(document).ready(function(){
    if ($('#isPublic').val()){
        // no versions actions in public mode
        // beware of https://github.com/owncloud/core/issues/4545
        // as enabling this might hang Chrome
        return;
    }

    if (OCA.Files) {
        // Add versions button to 'files/index.php'
        OCA.Files.fileActions.register('file', 'B2SHARE', OC.PERMISSION_READ,
            function() {
            },
            function(filename, context){
                // Action to perform when clicked
                if (scanFiles.scanning){return;}//workaround to prevent additional http request block scanning feedback

                var file = context.dir.replace(/(?!<=\/)$|\/$/, '/' + filename);
                var createDropDown = true;
                // Check if drop down is already visible for a different file
                if (($('#dropdown').length > 0) ) {
                    if ( $('#dropdown').hasClass('drop-b2drop') && file == $('#dropdown').data('file')) {
                        createDropDown = false;
                    }
                    $('#dropdown').slideUp(OC.menuSpeed);
                    $('#dropdown').remove();
                    $('tr').removeClass('mouseOver');
                }

                if(createDropDown === true) {
                    showDropDown(filename, file, context.fileList);
                }
            }
        ), t('eudat', 'B2SHARE');
    }
});

function showDropDown(filename, files, fileList) {
    var html = '<div id="dropdown" class="drop-b2drop" data-item="'+escapeHTML(files)+'">';
    //html += '<form action="transfer.php">';
    html += '<input id="b2share_token" type="text" value="" autofocus />';
    html += '<input id="b2share_submit" type="submit" value="publish" />';
    //html += '</form>';

    if (filename) {
        fileEl = fileList.findFileEl(filename);
        fileEl.addClass('mouseOver');
        $(html).appendTo(fileEl.find('td.filename'));
    } else {
        $(html).appendTo($('thead .share'));
    }
    $('#dropdown').slideDown(1000);
};

$(this).click(
    function(event) {
        if ($('#dropdown').has(event.target).length === 0 && $('#dropdown').hasClass('drop-b2drop')) {
            $('#dropdown').slideUp(OC.menuSpeed, function() {
                $('#dropdown').remove();
                $('tr').removeClass('mouseOver');
            });
        }
    }
);