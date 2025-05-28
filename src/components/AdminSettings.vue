<template>
	<div id="admin-settings" class="bridgeadmin">
		<h2>EUDAT B2SHARE Bridge</h2>
		<div id="create">
			<h3>Create a new Server:</h3>
			<ServerEditor
				:id="dummy_server.id"
				:name="dummy_server.name"
				:publishurl="dummy_server.publishUrl"
				:maxuploads="dummy_server.maxUploads"
				:maxuploadfilesize="dummy_server.maxUploadFilesize"
				:checkssl="dummy_server.checkSsl"
				:version="dummy_server.version"
				@server-change="loadServers" />
		</div>
		<div v-if="loaded && servers.length">
			<h3>Servers:</h3>
			<ul>
				<li v-for="server in servers" :key="server.id">
					<ServerEditor
						:id="parseInt(server.id)"
						:name="server.name"
						:publishurl="server.publishUrl"
						:maxuploads="server.maxUploads"
						:maxuploadfilesize="server.maxUploadFilesize"
						:checkssl="server.checkSsl === 1"
						:version="{ id: server.version, label: `API-Version ${server.version}` }"
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
				id: -1,
				name: '',
				publishUrl: '',
				maxUploads: 5,
				maxUploadFilesize: 512,
				checkSsl: false,
				version: {
					id: 2,
					label: 'API-Version 2',
				},
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
			const urlPath = '/apps/b2sharebridge/servers?requesttoken=' + encodeURIComponent(OC.requestToken)

			return axios
				.get(generateUrl(urlPath))
				.then((response) => {
					console.debug('Loaded servers:')
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

<style>
.bridgeadmin {
	padding: 10px;
}

</style>
