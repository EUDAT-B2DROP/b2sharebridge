$(document).ready(function() {
    $('#b2share_enabled').change(function() {
        var value = 'no';
        if (this.checked) {
            value = 'yes';
            $('#b2share_url_field').removeClass('hidden');
        } else {
            $('#b2share_url_field').addClass('hidden');
        }
        OC.AppConfig.setValue('eudat', $(this).attr('name'), value);
    });
});