import Vue from 'vue'
import AdminSettings from './components/AdminSettings.vue'

Vue.extend(AdminSettings)
Vue.mixin({ methods: { t, n } })

if (document.getElementById('admin-settings')) {
	new Vue({
		el: '#admin-settings',
		render: h => h(AdminSettings),
	})
}
