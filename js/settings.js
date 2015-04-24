$(document).ready(function() {
    $('#b2shareUrl').change(function() {
        var regex = /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/i;
        var value = $(this).val();

        if (regex.exec(value) !== null) {
            document.getElementsByName('regexstatus_success').style.visibility = 'visible';
            document.getElementsByName('regexstatus_error').style.visibility = 'hidden';
            OC.AppConfig.setValue('eudat', $(this).attr('name'), value)
        } else {
            document.getElementsByName('regexstatus_success').style.visibility = 'hidden';
            document.getElementsByName('regexstatus_error').style.visibility = 'visible';
        }
    });
});