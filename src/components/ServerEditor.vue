<template>
	<div class="bridgeserver">
		<div class="bridgeserver__input">
			<h4>{{ id == -1 ? "New Server" : mutable_name }}</h4>
			<NcTextField
				v-model="mutable_maxUploads"
				label="# of uploads per user at the same time"
				trailing-cutton-icon="close"
				:show-trailing-button="true"
				:success="parseInt(mutable_maxUploads) > 0"
				:error="isNaN(parseInt(mutable_maxUploads))"
				@trailing-button-click="mutable_maxUploads = 5">
				<template #icon>
					<UploadMultiple :size="20" />
				</template>
			</NcTextField>
			<NcTextField
				v-model="mutable_maxUploadFilesize"
				label="MB, maximum filesize per upload"
				trailing-cutton-icon="close"
				:show-trailing-button="true"
				:success="parseInt(mutable_maxUploadFilesize) > 0"
				:error="isNaN(parseInt(mutable_maxUploadFilesize))"
				@trailing-button-click="mutable_maxUploadFilesize = 512">
				<template #icon>
					<FileUploadOutline :size="20" />
				</template>
			</NcTextField>
			<NcCheckboxRadioSwitch :id="getCheckboxName" v-model="mutable_checkSsl">
				Check SSL
			</NcCheckboxRadioSwitch>
			<NcTextField
				v-model="mutable_publishUrl"
				label="Publish URL"
				trailing-cutton-icon="close"
				:show-trailing-button="true"
				:success="String(mutable_publishUrl).trim().startsWith('https://') && !String(mutable_publishUrl).trim().endsWith('/')"
				:error="String(mutable_publishUrl).length > 0 && (!String(mutable_publishUrl).trim().startsWith('https://') || String(mutable_publishUrl).trim().endsWith('/'))"
				@trailing-button-click="mutable_publishUrl = ''">
				<template #icon>
					<Web :size="20" />
				</template>
			</NcTextField>
			<NcTextField
				v-model="mutable_name"
				label="Your Server Name"
				trailing-cutton-icon="close"
				:show-trailing-button="true"
				:success="String(mutable_name).length > 0"
				@trailing-button-click="mutable_name = ''">
				<template #icon>
					<TagEditOutline :size="20" />
				</template>
			</NcTextField>
			<div class="bridgeserver__row bridgeserver__input__div">
				<p>B2SHARE API-version:</p>
				<NcSelect
					v-bind="version_options"
					v-model="mutable_version"
					class="bridgeserver__input__div__select"
					:label-outside="true"
					@update:model-value="updateVersion" />
			</div>
		</div>
		<div class="bridgeserver__buttons bridgeserver__row">
			<NcButton id="save" :disabled="!hasChanged()" @click="saveServer">
				Save
			</NcButton>
			<NcButton v-if="id !== -1" @click="deleteServer">
				Delete
			</NcButton>
			<NcButton
				v-if="id === -1"
				id="reset"
				:disabled="!hasChanged()"
				@click="resetProps">
				Reset
			</NcButton>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcCheckboxRadioSwitch, NcSelect, NcTextField } from '@nextcloud/vue'
import FileUploadOutline from 'vue-material-design-icons/FileUploadOutline.vue'
import TagEditOutline from 'vue-material-design-icons/TagEditOutline.vue'
import UploadMultiple from 'vue-material-design-icons/UploadMultiple.vue'
import Web from 'vue-material-design-icons/Web.vue'

export default {
	name: 'ServerEditor',
	components: {
		NcButton,
		NcSelect,
		NcTextField,
		NcCheckboxRadioSwitch,
		UploadMultiple,
		FileUploadOutline,
		TagEditOutline,
		Web,
	},

	props: {
		id: { type: Number, required: true },
		name: { default: '', required: false, type: String },
		publishurl: { default: '', required: false, type: String },
		maxuploads: { default: 5, type: Number },
		maxuploadfilesize: { default: 512, type: Number },
		checkssl: { default: false, type: Boolean },
		version: { default: 3, type: Number },
	},

	emits: ['server-change'],

	data() {
		return {
			mutable_name: this.name,
			mutable_publishUrl: this.publishurl,
			mutable_maxUploads: this.maxuploads,
			mutable_maxUploadFilesize: this.maxuploadfilesize,
			mutable_checkSsl: this.checkssl,
			mutable_version: this.version,
			version_options: {
				options: [2, 3],
			},
		}
	},

	computed: {
		getCheckboxName() {
			return 'checkSsl' + (this.id === -1 ? '' : this.id)
		},
	},

	methods: {
		resetProps() {
			this.mutable_name = ''
			this.mutable_publishUrl = ''
			this.mutable_maxUploads = 5
			this.mutable_maxUploadFilesize = 512
			this.mutable_checkSsl = false
			this.mutable_version = 3
		},

		hasChanged() {
			return this.mutable_name !== this.name
				|| this.mutable_publishUrl !== this.publishurl
				|| this.mutable_maxUploads !== this.maxuploads
				|| this.mutable_maxUploadFilesize !== this.maxuploadfilesize
				|| this.mutable_checkSsl !== this.checkssl
				|| this.mutable_version !== this.version
		},

		saveServer() {
			const data = {}
			if (this.id) {
				data.id = this.id
			}
			data.name = this.mutable_name
			data.publishUrl = this.stripTrailingSlash(this.mutable_publishUrl)
			this.mutable_publishUrl = data.publishUrl
			data.maxUploads = this.mutable_maxUploads
			data.maxUploadFilesize = this.mutable_maxUploadFilesize
			data.checkSsl = this.mutable_checkSsl
			data.version = this.mutable_version

			console.debug(JSON.stringify(data))

			axios.post(generateUrl('/apps/b2sharebridge/server'), { server: data })
				.then(() => {
					this.$emit('server-change', this.id === -1 ? 0 : this.id)
				})
				.catch((error) => {
					console.error('Could not save server')
					console.debug(error)
				})
		},

		deleteServer() {
			if (this.id) {
				axios.delete(generateUrl('/apps/b2sharebridge/servers/' + this.id))
					.then(() => {
						console.debug("Deleted server '" + this.name + "'")
						this.$emit('server-change', this.id)
					})
					.catch((error) => {
						console.error('Could not delete server')
						console.debug(error)
					})
			}
		},

		stripTrailingSlash(str) {
			str = str.trim()
			return str.endsWith('/')
				? str.slice(0, -1)
				: str
		},

		updateVersion(version) {
			this.mutable_version = version
		},
	},
}
</script>

<style lang="scss" scoped>
.bridgeserver {
	border-bottom: 1px solid var(--color-border);

	&__input {
		max-width: 600px;

		&__div {
			margin-top: 6px;
			p {
				margin-right: auto;
			}
			&__select {
				max-width: 600px;
			}
		}
	}

	&__row {
		display: flex;
		flex-direction: row;
		align-items: center;
	}

	&__buttons {
		max-width: 600px;
		margin-bottom: 10px;

		#save {
			margin-left: auto;
		}
	}
}
</style>
