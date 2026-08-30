<script>
import ProspectionLayout from '@/Layouts/ProspectionLayout.vue';

export default {
    layout: ProspectionLayout,
};
</script>

<script setup>
import {computed, ref, watch, watchEffect} from "vue";
import SaveButton from "@/Components/SaveButton.vue";
import SavedLabel from "@/Components/SavedLabel.vue";
import CollapsibleSection from "@/Components/CollapsibleSection.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DeleteConfirmPopover from "@/Pages/Directories/Partials/DeleteConfirmPopover.vue";
import EmailTemplates from "@/Pages/Directories/Partials/EmailTemplates.vue";
import debounce from "lodash/debounce";
import {Head, router} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";

const props = defineProps({directoryId: Number});

const directory = ref({name: '', prompt: '', from_label: '', default_reply_to_email: '', product: null, prospects: []});

watchEffect(() => {
    const crumbs = [{label: 'Prospection', href: route('products')}];
    if (directory.value.product) {
        crumbs.push({label: directory.value.product.name, href: route('products.view', directory.value.product.id)});
    }
    crumbs.push({label: directory.value.name || '...', href: null});
    useStore().setProspectionActive({
        productId: directory.value.product?.id,
        directoryId: props.directoryId,
        breadcrumb: crumbs,
    });
});
const generateCount = ref(5);
const generating = ref(false);
const errorMsg = ref('');
const generateResultMsg = ref('');
const newProspect = ref({name: '', website: '', email: ''});
let storedDirectorySnapshot = null;
let watchDirectoryActive = false;
const savingDirectory = ref(false);
const savedDirectory = ref(false);
let savedDirectoryTimer = null;

const refreshDirectory = () => {
    axios.get(route('directories.show', props.directoryId)).then(response => {
        directory.value = response.data;
        storedDirectorySnapshot = cleanDirectory(directory.value);
        watchDirectoryActive = true;
    });
}

const cleanDirectory = (d) => JSON.stringify({
    name: d.name, prompt: d.prompt,
    from_label: d.from_label, default_reply_to_email: d.default_reply_to_email,
});

watch(() => [
    directory.value.name, directory.value.prompt,
    directory.value.from_label, directory.value.default_reply_to_email,
], () => {
    if (!watchDirectoryActive) return;
    if (cleanDirectory(directory.value) === storedDirectorySnapshot) return;
    debouncedUpdateDirectory();
});

const updateDirectory = () => {
    savingDirectory.value = true;
    axios.patch(route('directories.update', props.directoryId), {
        name: directory.value.name,
        prompt: directory.value.prompt,
        from_label: directory.value.from_label,
        default_reply_to_email: directory.value.default_reply_to_email,
    }).then(() => {
        storedDirectorySnapshot = cleanDirectory(directory.value);
        savingDirectory.value = false;
        savedDirectory.value = true;
        if (savedDirectoryTimer) clearTimeout(savedDirectoryTimer);
        savedDirectoryTimer = setTimeout(() => savedDirectory.value = false, 1500);
    }).catch(() => {
        savingDirectory.value = false;
    });
}
const debouncedUpdateDirectory = debounce(updateDirectory, 600);

// Explains what actually happened — the AI can suggest names that turn out
// to be already-known contacts or unreachable websites, so a batch can come
// back with fewer prospects than requested, or none at all. Silently doing
// nothing in that case is confusing, so always summarize the outcome.
const describeGenerateResult = (result) => {
    const created = result.created_count ?? 0;
    const reasons = [];
    if (result.skipped_duplicate_count) reasons.push(`${result.skipped_duplicate_count} already known`);
    if (result.skipped_unreachable_count) reasons.push(`${result.skipped_unreachable_count} with an unreachable website`);
    if (result.skipped_incomplete_count) reasons.push(`${result.skipped_incomplete_count} incomplete`);
    const skipped = reasons.length ? (result.skipped_duplicate_count ?? 0) + (result.skipped_unreachable_count ?? 0) + (result.skipped_incomplete_count ?? 0) : 0;

    if (created === 0) {
        return skipped
            ? `No new prospects — all ${skipped} suggestion${skipped === 1 ? '' : 's'} ${skipped === 1 ? 'was' : 'were'} skipped (${reasons.join(', ')}).`
            : 'No new prospects were generated — try a different prompt.';
    }

    let message = `Added ${created} new prospect${created === 1 ? '' : 's'}.`;
    if (skipped) message += ` Skipped ${skipped} (${reasons.join(', ')}).`;
    return message;
}

const generateProspects = () => {
    generating.value = true;
    errorMsg.value = '';
    generateResultMsg.value = '';
    axios.post(route('directories.generate', props.directoryId), {count: generateCount.value})
        .then((response) => {
            generateResultMsg.value = describeGenerateResult(response.data);
            refreshDirectory();
            useStore().refreshProspectionTree();
        })
        .catch((error) => {
            errorMsg.value = error.response?.data?.message ?? 'Could not generate prospects';
        })
        .finally(() => generating.value = false);
}

const addProspect = () => {
    if (!newProspect.value.name) return;
    axios.post(route('prospects.store', props.directoryId), newProspect.value).then(() => {
        newProspect.value = {name: '', website: '', email: ''};
        refreshDirectory();
        useStore().refreshProspectionTree();
    });
}

const deleteProspect = (prospect) => {
    axios.delete(route('prospects.delete', prospect.id)).then(() => {
        refreshDirectory();
        useStore().refreshProspectionTree();
    });
}

const openProspect = (prospect) => {
    router.visit(route('prospects.view', [props.directoryId, prospect.id]));
}

const selectedProspectIds = ref(new Set());

const toggleProspectSelected = (prospect) => {
    const next = new Set(selectedProspectIds.value);
    if (next.has(prospect.id)) next.delete(prospect.id);
    else next.add(prospect.id);
    selectedProspectIds.value = next;
}

const allProspectsSelected = computed(() => {
    const prospects = directory.value.prospects ?? [];
    return prospects.length > 0 && prospects.every(p => selectedProspectIds.value.has(p.id));
});

const toggleSelectAllProspects = () => {
    selectedProspectIds.value = allProspectsSelected.value
        ? new Set()
        : new Set((directory.value.prospects ?? []).map(p => p.id));
}

const templates = ref([]);
const showScheduleModal = ref(false);
const scheduleTemplateId = ref('');
const scheduleDatetime = ref('');
const scheduling = ref(false);
const scheduleError = ref('');

const refreshTemplates = () => {
    axios.get(route('email-templates.index', props.directoryId)).then(response => {
        templates.value = response.data;
    });
}

function toDatetimeLocal(date) {
    const d = new Date(date);
    if (isNaN(d)) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

// <input type="datetime-local"> hands back a timezone-naive string (the
// browser's local wall-clock time). Convert it to a real UTC instant before
// sending it anywhere, so the server isn't left to guess a timezone.
function localToUtcIso(localDatetimeString) {
    if (!localDatetimeString) return null;
    const d = new Date(localDatetimeString);
    return isNaN(d.getTime()) ? null : d.toISOString();
}

const defaultScheduleDate = () => new Date(Date.now() + 5 * 60 * 1000);

const openScheduleModal = () => {
    scheduleTemplateId.value = '';
    scheduleDatetime.value = toDatetimeLocal(defaultScheduleDate());
    scheduleError.value = '';
    showScheduleModal.value = true;
}

const closeScheduleModal = () => {
    showScheduleModal.value = false;
}

const submitSchedule = async () => {
    if (!scheduleTemplateId.value) {
        scheduleError.value = 'Select a template';
        return;
    }
    const template = templates.value.find(t => t.id === scheduleTemplateId.value);
    if (!template) return;

    scheduling.value = true;
    scheduleError.value = '';
    // If left blank, or set to something not safely in the future, fall
    // back to a few minutes out — same convention as checking "auto-send"
    // on a single action.
    const chosen = localToUtcIso(scheduleDatetime.value);
    const scheduledAt = (chosen && new Date(chosen) > new Date()) ? chosen : defaultScheduleDate().toISOString();
    const ids = Array.from(selectedProspectIds.value);

    try {
        await Promise.all(ids.map(prospectId => axios.post(route('prospect-actions.store', prospectId), {
            type: 'email',
            email_template_id: template.id,
            subject: template.subject,
            message: template.body,
            status: 'planned',
            queued_for_send: true,
            scheduled_at: scheduledAt,
        })));
        selectedProspectIds.value = new Set();
        showScheduleModal.value = false;
        refreshDirectory();
        useStore().setSaved(`Scheduled ${ids.length} email${ids.length === 1 ? '' : 's'}`);
    } catch (error) {
        scheduleError.value = error.response?.data?.message ?? 'Could not schedule sending';
    } finally {
        scheduling.value = false;
    }
}

const STATUS_LABELS = {
    pending: 'pending', planned: 'planned', sent: 'sent', replied: 'replied', bounced: 'bounced',
    no_response: 'no response', lost: 'lost',
};
const STATUS_COLORS = {
    replied: 'bg-blue-50 text-blue-700',
    bounced: 'bg-red-50 text-red-700',
    no_response: 'bg-red-50 text-red-700',
    lost: 'bg-red-50 text-red-700',
    sent: 'bg-brand-accent/10 text-brand-accent-dark',
    planned: 'bg-brand-accent/10 text-brand-accent-dark',
    pending: 'bg-gray-100 text-gray-600',
};
const STATUS_ORDER = ['sent', 'replied', 'lost', 'bounced', 'no_response', 'planned', 'pending'];

const actionFlags = (prospect) => {
    const flags = STATUS_ORDER
        .map(status => ({status, count: prospect[`${status}_count`] ?? 0}))
        .filter(s => s.count > 0)
        .map(s => ({...s, label: `${s.count} ${STATUS_LABELS[s.status]}`, colorClass: STATUS_COLORS[s.status]}));
    if (prospect.won) {
        flags.unshift({status: 'won', label: 'Won', colorClass: 'bg-blue-50 text-blue-700 border border-blue-600'});
    }
    return flags;
}

refreshDirectory();
refreshTemplates();
</script>

<template>
    <Head :title="directory.name || 'Directory'"/>

    <SavedLabel/>

    <CollapsibleSection title="Directory details" default-open>
                    <label class="text-xs font-medium text-gray-500">Name</label>
                    <input type="text" v-model="directory.name"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                    <label class="text-xs font-medium text-gray-500 mt-2">AI prompt / criteria</label>
                    <textarea v-model="directory.prompt" rows="2"
                              class="px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"/>
                    <div class="flex flex-col sm:flex-row gap-2 mt-2">
                        <div class="w-full sm:flex-1 flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-500">From label override</label>
                            <input type="text" v-model="directory.from_label"
                                   :placeholder="directory.product?.from_label || 'e.g. Acme Team'"
                                   class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                            <span class="text-[11px] text-gray-400">
                                Optional — leave blank to use the product's from label. Emails send from no-reply@addeos.com.
                            </span>
                        </div>
                        <div class="w-full sm:flex-1 flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-500">Default reply-to email override</label>
                            <input type="email" v-model="directory.default_reply_to_email"
                                   :placeholder="directory.product?.default_reply_to_email || 'Optional'"
                                   class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                            <span class="text-[11px] text-gray-400">Optional — leave blank to use the product's default.</span>
                        </div>
                    </div>
                    <div class="w-16 text-[11px] leading-3 text-gray-500">
                        <span v-if="savingDirectory" class="text-gray-400">Saving…</span>
                        <span v-else-if="savedDirectory" class="text-brand-accent-dark font-medium">Saved</span>
                    </div>
        </CollapsibleSection>

            <CollapsibleSection title="Email templates" flush>
                <EmailTemplates :directory-id="directoryId"/>
            </CollapsibleSection>

            <div class="surface-card">
                <div class="p-4 flex flex-col gap-2 border-b border-gray-100">
                    <div class="flex items-center justify-between gap-2">
                        <div class="text-sm font-medium text-gray-900">Prospects</div>
                        <div v-if="selectedProspectIds.size" class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">
                                {{ selectedProspectIds.size }} selected
                            </span>
                            <button type="button" @click="openScheduleModal"
                                    class="inline-flex items-center px-3 py-1.5 bg-brand-navy border border-transparent rounded-lg font-semibold text-[11px] text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light transition">
                                Schedule sending
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" v-model="newProspect.name" placeholder="Name"
                               class="h-10 px-2 rounded-lg w-full sm:flex-1 border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                               @keydown.enter="addProspect">
                        <input type="text" v-model="newProspect.website" placeholder="Website"
                               class="h-10 px-2 rounded-lg w-full sm:flex-1 border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                               @keydown.enter="addProspect">
                        <input type="email" v-model="newProspect.email" placeholder="Email"
                               class="h-10 px-2 rounded-lg w-full sm:flex-1 border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                               @keydown.enter="addProspect">
                        <SaveButton @click="addProspect"/>
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <input type="number" v-model.number="generateCount" min="1" max="50"
                               class="h-10 w-20 px-2 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                        <button type="button" @click="generateProspects" :disabled="generating || !directory.prompt"
                                class="inline-flex items-center px-4 py-2 bg-brand-navy border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light disabled:opacity-50 transition">
                            {{ generating ? 'Generating…' : 'Generate with AI' }}
                        </button>
                        <span class="text-xs text-gray-400">
                            Set a prompt in Directory details describing the kind of prospects you want.
                        </span>
                    </div>
                    <div v-if="errorMsg" class="text-sm text-red-600">{{ errorMsg }}</div>
                    <div v-else-if="generateResultMsg" class="text-sm text-gray-500">{{ generateResultMsg }}</div>
                </div>

                <div v-if="!(directory.prospects ?? []).length" class="p-8 text-center text-sm text-gray-400">
                    No prospects yet. Add one above, or generate some with AI.
                </div>
                <template v-else>
                    <label class="flex items-center gap-2 px-4 py-2 text-xs text-gray-500 border-b border-gray-100">
                        <input type="checkbox" :checked="allProspectsSelected" @change="toggleSelectAllProspects"
                               class="rounded border-gray-300 text-brand-accent focus:ring-brand-accent transition">
                        Select all
                    </label>
                    <div class="divide-y divide-gray-100">
                    <div v-for="prospect in directory.prospects" :key="prospect.id"
                         class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-brand-surface transition"
                         @click="openProspect(prospect)">
                        <div @click.stop>
                            <input type="checkbox" :checked="selectedProspectIds.has(prospect.id)"
                                   @change="toggleProspectSelected(prospect)"
                                   class="rounded border-gray-300 text-brand-accent focus:ring-brand-accent transition">
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 truncate">
                                {{ prospect.name || 'Untitled prospect' }}
                            </div>
                            <div class="text-xs text-gray-500 truncate">
                                <a v-if="prospect.website" :href="prospect.website" target="_blank" rel="noopener"
                                   @click.stop class="hover:underline hover:text-brand-accent-dark">
                                    {{ prospect.website }}
                                </a>
                                <span v-if="prospect.website && prospect.email"> · </span>
                                <span v-if="prospect.email">{{ prospect.email }}</span>
                                <span v-if="!prospect.website && !prospect.email" class="text-gray-300">
                                    No website or email yet
                                </span>
                            </div>
                        </div>
                        <div v-if="actionFlags(prospect).length" class="shrink-0 flex flex-wrap items-center justify-end gap-1">
                            <span v-for="flag in actionFlags(prospect)" :key="flag.status"
                                  class="rounded-full text-xs font-semibold px-2 py-1"
                                  :class="flag.colorClass">
                                {{ flag.label }}
                            </span>
                        </div>
                        <span v-else class="shrink-0 text-xs text-gray-400">No actions yet</span>
                        <div @click.stop>
                            <DeleteConfirmPopover @deleted="deleteProspect(prospect)"
                                                   label="Delete this prospect? Its logged actions will be deleted too."/>
                        </div>
                    </div>
                    </div>
                </template>
            </div>

            <Modal :show="showScheduleModal" @close="closeScheduleModal">
                <div class="p-6 flex flex-col gap-4">
                    <h3 class="text-lg font-medium text-gray-900">Schedule sending</h3>
                    <p class="text-sm text-gray-500">
                        {{ selectedProspectIds.size }} prospect{{ selectedProspectIds.size === 1 ? '' : 's' }} selected.
                    </p>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-500">Email template</label>
                        <select v-model="scheduleTemplateId"
                                class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                            <option value="" disabled>Select a template</option>
                            <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <span v-if="!templates.length" class="text-[11px] text-gray-400">
                            No email templates yet — add one below first.
                        </span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-500">Send at</label>
                        <input type="datetime-local" v-model="scheduleDatetime"
                               class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                        <span class="text-[11px] text-gray-400">Leave as-is (or blank) to send a few minutes from now.</span>
                    </div>
                    <div v-if="scheduleError" class="text-sm text-red-600">{{ scheduleError }}</div>
                    <div class="flex justify-end gap-2">
                        <SecondaryButton @click="closeScheduleModal">Cancel</SecondaryButton>
                        <PrimaryButton @click="submitSchedule" :disabled="scheduling">
                            {{ scheduling ? 'Scheduling…' : 'Schedule' }}
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>
</template>
