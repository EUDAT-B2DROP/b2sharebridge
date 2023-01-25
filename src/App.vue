<template>
  <div id="content" class="app-b2sharebridge">
    <NcAppNavigation>
      <NcAppNavigationNew v-if="!loading"
                          :text="t('b2sharebridge', 'All Deposits')"
                          :disabled="tableStatus === 0"
                          button-id="deposit-all-button"
                          button-class="icon-add"
                          @click="showAllDeposits"/>
      <NcAppNavigationNew v-if="!loading"
                          :text="t('b2sharebridge', 'Pending Deposits')"
                          :disabled="tableStatus === 1"
                          button-id="deposit-pending-button"
                          button-class="icon-add"
                          @click="showPendingDeposits"/>
      <NcAppNavigationNew v-if="!loading"
                          :text="t('b2sharebridge', 'Published Deposits')"
                          :disabled="tableStatus === 2"
                          button-id="deposit-published-button"
                          button-class="icon-add"
                          @click="showPublishedDeposits"/>
      <NcAppNavigationNew v-if="!loading"
                          :text="t('b2sharebridge', 'Failed Deposits')"
                          :disabled="tableStatus === 3"
                          button-id="deposit-failed-button"
                          button-class="icon-add"
                          @click="showFailedDeposits"/>
    </NcAppNavigation>
    <NcAppContent>
      <div v-if="deposits.length === 0">
        <div v-if="tableStatus === 0">
          <div class="icon-file"/>
          <h2 style="text-align: center;">{{ t('b2sharebridge', 'Create a deposit to get started!') }}</h2>
        </div>
        <div v-else-if="tableStatus === 1">
          <div class="icon-file"/>
          <h2 style="text-align: center;">{{ t('b2sharebridge', 'No pending deposits!') }}</h2>
        </div>
        <div v-else-if="tableStatus === 2">
          <div class="icon-file"/>
          <h2 style="text-align: center;">{{ t('b2sharebridge', 'No published deposits!') }}</h2>
        </div>
        <div v-else-if="tableStatus === 3">
          <div class="icon-file"/>
          <h2 style="text-align: center;">{{ t('b2sharebridge', 'No failed deposits!') }}</h2>
        </div>
        <div v-else>
          <div class="icon-file"/>
          <h2 style="text-align: center;">{{ t('b2sharebridge', 'Unknown table status!') }}</h2>
        </div>
      </div>
      <div v-else>
        <b-table id="deposit-table" striped hover
                 :items="deposits"
                 :fields="fields"
                 :sort-by.sync="sortBy"
                 :sort-desc.sync="sortDesc"
                 label-sort-asc=""
                 label-sort-desc=""
                 label-sort-clear=""
                 sort-icon-left>
        </b-table>
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

import 'bootstrap/dist/css/bootstrap.min.css'
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
      sortDesc: true,
      updating: false,
      loading: true,
      tableStatus: 0,
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
      this.tableStatus = 0;
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
      this.tableStatus = 1;
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
      this.tableStatus = 2;
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
      this.tableStatus = 3;
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

.icon-file {
  height: 44px;
}

#deposit-table {
  margin-top: 44px; /*move down due to navigation button being in the way*/
  width: 100%;
  border-top: 0;
  border-bottom: 0;
}

table th td {
  color: white;
  text-align: left;
}

body .table.b-table > tfoot > tr > [aria-sort=none], body .table.b-table > thead > tr > th[aria-sort=none] {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='101' height='101' view-box='0 0 101 101' preserveAspectRatio='none'%3e%3cpath fill='white' opacity='.3' d='M51 1l25 23 24 22H1l25-22z'/%3e%3cpath fill='white' opacity='.3' d='M51 101l25-23 24-22H1l25 22z'/%3e%3c/svg%3e") !important;
}

body .table.b-table > tfoot > tr > [aria-sort=ascending], body .table.b-table > thead > tr > th[aria-sort=ascending] {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='101' height='101' view-box='0 0 101 101' preserveAspectRatio='none'%3e%3cpath fill='white' d='M51 1l25 23 24 22H1l25-22z'/%3e%3cpath fill='white' opacity='.3' d='M51 101l25-23 24-22H1l25 22z'/%3e%3c/svg%3e") !important;
}

body .table.b-table.table-dark > tfoot > tr > [aria-sort=descending], body #app .table.b-table.table-dark > thead > tr > [aria-sort=descending], .table.b-table > .thead-dark > tr > [aria-sort=descending] {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='101' height='101' view-box='0 0 101 101' preserveAspectRatio='none'%3e%3cpath fill='white' opacity='.3' d='M51 1l25 23 24 22H1l25-22z'/%3e%3cpath fill='white' d='M51 101l25-23 24-22H1l25 22z'/%3e%3c/svg%3e") !important;
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