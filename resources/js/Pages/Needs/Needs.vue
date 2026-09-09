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
import {useStore} from '@/Composables/store.js';

// Mirrors App\Models\Need::STAGES / STAGE_LABELS.
const STAGES = [
    {key: 'discovery', label: 'Discovery'},
    {key: 'documented', label: 'Documented'},
    {key: 'jira_created', label: 'Jira ticket created'},
    {key: 'validated', label: 'Validated with business'},
    {key: 'grooming', label: 'Grooming'},
    {key: 'todo', label: 'To do'},
    {key: 'dev', label: 'Dev'},
    {key: 'qa', label: 'QA'},
    {key: 'soon', label: 'Coming soon'},
    {key: 'prod', label: 'In production'},
];

// One distinct color per stage, reused for the filter pills, column headers,
// and the stage badge shown elsewhere (e.g. Dashboard.vue). `border` colors
// all four sides — the column's own static classes only ever set border
// WIDTH (never a color), so there's no competing color class to tie with.
const STAGE_COLORS = {
    discovery: {bg: 'bg-gray-100', text: 'text-gray-600', border: 'border-gray-400'},
    documented: {bg: 'bg-indigo-50', text: 'text-indigo-700', border: 'border-indigo-400'},
    jira_created: {bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-400'},
    validated: {bg: 'bg-cyan-50', text: 'text-cyan-700', border: 'border-cyan-400'},
    grooming: {bg: 'bg-purple-50', text: 'text-purple-700', border: 'border-purple-400'},
    todo: {bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-400'},
    dev: {bg: 'bg-orange-50', text: 'text-orange-700', border: 'border-orange-400'},
    qa: {bg: 'bg-pink-50', text: 'text-pink-700', border: 'border-pink-400'},
    soon: {bg: 'bg-teal-50', text: 'text-teal-700', border: 'border-teal-400'},
    prod: {bg: 'bg-green-50', text: 'text-green-700', border: 'border-green-400'},
};

const loading = ref(true);
const needsByStage = reactive(Object.fromEntries(STAGES.map(s => [s.key, []])));

// Cumulative (OR) multi-select — empty means every stage is shown.
const stageFilters = ref([]);

const toggleStageFilter = (key) => {
    stageFilters.value = stageFilters.value.includes(key)
        ? stageFilters.value.filter(k => k !== key)
        : [...stageFilters.value, key];
}

const isGroupFilterActive = (group) => group.keys.every(k => stageFilters.value.includes(k));

// Toggles every stage in the group at once — cumulative with individual
// stage toggles, so a group filter can be combined with other selections.
const toggleGroupFilter = (group) => {
    stageFilters.value = isGroupFilterActive(group)
        ? stageFilters.value.filter(k => !group.keys.includes(k))
        : [...new Set([...stageFilters.value, ...group.keys])];
}

const visibleStages = computed(() => STAGES.filter(
    s => !stageFilters.value.length || stageFilters.value.includes(s.key),
));

// Groups the pipeline into the three phases relative to the dev/build/test
// pipeline itself: everything upstream of it, inside it, and downstream of
// it (release/deployment).
const STAGE_GROUPS = [
    {label: 'Before Dev Pipeline', keys: ['discovery', 'documented', 'jira_created', 'validated']},
    {label: 'In Dev Pipeline', keys: ['grooming', 'todo', 'dev', 'qa']},
    {label: 'After Dev Pipeline', keys: ['soon', 'prod']},
];

const visibleGroups = computed(() => STAGE_GROUPS
    .map(group => ({...group, stages: visibleStages.value.filter(s => group.keys.includes(s.key))}))
    .filter(group => group.stages.length));

const refreshBoard = () => {
    loading.value = true;
    axios.get(route('needs.index')).then(response => {
        STAGES.forEach(s => needsByStage[s.key] = []);
        response.data.forEach(need => {
            (needsByStage[need.stage] ?? needsByStage.discovery).push(need);
        });
    }).finally(() => loading.value = false);
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
        needsByStage.discovery.push(response.data);
        showAddModal.value = false;
    }).finally(() => creating.value = false);
}

// --- Drag and drop between/within stage columns ---

const draggingId = ref(null);
const draggingFromStage = ref(null);
const justDragged = ref(false);

const onDragStart = (need) => {
    draggingId.value = need.id;
    draggingFromStage.value = need.stage;
}

const persistDrop = (id, fromStage, targetStage) => {
    const changedStage = fromStage !== targetStage;
    const ids = needsByStage[targetStage].map(n => n.id);
    const request = changedStage
        ? axios.patch(route('needs.stage', id), {stage: targetStage})
        : Promise.resolve();
    request
        .then(() => axios.post(route('needs.reorder'), {stage: targetStage, ids}))
        .catch(() => refreshBoard())
        .finally(() => {
            justDragged.value = true;
            setTimeout(() => justDragged.value = false, 50);
        });
}

const onDropOnCard = (targetStage, targetNeed) => {
    const id = draggingId.value;
    const fromStage = draggingFromStage.value;
    draggingId.value = null;
    draggingFromStage.value = null;
    if (id === null || fromStage === null) return;

    const fromArr = needsByStage[fromStage];
    const fromIndex = fromArr.findIndex(n => n.id === id);
    if (fromIndex === -1) return;
    const [moved] = fromArr.splice(fromIndex, 1);
    moved.stage = targetStage;

    const toArr = needsByStage[targetStage];
    let toIndex = toArr.findIndex(n => n.id === targetNeed.id);
    if (toIndex === -1) toIndex = toArr.length;
    toArr.splice(toIndex, 0, moved);

    persistDrop(id, fromStage, targetStage);
}

const onDropOnColumn = (targetStage) => {
    const id = draggingId.value;
    const fromStage = draggingFromStage.value;
    draggingId.value = null;
    draggingFromStage.value = null;
    if (id === null || fromStage === null) return;

    const fromArr = needsByStage[fromStage];
    const fromIndex = fromArr.findIndex(n => n.id === id);
    if (fromIndex === -1) return;
    const [moved] = fromArr.splice(fromIndex, 1);
    moved.stage = targetStage;
    needsByStage[targetStage].push(moved);

    persistDrop(id, fromStage, targetStage);
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
    if (justDragged.value) return;
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
        const card = needsByStage[selectedNeed.value.stage]?.find(n => n.id === selectedNeed.value.id);
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
    const stage = event.target.value;
    if (!selectedNeed.value || stage === selectedNeed.value.stage) return;
    const fromStage = selectedNeed.value.stage;
    axios.patch(route('needs.stage', selectedNeed.value.id), {stage}).then(() => {
        const arr = needsByStage[fromStage];
        const idx = arr.findIndex(n => n.id === selectedNeed.value.id);
        if (idx !== -1) {
            const [moved] = arr.splice(idx, 1);
            moved.stage = stage;
            needsByStage[stage].push(moved);
        }
        selectedNeed.value.stage = stage;
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
    const stage = selectedNeed.value.stage;
    axios.delete(route('needs.destroy', id)).then(() => {
        needsByStage[stage] = needsByStage[stage].filter(n => n.id !== id);
        useStore().setSaved('Need deleted');
        closeDetail();
    });
}

const stageLabel = (key) => STAGES.find(s => s.key === key)?.label ?? key;
const formatDate = (date) => date ? format(new Date(date), 'MMM d, yyyy HH:mm') : '';

const activityText = computed(() => (activity) => {
    if (activity.type === 'created') return 'Need created';
    if (activity.type === 'stage_changed') {
        return `Moved from "${stageLabel(activity.from_stage)}" to "${stageLabel(activity.to_stage)}"`;
    }
    return activity.note;
});

refreshBoard();
</script>

<template>
    <Head title="Needs"/>
    <AppLayout title="Needs">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight text-slate-900">Needs</h2>
        </template>

        <div class="max-w-full mx-auto sm:px-6 lg:px-8 flex flex-col gap-4">
            <div class="surface-card p-3 flex flex-col gap-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">Filter by stage</span>
                    <button v-if="stageFilters.length" type="button" @click="stageFilters = []"
                            class="text-xs text-gray-400 hover:text-gray-600 underline">
                        Clear
                    </button>
                </div>
                <div v-for="group in STAGE_GROUPS" :key="group.label" class="flex flex-wrap items-center gap-1">
                    <button type="button" @click="toggleGroupFilter(group)"
                            class="text-[10px] font-semibold uppercase tracking-wider w-36 shrink-0 text-left transition"
                            :class="isGroupFilterActive(group) ? 'text-brand-accent-dark underline' : 'text-gray-400 hover:text-gray-600'">
                        {{ group.label }}
                    </button>
                    <button v-for="stage in STAGES.filter(s => group.keys.includes(s.key))" :key="stage.key"
                            type="button" @click="toggleStageFilter(stage.key)"
                            class="rounded-full text-xs font-semibold px-2 py-1 transition"
                            :class="[STAGE_COLORS[stage.key].bg, STAGE_COLORS[stage.key].text,
                                     stageFilters.includes(stage.key) ? 'ring-1 ring-current' : 'opacity-40 hover:opacity-70']">
                        {{ stage.label }} ({{ needsByStage[stage.key].length }})
                    </button>
                </div>
            </div>

            <div v-if="loading" class="p-8 text-center text-sm text-gray-400">Loading…</div>
            <div v-else class="flex flex-col gap-6">
                <template v-for="(group, groupIndex) in visibleGroups" :key="group.label">
                    <div v-if="groupIndex > 0" class="h-px bg-gray-200"/>
                    <div class="flex flex-col gap-2">
                        <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider px-1">
                            {{ group.label }}
                        </div>
                        <div class="flex gap-4 overflow-x-auto pb-2">
                            <div v-for="stage in group.stages" :key="stage.key"
                                 class="shrink-0 w-72 flex flex-col rounded-xl bg-brand-surface border border-t-4"
                                 :class="STAGE_COLORS[stage.key].border">
                                <div class="p-3 flex items-center justify-between border-b border-gray-200">
                                    <div class="text-xs font-semibold uppercase tracking-wide" :class="STAGE_COLORS[stage.key].text">
                                        {{ stage.label }}
                                    </div>
                                    <span class="text-xs text-gray-400">{{ needsByStage[stage.key].length }}</span>
                                </div>

                                <div class="flex-1 flex flex-col gap-2 p-2 min-h-[4rem]"
                         @dragover.prevent @drop="onDropOnColumn(stage.key)">
                        <div v-for="need in needsByStage[stage.key]" :key="need.id"
                             draggable="true" @dragstart="onDragStart(need)"
                             @dragover.prevent @drop.stop="onDropOnCard(stage.key, need)"
                             @click="openNeed(need)"
                             class="surface-card p-3 cursor-pointer hover:ring-1 hover:ring-brand-accent transition">
                            <div class="text-sm font-medium text-gray-900">{{ need.title }}</div>
                            <div v-if="need.business_owner" class="text-xs text-gray-400 mt-1">{{ need.business_owner }}</div>
                            <div v-if="need.jira_key || need.jira_url || need.confluence_url" class="flex flex-wrap gap-1 mt-2">
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
                    <select :value="selectedNeed.stage" @change="changeStageFromModal"
                            class="h-11 shrink-0 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm font-medium"
                            :class="STAGE_COLORS[selectedNeed.stage].text">
                        <option v-for="s in STAGES" :key="s.key" :value="s.key">{{ s.label }}</option>
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

        <button type="button" @click="openAddModal" title="Add a need"
                class="fixed bottom-6 right-6 z-40 flex items-center justify-center size-14 rounded-full bg-brand-accent text-white shadow-soft hover:bg-brand-accent-dark active:scale-95 transition">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
        </button>

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
                <span class="text-xs text-gray-400">New needs always start in the Discovery stage.</span>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="closeAddModal">Cancel</SecondaryButton>
                    <PrimaryButton @click="addNeed" :disabled="creating || !newNeedTitle.trim()">
                        {{ creating ? 'Adding…' : 'Add need' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
