<template>
	<NcModal v-if="info.message !== ''" id="infodial" name="">
		<div class="modal__content">
			<h2>{{ info.heading }}</h2>
			<p>{{ info.message }}</p>
			<span class="button-container">
				<NcButton
					v-for="button in info.buttons"
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
	<NcModal v-else-if="showDialog" id="bridgedial" name="">
		<div class="modal__content">
			<h2>Upload data to B2SHARE</h2>
			<NcSelect
				v-bind="modeprops"
				v-model="modeprops.value"
				required
				:class="{ selecterror: modeprops.error }"
				@update:model-value="onChangeMode" />
			<NcTextField
				v-if="!modeprops.value || modeprops.value.id === 'create'"
				v-model="title.text"
				label="Title"
				placeholder="Please enter a title"
				:disabled="!modeprops.value"
				:error="title.error"
				:success="title.success"
				minlength="3"
				maxlength="128"
				:helper-text="title.helpertext"
				@update:model-value="validate">
				<PencilIcon :size="20" />
			</NcTextField>
			<NcSelect
				v-bind="serverprops"
				v-model="serverprops.value"
				required
				:class="{ selecterror: serverprops.error }"
				@update:model-value="onChangeServer" />
			<NcSelect
				v-if="modeprops.value && modeprops.value.id === 'attach'"
				v-bind="depositselectprops"
				v-model="depositselectprops.value"
				required
				:class="{ selecterror: depositselectprops.error }"
				@update:model-value="validate" />
			<NcSelect
				v-if="!modeprops.value || modeprops.value.id === 'create'"
				v-bind="communityprops"
				v-model="communityprops.value"
				:disabled="!modeprops.value"
				required
				:class="{ selecterror: communityprops.error }"
				@update:model-value="validate" />
			<NcCheckboxRadioSwitch
				v-if="!modeprops.value || modeprops.value.id === 'create'"
				v-model="openAccess"
				:disabled="!modeprops.value">
				Open Access
			</NcCheckboxRadioSwitch>
			<span class="button-container">
				<NcButton aria-label="close" @click="closeModal">
					Cancel
				</NcButton>
				<NcButton
					:disabled="publish.disabled || publish.publishing"
					type="primary"
					aria-label="publish"
					@click="createDeposit">
					{{ getPublishLabel() }}
				</NcButton>
			</span>
			<NcProgressBar v-if="publish.publishTime" :value="getProgressBar()" size="medium" />
		</div>
	</NcModal>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcCheckboxRadioSwitch, NcModal, NcProgressBar, NcSelect, NcTextField } from '@nextcloud/vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'

export default {
	name: 'BridgeDialog',
	components: {
		NcModal,
		NcTextField,
		NcSelect,
		NcCheckboxRadioSwitch,
		NcButton,
		NcProgressBar,
		PencilIcon,
	},

	props: {
		selectedFiles: {
			type: Array,
			required: true,
		},
	},

	data() {
		return {
			showDialog: false,
			publish: {
				disabled: true,
				publishing: false,
				publishTime: null,
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

			modeprops: {
				inputLabel: 'Mode',
				options: [
					{
						id: 'create',
						label: 'Create a new draft',
					},
					{
						id: 'attach',
						label: 'Attach to existing draft',
					},
				],

				value: null,
				error: false,
			},

			depositselectprops: {
				inputLabel: 'Select Draft',
				options: [],
				deposits: null,
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
			this.servers.forEach((server) => {
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
		async loadServers() {
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

		async loadCommunities() {
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

		async loadTokens() {
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

		updateDespositList() {
			if (!this.depositselectprops.deposits || !this.serverprops.value) {
				return
			}

			this.depositselectprops.options = []
			const deposits = this.depositselectprops.deposits[this.serverprops.value.id].hits
			deposits.forEach((deposit) => {
				const depositOption = {
					id: deposit.id,
					label: deposit.metadata.titles[0].title,
				}
				this.depositselectprops.options.push(depositOption)
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
				this.communities.forEach((community, _index) => {
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

				// update deposit list in attach mode
				this.updateDespositList()
			}
			this.validate()
		},

		onChangeMode() {
			console.debug('Updating b2share draft list')

			if (!this.depositselectprops.deposits) {
				this.fetchAllDrafts()
			}

			this.validate()
		},

		async fetchAllDrafts() {
			const size = 50

			/**
			 *
			 * @param page page number
			 */
			function createUrl(page) {
				const urlArr = [
					'/apps/b2sharebridge/publications?draft=',
					true,
					'&page=',
					page,
					'&size=',
					size,
					'&requesttoken=',
					encodeURIComponent(OC.requestToken),
				]
				return generateUrl(urlArr.join(''))
			}

			// catch the first result and calculate if more are needed
			const depositData = {}
			try {
				const firstRes = await axios.get(createUrl(1))
				const servers = Object.keys(firstRes.data)
				let totalNumber = 0
				for (const server of servers) {
					const total = firstRes.data[server].total
					if (total > totalNumber) {
						totalNumber = total
					}
					const serverData = {
						hits: firstRes.data[server].hits,
						total,
					}
					depositData[server] = serverData
				}

				// catch other results
				if (totalNumber > size) {
					// promises
					const requests = []
					for (let page = 2; (page - 1) * size < totalNumber; page++) {
						requests.push(axios.get(createUrl(page)))
					}

					const responses = await Promise.all(requests)

					// append missing deposits
					for (const res of responses) {
						for (const server of servers) {
							depositData[server].hits.push(...res.data[server].hits)
						}
					}
				}
			} catch (_error) {
				console.error('Could not fetch deposits')
				return
			}

			// save results
			this.depositselectprops.deposits = depositData

			// update deposit list
			this.updateDespositList()
		},

		hasValidTokens() {
			if (this.tokens === null) {
				return false
			}
			let validTokenFound = false
			Object.keys(this.tokens).forEach((key) => {
				if (this.tokens[key] !== '') {
					validTokenFound = true
				}
			})
			return validTokenFound
		},

		validate(_event) {
			let isValid = true
			this.title.error = false
			this.title.helpertext = ''
			this.serverprops.error = false
			this.communityprops.error = false
			this.modeprops.error = false
			this.depositselectprops.error = false

			// run checks
			// check server
			if (this.serverprops.value === null) {
				isValid = false
				if (this.publish.pressedOnce) {
					this.serverprops.error = true
				}
			}

			// check mode
			if (this.modeprops.value === null) {
				isValid = false
				if (this.publish.pressedOnce) {
					this.modeprops.error = true
				}
			} else if (this.modeprops.value.id === 'attach') {
				// mode attach: check deposit selected
				if (this.depositselectprops.value === null) {
					isValid = false
					if (this.publish.pressedOnce) {
						this.depositselectprops.error = true
					}
				}
			} else if (this.modeprops.value.id === 'create') {
				// mode create: check community
				if (this.communityprops.value === null) {
					isValid = false
					if (this.publish.pressedOnce) {
						this.communityprops.error = true
					}
				}

				// mode create: check title
				if (this.title.text.length <= 3 || this.title.text.length > 128) {
					isValid = false
					this.title.success = false
					if (this.publish.pressedOnce) {
						// Note both can be false
						this.title.error = true
						this.title.helpertext = 'Please set a title with a length between 3 and 128 characters'
					}
				} else {
					this.title.error = false
					this.title.success = true
				}
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

			// update progress bar
			this.publish.publishTime = Date.now()
			this.publish.publishing = true

			let url = ''
			let props = {}
			if (this.modeprops.value.id === 'attach') {
				/* const draft = this.getCurrentDraft() */
				url = '/apps/b2sharebridge/attach'
				props = {
					ids: this.selectedFiles,
					draftId: this.depositselectprops.value.id,
					server_id: this.serverprops.value.id,
				}
			} else if (this.modeprops.value.id === 'create') {
				url = '/apps/b2sharebridge/publish'
				props = {
					ids: this.selectedFiles,
					community: this.communityprops.value.id,
					open_access: this.openAccess,
					title: this.title.text,
					server_id: this.serverprops.value.id,
				}
			} else {
				return
			}
			axios
				.post(
					generateUrl(url),
					props,
				)
				.then((response) => {
					this.info.heading = 'Transferring to B2SHARE'
					this.info.message = response.response.message
					this.info.buttons = [
						{
							label: 'Ok',
							type: 'secondary',
							callback: () => (this.closeModal()),
						},
						{
							label: 'Show Deposit',
							type: 'primary',
							callback: () => (this.redirect('/apps/b2sharebridge/?uploads')),
							href: generateUrl('/apps/b2sharebridge/?uploads'),
						},
					]
					this.publish.publishing = false
					this.publish.publishTime = null
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
					this.publish.publishing = false
					this.publish.publishTime = null
				})
		},

		closeModal() {
			this.showDialog = false
			this.info.message = ''
		},

		getProgressBar() {
			if (!this.publish.publishTime) {
				return 0
			}
			const seconds = (Date.now() - this.publish.publishTime) / 1000.0
			const progress = Math.max(Math.min(Math.round(seconds * 20), 99), 10)
			return progress
		},

		getPublishLabel() {
			if (!this.modeprops.value || this.modeprops.value.id === 'create') {
				return 'Publish'
			} else if (this.modeprops.value.id === 'attach') {
				return 'Attach'
			} else {
				console.error('Unknown mode, please tell an administrator')
				return 'Unknown'
			}
		},

		redirect(url) {
			this.$router.push(generateUrl(url))
		},

		/* getCurrentDraft() {
			if (this.serverprops.value && this.depositselectprops.value) {
				return this.depositselectprops.deposits[this.serverprops.value.id].hits.find((deposit) => deposit.id === this.depositselectprops.value.id)
			}
			return null
		}, */
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
