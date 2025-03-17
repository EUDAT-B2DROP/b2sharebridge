import Vue from 'vue'
import AdminSettings from './components/AdminSettings.vue'
import { generateFilePath } from '@nextcloud/router'

// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('b2sharebridge', '', 'js/')

Vue.extend(AdminSettings)
Vue.mixin({ methods: { t, n } })

export default new Vue({
	el: '#admin-settings',
	render: h => h(AdminSettings),
})
