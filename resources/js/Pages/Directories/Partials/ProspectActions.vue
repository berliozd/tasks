<script setup>
import {ref} from "vue";
import DeleteModal from "@/Pages/Tasks/Partials/DeleteModal.vue";

const props = defineProps({prospectId: Number});

const TYPES = ['email', 'call', 'linkedin', 'meeting', 'other'];
const STATUSES = ['planned', 'sent', 'replied', 'bounced', 'no_response', 'won', 'lost'];

const actions = ref([]);
const loading = ref(true);
const newAction = ref({type: 'email', message: '', status: 'planned', scheduled_at: toDatetimeLocal(new Date())});

function toDatetimeLocal(date) {
    const d = new Date(date);
    if (isNaN(d)) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const refreshActions = () => {
    loading.value = true;
    axios.get(route('prospect-actions.index', props.prospectId)).then(response => {
        actions.value = response.data;
    }).finally(() => loading.value = false);
}

const logAction = () => {
    if (!newAction.value.message) return;
    axios.post(route('prospect-actions.store', props.prospectId), newAction.value).then(() => {
        newAction.value = {type: 'email', message: '', status: 'planned', scheduled_at: toDatetimeLocal(new Date())};
        refreshActions();
    });
}

const updateStatus = (action) => {
    axios.patch(route('prospect-actions.update', action.id), {status: action.status});
}

const deleteAction = (action) => {
    axios.delete(route('prospect-actions.destroy', action.id)).then(() => refreshActions());
}

refreshActions();
</script>

<template>
    <div class="rounded-lg bg-brand-surface p-3 flex flex-col gap-3">
        <div class="flex flex-col sm:flex-row gap-2">
            <select v-model="newAction.type"
                    class="h-9 px-2 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
                <option v-for="t in TYPES" :key="t" :value="t">{{ t }}</option>
            </select>
            <input type="datetime-local" v-model="newAction.scheduled_at"
                   class="h-9 px-2 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
            <select v-model="newAction.status"
                    class="h-9 px-2 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
                <option v-for="s in STATUSES" :key="s" :value="s">{{ s }}</option>
            </select>
        </div>
        <div class="flex gap-2">
            <textarea v-model="newAction.message" placeholder="Message" rows="2"
                      class="text-sm px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"/>
            <button type="button" @click="logAction"
                    class="shrink-0 self-start inline-flex items-center px-3 py-2 bg-brand-navy border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light transition">
                Log action
            </button>
        </div>

        <div v-if="loading" class="text-xs text-gray-400">Loading…</div>
        <div v-else-if="!actions.length" class="text-xs text-gray-400">No actions logged yet.</div>
        <div v-else class="flex flex-col divide-y divide-gray-200">
            <div v-for="action in actions" :key="action.id" class="flex items-start gap-3 py-2">
                <span class="shrink-0 text-xs font-medium text-gray-500 w-16 capitalize">{{ action.type }}</span>
                <span class="shrink-0 text-xs text-gray-400 w-36">
                    {{ action.scheduled_at ? new Date(action.scheduled_at).toLocaleString() : '' }}
                </span>
                <span class="text-sm text-gray-700 flex-1 whitespace-pre-wrap">{{ action.message }}</span>
                <select v-model="action.status" @change="updateStatus(action)"
                        class="shrink-0 h-8 px-2 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-xs">
                    <option v-for="s in STATUSES" :key="s" :value="s">{{ s }}</option>
                </select>
                <div class="shrink-0 w-6 h-6 flex items-center justify-center rounded-full hover:bg-red-50 transition">
                    <DeleteModal @deleted="deleteAction(action)" label="Delete this logged action?"/>
                </div>
            </div>
        </div>
    </div>
</template>
