<template>
  <div class="token_form">
    <h3>{{ name }}</h3>
    <ValidationObserver>
      <b-form @submit.prevent.stop="saveToken">
      <p id="b2shareUrlField">
        <input title="publish_baseurl" type="text"
               v-model="url"
               style="width: 400px" disabled class="publish_url"/>
        <em>External publishing</em>
      </p>
      <p id="b2shareAPITokenField">
        <ValidationProvider v-slot="validationContext"
                            name="token"
                            rules="required|tokenrule"
                            :vid="'prov_'+id"
                            class="validation">
          <b-form-input title="b2share API token" type="text"
                        placeholder="Your API token"
                        v-model="mutable_token"
                        name="b2share_apitoken"
                        style="width: 400px; grid-column: 1"
                        class="form-control"
                        :state="getValidationState(validationContext)"/>
          <em class="validation">B2Share API token</em>
          <b-form-invalid-feedback id="input-1-live-feedback"
                                   style="grid-row: 2">
            {{
              validationContext.errors[0]
            }}
          </b-form-invalid-feedback>
        </ValidationProvider>

      </p>
      <p id="b2shareManageAPIToken">
        <button @click="saveToken" type="submit" :disabled="!canSave()">Save B2SHARE API Token</button>
        <button @click="deleteToken" :disabled="this.token === ''">Delete B2SHARE API Token</button>
      </p>
      </b-form>
    </ValidationObserver>
  </div>
</template>

<script>
import axios from "@nextcloud/axios";
import {generateUrl} from "@nextcloud/router";
import {configure, extend, ValidationObserver, ValidationProvider} from "vee-validate";

import '../../css/style.scss'
import 'bootstrap-vue/dist/bootstrap-vue.css'

extend('tokenrule', {
  validate(value) {
    return {
      required: true,
      valid: value.length === 60,
    }
  },
})

extend('required', {
  validate(value) {
    return {
      required: true,
      valid: ['', null, undefined].indexOf(value) === -1,
    }
  },
  computesRequired: true,
  message: (fieldName) => {
    return `Please enter a valid ${fieldName}`
  },
})

const config = {
  classes: {
    valid: 'is-valid',
    invalid: 'is-invalid'
  },
  bails: true,
  skipOptional: true,
  mode: 'aggressive',
  useConstraintAttrs: true
};
configure(config)

export default {
  name: "TokenEditor",
  components: {
    ValidationObserver,
    ValidationProvider,
  },
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
      mutable_token: this.token,
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
      axios.delete(generateUrl('/apps/b2sharebridge/apitoken/' + this.id))
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
    },

    // VeeValidate
    getValidationState({dirty, validated, valid = null}) {
      return dirty || validated ? valid : null
    },
  }
}
</script>
<style scoped>

.validation-bootstrap-limit {
@import (less) "bootstrap/dist/css/bootstrap.min.css";
}

div.token_form {
  background: rgba(128, 128, 128, 0.1);
  padding: 10px;
  border-radius: 20px;
  margin-top: 2px;
}

input.publish_url {
  background: rgba(128, 128, 128, 0.1);
}

em.validation {
  align-self: center;
  grid-column: 2;
}

span.validation {
  display: grid;
  column-gap: 7px;
  grid-template-columns: 400px;
}

</style>