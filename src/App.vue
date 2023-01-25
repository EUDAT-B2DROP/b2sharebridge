<template>
  <div id="content" class="app-b2sharebridge">
    <NcAppNavigation>
      <NcAppNavigationNew v-if="!loading"
                          :text="t('b2sharebridge', 'All DepositsTODORemoveTest')"
                          :disabled="false"
                          button-id="deposit-all-button"
                          button-class="icon-add"
                          @click="showAllDeposits"/>
      <NcAppNavigationNew v-if="!loading"
                          :text="t('b2sharebridge', 'Pending Deposits')"
                          :disabled="false"
                          button-id="deposit-pending-button"
                          button-class="icon-add"
                          @click="showPendingDeposits"/>
      <NcAppNavigationNew v-if="!loading"
                          :text="t('b2sharebridge', 'Published Deposits')"
                          :disabled="false"
                          button-id="deposit-published-button"
                          button-class="icon-add"
                          @click="showPublishedDeposits"/>
      <NcAppNavigationNew v-if="!loading"
                          :text="t('b2sharebridge', 'Failed Deposits')"
                          :disabled="false"
                          button-id="deposit-failed-button"
                          button-class="icon-add"
                          @click="showFailedDeposits"/>
    </NcAppNavigation>
    <NcAppContent>
      <div v-if="deposits.length === 0">
        <div class="icon-file"/>
        <h2 style="text-align: center;">{{ t('b2sharebridge', 'Create a deposit to get started') }}</h2>
      </div>
      <div v-else>
        <b-table id="deposit-table" striped hover :items="deposits" :fields="fields" :sort-by.sync="sortBy"
                 :sort-desc.sync="sortDesc"></b-table>
      </div>
    </NcAppContent>
  </div>
</template>
<script>
import '@nextcloud/dialogs/styles/toast.scss'
import '../css/style.css'
import axios from '@nextcloud/axios'
import {generateUrl} from '@nextcloud/router'
import {showError, showSuccess} from '@nextcloud/dialogs'
import {
  NcActionButton,
  NcAppContent,
  NcAppNavigation,
  NcAppNavigationItem,
  NcAppNavigationNew
} from '@nextcloud/vue'

//import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.min.js'
import 'bootstrap-vue/dist/bootstrap-vue.css'

export default {
  name: 'App',
  components: {
    NcActionButton,
    NcAppContent,
    NcAppNavigation,
    NcAppNavigationItem,
    NcAppNavigationNew,
  },
  data() {
    return {
      deposits: [],
      fields: [],
      sortBy: 'createdAt',
      sortDesc: false,
      updating: false,
      loading: true,
    }
  },
  /**
   * Fetch list of deposits when the component is loaded
   */
  async mounted() {
    try {
      await this.showAllDeposits();
    } catch (e) {
      console.error(e)
      showError(t('b2sharebridge', 'Could not fetch deposits'))
    }
    this.loading = false
  },

  methods: {
    loadDeposits(filter) {
      return axios
          .get(generateUrl('/apps/b2sharebridge/deposits?filter=' + filter))
          .then((response) => {
            console.log(response.data)
            this.deposits = response.data
            this.deposits.forEach(function (value, index, array) {
              array[index] = JSON.parse(value);
            })
          })
          .catch((error) => {
            console.error(error)
            console.error("Could not load deposit List")
          })
    },

    showAllDeposits() {
      this.fields = [
        {key: "status", sortable: true, thClass: "columnWidthInt"},
        {key: "title", sortable: true, thClass: "columnWidthTitle"},
        {key: "createdAt", sortable: true, thClass: "columnWidthDate"},
        {key: "updatedAt", sortable: true, thClass: "columnWidthDate"},
        {key: "url", sortable: true, thStyle: {width: "20%"}},
        {key: "fileCount", sortable: true, thClass: "columnWidthInt"},
        {key: "serverId", sortable: true, thClass: "columnWidthInt"},
      ]
      return this.loadDeposits("all")
    },

    showPendingDeposits() {
      this.fields = [
        {key: "title", sortable: true, thStyle: {width: "50%"}},
        {key: "createdAt", sortable: true, thClass: "columnWidthDate"},
        {key: "updatedAt", sortable: true, thClass: "columnWidthDate"},
        {key: "fileCount", sortable: true, thClass: "columnWidthInt"},
        {key: "serverId", sortable: true, thClass: "columnWidthInt"},
      ]
      return this.loadDeposits("pending")
    },

    showPublishedDeposits() {
      this.fields = [
        {key: "title", sortable: true, thClass: "columnWidthTitle"},
        {key: "createdAt", sortable: true, thClass: "columnWidthDate"},
        {key: "updatedAt", sortable: true, thClass: "columnWidthDate"},
        {key: "url", sortable: true, thStyle: {width: "30%"}},
        {key: "fileCount", sortable: true, thClass: "columnWidthInt"},
        {key: "serverId", sortable: true, thClass: "columnWidthInt"},
      ]
      return this.loadDeposits("published")
    },

    showFailedDeposits() {
      this.fields = [
        {key: "title", sortable: true, thClass: "columnWidthTitle"},
        {key: "createdAt", sortable: true, thClass: "columnWidthDate"},
        {key: "updatedAt", sortable: true, thClass: "columnWidthDate"},
        {key: "fileCount", sortable: true, thClass: "columnWidthInt"},
        {key: "serverId", sortable: true, thClass: "columnWidthInt"},
        {key: "error", sortable: false, thStyle: {width: "30%"}},
      ]
      return this.loadDeposits("failed")
    },
  }
}
</script>
<style scoped>
#app-content > div {
  width: 100%;
  height: 100%;
  padding: 20px;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

#deposit-table {
  width: 100%;
}

input[type='text'] {
  width: 100%;
}

textarea {
  flex-grow: 1;
  width: 100%;
}

.columnWidthInt {
  width: 10%
}

.columnWidthDate {
  width: 15%
}

.columnWidthTitle {
  width: 20%
}
</style>