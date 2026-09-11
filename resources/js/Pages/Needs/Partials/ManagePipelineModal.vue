<script setup>
import {reactive, ref, watch} from 'vue';
import debounce from 'lodash/debounce';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SaveButton from '@/Components/SaveButton.vue';
import SavedLabel from '@/Components/SavedLabel.vue';
import {useStore} from '@/Composables/store.js';

const props = defineProps({show: Boolean});
const emit = defineEmits(['close']);

const groups = ref([]);
const loading = ref(false);
const newGroupLabel = ref('');
const newStageLabel = reactive({});
const groupError = ref('');
const stageError = ref('');

const refresh = () => {
    loading.value = true;
    axios.get(route('need-stage-groups.index')).then(response => {
        groups.value = response.data;
    }).finally(() => loading.value = false);
}

watch(() => props.show, (show) => {
    if (show) refresh();
});

const close = () => emit('close');

// --- Groups ---

const addGroup = () => {
    const label = newGroupLabel.value.trim();
    if (!label) return;
    groupError.value = '';
    axios.post(route('need-stage-groups.store'), {label}).then(response => {
        groups.value.push({...response.data, stages: []});
        newGroupLabel.value = '';
        useStore().setSaved('Group added');
    }).catch((error) => {
        groupError.value = error.response?.data?.message ?? 'Could not add group';
    });
}

const saveGroupLabel = debounce((group) => {
    axios.patch(route('need-stage-groups.update', group.id), {label: group.label}).then(() => {
        useStore().setSaved('Saved');
    });
}, 600);

const deleteGroup = (group) => {
    groupError.value = '';
    axios.delete(route('need-stage-groups.destroy', group.id)).then(() => {
        groups.value = groups.value.filter(g => g.id !== group.id);
        useStore().setSaved('Group deleted');
    }).catch((error) => {
        groupError.value = error.response?.data?.message ?? 'Could not delete group';
    });
}

const draggingGroupId = ref(null);
const onGroupDragStart = (group) => draggingGroupId.value = group.id;

const onGroupDrop = (targetGroup) => {
    const id = draggingGroupId.value;
    draggingGroupId.value = null;
    if (id === null || id === targetGroup.id) return;

    const fromIndex = groups.value.findIndex(g => g.id === id);
    const toIndex = groups.value.findIndex(g => g.id === targetGroup.id);
    if (fromIndex === -1 || toIndex === -1) return;

    const [moved] = groups.value.splice(fromIndex, 1);
    groups.value.splice(toIndex, 0, moved);

    axios.post(route('need-stage-groups.reorder'), {ids: groups.value.map(g => g.id)}).then(() => {
        useStore().setSaved('Saved');
    });
}

// --- Stages ---

const addStage = (group) => {
    const label = (newStageLabel[group.id] || '').trim();
    if (!label) return;
    stageError.value = '';
    axios.post(route('need-stages.store'), {
        need_stage_group_id: group.id, label, color: '#9ca3af',
    }).then(response => {
        group.stages.push(response.data);
        newStageLabel[group.id] = '';
        useStore().setSaved('Stage added');
    }).catch((error) => {
        stageError.value = error.response?.data?.message ?? 'Could not add stage';
    });
}

const saveStage = debounce((stage) => {
    axios.patch(route('need-stages.update', stage.id), {label: stage.label, color: stage.color}).then(() => {
        useStore().setSaved('Saved');
    });
}, 600);

const deleteStage = (group, stage) => {
    stageError.value = '';
    axios.delete(route('need-stages.destroy', stage.id)).then(() => {
        group.stages = group.stages.filter(s => s.id !== stage.id);
        useStore().setSaved('Stage deleted');
    }).catch((error) => {
        stageError.value = error.response?.data?.message ?? 'Could not delete stage';
    });
}

const draggingStageId = ref(null);
const draggingStageFromGroupId = ref(null);

const onStageDragStart = (group, stage) => {
    draggingStageId.value = stage.id;
    draggingStageFromGroupId.value = group.id;
}

// stageId/fromGroupId are passed explicitly rather than read from the
// dragging refs here — the callers already reset those refs to null
// before calling this, since the drop has already been handled by then.
const persistStageMove = (stageId, fromGroupId, targetGroup) => {
    const ids = targetGroup.stages.map(s => s.id);
    const request = fromGroupId === targetGroup.id
        ? Promise.resolve()
        : axios.patch(route('need-stages.move', stageId), {group_id: targetGroup.id});
    request
        .then(() => axios.post(route('need-stages.reorder'), {group_id: targetGroup.id, ids}))
        .then(() => useStore().setSaved('Saved'))
        .catch((error) => {
            stageError.value = error.response?.data?.message ?? 'Could not move stage';
        });
}

const onStageDropOnStage = (targetGroup, targetStage) => {
    const id = draggingStageId.value;
    const fromGroupId = draggingStageFromGroupId.value;
    draggingStageId.value = null;
    draggingStageFromGroupId.value = null;
    if (id === null) return;

    const fromGroup = groups.value.find(g => g.id === fromGroupId);
    const fromIndex = fromGroup?.stages.findIndex(s => s.id === id) ?? -1;
    if (fromIndex === -1) return;

    const [moved] = fromGroup.stages.splice(fromIndex, 1);
    let toIndex = targetGroup.stages.findIndex(s => s.id === targetStage.id);
    if (toIndex === -1) toIndex = targetGroup.stages.length;
    targetGroup.stages.splice(toIndex, 0, moved);

    persistStageMove(id, fromGroupId, targetGroup);
}

const onStageDropOnGroup = (targetGroup) => {
    const id = draggingStageId.value;
    const fromGroupId = draggingStageFromGroupId.value;
    draggingStageId.value = null;
    draggingStageFromGroupId.value = null;
    if (id === null) return;

    const fromGroup = groups.value.find(g => g.id === fromGroupId);
    const fromIndex = fromGroup?.stages.findIndex(s => s.id === id) ?? -1;
    if (fromIndex === -1) return;

    const [moved] = fromGroup.stages.splice(fromIndex, 1);
    targetGroup.stages.push(moved);

    persistStageMove(id, fromGroupId, targetGroup);
}
</script>

<template>
    <Modal :show="show" @close="close" max-width="2xl">
        <div class="p-6 flex flex-col gap-4 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Manage pipeline</h3>
                <SavedLabel/>
            </div>
            <p class="text-xs text-gray-400">
                Drag a stage onto another group to move it there, or drag a group to reorder the pipeline.
            </p>

            <div v-if="loading" class="p-8 text-center text-sm text-gray-400">Loading…</div>
            <template v-else>
                <div v-if="groupError" class="text-sm text-red-600">{{ groupError }}</div>
                <div v-if="stageError" class="text-sm text-red-600">{{ stageError }}</div>

                <div v-for="group in groups" :key="group.id"
                     draggable="true" @dragstart="onGroupDragStart(group)"
                     @dragover.prevent @drop="onGroupDrop(group)"
                     class="surface-card p-3 flex flex-col gap-2 cursor-move">
                    <div class="flex items-center gap-2">
                        <input type="text" v-model="group.label" @input="saveGroupLabel(group)"
                               class="h-9 px-2 text-sm font-medium rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                        <button type="button" @click="deleteGroup(group)"
                                class="shrink-0 text-xs text-red-500 hover:underline">
                            Delete
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-2 pl-2 min-h-[2.5rem]"
                         @dragover.prevent @drop.stop="onStageDropOnGroup(group)">
                        <div v-for="stage in group.stages" :key="stage.id"
                             draggable="true" @dragstart.stop="onStageDragStart(group, stage)"
                             @dragover.prevent @drop.stop="onStageDropOnStage(group, stage)"
                             class="flex items-center gap-1 rounded-lg border border-gray-200 px-2 py-1 cursor-move">
                            <input type="color" v-model="stage.color" @input="saveStage(stage)"
                                   class="size-6 rounded border-0 p-0 cursor-pointer">
                            <input type="text" v-model="stage.label" @input="saveStage(stage)"
                                   class="h-7 px-1 text-xs rounded border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition w-28">
                            <button type="button" @click="deleteStage(group, stage)"
                                    class="shrink-0 text-gray-400 hover:text-red-600 transition">
                                ×
                            </button>
                        </div>

                        <input type="text" v-model="newStageLabel[group.id]" placeholder="+ Add stage"
                               @keydown.enter="addStage(group)"
                               class="h-9 px-2 text-xs rounded-lg border-dashed border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition w-28">
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="text" v-model="newGroupLabel" placeholder="New group name" @keydown.enter="addGroup"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                    <SaveButton @click="addGroup"/>
                </div>
            </template>

            <div class="flex justify-end pt-2">
                <SecondaryButton @click="close">Close</SecondaryButton>
            </div>
        </div>
    </Modal>
</template>
