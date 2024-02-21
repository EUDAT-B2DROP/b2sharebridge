<template>
    <table class="sortable-table">
        <thead>
            <tr>
                <template v-for="( field, index ) in fields">
                    <th v-if="fields.at(index).active" :key="index">
                        {{ field.name }}
                        <button @click="sortByColumn(field.label, 'asc')">▲</button>
                        <button @click="sortByColumn(field.label, 'desc')">▼</button>
                    </th>
                </template>
            </tr>
        </thead>
        <tbody>
            <tr v-for="row in sortedRows">
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
export default {
    name: "SortableTable",
    props: {
        fields: {
            type: Array,
            required: true
        },
        rows: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            sortBy: null,
            sortDir: 'asc',
        };
    },
    computed: {
        sortedRows() {
            if (!this.sortBy) {
                return this.rows;
            }
            this.rows.sort((rowA, rowB) => {
                return rowA[this.sortBy] > rowB[this.sortBy]
            })
            if (this.sortDir === 'desc') {
                this.rows.reverse()
            }
            return this.rows
        },
    },
    methods: {
        sortByColumn(columnName, direction) {
            this.sortBy = columnName
            this.sortDir = direction
        },
    }
};
</script>
  
<style>
.sortable-table {
    width: 100%;
    border-collapse: collapse;
}

.sortable-table th,
.sortable-table td {
    padding: 8px;
    border: 1px solid #ccc;
}

.sortable-table th {
    background-color: #f2f2f2;
}
</style>