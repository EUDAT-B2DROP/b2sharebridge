import Vue from 'vue'
/**
 * Import Vee-Validate
 */
import {ValidationProvider, ValidationObserver, configure} from 'vee-validate';

Vue.component('ValidationProvider', ValidationProvider);
Vue.component('ValidationObserver', ValidationObserver);

configure({
    classes: {
        valid: 'is-valid',
        invalid: 'is-invalid'
    }
})

/**
 * Import Bootstrap
 */
import {BootstrapVue, IconsPlugin} from 'bootstrap-vue'

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

/**
 * Registers the action handler for the multi select actions menu.
 *
 * @param {Function} action Callback to the handler
 */
export function registerMultiSelect(action) {
    const actionObj = {
        name: 'b2sharebridge_multi_action',
        displayName: t('b2sharebridge_multi_action', 'B2SHARE'),
        iconClass: 'icon-rename',
        order: 1001,
        action,
    }

    if (OCA.Files.App.fileList) {
        OCA.Files.App.fileList.registerMultiSelectFileAction(actionObj)
    } else {
        OC.Plugins.register('OCA.Files.FileList', {
            attach(fileList) {
                fileList.registerMultiSelectFileAction(actionObj)
            },
        })
    }
}

window.addEventListener('DOMContentLoaded', function () {
    if (OCA.Files) {

        let b2sharebridgeMain = {
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
        };
        if (OCA.Files.Sidebar) {
            const b2sharebridgeTab = new OCA.Files.Sidebar.Tab(b2sharebridgeMain);
            OCA.Files.Sidebar.registerTab(b2sharebridgeTab)

        }
        if (OCA.Files.FileList) {
            OCA.Files.FileList.registerDefaultView(b2sharebridgeMain);
            //OC.Plugins.register('OCA.Files.FileList', b2sharebridgeMain);
        }
    }
})