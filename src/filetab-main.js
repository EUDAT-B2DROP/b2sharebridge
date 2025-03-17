import { registerFileAction } from '@nextcloud/files'
import { action } from './actions/B2sharebridgeTabOpenAction.js'
import { generateFilePath } from '@nextcloud/router'

// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('b2sharebridge', '', 'js/')

registerFileAction(action)
