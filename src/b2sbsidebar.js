import Vue from 'vue'
import b2sharebridgeSidebar from 'components/B2SBSidebar.vue'

const View = Vue.extend(b2sharebridgeSidebar)
let tabInstance = null

const workspaceTab = new OCA.Files.Sidebar.Tab({
    id: 'b2sbtab',
    name: t('b2sharebridge', 'B2SHARE'),
    icon: 'icon-rename',

    async mount(el, fileInfo, context) {
        if (tabInstance) {
            tabInstance.$destroy()
        }
        tabInstance = new View({
            // Better integration with vue parent component
            parent: context,
        })
        // Only mount after we have all the info we need
        await tabInstance.update(fileInfo)
        tabInstance.$mount(el)
    },
    update(fileInfo) {
        tabInstance.update(fileInfo)
    },
    destroy() {
        tabInstance.$destroy()
        tabInstance = null
    },
})

window.addEventListener('DOMContentLoaded', function() {
    if (OCA.Files && OCA.Files.Sidebar) {
        OCA.Files.Sidebar.registerTab(workspaceTab)
    } else {
        console.error('Error with OCA.Files and/or OCA.Files.Sidebar')
    }
})
