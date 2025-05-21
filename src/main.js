import { generateFilePath } from '@nextcloud/router'
import { createApp } from 'vue'
import App from './App.vue'

__webpack_nonce__ = btoa(OC.requestToken)
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('b2sharebridge', '', 'js/')

const app = createApp(App)
app.mixin({ methods: { t, n } })
app.mount('#content')
