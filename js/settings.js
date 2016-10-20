$(document).ready(function() {
    $('#b2shareUrl').change(function() {
        var regex = /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/i;
        var value = $(this).val();

        if (regex.exec(value) !== null) {
            OC.AppConfig.setValue('b2sharebridge', $(this).attr('name'), value)
        }
    });
    $('#maxB2shareUploads').change(function() {
        OC.AppConfig.setValue('b2sharebridge', $(this).attr('name'), $(this).val())
    });
    $('#maxB2shareUploadFilesize').change(function() {
        OC.AppConfig.setValue('b2sharebridge', $(this).attr('name'), $(this).val())
    });
    $('#checkSsl').change(function() {
        var value = '0';
        if (this.checked) {
            value = '1';
        }
        OC.AppConfig.setValue('b2sharebridge', $(this).attr('name'), value);
    });
});