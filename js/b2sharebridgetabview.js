


(function() {

    var TEMPLATE =
        '<div>' +
        '<div id="b2sharebridgeTabView" class="dialogContainer"><div id="communitySelector"></div>' + 
		'<div><input type="checkbox" name="open_access" id="cbopen_access" />open access</div>' +
		'<div><input type="button" value="publish" id="publish_button"/></div></div>' +
		'<div class="errormsg" id="b2sharebridge_errormsg">ERROR3</div>' +
        '</div>';

        function publishAction(e){
			$(publish_button).prop('disabled', true);
            fileInfo = e.data.param;
            selected_community = $(ddCommunitySelector).val();
			open_access = $('input[name="open_access"]:checked').length > 0;
            $.post(
                OC.generateUrl('/apps/b2sharebridge/publish'),
                {
                    id: fileInfo.id,
                    community: selected_community,
					open_access: open_access
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

        },

        events: {
        },


        getLabel: function() {
            return t('b2sharebridge', 'B2SHARE');
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

            if (!this._template) {
                this._template = Handlebars.compile(TEMPLATE);
            }
            return this._template(data);
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
			that._error_msg = "ERROR - Owncloud server cannot be reached."
			
        });
		}
    });

	

    OCA.B2shareBridge = OCA.B2shareBridge || {};

    OCA.B2shareBridge.B2shareBridgeTabView = B2shareBridgeTabView;
})();

