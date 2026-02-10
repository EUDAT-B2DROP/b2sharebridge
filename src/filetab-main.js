import { registerFileAction } from '@nextcloud/files'
import { generateFilePath } from '@nextcloud/router'
import { action } from './actions/B2sharebridgeTabOpenAction.js'
import { t, n } from '@nextcloud/l10n'

__webpack_nonce__ = btoa(OC.requestToken)
__webpack_public_path__ = generateFilePath('b2sharebridge', '', 'js/')
__webpack_require__.p = OC.filePath('b2sharebridge', 'js', '../build/'); // put your own app id and build path here.
const script = document.querySelector('[nonce]');
__webpack_require__.nc = script.nonce || script.getAttribute('nonce');


registerFileAction(action)
