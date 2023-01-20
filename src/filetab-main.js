import Vue from 'vue'
/**
 * Import Vee-Validate
 */
import { ValidationProvider, ValidationObserver} from 'vee-validate';
import VeeValidate from "vee-validate";

Vue.component('ValidationProvider', ValidationProvider);
Vue.component('ValidationObserver', ValidationObserver);
Vue.use(VeeValidate, {
    classes: true,
    classNames: {
        valid: 'is-valid',
        invalid: 'is-invalid'
    }
});


/**
 * Import Bootstrap
 */
import { BootstrapVue, IconsPlugin } from 'bootstrap-vue'

// Import Bootstrap and BootstrapVue CSS files (order is important)
//import 'bootstrap/dist/css/bootstrap.css'
//import 'bootstrap-vue/dist/bootstrap-vue.css'

// Make BootstrapVue available throughout your project
Vue.use(BootstrapVue)
// Optionally install the BootstrapVue icon components plugin
Vue.use(IconsPlugin)

/**
 * Import Sidebar
 */
import B2SBSidebar from "./components/B2SBSidebar.vue";
const View = Vue.extend(B2SBSidebar);
let tabInstance = null;

window.addEventListener('DOMContentLoaded', function() {
    if (OCA.Files && OCA.Files.Sidebar) {
        const b2sharebridgeTab = new OCA.Files.Sidebar.Tab({
            id: 'b2sharebridge',
            name: t('b2sharebridge', 'B2SHARE'),
            icon: 'icon-rename',

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
                //return (fileInfo && !fileInfo.isDirectory());
                return true;
            },
        })
        OCA.Files.Sidebar.registerTab(b2sharebridgeTab)
    }
})