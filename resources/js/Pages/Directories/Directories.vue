<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Link} from '@inertiajs/vue3';
import {ref, watch} from "vue";
import SaveButton from "@/Components/SaveButton.vue";
import DeleteModal from "@/Pages/Tasks/Partials/DeleteModal.vue";
import debounce from "lodash/debounce";

const reactiveDirectories = ref([]);
const newDirectoryName = ref('');
const newDirectoryPrompt = ref('');
const errorMsg = ref('');
let storedDirectories = null;
let watchActive = false;
const savingById = ref({});
const savedById = ref({});
const savedTimersById = ref({});

const refreshDirectories = () => {
    axios.get(route('directories.index'))
        .then(response => {
            reactiveDirectories.value = response.data;
            storedDirectories = JSON.parse(JSON.stringify(reactiveDirectories.value));
            watchActive = true;
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
        refreshDirectories()
    })
}

const cleanDirectory = (directory) => {
    // Avoid comparing transient UI fields.
    const {id, name, prompt} = directory;
    return {id, name, prompt};
}

const updateDirectory = (directory) => {
    savingById.value[directory.id] = true;
    axios.patch(route('directories.update', directory.id), {name: directory.name, prompt: directory.prompt}).then(() => {
        storedDirectories = JSON.parse(JSON.stringify(reactiveDirectories.value));
        savingById.value[directory.id] = false;

        savedById.value[directory.id] = true;
        if (savedTimersById.value[directory.id]) {
            clearTimeout(savedTimersById.value[directory.id]);
        }
        savedTimersById.value[directory.id] = setTimeout(() => {
            savedById.value[directory.id] = false;
            savedTimersById.value[directory.id] = null;
        }, 1500);
    }).catch(() => {
        savingById.value[directory.id] = false;
    });
}

const debouncedUpdateDirectory = debounce(updateDirectory, 500);

watch(reactiveDirectories, () => {
    if (!watchActive) return;
    for (const directory of (reactiveDirectories.value ?? [])) {
        const stored = (storedDirectories ?? []).find(d => d.id === directory.id);
        if (!stored) continue;
        if (JSON.stringify(cleanDirectory(stored)) !== JSON.stringify(cleanDirectory(directory))) {
            debouncedUpdateDirectory(directory);
        }
    }
}, {deep: true});

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
                <div v-if="!reactiveDirectories.length" class="p-8 text-center text-sm text-gray-400">
                    No directories yet. Add one above to start prospecting.
                </div>
                <div v-else class="divide-y divide-gray-100">
                    <div v-for="directory in reactiveDirectories" :key="directory.id"
                         class="flex items-center gap-3 px-4 py-3">
                        <Link :href="route('directories.view', directory.id)"
                              class="shrink-0 text-sm font-medium text-brand-navy hover:underline">
                            {{ directory.name || 'Untitled directory' }}
                        </Link>
                        <input type="text" v-model="directory.name" placeholder="Name"
                               class="h-10 px-2 rounded-lg w-full max-w-xs border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                        <input type="text" v-model="directory.prompt" placeholder="AI prompt"
                               class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                        <span class="shrink-0 inline-flex items-center justify-center rounded-full bg-brand-accent/10 text-brand-accent-dark text-xs font-semibold tabular-nums min-w-6 h-6 px-2">
                            {{ directory.prospects_count ?? 0 }}
                        </span>
                        <div class="w-16 shrink-0 text-[11px] leading-3 text-center">
                            <span v-if="savingById[directory.id]" class="text-gray-400">Saving…</span>
                            <span v-else-if="savedById[directory.id]" class="text-brand-accent-dark font-medium">Saved</span>
                        </div>
                        <div class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full hover:bg-red-50 transition">
                            <DeleteModal @deleted="deleteDirectory(directory)"
                                         label="Are you sure you want to delete this directory? All its prospects and logged actions will be deleted too."/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
