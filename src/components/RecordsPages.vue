<template>
	<div class="rp">
		<div class="rp__selectors">
			<p>{{ getResultsOverview() }}</p>

			<div class="rp__selectors__select">
				<p>B2SHARE instance:</p>
				<NcSelect v-model="selectedServer.value"
					:label-outside="true"
					:options="selectedServer.options"
					@update:modelValue="updateServer" />
			</div>
			<div class="rp__selectors__select">
				<p>Page Size:</p>
				<NcSelect v-model="selectPageSize.value"
					:label-outside="true"
					:options="selectPageSize.options"
					@update:modelValue="updatePageSize" />
			</div>
			<div class="rp__selectors__buttons">
				<NcButton class="rp__selectors__buttons__left"
					aria-label="First Page"
					:disabled="isFirstPage()"
					@click="updatePageFirst">
					<template #icon>
						<PageFirst :size="20" />
					</template>
				</NcButton>
				<NcButton class="rp__selectors__buttons__middle"
					aria-label="Previous Page"
					:disabled="isFirstPage()"
					@click="updatePageDecrement">
					<template #icon>
						<ChevronLeft :size="20" />
					</template>
				</NcButton>
				<NcButton class="rp__selectors__buttons__middle"
					aria-label="Next Page"
					:disabled="isLastPage()"
					@click="updatePageIncrement">
					<template #icon>
						<ChevronRight :size="20" />
					</template>
				</NcButton>
				<NcButton class="rp__selectors__buttons__right"
					aria-label="Last Page"
					:disabled="isLastPage()"
					@click="updatePageLast">
					<template #icon>
						<PageLast :size="20" />
					</template>
				</NcButton>
			</div>
		</div>
		<div class="rp__records">
			<template v-for="record in getRecords()">
				<div :key="record['id']" class="rp__records__record">
					<a :href="getLink(record)" target="_blank">
						<div class="rp__records__record__info">
							<p class="rp__records__record__title">{{ getTitle(record) }}</p>
							<p class="rp__records__record__date">{{ getDate(record) }}</p>
						</div>
					</a>
					<div class="rp__records__record__buttons">
						<NcButton class="rp__records__record__buttons__publish"
							aria-label="TODO"
							:disabled="!draft"
							:hidden="!draft"
							:href="getLink(record)"
							target="_blank">
							<template #icon>
								<EarthArrowUp :size="30" />
							</template>
							Publish
						</NcButton>
						<NcButton class="rp__records__record__buttons__download"
							aria-label="TODO"
							@click="showDownloadModal(record)">
							<template #icon>
								<CloudDownload :size="30" />
							</template>
						</NcButton>
						<NcButton class="rp__records__record__buttons__delete"
							aria-label="TODO"
							:disabled="!draft"
							:hidden="!draft"
							@click="showDeleteModal(record)">
							<template #icon>
								<TrashCan :size="30" />
							</template>
						</NcButton>
					</div>
				</div>
			</template>
			<div v-if="!getNumRecords()" class="rp__records__none">
				<h3 v-if="draft">
					You don't have any drafts at <a :href="selectedServer.value">{{ selectedServer.value }}</a>
				</h3>
				<h3 v-else>
					You don't have any publications at <a :href="selectedServer.value">{{ selectedServer.value }}</a>
				</h3>
				<h3>
					You can upload files from B2DROP by selecting them and pressing the <b>B2SHARE</b> button &#128640;
				</h3>
			</div>
		</div>
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
	</div>
</template>
<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

import PageFirst from 'vue-material-design-icons/PageFirst.vue'
import PageLast from 'vue-material-design-icons/PageLast.vue'
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue'
import CloudDownload from 'vue-material-design-icons/CloudDownload.vue'
import EarthArrowUp from 'vue-material-design-icons/EarthArrowUp.vue'
import TrashCan from 'vue-material-design-icons/TrashCan.vue'

import { NcSelect, NcButton, NcModal } from '@nextcloud/vue'
const selectPageSize = {
	options: [
		'10',
		'25',
		'50',
	],
	value: '10',
}

const selectedServer = {
	options: [],
	value: 'None',
}

export default {
	name: 'RecordsPages',
	components: {
		NcButton,
		NcSelect,
		NcModal,
		PageFirst,
		PageLast,
		ChevronLeft,
		ChevronRight,
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
	data() {
		return {
			sortBy: 'createdAt',
			sortDir: 'desc',
			selectPageSize,
			selectedServer,
			modalDelete: {
				show: false,
				record: null,
			},
			modalDownload: {
				show: false,
				record: null,
			},
		}
	},
	computed: {

	},
	async mounted() {
		selectedServer.options = Object.keys(this.records)
		selectedServer.value = selectedServer.options ? selectedServer.options[0] : 'None'
	},
	methods: {
		getResultsOverview() {
			const results = this.getNumRecords()
			if (!results) { return '0 results' }
			const startIndex = this.page * this.pageSize + 1
			const endIndex = Math.min((this.page + 1) * this.pageSize, results)
			return `${startIndex} - ${endIndex} of ${results} results`
		},

		getRecords() {
			if (selectedServer.value !== 'None') { return this.records[selectedServer.value].hits }
			return []
		},

		getNumRecords() {
			if (selectedServer.value !== 'None') { return this.records[selectedServer.value].total }
			return 0
		},

		updateServer(serverValue) {
			selectedServer.value = serverValue
		},

		updatePageSize(size) {
			this.$emit('page-size-update', Number(size))
		},

		updatePage(pageString) {
			const lastPage = Math.floor(this.getNumRecords() / this.pageSize)
			let page = this.page
			if (pageString === '+1') {
				page += 1
				if (page > lastPage) {
					page = lastPage
				}
			} else if (pageString === '-1') {
				page -= 1
				if (page < 0) {
					page = 0
				}
			} else if (pageString === 'first') {
				page = 0
			} else if (pageString === 'last') {
				page = lastPage
			}
			this.$emit('page-update', page)
		},

		updatePageIncrement() {
			this.updatePage('+1')
		},

		updatePageDecrement() {
			this.updatePage('-1')
		},

		updatePageFirst() {
			this.updatePage('first')
		},

		updatePageLast() {
			this.updatePage('last')
		},

		isLastPage() {
			return (this.page + 1) * this.pageSize >= this.getNumRecords()
		},

		isFirstPage() {
			return this.page === 0
		},

		getTitle(record) {
			// console.debug("Single record:")
			// console.debug(record)
			return record.metadata.titles[0].title
		},

		getDate(record) {
			return new Date(record.created).toDateString()
		},

		getLink(record) {
			const id = record.id
			return `${this.selectedServer.value}/records/${id}/edit`
		},

		showDeleteModal(record) {

			this.modalDelete.record = record
			this.modalDelete.show = true
		},

		showDownloadModal(record) {

		},

		closeModals() {
			this.modalDelete.show = false
			this.modalDelete.record = null
			this.modalDownload.show = false
			this.modalDownload.record = null
		},

		deleteRecord(record) {
			console.debug(record.id)
			const urlArr = [
				'/apps/b2sharebridge/drafts/',
				this.records[this.selectedServer.value].server_id,
				'/',
				record.id,
			]
			const url = urlArr.join('')
			console.debug(url)
			axios
				.delete(generateUrl(urlArr.join('')))
				.then((response) => {
					console.debug(response)
					this.$emit('refresh')
				})
				.catch((error) => {
					console.error(error)
					console.error(`Could not delete draft with ID ${record.id} from server ${this.selectedServer.value}`)
				})
		},
	},
}
</script>
<style lang="scss" scoped>
.rp {
    margin: 10px;

    &__selectors {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;

        &__buttons,
        &__select {
            display: flex;
            flex-direction: row;
            justify-content: flex-end;
            align-items: center;
        }

        &__select {
            gap: 10px;
        }

        &__buttons {
            &__right {
                border-top-left-radius: 0 !important;
                border-bottom-left-radius: 0 !important;
            }

            &__left {
                border-top-right-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
            }

            &__middle {
                border-radius: 0 !important;
            }
        }
    }

    &__records {
        &__record {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            margin-top: 5px;
            margin-bottom: 5px;
            border-radius: 4px;

            &:hover {
                box-shadow: 0 0 var(--border-radius-pill) var(--color-primary);
            }

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
        }
    }
}
</style>
