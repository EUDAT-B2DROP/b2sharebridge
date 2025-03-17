<template>
	<table class="sortable-table">
		<thead>
			<tr>
				<template v-for="( field, index ) in fields">
					<th v-if="fields.at(index).active" :key="index">
						<div class="sortheader">
							{{ field.name }}
							<span class="sortbuttons">
								<NcButton type="secondary"
									aria-label="Ascending Sort"
									class="sorttop"
									:disabled="sortBy === field.label && sortDir === 'asc'"
									@click="sortByColumn(field.label, 'asc')">
									<template #icon>
										<Triangle :size="10" />
									</template>
								</NcButton>
								<NcButton type="secondary"
									aria-label="Descending Sort"
									class="sortbottom"
									:disabled="sortBy === field.label && sortDir === 'desc'"
									@click="sortByColumn(field.label, 'desc')">
									<template #icon>
										<TriangleDown :size="10" />
									</template>
								</NcButton>
							</span>
						</div>
					</th>
				</template>
			</tr>
		</thead>
		<tbody>
			<tr v-for="row in sortedRows" :key="row">
				<template v-for="( field, index ) in fields">
					<td v-if="field.active" :key="index" :class="field.extraClass">
						<a v-if="field.type === 'link'" :href="row[field.label]">
							{{ row[field.label] }}
						</a>
						<template v-else>
							{{ row[field.label] }}
						</template>
					</td>
				</template>
			</tr>
		</tbody>
	</table>
</template>

<script>
import Triangle from 'vue-material-design-icons/Triangle.vue'
import TriangleDown from 'vue-material-design-icons/TriangleDown.vue'
import { NcButton } from '@nextcloud/vue'
export default {
	name: 'SortableTable',
	components: {
		Triangle,
		TriangleDown,
		NcButton,
	},
	props: {
		fields: {
			type: Array,
			required: true,
		},
		rows: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			sortBy: 'createdAt',
			sortDir: 'desc',
		}
	},
	computed: {
		sortedRows() {
			if (!this.sortBy) {
				return this.rows
			}
			const rowsSorted = this.rows.slice(0).sort((rowA, rowB) => {
				return rowA[this.sortBy] > rowB[this.sortBy]
			})
			if (this.sortDir === 'desc') {
				rowsSorted.reverse()
			}
			return rowsSorted
		},
	},
	methods: {
		sortByColumn(columnName, direction) {
			this.sortBy = columnName
			this.sortDir = direction
		},
	},
}
</script>

<style>
.sortable-table {
	width: 100%;
	border-collapse: collapse;
}

.sortable-table th,
.sortable-table td {
	padding: 4px;
	border-right: 1px solid;
	border-color: var(--color-border);
}

.sortable-table .sortheader {
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: space-between;
}

.sortable-table .sortbuttons {
	display: flex;
	flex-direction: column;
}

.sortable-table .sorttop {
	border-bottom-left-radius: 0 !important;
	border-bottom-right-radius: 0 !important;

}

.sortable-table .sortbottom {
	border-top-left-radius: 0 !important;
	border-top-right-radius: 0 !important;
}

.sortable-table .sortbottom,
.sortable-table .sorttop {
	min-height: 22px !important;
	height: 22px;
}
</style>
