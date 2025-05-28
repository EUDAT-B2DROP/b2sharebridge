import { generateFilePath } from '@nextcloud/router'
import { createApp } from 'vue'
import AdminSettings from './components/AdminSettings.vue'

__webpack_nonce__ = btoa(OC.requestToken)
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('b2sharebridge', '', 'js/')

const app = createApp(AdminSettings)
app.mixin({ methods: { t, n } })
app.mount('#admin-settings')
