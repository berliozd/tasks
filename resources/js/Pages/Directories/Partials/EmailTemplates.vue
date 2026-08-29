<script setup>
import {ref, watch} from "vue";
import Modal from "@/Components/Modal.vue";
import DeleteConfirmPopover from "@/Pages/Directories/Partials/DeleteConfirmPopover.vue";
import debounce from "lodash/debounce";
import {useStore} from "@/Composables/store.js";

const props = defineProps({directoryId: Number});

const templates = ref([]);
const loading = ref(true);
const generatePrompt = ref('');
const generating = ref(false);
const errorMsg = ref('');

const activeTemplate = ref(null);
const savingActive = ref(false);
const savedActive = ref(false);
let savedActiveTimer = null;
let activeTemplateSnapshot = null;

const refreshTemplates = () => {
    loading.value = true;
    axios.get(route('email-templates.index', props.directoryId)).then(response => {
        templates.value = response.data;
    }).finally(() => loading.value = false);
}

const generateTemplate = () => {
    if (!generatePrompt.value) return;
    generating.value = true;
    errorMsg.value = '';
    axios.post(route('email-templates.generate', props.directoryId), {prompt: generatePrompt.value})
        .then((response) => {
            generatePrompt.value = '';
            refreshTemplates();
            openTemplate(response.data);
        })
        .catch((error) => {
            errorMsg.value = error.response?.data?.message ?? 'Could not generate a template';
        })
        .finally(() => generating.value = false);
}

const templateText = (template) => {
    return template.subject ? `Subject: ${template.subject}\n\n${template.body}` : (template.body ?? '');
}

const copyTemplate = async (template) => {
    const text = templateText(template);
    try {
        await navigator.clipboard.writeText(text);
        useStore().setSaved('Copied to clipboard!');
    } catch (e) {
        errorMsg.value = 'Could not copy to clipboard';
    }
}

const deleteTemplate = (template) => {
    axios.delete(route('email-templates.destroy', template.id)).then(() => {
        if (activeTemplate.value?.id === template.id) activeTemplate.value = null;
        refreshTemplates();
    });
}

const cleanTemplate = (t) => JSON.stringify({name: t.name, subject: t.subject, body: t.body});

const openTemplate = (template) => {
    activeTemplate.value = template;
    activeTemplateSnapshot = cleanTemplate(template);
}

const closeTemplate = () => {
    activeTemplate.value = null;
}

watch(() => activeTemplate.value && [activeTemplate.value.name, activeTemplate.value.subject, activeTemplate.value.body], () => {
    if (!activeTemplate.value) return;
    if (cleanTemplate(activeTemplate.value) === activeTemplateSnapshot) return;
    debouncedUpdateActiveTemplate();
});

const updateActiveTemplate = () => {
    const template = activeTemplate.value;
    if (!template) return;
    savingActive.value = true;
    axios.patch(route('email-templates.update', template.id), {
        name: template.name,
        subject: template.subject,
        body: template.body,
    }).then(() => {
        activeTemplateSnapshot = cleanTemplate(template);
        savingActive.value = false;
        savedActive.value = true;
        if (savedActiveTimer) clearTimeout(savedActiveTimer);
        savedActiveTimer = setTimeout(() => savedActive.value = false, 1500);
        refreshTemplates();
    }).catch(() => {
        savingActive.value = false;
    });
}
const debouncedUpdateActiveTemplate = debounce(updateActiveTemplate, 600);

refreshTemplates();
</script>

<template>
    <div class="surface-card">
        <div class="p-4 flex flex-col gap-2 border-b border-gray-100">
            <div class="text-sm font-medium text-gray-900">Email templates</div>
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" v-model="generatePrompt"
                       placeholder="What should this email do? (e.g. cold intro offering a free trial)"
                       class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                       @keydown.enter="generateTemplate">
                <button type="button" @click="generateTemplate" :disabled="generating || !generatePrompt"
                        class="shrink-0 inline-flex items-center px-4 py-2 bg-brand-navy border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light disabled:opacity-50 transition">
                    {{ generating ? 'Generating…' : 'Generate with AI' }}
                </button>
            </div>
            <div v-if="errorMsg" class="text-sm text-red-600">{{ errorMsg }}</div>
        </div>

        <div v-if="loading" class="p-8 text-center text-sm text-gray-400">Loading…</div>
        <div v-else-if="!templates.length" class="p-8 text-center text-sm text-gray-400">
            No email templates yet. Describe one above and generate it with AI.
        </div>
        <div v-else class="divide-y divide-gray-100">
            <div v-for="template in templates" :key="template.id"
                 class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-brand-surface transition"
                 @click="openTemplate(template)">
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium text-gray-900 truncate">{{ template.name || 'Untitled template' }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ template.subject || 'No subject' }}</div>
                </div>
                <button type="button" @click.stop="copyTemplate(template)"
                        class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
                        title="Copy subject + body to clipboard">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="2" stroke="currentColor">
                        <rect x="8" y="8" width="12" height="12" rx="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>
                    </svg>
                </button>
                <div @click.stop>
                    <DeleteConfirmPopover @deleted="deleteTemplate(template)" label="Delete this email template?"/>
                </div>
            </div>
        </div>
    </div>

    <Modal :show="!!activeTemplate" @close="closeTemplate">
        <div v-if="activeTemplate" class="p-6 flex flex-col gap-4 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">{{ activeTemplate.name || 'Email template' }}</h3>
                <button type="button" @click="closeTemplate" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-xs font-medium text-gray-500">Name</label>
                <input type="text" v-model="activeTemplate.name"
                       class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                <label class="text-xs font-medium text-gray-500 mt-1">Subject</label>
                <input type="text" v-model="activeTemplate.subject"
                       class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                <label class="text-xs font-medium text-gray-500 mt-1">Body</label>
                <textarea v-model="activeTemplate.body" rows="10"
                          class="px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition font-mono text-sm"/>
                <div class="text-[11px] leading-3 text-gray-500 h-3">
                    <span v-if="savingActive" class="text-gray-400">Saving…</span>
                    <span v-else-if="savedActive" class="text-brand-accent-dark font-medium">Saved</span>
                </div>
            </div>
        </div>
    </Modal>
</template>
