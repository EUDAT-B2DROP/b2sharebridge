<template>
	<div class="bridgetoken">
		<h3>{{ name }}</h3>
		<ValidationObserver>
			<b-form @submit.prevent.stop="saveToken">
				<p id="b2shareUrlField">
					<input v-model="url" title="publish_baseurl" type="text" style="width: 400px" disabled
						class="publish_url">
					<em>External publishing</em>
				</p>
				<p id="b2shareAPITokenField">
					<ValidationProvider v-slot="validationContext" name="token" rules="required|tokenrule"
						:vid="'prov_' + id" class="validation">
						<b-form-input v-model="mutable_token" title="b2share API token" type="text"
							placeholder="Your API token" name="b2share_apitoken" style="width: 400px; grid-column: 1"
							class="form-control" :state="getValidationState(validationContext)" />
						<em class="validation">B2Share API token</em>
						<b-form-invalid-feedback id="input-1-live-feedback" style="grid-row: 2">
							{{
								validationContext.errors[0]
							}}
						</b-form-invalid-feedback>
					</ValidationProvider>
				</p>
				<p id="b2shareManageAPIToken">
					<button type="submit" :disabled="!canSave()" @click="saveToken">
						Save B2SHARE API Token
					</button>
					<button :disabled="token === ''" @click="deleteToken">
						Delete B2SHARE API Token
					</button>
				</p>
			</b-form>
		</ValidationObserver>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { configure, extend, ValidationObserver, ValidationProvider } from 'vee-validate'

import '../../css/style.scss'
import 'bootstrap-vue/dist/bootstrap-vue.css'

extend('tokenrule', {
	validate(value) {
		return {
			required: true,
			valid: value.length === 60,
		}
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

const config = {
	classes: {
		valid: 'is-valid',
		invalid: 'is-invalid',
	},
	bails: true,
	skipOptional: true,
	mode: 'aggressive',
	useConstraintAttrs: true,
}
configure(config)

export default {
	name: 'TokenEditor',
	components: {
		ValidationObserver,
		ValidationProvider,
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
		}
	},
	methods: {
		saveToken() {
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

		// VeeValidate
		getValidationState({ dirty, validated, valid = null }) {
			return dirty || validated ? valid : null
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
