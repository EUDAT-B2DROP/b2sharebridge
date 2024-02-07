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
 * Import Modal
 */
import { registerFileAction } from '@nextcloud/files'


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

Vue.use(BootstrapVue)
// Optionally install the BootstrapVue icon components plugin
Vue.use(IconsPlugin)

registerFileAction(action)
