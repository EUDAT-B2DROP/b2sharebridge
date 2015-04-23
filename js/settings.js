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
        var expression = /[-a-zA-Z0-9@:%_\+.~#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&//=]*)?/gi;
        var regex = new RegExp(expression);
        if (value.match(regex))
        {
            OC.AppConfig.setValue('eudat', $(this).attr('name'), value)
        }
    });
});