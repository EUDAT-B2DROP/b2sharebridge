$(document).ready(function() {
    $('#b2shareUrl').change(function() {
        var regex = /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/i;
        var value = $(this).val();

        if (regex.exec(value) !== null) {
            /*document.getElementById('regexstatusSuccess').style.visibility = 'visible';
            document.getElementById('regexstatusError').style.visibility = 'hidden';*/
            OC.AppConfig.setValue('eudat', $(this).attr('name'), value)
        } /*else {
            document.getElementById('regexstatusSuccess').style.visibility = 'hidden';
            document.getElementById('regexstatusError').style.visibility = 'visible';
        }*/
    });
});