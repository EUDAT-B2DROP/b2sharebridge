<template>
  <NcAppSidebar>
    <NcAppSidebarTab name="B2Share" id="b2sharebridge-tab">
      <template #icon>
        <mdiCloudUpload :size="20"/>
      </template>
      <div id="b2shareBridgeTabView" class="dialogContainer">
        <table>
          <validation-observer ref="observer" v-slot="{ handleSubmit }">
            <b-form @submit.stop.prevent="publishAction()">
              <tr>
                <td>
                  <validation-provider
                      name="Name"
                      :rules="{ required: true, min: 3 }"
                      v-slot="validationContext"
                  >
                    <b-form-input v-model="deposit_title" id="b2s_title" placeholder="Deposit title"></b-form-input>
                  </validation-provider>
                </td>
              </tr>
              <tr>
                <td>Server:</td>
                <td>
                  <div>
                    <validation-provider
                        name="Name"
                        :rules="{ required: true }"
                        v-slot="validationContext"
                    >
                      <b-form-select v-model="server_selected" :options="server_options"
                                     @change="onChangeServer"></b-form-select>
                    </validation-provider>
                  </div>
                </td>
              </tr>
              <tr>
                <td>Community:</td>
                <td>
                  <validation-provider
                      name="Name"
                      :rules="{ required: true }"
                      v-slot="validationContext"
                  >
                    <b-form-select v-model="community_selected" :options="community_options"></b-form-select>
                  </validation-provider>
                </td>
              </tr>
              <tr>
                <td>Open access:</td>
                <td>
                  <b-form-checkbox v-model="checkbox_status" type="checkbox" name="open_access" id="cbopen_access"/>
                </td>
              </tr>
              <tr>
                <td></td>
                <td>
                  <b-btn variant="outline-primary" type="submit" id="publish_button" @click="publishAction"/>
                </td>
              </tr>
            </b-form>
          </validation-observer>
        </table>
        <div v-if="tokens === null" class="errormsg" id="b2sharebridge_errormsg">Please set your B2SHARE API token <a
            href="/settings/user/b2sharebridge">here</a>
        </div>
      </div>
    </NcAppSidebarTab>
  </NcAppSidebar>
</template>
<script>
import {mdiCloudUpload} from '@mdi/js';
import {
  NcAppSidebar,
  NcAppSidebarTab,
} from '@nextcloud/vue'

export default {
  name: "b2sharebridgeSidebar",
  components: {
    mdiCloudUpload,
    NcAppSidebar,
    NcAppSidebarTab,
  },
  data() {
    return {
      publishEnabled: false,
      communities: [],
      servers: [],
      server_selected: null,
      server_options: [
        {value: null, text: "No servers found!"}
      ],
      community_selected: null,
      community_options: [
        {value: null, text: "No communities found!"},
      ],
      checkbox_status: false,
      deposit_title: "",
      tokens: null,
    }
  },

  async mounted() {
    this.loadServers();
    this.loadCommunities();
    this.servers.forEach();
    if (this.servers !== 0) {
      this.server_options = Array.from(this.servers, function (server) {
        return {value: server.id, text: server.url};
      })
    }
    this.tokens = this.getTokens();
  },

  methods: {
    /**
     *
     * @param e
     */
    publishAction(e) {
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

      $.post(
          OC.generateUrl('/apps/b2sharebridge/publish'),
          {
            ids: ids,
            community: this.community_selected.id,
            open_access: this.checkbox_status,
            title: this.deposit_title,
            server_id: this.server_selected,
          },
          function (result) {
            if (result && result.status === 'success') {
              OC.dialogs.info(
                  t('b2sharebridge', result.message),
                  t('b2sharebridge', 'Info'));
            }
          });
    },
    initialize() {
      OCA.Files.DetailTabView.prototype.initialize.apply(this, arguments);
      this.collection = new OCA.B2shareBridge.B2shareBridgeCollection();
      this.collection.setObjectType('files');
      this.collection.on('request', this._onRequest, this);
      this.collection.on('sync', this._onEndRequest, this);
      this.collection.on('update', this._onChange, this);
      this.collection.on('error', this._onError, this);
    },

    setFileInfo: function (fileInfo) {
      if (fileInfo) {
        this.fileInfo = fileInfo;
        this.initializeB2ShareUI(fileInfo);
        this.render();
        if (this._error_detected)
          this.do_ErrorCallback(this._error_msg)
      }
    },

    setCommunities: function (data) {
      this.communities = data;
    },

    setServers: function (data) {
      this.servers = data;
    },

    //API stuff
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
        success: function (a, b, c) {
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
        success: function (a, b, c) {
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
    //Events

    onChangeServer: function () {
      this.community_options = []
      let community;
      for (community in this.communities) {
        if (community.serverId === this.server_selected.id)
          this.community_options.push({value: community.id, text: community.name});
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
    },

    //VeeValidate
    getValidationState({dirty, validated, valid = null}) {
      return dirty || validated ? valid : null;
    },
  }
}
</script>