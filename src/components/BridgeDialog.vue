<template>
	<NcModal v-if="info.message !== ''"
		id="infodial"
		ref="modalRef"
		name="">
		<div class="modal__content">
			<h2>{{ info.heading }}</h2>
			<p>{{ info.message }}</p>
			<span class="button-container">
				<NcButton v-for="button in info.buttons"
					:key="button.label"
					:href="button.href"
					:type="button.type"
					:area-label="button.label"
					@click="button.callback">
					{{ button.label }}
				</NcButton>
			</span>
		</div>
	</NcModal>
	<NcModal v-else-if="showDialog"
		id="bridgedial"
		ref="modalRef"
		name="">
		<div class="modal__content">
			<h2>Create a B2SHARE deposit</h2>
			<NcTextField :value.sync="title.text"
				label="Title"
				placeholder="Please enter a title"
				:error="title.error"
				:success="title.success"
				minlength="3"
				maxlength="128"
				:helper-text="title.helpertext"
				@update:value="validate">
				<PencilIcon :size="20" />
			</NcTextField>
			<NcSelect v-bind="serverprops"
				v-model="serverprops.value"
				required
				:class="{ selecterror: serverprops.error }"
				@input="onChangeServer" />
			<NcSelect v-bind="communityprops"
				v-model="communityprops.value"
				required
				:class="{ selecterror: communityprops.error }"
				@input="validate" />
			<NcCheckboxRadioSwitch :checked.sync="openAccess">
				Open Access
			</NcCheckboxRadioSwitch>
			<span class="button-container">
				<NcButton aria-label="close"
					@click="closeModal">
					Cancel
				</NcButton>
				<NcButton :disabled="publish.disabled"
					type="primary"
					aria-label="publish"
					@click="createDeposit">
					Publish
				</NcButton>
			</span>
		</div>
	</NcModal>
</template>
<script>
import { NcModal, NcButton, NcTextField, NcSelect, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
export default {
	name: 'BridgeDialog',
	components: {
		NcModal,
		NcTextField,
		NcSelect,
		NcCheckboxRadioSwitch,
		NcButton,
		PencilIcon,
	},
	data() {
		return {
			showDialog: false,
			publish: {
				disabled: true,
				pressedOnce: false,
			},
			title: {
				success: false,
				error: false,
				text: '',
				helpertext: '',
			},
			serverprops: {
				inputLabel: 'Server',
				options: [],
				value: null,
				error: false,
			},
			communityprops: {
				inputLabel: 'Community',
				options: [],
				value: null,
				error: false,
			},
			openAccess: false,
			info: {
				heading: 'Error',
				message: '',
				buttons: [
					{
						label: 'Ok',
						type: 'error',
						callback: () => (this.closeModal()),
					},
				],
			},

			// Technical fields
			tokens: [],
			servers: [],
			selectedFiles: [],
		}
	},

	async mounted() {
		const serverPromise = this.loadServers()
		const communityPromise = this.loadCommunities()
		const tokenPromise = this.loadTokens()
		await serverPromise
		await communityPromise
		await tokenPromise

		if (!this.hasValidTokens()) {
			this.tokens = null
			this.info.heading = 'You are missing a token!'
			this.info.message = 'Please set your B2SHARE API token.' // <a class="bridgelink" href="/settings/user/b2sharebridge">here</a>'
			// this.showDialog = true
			this.info.buttons = [
				{
					label: 'Cancel',
					type: 'secondary',
					callback: () => (this.closeModal()),
				},
				{
					label: 'Set API Token',
					type: 'primary',
					callback: () => (this.redirect('/settings/user/b2sharebridge')),
					href: generateUrl('/settings/user/b2sharebridge'),
				},
			]
			return
		}

		if (this.servers.length !== Object.keys(this.tokens).length) {
			this.info.message = 'Number of servers and tokens differ, please contact an administrator'
			console.error(this.info.message)
			this.tokens = null
			// this.showDialog = true
			return
		}

		if (this.servers.length !== 0) {
			this.servers.forEach(server => {
				const hasToken = this.tokens[server.id] !== ''
				if (hasToken) {
					const serverOption = {
						id: server.id,
						label: server.name,
					}
					this.serverprops.options.push(serverOption)
					if (this.serverprops.value === null) {
						console.debug('Selected server automatically')
						this.serverprops.value = serverOption
						this.onChangeServer() // update communities
					}
				}
			})
		}

		this.showDialog = true
	},

	methods: {
		// API stuff
		loadServers() {
			const urlPath
				= '/apps/b2sharebridge/servers?requesttoken='
				+ encodeURIComponent(OC.requestToken)

			return axios
				.get(generateUrl(urlPath))
				.then((response) => {
					console.debug('Loaded servers:')
					console.debug(response)
					this.servers = response.data
				})
				.catch((error) => {
					this.info.message = 'Fetching B2SHARE servers failed! Please check your connection or contact an administrator!'
					console.error(this.info.message)
					console.error(error)
				})
		},

		loadCommunities() {
			const urlPath
				= '/apps/b2sharebridge/gettabviewcontent?requesttoken='
				+ encodeURIComponent(OC.requestToken)

			return axios
				.get(generateUrl(urlPath))
				.then((response) => {
					console.debug('Loaded communities:')
					console.debug(response)
					this.communities = response.data
				})
				.catch((error) => {
					this.info.message = 'Fetching B2SHARE communities failed! Please check your connection or contact an administrator!'
					console.error(this.info.message)
					console.error(error)
				})
		},

		loadTokens() {
			const urlPath
				= '/apps/b2sharebridge/apitoken?requesttoken='
				+ encodeURIComponent(OC.requestToken)

			return axios
				.get(generateUrl(urlPath))
				.then((response) => {
					console.debug('Successfully requested tokens!')
					console.debug(response.data)
					if (response.data) {
						this.tokens = response.data
					} else {
						console.info('No token set!')
					}
				})
				.catch((error) => {
					this.info.message = 'Fetching tokens failed! Please check your connection or contact an administrator!'
					console.error(this.info.message)
					console.error(error)
				})
		},

		// Events
		onChangeServer() {
			console.debug('Updating community options')

			// reset communityprops
			this.communityprops.options = []
			this.communityprops.value = null

			if (this.serverprops.value !== null) {
				// set communities for new server
				this.communities.forEach((community, index) => {
					if (Object.hasOwn(community, 'serverId') && parseInt(community.serverId) === parseInt(this.serverprops.value.id)) {
						const communityOption = {
							id: community.id,
							label: community.name,
						}
						this.communityprops.options.push(communityOption)
						if (community.name === 'EUDAT') { // TODO make this configurable
							console.debug('Automatically selected EUDAT as community')
							this.communityprops.value = communityOption
						}
					}
				})
			}
			this.validate()
		},

		hasValidTokens() {
			if (this.tokens === null) {
				return false
			}
			let validTokenFound = false
			Object.keys(this.tokens).forEach(key => {
				if (this.tokens[key] !== '') {
					validTokenFound = true
				}
			})
			return validTokenFound
		},

		validate(event) {
			let isValid = true
			this.title.error = false
			this.title.helpertext = ''
			this.serverprops.error = false
			this.communityprops.error = false

			// run checks
			if (this.serverprops.value === null) {
				isValid = false
				if (this.publish.pressedOnce) {
					this.serverprops.error = true
				}
			}

			if (this.communityprops.value === null) {
				isValid = false
				if (this.publish.pressedOnce) {
					this.communityprops.error = true
				}
			}

			if (this.title.text.length <= 3 || this.title.text.length > 128) {
				isValid = false
				if (this.publish.pressedOnce) {
					// Note both can be false
					this.title.error = true
					this.title.success = false
					this.title.helpertext = 'Please set a title with a length between 3 and 128 characters'
				}
			} else if (this.publish.pressedOnce) {
				this.title.error = false
				this.title.success = true
			}

			// Change publishable with validation
			this.publish.disabled = !isValid
			return isValid
		},

		async createDeposit() {
			// validate inputs only after first press
			this.publish.pressedOnce = true
			if (!this.validate()) {
				return
			}

			if (!this.selectedFiles.length) {
				console.error('No files selected')
				return
			}

			axios
				.post(generateUrl('/apps/b2sharebridge/publish'),
					{
						ids: this.selectedFiles,
						community: this.communityprops.value.id,
						open_access: this.openAccess,
						title: this.title.text,
						server_id: this.serverprops.value.id,
					})
				.then(() => {
					this.info.heading = 'Transferring to B2SHARE'
					this.info.message = 'Your files are transfarred in the background. This may take a few minutes. You\'ll '
						+ 'get notified after the transfer finished.'
					this.info.buttons = [
						{
							label: 'Ok',
							type: 'secondary',
							callback: () => (this.closeModal()),
						},
						{
							label: 'Show Deposit',
							type: 'primary',
							callback: () => (this.redirect('/apps/b2sharebridge')),
							href: generateUrl('/apps/b2sharebridge'),
						},
					]
				})
				.catch((error) => {
					if (error.response) {
						if (error.response.status === 413) { // entity too large
							this.info.message = 'Publishing failed! One or more of your files are too large!'
						} else if (error.response.status === 429) { // too many uploads
							this.info.message = 'Publishing failed! You have too many pending uploads, please try again later!'
						} else {
							this.info.message = 'Publishing failed! Please check your connection or contact an administrator!'
						}
						console.error(this.info.message)
					}
					console.error(error)
				})

		},

		closeModal() {
			this.showDialog = false
			this.info.message = ''
		},
	},
}
</script>

<style>
#bridgedial {
	min-width: 60%;
}

#bridgedial .select {
	width: 100%;
}

#bridgedial .modal__content,
#infodial .modal__content {
	margin: 50px;
}

#bridgedial .modal__content h2,
#infodial .modal__content h2 {
	text-align: center;
}

#bridgedial .form-group,
#infodial .form-group {
	margin: calc(var(--default-grid-baseline) * 4) 0;
	display: flex;
	flex-direction: column;
	align-items: flex-start;
}

#bridgedial .button-container,
#infodial .button-container {
	display: flex;
	flex-direction: row;
	align-items: start;
	justify-content: flex-end;
}

#bridgedial .selecterror div {
	border-color: red;
}

#infodial a {
	border-radius: var(--border-radius-pill);
}
</style>
