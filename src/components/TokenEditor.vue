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
				:success="token.length === 60"
				:helper-text="token.length === 60 ? 'Token saved' : ''"
				@valid="saveToken"
				@update:model-value="saveToken" />
			<div class="bridgetoken__fields__buttons">
				<NcButton type="error" aria-label="Delete Token" @click="deleteToken">
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

	emits: ['token-change'],

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
					this.$emit('token-change', this.id)
				})
				.catch((error) => {
					console.error('Could not save token')
					console.debug(error)
				})
		},

		deleteToken() {
			axios.delete(generateUrl('/apps/b2sharebridge/apitoken/' + this.id))
				.then(() => {
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
