<template>
	<NcContent id="bridgecontent" app-name="b2sharebridge" class="app-b2sharebridge">
		<NcAppNavigation>
			<NcAppNavigationNew
				v-if="!loading"
				:text="t('b2sharebridge', 'Publications')"
				:disabled="currentState === BridgeState.RECORDS_PUBLISHED"
				button-id="records-published"
				button-class="icon-add"
				@click="showRecordsPublished" />
			<NcAppNavigationNew
				v-if="!loading"
				:text="t('b2sharebridge', 'Drafts')"
				:disabled="currentState === BridgeState.RECORDS_DRAFT"
				button-id="records-published"
				button-class="icon-add"
				@click="showRecordsDrafted" />
			<NcAppNavigationNew
				v-if="!loading"
				:text="t('b2sharebridge', 'All Uploads')"
				:disabled="currentState === BridgeState.UPLOAD_ALL"
				button-id="upload-all-button"
				button-class="icon-add"
				@click="showAllUploads" />
			<NcAppNavigationNew
				v-if="!loading"
				:text="t('b2sharebridge', 'Pending Uploads')"
				:disabled="currentState === BridgeState.UPLOAD_PENDING"
				button-id="upload-pending-button"
				button-class="icon-add"
				@click="showPendingUploads" />
			<NcAppNavigationNew
				v-if="!loading"
				:text="t('b2sharebridge', 'Published Uploads')"
				:disabled="currentState === BridgeState.UPLOAD_PUBLISHED"
				button-id="upload-published-button"
				button-class="icon-add"
				@click="showPublishedUploads" />
			<NcAppNavigationNew
				v-if="!loading"
				:text="t('b2sharebridge', 'Failed Uploads')"
				:disabled="currentState === BridgeState.UPLOAD_FAILED"
				button-id="upload-failed-button"
				button-class="icon-add"
				@click="showFailedUploads" />
		</NcAppNavigation>
		<NcAppContent>
			<div v-if="Uploads.length === 0 && isUpload()">
				<div v-if="currentState === BridgeState.UPLOAD_ALL">
					<h2 style="text-align: center;">
						{{ t('b2sharebridge', 'Create an Upload to get started!') }}
					</h2>
				</div>
				<div v-else-if="currentState === BridgeState.UPLOAD_PENDING">
					<h2 style="text-align: center;">
						{{ t('b2sharebridge', 'No pending Uploads!') }}
					</h2>
				</div>
				<div v-else-if="currentState === BridgeState.UPLOAD_PUBLISHED">
					<h2 style="text-align: center;">
						{{ t('b2sharebridge', 'No published Uploads!') }}
					</h2>
				</div>
				<div v-else-if="currentState === BridgeState.UPLOAD_FAILED">
					<h2 style="text-align: center;">
						{{ t('b2sharebridge', 'No failed Uploads!') }}
					</h2>
				</div>
				<div v-else>
					<h2 style="text-align: center;">
						{{ t('b2sharebridge', 'Unknown table status!') }}
					</h2>
				</div>
			</div>
			<div v-else-if="isUpload()">
				<h2 id="upload-table-name" style="text-align: center;">
					{{ getTableName() }}
				</h2>
				<SortableTable
					id="upload-table"
					striped
					hover
					:rows="Uploads"
					:fields="fields"
					sort-by="createdAt"
					sort-dir="desc" />
			</div>
			<div v-else-if="!loadedPublications">
				<h2 v-if="currentState === BridgeState.RECORDS_PUBLISHED" style="text-align: center;">
					{{ t('b2sharebridge', 'Loading publications ...') }}
				</h2>
				<h2 v-if="currentState === BridgeState.RECORDS_DRAFT" style="text-align: center;">
					{{ t('b2sharebridge', 'Loading drafts ...') }}
				</h2>
			</div>
			<div v-else-if="Publications.length === 0">
				<div
					v-if="currentState === BridgeState.RECORDS_PUBLISHED || currentState === BridgeState.RECORDS_DRAFT">
					<h2 style="text-align: center;">
						{{ t('b2sharebridge', 'Missing API Token') }}
					</h2>
					<div>
						<p>Please set a valid API token</p>
						<NcButton
							label="Set API Token"
							:href="generateUrl('/settings/user/b2sharebridge')"
							type="Primary"
							area-label="Set API Token"
							@click="redirect('/settings/user/b2sharebridge')">
							"Set API Token"
						</NcButton>
					</div>
				</div>
			</div>
			<div v-else>
				<h2 id="records-pages-name" style="text-align: center;">
					{{ getTableName() }}
				</h2>
				<RecordsPages
					v-if="Publications"
					id="records-pages"
					:records="Publications"
					:draft="currentState === BridgeState.RECORDS_DRAFT"
					:page="page"
					:page-size="pageSize"
					@page-update="updatePage"
					@page-size-update="updatePageSize"
					@refresh="refreshRecords" />
			</div>
		</NcAppContent>
	</NcContent>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
// import NcActionButton from '@nextcloud/vue/dist/Components/ActionButton.cjs'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcContent from '@nextcloud/vue/components/NcContent'
import RecordsPages from './components/RecordsPages.vue'
import SortableTable from './components/SortableTable.vue'

const BridgeState = {
	UPLOAD_ALL: 'all',
	UPLOAD_PENDING: 'pending',
	UPLOAD_PUBLISHED: 'published',
	UPLOAD_FAILED: 'failed',
	RECORDS_PUBLISHED: 'records_published',
	RECORDS_DRAFT: 'records_draft',
}

const UploadFields = [
	'status',
	'title',
	'url',
	'owner',
	'error',
	'serverId',
	'fileCount',
	'createdAt',
	'updatedAt',
]

export default {
	name: 'App',
	components: {
		NcContent,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationNew,
		NcButton,
		SortableTable,
		RecordsPages,
	},

	data() {
		return {
			Uploads: [],
			fields: [],
			sortBy: 'createdAt',
			sortDesc: true,
			updating: false,
			loading: true,
			currentState: BridgeState.RECORDS_PUBLISHED,
			timer: null,
			lastUploadUpdate: null,
			BridgeState, // https://stackoverflow.com/questions/57538539/how-to-use-enums-or-const-in-vuejs
			UploadFields,
			Publications: null,
			loadedPublications: false,
			pageSize: 10,
			page: 0,
		}
	},

	/**
	 * Fetch list of Uploads when the component is loaded
	 */
	async mounted() {
		const urlParams = new URLSearchParams(window.location.search)
		if (urlParams.has('uploads')) {
			this.currentState = BridgeState.UPLOAD_ALL
		} else if (urlParams.has('publications')) {
			this.currentState = BridgeState.RECORDS_PUBLISHED
		} else if (urlParams.has('drafts')) {
			this.currentState = BridgeState.RECORDS_DRAFT
		}
		if (this.currentState === BridgeState.RECORDS_DRAFT || this.currentState === BridgeState.RECORDS_PUBLISHED) {
			try {
				await this.loadPublications(this.currentState)
			} catch (e) {
				console.error(e)
				showError(t('b2sharebridge', 'Could not fetch Records'))
			}
		} else {
			try {
				await this.showAllUploads()
			} catch (e) {
				console.error(e)
				showError(t('b2sharebridge', 'Could not fetch Uploads'))
			}
		}
		this.loading = false
	},

	beforeUnmount() { // might need to switch to beforeUnmount in the future
		clearInterval(this.timer)
	},

	methods: {
		async loadUploads(filter) {
			if (this.currentState === BridgeState.RECORDS_DRAFT || this.currentState === BridgeState.RECORDS_PUBLISHED) {
				return
			}
			if (this.timer !== null) {
				clearInterval(this.timer)
			}
			this.timer = setInterval(() => {
				this.checkUploadUpdate()
			}, 1000 * 60 * 2) // every two minutes
			this.lastUploadUpdate = new Date()
			return axios
				.get(generateUrl('/apps/b2sharebridge/uploads?filter=' + filter))
				.then((response) => {
					console.debug(response.data)
					this.Uploads = response.data
					this.Uploads.forEach((value, index, array) => {
						array[index] = this.translateUploadstatus(value)
					})
				})
				.catch((error) => {
					console.error(error)
					console.error('Could not load Upload List')
				})
		},

		async loadPublications(state) {
			this.loadedPublications = false
			const draft = state !== BridgeState.RECORDS_PUBLISHED
			const urlArr = [
				'/apps/b2sharebridge/publications?draft=',
				draft,
				'&page=',
				this.page + 1,
				'&size=',
				this.pageSize,
			]
			console.debug('URL: ' + urlArr.join(''))
			return axios
				.get(generateUrl(urlArr.join('')))
				.then((response) => {
					console.debug('Publication data:')
					console.debug(response.data)
					this.Publications = response.data
					this.loadedPublications = true
					// TODO maybe change layout of data
				})
				.catch((error) => {
					console.error(error)
					console.error('Could not load Publications')
				})
		},

		showAllUploads() {
			this.currentState = BridgeState.UPLOAD_ALL
			this.generateTableFields(['status', 'title', 'url', 'fileCount', 'serverId', 'createdAt', 'updatedAt'])
			return this.loadUploads(this.currentState)
		},

		showPendingUploads() {
			this.currentState = BridgeState.UPLOAD_PENDING
			this.generateTableFields(['title', 'fileCount', 'serverId', 'createdAt', 'updatedAt'])
			return this.loadUploads(this.currentState)
		},

		showPublishedUploads() {
			this.currentState = BridgeState.UPLOAD_PUBLISHED
			this.generateTableFields(['title', 'url', 'fileCount', 'serverId', 'createdAt', 'updatedAt'])
			return this.loadUploads(this.currentState)
		},

		showFailedUploads() {
			this.currentState = BridgeState.UPLOAD_FAILED
			this.generateTableFields(['title', 'fileCount', 'serverId', 'error', 'createdAt', 'updatedAt'])
			return this.loadUploads(this.currentState)
		},

		showRecordsPublished() {
			this.currentState = BridgeState.RECORDS_PUBLISHED
			return this.loadPublications(this.currentState)
		},

		showRecordsDrafted() {
			this.currentState = BridgeState.RECORDS_DRAFT
			return this.loadPublications(this.currentState)
		},

		getTableName() {
			switch (this.currentState) {
				case BridgeState.UPLOAD_ALL:
					return 'All Uploads'
				case BridgeState.UPLOAD_PENDING:
					return 'Pending Uploads'
				case BridgeState.UPLOAD_PUBLISHED:
					return 'Published Uploads'
				case BridgeState.UPLOAD_FAILED:
					return 'Failed Uploads'
				case BridgeState.RECORDS_PUBLISHED:
					return 'Published Records'
				case BridgeState.RECORDS_DRAFT:
					return 'Drafted Records'
				default:
					return 'Error Table'
			}
		},

		capitalizeFirstLetter(string) {
			return string.charAt(0).toUpperCase() + string.slice(1)
		},

		translateUploadstatus(Uploadstatus) {
			if ('status' in Uploadstatus) {
				switch (Uploadstatus.status) {
					case 0:
						Uploadstatus.status = this.capitalizeFirstLetter(BridgeState.UPLOAD_PUBLISHED)
						break
					case 1:
					case 2:
						Uploadstatus.status = this.capitalizeFirstLetter(BridgeState.UPLOAD_PENDING)
						break
					case 3:
					case 4:
					case 5:
						Uploadstatus.status = this.capitalizeFirstLetter(BridgeState.UPLOAD_FAILED)
						break
					default:
						break
				}
			}
			// TODO query server id?

			return Uploadstatus
		},

		checkUploadUpdate() {
			console.debug('Polling Uploads')
			this.loadUploads(this.currentState) // try to fetch update after transfer handler
		},

		generateTableFields(activeFieldNames) {
			this.fields = []
			for (let i = 0; i < this.UploadFields.length; i++) {
				const originalField = UploadFields[i]
				const field = {
					name: this.capitalizeFirstLetter(originalField),
					label: originalField,
					type: originalField === 'url' ? 'link' : null,
					active: activeFieldNames.includes(originalField),
					extraClass: this.getExtraClass(originalField),
				}
				this.fields.push(field)
			}
		},

		getExtraClass(fieldName) {
			let extraClass = 'columnWidthInt'
			switch (fieldName) {
				case 'url':
					extraClass = 'bridgelink'
					break
				case 'title':
					extraClass = 'columnWidthTitle'
					break
				case 'createdAt':
				case 'updatedAt':
					extraClass = 'columnWidthDate'
					break
				default: extraClass = 'columnWidthInt'
			}
			return extraClass
		},

		isUpload() {
			return this.currentState !== BridgeState.RECORDS_DRAFT && this.currentState !== BridgeState.RECORDS_PUBLISHED
		},

		updatePage(page) {
			this.page = page
			console.debug('page updated: ' + this.page)
			this.loadPublications(this.currentState)
		},

		updatePageSize(size) {
			this.page = 0
			this.pageSize = size
			console.debug('size updated: ' + this.pageSize)
			this.loadPublications(this.currentState)
		},

		refreshRecords() {
			this.updatePageSize(this.pageSize)
		},
	},
}
</script>

<style>
#upload-table-name,
#records-pages-name {
	height: 50px;
}

#app-content>div {
	width: 100%;
	height: 100%;
	padding: 20px;
	display: flex;
	flex-direction: column;
	flex-grow: 1;
	background-color: var(--color-main-background)
}

#bridgecontent .icon-file {
	height: 50px;
}

#Upload-table {
	width: 100%;
	border-top: 0;
	border-bottom: 0;
}

#bridgecontent td,
#bridgecontent th {
	color: var(--color-main-text);
	border-color: var(--color-border);
}

body .table.b-table>tfoot>tr>[aria-sort=none],
body .table.b-table>thead>tr>th[aria-sort=none] {
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='101' height='101' view-box='0 0 101 101' preserveAspectRatio='none'%3e%3cpath fill='white' opacity='.3' d='M51 1l25 23 24 22H1l25-22z'/%3e%3cpath fill='white' opacity='.3' d='M51 101l25-23 24-22H1l25 22z'/%3e%3c/svg%3e") !important;
}

body .table.b-table>tfoot>tr>[aria-sort=ascending],
body .table.b-table>thead>tr>th[aria-sort=ascending] {
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='101' height='101' view-box='0 0 101 101' preserveAspectRatio='none'%3e%3cpath fill='white' d='M51 1l25 23 24 22H1l25-22z'/%3e%3cpath fill='white' opacity='.3' d='M51 101l25-23 24-22H1l25 22z'/%3e%3c/svg%3e") !important;
}

body .table.b-table.table-dark>tfoot>tr>[aria-sort=descending],
body #app .table.b-table.table-dark>thead>tr>[aria-sort=descending],
.table.b-table>.thead-dark>tr>[aria-sort=descending] {
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='101' height='101' view-box='0 0 101 101' preserveAspectRatio='none'%3e%3cpath fill='white' opacity='.3' d='M51 1l25 23 24 22H1l25-22z'/%3e%3cpath fill='white' d='M51 101l25-23 24-22H1l25 22z'/%3e%3c/svg%3e") !important;
}

input[type='text'] {
	width: 100%;
}

textarea {
	flex-grow: 1;
	width: 100%;
}

.columnWidthInt {
	width: 6%;
}

.columnWidthDate {
	width: 15%;
}

.columnWidthTitle {
	width: 20%;
}

.bridgelink {
	width: 30%;
}

.bridgelink a {
	color: blue;
}

.bridgelink a:hover {
	text-decoration: underline;
}

.bridgelink a:visited {
	color: purple;
}
</style>
