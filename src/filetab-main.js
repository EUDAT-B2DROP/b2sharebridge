import { registerFileAction } from '@nextcloud/files'
import { action } from './actions/B2sharebridgeTabOpenAction'

registerFileAction(action)
