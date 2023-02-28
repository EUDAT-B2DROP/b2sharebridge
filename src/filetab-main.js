import Vue from 'vue'
/**
 * Import Vee-Validate
 */
import { ValidationProvider, ValidationObserver, configure } from 'vee-validate'

/**
 * Import Bootstrap
 */
import { BootstrapVue, IconsPlugin } from 'bootstrap-vue'

/**
 * Import Sidebar
 */
import B2SBSidebar from './components/B2SBSidebar.vue'

import '../css/fix-breadcrumbs.css'

Vue.component('ValidationProvider', ValidationProvider)
Vue.component('ValidationObserver', ValidationObserver)
const config = {
	classes: {
		valid: 'is-valid',
		invalid: 'is-invalid',
	},
	bails: true,
	skipOptional: true,
	mode: 'aggressive',
	useConstraintAttrs: true,
}
configure(config)

// Import Bootstrap and BootstrapVue CSS files (order is important)
// import 'bootstrap/dist/css/bootstrap.css'
// import 'bootstrap-vue/dist/bootstrap-vue.css'

// Make BootstrapVue available throughout your project
Vue.use(BootstrapVue)
// Optionally install the BootstrapVue icon components plugin
Vue.use(IconsPlugin)

const View = Vue.extend(B2SBSidebar)
let tabInstance = null

window.addEventListener('DOMContentLoaded', function() {
	if (OCA.Files) {
		if (OCA.Files.Sidebar) {
			const b2sharebridgeTab = new OCA.Files.Sidebar.Tab({
				id: 'b2sharebridge',
				name: t('b2sharebridge', 'B2SHARE'),
				// iconClass: 'icon-b2share', // OC.imagePath('b2sharebridge', 'filelisticon'),
				icon: 'icon-upload',
				mount(el, fileInfo, context) {
					if (tabInstance) {
						tabInstance.$destroy()
					}
					tabInstance = new View({
						// Better integration with vue parent component
						parent: context,
					})
					// Only mount after we have all the info we need
					tabInstance.initializeB2ShareUI(fileInfo)
					tabInstance.$mount(el)
				},
				update(fileInfo) {
					tabInstance.initializeB2ShareUI(fileInfo)
				},
				destroy() {
					tabInstance.$destroy()
					tabInstance = null
				},
				enabled(fileInfo) {
					return (fileInfo && !fileInfo.isDirectory())
				},
			})
			OCA.Files.Sidebar.registerTab(b2sharebridgeTab)
		}
		if (OCA.Files.FileActions) {
			if (!OCA.Files.Sidebar) {
				console.error('No sidebar available!')
			}
			console.debug('File Actions available')
			OCA.Files.fileActions.registerAction({
				name: 'b2sharebridge-action',
				mime: 'all',
				displayName: t('b2sharebridge', 'B2SHARE'),
				permissions: OC.PERMISSION_READ,
				// icon: 'icon-upload',
				iconClass: 'icon-upload', //
				actionHandler(fileName, context) {
					// Comes from apps/files/src/services/Sidebar.js
					// and apps/files/js/filelist.js#L677

					console.debug('action handler called')
					if (!(OCA.Files && OCA.Files.Sidebar)) {
						console.error('No sidebar available')
						return
					}

					if (!fileName && OCA.Files.Sidebar.close) {
						console.debug('Closing sidebar')
						OCA.Files.Sidebar.close()
						return
					} else if (typeof fileName !== 'string') {
						fileName = ''
					}
					const path = context.dir + '/' + fileName
					if (fileName !== '') {
						console.debug('Trying to open sidebar')
						console.debug('Path:'.path)
						OCA.Files.Sidebar.setActiveTab('b2sharebridge')
						OCA.Files.Sidebar.open(path.replace('//', '/'))

					} else {
						console.error('No file selected')
					}
				},
			}
			)
		}
	}
}
)
