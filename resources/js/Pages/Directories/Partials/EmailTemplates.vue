<script setup>
import {ref} from "vue";
import DeleteConfirmPopover from "@/Pages/Directories/Partials/DeleteConfirmPopover.vue";
import {useStore} from "@/Composables/store.js";
import {router} from "@inertiajs/vue3";

const props = defineProps({directoryId: Number});

const LANGUAGES = {fr: 'French', en: 'English', de: 'German', da: 'Danish', sv: 'Swedish', fi: 'Finnish', no: 'Norwegian'};

const templates = ref([]);
const loading = ref(true);
const generatePrompt = ref('');
const generateLanguage = ref('en');
const generating = ref(false);
const errorMsg = ref('');

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
    axios.post(route('email-templates.generate', props.directoryId), {
        prompt: generatePrompt.value,
        language: generateLanguage.value,
    })
        .then((response) => {
            router.visit(route('email-templates.view', [props.directoryId, response.data.id]));
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
        refreshTemplates();
    });
}

const openTemplate = (template) => {
    router.visit(route('email-templates.view', [props.directoryId, template.id]));
}

refreshTemplates();
</script>

<template>
    <div class="p-4 flex flex-col gap-2 border-b border-gray-100">
        <div class="flex flex-col sm:flex-row gap-2">
            <input type="text" v-model="generatePrompt"
                   placeholder="What should this email do? (e.g. cold intro offering a free trial)"
                   class="h-10 px-2 rounded-lg w-full sm:flex-1 border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                   @keydown.enter="generateTemplate">
            <select v-model="generateLanguage"
                    class="h-10 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                <option v-for="(label, code) in LANGUAGES" :key="code" :value="code">{{ label }}</option>
            </select>
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
            <span class="shrink-0 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1">
                {{ LANGUAGES[template.language] || template.language }}
            </span>
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
</template>
