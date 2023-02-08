<template>
  <div class="section" id="eudat_b2share">
    <h2>EUDAT B2SHARE Bridge</h2>
    <div v-if="loaded && servers.length" class="servers">
    <ul>
      <li  v-for="server in servers">
        <TokenEditor :id="parseInt(server.id)"
                     :name="server.name"
                     :url="server.publishUrl"
                     :token="getToken(server.id)"
                     @token-change="updateTokens"/>
      </li>
    </ul>
    </div>
    <div v-if="loaded && servers.length === 0">
      <p>No B2SHARE servers configured! </p>
    </div>
  </div>
</template>

<script>
import axios from "@nextcloud/axios";
import {generateUrl} from "@nextcloud/router";
import TokenEditor from "./TokenEditor.vue";

export default {
  name: "PersonalSettings",
  components: {
    TokenEditor
  },
  data() {
    return {
      servers: [],
      loaded: false,
    }
  },

  /**
   * Fetch list of servers when the component is loaded
   */
  async mounted() {
    let promise = this.loadServers()
    await this.loadTokens()  // ask for both and then wait
    await promise
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
            this.servers = response.data
          })
          .catch((error) => {
            console.error('Fetching B2SHARE servers failed!')
            console.error(error)
          })
    },

    loadTokens() {
      const url_path = '/apps/b2sharebridge/apitoken?requesttoken=' + encodeURIComponent(OC.requestToken)

      return axios
          .get(generateUrl(url_path))
          .then((response) => {
            console.log('Loaded tokens:')
            console.debug(response)
            this.tokens = response.data
          })
          .catch((error) => {
            console.error('Fetching B2SHARE tokens failed!')
            console.error(error)
          })
    },

    getToken(server_id) {
      return this.tokens[server_id] || ""
    },

    updateTokens() {
      this.loadTokens()
      this.$forceUpdate();
    }
  }
}
</script>

<style scoped>
div.servers {
  margin-top: 10px;
  background: rgba(128, 128, 128, 0.1);
  padding: 10px;
  border-radius: 20px;
}
</style>