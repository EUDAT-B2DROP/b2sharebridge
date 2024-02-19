import Vue from 'vue'
import { Permission, FileAction, Node, FileType } from '@nextcloud/files'
import logger from '../logger.js'

import { showMessage, showInfo, showSuccess, showWarning, showError, spawnDialog } from '@nextcloud/dialogs'
import B2SBSidebar from '../components/B2SBSidebar.vue'

const filepicker = async (nodes) => {
	const FileIds = nodes.map(node => node.fileid)
	const bridgeVueComponent = Vue.extend({
		extends: B2SBSidebar,
		data() {
			return {
				selectedFiles: FileIds,
			}
		},
	})

	spawnDialog(bridgeVueComponent)
	return true
}

export const action = new FileAction({
	id: 'b2sharebridge-action',

	title(nodes) {
		return 'B2SharebridgeFileActionTitle'
	},

	// Empty string when rendered inline
	displayName: () => 'B2SHARE',

	iconSvgInline: () => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M6.5 20Q4.22 20 2.61 18.43 1 16.85 1 14.58 1 12.63 2.17 11.1 3.35 9.57 5.25 9.15 5.88 6.85 7.75 5.43 9.63 4 12 4 14.93 4 16.96 6.04 19 8.07 19 11 20.73 11.2 21.86 12.5 23 13.78 23 15.5 23 17.38 21.69 18.69 20.38 20 18.5 20H13Q12.18 20 11.59 19.41 11 18.83 11 18V12.85L9.4 14.4L8 13L12 9L16 13L14.6 14.4L13 12.85V18H18.5Q19.55 18 20.27 17.27 21 16.55 21 15.5 21 14.45 20.27 13.73 19.55 13 18.5 13H17V11Q17 8.93 15.54 7.46 14.08 6 12 6 9.93 6 8.46 7.46 7 8.93 7 11H6.5Q5.05 11 4.03 12.03 3 13.05 3 14.5 3 15.95 4.03 17 5.05 18 6.5 18H9V20M12 13Z" /></svg>',

	enabled(nodes) {
		if (nodes.length) { return !nodes.some(node => node.type === FileType.Folder) && nodes.every(node => node.permissions !== Permission.NONE) }
		return false
	},

	async exec(node, view, dir) {
		/* try {
			window.OCA.Files.Sidebar.setActiveTab('b2sharebridgetab')
			await window.OCA.Files.Sidebar.open(node.path)
			return null
		} catch (error) {
			logger.error('Error while opening sidebar', { error })
			return false
		} */
		const result = await filepicker([node])// await openB2SharePickerForAction(dir, [node])
		return result
	},

	async execBatch(nodes, view, dir) {
		if (nodes.length === 0) {
			return false
		}
		const result = await filepicker(nodes)// await openB2SharePickerForAction(dir, nodes)
		return result
	},

	inline: () => false,

	order: -51,
})
