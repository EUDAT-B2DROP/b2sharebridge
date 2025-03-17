import Vue from 'vue'
import PersonalSettings from './components/PersonalSettings.vue'
import { generateFilePath } from '@nextcloud/router'

// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('b2sharebridge', '', 'js/')

Vue.extend(PersonalSettings)
Vue.mixin({ methods: { t, n } })

export default new Vue({
	el: '#b2sharebridge-personal-settings',
	render: h => h(PersonalSettings),
})
