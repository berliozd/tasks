<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {ref, watch} from "vue";
import SaveButton from "@/Components/SaveButton.vue";
import DeleteModal from "@/Pages/Tasks/Partials/DeleteModal.vue";
import ProspectActions from "@/Pages/Directories/Partials/ProspectActions.vue";
import debounce from "lodash/debounce";
import {Link} from "@inertiajs/vue3";

const props = defineProps({directoryId: Number});

const directory = ref({name: '', prompt: '', prospects: []});
const generateCount = ref(5);
const generating = ref(false);
const errorMsg = ref('');
const newProspect = ref({name: '', website: '', email: ''});
const expandedProspectId = ref(null);
let storedDirectorySnapshot = null;
let watchDirectoryActive = false;
const savingDirectory = ref(false);
const savedDirectory = ref(false);
let savedDirectoryTimer = null;

const savingProspectById = ref({});
const savedProspectById = ref({});
const savedProspectTimersById = ref({});
let storedProspects = null;
let watchProspectsActive = false;

const refreshDirectory = () => {
    axios.get(route('directories.show', props.directoryId)).then(response => {
        directory.value = response.data;
        storedDirectorySnapshot = cleanDirectory(directory.value);
        storedProspects = JSON.parse(JSON.stringify(directory.value.prospects ?? []));
        watchDirectoryActive = true;
        watchProspectsActive = true;
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
        if (expandedProspectId.value === prospect.id) expandedProspectId.value = null;
        refreshDirectory();
    });
}

const toggleExpand = (prospect) => {
    expandedProspectId.value = expandedProspectId.value === prospect.id ? null : prospect.id;
}

const cleanProspect = (p) => {
    const {id, name, website, email} = p;
    return {id, name, website, email};
}

const updateProspect = (prospect) => {
    savingProspectById.value[prospect.id] = true;
    axios.patch(route('prospects.update', prospect.id), {
        name: prospect.name,
        website: prospect.website,
        email: prospect.email,
    }).then(() => {
        storedProspects = JSON.parse(JSON.stringify(directory.value.prospects ?? []));
        savingProspectById.value[prospect.id] = false;
        savedProspectById.value[prospect.id] = true;
        if (savedProspectTimersById.value[prospect.id]) clearTimeout(savedProspectTimersById.value[prospect.id]);
        savedProspectTimersById.value[prospect.id] = setTimeout(() => {
            savedProspectById.value[prospect.id] = false;
        }, 1500);
    }).catch(() => {
        savingProspectById.value[prospect.id] = false;
    });
}
const debouncedUpdateProspect = debounce(updateProspect, 600);

watch(() => directory.value.prospects, () => {
    if (!watchProspectsActive) return;
    for (const prospect of (directory.value.prospects ?? [])) {
        const stored = (storedProspects ?? []).find(p => p.id === prospect.id);
        if (!stored) continue;
        if (JSON.stringify(cleanProspect(stored)) !== JSON.stringify(cleanProspect(prospect))) {
            debouncedUpdateProspect(prospect);
        }
    }
}, {deep: true});

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
                            Placeholder generator for now — set a prompt above, this doesn't call a real AI yet.
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
                    <div v-for="prospect in directory.prospects" :key="prospect.id">
                        <div class="flex items-center gap-3 px-4 py-3">
                            <button type="button" @click="toggleExpand(prospect)"
                                    class="shrink-0 w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-700 transition">
                                <svg class="size-4 transition-transform"
                                     :class="expandedProspectId === prospect.id ? 'rotate-90' : ''"
                                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                            <input type="text" v-model="prospect.name" placeholder="Name"
                                   class="h-10 px-2 rounded-lg w-full max-w-xs border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                            <input type="text" v-model="prospect.website" placeholder="Website"
                                   class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                            <input type="email" v-model="prospect.email" placeholder="Email"
                                   class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                            <div class="w-16 shrink-0 text-[11px] leading-3 text-center">
                                <span v-if="savingProspectById[prospect.id]" class="text-gray-400">Saving…</span>
                                <span v-else-if="savedProspectById[prospect.id]" class="text-brand-accent-dark font-medium">Saved</span>
                            </div>
                            <div class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full hover:bg-red-50 transition">
                                <DeleteModal @deleted="deleteProspect(prospect)"
                                             label="Are you sure you want to delete this prospect? Its logged actions will be deleted too."/>
                            </div>
                        </div>
                        <div v-if="expandedProspectId === prospect.id" class="px-4 pb-4 pl-12">
                            <ProspectActions :prospect-id="prospect.id"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
