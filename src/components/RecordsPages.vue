<template>
    <div class="rp">
        <div class="rp__selectors">
            <p>{{ getResultsOverview() }}</p>

            <div class="rp__selectors__select">
                <p>B2SHARE instance: </p>
                <NcSelect v-model="selectedServer.value" :labelOutside="true" :options="selectedServer.options"
                    @update:modelValue="updateServer" />
            </div>
            <div class="rp__selectors__select">
                <p>Page Size:</p>
                <NcSelect v-model="selectPageSize.value" :labelOutside="true" :options="selectPageSize.options"
                    @update:modelValue="updatePageSize" />
            </div>
            <div class="rp__selectors__buttons">
                <NcButton class="rp__selectors__buttons__left" aria-label="First Page">
                    <template #icon>
                        <PageFirst :size="20" />
                    </template>
                </NcButton>
                <NcButton class="rp__selectors__buttons__middle" aria-label="Previous Page">
                    <template #icon>
                        <ChevronLeft :size="20" />
                    </template>
                </NcButton>
                <NcButton class="rp__selectors__buttons__middle" aria-label="Next Page">
                    <template #icon>
                        <ChevronRight :size="20" />
                    </template>
                </NcButton>
                <NcButton class="rp__selectors__buttons__right" aria-label="Last Page">
                    <template #icon>
                        <PageLast :size="20" />
                    </template>
                </NcButton>
            </div>
        </div>
        <div class="rp__records">
            <template v-for="(record, index) in getRecords()">
                <a :href="getLink(record)" target="_blank">
                    <div class="rp__records__record" :key="selectedServer.value">
                        <p class="rp__records__record__title">{{ getTitle(record) }}</p>
                        <p class="rp__records__record__date">{{ getDate(record) }}</p>
                    </div>
                </a>
            </template>
        </div>
    </div>
</template>
<script>
import PageFirst from 'vue-material-design-icons/PageFirst.vue'
import PageLast from 'vue-material-design-icons/PageLast.vue'
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue'
import { NcSelect, NcButton } from '@nextcloud/vue'
const selectPageSize = {
    options: [
        '10',
        '25',
        '50',
    ],
    value: '10'
}

const selectedServer = {
    options: [],
    value: "None",
}

export default {
    name: 'RecordsPages',
    components: {
        NcButton,
        NcSelect,
        PageFirst,
        PageLast,
        ChevronLeft,
        ChevronRight,
    },
    props: {
        records: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            sortBy: 'createdAt',
            sortDir: 'desc',
            selectPageSize,
            selectedServer,
        }
    },
    async mounted() {
        selectedServer.options = Object.keys(this.records)
        selectedServer.value = selectedServer.options ? selectedServer.options[0] : "None"
    },
    computed: {

    },
    methods: {
        getResultsOverview() {
            return "TODO: Results Overview"
        },

        getRecords() {
            console.debug("Records pages:")
            console.debug(Object.keys(this.records))
            if (selectedServer.value != "None")
                return this.records[selectedServer.value]["hits"]
            return []
        },

        updateServer(serverValue) {
            selectedServer.value = serverValue;
        },

        updatePageSize(size) {
            this.$emit("page-size-update", size)
        },

        updatePage(pageString) {
            this.$emit("page-update", this.pageString)
        },

        incrementPage() {
            updatePage("+1")
        },

        decrementPage() {
            updatePage("-1")
        },

        firstPage() {
            updatePage("first")
        },

        lastPage() {
            updatePage("last")
        },

        getTitle(record) {
            //console.debug("Single record:")
            //console.debug(record)
            return record["metadata"]["titles"][0]["title"]
        },

        getDate(record) {
            return new Date(record["created"]).toDateString()
        },

        getLink(record) {
            const id = record["id"]
            return `${this.selectedServer.value}/records/${id}/edit`
        }
    }
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
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
            }

            &__left {
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
            }

            &__middle {
                border-radius: 0;
            }
        }
    }

    &__records {
        &__record {
            margin-top: 5px;
            margin-bottom: 5px;
            border-radius: 4px;
            &:hover {
                box-shadow: 0 0 4px;
            }
            &__title {
                font-weight: bold;
            }
            &__title,
            &__date {
                margin-left: 5px;
                margin-right: 5px;
            }
        }
    }
}
</style>