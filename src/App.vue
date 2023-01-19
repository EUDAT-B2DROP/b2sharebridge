<template>
  <NcContent appName="b2sharebridge">
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
        <div v-if="Deposits.length === 0">
          <div class="icon-file"/>
          <h2 style="text-align: center;">{{ t('b2sharebridge', 'Create a deposit to get started') }}</h2>
        </div>
        <div v-else>
          <b-table striped hover :deposits="Deposits"></b-table>
        </div>
      </NcAppContent>
    </div>
  </NcContent>
</template>
<script>
import '@nextcloud/dialogs/styles/toast.scss'
import '../css/style.css'
import axios from '@nextcloud/axios'
import {generateUrl} from '@nextcloud/router'
import {showError, showSuccess} from '@nextcloud/dialogs'
import {
  NcContent,
  NcActionButton,
  NcAppContent,
  NcAppNavigation,
  NcAppNavigationItem,
  NcAppNavigationNew
} from '@nextcloud/vue'

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
      Deposits: [],
      updating: false,
      loading: true,
    }
  },
  /**
   * Fetch list of deposits when the component is loaded
   */
  async mounted() {
    try {
      const response = await axios.get(generateUrl('/apps/b2sharebridge/')); //TODO
      this.Deposits = response.data
    } catch (e) {
      console.error(e)
      showError(t('b2sharebridge', 'Could not fetch deposits'))
    }
    this.loading = false
  },
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

input[type='text'] {
  width: 100%;
}

textarea {
  flex-grow: 1;
  width: 100%;
}
</style>