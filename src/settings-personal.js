import Vue from 'vue'

/**
 * Import Bootstrap
 */
import {BootstrapVue, IconsPlugin} from 'bootstrap-vue'

/**
 * Import Sidebar
 */
import PersonalSettings from './components/PersonalSettings.vue'


// Import Bootstrap and BootstrapVue CSS files (order is important)
// import 'bootstrap/dist/css/bootstrap.css'
// import 'bootstrap-vue/dist/bootstrap-vue.css'

// Make BootstrapVue available throughout your project
Vue.use(BootstrapVue)
// Optionally install the BootstrapVue icon components plugin
Vue.use(IconsPlugin)

Vue.extend(PersonalSettings)

Vue.mixin({methods: {t, n}})

if (document.getElementById("personal-settings")) {
    new Vue({
        el: '#personal-settings',
        render: h => h(PersonalSettings),
    })
}