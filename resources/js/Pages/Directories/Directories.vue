<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Link} from '@inertiajs/vue3';
import {ref, watch} from "vue";
import SaveButton from "@/Components/SaveButton.vue";
import Modal from "@/Components/Modal.vue";
import DeleteConfirmPopover from "@/Pages/Directories/Partials/DeleteConfirmPopover.vue";
import debounce from "lodash/debounce";

const directories = ref([]);
const newDirectoryName = ref('');
const newDirectoryPrompt = ref('');
const errorMsg = ref('');

const activeDirectory = ref(null);
const savingActive = ref(false);
const savedActive = ref(false);
let savedActiveTimer = null;
let activeDirectorySnapshot = null;

const refreshDirectories = () => {
    axios.get(route('directories.index'))
        .then(response => {
            directories.value = response.data;
        });
}

const addDirectory = async () => {
    if (!validateInput()) return
    await axios.post(route('directories.store'), {name: newDirectoryName.value, prompt: newDirectoryPrompt.value})
        .then(() => {
            newDirectoryName.value = ''
            newDirectoryPrompt.value = ''
            refreshDirectories();
        })
        .catch((error) => {
            errorMsg.value = error.response?.data?.message ?? 'Something went wrong';
        })
}

const validateInput = () => {
    errorMsg.value = '';
    if (newDirectoryName.value === '') {
        errorMsg.value = 'Directory name is required';
        return false;
    }
    return true;
}

const deleteDirectory = (directory) => {
    axios.delete(route('directories.delete', directory.id)).then(() => {
        if (activeDirectory.value?.id === directory.id) activeDirectory.value = null;
        refreshDirectories()
    })
}

const cleanDirectory = (d) => JSON.stringify({name: d.name, prompt: d.prompt});

const openDirectory = (directory) => {
    activeDirectory.value = directory;
    activeDirectorySnapshot = cleanDirectory(directory);
}

const closeDirectory = () => {
    activeDirectory.value = null;
}

watch(() => activeDirectory.value && [activeDirectory.value.name, activeDirectory.value.prompt], () => {
    if (!activeDirectory.value) return;
    if (cleanDirectory(activeDirectory.value) === activeDirectorySnapshot) return;
    debouncedUpdateActiveDirectory();
});

const updateActiveDirectory = () => {
    const directory = activeDirectory.value;
    if (!directory) return;
    savingActive.value = true;
    axios.patch(route('directories.update', directory.id), {name: directory.name, prompt: directory.prompt}).then(() => {
        activeDirectorySnapshot = cleanDirectory(directory);
        savingActive.value = false;
        savedActive.value = true;
        if (savedActiveTimer) clearTimeout(savedActiveTimer);
        savedActiveTimer = setTimeout(() => savedActive.value = false, 1500);
    }).catch(() => {
        savingActive.value = false;
    });
}
const debouncedUpdateActiveDirectory = debounce(updateActiveDirectory, 600);

refreshDirectories();
</script>

<template>
    <AppLayout title="Prospection">

        <template #header>
            <h2 class="font-semibold text-xl leading-tight text-slate-900">Prospection</h2>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="surface-card mb-6">
                <div class="p-4 flex flex-col gap-2">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" v-model="newDirectoryName" placeholder="Directory name"
                               class="w-full sm:flex-1 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                               @keydown.enter="addDirectory">
                        <input type="text" v-model="newDirectoryPrompt"
                               placeholder="AI prompt (e.g. SaaS companies in Paris)"
                               class="w-full sm:flex-1 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                               @keydown.enter="addDirectory">
                        <SaveButton @click="addDirectory"/>
                    </div>
                    <div v-if="errorMsg" class="text-sm text-red-600">{{ errorMsg }}</div>
                </div>
            </div>

        </div>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="surface-card overflow-hidden">
                <div v-if="!directories.length" class="p-8 text-center text-sm text-gray-400">
                    No directories yet. Add one above to start prospecting.
                </div>
                <div v-else class="divide-y divide-gray-100">
                    <div v-for="directory in directories" :key="directory.id"
                         class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-brand-surface transition"
                         @click="openDirectory(directory)">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 truncate">
                                {{ directory.name || 'Untitled directory' }}
                            </div>
                            <div class="text-xs text-gray-500 truncate">
                                {{ directory.prompt || 'No AI prompt set' }}
                            </div>
                        </div>
                        <span class="shrink-0 inline-flex items-center justify-center rounded-full bg-brand-accent/10 text-brand-accent-dark text-xs font-semibold tabular-nums min-w-6 h-6 px-2">
                            {{ directory.prospects_count ?? 0 }}
                        </span>
                        <Link :href="route('directories.view', directory.id)" @click.stop
                              class="shrink-0 text-xs font-medium text-brand-navy hover:underline">
                            Open →
                        </Link>
                        <div @click.stop>
                            <DeleteConfirmPopover @deleted="deleteDirectory(directory)"
                                                   label="Delete this directory? All its prospects and logged actions will be deleted too."/>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="!!activeDirectory" @close="closeDirectory">
            <div v-if="activeDirectory" class="p-6 flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">{{ activeDirectory.name || 'Directory' }}</h3>
                    <button type="button" @click="closeDirectory" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-xs font-medium text-gray-500">Name</label>
                    <input type="text" v-model="activeDirectory.name"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                    <label class="text-xs font-medium text-gray-500 mt-1">AI prompt / criteria</label>
                    <textarea v-model="activeDirectory.prompt" rows="3"
                              class="px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"/>
                    <div class="text-[11px] leading-3 text-gray-500 h-3">
                        <span v-if="savingActive" class="text-gray-400">Saving…</span>
                        <span v-else-if="savedActive" class="text-brand-accent-dark font-medium">Saved</span>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <Link :href="route('directories.view', activeDirectory.id)"
                          class="inline-flex items-center px-4 py-2 bg-brand-navy border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light transition">
                        Open prospects →
                    </Link>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
