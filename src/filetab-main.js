import Vue from 'vue'
import { ValidationProvider } from 'vee-validate';
import B2SBSidebar from "./components/B2SBSidebar.vue";

Vue.component('ValidationProvider', ValidationProvider);
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
                tabInstance.update(fileInfo)
                tabInstance.$mount(el)
            },
            update(fileInfo) {
                tabInstance.update(fileInfo)
            },
            destroy() {
                tabInstance.$destroy()
                tabInstance = null
            },
            enabled(fileInfo) {
                return (fileInfo && !fileInfo.isDirectory());
            },
        })
        OCA.Files.Sidebar.registerTab(b2sharebridgeTab)
    }
})