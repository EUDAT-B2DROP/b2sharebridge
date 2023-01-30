<template>
  <div id="content" class="app-b2sharebridge">
    <NcAppNavigation>
      <NcAppNavigationNew v-if="!loading"
                          :text="t('b2sharebridge', 'All Deposits')"
                          :disabled="filter === DepositFilter.ALL"
                          button-id="deposit-all-button"
                          button-class="icon-add"
                          @click="showAllDeposits"/>
      <NcAppNavigationNew v-if="!loading"
                          :text="t('b2sharebridge', 'Pending Deposits')"
                          :disabled="filter === DepositFilter.PENDING"
                          button-id="deposit-pending-button"
                          button-class="icon-add"
                          @click="showPendingDeposits"/>
      <NcAppNavigationNew v-if="!loading"
                          :text="t('b2sharebridge', 'Published Deposits')"
                          :disabled="filter === DepositFilter.PUBLISHED"
                          button-id="deposit-published-button"
                          button-class="icon-add"
                          @click="showPublishedDeposits"/>
      <NcAppNavigationNew v-if="!loading"
                          :text="t('b2sharebridge', 'Failed Deposits')"
                          :disabled="filter === DepositFilter.FAILED"
                          button-id="deposit-failed-button"
                          button-class="icon-add"
                          @click="showFailedDeposits"/>
    </NcAppNavigation>
    <NcAppContent>
      <div v-if="deposits.length === 0">
        <div v-if="filter === DepositFilter.ALL">
          <div class="icon-file"/>
          <h2 style="text-align: center;">{{ t('b2sharebridge', 'Create a deposit to get started!') }}</h2>
        </div>
        <div v-else-if="filter === DepositFilter.PENDING">
          <div class="icon-file"/>
          <h2 style="text-align: center;">{{ t('b2sharebridge', 'No pending deposits!') }}</h2>
        </div>
        <div v-else-if="filter === DepositFilter.PUBLISHED">
          <div class="icon-file"/>
          <h2 style="text-align: center;">{{ t('b2sharebridge', 'No published deposits!') }}</h2>
        </div>
        <div v-else-if="filter === DepositFilter.FAILED">
          <div class="icon-file"/>
          <h2 style="text-align: center;">{{ t('b2sharebridge', 'No failed deposits!') }}</h2>
        </div>
        <div v-else>
          <div class="icon-file"/>
          <h2 style="text-align: center;">{{ t('b2sharebridge', 'Unknown table status!') }}</h2>
        </div>
      </div>
      <div v-else>
        <h2 id="deposit-table-name" style="text-align: center;">{{ getTableName() }}</h2>
        <b-table id="deposit-table" striped hover
                 :items="deposits"
                 :fields="fields"
                 :sort-by.sync="sortBy"
                 :sort-desc.sync="sortDesc"
                 label-sort-asc=""
                 label-sort-desc=""
                 label-sort-clear=""
                 sort-icon-left>
          <template #cell(url)="url_data" class="link-primary">
            <a :href="url_data.value">{{ url_data.value }}</a>
          </template>
        </b-table>
      </div>
    </NcAppContent>
  </div>
</template>
<script>
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
import '@nextcloud/dialogs/styles/toast.scss'
import '../css/style.css'

const DepositFilter = {
  ALL: 'all',
  PENDING: 'pending',
  PUBLISHED: 'published',
  FAILED: 'failed'
};

export default {
  name: 'App',
  components: {
    NcActionButton,
    NcAppContent,
    NcAppNavigation,
    NcAppNavigationItem,
    NcAppNavigationNew,
    DepositFilter,
  },

  data() {
    return {
      deposits: [],
      fields: [],
      sortBy: 'createdAt',
      sortDesc: true,
      updating: false,
      loading: true,
      filter: DepositFilter.ALL,
      timer: null,
      last_deposit_update: null,
      DepositFilter,  //https://stackoverflow.com/questions/57538539/how-to-use-enums-or-const-in-vuejs
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

  beforeDestroy() {  // might need to switch to beforeUnmount in the future
    clearInterval(this.timer)
  },

  methods: {
    loadDeposits(filter) {
      if (this.timer !== null) {
        clearInterval(this.timer);
      }
      this.timer = setInterval(() => {
        this.checkDepositUpdate()
      }, 1000 * 60 * 2)  //every two minutes
      this.last_deposit_update = new Date();
      return axios
          .get(generateUrl('/apps/b2sharebridge/deposits?filter=' + filter))
          .then((response) => {
            console.log(response.data)
            this.deposits = response.data
            this.deposits.forEach((value, index, array) => {
              array[index] = this.translateDepositStatus(JSON.parse(value));
            })
          })
          .catch((error) => {
            console.error(error)
            console.error("Could not load deposit List")
          })
    },

    showAllDeposits() {
      this.filter = DepositFilter.ALL;
      this.fields = [
        {key: "status", sortable: true, thClass: "columnWidthInt"},
        {key: "title", sortable: true, thClass: "columnWidthTitle"},
        {key: "url", sortable: true, thStyle: {width: "20%"}},
        {key: "fileCount", sortable: true, thClass: "columnWidthInt"},
        {key: "serverId", sortable: true, thClass: "columnWidthInt"},
        {key: "createdAt", sortable: true, thClass: "columnWidthDate"},
        {key: "updatedAt", sortable: true, thClass: "columnWidthDate"},
      ]
      return this.loadDeposits(this.filter)
    },

    showPendingDeposits() {
      this.filter = DepositFilter.PENDING;
      this.fields = [
        {key: "title", sortable: true, thStyle: {width: "50%"}},
        {key: "fileCount", sortable: true, thClass: "columnWidthInt"},
        {key: "serverId", sortable: true, thClass: "columnWidthInt"},
        {key: "createdAt", sortable: true, thClass: "columnWidthDate"},
        {key: "updatedAt", sortable: true, thClass: "columnWidthDate"},
      ]
      return this.loadDeposits(this.filter)
    },

    showPublishedDeposits() {
      this.filter = DepositFilter.PUBLISHED;
      this.fields = [
        {key: "title", sortable: true, thClass: "columnWidthTitle"},
        {key: "url", sortable: true, thStyle: {width: "30%"}},
        {key: "fileCount", sortable: true, thClass: "columnWidthInt"},
        {key: "serverId", sortable: true, thClass: "columnWidthInt"},
        {key: "createdAt", sortable: true, thClass: "columnWidthDate"},
        {key: "updatedAt", sortable: true, thClass: "columnWidthDate"},
      ]
      return this.loadDeposits(this.filter)
    },

    showFailedDeposits() {
      this.filter = DepositFilter.FAILED;
      this.fields = [
        {key: "title", sortable: true, thClass: "columnWidthTitle"},
        {key: "fileCount", sortable: true, thClass: "columnWidthInt"},
        {key: "serverId", sortable: true, thClass: "columnWidthInt"},
        {key: "error", sortable: false, thStyle: {width: "30%"}},
        {key: "createdAt", sortable: true, thClass: "columnWidthDate"},
        {key: "updatedAt", sortable: true, thClass: "columnWidthDate"},
      ]
      return this.loadDeposits(this.filter)
    },

    getTableName() {
      switch (this.filter) {
        case DepositFilter.ALL:
          return "All Deposits";
        case DepositFilter.PENDING:
          return "Pending Deposits";
        case DepositFilter.PUBLISHED:
          return "Published Deposits";
        case DepositFilter.FAILED:
          return "Failed Deposits";
        default:
          return "Error Table";
      }
    },

    capitalizeFirstLetter(string) {
      return string.charAt(0).toUpperCase() + string.slice(1);
    },

    translateDepositStatus(deposit_status) {
      if ("status" in deposit_status) {
        switch (deposit_status["status"]) {
          case 0:
            deposit_status.status = this.capitalizeFirstLetter(DepositFilter.PUBLISHED);
            break;
          case 1:
          case 2:
            deposit_status.status = this.capitalizeFirstLetter(DepositFilter.PENDING);
            break;
          case 3:
          case 4:
          case 5:
            deposit_status.status = this.capitalizeFirstLetter(DepositFilter.FAILED);
            break;
          default:
            break;
        }
      }
      //TODO query server id?

      return deposit_status;
    },

    checkDepositUpdate() {
      console.log("Polling deposits")
      this.loadDeposits(this.filter)  //try to fetch update after transfer handler
    },
  }
}
</script>
<style scoped>
#deposit-table-name {
  height: 50px;
}

#app-content > div {
  width: 100%;
  height: 100%;
  padding: 20px;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.icon-file {
  height: 50px;
}

#deposit-table {
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