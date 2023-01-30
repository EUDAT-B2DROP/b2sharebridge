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

window.addEventListener('DOMContentLoaded', function () {
    if (OCA.Files) {

        let b2sharebridgeMain = {
            id: 'b2sharebridge',
            name: t('b2sharebridge', 'B2SHARE'),
            icon: OC.imagePath('b2sharebridge', 'img/filelisticon'),

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
                return (fileInfo && !fileInfo.isDirectory());
            },
        };
        if (OCA.Files.Sidebar) {
            const b2sharebridgeTab = new OCA.Files.Sidebar.Tab(b2sharebridgeMain);
            OCA.Files.Sidebar.registerTab(b2sharebridgeTab)
        }
        if (OCA.Files.FileActions) {
            if (!OCA.Files.Sidebar) {
                console.error("No sidebar available!");
            }
            console.log("File Actions available");
            OCA.Files.fileActions.registerAction({
                    name: 'b2sharebridge-action',
                    mime: 'all',
                    displayName: t('b2sharebridge', 'B2SHARE'),
                    permissions: OC.PERMISSION_ALL,
                    icon: OC.imagePath('b2sharebridge', 'filelisticon'),
                    actionHandler: function (fileName, context) {
                        //Comes from apps/files/src/services/Sidebar.js
                        // and apps/files/js/filelist.js#L677

                        console.log("action handler called");
                        if (!(OCA.Files && OCA.Files.Sidebar)) {
                            console.error('No sidebar available');
                            return;
                        }

                        if (!fileName && OCA.Files.Sidebar.close) {
                            console.log("Closing sidebar");
                            OCA.Files.Sidebar.close();
                            return;
                        } else if (typeof fileName !== 'string') {
                            fileName = '';
                        }
                        let path = context.fileInfoModel.path + '/' + context.fileInfoModel.name;
                        if (fileName !== '') {
                            console.log("Trying to open sidebar");
                            OCA.Files.Sidebar.open(path.replace('//', '/'));
                            OCA.Files.Sidebar.setActiveTab('b2sharebridge');
                        } else {
                            console.error("No file selected");
                        }
                    },
                }
            );
        }
    }
})