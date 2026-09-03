<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Head, router} from '@inertiajs/vue3';
import {computed, ref} from "vue";
import {format} from 'date-fns';
import DeleteConfirmPopover from "@/Pages/Directories/Partials/DeleteConfirmPopover.vue";

const documents = ref([]);
const allFlags = ref([]);
const selectedFlagIds = ref([]);
const loading = ref(true);
const creating = ref(false);
const dragging = ref(false);
const dropError = ref('');

const refreshDocuments = () => {
    loading.value = true;
    axios.get(route('documents.index'), {params: {flag_ids: selectedFlagIds.value}})
        .then(response => {
            documents.value = response.data;
        })
        .finally(() => loading.value = false);
}

const refreshFlags = () => {
    axios.get(route('document-flags.index')).then(response => {
        allFlags.value = response.data;
    });
}

const toggleFlagFilter = (flagId) => {
    selectedFlagIds.value = selectedFlagIds.value.includes(flagId)
        ? selectedFlagIds.value.filter(id => id !== flagId)
        : [...selectedFlagIds.value, flagId];
    refreshDocuments();
}

const openDocument = (document) => {
    router.visit(route('documents.view', document.id));
}

const deleteDocument = (document) => {
    axios.delete(route('documents.destroy', document.id)).then(() => refreshDocuments());
}

const createBlank = () => {
    creating.value = true;
    axios.post(route('documents.store'), {title: 'Untitled', content: ''})
        .then(response => {
            router.visit(route('documents.view', response.data.id));
        })
        .finally(() => creating.value = false);
}

const onDrop = (event) => {
    dragging.value = false;
    dropError.value = '';
    const file = event.dataTransfer?.files?.[0];
    if (!file) return;
    if (!file.name.toLowerCase().endsWith('.md')) {
        dropError.value = 'Only .md files are supported.';
        return;
    }

    const reader = new FileReader();
    reader.onload = () => {
        creating.value = true;
        const title = file.name.replace(/\.md$/i, '');
        axios.post(route('documents.store'), {title, content: String(reader.result ?? '')})
            .then(response => {
                router.visit(route('documents.view', response.data.id));
            })
            .catch((error) => {
                dropError.value = error.response?.data?.message ?? 'Could not import file';
            })
            .finally(() => creating.value = false);
    };
    reader.readAsText(file);
}

const formatDate = (date) => date ? format(new Date(date), 'MMM d, yyyy HH:mm') : '';

const flagName = computed(() => (id) => allFlags.value.find(f => f.id === id)?.name ?? '');

refreshDocuments();
refreshFlags();
</script>

<template>
    <Head title="Documents"/>
    <AppLayout title="Documents">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight text-slate-900">Documents</h2>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-6">

            <div class="surface-card p-4 flex flex-col gap-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="text-sm font-medium text-gray-900">Add a document</div>
                    <button type="button" @click="createBlank" :disabled="creating"
                            class="inline-flex items-center px-4 py-2 bg-brand-navy border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light disabled:opacity-50 transition">
                        {{ creating ? 'Creating…' : '+ New document' }}
                    </button>
                </div>
                <div class="rounded-xl border-2 border-dashed p-6 text-center text-sm transition"
                     :class="dragging ? 'border-brand-accent bg-brand-accent/5 text-brand-accent-dark' : 'border-gray-200 text-gray-400'"
                     @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="onDrop">
                    Drag and drop a <code>.md</code> file here to import it as a new document
                </div>
                <div v-if="dropError" class="text-sm text-red-600">{{ dropError }}</div>
            </div>

            <div v-if="allFlags.length" class="surface-card p-4">
                <div class="flex flex-wrap items-center gap-1">
                    <span class="text-xs text-gray-500 mr-1">Flags:</span>
                    <button v-for="flag in allFlags" :key="flag.id" type="button" @click="toggleFlagFilter(flag.id)"
                            class="rounded-full text-xs font-semibold px-2 py-1 transition"
                            :class="selectedFlagIds.includes(flag.id)
                                ? 'bg-brand-accent/10 text-brand-accent-dark ring-1 ring-current'
                                : 'bg-gray-100 text-gray-400 hover:bg-gray-200'">
                        {{ flag.name }}
                    </button>
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div v-if="loading" class="p-8 text-center text-sm text-gray-400">Loading…</div>
                <div v-else-if="!documents.length" class="p-8 text-center text-sm text-gray-400">
                    No documents{{ selectedFlagIds.length ? ' match this filter' : ' yet' }}. Add one above to start
                    your knowledge base.
                </div>
                <div v-else class="divide-y divide-gray-100">
                    <div v-for="document in documents" :key="document.id"
                         class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-brand-surface transition"
                         @click="openDocument(document)">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 truncate">
                                {{ document.title || 'Untitled document' }}
                            </div>
                            <div v-if="(document.flags ?? []).length" class="flex flex-wrap gap-1 mt-1">
                                <span v-for="flag in document.flags" :key="flag.id"
                                      class="rounded-full text-[11px] font-medium px-2 py-0.5 bg-brand-accent/10 text-brand-accent-dark">
                                    {{ flag.name }}
                                </span>
                            </div>
                        </div>
                        <span class="shrink-0 text-xs text-gray-400">{{ formatDate(document.updated_at) }}</span>
                        <div @click.stop>
                            <DeleteConfirmPopover @deleted="deleteDocument(document)" label="Delete this document?"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
