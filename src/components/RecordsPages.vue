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
					:label-outside="true"
					:options="selectedServer.options"
					@update:model-value="updateServer" />
			</div>
			<div class="rp__selectors__select">
				<p>Page Size:</p>
				<NcSelect
					v-bind="selectPageSize"
					v-model="selectPageSize.value"
					:label-outside="true"
					:options="selectPageSize.options"
					@update:model-value="updatePageSize" />
			</div>
			<PageButtons
				class="rp__selectors__pagebuttons"
				:page="page"
				:num-pages="getNumPages()"
				@page-update="updatePage" />
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
							v-if="!draft"
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
			<div v-if="!getNumRecords()" class="rp__records__none">
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
		</div>
		<!-- Bottom of Listing, with less selectors: -->
		<div class="rp__selectors__bottom">
			<p>{{ getResultsOverview() }}</p>
			<PageButtons
				class="rp__selectors__pagebuttons"
				:page="page"
				:num-pages="getNumPages()"
				@page-update="updatePage" />
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
					<NcButton type="primary" aria-label="Yes" @click="deleteRecord(modalDelete.record)">
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
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcModal, NcSelect } from '@nextcloud/vue'
import CloudDownload from 'vue-material-design-icons/CloudDownload.vue'
import EarthArrowUp from 'vue-material-design-icons/EarthArrowUp.vue'
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
	},

	emits: ['page-size-update', 'page-update', 'refresh'],

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

				value: '10',
			},

			selectedServer: {
				options: [],
				value: null,
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
		}
	},

	computed: {

	},

	async mounted() {
		this.selectedServer.options = []
		Object.keys(this.records).forEach((key) => {
			this.selectedServer.options.push({
				id: key,
				label: this.records[key].server_name,
			})
		})
		this.selectedServer.value = this.selectedServer.options.length ? this.selectedServer.options[0] : { id: null, label: 'None' }
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
				return this.records[this.selectedServer.value.id].hits
			}
			return []
		},

		getNumRecords() {
			if (this.selectedServer.value && this.selectedServer.value.id) {
				return this.records[this.selectedServer.value.id].total
			}
			return 0
		},

		getNumPages() {
			const numRecords = this.getNumRecords()
			if (!numRecords) {
				return 0
			}
			return Math.floor(numRecords / this.pageSize) + 1
		},

		updateServer(serverValue) {
			this.selectedServer.value = serverValue
		},

		updatePageSize(size) {
			this.$emit('page-size-update', Number(size))
		},

		updatePage(page) {
			this.$emit('page-update', Number(page))
		},

		getTitle(record) {
			return record.metadata.titles[0].title
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

		showDownloadModal() {

		},

		closeModals() {
			this.modalDelete.show = false
			this.modalDelete.record = null
			this.modalDownload.show = false
			this.modalDownload.record = null
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
			if (this.selectedServer.value) {
				return this.records[this.selectedServer.value.id].server_url
			}
			return ''
		},

		getServerLabel() {
			if (this.selectedServer.value) {
				return this.selectedServer.value.label
			}
			return 'None'
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
				margin-left: 5px;
				margin-right: 5px;
			}

			&__info {
				flex-direction: column;
			}

			&__buttons {
				margin-right: 5px;
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
		&__delete {
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
