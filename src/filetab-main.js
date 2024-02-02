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
import { registerFileAction, getNavigation } from '@nextcloud/files'


import '../css/fix-breadcrumbs.css'
import { action } from './actions/B2sharebridgeTabOpenAction'

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


/*const b2sharebridgeTab = new View({
	id: 'b2sharebridge',
	name: t('b2sharebridge', 'B2SHARE'),
	// iconClass: 'icon-b2share', // OC.imagePath('b2sharebridge', 'filelisticon'),
	icon: 'mdi-cloud-upload-outline',
	getContents(path) {
	    return new B2SBSidebar
	}
})

window.addEventListener('DOMContentLoaded', function() {
	if (OCA.Files && OCA.Files.Sidebar) {
		OCA.Files.Sidebar.registerTab(b2sharebridgeTab)
	} else {
		console.error('Error with OCA.Files and/or OCA.Files.Sidebar')
	}
})*/

registerFileAction(action)
let tabInstance = null

window.addEventListener('DOMContentLoaded', function() {
    if (OCA.Files?.Sidebar === undefined) {
        return
    }
    const b2sharebridgeTab = new OCA.Files.Sidebar.Tab({
        id: 'b2sharebridgetab',
        name: t('b2sharebridge', 'B2SHARE'),
        // iconClass: 'icon-b2share', // OC.imagePath('b2sharebridge', 'filelisticon'),
        icon: 'icon-upload',
        async mount(el, fileInfo, context) {
            if (tabInstance) {
                tabInstance.$destroy()
            }
            tabInstance = new View({
                // Better integration with vue parent component
                parent: context,
            })
            // Only mount after we have all the info we need
            await tabInstance.initializeB2ShareUI(fileInfo)
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
})