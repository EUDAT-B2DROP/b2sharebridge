<template>
	<div class="rp">
		<!-- Head of Listing, with selectors: -->
		<div class="rp__selectors__top">
			<p>{{ getResultsOverview() }}</p>

			<div class="rp__selectors__select">
				<p>B2SHARE instance:</p>
				<NcSelect
					v-bind="selectedServer"
					v-model="selectedServer.value"
					:labelOutside="true"
					:options="selectedServer.options"
					@update:modelValue="updateServer" />
			</div>
			<div class="rp__selectors__select">
				<p>Page Size:</p>
				<NcSelect
					v-bind="selectPageSize"
					v-model="selectPageSize.value"
					:labelOutside="true"
					:options="selectPageSize.options"
					@update:modelValue="updatePageSize" />
			</div>
			<PageButtons
				class="rp__selectors__pagebuttons"
				:page="page"
				:numPages="getNumPages()"
				@pageUpdate="updatePage" />
		</div>
		<!-- Listed Records: -->
		<div class="rp__records">
			<template v-for="record in getRecords()" :key="record['id']">
				<div class="rp__records__record">
					<a :href="getLink(record)" target="_blank">
						<div class="rp__records__record__info">
							<p class="rp__records__record__title">{{ getTitle(record) }}</p>
							<p class="rp__records__record__date">{{ getDate(record) }}</p>
						</div>
					</a>
					<div class="rp__records__record__buttons">
						<NcButton
							v-if="!draft"
							class="rp__records__record__buttons__next"
							aria-label="Create a new Version"
							target="_blank"
							@click="nextVersion(record)">
							<template #icon>
								<Plus :size="30" />
							</template>
							New Version
						</NcButton>
						<NcButton
							v-if="draft"
							class="rp__records__record__buttons__publish"
							aria-label="publish draft to B2SHARE"
							:href="getLink(record)"
							target="_blank">
							<template #icon>
								<EarthArrowUp :size="30" />
							</template>
							Publish
						</NcButton>
						<NcButton
							class="rp__records__record__buttons__download"
							aria-label="Download B2SHARE contents to B2DROP"
							@click="downloadRecord(record)">
							<template #icon>
								<CloudDownload :size="30" />
							</template>
						</NcButton>
						<NcButton
							v-if="draft"
							class="rp__records__record__buttons__delete"
							aria-label="Delete Draft"
							@click="showDeleteModal(record)">
							<template #icon>
								<TrashCan :size="30" />
							</template>
						</NcButton>
					</div>
				</div>
			</template>
			<!-- No records default text: -->
			<div v-if="!getNumRecords() && getHasToken()" class="rp__records__none">
				<h3 v-if="draft">
					You don't have any drafts at <a :href="getServerUrl()">{{ getServerLabel() }}</a>
				</h3>
				<h3 v-else>
					You don't have any publications at <a :href="getServerUrl()">{{ getServerLabel() }}</a>
				</h3>
				<h3>
					You can upload files from B2DROP by selecting them and pressing the <b>B2SHARE</b> button &#128640;
				</h3>
			</div>
			<!-- No token default text: -->
			<div v-if="!getHasToken()" class="rp__records__none">
				<h3>
					You don't have any (or any valid) token for <a :href="getServerUrl()">{{ getServerLabel() }}</a>
				</h3>
				<div>
					<p>Please set a valid API token</p>
					<NcButton
						label="Set API Token"
						:href="getSettingsUrl()"
						type="Primary"
						areaLabel="Set API Token"
						@click="redirect('/settings/user/b2sharebridge')">
						"Set API Token"
					</NcButton>
				</div>
			</div>
		</div>
		<!-- Bottom of Listing, with less selectors: -->
		<div class="rp__selectors__bottom">
			<p>{{ getResultsOverview() }}</p>
			<PageButtons
				class="rp__selectors__pagebuttons"
				:page="page"
				:numPages="getNumPages()"
				@pageUpdate="updatePage" />
		</div>
		<!-- Modals -->
		<NcModal v-if="modalDelete.show" name="DeleteModal" @close="closeModals">
			<div class="rp__modal__delete">
				<h2>Delete Draft</h2>
				<p>Are you sure you want to delete "{{ getTitle(modalDelete.record) }}"?</p>
				<span class="rp__modal__delete__buttons">
					<NcButton aria-label="Cancel" @click="closeModals">
						Cancel
					</NcButton>
					<NcButton variant="primary" aria-label="Yes" @click="deleteRecord(modalDelete.record)">
						Yes
					</NcButton>
				</span>
			</div>
		</NcModal>
		<NcModal v-if="modalDownload.show" name="DownloadModal" @close="closeModals">
			<div class="rp__modal__download">
				<h2>{{ modalDownload.title }}</h2>
				<p>{{ modalDownload.message }}</p>
				<p v-if="modalDownload.code !== 0" class="rp__modal__download__devdesc">
					(Status code for Developers: {{
						modalDownload.code }})
				</p>
				<NcButton aria-label="Cancel" @click="closeModals">
					Ok
				</NcButton>
			</div>
		</NcModal>
		<NcModal v-if="modalVersion.show" name="VersionModal" @close="closeModals">
			<div class="rp__modal__version">
				<h2>{{ modalVersion.title }}</h2>
				<p>{{ modalVersion.message }}</p>
				<span class="rp__modal__version__buttons">
					<NcButton v-if="modalVersion.success" aria-label="Cancel" @click="closeModals">
						Cancel
					</NcButton>
					<NcButton
						v-if="modalVersion.success"
						variant="primary"
						aria-label="Yes"
						@click="changeStatus('draft')">
						Ok
					</NcButton>
					<NcButton
						v-else
						variant="primary"
						aria-label="Yes"
						@click="closeModals">
						Ok
					</NcButton>
				</span>
			</div>
		</NcModal>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcModal, NcSelect } from '@nextcloud/vue'
import CloudDownload from 'vue-material-design-icons/CloudDownload.vue'
import EarthArrowUp from 'vue-material-design-icons/EarthArrowUp.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import TrashCan from 'vue-material-design-icons/TrashCan.vue'
import PageButtons from './PageButtons.vue'

export default {
	name: 'RecordsPages',
	components: {
		NcButton,
		NcSelect,
		NcModal,
		PageButtons,
		CloudDownload,
		TrashCan,
		EarthArrowUp,
		Plus,
	},

	props: {
		records: {
			type: Object,
			required: true,
		},

		draft: {
			type: Boolean,
			required: true,
		},

		page: {
			type: Number,
			required: true,
		},

		pageSize: {
			type: Number,
			required: true,
		},

		serverId: {
			type: Number,
			required: false,
			default: null,
		},
	},

	emits: ['pageSizeUpdate', 'pageUpdate', 'serverUpdate', 'refresh', 'status'],

	data() {
		return {
			sortBy: 'createdAt',
			sortDir: 'desc',
			selectPageSize: {
				options: [
					'10',
					'25',
					'50',
				],

				value: String(this.pageSize),
			},

			selectedServer: {
				options: [],
				value: {
					id: this.serverId,
					label: this.serverId === null ? 'None' : this.records[this.serverId].server_name,
				},
			},

			modalDelete: {
				show: false,
				record: null,
			},

			modalDownload: {
				show: false,
				record: null,
				title: '',
				code: -1,
			},

			modalVersion: {
				show: false,
				record: null,
				title: '',
				success: true,
			},
		}
	},

	computed: {

	},

	async mounted() {
		this.updateServerOptions()
	},

	methods: {
		getResultsOverview() {
			const results = this.getNumRecords()
			if (!results) {
				return '0 results'
			}
			const startIndex = this.page * this.pageSize + 1
			const endIndex = Math.min((this.page + 1) * this.pageSize, results)
			return `${startIndex} - ${endIndex} of ${results} results`
		},

		getRecords() {
			if (this.selectedServer.value && this.selectedServer.value.id) {
				return this.records[this.selectedServer.value.id].records.hits
			}
			return []
		},

		getNumRecords() {
			if (this.selectedServer.value && this.selectedServer.value.id) {
				return this.records[this.selectedServer.value.id].records.total
			}
			return 0
		},

		getHasToken() {
			if (this.selectedServer.value && this.selectedServer.value.id) {
				return this.records[this.selectedServer.value.id].has_token
			}
			return false
		},

		getNumPages() {
			const numRecords = this.getNumRecords()
			if (!numRecords) {
				return 0
			}
			return Math.floor(numRecords / this.pageSize) + 1
		},

		updateServer(serverValue) {
			// this.selectedServer.value = this.selectedServer.options.find((server) => server.id == serverValue)
			this.$emit('serverUpdate', Number(serverValue.id))
		},

		updatePageSize(size) {
			this.selectPageSize.value = size
			this.$emit('pageSizeUpdate', Number(size))
		},

		updatePage(page) {
			this.$emit('pageUpdate', Number(page))
		},

		getTitle(record) {
			const title = record?.metadata?.titles?.[0]?.title
				?? record?.metadata?.title
				?? 'ERROR: no title'
			return title
		},

		getDate(record) {
			return new Date(record.created)
				.toDateString()
		},

		getLink(record) {
			const id = record.id
			const server = this.records[this.selectedServer.value.id]
			if (server.server_version === 2) {
				return `${server.server_url}/records/${id}/edit`
			} else {
				return `${server.server_url}/uploads/${id}`
			}
		},

		showDeleteModal(record) {
			this.modalDelete.record = record
			this.modalDelete.show = true
		},

		changeStatus(status) {
			this.$emit(status)
		},

		closeModals() {
			this.modalDelete.show = false
			this.modalDelete.record = null
			this.modalDownload.show = false
			this.modalDownload.record = null
			this.modalVersion.show = false
			this.modalVersion.record = null
		},

		deleteRecord(record) {
			const urlArr = [
				'/apps/b2sharebridge/drafts/',
				this.selectedServer.value.id,
				'/',
				record.id,
			]
			axios
				.delete(generateUrl(urlArr.join('')))
				.then((response) => {
					console.debug(response)
					this.$emit('refresh')
				})
				.catch((error) => {
					console.error(error)
					console.error(`Could not delete draft with ID ${record.id} from server ${this.selectedServer.value.label}`)
				})
		},

		nextVersion(record) {
			const url = '/apps/b2sharebridge/next'
			const params = {
				server_id: this.selectedServer.value.id,
				recordId: record.id,
			}
			axios
				.post(generateUrl(url), params)
				.then((response) => {
					console.debug(response)
					this.modalVersion.title = 'New Version created!'
					this.modalVersion.message = 'Successfully created a new version. You can see it under your drafts. Would you like to switch to draft view?'
					this.modalVersion.success = true
					this.modalVersion.show = true
				})
				.catch((error) => {
					console.error(error)
					console.error(`Could not create new version with ${record.id} from server ${this.selectedServer.value.label}`)

					this.modalVersion.title = 'Creating a new version failed!'
					this.modalVersion.message = 'Could not create a new version. Maybe a new version already exists'
					this.modalVersion.success = false
					this.modalVersion.show = true
				})
		},

		downloadRecord(record) {
			const urlArr = [
				'/apps/b2sharebridge/download/',
				this.selectedServer.value.id,
			]
			axios
				.post(generateUrl(urlArr.join('')), {
					record,
				})
				.then((response) => {
					console.debug(response)
					this.modalDownload.message = 'Successfully downloaded your data into your home directory'
					this.modalDownload.title = 'Data ready'
					this.modalDownload.code = 0
					this.modalDownload.show = true
				})
				.catch((error) => {
					console.error(error)
					console.error(`Could not download record with ID ${record.id} from server ${this.selectedServer.value.label}`)
					const response = error.response.data
					if (Number(error.status) === 429) {
						this.modalDownload.message = 'You are rate limited due to your last download(s), please come back later'
						this.modalDownload.code = 0
						this.modalDownload.title = 'Rate limit exceeded'
					} else {
						this.modalDownload.message = response.message

						if (response.status === 'error') {
							this.modalDownload.code = response.code
						}

						if (response.status >= 500) {
							this.modalDownload.title = 'Server Error'
						} else {
							this.modalDownload.title = 'Bad Request'
						}
					}

					this.modalDownload.show = true
				})
		},

		getServerUrl() {
			if (this.selectedServer.value && this.selectedServer.value.id) {
				return this.records[this.selectedServer.value.id].server_url
			}
			return ''
		},

		getServerLabel() {
			if (!this.selectedServer.value.label) {
				return 'None'
			}
			return this.selectedServer.value.label
		},

		updateServerOptions() {
			this.selectedServer.options = []
			console.debug('Keys: ' + Object.keys(this.records))
			Object.keys(this.records).forEach((key) => {
				this.selectedServer.options.push({
					id: key,
					label: this.records[key].server_name,
				})
			})
			if (this.selectedServer.options.length) {
				// try to find selected server
				if (this.serverId !== null) {
					this.selectedServer.value = this.selectedServer.options.find((server) => Number(server.id) === this.serverId)
				}

				// use first one if no valid server is selected
				if (!this.selectedServer.value || !this.selectedServer.value.id) {
					this.selectedServer.value = this.selectedServer.options[0]
				}
			} else {
				this.selectedServer.value = { id: null, label: 'None' }
			}
			return this.selectedServer.value.id !== null
		},

		getSettingsUrl() {
			return generateUrl('/settings/user/b2sharebridge')
		},
	},
}
</script>

<style lang="scss" scoped>
.rp {
	margin: 10px;

	&__selectors {

		&__top,
		&__bottom {
			display: flex;
			flex-direction: row;
			justify-content: space-between;
			align-items: center;
		}

		&__select {
			display: flex;
			flex-direction: row;
			justify-content: flex-end;
			align-items: center;
			gap: 10px;
		}

		&__top {
			border-bottom: 1px solid var(--color-border);
		}
	}

	&__records {
		&__record {
			display: flex;
			flex-direction: row;
			justify-content: space-between;
			align-items: center;
			margin-top: 5px;
			padding-bottom: 5px;
			border-bottom: 1px solid var(--color-border);

			&__title {
				font-weight: bold;
			}

			&__title,
			&__date {
				margin-inline: 5px;
			}

			&__info {
				flex-direction: column;
			}

			&__buttons {
				margin-inline-end: 5px;
				gap: 2px;
				display: flex;
				flex-direction: row;
			}
		}

		&__none {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			text-align: center;
			margin: auto;
		}
	}

	&__modal {

		&__download,
		&__delete,
		&__version {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;

			&__buttons {
				width: 100%;
				min-height: 100px;

				display: flex;
				flex-direction: row;
				align-items: center;
				justify-content: center;
			}

			&__devdesc {
				font-size: 8px;
			}
		}

	}
}
</style>
