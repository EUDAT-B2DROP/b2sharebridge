import { registerFileAction } from '@nextcloud/files'
import '../css/fix-breadcrumbs.css'
import { action } from './actions/B2sharebridgeTabOpenAction'

registerFileAction(action)
