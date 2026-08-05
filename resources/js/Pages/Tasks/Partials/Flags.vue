<script setup>
import {Link} from "@inertiajs/vue3";
import {ref, watch} from "vue";

const props = defineProps({allFlags: Array});
const emit = defineEmits(["filter"]);

const selectedFlagIds = ref([]);
const isOpen = ref(true);

watch(
    () => props.allFlags,
    (flags) => {
        const allowed = new Set((flags ?? []).map(f => f.id));
        const next = selectedFlagIds.value.filter(id => allowed.has(id));
        if (next.length !== selectedFlagIds.value.length) {
            selectedFlagIds.value = next;
            emit("filter", selectedFlagIds);
        }
    },
    {immediate: true}
);

const toggleOpen = () => {
    isOpen.value = !isOpen.value;
}

const clearFlagFilters = () => {
    selectedFlagIds.value = [];
    emit("filter", selectedFlagIds);
}

const toggleFlagFilter = (flagId) => {
    const idx = selectedFlagIds.value.indexOf(flagId);
    if (idx >= 0) {
        selectedFlagIds.value.splice(idx, 1);
    } else {
        selectedFlagIds.value.push(flagId);
    }
    emit("filter", selectedFlagIds);
}
</script>

<template>
    <div class="surface-card overflow-hidden mb-6" v-if="allFlags.length">
        <div class="p-4">
            <div class="flex items-center justify-between gap-2">
                <div class="inline-flex items-center gap-2 min-w-0">
                    <Link :href="route('flags')" class="text-sm font-semibold inline-flex items-center gap-2">
                        <span>Filter by flags</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round"
                             class="lucide lucide-pencil">
                            <path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                            <path d="m15 5 4 4"/>
                        </svg>
                    </Link>
                    <div v-if="selectedFlagIds.length" class="text-xs text-gray-500 truncate">
                        {{ selectedFlagIds.length }} selected
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-ghost btn-xs btn-square tooltip tooltip-left" type="button" @click="toggleOpen"
                            :aria-expanded="isOpen ? 'true' : 'false'"
                            aria-label="Toggle flag filters"
                            :data-tip="isOpen ? 'Hide' : 'Show'">
                        <svg v-if="isOpen" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round"
                             class="lucide lucide-chevron-up">
                            <path d="m18 15-6-6-6 6"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round"
                             class="lucide lucide-chevron-down">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>
                    <button class="btn btn-ghost btn-xs tooltip tooltip-left" type="button" @click="clearFlagFilters"
                            :disabled="!selectedFlagIds.length" data-tip="Clear selection"
                            aria-label="Clear flag filters">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round"
                             class="lucide lucide-x">
                            <path d="M18 6 6 18"/>
                            <path d="m6 6 12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div v-if="isOpen" class="flex flex-wrap gap-2 mt-3">
                <button v-for="flag in allFlags"
                        :key="flag.id"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium ring-1 transition"
                        :class="selectedFlagIds.includes(flag.id)
                            ? 'bg-brand-navy text-white ring-brand-navy'
                            : 'bg-white text-gray-700 ring-gray-200 hover:ring-gray-300 hover:bg-gray-50'"
                        @click="toggleFlagFilter(flag.id)">
                            <span class="inline-block w-2.5 h-2.5 rounded-full ring-1 ring-black/10"
                                  :style="{ backgroundColor: flag.color }"/>
                    <span class="truncate max-w-48">{{ flag.name }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
