<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {ref, watch} from "vue";
import SaveButton from "@/Components/SaveButton.vue";
import SavedLabel from "@/Components/SavedLabel.vue";
import Modal from "@/Components/Modal.vue";
import DeleteConfirmPopover from "@/Pages/Directories/Partials/DeleteConfirmPopover.vue";
import ProspectActions from "@/Pages/Directories/Partials/ProspectActions.vue";
import EmailTemplates from "@/Pages/Directories/Partials/EmailTemplates.vue";
import debounce from "lodash/debounce";
import {Link} from "@inertiajs/vue3";

const props = defineProps({directoryId: Number});

const directory = ref({name: '', prompt: '', prospects: []});
const generateCount = ref(5);
const generating = ref(false);
const errorMsg = ref('');
const newProspect = ref({name: '', website: '', email: ''});
let storedDirectorySnapshot = null;
let watchDirectoryActive = false;
const savingDirectory = ref(false);
const savedDirectory = ref(false);
let savedDirectoryTimer = null;

const activeProspect = ref(null);
const savingActive = ref(false);
const savedActive = ref(false);
let savedActiveTimer = null;
let activeProspectSnapshot = null;

const refreshDirectory = () => {
    axios.get(route('directories.show', props.directoryId)).then(response => {
        directory.value = response.data;
        storedDirectorySnapshot = cleanDirectory(directory.value);
        watchDirectoryActive = true;
    });
}

const cleanDirectory = (d) => JSON.stringify({name: d.name, prompt: d.prompt});

watch(() => [directory.value.name, directory.value.prompt], () => {
    if (!watchDirectoryActive) return;
    if (cleanDirectory(directory.value) === storedDirectorySnapshot) return;
    debouncedUpdateDirectory();
});

const updateDirectory = () => {
    savingDirectory.value = true;
    axios.patch(route('directories.update', props.directoryId), {
        name: directory.value.name,
        prompt: directory.value.prompt,
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
        .then(() => refreshDirectory())
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
    });
}

const deleteProspect = (prospect) => {
    axios.delete(route('prospects.delete', prospect.id)).then(() => {
        if (activeProspect.value?.id === prospect.id) activeProspect.value = null;
        refreshDirectory();
    });
}

const cleanProspect = (p) => JSON.stringify({name: p.name, website: p.website, email: p.email});

const openProspect = (prospect) => {
    activeProspect.value = prospect;
    activeProspectSnapshot = cleanProspect(prospect);
}

const closeProspect = () => {
    activeProspect.value = null;
}

watch(() => activeProspect.value && [activeProspect.value.name, activeProspect.value.website, activeProspect.value.email], () => {
    if (!activeProspect.value) return;
    if (cleanProspect(activeProspect.value) === activeProspectSnapshot) return;
    debouncedUpdateActiveProspect();
});

const updateActiveProspect = () => {
    const prospect = activeProspect.value;
    if (!prospect) return;
    savingActive.value = true;
    axios.patch(route('prospects.update', prospect.id), {
        name: prospect.name,
        website: prospect.website,
        email: prospect.email,
    }).then(() => {
        activeProspectSnapshot = cleanProspect(prospect);
        savingActive.value = false;
        savedActive.value = true;
        if (savedActiveTimer) clearTimeout(savedActiveTimer);
        savedActiveTimer = setTimeout(() => savedActive.value = false, 1500);
    }).catch(() => {
        savingActive.value = false;
    });
}
const debouncedUpdateActiveProspect = debounce(updateActiveProspect, 600);

refreshDirectory();
</script>

<template>
    <AppLayout :title="directory.name || 'Directory'">

        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('directories')" class="text-sm text-gray-500 hover:text-gray-700">
                    Prospection
                </Link>
                <span class="text-gray-300">/</span>
                <h2 class="font-semibold text-xl leading-tight text-slate-900">{{ directory.name || '...' }}</h2>
            </div>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-6">

            <div class="min-h-6">
                <SavedLabel/>
            </div>

            <div class="surface-card">
                <div class="p-4 flex flex-col gap-2">
                    <label class="text-xs font-medium text-gray-500">Name</label>
                    <input type="text" v-model="directory.name"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                    <label class="text-xs font-medium text-gray-500 mt-2">AI prompt / criteria</label>
                    <textarea v-model="directory.prompt" rows="2"
                              class="px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"/>
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
                </div>
            </div>

            <div class="surface-card">
                <div class="p-4 flex flex-col gap-2 border-b border-gray-100">
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
                        <span v-if="prospect.actions_count"
                              class="shrink-0 inline-flex items-center justify-center rounded-full bg-brand-accent/10 text-brand-accent-dark text-xs font-semibold tabular-nums min-w-6 h-6 px-2">
                            {{ prospect.actions_count }} action{{ prospect.actions_count === 1 ? '' : 's' }}
                        </span>
                        <span v-else class="shrink-0 text-xs text-gray-400">No actions yet</span>
                        <div @click.stop>
                            <DeleteConfirmPopover @deleted="deleteProspect(prospect)"
                                                   label="Delete this prospect? Its logged actions will be deleted too."/>
                        </div>
                    </div>
                </div>
            </div>

            <EmailTemplates :directory-id="directoryId"/>
        </div>

        <Modal :show="!!activeProspect" @close="closeProspect" max-width="4xl">
            <div v-if="activeProspect" class="p-6 flex flex-col gap-4 max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">{{ activeProspect.name || 'Prospect' }}</h3>
                    <button type="button" @click="closeProspect" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-xs font-medium text-gray-500">Name</label>
                    <input type="text" v-model="activeProspect.name"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                    <label class="text-xs font-medium text-gray-500 mt-1">Website</label>
                    <input type="text" v-model="activeProspect.website"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                    <label class="text-xs font-medium text-gray-500 mt-1">Email</label>
                    <input type="email" v-model="activeProspect.email"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                    <div class="text-[11px] leading-3 text-gray-500 h-3">
                        <span v-if="savingActive" class="text-gray-400">Saving…</span>
                        <span v-else-if="savedActive" class="text-brand-accent-dark font-medium">Saved</span>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <ProspectActions :prospect-id="activeProspect.id" :directory-id="directoryId"
                                      @count-changed="activeProspect.actions_count = $event"/>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
