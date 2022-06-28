import TEMPLATE from './templates/template';
import $ from "jquery";
//import B2shareBridgeCollection from "b2sharebridgecollection.js";

(function () {

    function publishAction(e) {
        $("#publish_button").prop('disabled', true);
        const selectedFiles = FileList.getSelectedFiles();
        // if selectedFiles is empty, use fileInfo
        // otherwise create an array of files from the selection
        let ids
        let fileInfo
        if (selectedFiles.length > 0) {
            ids = []
            for (let index in selectedFiles) {
                ids.push(selectedFiles[index].id)
            }
        } else {
            fileInfo = e.data.param;
            ids = [fileInfo.id];
        }
        let selected_community = $("#ddCommunitySelector").val();
        let open_access = $('input[name="open_access"]:checked').length > 0;
        let title = $("#b2s_title").val();
        $.post(
            OC.generateUrl('/apps/b2sharebridge/publish'),
            {
                ids: ids,
                community: selected_community,
                open_access: open_access,
                title: title,
                server_id: $('#ddServerSelector').val()
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
            _error_detected: false,

            communities: [],
            servers: [],

            initialize: function () {
                OCA.Files.DetailTabView.prototype.initialize.apply(this, arguments);
                this.collection = new OCA.B2shareBridge.B2shareBridgeCollection();
                this.collection.setObjectType('files');
                this.collection.on('request', this._onRequest, this);
                this.collection.on('sync', this._onEndRequest, this);
                this.collection.on('update', this._onChange, this);
                this.collection.on('error', this._onError, this);
                this._error_msg = "initializing";
                this._b2s_title = "Deposit title here";
                //this.communities = [];
            },

            setFileInfo: function (fileInfo) {
                if (fileInfo) {
                    this.fileInfo = fileInfo;
                    this.initializeB2ShareUI(fileInfo);
                    this.render();
                    if(this._error_detected)
                        this.do_ErrorCallback(this._error_msg)
                }
            },

            //API stuff
            events: {},

            getLabel: function () {
                return t('b2sharebridge', 'B2SHARE');
            },

            getIcon: function () {
                return 'icon-filelist';
            },

            nextPage: function () {
            },

            _onClickShowMoreVersions: function (ev) {
            },

            _onClickRevertVersion: function (ev) {
            },

            _toggleLoading: function (state) {
            },

            _onRequest: function () {
            },

            _onEndRequest: function () {
            },

            _onAddModel: function (model) {
            },

            itemTemplate: function (data) {
            },

            _formatItem: function (version) {
            },

            //Loading

            setCommunities: function (data) {
                this.communities = data;
            },

            setServers: function (data) {
                this.servers = data;
            },

            loadServers: function () {
                const url_path =
                    "/apps/b2sharebridge/servers?requesttoken=" +
                    encodeURIComponent(OC.requestToken);
                let bview = this;
                $.ajax({
                    type: 'GET',
                    url: OC.generateUrl(url_path),
                    async: false,
                    dataType: 'json',
                    success: function (a, b, c)
                    {
                        bview.setServers(a)
                    }
                }).fail(this.createErrorThrow('Fetching B2SHARE servers failed!'));
            },

            loadCommunities: function () {
                const url_path =
                    "/apps/b2sharebridge/gettabviewcontent?requesttoken=" +
                    encodeURIComponent(OC.requestToken);
                let bview = this;
                $.ajax({
                    type: 'GET',
                    url: OC.generateUrl(url_path),
                    async: false,
                    success: function (a, b, c)
                    {
                        bview.setCommunities(a)
                    }
                }).fail(this.createErrorThrow('Fetching B2SHARE communities failed!'));
            },

            getTokens: function () {
                let that = this;
                if (!this.tokens) {
                    const url_path =
                        "/apps/b2sharebridge/apitoken?requesttoken=" +
                        encodeURIComponent(OC.requestToken);
                    $.ajax({
                        type: 'GET',
                        url: OC.generateUrl(url_path),
                        async: false
                    }).done(function (data) {
                        that.tokens = data;
                    }).fail(this.createErrorThrow('Fetching tokens failed!'));
                }
                return this.tokens;
            },

            getCommunities: function () {
                if (!this.communities.length) {
                    this.loadCommunities();
                }
                return this.communities;
            },

            getCommunitySelectorHTML: function () {
                let result = "<select id='ddCommunitySelector'>";
                let $ddserver = this.$el.find('#ddServerSelector');
                $.each(
                    this.getCommunities().filter(function (community) {
                        return community.serverId.toString() === $ddserver.val().toString();
                    }),
                    function (i, c) {
                        result = result + "<option value=\"" + c.id + "\">" + c.name + "</option>";
                    }
                );
                result = result + "</select>";
                return result;
            },

            getServerSelectorHTML: function () {
                let result = "<select id='ddServerSelector' >";
                this.servers.forEach(function (data) {
                    result = result + "<option value=\"" + data.id + "\">" + data.name + "</option>";
                });
                result = result + "</select>";
                return result;
            },

            //Error Handling

            doErrorCallback: function (message) {
                this.$el.find("#b2sharebridge_errormsg").html(message).show();
                this.setPublishButtonDisabled(true);
            },

            createErrorCallback: function(message, obj) {
                function doEC() {
                    return obj.doErrorCallback(message);
                }
                return doEC;
            },

            createErrorThrow: function(message) {
                function doET() {
                    throw message;
                }
                return doET;
            },

            //OTHER

            template: function (data) {
                return TEMPLATE;
            },

            checkToken: function () {
                let b2sharebridge_errormsg = this.$el.find("#b2sharebridge_errormsg")
                if (!this.tokens[this.$el.find('#ddServerSelector').val()]) {
                    throw 'Please set your B2SHARE API token <a href="/settings/user/b2sharebridge">here<a>';
                }
                b2sharebridge_errormsg.hide();
                this.setPublishButtonDisabled(false);

            },

            //Events

            onChangeServer: function () {
                try {
                    this.$el.find("#communitySelector").html(this.getCommunitySelectorHTML());
                    this.checkToken();
                } catch(err) {
                    if(err instanceof Error)
                        throw err;
                    this.doErrorCallback(err);
                }
            },

            setPublishButtonDisabled: function (NotAvailable) {
                this.$el.find("#publish_button").prop('disabled', NotAvailable).show();
            },

            /**
             * Renders this details view
             */
            render: function () {
                // set template
                this.$el.html(this.template());

                try {
                    // load stuff
                    this.loadServers();
                    this.$el.find("#serverSelector").html(this.getServerSelectorHTML());

                    // load communities
                    this.loadCommunities();
                    this.$el.find("#communitySelector").html(this.getCommunitySelectorHTML());

                    // load tokens
                    this.getTokens();

                    // handle events
                    this.$el.find("#serverSelector").change(this.onChangeServer.bind(this));
                    this.$el.find("#publish_button").bind('click', {param: this.fileInfo}, publishAction);
                    this.$el.find("#b2s_title").val(this._b2s_title);
                    this.delegateEvents();

                    // checks
                    this.checkToken();
                } catch(err) {
                    if(err instanceof Error)
                        throw err;
                    this.doErrorCallback(err);
                }
            },

            /**
             * Returns true for files, false for folders.
             *
             * @return {boolean} true for files, false for folders
             */
            canDisplay: function (fileInfo) {
                if (!fileInfo) {
                    return false;
                }
                return !fileInfo.isDirectory();
            },

            processData: function (data) {
                this._error_detected = data['error'];
                this._error_msg = data['error_msg'];
                this._b2s_title = data['title'];
            },

            initializeB2ShareUI: function (fileInfo) {
                const url_path =
                    "/apps/b2sharebridge/initializeb2shareui?requesttoken=" +
                    encodeURIComponent(OC.requestToken) + "&file_id=" +
                    encodeURIComponent(fileInfo.id);
                //var communities = [];
                //var result = "";
                let that = this;
                $.ajax({
                    type: 'GET',
                    url: OC.generateUrl(url_path),
                    async: false
                }).done(this.processData).fail(function () {
                    //if PHP not reachable, disable publish button
                    that._error_detected = true;
                    that._error_msg = "ERROR - Nextcloud server cannot be reached."
                });
            }
        });

    OCA.B2shareBridge = OCA.B2shareBridge || {};

    OCA.B2shareBridge.B2shareBridgeTabView = B2shareBridgeTabView;
})();

