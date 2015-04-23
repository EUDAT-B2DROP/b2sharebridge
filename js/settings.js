$(document).ready(function() {
    $('#b2shareEnabled').change(function() {
        var value = 'no';
        if (this.checked) {
            value = 'yes';
            $('#b2shareUrlField').removeClass('hidden');
        } else {
            $('#b2shareUrlField').addClass('hidden');
        }
        OC.AppConfig.setValue('eudat', $(this).attr('name'), value);
    });

    $('#b2shareUrl').change(function() {
        var value = $(this).val()
        var expression = '/^(http[s]?:\/\/(www\.)?|ftp:\/\/(www\.)?|www\.){1}([0-9A-Za-z-\.@:%_\+~#=]+)+((‌​\.[a-zA-Z]{2,3})+)(\/(.)*)?(\?(.)*)?/';
        var regex = new RegExp(expression, 'i');
        if (regex.test(value))
        {
            OC.AppConfig.setValue('eudat', $(this).attr('name'), value)
        }
    });
});