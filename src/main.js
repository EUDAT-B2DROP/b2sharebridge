import Vue from 'vue'
import App from './App.vue'
import { generateFilePath } from '@nextcloud/router'

// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('b2sharebridge', '', 'js/')

Vue.mixin({ methods: { t, n } })

export default new Vue({
	el: '#content',
	render: h => h(App),
})
