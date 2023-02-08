<template>
  <div class="token_form">
    <h3>{{ name }}</h3>
    <p id="b2shareUrlField">
      <input title="publish_baseurl" type="text"
             v-model="url"
             style="width: 400px" disabled class="publish_url"/>
      <em>External publishing</em>
    </p>
    <p id="b2shareAPITokenField">
      <input title="b2share API token" type="text"
             placeholder="Your API token"
             v-model="mutable_token" name="b2share_apitoken"
             style="width: 400px"/>
      <em>B2Share API token</em>
    </p>
    <p id="b2shareManageAPIToken">
      <button @click="saveToken" :disabled="!canSave()">Save B2SHARE API Token</button>
      <button @click="deleteToken" :disabled="this.token === ''">Delete B2SHARE API Token</button>
    </p>
  </div>
</template>

<script>
import axios from "@nextcloud/axios";
import {generateUrl} from "@nextcloud/router";

export default {
  name: "TokenEditor",
  props: {
    id: {required: true, type: Number},
    name: {required: true, type: String},
    url: {required: true, type: String},
    token: {default: "", type: String}
  },
  model: {
    event: 'token-change'
  },
  data() {
    return {
      mutable_token: this.token
    }
  },
  methods: {
    saveToken() {
      let data = {
        requesttoken: OC.requesttoken,
        token: this.mutable_token,
        serverid: this.id,
      }
      axios.post(generateUrl('/apps/b2sharebridge/apitoken'), data)
          .then((response) => {
            console.info("Saved token!")
            this.$emit('token-change', this.id)
          })
          .catch((error) => {
            console.error("Could not save token")
            console.debug(error)
          })
    },

    deleteToken() {
      axios.delete(generateUrl('/apps/b2sharebridge/apitoken/'+ this.id))
          .then((response) => {
            console.info("Deleted token!")
            this.mutable_token = ""
            this.$emit('token-change', this.id)
          })
          .catch((error) => {
            console.error("Could not delete token")
            console.debug(error)
          })
    },

    canSave() {
      return this.mutable_token !== this.token && this.mutable_token !== ""
    }
  }
}
</script>

<style scoped>
div.token_form {
  background: rgba(128, 128, 128, 0.1);
  padding: 10px;
  border-radius: 20px;
  margin-top: 2px;
}

input.publish_url {
  background: rgba(128, 128, 128, 0.1);
}
</style>