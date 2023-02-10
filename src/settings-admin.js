import Vue from 'vue'

/**
 * Import Bootstrap
 */
import {BootstrapVue, IconsPlugin} from 'bootstrap-vue'

/**
 * Import Sidebar
 */
import AdminSettings from './components/AdminSettings.vue'


// Import Bootstrap and BootstrapVue CSS files (order is important)
// import 'bootstrap/dist/css/bootstrap.css'
// import 'bootstrap-vue/dist/bootstrap-vue.css'

// Make BootstrapVue available throughout your project
Vue.use(BootstrapVue)
// Optionally install the BootstrapVue icon components plugin
Vue.use(IconsPlugin)

Vue.extend(AdminSettings)

Vue.mixin({methods: {t, n}})

if (document.getElementById("admin-settings")) {
    new Vue({
        el: '#admin-settings',
        render: h => h(AdminSettings),
    })
}