<script setup>
import {reactive, ref} from "vue";
import DeleteModal from "@/Pages/Tasks/Partials/DeleteModal.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({prospectId: Number, directoryId: Number});
const emit = defineEmits(['counts-changed']);

const TYPES = ['email', 'call', 'linkedin', 'meeting', 'other'];
const STATUSES = ['pending', 'planned', 'sent', 'replied', 'bounced', 'no_response', 'lost'];

const actions = ref([]);
const loading = ref(true);
const logging = ref(false);
const errorMsg = ref('');
const templates = ref([]);
const expandedIds = ref(new Set());
const expandedContentIds = ref(new Set());
const savedContentIds = ref(new Set());
const sendingIds = ref(new Set());
const rowErrors = reactive({});
const savedContentTimers = {};
const showLogModal = ref(false);

const toggleExpand = (action) => {
    const next = new Set(expandedIds.value);
    if (next.has(action.id)) next.delete(action.id);
    else next.add(action.id);
    expandedIds.value = next;
}

const toggleContent = (action) => {
    const next = new Set(expandedContentIds.value);
    if (next.has(action.id)) next.delete(action.id);
    else next.add(action.id);
    expandedContentIds.value = next;
}

const blankAction = () => ({
    type: 'email', subject: '', message: '', email_template_id: '',
});

const newAction = ref(blankAction());

const openLogModal = () => {
    newAction.value = blankAction();
    errorMsg.value = '';
    showLogModal.value = true;
}

const closeLogModal = () => {
    showLogModal.value = false;
}

function toDatetimeLocal(date) {
    const d = new Date(date);
    if (isNaN(d)) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

// <input type="datetime-local"> hands back a timezone-naive string (the
// browser's local wall-clock time, e.g. "2026-08-29T14:30"). Convert it to a
// real UTC instant before sending it anywhere, so the server isn't left to
// guess a timezone (it would otherwise assume UTC and store the wrong hour).
function localToUtcIso(localDatetimeString) {
    if (!localDatetimeString) return null;
    const d = new Date(localDatetimeString);
    return isNaN(d.getTime()) ? null : d.toISOString();
}

const countsByStatus = (list) => {
    const counts = Object.fromEntries(STATUSES.map(s => [`${s}_count`, 0]));
    for (const action of list) {
        const key = `${action.status}_count`;
        if (key in counts) counts[key]++;
    }
    return counts;
}

const refreshActions = () => {
    loading.value = true;
    axios.get(route('prospect-actions.index', props.prospectId)).then(response => {
        actions.value = response.data;
        emit('counts-changed', countsByStatus(actions.value));
    }).finally(() => loading.value = false);
}

const refreshTemplates = () => {
    if (!props.directoryId) return;
    axios.get(route('email-templates.index', props.directoryId)).then(response => {
        templates.value = response.data;
    });
}

const onTemplateSelected = () => {
    // Pre-fill the subject/message from the template, but don't clobber something already typed.
    const template = templates.value.find(t => t.id === newAction.value.email_template_id);
    if (!template) return;
    if (!newAction.value.subject) newAction.value.subject = template.subject ?? '';
    if (!newAction.value.message) newAction.value.message = template.body;
}

const logAction = () => {
    if (!newAction.value.message) return;
    errorMsg.value = '';
    logging.value = true;
    axios.post(route('prospect-actions.store', props.prospectId), newAction.value).then(() => {
        closeLogModal();
        refreshActions();
    }).catch((error) => {
        errorMsg.value = error.response?.data?.message ?? 'Could not log action';
    }).finally(() => logging.value = false);
}

const updateStatus = (action) => {
    axios.patch(route('prospect-actions.update', action.id), {status: action.status}).then(() => {
        emit('counts-changed', countsByStatus(actions.value));
    });
}

const updateMessage = (action) => {
    axios.patch(route('prospect-actions.update', action.id), {message: action.message}).then(() => {
        savedContentIds.value = new Set(savedContentIds.value).add(action.id);
        if (savedContentTimers[action.id]) clearTimeout(savedContentTimers[action.id]);
        savedContentTimers[action.id] = setTimeout(() => {
            const next = new Set(savedContentIds.value);
            next.delete(action.id);
            savedContentIds.value = next;
        }, 1500);
    });
}

const updateSchedule = (action) => {
    // Queuing an action for auto-send marks it planned (the row-level status
    // select is hidden while queued); unqueuing drops it back to pending.
    if (action.queued_for_send) {
        action.status = 'planned';
        // A schedule that isn't safely in the future would fire immediately
        // (or never, if already past) — bump it a few minutes out instead.
        const scheduled = action.scheduled_at ? new Date(action.scheduled_at) : null;
        if (!scheduled || isNaN(scheduled.getTime()) || scheduled <= new Date()) {
            action.scheduled_at = new Date(Date.now() + 5 * 60 * 1000).toISOString();
        }
    } else if (action.status === 'planned') {
        action.status = 'pending';
    }
    axios.patch(route('prospect-actions.update', action.id), {
        scheduled_at: action.scheduled_at,
        queued_for_send: action.queued_for_send,
        status: action.status,
        from_label: action.from_label,
        reply_to_email: action.reply_to_email,
    }).then(() => {
        emit('counts-changed', countsByStatus(actions.value));
    });
}

const sendNow = (action) => {
    delete rowErrors[action.id];
    sendingIds.value = new Set(sendingIds.value).add(action.id);
    axios.post(route('prospect-actions.send', action.id)).then(response => {
        const index = actions.value.findIndex(a => a.id === action.id);
        if (index !== -1) actions.value[index] = {...actions.value[index], ...response.data};
        emit('counts-changed', countsByStatus(actions.value));
    }).catch(error => {
        rowErrors[action.id] = error.response?.data?.message ?? 'Could not send email';
    }).finally(() => {
        const next = new Set(sendingIds.value);
        next.delete(action.id);
        sendingIds.value = next;
    });
}

const deleteAction = (action) => {
    axios.delete(route('prospect-actions.destroy', action.id)).then(() => refreshActions());
}

refreshActions();
refreshTemplates();
</script>

<template>
    <button type="button" @click="openLogModal"
            class="w-full h-14 flex items-center justify-center rounded-lg border-2 border-brand-accent text-brand-accent text-sm font-semibold uppercase tracking-widest hover:bg-brand-accent/10 active:scale-95 transition">
        + Log action
    </button>

    <Modal :show="showLogModal" @close="closeLogModal" max-width="md">
        <div class="p-4 flex flex-col gap-2">
            <div class="text-sm font-medium text-gray-900">Log a new action</div>
            <div class="flex flex-col sm:flex-row gap-2">
                <select v-model="newAction.type"
                        class="h-9 px-2 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
                    <option v-for="t in TYPES" :key="t" :value="t">{{ t }}</option>
                </select>
                <select v-model="newAction.email_template_id" @change="onTemplateSelected"
                        class="h-9 px-2 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
                    <option value="">No template</option>
                    <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
            </div>
            <input v-if="newAction.type === 'email'" type="text" v-model="newAction.subject" placeholder="Subject"
                   class="h-9 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
            <textarea v-model="newAction.message" placeholder="Message" rows="4"
                      class="text-sm px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"/>
            <div v-if="newAction.type === 'email'" class="text-[11px] text-gray-500">
                Email actions are logged as pending — send them (or schedule a send) from the list below once created.
            </div>
            <div v-if="errorMsg" class="text-xs text-red-600">{{ errorMsg }}</div>
            <div class="flex justify-end gap-2 mt-2">
                <button type="button" @click="closeLogModal"
                        class="inline-flex items-center px-4 py-2 rounded-lg font-semibold text-xs text-gray-600 uppercase tracking-widest hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="button" @click="logAction" :disabled="logging"
                        class="inline-flex items-center px-4 py-2 bg-brand-navy border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light disabled:opacity-50 transition">
                    {{ logging ? 'Logging…' : 'Log action' }}
                </button>
            </div>
        </div>
    </Modal>

    <div class="rounded-lg bg-brand-surface p-3 mt-3 flex flex-col gap-3">
        <div v-if="loading" class="text-xs text-gray-400">Loading…</div>
        <div v-else-if="!actions.length" class="text-xs text-gray-400">No actions logged yet.</div>
        <div v-else class="flex flex-col divide-y divide-gray-200">
            <div v-for="action in actions" :key="action.id">
                <div class="flex items-center flex-wrap gap-x-2 gap-y-1 sm:gap-3 py-2 cursor-pointer" @click="toggleExpand(action)">
                    <svg class="shrink-0 size-3.5 text-gray-400 transition-transform"
                         :class="expandedIds.has(action.id) ? 'rotate-90' : ''"
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="shrink-0 text-xs font-medium text-gray-500 sm:w-16 capitalize">{{ action.type }}</span>
                    <span class="hidden sm:block shrink-0 text-xs text-gray-400 sm:w-36">
                        {{ action.scheduled_at ? new Date(action.scheduled_at).toLocaleString() : '' }}
                        <span v-if="action.queued_for_send" class="text-brand-accent-dark">· queued</span>
                    </span>
                    <span class="text-sm text-gray-700 flex-1 min-w-0 basis-full sm:basis-0 order-last sm:order-none truncate">
                        {{ action.email_template ? action.email_template.name : action.message }}
                    </span>
                    <select v-if="!action.queued_for_send" v-model="action.status" @change="updateStatus(action)" @click.stop
                            class="shrink-0 h-8 pl-1 pr-6 sm:px-2 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-xs max-w-[6.5rem] sm:max-w-none">
                        <option v-for="s in STATUSES" :key="s" :value="s">{{ s }}</option>
                    </select>
                    <span v-else class="shrink-0 text-xs text-brand-accent-dark font-medium capitalize">{{ action.status }}</span>
                    <div class="ml-auto flex items-center gap-2">
                        <button v-if="action.type === 'email' && (action.status === 'pending' || action.status === 'planned')" type="button"
                                @click.stop="sendNow(action)" :disabled="sendingIds.has(action.id)"
                                class="shrink-0 inline-flex items-center px-2 py-1 bg-brand-navy border border-transparent rounded-lg font-semibold text-[11px] text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light disabled:opacity-50 transition">
                            {{ sendingIds.has(action.id) ? 'Sending…' : 'Send now' }}
                        </button>
                        <div @click.stop class="shrink-0 w-6 h-6 flex items-center justify-center rounded-full hover:bg-red-50 transition">
                            <DeleteModal @deleted="deleteAction(action)" label="Delete this logged action?"/>
                        </div>
                    </div>
                </div>
                <div v-if="rowErrors[action.id]" class="pb-2 pl-6 pr-2 text-xs text-red-600">
                    {{ rowErrors[action.id] }}
                </div>
                <div v-if="expandedIds.has(action.id)" class="pb-3 pl-6 pr-2 flex flex-col gap-2">
                    <span v-if="action.email_template" class="block text-xs text-brand-accent-dark font-medium">
                        Template: {{ action.email_template.name }}
                    </span>
                    <span v-if="action.subject" class="block text-xs text-gray-500 font-medium">
                        Subject: {{ action.subject }}
                    </span>
                    <span v-if="action.from_label || action.reply_to_email" class="block text-xs text-gray-500 font-medium">
                        <template v-if="action.from_label">From label: {{ action.from_label }}</template>
                        <span v-if="action.from_label && action.reply_to_email"> · </span>
                        <template v-if="action.reply_to_email">Reply-to: {{ action.reply_to_email }}</template>
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="toggleContent(action)"
                                class="self-start text-xs font-medium text-brand-navy hover:underline">
                            {{ expandedContentIds.has(action.id) ? 'Hide content' : 'Show content' }}
                        </button>
                        <span v-if="savedContentIds.has(action.id)" class="text-xs text-brand-accent-dark font-medium">
                            Saved
                        </span>
                    </div>
                    <textarea v-if="expandedContentIds.has(action.id)" v-model="action.message" rows="4"
                              @change="updateMessage(action)" @click.stop
                              class="text-sm text-gray-700 px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"/>

                    <div v-if="action.type === 'email' && (action.status === 'pending' || action.status === 'planned')"
                         class="mt-1 pt-2 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center gap-2">
                        <label class="flex items-center gap-2 text-xs text-gray-600 shrink-0">
                            <input type="checkbox" v-model="action.queued_for_send" @change="updateSchedule(action)"
                                   class="rounded border-gray-300 text-brand-accent focus:ring-brand-accent transition">
                            Auto-send at the scheduled time
                        </label>
                        <input type="datetime-local" :value="toDatetimeLocal(action.scheduled_at)"
                               @change="e => { action.scheduled_at = localToUtcIso(e.target.value); updateSchedule(action); }"
                               class="h-8 px-2 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-xs">
                        <input type="text" v-model="action.from_label" @change="updateSchedule(action)"
                               placeholder="From label (optional override)"
                               class="h-8 px-2 rounded-lg w-full sm:flex-1 border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-xs">
                        <input type="email" v-model="action.reply_to_email" @change="updateSchedule(action)"
                               placeholder="Reply-to (optional override)"
                               class="h-8 px-2 rounded-lg w-full sm:flex-1 border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-xs">
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
