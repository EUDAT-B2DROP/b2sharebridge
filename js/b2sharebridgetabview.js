
(function() {
    var TEMPLATE =
        '<div>' +
        '<div class="dialogContainer"></div>' +
        '</div>';
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

        initialize: function() {
            OCA.Files.DetailTabView.prototype.initialize.apply(this, arguments);
            this.collection = new OCA.Versions.VersionCollection();
            this.collection.on('request', this._onRequest, this);
            this.collection.on('sync', this._onEndRequest, this);
            this.collection.on('update', this._onUpdate, this);
            this.collection.on('error', this._onError, this);
            this.collection.on('add', this._onAddModel, this);
        },

        getLabel: function() {
            return t('b2sharebridge', 'B2shareBridge');
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

        template: function(data) {
        },

        itemTemplate: function(data) {
        },

        setFileInfo: function(fileInfo) {
        },

        _formatItem: function(version) {
        },

        /**
         * Renders this details view
         */
        render: function() {
        },

        /**
         * Returns true for files, false for folders.
         *
         * @return {bool} true for files, false for folders
         */
        canDisplay: function(fileInfo) {
        }
    });


    OCA.B2shareBridge.B2shareBridgeTabView = B2shareBridgeTabView;
})();

