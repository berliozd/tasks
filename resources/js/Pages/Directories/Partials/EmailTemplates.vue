<script setup>
import {reactive, ref} from "vue";
import DeleteConfirmPopover from "@/Pages/Directories/Partials/DeleteConfirmPopover.vue";
import {useStore} from "@/Composables/store.js";
import debounce from "lodash/debounce";

const props = defineProps({directoryId: Number});

const LANGUAGES = {fr: 'French', en: 'English', de: 'German', da: 'Danish', sv: 'Swedish', fi: 'Finnish', no: 'Norwegian'};

const templates = ref([]);
const loading = ref(true);
const generatePrompt = ref('');
const generateLanguage = ref('en');
const generating = ref(false);
const errorMsg = ref('');
const expandedIds = ref(new Set());
const savingIds = ref(new Set());
const savedIds = ref(new Set());
const savedTimers = {};
const debouncedSavers = {};

const refreshTemplates = () => {
    loading.value = true;
    axios.get(route('email-templates.index', props.directoryId)).then(response => {
        templates.value = response.data;
    }).finally(() => loading.value = false);
}

const expand = (id) => {
    expandedIds.value = new Set(expandedIds.value).add(id);
}

const toggleExpand = (template) => {
    const next = new Set(expandedIds.value);
    if (next.has(template.id)) next.delete(template.id);
    else next.add(template.id);
    expandedIds.value = next;
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
            // Add it to the list and open it right below, instead of
            // navigating away to a separate page.
            templates.value = [response.data, ...templates.value];
            expand(response.data.id);
            generatePrompt.value = '';
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

const markSaved = (id) => {
    savedIds.value = new Set(savedIds.value).add(id);
    if (savedTimers[id]) clearTimeout(savedTimers[id]);
    savedTimers[id] = setTimeout(() => {
        const next = new Set(savedIds.value);
        next.delete(id);
        savedIds.value = next;
    }, 1500);
}

const saveTemplate = (template) => {
    savingIds.value = new Set(savingIds.value).add(template.id);
    axios.patch(route('email-templates.update', template.id), {
        name: template.name,
        subject: template.subject,
        body: template.body,
    }).then(() => {
        markSaved(template.id);
    }).finally(() => {
        const next = new Set(savingIds.value);
        next.delete(template.id);
        savingIds.value = next;
    });
}

const debouncedSaveTemplate = (template) => {
    if (!debouncedSavers[template.id]) {
        debouncedSavers[template.id] = debounce(saveTemplate, 600);
    }
    debouncedSavers[template.id](template);
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
        <div v-for="template in templates" :key="template.id">
            <div class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-brand-surface transition"
                 @click="toggleExpand(template)">
                <svg class="shrink-0 size-3.5 text-gray-400 transition-transform"
                     :class="expandedIds.has(template.id) ? 'rotate-90' : ''"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                     stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
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
            <div v-if="expandedIds.has(template.id)" class="px-4 pb-4 flex flex-col gap-2" @click.stop>
                <label class="text-xs font-medium text-gray-500">Name</label>
                <input type="text" v-model="template.name" @input="debouncedSaveTemplate(template)"
                       class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                <label class="text-xs font-medium text-gray-500 mt-1">Subject</label>
                <input type="text" v-model="template.subject" @input="debouncedSaveTemplate(template)"
                       class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                <label class="text-xs font-medium text-gray-500 mt-1">Body</label>
                <textarea v-model="template.body" @input="debouncedSaveTemplate(template)" rows="10"
                          class="px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition font-mono text-sm"/>
                <div class="text-[11px] leading-3 text-gray-500 h-3">
                    <span v-if="savingIds.has(template.id)" class="text-gray-400">Saving…</span>
                    <span v-else-if="savedIds.has(template.id)" class="text-brand-accent-dark font-medium">Saved</span>
                </div>
            </div>
        </div>
    </div>
</template>
