<template>
	<div class="bridgetoken">
		<h3>{{ name }}</h3>
		<div class="bridgetoken__fields">
			<a :href="url + '/user'">{{ url }}</a>
			<NcPasswordField
				v-model="mutable_token"
				label="Token"
				placeholder="Token"
				:minlength="60"
				:maxlength="60"
				:success="getSuccess()"
				:error="getError()"
				:helperText="getHelperText()"
				@valid="saveToken"
				@update:modelValue="saveToken" />
			<div class="bridgetoken__fields__buttons">
				<NcButton variant="error" aria-label="Delete Token" @click="deleteToken">
					Delete Token
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcPasswordField } from '@nextcloud/vue'

export default {
	name: 'TokenEditor',
	components: {
		NcPasswordField,
		NcButton,
	},

	props: {
		id: { required: true, type: Number },
		name: { required: true, type: String },
		url: { required: true, type: String },
		token: { default: '', type: String },
	},

	emits: ['tokenChange'],

	data() {
		return {
			mutable_token: this.token,
			helpertext: '',
			token_validated: null,
		}
	},

	async mounted() {
		if (this.mutable_token.length === 60) {
			const data = {
				requesttoken: OC.requesttoken,
				token: this.mutable_token,
				serverid: this.id,
			}
			axios.post(generateUrl('/apps/b2sharebridge/apitoken'), data)
				.then(() => {
					console.info('Saved token!')
					this.$emit('tokenChange', this.id)
					this.token_validated = true
				})
				.catch((error) => {
					console.error('Could not save token')
					console.debug(error)
					this.token_validated = false
				})
		}
	},

	methods: {
		saveToken() {
			if (!this.canSave()) {
				return
			}
			if (this.mutable_token.length !== 60) {
				console.debug('token has wrong length!', this.mutable_token.length)
				return
			}
			const data = {
				requesttoken: OC.requesttoken,
				token: this.mutable_token,
				serverid: this.id,
			}
			axios.post(generateUrl('/apps/b2sharebridge/apitoken'), data)
				.then(() => {
					console.info('Saved token!')
					this.$emit('tokenChange', this.id)
					this.token_validated = true
				})
				.catch((error) => {
					console.error('Could not save token')
					console.debug(error)
					this.token_validated = false
				})
		},

		getHelperText() {
			if (this.mutable_token.length === 60) {
				if (!this.token_validated) {
					return 'Server token validation failed! Please create a new token!'
				}
				return 'Token saved and validated'
			} else if (this.mutable_token.length === 0) {
				return 'Copy your token from b2share'
			}
			return 'A token has 60 characters'
		},

		getSuccess() {
			return this.mutable_token.length === 60 && this.token_validated
		},

		getError() {
			return this.mutable_token.length !== 0 && !this.token_validated
		},

		deleteToken() {
			axios.delete(generateUrl('/apps/b2sharebridge/apitoken/' + this.id))
				.then(() => {
					console.info('Deleted token!')
					this.mutable_token = ''
					this.token_validated = null
					this.$emit('tokenChange', this.id)
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

<style lang="scss" scoped>
.bridgetoken {
	border-bottom: 1px solid var(--color-border);

	&__fields {
		max-width: 600px;
		margin-bottom: 10px;

		&__buttons {
			display: flex;
			flex-direction: column;
			align-items: end;
		}
	}
}
</style>
