<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {router} from '@inertiajs/vue3';
import {ref} from "vue";
import SaveButton from "@/Components/SaveButton.vue";
import DeleteConfirmPopover from "@/Pages/Directories/Partials/DeleteConfirmPopover.vue";

const directories = ref([]);
const newDirectoryName = ref('');
const newDirectoryPrompt = ref('');
const errorMsg = ref('');

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
        refreshDirectories()
    })
}

const openDirectory = (directory) => {
    router.visit(route('directories.view', directory.id));
}

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
                    <div class="text-sm font-medium text-gray-900">New directory</div>
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
                <div class="p-4 text-sm font-medium text-gray-900 border-b border-gray-100">Directories</div>
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
                        <div @click.stop>
                            <DeleteConfirmPopover @deleted="deleteDirectory(directory)"
                                                   label="Delete this directory? All its prospects and logged actions will be deleted too."/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
