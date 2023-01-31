<template>
	<div>
		<NcAppContent>
			<div id="b2shareBridgeTabView" class="dialogContainer">
				<div>
					<ValidationObserver ref="observer" v-slot="{ handleSubmit }">
						<b-form @submit.stop.prevent="handleSubmit(publishAction)">
							<ValidationProvider v-slot="validationContext"
								name="Title"
								:rules="{ required: true, min: 3 }">
								<b-form-group label-cols="3"
									label-cols-lg="sm"
									label="Deposit Title:"
									label-for="b2s_title">
									<b-form-input id="b2s_title"
										v-model="deposit_title"
										placeholder="Deposit title"
										:state="getValidationState(validationContext)" />
									<b-form-invalid-feedback id="input-1-live-feedback">
										{{
											validationContext.errors[0]
										}}
									</b-form-invalid-feedback>
								</b-form-group>
							</ValidationProvider>
							<ValidationProvider v-slot="validationContext"
								name="Server"
								:rules="{ required: true }">
								<b-form-group label-cols="3"
									label-cols-lg="sm"
									label="Server:"
									label-for="b2s_server">
									<b-form-select id="b2s_server"
										v-model="server_selected"
										:options="server_options"
										:state="getValidationState(validationContext)"
										@change="onChangeServer" />
									<b-form-invalid-feedback id="input-2-live-feedback">
										{{
											validationContext.errors[0]
										}}
									</b-form-invalid-feedback>
								</b-form-group>
							</ValidationProvider>
							<ValidationProvider v-slot="validationContext"
								name="Community"
								:rules="{ required: true }">
								<b-form-group label-cols="3"
									label-cols-lg="sm"
									label="Community:"
									label-for="b2s_community">
									<b-form-select id="b2s_community"
										v-model="community_selected"
										label="Community:"
										:options="community_options"
										:state="getValidationState(validationContext)" />
									<b-form-invalid-feedback id="input-3-live-feedback">
										{{
											validationContext.errors[0]
										}}
									</b-form-invalid-feedback>
								</b-form-group>
							</ValidationProvider>
							<b-form-group label-cols="3"
								label-cols-lg="sm"
								label="Open access:"
								label-for="cbopen_access">
								<b-form-checkbox id="cbopen_access"
									v-model="checkbox_status"
									label="Open access:"
									type="checkbox"
									name="open_access"
									size="lg" />
							</b-form-group>
							<b-btn id="publish_button"
								variant="primary"
								type="submit"
								:disabled="publishDisabled">
								Publish
							</b-btn>
						</b-form>
					</ValidationObserver>
				</div>
				<div v-if="tokens === null" id="b2sharebridge_errormsg" class="errormsg">
					Please set your B2SHARE API token <a href="/settings/user/b2sharebridge">here</a>
				</div>
				<div v-if="errormessage !== null" id="b2sharebridge_errormsg" class="errormsg">
					{{ errormessage }}
				</div>
			</div>
		</NcAppContent>
	</div>
</template>
<script>
import {
	NcAppContent,
} from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { ValidationProvider, ValidationObserver, extend } from 'vee-validate'
import 'bootstrap/dist/css/bootstrap.min.css'
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
		NcAppContent,
		ValidationObserver,
		ValidationProvider,
	},
	data() {
		return {
			publishDisabled: false,
			communities: [],
			servers: [],
			server_selected: null,
			server_options: [
				{ value: null, text: 'No servers found!' },
			],
			community_selected: null,
			community_options: [
				{ value: null, text: 'No communities found!' },
			],
			checkbox_status: false,
			deposit_title: '',
			tokens: null,
			fileInfo: null,
			errormessage: null,
		}
	},

	async mounted() {
		await this.loadServers()
		await this.loadCommunities()
		if (this.servers.length !== 0) {
			this.server_options = []
			this.servers.forEach(server => {
				this.server_options.push(new Object({
					value: server.id,
					text: server.name,
				}))
			})
			// TODO auto select if only one server is available
		}
		await this.loadTokens()
	},

	methods: {
		/**
		 * Submit deposit to B2SHARE
		 */
		async publishAction() {
			const selectedFiles = FileList.getSelectedFiles()

			// if selectedFiles is empty, use fileInfo
			// otherwise create an array of files from the selection
			let ids
			if (selectedFiles.length > 0) {
				ids = []
				for (const index in selectedFiles) {
					ids.push(selectedFiles[index].id)
				}
			} else {
				ids = [this.fileInfo.id]
			}

			const result = await axios
				.post(generateUrl('/apps/b2sharebridge/publish'),
					{
						ids,
						community: this.community_selected,
						open_access: this.checkbox_status,
						title: this.deposit_title,
						server_id: this.server_selected,
					})
				.then(() => {
					this.publishDisabled = true
				})
				.catch((error) => {
					if (error.response) {
						if (error.response.status === 413 // entity too large
                  || error.response.status === 429) { // too many uploads
							this.errormessage = error.response.data.message
						}
					}
					console.log(error)
				})
			if (result && result.status === 'success') {
				OC.dialogs.info(
					t('b2sharebridge', result.message),
					t('b2sharebridge', 'Info'))
			}
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
					console.log(response)
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
					console.log(response)
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
					console.log('Loaded tokens!')
					this.tokens = response.data
				})
				.catch((error) => {
					console.error('Fetching tokens failed!')
					console.error(error)
				})
		},

		// Events
		onChangeServer() {
			if (this.server_selected !== null) {
				this.community_options = []
				console.log(this.server_selected)
				this.communities.forEach((community) => {
					if (community.hasOwnProperty('serverId') && parseInt(community.serverId) === parseInt(this.server_selected)) {
						this.community_options.push(new Object({
							value: community.id,
							text: community.name,
						}))
					}
				})
			}
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
			axios.get(generateUrl(url_path)) // TODO process errors with then
		},

		// VeeValidate
		getValidationState({ dirty, validated, valid = null }) {
			return dirty || validated ? valid : null
		},
	},
}
</script>

<style scoped>
#tab-b2sharebridge {
	height: 100%;
	padding: 0;
}

label.col-auto {
	width: 25%;
}

#publish_button {
	margin-left: 3px;
	width: 25%;
}

input.is-valid, select.is-valid {
	outline-color: rgb(37, 156, 64);
	border: 2px solid rgb(37, 156, 64);
}

input.is-valid:focus, input.is-invalid:hover, select.is-valid:focus {
	box-shadow: rgba(32, 134, 55, 0.25) 0 0 0 0.2rem;
	border-color: rgb(37, 156, 64);
}

input.is-invalid, select.is-invalid {
	outline-color: rgb(148, 26, 37);
	border: 2px solid rgb(148, 26, 37);
}

input.is-invalid:focus, select.is-invalid:focus {
	box-shadow: rgba(165, 29, 42, 0.25) 0 0 0 0.2rem;
	border-color: rgb(148, 26, 37);
}

div.invalid-feedback div.errormsg{
	color: rgb(148, 26, 37);
}

</style>
