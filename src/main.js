/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { generateFilePath } from '@nextcloud/router'

import Vue from 'vue'
import App from './App.vue'
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

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('b2sharebridge', '', 'js/')

Vue.mixin({ methods: { t, n } })

export default new Vue({
	el: '#content',
	render: h => h(App),
})
