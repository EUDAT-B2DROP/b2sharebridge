import Vue from 'vue'
import PersonalSettings from './components/PersonalSettings.vue'

Vue.extend(PersonalSettings)

Vue.mixin({ methods: { t, n } })

if (document.getElementById('b2sharebridge-personal-settings')) {
	new Vue({
		el: '#b2sharebridge-personal-settings',
		render: h => h(PersonalSettings),
	})
}
