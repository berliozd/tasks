<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Head} from '@inertiajs/vue3';
import {computed, reactive, ref, watch} from 'vue';
import {format} from 'date-fns';
import debounce from 'lodash/debounce';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SaveButton from '@/Components/SaveButton.vue';
import ManagePipelineModal from '@/Pages/Needs/Partials/ManagePipelineModal.vue';
import {useStore} from '@/Composables/store.js';

const loading = ref(true);
const groups = ref([]); // [{id, label, position, stages: [{id, label, color, position, need_stage_group_id}]}]
const needsByStage = reactive({}); // stage id -> Need[]

const allStages = computed(() => groups.value.flatMap(g => g.stages));
const stagesById = computed(() => Object.fromEntries(allStages.value.map(s => [s.id, s])));

const ensureBucket = (stageId) => {
    if (!needsByStage[stageId]) needsByStage[stageId] = [];
}

// Cumulative (OR) multi-select — empty means every stage is shown.
const stageFilters = ref([]);

const toggleStageFilter = (stageId) => {
    stageFilters.value = stageFilters.value.includes(stageId)
        ? stageFilters.value.filter(id => id !== stageId)
        : [...stageFilters.value, stageId];
}

const isGroupFilterActive = (group) => group.stages.length > 0
    && group.stages.every(s => stageFilters.value.includes(s.id));

// Toggles every stage in the group at once — cumulative with individual
// stage toggles, so a group filter can be combined with other selections.
const toggleGroupFilter = (group) => {
    const ids = group.stages.map(s => s.id);
    stageFilters.value = isGroupFilterActive(group)
        ? stageFilters.value.filter(id => !ids.includes(id))
        : [...new Set([...stageFilters.value, ...ids])];
}

const visibleStages = computed(() => allStages.value.filter(
    s => !stageFilters.value.length || stageFilters.value.includes(s.id),
));

const visibleGroups = computed(() => groups.value
    .map(group => ({...group, stages: group.stages.filter(s => visibleStages.value.includes(s))}))
    .filter(group => group.stages.length));

const stageBgStyle = (color) => ({backgroundColor: `${color}1a`, color});

// --- Search ---

const searchQuery = ref('');

const matchesSearch = (need) => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return true;
    return (need.title ?? '').toLowerCase().includes(query)
        || (need.description ?? '').toLowerCase().includes(query);
}

const visibleNeeds = (stageId) => (needsByStage[stageId] ?? []).filter(matchesSearch);

// --- Presentation mode ---

const presentationMode = ref(false);
const togglePresentationMode = () => presentationMode.value = !presentationMode.value;

const refreshStages = () => axios.get(route('need-stage-groups.index')).then(response => {
    groups.value = response.data;
    allStages.value.forEach(s => ensureBucket(s.id));
    // Drop any filter selection pointing at a stage that no longer exists —
    // otherwise a deleted stage can linger in the filter and blank the whole
    // board until the page is reloaded (which resets this ref to []).
    const validIds = new Set(allStages.value.map(s => s.id));
    stageFilters.value = stageFilters.value.filter(id => validIds.has(id));
});

const refreshBoard = () => {
    loading.value = true;
    return axios.get(route('needs.index')).then(response => {
        Object.keys(needsByStage).forEach(id => needsByStage[id] = []);
        response.data.forEach(need => {
            ensureBucket(need.need_stage_id);
            needsByStage[need.need_stage_id].push(need);
        });
    }).finally(() => loading.value = false);
}

const showManageModal = ref(false);
const openManageModal = () => showManageModal.value = true;
const closeManageModal = () => {
    showManageModal.value = false;
    refreshStages().then(refreshBoard);
}

const showAddModal = ref(false);
const newNeedTitle = ref('');
const newNeedDescription = ref('');
const creating = ref(false);

const openAddModal = () => {
    newNeedTitle.value = '';
    newNeedDescription.value = '';
    showAddModal.value = true;
}

const closeAddModal = () => {
    showAddModal.value = false;
}

const addNeed = () => {
    const title = newNeedTitle.value.trim();
    if (!title) return;
    creating.value = true;
    axios.post(route('needs.store'), {title, description: newNeedDescription.value.trim() || null}).then(response => {
        ensureBucket(response.data.need_stage_id);
        needsByStage[response.data.need_stage_id].push(response.data);
        showAddModal.value = false;
    }).finally(() => creating.value = false);
}

// --- Drag and drop between/within stage columns ---

const draggingId = ref(null);
const draggingFromStage = ref(null);
const justDragged = ref(false);

const onDragStart = (need) => {
    if (presentationMode.value) return;
    draggingId.value = need.id;
    draggingFromStage.value = need.need_stage_id;
}

const persistDrop = (id, fromStageId, targetStageId) => {
    const changedStage = fromStageId !== targetStageId;
    const ids = needsByStage[targetStageId].map(n => n.id);
    const request = changedStage
        ? axios.patch(route('needs.stage', id), {stage_id: targetStageId})
        : Promise.resolve();
    request
        .then(() => axios.post(route('needs.reorder'), {stage_id: targetStageId, ids}))
        .catch(() => refreshBoard())
        .finally(() => {
            justDragged.value = true;
            setTimeout(() => justDragged.value = false, 50);
        });
}

const onDropOnCard = (targetStageId, targetNeed) => {
    if (presentationMode.value) return;
    const id = draggingId.value;
    const fromStageId = draggingFromStage.value;
    draggingId.value = null;
    draggingFromStage.value = null;
    if (id === null || fromStageId === null) return;

    const fromArr = needsByStage[fromStageId];
    const fromIndex = fromArr.findIndex(n => n.id === id);
    if (fromIndex === -1) return;
    const [moved] = fromArr.splice(fromIndex, 1);
    moved.need_stage_id = targetStageId;

    const toArr = needsByStage[targetStageId];
    let toIndex = toArr.findIndex(n => n.id === targetNeed.id);
    if (toIndex === -1) toIndex = toArr.length;
    toArr.splice(toIndex, 0, moved);

    persistDrop(id, fromStageId, targetStageId);
}

const onDropOnColumn = (targetStageId) => {
    if (presentationMode.value) return;
    const id = draggingId.value;
    const fromStageId = draggingFromStage.value;
    draggingId.value = null;
    draggingFromStage.value = null;
    if (id === null || fromStageId === null) return;

    const fromArr = needsByStage[fromStageId];
    const fromIndex = fromArr.findIndex(n => n.id === id);
    if (fromIndex === -1) return;
    const [moved] = fromArr.splice(fromIndex, 1);
    moved.need_stage_id = targetStageId;
    needsByStage[targetStageId].push(moved);

    persistDrop(id, fromStageId, targetStageId);
}

// --- Detail modal ---

const showDetail = ref(false);
const selectedNeed = ref(null);
const loadingDetail = ref(false);
const savingDetail = ref(false);
const savedDetail = ref(false);
let savedDetailTimer = null;
let detailSnapshot = null;
let watchDetailActive = false;
const confirmingDelete = ref(false);
const newNote = ref('');
const addingNote = ref(false);

const cleanDetail = (n) => JSON.stringify({
    title: n.title, description: n.description, confluence_url: n.confluence_url,
    jira_key: n.jira_key, jira_url: n.jira_url, business_owner: n.business_owner,
});

const openNeed = (need) => {
    if (justDragged.value || presentationMode.value) return;
    confirmingDelete.value = false;
    newNote.value = '';
    watchDetailActive = false;
    loadingDetail.value = true;
    showDetail.value = true;
    axios.get(route('needs.show', need.id)).then(response => {
        selectedNeed.value = response.data;
        detailSnapshot = cleanDetail(selectedNeed.value);
        watchDetailActive = true;
    }).finally(() => loadingDetail.value = false);
}

const closeDetail = () => {
    showDetail.value = false;
    selectedNeed.value = null;
    watchDetailActive = false;
}

const updateDetail = () => {
    if (!selectedNeed.value) return;
    savingDetail.value = true;
    axios.patch(route('needs.update', selectedNeed.value.id), {
        title: selectedNeed.value.title,
        description: selectedNeed.value.description,
        confluence_url: selectedNeed.value.confluence_url,
        jira_key: selectedNeed.value.jira_key,
        jira_url: selectedNeed.value.jira_url,
        business_owner: selectedNeed.value.business_owner,
    }).then(() => {
        detailSnapshot = cleanDetail(selectedNeed.value);
        // Keep the board card in sync without a full refetch.
        const card = needsByStage[selectedNeed.value.need_stage_id]?.find(n => n.id === selectedNeed.value.id);
        if (card) {
            card.title = selectedNeed.value.title;
            card.business_owner = selectedNeed.value.business_owner;
            card.confluence_url = selectedNeed.value.confluence_url;
            card.jira_key = selectedNeed.value.jira_key;
            card.jira_url = selectedNeed.value.jira_url;
        }
        savingDetail.value = false;
        savedDetail.value = true;
        if (savedDetailTimer) clearTimeout(savedDetailTimer);
        savedDetailTimer = setTimeout(() => savedDetail.value = false, 1500);
    }).catch(() => savingDetail.value = false);
}
const debouncedUpdateDetail = debounce(updateDetail, 600);

watch(() => selectedNeed.value && [
    selectedNeed.value.title, selectedNeed.value.description, selectedNeed.value.confluence_url,
    selectedNeed.value.jira_key, selectedNeed.value.jira_url, selectedNeed.value.business_owner,
], () => {
    if (!watchDetailActive || !selectedNeed.value) return;
    if (cleanDetail(selectedNeed.value) === detailSnapshot) return;
    debouncedUpdateDetail();
});

const changeStageFromModal = (event) => {
    const stageId = Number(event.target.value);
    if (!selectedNeed.value || stageId === selectedNeed.value.need_stage_id) return;
    const fromStageId = selectedNeed.value.need_stage_id;
    axios.patch(route('needs.stage', selectedNeed.value.id), {stage_id: stageId}).then(() => {
        const arr = needsByStage[fromStageId];
        const idx = arr.findIndex(n => n.id === selectedNeed.value.id);
        if (idx !== -1) {
            const [moved] = arr.splice(idx, 1);
            moved.need_stage_id = stageId;
            ensureBucket(stageId);
            needsByStage[stageId].push(moved);
        }
        selectedNeed.value.need_stage_id = stageId;
        return axios.get(route('needs.show', selectedNeed.value.id));
    }).then(response => {
        selectedNeed.value.activities = response.data.activities;
    });
}

const addNote = () => {
    const note = newNote.value.trim();
    if (!note || !selectedNeed.value) return;
    addingNote.value = true;
    axios.post(route('needs.notes.store', selectedNeed.value.id), {note}).then(response => {
        selectedNeed.value.activities = response.data.activities;
        newNote.value = '';
    }).finally(() => addingNote.value = false);
}

const deleteNeed = () => {
    if (!selectedNeed.value) return;
    const id = selectedNeed.value.id;
    const stageId = selectedNeed.value.need_stage_id;
    axios.delete(route('needs.destroy', id)).then(() => {
        needsByStage[stageId] = needsByStage[stageId].filter(n => n.id !== id);
        useStore().setSaved('Need deleted');
        closeDetail();
    });
}

const formatDate = (date) => date ? format(new Date(date), 'MMM d, yyyy HH:mm') : '';

const activityText = computed(() => (activity) => {
    if (activity.type === 'created') return 'Need created';
    if (activity.type === 'stage_changed') {
        return `Moved from "${activity.from_stage}" to "${activity.to_stage}"`;
    }
    return activity.note;
});

refreshStages().then(refreshBoard);
</script>

<template>
    <Head title="Needs"/>
    <AppLayout title="Needs" :fullscreen="presentationMode">
        <template #header>
            <div class="flex items-center gap-4">
                <h2 class="font-semibold text-xl leading-tight text-slate-900">Needs</h2>
                <button type="button" @click="togglePresentationMode"
                        :title="presentationMode ? 'Exit presentation view' : 'Presentation view'"
                        class="ml-auto shrink-0 inline-flex items-center justify-center size-10 rounded-full border transition"
                        :class="presentationMode
                            ? 'border-brand-accent bg-brand-accent/10 text-brand-accent-dark'
                            : 'border-gray-300 text-gray-500 hover:bg-gray-100'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
                <template v-if="!presentationMode">
                    <button type="button" @click="openManageModal" title="Manage pipeline"
                            class="shrink-0 inline-flex items-center justify-center size-10 rounded-full border border-gray-300 text-gray-500 hover:bg-gray-100 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                    <button type="button" @click="openAddModal" title="Add a need"
                            class="shrink-0 inline-flex items-center justify-center size-12 rounded-full bg-brand-accent text-white text-3xl leading-none hover:bg-brand-accent-dark active:scale-95 transition">
                        +
                    </button>
                </template>
            </div>
        </template>

        <button v-if="presentationMode" type="button" @click="togglePresentationMode"
                title="Exit presentation view"
                class="fixed top-4 right-4 z-50 inline-flex items-center justify-center size-10 rounded-full border border-brand-accent bg-white shadow-card-hover text-brand-accent-dark hover:bg-brand-accent/10 transition">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18"/>
                <path d="M6 6l12 12"/>
            </svg>
        </button>

        <div class="flex flex-col gap-4"
             :class="presentationMode ? 'w-full px-4 pt-4' : 'max-w-7xl mx-auto sm:px-6 lg:px-8'">
            <div v-if="!presentationMode" class="surface-card p-3 flex flex-col gap-3">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="text" v-model="searchQuery" placeholder="Search needs…"
                           class="h-10 pl-9 pr-3 text-sm rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                </div>

                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Filter by stage</span>
                        <button v-if="stageFilters.length" type="button" @click="stageFilters = []"
                                class="text-xs text-gray-400 hover:text-gray-600 underline">
                            Clear
                        </button>
                    </div>
                    <div v-for="group in groups" :key="group.id" class="flex flex-wrap items-center gap-1">
                        <button type="button" @click="toggleGroupFilter(group)"
                                class="text-[10px] font-semibold uppercase tracking-wider w-36 shrink-0 text-left transition"
                                :class="isGroupFilterActive(group) ? 'text-brand-accent-dark underline' : 'text-gray-400 hover:text-gray-600'">
                            {{ group.label }}
                        </button>
                        <button v-for="stage in group.stages" :key="stage.id"
                                type="button" @click="toggleStageFilter(stage.id)"
                                class="rounded-full text-xs font-semibold px-2 py-1 transition"
                                :style="stageBgStyle(stage.color)"
                                :class="stageFilters.includes(stage.id) ? 'ring-1 ring-current' : 'opacity-40 hover:opacity-70'">
                            {{ stage.label }} ({{ (needsByStage[stage.id] ?? []).length }})
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="loading" class="p-8 text-center text-sm text-gray-400">Loading…</div>
            <div v-else class="flex flex-col gap-6">
                <template v-for="(group, groupIndex) in visibleGroups" :key="group.id">
                    <div v-if="groupIndex > 0" class="h-px bg-gray-200"/>
                    <div class="flex flex-col gap-2">
                        <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider px-1">
                            {{ group.label }}
                        </div>
                        <div class="flex overflow-x-auto pb-2 gap-4">
                            <div v-for="stage in group.stages" :key="stage.id"
                                 class="shrink-0 flex flex-col rounded-xl bg-brand-surface border border-t-4 w-72"
                                 :style="{borderColor: stage.color}">
                                <div class="flex items-center justify-between border-b border-gray-200 p-3">
                                    <div class="font-semibold uppercase tracking-wide text-xs"
                                         :style="{color: stage.color}">
                                        {{ stage.label }}
                                    </div>
                                    <span class="text-xs text-gray-400">{{ visibleNeeds(stage.id).length }}</span>
                                </div>

                                <div class="flex-1 flex flex-col min-h-[4rem] gap-2 p-2"
                                     @dragover.prevent @drop="onDropOnColumn(stage.id)">
                                    <div v-for="need in visibleNeeds(stage.id)" :key="need.id"
                                         :draggable="!presentationMode" @dragstart="onDragStart(need)"
                                         @dragover.prevent @drop.stop="onDropOnCard(stage.id, need)"
                                         @click="openNeed(need)"
                                         class="surface-card transition p-3"
                                         :class="presentationMode ? '' : 'cursor-pointer hover:ring-1 hover:ring-brand-accent'">
                                        <div class="font-medium text-gray-900 text-sm">
                                            {{ need.title }}
                                        </div>
                                        <div v-if="need.business_owner" class="text-gray-400 mt-1 text-xs">
                                            {{ need.business_owner }}
                                        </div>
                                        <div v-if="!presentationMode && (need.jira_key || need.jira_url || need.confluence_url)" class="flex flex-wrap gap-1 mt-2">
                                            <a v-if="need.jira_url" :href="need.jira_url" target="_blank" rel="noopener" @click.stop
                                               :title="need.jira_key || 'Jira ticket'"
                                               class="inline-flex items-center justify-center size-6 rounded-full bg-blue-50 text-blue-700 hover:bg-blue-100 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
                                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42Z"/>
                                                    <circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>
                                                </svg>
                                            </a>
                                            <span v-else-if="need.jira_key" :title="need.jira_key"
                                                  class="inline-flex items-center justify-center size-6 rounded-full bg-blue-50 text-blue-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
                                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42Z"/>
                                                    <circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>
                                                </svg>
                                            </span>
                                            <a v-if="need.confluence_url" :href="need.confluence_url" target="_blank" rel="noopener" @click.stop
                                               title="Confluence page"
                                               class="inline-flex items-center justify-center size-6 rounded-full bg-brand-accent/10 text-brand-accent-dark hover:bg-brand-accent/20 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
                                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 7v14"/>
                                                    <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <Modal :show="showDetail" @close="closeDetail" max-width="2xl">
            <div v-if="loadingDetail" class="p-8 text-center text-sm text-gray-400">Loading…</div>
            <div v-else-if="selectedNeed" class="p-6 flex flex-col gap-4 max-h-[85vh] overflow-y-auto">
                <div class="flex items-start justify-between gap-2">
                    <input type="text" v-model="selectedNeed.title"
                           class="text-lg font-medium h-11 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                    <select :value="selectedNeed.need_stage_id" @change="changeStageFromModal"
                            class="h-11 shrink-0 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm font-medium"
                            :style="{color: stagesById[selectedNeed.need_stage_id]?.color}">
                        <option v-for="s in allStages" :key="s.id" :value="s.id">{{ s.label }}</option>
                    </select>
                </div>

                <textarea v-model="selectedNeed.description" rows="3" placeholder="Description…"
                          class="px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm"/>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div class="flex items-center gap-1">
                        <input type="text" v-model="selectedNeed.confluence_url" placeholder="Confluence URL"
                               class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
                        <a v-if="selectedNeed.confluence_url" :href="selectedNeed.confluence_url" target="_blank" rel="noopener"
                           title="Open in Confluence"
                           class="shrink-0 inline-flex items-center justify-center size-10 rounded-lg text-brand-navy hover:bg-gray-100 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <path d="M15 3h6v6"/>
                                <path d="M10 14 21 3"/>
                            </svg>
                        </a>
                    </div>
                    <div class="flex items-center gap-1">
                        <input type="text" v-model="selectedNeed.business_owner" placeholder="Business owner"
                               class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
                    </div>
                    <div class="flex items-center gap-1">
                        <input type="text" v-model="selectedNeed.jira_key" placeholder="Jira key (e.g. PROJ-123)"
                               class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
                    </div>
                    <div class="flex items-center gap-1">
                        <input type="text" v-model="selectedNeed.jira_url" placeholder="Jira URL"
                               class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
                        <a v-if="selectedNeed.jira_url" :href="selectedNeed.jira_url" target="_blank" rel="noopener"
                           title="Open in Jira"
                           class="shrink-0 inline-flex items-center justify-center size-10 rounded-lg text-brand-navy hover:bg-gray-100 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <path d="M15 3h6v6"/>
                                <path d="M10 14 21 3"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="text-[11px] leading-3 text-gray-500 h-3">
                    <span v-if="savingDetail" class="text-gray-400">Saving…</span>
                    <span v-else-if="savedDetail" class="text-brand-accent-dark font-medium">Saved</span>
                </div>

                <div class="border-t border-gray-100 pt-3 flex flex-col gap-2">
                    <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Activity</div>
                    <div class="flex flex-col gap-2 max-h-48 overflow-y-auto">
                        <div v-for="activity in selectedNeed.activities" :key="activity.id" class="text-sm">
                            <span class="text-gray-700">{{ activityText(activity) }}</span>
                            <span class="text-xs text-gray-400 ml-1">
                                — {{ activity.user?.name ?? 'Someone' }}, {{ formatDate(activity.created_at) }}
                            </span>
                        </div>
                        <div v-if="!selectedNeed.activities?.length" class="text-sm text-gray-400">No activity yet.</div>
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <input type="text" v-model="newNote" placeholder="Add a note or feedback…"
                               @keydown.enter="addNote" :disabled="addingNote"
                               class="h-9 px-2 text-sm rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                        <SaveButton @click="addNote"/>
                    </div>
                </div>

                <div class="flex justify-between items-center border-t border-gray-100 pt-3">
                    <template v-if="confirmingDelete">
                        <span class="text-sm text-red-600">Delete this need? This can't be undone.</span>
                        <div class="flex gap-2">
                            <SecondaryButton @click="confirmingDelete = false">Cancel</SecondaryButton>
                            <DangerButton @click="deleteNeed">Delete</DangerButton>
                        </div>
                    </template>
                    <template v-else>
                        <button type="button" @click="confirmingDelete = true"
                                class="text-sm text-red-600 hover:underline">
                            Delete need
                        </button>
                        <SecondaryButton @click="closeDetail">Close</SecondaryButton>
                    </template>
                </div>
            </div>
        </Modal>

        <Modal :show="showAddModal" @close="closeAddModal">
            <div class="p-6 flex flex-col gap-4">
                <h3 class="text-lg font-medium text-gray-900">Add a need</h3>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">Title</label>
                    <input type="text" v-model="newNeedTitle" placeholder="What does business need?" autofocus
                           @keydown.enter="addNeed"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">Description (optional)</label>
                    <textarea v-model="newNeedDescription" rows="3"
                              class="px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm"/>
                </div>
                <span class="text-xs text-gray-400">New needs always start in the first stage of your pipeline.</span>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="closeAddModal">Cancel</SecondaryButton>
                    <PrimaryButton @click="addNeed" :disabled="creating || !newNeedTitle.trim()">
                        {{ creating ? 'Adding…' : 'Add need' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <ManagePipelineModal :show="showManageModal" @close="closeManageModal"/>
    </AppLayout>
</template>
