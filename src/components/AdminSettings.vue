<template>
  <div class="section" id="admin-settings">
      <h2>EUDAT B2SHARE Bridge</h2>
      <ul>
        <li :key="index" v-if="loaded" v-for="(server, index) in servers">
          <div :id="server.id">
            <p id="maxB2shareUploadsPerUser">
              <input title="max_uploads" type="text" name="max_uploads"
                     id="maxB2shareUploads"
                     placeholder="5" style="width: 400px"
                     :value="server.maxUploads"/>
              <em># of uploads per user at the same time</em>
            </p>
            <p :id="'maxB2shareUploadSizePerFile_' + server.id">
              <input title="max_upload_filesize" type="text" name="max_upload_filesize"
                     id="maxB2shareUploadFilesize"
                     placeholder="512" style="width: 400px"
                     :value="server.maxUploadFilesize"/>
              <em>MB maximum filesize per upload</em>
            </p>
            <p>
              <input type="checkbox" name="check_ssl" id="checkSsl" class="checkbox"
                     value="1" :checked="server.checkSsl">
              <label for="checkSsl">
                {{ t('Check valid secure (https) connections to B2SHARE') }}
              </label>
            </p>
            <p :id="'b2shareUrlField_' + server.id">
              <input title="publish_baseurl" type="text" name="publish_baseurl"
                     :id="'url_' + server.id"
                     placeholder="https://b2share.eudat.eu" style="width: 400px"
                     :value="server.url"/>
              <em>Publish URL</em>
            </p>
            <p :id="'b2shareNameField_' + server.id">
              <input :title="'name_' + server.id" type="text" name="name"
                     :id="'name_' + server.id"
                     style="width: 400px"
                     :value="server.name"/>
              <em>Server name</em>
            </p>
            <button :id="'save_' + server.id">Save</button>
            <button id="'delete_' + server.id">Delete</button>
          </div>
        </li>
      </ul>
      <button id="add-server">Add new server</button>
      <button id="send">Save changes</button>
      <div id="saving"><span class="msg"></span><br/></div>
  </div>
</template>

<script>
import {
  NcAppContent,
} from "@nextcloud/vue";
import axios from "@nextcloud/axios";
import {generateUrl} from "@nextcloud/router";

export default {
  name: "AdminSettings",
  component: {
    NcAppContent
  },
  data() {
    return {
      dummy_server: {
        id: 0,
        name: "Your Server Name",
        publishUrl: "https://trng-b2share.eudat.eu",
        maxUploads: 5,
        maxUploadFilesize: 512,
        checkSsl: false
      },
      servers: [this.dummy_server],
      loaded: false,
    }
  },

  /**
   * Fetch list of servers when the component is loaded
   */
  async mounted() {
    //await this.loadServers()
    this.loaded = true
  },
  methods: {
    loadServers() {
      const url_path = '/apps/b2sharebridge/servers?requesttoken=' + encodeURIComponent(OC.requestToken)

      return axios
          .get(generateUrl(url_path))
          .then((response) => {
            console.log('Loaded servers:')
            console.debug(response)
            this.servers = [this.dummy_server]
            this.servers = this.servers.concat(response.data)
          })
          .catch((error) => {
            console.error('Fetching B2SHARE servers failed!')
            console.error(error)
          })
    },
  }
}
</script>

<style scoped>

</style>