import { registerFileAction } from '@nextcloud/files'
import { generateFilePath } from '@nextcloud/router'
import { action } from './actions/B2sharebridgeTabOpenAction.js'

__webpack_nonce__ = btoa(OC.requestToken)
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('b2sharebridge', '', 'js/')

registerFileAction(action)
