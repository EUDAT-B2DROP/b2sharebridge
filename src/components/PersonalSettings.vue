<template>
	<div id="bridgepersonal">
		<h2>EUDAT B2SHARE Bridge</h2>
		<div v-if="loaded && servers.length">
			<ul>
				<li v-for="server in servers" :key="server.id">
					<TokenEditor
						:id="parseInt(server.id)"
						:name="server.name"
						:url="server.publishUrl"
						:token="getToken(server.id)"
						@token-change="updateTokens" />
				</li>
			</ul>
		</div>
		<div v-if="loaded && servers.length === 0">
			<p>No B2SHARE servers configured! </p>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import TokenEditor from './TokenEditor.vue'

export default {
	name: 'PersonalSettings',
	components: {
		TokenEditor,
	},

	data() {
		return {
			servers: [],
			tokens: [],
			loaded: false,
		}
	},

	/**
	 * Fetch list of servers when the component is loaded
	 */
	async mounted() {
		const promise = this.loadServers()
		await this.loadTokens() // ask for both and then wait
		await promise
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
					this.servers = response.data
				})
				.catch((error) => {
					console.error('Fetching B2SHARE servers failed!')
					console.error(error)
				})
		},

		loadTokens() {
			const urlPath = '/apps/b2sharebridge/apitoken?requesttoken=' + encodeURIComponent(OC.requestToken)

			return axios
				.get(generateUrl(urlPath))
				.then((response) => {
					console.debug('Loaded tokens:')
					console.debug(response)
					this.tokens = response.data
				})
				.catch((error) => {
					console.error('Fetching B2SHARE tokens failed!')
					console.error(error)
				})
		},

		getToken(serverId) {
			return this.tokens[serverId] || ''
		},

		updateTokens() {
			this.loadTokens()
			this.$forceUpdate()
		},
	},
}
</script>

<style>
#bridgepersonal {
	padding: 10px;
}
</style>
