<template>
	<div class="bridgetoken">
		<h3>{{ name }}</h3>
		<a :href="url + '/user'">{{ url }}</a>
		<NcPasswordField :value.sync="mutable_token"
			label="Token"
			placeholder="Token"
			:minlength="60"
			:maxlength="60"
			:success="token.length === 60"
			:helper-text="token.length === 60 ? 'Token saved' : ''"
			@valid="saveToken"
			@update:value="saveToken" />
		<NcButton type="error" @click="deleteToken">
			Delete Token
		</NcButton>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { NcPasswordField, NcButton } from '@nextcloud/vue'

export default {
	name: 'TokenEditor',
	components: {
		NcPasswordField,
		NcButton,
	},
	model: {
		event: 'token-change',
	},
	props: {
		id: { required: true, type: Number },
		name: { required: true, type: String },
		url: { required: true, type: String },
		token: { default: '', type: String },
	},
	data() {
		return {
			mutable_token: this.token,
			helpertext: '',
		}
	},
	methods: {
		saveToken() {
			if (!this.canSave()) {
				return
			}
			if (this.mutable_token.length !== 60) {
				return
			}
			const data = {
				requesttoken: OC.requesttoken,
				token: this.mutable_token,
				serverid: this.id,
			}
			axios.post(generateUrl('/apps/b2sharebridge/apitoken'), data)
				.then((response) => {
					console.info('Saved token!')
					this.$emit('token-change', this.id)
				})
				.catch((error) => {
					console.error('Could not save token')
					console.debug(error)
				})
		},

		deleteToken() {
			axios.delete(generateUrl('/apps/b2sharebridge/apitoken/' + this.id))
				.then((response) => {
					console.info('Deleted token!')
					this.mutable_token = ''
					this.$emit('token-change', this.id)
				})
				.catch((error) => {
					console.error('Could not delete token')
					console.debug(error)
				})
		},

		canSave() {
			return this.mutable_token !== this.token && this.mutable_token !== ''
		},
	},
}
</script>
<style>
.bridgetoken {
	background: var(--color-main-background);
	padding: 10px;
	border-radius: var(--border-radius-large);
	margin-top: 2px;
	border: 5px solid var(--color-border);
}

.bridgetoken input {
	border-radius: var(--border-radius-large);
}

em.validation {
	align-self: center;
	grid-column: 2;
}

span.validation {
	display: grid;
	column-gap: 7px;
	grid-template-columns: 400px;
}
</style>
