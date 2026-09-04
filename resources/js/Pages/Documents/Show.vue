<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Head, Link, router} from '@inertiajs/vue3';
import {nextTick, ref, watch} from "vue";
import {marked} from "marked";
import DOMPurify from "dompurify";
import SavedLabel from "@/Components/SavedLabel.vue";
import DeleteModal from "@/Pages/Tasks/Partials/DeleteModal.vue";
import debounce from "lodash/debounce";
import {useStore} from "@/Composables/store.js";

const props = defineProps({documentId: Number});

// Named "doc", not "document" — a ref called `document` would shadow the
// global `window.document` for the rest of this script.
const doc = ref(null);
const loading = ref(true);
const savingActive = ref(false);
const savedActive = ref(false);
let savedActiveTimer = null;
let docSnapshot = null;

const mobileView = ref('edit'); // 'edit' | 'preview'
const newFlagName = ref('');
const addingFlag = ref(false);
const rescanning = ref(false);
const rescanError = ref('');
const contentTextarea = ref(null);
const imageUploadError = ref('');

const cleanDoc = (d) => JSON.stringify({title: d.title, content: d.content});

const refreshDocument = () => {
    loading.value = true;
    axios.get(route('documents.show', props.documentId)).then(response => {
        doc.value = response.data;
        docSnapshot = cleanDoc(doc.value);
    }).finally(() => loading.value = false);
}

watch(() => doc.value && [doc.value.title, doc.value.content], () => {
    if (!doc.value) return;
    if (cleanDoc(doc.value) === docSnapshot) return;
    debouncedUpdateDocument();
});

const updateDocument = () => {
    if (!doc.value) return;
    savingActive.value = true;
    axios.patch(route('documents.update', doc.value.id), {
        title: doc.value.title,
        content: doc.value.content,
    }).then(() => {
        docSnapshot = cleanDoc(doc.value);
        savingActive.value = false;
        savedActive.value = true;
        if (savedActiveTimer) clearTimeout(savedActiveTimer);
        savedActiveTimer = setTimeout(() => savedActive.value = false, 1500);
    }).catch(() => {
        savingActive.value = false;
    });
}
const debouncedUpdateDocument = debounce(updateDocument, 600);

const renderedHtml = (content) => {
    // DOMPurify needs a real DOM (window), which doesn't exist during SSR.
    // Render nothing server-side rather than risk crashing that render —
    // the client immediately fills this in correctly after hydration.
    if (typeof window === 'undefined') return '';
    return DOMPurify.sanitize(marked.parse(content || '', {breaks: true}));
}

// Inserts text at the textarea's cursor (replacing any selection) rather
// than appending, so pasting/dropping an image mid-document lands where
// the cursor was.
const insertAtCursor = (text) => {
    const textarea = contentTextarea.value;
    const content = doc.value.content || '';
    const start = textarea?.selectionStart ?? content.length;
    const end = textarea?.selectionEnd ?? content.length;
    doc.value.content = content.slice(0, start) + text + content.slice(end);
    nextTick(() => {
        if (!textarea) return;
        const pos = start + text.length;
        textarea.focus();
        textarea.setSelectionRange(pos, pos);
    });
}

// Inserts a placeholder immediately so the user sees something happen,
// then swaps it for the real Markdown image (or removes it) once the
// upload settles.
const uploadImage = (file) => {
    if (!file || !file.type.startsWith('image/')) return;
    imageUploadError.value = '';
    const placeholder = `![Uploading ${file.name}…]()`;
    insertAtCursor(placeholder);

    const formData = new FormData();
    formData.append('image', file);
    axios.post(route('documents.images.store', props.documentId), formData).then(response => {
        doc.value.content = doc.value.content.replace(placeholder, `![](${response.data.url})`);
    }).catch((error) => {
        doc.value.content = doc.value.content.replace(placeholder, '');
        imageUploadError.value = error.response?.data?.message ?? 'Could not upload image';
    });
}

const onContentPaste = (event) => {
    const item = Array.from(event.clipboardData?.items ?? []).find(i => i.type.startsWith('image/'));
    if (!item) return;
    event.preventDefault();
    uploadImage(item.getAsFile());
}

const onContentDrop = (event) => {
    const file = Array.from(event.dataTransfer?.files ?? []).find(f => f.type.startsWith('image/'));
    if (!file) return;
    event.preventDefault();
    uploadImage(file);
}

const saveFlags = (names) => {
    axios.patch(route('documents.flags.update', props.documentId), {flags: names}).then(response => {
        doc.value.flags = response.data.flags;
    });
}

const addFlag = () => {
    const name = newFlagName.value.trim();
    if (!name) return;
    const names = [...(doc.value.flags ?? []).map(f => f.name), name];
    addingFlag.value = true;
    axios.patch(route('documents.flags.update', props.documentId), {flags: names}).then(response => {
        doc.value.flags = response.data.flags;
        newFlagName.value = '';
    }).finally(() => addingFlag.value = false);
}

const removeFlag = (flag) => {
    const names = (doc.value.flags ?? []).filter(f => f.id !== flag.id).map(f => f.name);
    saveFlags(names);
}

const rescanFlags = () => {
    rescanning.value = true;
    rescanError.value = '';
    axios.post(route('documents.flags.rescan', props.documentId)).then(response => {
        doc.value.flags = response.data.flags;
        useStore().setSaved('Flags rescanned');
    }).catch((error) => {
        rescanError.value = error.response?.data?.message ?? 'Could not rescan flags';
    }).finally(() => rescanning.value = false);
}

const deleteDocument = () => {
    axios.delete(route('documents.destroy', props.documentId)).then(() => {
        useStore().setSaved('Document deleted');
        router.visit(route('documents'));
    });
}

refreshDocument();
</script>

<template>
    <Head :title="doc?.title || 'Document'"/>
    <AppLayout :title="doc?.title || 'Document'">
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('documents')" class="text-sm text-gray-500 hover:text-gray-700">
                    Documents
                </Link>
                <span class="text-gray-300">/</span>
                <h2 class="font-semibold text-xl leading-tight text-slate-900">
                    {{ doc?.title || (loading ? '...' : 'Document') }}
                </h2>
            </div>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-6">

            <div class="min-h-6">
                <SavedLabel/>
            </div>

            <div v-if="loading" class="p-8 text-center text-sm text-gray-400">Loading…</div>
            <template v-else-if="doc">
                <div class="surface-card p-4 flex flex-col gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <input type="text" v-model="doc.title" placeholder="Untitled"
                               class="text-lg font-medium h-11 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                        <DeleteModal @deleted="deleteDocument"
                                     label="Are you sure you want to delete this document?"/>
                    </div>
                    <div class="flex flex-wrap items-center gap-1">
                        <span v-for="flag in (doc.flags ?? [])" :key="flag.id"
                              class="inline-flex items-center gap-1 rounded-full text-xs font-medium px-2 py-1 bg-brand-accent/10 text-brand-accent-dark">
                            {{ flag.name }}
                            <button type="button" @click="removeFlag(flag)" class="hover:text-red-600 transition">×</button>
                        </span>
                        <input type="text" v-model="newFlagName" placeholder="Add flag"
                               @keydown.enter="addFlag" :disabled="addingFlag"
                               class="h-7 w-28 px-2 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-xs">
                        <button type="button" @click="rescanFlags" :disabled="rescanning"
                                title="Re-scan the content with AI and add any newly-relevant flags"
                                class="inline-flex items-center px-2 py-1 rounded-lg border border-gray-300 text-[11px] font-medium text-gray-600 uppercase tracking-widest hover:bg-gray-100 disabled:opacity-50 transition">
                            {{ rescanning ? 'Rescanning…' : 'Rescan flags' }}
                        </button>
                    </div>
                    <div v-if="rescanError" class="text-xs text-red-600">{{ rescanError }}</div>
                    <div class="text-[11px] leading-3 text-gray-500 h-3">
                        <span v-if="savingActive" class="text-gray-400">Saving…</span>
                        <span v-else-if="savedActive" class="text-brand-accent-dark font-medium">Saved</span>
                    </div>
                </div>

                <div class="lg:hidden flex rounded-lg border border-gray-200 overflow-hidden w-fit">
                    <button type="button" @click="mobileView = 'edit'"
                            class="px-3 py-1 text-sm font-medium transition"
                            :class="mobileView === 'edit' ? 'bg-brand-navy text-white' : 'bg-white text-gray-700'">
                        Edit
                    </button>
                    <button type="button" @click="mobileView = 'preview'"
                            class="px-3 py-1 text-sm font-medium border-l border-gray-200 transition"
                            :class="mobileView === 'preview' ? 'bg-brand-navy text-white' : 'bg-white text-gray-700'">
                        Preview
                    </button>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="surface-card p-2 flex flex-col gap-1" :class="mobileView === 'preview' ? 'hidden lg:block' : ''">
                        <textarea ref="contentTextarea" v-model="doc.content"
                                  placeholder="Write Markdown here… (paste or drop an image to upload it)" rows="24"
                                  @paste="onContentPaste" @dragover.prevent @drop.prevent="onContentDrop"
                                  class="w-full h-full min-h-[60vh] font-mono text-sm rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"/>
                        <div v-if="imageUploadError" class="text-xs text-red-600">{{ imageUploadError }}</div>
                    </div>
                    <div class="surface-card p-4 overflow-auto" :class="mobileView === 'edit' ? 'hidden lg:block' : ''">
                        <div class="prose prose-sm max-w-none" v-html="renderedHtml(doc.content)"/>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
