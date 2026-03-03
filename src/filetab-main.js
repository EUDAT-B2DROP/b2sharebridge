import { registerFileAction } from '@nextcloud/files'
import { B2sharebridgeTabOpenAction } from './actions/B2sharebridgeTabOpenAction.js'

__webpack_nonce__ = btoa(OC.requestToken)
registerFileAction(B2sharebridgeTabOpenAction)
