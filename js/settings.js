$(document).ready(function() {
    $('#b2shareEnabled').change(function() {
        var value = 'no';
        if (this.checked) {
            value = 'yes';
            $('#b2shareUrlField').removeClass('hidden');
        } else {
            $('#b2shareUrlField').addClass('hidden');
        }
/*        OC.AppConfig.setValue('eudat', $(this).attr('name'), value); */
    });
});