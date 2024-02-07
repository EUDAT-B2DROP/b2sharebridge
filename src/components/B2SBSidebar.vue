<template>
	<b-modal id="bridgedial" v-model="isModalOpen" title="Create a B2SHARE deposit" ok-title="Publish" @ok="handleOk"
		:ok-disabled="publishDisabled">
		<h1> Create a B2SHARE deposit</h1>
		<p>TODO List selected files here</p>
		<div id="b2shareBridgeTabView" class="dialogContainer">
			<div v-if="tokens === null && loaded_sidebar" id="b2sharebridge_errormsg" style="color: red;">
				Please set your B2SHARE API token <a href="/settings/user/b2sharebridge">here</a>
			</div>
			<div v-else-if="loaded_sidebar">
				<ValidationObserver tag="form" ref="observer" v-slot="{ handleSubmit }" @submit.prevent="handleSubmit(publishAction)">
					<ValidationProvider v-slot="validationContext" name="Title" :rules="{ required: true, min: 3 }">
						<b-form-group label-cols="3" label-cols-lg="sm" label="Deposit Title:" label-for="b2s_title">
							<b-form-input id="b2s_title" v-model="deposit_title" placeholder="Deposit title"
								:state="getValidationState(validationContext)" />
							<b-form-invalid-feedback id="input-1-live-feedback">
								{{
									validationContext.errors[0]
								}}
							</b-form-invalid-feedback>
						</b-form-group>
					</ValidationProvider>
					<ValidationProvider v-slot="validationContext" name="Server" :rules="{ required: true }">
						<b-form-group label-cols="3" label-cols-lg="sm" label="Server:" label-for="b2s_server">
							<b-form-select id="b2s_server" v-model="server_selected" :options="server_options"
								:state="getValidationState(validationContext)" @change="onChangeServer" />
							<b-form-invalid-feedback id="input-2-live-feedback">
								{{
									validationContext.errors[0]
								}}
							</b-form-invalid-feedback>
						</b-form-group>
					</ValidationProvider>
					<ValidationProvider v-slot="validationContext" name="Community" :rules="{ required: true }">
						<b-form-group label-cols="3" label-cols-lg="sm" label="Community:" label-for="b2s_community">
							<b-form-select id="b2s_community" v-model="community_selected" label="Community:"
								:options="community_options" :state="getValidationState(validationContext)" />
							<b-form-invalid-feedback id="input-3-live-feedback">
								{{
									validationContext.errors[0]
								}}
							</b-form-invalid-feedback>
						</b-form-group>
					</ValidationProvider>
					<b-form-group label-cols="3" label-cols-lg="sm" label="Open access:" label-for="cbopen_access">
						<b-form-checkbox id="cbopen_access" v-model="checkbox_status" label="Open access:" type="checkbox"
							name="open_access" size="lg" />
					</b-form-group>
					<!-- <b-btn id="publish_button"
									type="submit"
									:disabled="publishDisabled">
									Publish
								</b-btn> -->

				</ValidationObserver>
			</div>
		</div>
		<b-modal v-if="errormessage !== null" id="error_modal" v-model="showErrorModal" title="B2SHARE" ok-only
			header-bg-variant="danger" header-text-variant="light">
			<div>
				<span v-html="errormessage"></span>
			</div>
		</b-modal>
		<b-modal id="published_modal" v-model="showPublishedModal" title="B2SHARE" ok-only @close="handleOkPublished"
			@ok="handleOkPublished" header-class="b2share-modal-header">
			<div>
				<p class="my-4">
					Transferring file to B2SHARE in the background.
				</p>
				<p>
					Click <a href="/apps/b2sharebridge">here</a> to review the deposit status or edit your draft after the
					transfer.
				</p>
			</div>
		</b-modal>
	</b-modal>
</template>
<script>
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { ValidationProvider, ValidationObserver, extend } from 'vee-validate'

//css
import '../../css/style.scss'
/* DO NOT IMPORT ALL OF BOOTSTRAP, IT BREAKS NEXTCLOUD */
// import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.min.js'
import 'bootstrap-vue/dist/bootstrap-vue.css'


/**
 * Validation rules
 */
extend('min', {
	validate(value, args) {
		return value.length >= args.length
	},
	params: ['length'],
	message: (fieldName, placeholders) => {
		return `The ${fieldName} must have at least ${placeholders.length} characters`
	},
})

extend('required', {
	validate(value) {
		return {
			required: true,
			valid: ['', null, undefined].indexOf(value) === -1,
		}
	},
	computesRequired: true,
	message: (fieldName) => {
		return `Please enter a valid ${fieldName}`
	},
})

export default {
	name: 'B2sharebridgeSidebar',
	components: {
		//NcAppContent,
		//FilePicker,
		ValidationObserver,
		ValidationProvider,
	},
	data() {
		return {
			isModalOpen: true,
			publishDisabled: false,
			showPublishedModal: false,
			showErrorModal: false,
			communities: [],
			servers: [],
			server_selected: null,
			server_options: [
				{ value: null, text: 'Please select a server', disabled: true },
			],
			community_selected: null,
			community_options: [
				{ value: null, text: 'Please select a community', disabled: true },
			],
			checkbox_status: false,
			deposit_title: '',
			tokens: null,
			fileInfo: null,
			errormessage: null,
			loaded_sidebar: false,
			selectedFiles: [],
		}
	},

	async mounted() {
		const server_promise = this.loadServers()
		const community_promise = this.loadCommunities()
		const token_promise = this.loadTokens()
		await server_promise
		await community_promise
		await token_promise

		if (!this.hasValidTokens()) {
			this.tokens = null
			this.errormessage = 'Please set your B2SHARE API token <a href="/settings/user/b2sharebridge">here</a>'
			this.loaded_sidebar = true
			return
		}

		if (this.servers.length !== Object.keys(this.tokens).length) {
			console.error('Number of servers and tokens differ, please contact an administrator')
			this.tokens = null
			this.loaded_sidebar = true
			return
		}

		if (this.servers.length !== 0) {
			this.server_options = [{ value: null, text: 'Please select a server', disabled: true }]

			this.servers.forEach(server => {
				const disabled = this.tokens[server.id] === ''
				this.server_options.push(new Object({
					value: server.id,
					text: server.name,
					disabled,
				}))
				if (!disabled && this.server_selected === null) {
					console.debug('Selected server automatically')
					this.server_selected = server.id
					this.onChangeServer() // update communities
				}
			})
		}

		this.loaded_sidebar = true
	},

	async activated() {

	},

	methods: {
		/**
		* Only triggers, if the sidebar is visible for the first time
		*/
		visibleHandlerOnce(isVisible) {
			if (isVisible) {
				if (!this.hasValidTokens()) {
					this.showErrorModal = false
					return
				}
			}
		},

		handleOk(bvModalEvent) {
			bvModalEvent.preventDefault()
			this.publishAction()
		},

		handleOkPublished() {
			this.$bvModal.hide()
			this.$emit("close")
			this.isModalOpen = false
		},
		/**
		 * Submit deposit to B2SHARE
		 */
		async publishAction() {
			this.publishDisabled = await this.$refs.observer.validate()
			
			if (!this.publishDisabled)
			{
				return
			}
			if (!this.selectedFiles.length) {
				console.error("No files selected")
				return
			}
			//this.initializeB2ShareUI(this.selectedFiles[0])

			axios
				.post(generateUrl('/apps/b2sharebridge/publish'),
					{
						ids: this.selectedFiles,
						community: this.community_selected,
						open_access: this.checkbox_status,
						title: this.deposit_title,
						server_id: this.server_selected,
					})
				.then(() => {
					this.showPublishedModal = true
				})
				.catch((error) => {
					if (error.response) {
						if (error.response.status === 413 // entity too large
							|| error.response.status === 429) { // too many uploads
							this.errormessage = '<p>' + error.response.data.message + '</p>'
							this.showErrorModal = true
						}
					}
					this.publishDisabled = false
					console.error(error)
				})

		},

		// API stuff
		loadServers() {
			const url_path
				= '/apps/b2sharebridge/servers?requesttoken='
				+ encodeURIComponent(OC.requestToken)

			return axios
				.get(generateUrl(url_path))
				.then((response) => {
					console.log('Loaded servers:')
					console.debug(response)
					this.servers = response.data
				})
				.catch((error) => {
					console.error('Fetching B2SHARE servers failed!')
					console.error(error)
				})
		},

		loadCommunities() {
			const url_path
				= '/apps/b2sharebridge/gettabviewcontent?requesttoken='
				+ encodeURIComponent(OC.requestToken)

			return axios
				.get(generateUrl(url_path))
				.then((response) => {
					console.log('Loaded communities:')
					console.debug(response)
					this.communities = response.data
				})
				.catch((error) => {
					console.error('Fetching B2SHARE communities failed!')
					console.error(error)
				})
		},

		loadTokens() {
			const url_path
				= '/apps/b2sharebridge/apitoken?requesttoken='
				+ encodeURIComponent(OC.requestToken)

			return axios
				.get(generateUrl(url_path))
				.then((response) => {
					console.log('Successfully requested tokens!')
					console.debug(response.data)
					if (response.data) {
						this.tokens = response.data
					} else {
						console.info('No token set!')
					}
				})
				.catch((error) => {
					console.error('Fetching tokens failed!')
					console.error(error)
				})
		},

		// Events
		onChangeServer() {
			if (this.server_selected !== null) {
				this.community_options = [{ value: null, text: 'Please select a community', disabled: true }]
				this.communities.forEach((community, index) => {
					if (community.hasOwnProperty('serverId') && parseInt(community.serverId) === parseInt(this.server_selected)) {
						this.community_options.push(new Object({
							value: community.id,
							text: community.name,
						}))
						if (community.name === 'EUDAT') { // TODO make this configurable
							console.debug('Automatically selected EUDAT as community')
							this.community_selected = community.id
						}
					}
				})
			}
		},

		hasValidTokens() {
			if (this.tokens === null) {
				return false
			}
			let valid_token_found = false
			Object.keys(this.tokens).forEach(key => {
				if (this.tokens[key] !== '') {
					valid_token_found = true
				}
			})
			return valid_token_found
		},

		/**
		 * Returns true for files, false for folders.
		 *
		 * @param fileInfo
		 * @return {boolean} true for files, false for folders
		 */
		canDisplay(fileInfo) {
			if (!fileInfo) {
				return false
			}
			return !fileInfo.isDirectory()
		},

		initializeB2ShareUI(fileInfo) {
			const url_path
				= '/apps/b2sharebridge/initializeb2shareui?requesttoken='
				+ encodeURIComponent(OC.requestToken) + '&file_id='
				+ encodeURIComponent(fileInfo.id)
			this.fileInfo = fileInfo
			axios.get(generateUrl(url_path))
				.catch((error) => {
					if (error.data && 'error_msg' in error.data) {
						this.errormessage = '<p>' + error.response.data.error_msg + '</p>'
						this.showErrorModal = true
					}
					console.error(error)
				})
		},

		// VeeValidate
		getValidationState({ dirty, validated, valid = null }) {
			return dirty || validated ? valid : null
		},
	},

	computed: {
		filepickerOptions() {
			return {
				allowPickDirectory: false,
				buttons: this.buttonFactory,
				container: `#${this.containerId}`,
				multiselect: false,
				name: t('files', 'Select file or folder to link to'),
			}
		},
	},
}
</script>

<style scoped>
#tab-b2sharebridge {
	height: 100%;
	padding: 0;
}

#bridgedial {
	padding-block: 4px 8px;
	padding-inline: 12px 8px;
	max-width: 90%;
	width: 900px;
	max-height: min(90%, 100% - 100px);
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	transition: transform .3s ease;
	border-radius: var(--border-radius-large);
	background-color: var(--color-main-background);
	color: var(--color-main-text);
	box-shadow: 0 0 40px #0003;
}

label.col-auto {
	width: 25%;
}

#publish_button {
	margin-left: 3px;
	width: 25%;
	background-color: rgb(26, 48, 99);
	color: white;
}

input.is-valid,
select.is-valid {
	outline-color: rgb(37, 156, 64);
	border: 2px solid rgb(37, 156, 64);
}

input.is-valid:focus,
input.is-invalid:hover,
select.is-valid:focus {
	box-shadow: rgba(32, 134, 55, 0.25) 0 0 0 0.2rem;
	border-color: rgb(37, 156, 64);
}

input.is-invalid,
select.is-invalid {
	outline-color: rgb(148, 26, 37);
	border: 2px solid rgb(148, 26, 37);
}

input.is-invalid:focus,
select.is-invalid:focus {
	box-shadow: rgba(165, 29, 42, 0.25) 0 0 0 0.2rem;
	border-color: rgb(148, 26, 37);
}

div.invalid-feedback {
	color: rgb(148, 26, 37);
}
</style>
