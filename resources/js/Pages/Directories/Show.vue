<script setup>
import ProspectionLayout from '@/Layouts/ProspectionLayout.vue';
import {ref, watch} from "vue";
import SaveButton from "@/Components/SaveButton.vue";
import SavedLabel from "@/Components/SavedLabel.vue";
import CollapsibleSection from "@/Components/CollapsibleSection.vue";
import DeleteConfirmPopover from "@/Pages/Directories/Partials/DeleteConfirmPopover.vue";
import EmailTemplates from "@/Pages/Directories/Partials/EmailTemplates.vue";
import debounce from "lodash/debounce";
import {Link, router} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";

const props = defineProps({directoryId: Number});

const directory = ref({name: '', prompt: '', from_label: '', default_reply_to_email: '', product: null, prospects: []});
const generateCount = ref(5);
const generating = ref(false);
const errorMsg = ref('');
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

const generateProspects = () => {
    generating.value = true;
    errorMsg.value = '';
    axios.post(route('directories.generate', props.directoryId), {count: generateCount.value})
        .then(() => {
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

const STATUS_LABELS = {
    planned: 'planned', sent: 'sent', replied: 'replied', bounced: 'bounced',
    no_response: 'no response', lost: 'lost',
};
const STATUS_COLORS = {
    replied: 'bg-blue-50 text-blue-700',
    bounced: 'bg-red-50 text-red-700',
    no_response: 'bg-red-50 text-red-700',
    lost: 'bg-red-50 text-red-700',
    sent: 'bg-brand-accent/10 text-brand-accent-dark',
    planned: 'bg-gray-100 text-gray-600',
};
const STATUS_ORDER = ['sent', 'replied', 'lost', 'bounced', 'no_response', 'planned'];

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
</script>

<template>
    <ProspectionLayout :title="directory.name || 'Directory'" :active-product-id="directory.product?.id"
                        :active-directory-id="directoryId">

        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('products')" class="text-sm text-gray-500 hover:text-gray-700">
                    Prospection
                </Link>
                <span class="text-gray-300">/</span>
                <Link v-if="directory.product" :href="route('products.view', directory.product.id)"
                      class="text-sm text-gray-500 hover:text-gray-700">
                    {{ directory.product.name }}
                </Link>
                <span class="text-gray-300">/</span>
                <h2 class="font-semibold text-xl leading-tight text-slate-900">{{ directory.name || '...' }}</h2>
            </div>
        </template>

        <SavedLabel/>

        <CollapsibleSection title="Directory details">
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

                    <div class="flex items-center gap-2 mt-2">
                        <input type="number" v-model.number="generateCount" min="1" max="50"
                               class="h-10 w-20 px-2 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                        <button type="button" @click="generateProspects" :disabled="generating || !directory.prompt"
                                class="inline-flex items-center px-4 py-2 bg-brand-navy border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light disabled:opacity-50 transition">
                            {{ generating ? 'Generating…' : 'Generate with AI' }}
                        </button>
                        <span class="text-xs text-gray-400">
                            Set a prompt above describing the kind of prospects you want.
                        </span>
                    </div>
                    <div v-if="errorMsg" class="text-sm text-red-600">{{ errorMsg }}</div>
        </CollapsibleSection>

            <CollapsibleSection title="Email templates" flush>
                <EmailTemplates :directory-id="directoryId"/>
            </CollapsibleSection>

            <div class="surface-card">
                <div class="p-4 flex flex-col gap-2 border-b border-gray-100">
                    <div class="text-sm font-medium text-gray-900">Prospects</div>
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
                </div>

                <div v-if="!(directory.prospects ?? []).length" class="p-8 text-center text-sm text-gray-400">
                    No prospects yet. Add one above, or generate some with AI.
                </div>
                <div v-else class="divide-y divide-gray-100">
                    <div v-for="prospect in directory.prospects" :key="prospect.id"
                         class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-brand-surface transition"
                         @click="openProspect(prospect)">
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
            </div>
    </ProspectionLayout>
</template>
