/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/js/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 1);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports) {

module.exports = jQuery;

/***/ }),
/* 1 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./src/templates/template
var TEMPLATE =
    '<div>' +
        '<div id="b2shareBridgeTabView" class="dialogContainer">' +
        '<table><tr><td>Title:</td><td><input type="text" name="b2s_title" id="b2s_title"></input></td></tr>' +
        '<tr><td>Server:</td><td><div id="serverSelector"></div></td></tr>' +
        '<tr><td>Community:</td><td><div id="communitySelector"></div></td></tr>' +
        '<tr><td>Open access:</td><td><input type="checkbox" name="open_access" id="cbopen_access" /></td></tr>' +
        '<tr><td></td><td><input type="button" value="deposit" id="publish_button"/></td></tr></table>' +
        '<div class="errormsg" id="b2sharebridge_errormsg">ERROR3</div>' +
    '</div>';


// EXTERNAL MODULE: external "jQuery"
var external_jQuery_ = __webpack_require__(0);
var external_jQuery_default = /*#__PURE__*/__webpack_require__.n(external_jQuery_);

// CONCATENATED MODULE: ./src/b2sharebridgetabview.js


//import B2shareBridgeCollection from "b2sharebridgecollection.js";

(function () {

    function publishAction(e) {
        external_jQuery_default()("#publish_button").prop('disabled', true);
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
        let selected_community = external_jQuery_default()("#ddCommunitySelector").val();
        let open_access = external_jQuery_default()('input[name="open_access"]:checked').length > 0;
        let title = external_jQuery_default()("#b2s_title").val();
        external_jQuery_default.a.post(
            OC.generateUrl('/apps/b2sharebridge/publish'),
            {
                ids: ids,
                community: selected_community,
                open_access: open_access,
                title: title,
                server_id: external_jQuery_default()('#ddServerSelector').val()
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
                external_jQuery_default.a.ajax({
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
                external_jQuery_default.a.ajax({
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
                    external_jQuery_default.a.ajax({
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
                external_jQuery_default.a.each(
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
                    throw 'Please set B2SHARE API token in <a href="/settings/user/b2sharebridge">B2SHARE settings<a>';
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
                external_jQuery_default.a.ajax({
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



/***/ })
/******/ ]);