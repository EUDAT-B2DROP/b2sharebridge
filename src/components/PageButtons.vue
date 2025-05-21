<template>
	<div v-if="numPages" class="pagebuttons">
		<NcButton
			class="pagebuttons__left"
			aria-label="First Page"
			:disabled="isFirstPage"
			@click="updatePageFirst">
			<template #icon>
				<PageFirst :size="20" />
			</template>
		</NcButton>
		<NcButton
			class="pagebuttons__middle"
			aria-label="Previous Page"
			:disabled="isFirstPage"
			@click="updatePageDecrement">
			<template #icon>
				<ChevronLeft :size="20" />
			</template>
		</NcButton>
		<NcButton
			class="pagebuttons__middle"
			aria-label="Next Page"
			:disabled="isLastPage"
			@click="updatePageIncrement">
			<template #icon>
				<ChevronRight :size="20" />
			</template>
		</NcButton>
		<NcButton
			class="pagebuttons__right"
			aria-label="Last Page"
			:disabled="isLastPage"
			@click="updatePageLast">
			<template #icon>
				<PageLast :size="20" />
			</template>
		</NcButton>
	</div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue'
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue'
import PageFirst from 'vue-material-design-icons/PageFirst.vue'
import PageLast from 'vue-material-design-icons/PageLast.vue'
export default {
	name: 'PageButtons',
	components: {
		NcButton,
		PageFirst,
		PageLast,
		ChevronLeft,
		ChevronRight,
	},

	props: {
		page: {
			type: Number,
			required: true,
		},

		numPages: {
			type: Number,
			required: true,
		},
	},

	emits: ['page-update'],

	computed: {
		isLastPage() {
			return this.page >= this.numPages - 1
		},

		isFirstPage() {
			return this.page === 0
		},
	},

	methods: {
		updatePage(pageString) {
			const lastPage = this.numPages - 1
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
	},

}

</script>

<style lang="scss" scoped>
.pagebuttons {
    display: flex;
    flex-direction: row;
    justify-content: flex-end;
    align-items: center;

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
</style>
