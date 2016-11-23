
(function() {

    var TEMPLATE =
        '<div>' +
        '<div id="b2sharebridgeTabView" class="dialogContainer"><div id="communitySelector"></div><div><input type="button" value="publish" id="publish_button"/></div></div>' +
        '</div>';

        function publishAction(e){
            fileInfo = e.data.param;
            selected_community = $(ddCommunitySelector).val();
            $.post(
                OC.generateUrl('/apps/b2sharebridge/publish'),
                {
                    id: fileInfo.id,
                    community: selected_community
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


        initialize: function() {

            OCA.Files.DetailTabView.prototype.initialize.apply(this, arguments);
            this.collection = new OCA.B2shareBridge.B2shareBridgeCollection();
            this.collection.setObjectType('files');
            this.collection.on('request', this._onRequest, this);
            this.collection.on('sync', this._onEndRequest, this);
            this.collection.on('update', this._onChange, this);
            this.collection.on('error', this._onError, this);


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
                alert('failed');
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
            }
            this.render();
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
            this.delegateEvents();
        },

        /**
         * Returns true for files, false for folders.
         *
         * @return {bool} true for files, false for folders
         */
        canDisplay: function(fileInfo) {
            return true;
        }
    });

    OCA.B2shareBridge = OCA.B2shareBridge || {};

    OCA.B2shareBridge.B2shareBridgeTabView = B2shareBridgeTabView;
})();

