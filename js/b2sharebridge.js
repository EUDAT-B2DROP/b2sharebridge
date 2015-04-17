/**
* Copyright (c) 2014 Masoud KHorram <usef62@owncloud.com>
* This file is licensed under the Affero General Public License version 3 or
* later.
* See the COPYING-README file.
*/

$(document).ready(function(){
    if (typeof FileActions !== 'undefined') {
        FileActions.register('all',t('eudat','EUDAT'), OC.PERMISSION_READ,
        	function(){
        	},
	        function(filename, context){
			var dir = context.dir || context.fileList.getCurrentDirectory();
			var dir = $('#dir').val();
			if(dir != '/'){
				file = dir+"/"+filename;
			}else{
				file = "/"+filename;
			}
			
			var fileid = context.$file.data('id');
		
			var shareowner = context.$file.attr('data-share-owner');
		
			if(typeof shareowner !== "undefined")
				return false;
			var appendTo = $('tr').filterAttr('data-file',filename).find('td.filename');
            	if (($('#eudatdropdown').length > 0)) {
            	    if (file != $('#eudatdropdown').data('file')) {
            	        OC.Eudat.hideDropDown(function () {
            	            $('tr').removeClass('mouseOver');
            	            $('tr').filterAttr('data-file',filename).addClass('mouseOver');
            	            OC.Eudat.showDropDown(fileid, appendTo);
            	        });
            	    }
            	} else {
            	    OC.Eudat.showDropDown(fileid, appendTo);
            	}
        });
	
    	}
    	$(this).click(function(event) {
        	if (!($(event.target).hasClass('eudatdropdown')) && $(event.target).parents().index($('#eudatdropdown')) == -1) {
        	    if ($('#eudatdropdown').is(':visible')) {
        	        OC.Eudat.hideDropDown(function() {
        	            $('tr').removeClass('mouseOver');
        	        });
        	    }
        	}
    	});
});

OC.Eudat={
    loadCounter:function(fileid) {
        $.ajax({
            type: 'POST',
            url: OC.filePath('eudat', 'ajax', 'getCounter.php'),
            data: {
                fileid: fileid
            },
            success: function(data) {
            $('div#dwcount').text('Publish to https://b2share.eudat.eu');
            }
        });	
    },

    showDropDown:function(fileid, appendTo) {
      OC.Eudat.loadCounter(fileid);
        var html = '<div id="eudatdropdown" class="eudatdropdown" data-item="'+fileid+'">';
        html += '<a>Token:</a>';
        html += '<input autofocus id="b2sharetoken" type="text" value="" />';//autofocus is parameter in html5
        $(html).appendTo(appendTo);
        window.onload = document.getElementById('b2sharetoken').focus();
    },
    hideDropDown:function(callback) {
        $('#eudatdropdown').hide('blind', function() {
            $('#eudatdropdown').remove();
            if (callback) {
                callback.call();
            }
        });
    }
};
