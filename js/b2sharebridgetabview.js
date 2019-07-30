


(function() {

    var TEMPLATE =
        '<div>' +
		'<div id="b2sharebridgeTabView" class="dialogContainer">' +
		'<table><tr><td>Title:</td><td><input type="text" name="b2s_title" id="b2s_title"></input></td></tr>' +
		'<tr><td>Community:</td><td><div id="communitySelector"></div></td></tr>' + 
		'<tr><td>Open access:</td><td><input type="checkbox" name="open_access" id="cbopen_access" /></td></tr>' +
		'<tr><td></td><td><input type="button" value="deposit" id="publish_button"/></td></tr></table>' +
		'<div class="errormsg" id="b2sharebridge_errormsg">ERROR3</div>' +
        '</div>';

        function publishAction(e){
			$(publish_button).prop('disabled', true);	
			selectedFiles = FileList.getSelectedFiles();
			// if selectedFiles is empty, use fileInfo
			// otherwise create an array of files from the selection
			if (selectedFiles.length>0){
				ids = []
				for (index in selectedFiles){
					ids.push(selectedFiles[index].id)
				}
			} else {
            	fileInfo = e.data.param;
				ids = [fileInfo.id];
			}
            selected_community = $(ddCommunitySelector).val();
			open_access = $('input[name="open_access"]:checked').length > 0;
			title = $(b2s_title).val();
            $.post(
                OC.generateUrl('/apps/b2sharebridge/publish'),
                {
                    ids: ids,
                    community: selected_community,
					open_access: open_access,
					title: title
                },
                function (result) {
                    if (result && result.status === 'success') {
                        OC.dialogs.info(
                            t('b2sharebridge', result.message),
                            t('b2sharebridge', 'Info'));
                    }
                });
        }


    /**
     * @class OCA.B2shareBridge.B2shareBridgeTabView
     * @memberof OCA.B2shareBridge
     * @classdesc
     *
     * Shows publication (to b2share) information for file
     *
     */
    var B2shareBridgeTabView = OCA.Files.DetailTabView.extend(
        /** @lends OCA.B2shareBridge.B2shareBridgeTabView.prototype */{
        id: 'b2shareBridgeTabView',
        className: 'b2shareBridgeTabView tab',

        _label: 'b2sharebridge',

        _loading: false,
			
		_publish_buton_disabled: false,


        initialize: function() {

            OCA.Files.DetailTabView.prototype.initialize.apply(this, arguments);
            this.collection = new OCA.B2shareBridge.B2shareBridgeCollection();
            this.collection.setObjectType('files');
            this.collection.on('request', this._onRequest, this);
            this.collection.on('sync', this._onEndRequest, this);
            this.collection.on('update', this._onChange, this);
            this.collection.on('error', this._onError, this);
			this._error_msg = "initializing";
			this._b2s_title = "Deposit title here";

        },

        events: {
        },


        getLabel: function() {
            return t('b2sharebridge', 'B2SHARE');
        },

        getIcon: function() {
           return 'icon-filelist';
        },

        nextPage: function() {
        },

        _onClickShowMoreVersions: function(ev) {
        },

        _onClickRevertVersion: function(ev) {
        },

        _toggleLoading: function(state) {
        },

        _onRequest: function() {
        },

        _onEndRequest: function() {
        },

        _onAddModel: function(model) {
        },

        getCommunitySelectorHTML: function(){
            //TODO implement warning if token not yet set in settings
            var url_path =
                "/apps/b2sharebridge/gettabviewcontent?requesttoken=" +
                encodeURIComponent(oc_requesttoken);
            communities = [];
            result = "";
            $.ajax({
                type: 'GET',
                url: OC.generateUrl(url_path),
                async: false
            }).done(function(data){
                result = "<select id='ddCommunitySelector'>";
                $.each(data, function(key, value){
                    result = result + "<option value=\"" + key + "\">"+ value + "</option>";
                });
                result = result + "</select>"
            }).fail(function(data){
                //TODO: implement unhappy flow
                
            });



            return result;
        }
        ,


        template: function(data) {
            return TEMPLATE;
        },

        itemTemplate: function(data) {
        },

        setFileInfo: function(fileInfo) {
            if (fileInfo){
                this.fileInfo = fileInfo;
                this.initializeB2ShareUI(fileInfo);
                this.render();
            }
        },

        _formatItem: function(version) {
        },

        /**
         * Renders this details view
         */
        render: function() {
            this.$el.html(this.template());
            $(communitySelector).html(this.getCommunitySelectorHTML());
            $(publish_button).bind('click',{param: this.fileInfo}, publishAction);
			$(publish_button).prop('disabled', this._publish_button_disabled);
			$(b2s_title).val(this._b2s_title);
            this.delegateEvents();
			$(b2sharebridge_errormsg).html(this._error_msg);
			if (this._error_msg!=""){
				$(b2sharebridge_errormsg).show();
			}
        },

        /**
         * Returns true for files, false for folders.
         *
         * @return {bool} true for files, false for folders
         */
        canDisplay: function(fileInfo) {
            if (!fileInfo) {
                return false;
            }
            return !fileInfo.isDirectory();
        },
	
		processData: function(data){
			this._publish_button_disabled = data['error'];
			this._error_msg = data['error_msg'];
			this._b2s_title = data['title'];
		},
		
		initializeB2ShareUI: function(fileInfo){
        var url_path =
            "/apps/b2sharebridge/initializeb2shareui?requesttoken=" +
            encodeURIComponent(oc_requesttoken) + "&file_id=" +
			encodeURIComponent(fileInfo.id);
        communities = [];
        result = "";
		that = this;
        $.ajax({
            type: 'GET',
            url: OC.generateUrl(url_path),
            async: false
        }).done(function(data){
        	that.processData(data);
        }).fail(function(data){
            //if PHP not reachable, disable publish button
			that._publish_button_disabled = true;
			that._error_msg = "ERROR - Nextcloud server cannot be reached."
			
        });
		}
    });

	

    OCA.B2shareBridge = OCA.B2shareBridge || {};

    OCA.B2shareBridge.B2shareBridgeTabView = B2shareBridgeTabView;
})();

