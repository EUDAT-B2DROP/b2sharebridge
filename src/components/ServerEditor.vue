<template>
	<div class="server_form">
		<h4>{{ id == null ? "New Server" : mutable_name }}</h4>
		<p id="maxB2shareUploadsPerUser">
			<input v-model="mutable_maxUploads"
				title="max_uploads"
				type="text"
				name="max_uploads"
				placeholder="5"
				style="width: 400px">
			<em># of uploads per user at the same time</em>
		</p>
		<p id="maxB2shareUploadSizePerFile">
			<input v-model="mutable_maxUploadFilesize"
				title="max_upload_filesize"
				type="text"
				name="max_upload_filesize"
				placeholder="512"
				style="width: 400px">
			<em>MB maximum filesize per upload</em>
		</p>
		<p>
			<input :id="getCheckboxName"
				v-model="mutable_checkSsl"
				type="checkbox"
				:name="getCheckboxName"
				class="checkbox"
				:checked="mutable_checkSsl"
				style="width: 400px">
			<label :for="getCheckboxName">Check SSL</label>
		</p>
		<p id="b2shareUrlField">
			<input v-model="mutable_publishUrl"
				title="publishurl"
				type="text"
				name="publish_baseurl"
				placeholder="https://b2share.eudat.eu"
				style="width: 400px">
			<em>Publish URL</em>
		</p>
		<p id="b2shareNameField">
			<input v-model="mutable_name"
				title="name"
				type="text"
				name="name"
				style="width: 400px"
				placeholder="Your Server Name">
			<em>Server name</em>
		</p>
		<button id="save" :disabled="!hasChanged()" @click="saveServer">
			Save
		</button>
		<button v-if="id !== null" @click="deleteServer">
			Delete
		</button>
		<button v-if="id === null"
			id="reset"
			:disabled="!hasChanged()"
			@click="resetProps">
			Reset
		</button>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'ServerEditor',
	model: {
		event: 'server-change',
	},
	props: {
		id: { default: null, type: Number },
		name: { required: false, type: String },
		publishurl: { required: false, type: String },
		maxuploads: { default: 5, type: Number },
		maxuploadfilesize: { default: 512, type: Number },
		checkssl: { default: false, type: Boolean },
	},
	data() {
		return {
			mutable_name: this.name,
			mutable_publishUrl: this.publishurl,
			mutable_maxUploads: this.maxuploads,
			mutable_maxUploadFilesize: this.maxuploadfilesize,
			mutable_checkSsl: this.checkssl,
		}
	},
	computed: {
		getCheckboxName() {
			return 'checkSsl' + (this.id === null ? '' : this.id)
		},
	},
	methods: {
		resetProps() {
			this.mutable_name = null
			this.mutable_publishUrl = null
			this.mutable_maxUploads = 5
			this.mutable_maxUploadFilesize = 512
			this.mutable_checkSsl = false
		},

		hasChanged() {
			return this.mutable_name !== this.name
          || this.mutable_publishUrl !== this.publishurl
          || this.mutable_maxUploads !== this.maxuploads
          || this.mutable_maxUploadFilesize !== this.maxuploadfilesize
          || this.mutable_checkSsl !== this.checkssl
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
			console.debug(JSON.stringify(data))
			axios.post(generateUrl('/apps/b2sharebridge/server'), { server: data })
				.then((response) => {
					this.$emit('server-change', this.id === null ? 0 : this.id)
				})
				.catch((error) => {
					console.error('Could not save server')
					console.debug(error)
				})
		},

		deleteServer() {
			if (this.id) {
				axios.delete(generateUrl('/apps/b2sharebridge/servers/' + this.id))
					.then((response) => {
						console.log("Deleted server '" + this.name + "'")
						this.$emit('server-change', this.id)
					})
					.catch((error) => {
						console.error('Could not delete server')
						console.debug(error)
					})
			}
		},

		stripTrailingSlash(str) {
			return str.endsWith('/')
				? str.slice(0, -1)
				: str
		},
	},
}
</script>

<style scoped>
div.server_form {
	background: rgba(128, 128, 128, 0.1);
	padding: 10px;
	border-radius: 20px;
	margin-top: 2px;
}
</style>
