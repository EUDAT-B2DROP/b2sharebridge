$(document).ready(function() {

    $('#eudat_b2share input').change(function() {
        var value = 'no';
        if (this.checked) {
            value = 'yes';
        }
        OC.AppConfig.setValue('eudat', $(this).attr('name'), value);
    });
});