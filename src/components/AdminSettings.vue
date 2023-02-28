<template>
	<div id="admin-settings" class="section">
		<h2>EUDAT B2SHARE Bridge</h2>
		<div class="new_server">
			<h3>Create a new Server:</h3>
			<ServerEditor :id="dummy_server.id"
				:name="dummy_server.name"
				:publishurl="dummy_server.publishUrl"
				:maxuploads="dummy_server.maxUploads"
				:maxuploadfilesize="dummy_server.maxUploadFilesize"
				:checkssl="dummy_server.checkSsl"
				@server-change="loadServers" />
		</div>
		<div v-if="loaded && servers.length" class="servers">
			<h3>Servers:</h3>
			<ul>
				<li v-for="server in servers">
					<ServerEditor :id="parseInt(server.id)"
						:name="server.name"
						:publishurl="server.publishUrl"
						:maxuploads="server.maxUploads"
						:maxuploadfilesize="server.maxUploadFilesize"
						:checkssl="server.checkSsl === 1"
						@server-change="loadServers" />
				</li>
			</ul>
		</div>
	</div>
</template>

<script>
// import {NcAppContent} from "@nextcloud/vue";
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import ServerEditor from './ServerEditor.vue'

export default {
	name: 'AdminSettings',
	components: {
		// NcAppContent,
		ServerEditor,
	},
	data() {
		return {
			dummy_server: {
				id: null,
				name: null,
				publishUrl: null,
				maxUploads: 5,
				maxUploadFilesize: 512,
				checkSsl: false,
			},
			servers: [],
			loaded: false,
		}
	},

	/**
	 * Fetch list of servers when the component is loaded
	 */
	async mounted() {
		await this.loadServers()
		this.loaded = true
	},
	methods: {
		loadServers() {
			const url_path = '/apps/b2sharebridge/servers?requesttoken=' + encodeURIComponent(OC.requestToken)

			return axios
				.get(generateUrl(url_path))
				.then((response) => {
					console.log('Loaded servers:')
					console.debug(response)
					this.servers = []
					this.servers = this.servers.concat(response.data)
				})
				.catch((error) => {
					console.error('Fetching B2SHARE servers failed!')
					console.error(error)
				})
		},
	},
}
</script>

<style scoped>
div.new_server {
	background: rgba(128, 128, 128, 0.1);
	padding: 10px;
	border-radius: 20px;
}

div.servers {
	margin-top: 10px;
	background: rgba(128, 128, 128, 0.1);
	padding: 10px;
	border-radius: 20px;
}
</style>
