import { n, t } from '@nextcloud/l10n'
import { generateFilePath } from '@nextcloud/router'
import { createApp } from 'vue'
import PersonalSettings from './components/PersonalSettings.vue'

__webpack_nonce__ = btoa(OC.requestToken)
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('b2sharebridge', '', 'js/')

const app = createApp(PersonalSettings)
app.mixin({ methods: { t, n } })
app.mount('#b2sharebridge-personal-settings')
