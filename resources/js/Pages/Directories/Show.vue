<script>
import ProspectionLayout from '@/Layouts/ProspectionLayout.vue';

export default {
    layout: ProspectionLayout,
};
</script>

<script setup>
import {computed, ref, watch, watchEffect} from "vue";
import SavedLabel from "@/Components/SavedLabel.vue";
import CollapsibleSection from "@/Components/CollapsibleSection.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import DeleteConfirmPopover from "@/Pages/Directories/Partials/DeleteConfirmPopover.vue";
import EmailTemplates from "@/Pages/Directories/Partials/EmailTemplates.vue";
import debounce from "lodash/debounce";
import {Head, router} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";
import {STATUS_COLORS, STATUS_LABELS, STATUS_ORDER} from "@/Composables/prospectActionStatus.js";

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
const aiCount = ref(5);
const searchingWithAi = ref(false);

const stepAiCount = (delta) => {
    aiCount.value = Math.min(50, Math.max(1, aiCount.value + delta));
}

// Mirrors the query-building logic in DirectoryService::searchLinkedInProfiles()/
// searchCompanies(), so the popin can show the PO exactly what will be searched.
const baseSearchQuery = computed(() => (directory.value.prompt ?? '').trim());
const linkedInSearchQuery = computed(() => baseSearchQuery.value
    ? `${baseSearchQuery.value} site:linkedin.com/in`
    : '');
const aiResults = ref([]);
const aiSearchError = ref('');
const addingAiKeys = ref(new Set());
const newProspect = ref({name: '', website: '', email: ''});
const showAddProspectModal = ref(false);

const openAddProspectModal = () => {
    newProspect.value = {name: '', website: '', email: ''};
    linkedinResults.value = [];
    linkedinError.value = '';
    companyResults.value = [];
    companySearchError.value = '';
    aiResults.value = [];
    aiSearchError.value = '';
    showAddProspectModal.value = true;
}

const closeAddProspectModal = () => {
    showAddProspectModal.value = false;
}
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

const newProspectIds = ref(new Set());
const newProspectIdTimers = new Map();

// Merges into the existing set (rather than replacing it) and expires each
// id independently, so adding a second prospect doesn't clear the "New"
// badge that's still showing on the first one.
const markProspectsNew = (ids) => {
    if (!ids.length) return;
    const merged = new Set(newProspectIds.value);
    ids.forEach(id => merged.add(id));
    newProspectIds.value = merged;

    ids.forEach(id => {
        if (newProspectIdTimers.has(id)) clearTimeout(newProspectIdTimers.get(id));
        newProspectIdTimers.set(id, setTimeout(() => {
            const next = new Set(newProspectIds.value);
            next.delete(id);
            newProspectIds.value = next;
            newProspectIdTimers.delete(id);
        }, 30000));
    });
}

// Same mechanic as markProspectsNew, for prospects whose email was just
// filled in by the bulk "Find emails" action.
const updatedProspectIds = ref(new Set());
const updatedProspectIdTimers = new Map();

const markProspectsUpdated = (ids) => {
    if (!ids.length) return;
    const merged = new Set(updatedProspectIds.value);
    ids.forEach(id => merged.add(id));
    updatedProspectIds.value = merged;

    ids.forEach(id => {
        if (updatedProspectIdTimers.has(id)) clearTimeout(updatedProspectIdTimers.get(id));
        updatedProspectIdTimers.set(id, setTimeout(() => {
            const next = new Set(updatedProspectIds.value);
            next.delete(id);
            updatedProspectIds.value = next;
            updatedProspectIdTimers.delete(id);
        }, 30000));
    });
}

// Same mechanic again, for prospects the bulk "Find emails" action searched
// but came up empty for — so a mixed batch shows exactly which ones need a
// manual look, not just an aggregate count in the toast.
const notFoundProspectIds = ref(new Set());
const notFoundProspectIdTimers = new Map();

const markProspectsNotFound = (ids) => {
    if (!ids.length) return;
    const merged = new Set(notFoundProspectIds.value);
    ids.forEach(id => merged.add(id));
    notFoundProspectIds.value = merged;

    ids.forEach(id => {
        if (notFoundProspectIdTimers.has(id)) clearTimeout(notFoundProspectIdTimers.get(id));
        notFoundProspectIdTimers.set(id, setTimeout(() => {
            const next = new Set(notFoundProspectIds.value);
            next.delete(id);
            notFoundProspectIds.value = next;
            notFoundProspectIdTimers.delete(id);
        }, 30000));
    });
}

const searchWithAi = () => {
    searchingWithAi.value = true;
    aiSearchError.value = '';
    axios.post(route('directories.generate', props.directoryId), {count: aiCount.value})
        .then((response) => {
            aiResults.value = response.data.candidates ?? [];
            if (!aiResults.value.length) {
                aiSearchError.value = 'No new candidates found for this prompt.';
            }
        })
        .catch((error) => {
            aiSearchError.value = error.response?.data?.message ?? 'Could not search with AI';
        })
        .finally(() => searchingWithAi.value = false);
}

const addAiProspect = (result) => {
    const key = result.email || result.website || result.name;
    addingAiKeys.value = new Set(addingAiKeys.value).add(key);
    axios.post(route('prospects.store', props.directoryId), {
        name: result.name, website: result.website, email: result.email,
    })
        .then((response) => {
            aiResults.value = aiResults.value.filter(r => r !== result);
            markProspectsNew([response.data.id]);
            refreshDirectory();
            useStore().refreshProspectionTree();
        })
        .finally(() => {
            const next = new Set(addingAiKeys.value);
            next.delete(key);
            addingAiKeys.value = next;
        });
}

const linkedinResults = ref([]);
const searchingLinkedIn = ref(false);
const linkedinError = ref('');
const addingLinkedInUrls = ref(new Set());

const searchLinkedIn = () => {
    searchingLinkedIn.value = true;
    linkedinError.value = '';
    axios.post(route('directories.linkedin-search', props.directoryId), {count: 10})
        .then((response) => {
            linkedinResults.value = response.data;
            if (!response.data.length) {
                linkedinError.value = 'No new LinkedIn profiles found for this prompt.';
            }
        })
        .catch((error) => {
            linkedinError.value = error.response?.data?.message ?? 'Could not search LinkedIn';
        })
        .finally(() => searchingLinkedIn.value = false);
}

const addLinkedInProspect = (result) => {
    addingLinkedInUrls.value = new Set(addingLinkedInUrls.value).add(result.profile_url);
    axios.post(route('prospects.store', props.directoryId), {name: result.name, website: result.profile_url})
        .then((response) => {
            linkedinResults.value = linkedinResults.value.filter(r => r.profile_url !== result.profile_url);
            markProspectsNew([response.data.id]);
            refreshDirectory();
            useStore().refreshProspectionTree();
        })
        .finally(() => {
            const next = new Set(addingLinkedInUrls.value);
            next.delete(result.profile_url);
            addingLinkedInUrls.value = next;
        });
}

const companyResults = ref([]);
const searchingCompanies = ref(false);
const companySearchError = ref('');
const addingCompanyUrls = ref(new Set());

const searchCompanies = () => {
    searchingCompanies.value = true;
    companySearchError.value = '';
    axios.post(route('directories.company-search', props.directoryId), {count: 10})
        .then((response) => {
            companyResults.value = response.data;
            if (!response.data.length) {
                companySearchError.value = 'No new results found for this prompt.';
            }
        })
        .catch((error) => {
            companySearchError.value = error.response?.data?.message ?? 'Could not search the web';
        })
        .finally(() => searchingCompanies.value = false);
}

const addCompanyProspect = (result) => {
    addingCompanyUrls.value = new Set(addingCompanyUrls.value).add(result.website);
    axios.post(route('prospects.store', props.directoryId), {name: result.name, website: result.website})
        .then((response) => {
            companyResults.value = companyResults.value.filter(r => r.website !== result.website);
            markProspectsNew([response.data.id]);
            refreshDirectory();
            useStore().refreshProspectionTree();
        })
        .finally(() => {
            const next = new Set(addingCompanyUrls.value);
            next.delete(result.website);
            addingCompanyUrls.value = next;
        });
}

const addProspect = () => {
    if (!newProspect.value.name) return;
    axios.post(route('prospects.store', props.directoryId), newProspect.value).then((response) => {
        newProspect.value = {name: '', website: '', email: ''};
        markProspectsNew([response.data.id]);
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

// Persisted across directories/sessions, same convention as the tasks
// flag-filter (Tasks/Partials/Flags.vue) and highlight-late toggle.
const PROSPECT_FILTERS_STORAGE_KEY = 'directory-prospect-filters';

const loadStoredProspectFilters = () => {
    try {
        const parsed = JSON.parse(localStorage.getItem(PROSPECT_FILTERS_STORAGE_KEY) ?? '{}');
        return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (e) {
        return {};
    }
}
const storedProspectFilters = loadStoredProspectFilters();

// 'all' | 'yes' | 'no'
const excludedFilter = ref(storedProspectFilters.excludedFilter ?? 'all');
// Array of selected ProspectAction status values — a prospect matches if it
// has at least one action in any of the selected statuses (empty = any).
const statusFilters = ref(Array.isArray(storedProspectFilters.statusFilters) ? storedProspectFilters.statusFilters : []);
// 'all' | 'with' | 'without'
const emailFilter = ref(storedProspectFilters.emailFilter ?? 'all');
const actionsFilter = ref(storedProspectFilters.actionsFilter ?? 'all');

const persistProspectFilters = () => {
    try {
        localStorage.setItem(PROSPECT_FILTERS_STORAGE_KEY, JSON.stringify({
            excludedFilter: excludedFilter.value,
            emailFilter: emailFilter.value,
            actionsFilter: actionsFilter.value,
            statusFilters: statusFilters.value,
        }));
    } catch (e) {
        // localStorage unavailable (private mode, quota, ...) — filters still work for this session.
    }
}

watch([excludedFilter, emailFilter, actionsFilter, statusFilters], persistProspectFilters, {deep: true});

const toggleStatusFilter = (status) => {
    statusFilters.value = statusFilters.value.includes(status)
        ? statusFilters.value.filter(s => s !== status)
        : [...statusFilters.value, status];
}

const hasNoActions = (prospect) => STATUS_ORDER.every(s => !(prospect[`${s}_count`] > 0));

const filteredProspects = computed(() => {
    return (directory.value.prospects ?? []).filter(p => {
        if (excludedFilter.value === 'yes' && !p.is_excluded) return false;
        if (excludedFilter.value === 'no' && p.is_excluded) return false;
        if (actionsFilter.value === 'with' && hasNoActions(p)) return false;
        if (actionsFilter.value === 'without' && !hasNoActions(p)) return false;
        if (emailFilter.value === 'with' && !p.email) return false;
        if (emailFilter.value === 'without' && p.email) return false;
        if (statusFilters.value.length && !statusFilters.value.some(s => p[`${s}_count`] > 0)) return false;
        return true;
    });
});

const anyProspectFilterActive = computed(() => excludedFilter.value !== 'all'
    || emailFilter.value !== 'all' || actionsFilter.value !== 'all' || statusFilters.value.length > 0);

const clearProspectFilters = () => {
    excludedFilter.value = 'all';
    emailFilter.value = 'all';
    actionsFilter.value = 'all';
    statusFilters.value = [];
}

const toggleProspectSelected = (prospect) => {
    const next = new Set(selectedProspectIds.value);
    if (next.has(prospect.id)) next.delete(prospect.id);
    else next.add(prospect.id);
    selectedProspectIds.value = next;
}

const allProspectsSelected = computed(() => {
    const prospects = filteredProspects.value;
    return prospects.length > 0 && prospects.every(p => selectedProspectIds.value.has(p.id));
});

const toggleSelectAllProspects = () => {
    selectedProspectIds.value = allProspectsSelected.value
        ? new Set()
        : new Set(filteredProspects.value.map(p => p.id));
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

// --- Quick "log action" from the prospect list, without opening the prospect's own page ---

const LOG_ACTION_TYPES = ['email', 'call', 'linkedin', 'meeting', 'other'];
const showLogActionModal = ref(false);
const logActionProspect = ref(null);
const loggingAction = ref(false);
const logActionError = ref('');
const newLogAction = ref({type: 'email', subject: '', message: '', email_template_id: ''});

const openLogActionModal = (prospect) => {
    logActionProspect.value = prospect;
    newLogAction.value = {type: 'email', subject: '', message: '', email_template_id: ''};
    logActionError.value = '';
    showLogActionModal.value = true;
}

const closeLogActionModal = () => {
    showLogActionModal.value = false;
    logActionProspect.value = null;
}

const onLogActionTemplateSelected = () => {
    const template = templates.value.find(t => t.id === newLogAction.value.email_template_id);
    if (!template) return;
    if (!newLogAction.value.subject) newLogAction.value.subject = template.subject ?? '';
    if (!newLogAction.value.message) newLogAction.value.message = template.body;
}

const submitLogAction = () => {
    if (!newLogAction.value.message || !logActionProspect.value) return;
    logActionError.value = '';
    loggingAction.value = true;
    axios.post(route('prospect-actions.store', logActionProspect.value.id), newLogAction.value).then(() => {
        closeLogActionModal();
        refreshDirectory();
        useStore().setSaved('Action logged');
    }).catch((error) => {
        logActionError.value = error.response?.data?.message ?? 'Could not log action';
    }).finally(() => loggingAction.value = false);
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


const actionFlags = (prospect) => {
    const flags = STATUS_ORDER
        .map(status => ({status, count: prospect[`${status}_count`] ?? 0}))
        .filter(s => s.count > 0)
        .map(s => ({...s, label: `${s.count} ${STATUS_LABELS[s.status]}`, colorClass: STATUS_COLORS[s.status]}));
    if (prospect.won) {
        flags.unshift({status: 'won', label: 'Won', colorClass: 'bg-blue-50 text-blue-700 border border-blue-600'});
    }
    if (prospect.is_excluded) {
        flags.unshift({status: 'excluded', label: 'Excluded', colorClass: 'bg-gray-100 text-gray-500 border border-gray-300'});
    }
    return flags;
}

const findingEmailsForSelected = ref(false);

const findEmailsForSelected = async () => {
    // Only prospects that both lack an email and have a website to search —
    // mirrors the single-prospect "Find email from website" button's own
    // v-if condition on ProspectShow.vue.
    const candidates = (directory.value.prospects ?? [])
        .filter(p => selectedProspectIds.value.has(p.id) && !p.email && p.website);
    if (!candidates.length) return;

    findingEmailsForSelected.value = true;
    try {
        const results = await Promise.allSettled(candidates.map(p =>
            axios.post(route('prospects.find-email', p.id)).then(response => ({id: p.id, email: response.data.email}))
        ));
        const foundIds = results.filter(r => r.status === 'fulfilled').map(r => r.value.id);
        const notFoundIds = candidates.map(p => p.id).filter(id => !foundIds.includes(id));
        selectedProspectIds.value = new Set();
        refreshDirectory();
        markProspectsUpdated(foundIds);
        markProspectsNotFound(notFoundIds);

        const parts = [];
        if (foundIds.length) parts.push(`Found ${foundIds.length} email${foundIds.length === 1 ? '' : 's'}`);
        if (notFoundIds.length) parts.push(`${notFoundIds.length} not found`);
        useStore().setSaved(parts.length ? parts.join(', ') : 'No emails found');
    } finally {
        findingEmailsForSelected.value = false;
    }
}

const settingExcluded = ref(false);

const setExcludedForSelected = async (excluded) => {
    const ids = Array.from(selectedProspectIds.value);
    if (!ids.length) return;
    settingExcluded.value = true;
    try {
        await Promise.all(ids.map(id => axios.patch(route('prospects.update', id), {is_excluded: excluded})));
        selectedProspectIds.value = new Set();
        refreshDirectory();
        useStore().setSaved(`${excluded ? 'Excluded' : 'Included'} ${ids.length} prospect${ids.length === 1 ? '' : 's'}`);
    } finally {
        settingExcluded.value = false;
    }
}

const showDeleteSelectedModal = ref(false);
const deletingSelected = ref(false);

const openDeleteSelectedModal = () => {
    if (!selectedProspectIds.value.size) return;
    showDeleteSelectedModal.value = true;
}

const closeDeleteSelectedModal = () => {
    showDeleteSelectedModal.value = false;
}

const deleteSelectedProspects = async () => {
    const ids = Array.from(selectedProspectIds.value);
    if (!ids.length) return;
    deletingSelected.value = true;
    try {
        await Promise.all(ids.map(id => axios.delete(route('prospects.delete', id))));
        selectedProspectIds.value = new Set();
        showDeleteSelectedModal.value = false;
        refreshDirectory();
        useStore().refreshProspectionTree();
        useStore().setSaved(`Deleted ${ids.length} prospect${ids.length === 1 ? '' : 's'}`);
    } finally {
        deletingSelected.value = false;
    }
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
                    <div class="flex items-center gap-2">
                        <div class="text-sm font-medium text-gray-900">Prospects</div>
                        <div v-if="selectedProspectIds.size" class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">
                                {{ selectedProspectIds.size }} selected
                            </span>
                            <button type="button" @click="openScheduleModal"
                                    class="inline-flex items-center px-3 py-1.5 bg-brand-navy border border-transparent rounded-lg font-semibold text-[11px] text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light transition">
                                Schedule sending
                            </button>
                            <button type="button" @click="findEmailsForSelected" :disabled="findingEmailsForSelected"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 font-semibold text-[11px] text-gray-600 uppercase tracking-widest hover:bg-gray-100 disabled:opacity-50 transition">
                                {{ findingEmailsForSelected ? 'Searching…' : 'Search emails' }}
                            </button>
                            <button type="button" @click="setExcludedForSelected(true)" :disabled="settingExcluded"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 font-semibold text-[11px] text-gray-600 uppercase tracking-widest hover:bg-gray-100 disabled:opacity-50 transition">
                                Exclude
                            </button>
                            <button type="button" @click="setExcludedForSelected(false)" :disabled="settingExcluded"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 font-semibold text-[11px] text-gray-600 uppercase tracking-widest hover:bg-gray-100 disabled:opacity-50 transition">
                                Include
                            </button>
                            <button type="button" @click="openDeleteSelectedModal"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-red-300 font-semibold text-[11px] text-red-600 uppercase tracking-widest hover:bg-red-50 transition">
                                Delete
                            </button>
                        </div>
                        <button type="button" @click="openAddProspectModal" title="Add a prospect"
                                class="ml-auto shrink-0 inline-flex items-center justify-center size-12 rounded-full bg-brand-accent text-white text-3xl leading-none hover:bg-brand-accent-dark active:scale-95 transition">
                            +
                        </button>
                    </div>
                </div>

                <div v-if="!(directory.prospects ?? []).length" class="p-8 text-center text-sm text-gray-400">
                    No prospects yet. Add one above, or generate some with AI.
                </div>
                <template v-else>
                    <div class="flex flex-col gap-2 px-4 py-3 text-xs text-gray-500 border-b border-gray-100">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button type="button" @click="excludedFilter = excludedFilter === 'yes' ? 'all' : 'yes'"
                                    class="rounded-full text-xs font-semibold px-2 py-1 transition"
                                    :class="excludedFilter === 'yes' ? 'bg-red-600 text-white ring-1 ring-current' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'">
                                Excluded
                            </button>
                            <button type="button" @click="excludedFilter = excludedFilter === 'no' ? 'all' : 'no'"
                                    class="rounded-full text-xs font-semibold px-2 py-1 transition"
                                    :class="excludedFilter === 'no' ? 'bg-emerald-600 text-white ring-1 ring-current' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'">
                                Included
                            </button>
                            <span class="w-px h-4 bg-gray-200"/>
                            <button type="button" @click="emailFilter = emailFilter === 'with' ? 'all' : 'with'"
                                    class="rounded-full text-xs font-semibold px-2 py-1 transition"
                                    :class="emailFilter === 'with' ? 'bg-emerald-600 text-white ring-1 ring-current' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'">
                                With emails
                            </button>
                            <button type="button" @click="emailFilter = emailFilter === 'without' ? 'all' : 'without'"
                                    class="rounded-full text-xs font-semibold px-2 py-1 transition"
                                    :class="emailFilter === 'without' ? 'bg-amber-600 text-white ring-1 ring-current' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'">
                                Without emails
                            </button>
                            <span class="w-px h-4 bg-gray-200"/>
                            <button type="button" @click="actionsFilter = actionsFilter === 'with' ? 'all' : 'with'"
                                    class="rounded-full text-xs font-semibold px-2 py-1 transition"
                                    :class="actionsFilter === 'with' ? 'bg-blue-600 text-white ring-1 ring-current' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'">
                                With actions
                            </button>
                            <button type="button" @click="actionsFilter = actionsFilter === 'without' ? 'all' : 'without'"
                                    class="rounded-full text-xs font-semibold px-2 py-1 transition"
                                    :class="actionsFilter === 'without' ? 'bg-amber-600 text-white ring-1 ring-current' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'">
                                Without actions
                            </button>
                            <button v-if="anyProspectFilterActive" type="button" @click="clearProspectFilters"
                                    class="ml-auto text-gray-400 hover:text-gray-600 underline shrink-0">
                                Clear filters
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="shrink-0">Action status</span>
                            <button v-for="s in STATUS_ORDER" :key="s" type="button" @click="toggleStatusFilter(s)"
                                    class="rounded-full text-xs font-semibold px-2 py-1 transition"
                                    :class="statusFilters.includes(s) ? [STATUS_COLORS[s], 'ring-1 ring-current'] : 'bg-gray-100 text-gray-400 hover:bg-gray-200'">
                                {{ STATUS_LABELS[s] }}
                            </button>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 px-4 py-2 text-xs text-gray-500 border-b border-gray-100">
                        <input type="checkbox" :checked="allProspectsSelected" @change="toggleSelectAllProspects"
                               class="rounded border-gray-300 text-brand-accent focus:ring-brand-accent transition">
                        Select all
                    </label>
                    <div v-if="!filteredProspects.length" class="p-8 text-center text-sm text-gray-400">
                        No prospects match this filter.
                    </div>
                    <div v-else class="divide-y divide-gray-100">
                    <div v-for="prospect in filteredProspects" :key="prospect.id"
                         class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-brand-surface transition"
                         :class="newProspectIds.has(prospect.id) || updatedProspectIds.has(prospect.id) ? 'bg-brand-accent/5' : ''"
                         @click="openProspect(prospect)">
                        <div @click.stop>
                            <input type="checkbox" :checked="selectedProspectIds.has(prospect.id)"
                                   @change="toggleProspectSelected(prospect)"
                                   class="rounded border-gray-300 text-brand-accent focus:ring-brand-accent transition">
                        </div>
                        <div class="min-w-0 flex-1 flex items-center gap-2">
                            <div class="min-w-0 flex-1 flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-900 truncate">
                                    {{ prospect.name || 'Untitled prospect' }}
                                </span>
                                <span v-if="newProspectIds.has(prospect.id)"
                                      class="shrink-0 rounded-full bg-brand-accent text-white text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5">
                                    New
                                </span>
                                <span v-else-if="updatedProspectIds.has(prospect.id)"
                                      class="shrink-0 rounded-full bg-blue-600 text-white text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5">
                                    Updated
                                </span>
                                <span v-else-if="notFoundProspectIds.has(prospect.id)"
                                      class="shrink-0 rounded-full bg-gray-400 text-white text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5">
                                    Email not found
                                </span>
                            </div>
                            <div class="shrink-0 flex items-center gap-1.5">
                                <a v-if="prospect.website" :href="prospect.website" target="_blank" rel="noopener"
                                   @click.stop :data-tip="prospect.website"
                                   class="tooltip shrink-0 inline-flex items-center justify-center size-5 rounded-full bg-blue-50 text-blue-700 hover:bg-blue-100 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2v20"/>
                                        <path d="M2 12h20"/>
                                        <path d="M4.9 4.9l14.2 14.2"/>
                                        <path d="M19.1 4.9 4.9 19.1"/>
                                        <circle cx="12" cy="12" r="4"/>
                                        <circle cx="12" cy="12" r="8"/>
                                    </svg>
                                </a>
                                <a v-if="prospect.email" :href="`mailto:${prospect.email}`" @click.stop
                                   :data-tip="prospect.email"
                                   class="tooltip shrink-0 inline-flex items-center justify-center size-5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold hover:bg-emerald-100 transition">
                                    @
                                </a>
                                <span v-if="!prospect.website && !prospect.email" class="text-xs text-gray-300 whitespace-nowrap">
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
                        <button type="button" @click.stop="openLogActionModal(prospect)" title="Log an action"
                                class="shrink-0 inline-flex items-center justify-center size-7 rounded-full text-gray-400 hover:text-brand-accent-dark hover:bg-brand-accent/10 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                            </svg>
                        </button>
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

            <Modal :show="showDeleteSelectedModal" @close="closeDeleteSelectedModal">
                <div class="p-6 flex flex-col gap-4">
                    <h3 class="text-lg font-medium text-gray-900">Delete selected prospects?</h3>
                    <p class="text-sm text-gray-500">
                        {{ selectedProspectIds.size }} prospect{{ selectedProspectIds.size === 1 ? '' : 's' }} will be
                        permanently deleted, along with their logged actions. This can't be undone.
                    </p>
                    <div class="flex justify-end gap-2">
                        <SecondaryButton @click="closeDeleteSelectedModal">Cancel</SecondaryButton>
                        <DangerButton @click="deleteSelectedProspects" :disabled="deletingSelected">
                            {{ deletingSelected ? 'Deleting…' : 'Delete' }}
                        </DangerButton>
                    </div>
                </div>
            </Modal>

            <Modal :show="showLogActionModal" @close="closeLogActionModal" max-width="md">
                <div class="p-4 flex flex-col gap-2">
                    <div class="text-sm font-medium text-gray-900">
                        Log an action for {{ logActionProspect?.name || 'this prospect' }}
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <select v-model="newLogAction.type"
                                class="h-9 px-2 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
                            <option v-for="t in LOG_ACTION_TYPES" :key="t" :value="t">{{ t }}</option>
                        </select>
                        <select v-model="newLogAction.email_template_id" @change="onLogActionTemplateSelected"
                                class="h-9 px-2 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
                            <option value="">No template</option>
                            <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <input v-if="newLogAction.type === 'email'" type="text" v-model="newLogAction.subject" placeholder="Subject"
                           class="h-9 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
                    <textarea v-model="newLogAction.message" placeholder="Message" rows="4"
                              class="text-sm px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"/>
                    <div v-if="newLogAction.type === 'email'" class="text-[11px] text-gray-500">
                        Email actions are logged as pending — send them (or schedule a send) from the prospect's page once created.
                    </div>
                    <div v-else class="text-[11px] text-gray-500">
                        Logged as done immediately — this records something you already did.
                    </div>
                    <div v-if="logActionError" class="text-xs text-red-600">{{ logActionError }}</div>
                    <div class="flex justify-end gap-2 mt-2">
                        <SecondaryButton @click="closeLogActionModal">Cancel</SecondaryButton>
                        <PrimaryButton @click="submitLogAction" :disabled="loggingAction">
                            {{ loggingAction ? 'Logging…' : 'Log action' }}
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>

            <Modal :show="showAddProspectModal" @close="closeAddProspectModal" max-width="2xl">
                <div class="p-6 flex flex-col gap-4 max-h-[85vh] overflow-y-auto">
                    <h3 class="text-lg font-medium text-gray-900">Add prospects</h3>

                    <div class="flex flex-col gap-2">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-widest">Add manually</div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="text" v-model="newProspect.name" placeholder="Name" autofocus
                                   class="h-10 px-2 rounded-lg w-full sm:flex-1 border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                                   @keydown.enter="addProspect">
                            <input type="text" v-model="newProspect.website" placeholder="Website"
                                   class="h-10 px-2 rounded-lg w-full sm:flex-1 border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                                   @keydown.enter="addProspect">
                            <input type="email" v-model="newProspect.email" placeholder="Email"
                                   class="h-10 px-2 rounded-lg w-full sm:flex-1 border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                                   @keydown.enter="addProspect">
                            <button type="button" @click="addProspect"
                                    class="shrink-0 inline-flex items-center px-4 py-2 bg-brand-navy border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light transition">
                                Add
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 pt-3 border-t border-gray-100">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-widest">AI prompt / criteria</label>
                        <textarea v-model="directory.prompt" rows="2" placeholder="e.g. SaaS companies in Paris"
                                  class="px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm"/>
                        <span class="text-[11px] text-gray-400">
                            Used by all three search methods below.
                        </span>
                    </div>

                    <div class="flex flex-col gap-2 pt-3 border-t border-gray-100">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-widest">Search with AI</div>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center rounded-lg border border-gray-300 overflow-hidden">
                                <button type="button" @click="stepAiCount(-5)"
                                        class="h-10 w-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition">
                                    −
                                </button>
                                <input type="number" v-model.number="aiCount" min="1" max="50"
                                       class="h-10 w-14 px-1 text-center border-0 border-x border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                                <button type="button" @click="stepAiCount(5)"
                                        class="h-10 w-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition">
                                    +
                                </button>
                            </div>
                            <button type="button" @click="searchWithAi" :disabled="searchingWithAi || !directory.prompt"
                                    class="inline-flex items-center px-4 py-2 bg-brand-navy border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light disabled:opacity-50 transition">
                                {{ searchingWithAi ? 'Searching…' : 'Search' }}
                            </button>
                        </div>
                        <div class="text-xs text-gray-400 truncate">
                            {{ directory.prompt ? `Prompt: "${directory.prompt}"` : 'Set a prompt above describing the kind of prospects you want.' }}
                        </div>
                        <div v-if="aiSearchError" class="text-sm text-red-600">{{ aiSearchError }}</div>

                        <div v-if="aiResults.length" class="-mx-6 border-t border-gray-100 divide-y divide-gray-100">
                            <div v-for="result in aiResults" :key="result.email || result.website || result.name"
                                 class="flex items-center gap-3 px-6 py-3">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ result.name }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ result.website || result.email }}</div>
                                </div>
                                <a v-if="result.website" :href="result.website" target="_blank" rel="noopener"
                                   class="shrink-0 text-xs font-medium text-brand-navy hover:underline">
                                    Visit site ↗
                                </a>
                                <button type="button" @click="addAiProspect(result)"
                                        :disabled="addingAiKeys.has(result.email || result.website || result.name)"
                                        class="shrink-0 inline-flex items-center px-3 py-1.5 bg-brand-navy border border-transparent rounded-lg font-semibold text-[11px] text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light disabled:opacity-50 transition">
                                    {{ addingAiKeys.has(result.email || result.website || result.name) ? 'Adding…' : 'Add as prospect' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 pt-3 border-t border-gray-100">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-widest">Search LinkedIn</div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="searchLinkedIn" :disabled="searchingLinkedIn || !directory.prompt"
                                    class="inline-flex items-center px-4 py-2 rounded-lg border border-brand-accent font-semibold text-xs text-brand-accent uppercase tracking-widest hover:bg-brand-accent/10 disabled:opacity-50 transition">
                                {{ searchingLinkedIn ? 'Searching…' : 'Search LinkedIn' }}
                            </button>
                        </div>
                        <div class="text-xs text-gray-400 truncate">
                            {{ linkedInSearchQuery ? `Search term: "${linkedInSearchQuery}"` : 'Set a prompt above to search.' }}
                        </div>
                        <div v-if="linkedinError" class="text-sm text-red-600">{{ linkedinError }}</div>

                        <div v-if="linkedinResults.length" class="-mx-6 border-t border-gray-100 divide-y divide-gray-100">
                            <div v-for="result in linkedinResults" :key="result.profile_url"
                                 class="flex items-center gap-3 px-6 py-3">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ result.name }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ result.snippet || result.profile_url }}</div>
                                </div>
                                <a :href="result.profile_url" target="_blank" rel="noopener"
                                   class="shrink-0 text-xs font-medium text-brand-navy hover:underline">
                                    View profile ↗
                                </a>
                                <button type="button" @click="addLinkedInProspect(result)" :disabled="addingLinkedInUrls.has(result.profile_url)"
                                        class="shrink-0 inline-flex items-center px-3 py-1.5 bg-brand-navy border border-transparent rounded-lg font-semibold text-[11px] text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light disabled:opacity-50 transition">
                                    {{ addingLinkedInUrls.has(result.profile_url) ? 'Adding…' : 'Add as prospect' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 pt-3 border-t border-gray-100">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-widest">Basic web search</div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="searchCompanies" :disabled="searchingCompanies || !directory.prompt"
                                    class="inline-flex items-center px-4 py-2 rounded-lg border border-brand-accent font-semibold text-xs text-brand-accent uppercase tracking-widest hover:bg-brand-accent/10 disabled:opacity-50 transition">
                                {{ searchingCompanies ? 'Searching…' : 'Basic web search' }}
                            </button>
                        </div>
                        <div class="text-xs text-gray-400 truncate">
                            {{ baseSearchQuery ? `Search term: "${baseSearchQuery}"` : 'Set a prompt above to search.' }}
                        </div>
                        <div v-if="companySearchError" class="text-sm text-red-600">{{ companySearchError }}</div>

                        <div v-if="companyResults.length" class="-mx-6 border-t border-gray-100 divide-y divide-gray-100">
                            <div v-for="result in companyResults" :key="result.website"
                                 class="flex items-center gap-3 px-6 py-3">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ result.name }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ result.snippet || result.website }}</div>
                                </div>
                                <a :href="result.website" target="_blank" rel="noopener"
                                   class="shrink-0 text-xs font-medium text-brand-navy hover:underline">
                                    Visit site ↗
                                </a>
                                <button type="button" @click="addCompanyProspect(result)" :disabled="addingCompanyUrls.has(result.website)"
                                        class="shrink-0 inline-flex items-center px-3 py-1.5 bg-brand-navy border border-transparent rounded-lg font-semibold text-[11px] text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light disabled:opacity-50 transition">
                                    {{ addingCompanyUrls.has(result.website) ? 'Adding…' : 'Add as prospect' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="closeAddProspectModal"
                                class="inline-flex items-center px-4 py-2 rounded-lg font-semibold text-xs text-gray-600 uppercase tracking-widest hover:bg-gray-100 transition">
                            Close
                        </button>
                    </div>
                </div>
            </Modal>
</template>
