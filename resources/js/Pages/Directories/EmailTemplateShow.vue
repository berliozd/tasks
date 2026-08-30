<script>
import ProspectionLayout from '@/Layouts/ProspectionLayout.vue';

export default {
    layout: ProspectionLayout,
};
</script>

<script setup>
import {ref, watch, watchEffect} from "vue";
import SavedLabel from "@/Components/SavedLabel.vue";
import DeleteModal from "@/Pages/Tasks/Partials/DeleteModal.vue";
import debounce from "lodash/debounce";
import {Head, router} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";

const props = defineProps({directoryId: Number, templateId: Number});

const LANGUAGES = {fr: 'French', en: 'English', de: 'German', da: 'Danish', sv: 'Swedish', fi: 'Finnish', no: 'Norwegian'};

const directory = ref({name: '', product: null});
const template = ref(null);
const loading = ref(true);

watchEffect(() => {
    const crumbs = [{label: 'Prospection', href: route('products')}];
    if (directory.value.product) {
        crumbs.push({label: directory.value.product.name, href: route('products.view', directory.value.product.id)});
    }
    crumbs.push({label: directory.value.name || '...', href: route('directories.view', props.directoryId)});
    crumbs.push({label: 'Email templates', href: route('directories.email-templates', props.directoryId)});
    crumbs.push({label: template.value?.name || (loading.value ? '...' : 'Email template'), href: null});
    useStore().setProspectionActive({
        productId: directory.value.product?.id,
        directoryId: props.directoryId,
        breadcrumb: crumbs,
    });
});
const savingActive = ref(false);
const savedActive = ref(false);
let savedActiveTimer = null;
let templateSnapshot = null;

const cleanTemplate = (t) => JSON.stringify({name: t.name, subject: t.subject, body: t.body});

const refreshDirectory = () => {
    axios.get(route('directories.show', props.directoryId)).then(response => {
        directory.value = response.data;
    });
}

const refreshTemplate = () => {
    loading.value = true;
    axios.get(route('email-templates.show', props.templateId)).then(response => {
        template.value = response.data;
        templateSnapshot = cleanTemplate(template.value);
    }).finally(() => loading.value = false);
}

watch(() => template.value && [template.value.name, template.value.subject, template.value.body], () => {
    if (!template.value) return;
    if (cleanTemplate(template.value) === templateSnapshot) return;
    debouncedUpdateTemplate();
});

const updateTemplate = () => {
    if (!template.value) return;
    savingActive.value = true;
    axios.patch(route('email-templates.update', template.value.id), {
        name: template.value.name,
        subject: template.value.subject,
        body: template.value.body,
    }).then(() => {
        templateSnapshot = cleanTemplate(template.value);
        savingActive.value = false;
        savedActive.value = true;
        if (savedActiveTimer) clearTimeout(savedActiveTimer);
        savedActiveTimer = setTimeout(() => savedActive.value = false, 1500);
    }).catch(() => {
        savingActive.value = false;
    });
}
const debouncedUpdateTemplate = debounce(updateTemplate, 600);

const copyTemplate = async () => {
    if (!template.value) return;
    const text = template.value.subject
        ? `Subject: ${template.value.subject}\n\n${template.value.body}`
        : (template.value.body ?? '');
    try {
        await navigator.clipboard.writeText(text);
        useStore().setSaved('Copied to clipboard!');
    } catch (e) {
        // clipboard access can be denied by the browser; nothing more to do here.
    }
}

const deleteTemplate = () => {
    axios.delete(route('email-templates.destroy', props.templateId)).then(() => {
        router.visit(route('directories.email-templates', props.directoryId));
    });
}

refreshDirectory();
refreshTemplate();
</script>

<template>
    <Head :title="template?.name || 'Email template'"/>

    <SavedLabel/>

    <div v-if="loading" class="p-8 text-center text-sm text-gray-400">Loading…</div>
    <div v-else-if="template" class="surface-card">
                <div class="p-4 flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-900">Template details</div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="copyTemplate"
                                    class="w-7 h-7 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
                                    title="Copy subject + body to clipboard">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor">
                                    <rect x="8" y="8" width="12" height="12" rx="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>
                                </svg>
                            </button>
                            <DeleteModal @deleted="deleteTemplate" label="Delete this email template?"/>
                        </div>
                    </div>
                    <label class="text-xs font-medium text-gray-500">Name</label>
                    <input type="text" v-model="template.name"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                    <label class="text-xs font-medium text-gray-500 mt-1">Subject</label>
                    <input type="text" v-model="template.subject"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                    <label class="text-xs font-medium text-gray-500 mt-1">Language</label>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1">
                            {{ LANGUAGES[template.language] || template.language }}
                        </span>
                        <span class="text-[11px] text-gray-400">Set when generated — not editable afterwards.</span>
                    </div>
                    <label class="text-xs font-medium text-gray-500 mt-1">Body</label>
                    <textarea v-model="template.body" rows="14"
                              class="px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition font-mono text-sm"/>
                    <div class="text-[11px] leading-3 text-gray-500 h-3">
                        <span v-if="savingActive" class="text-gray-400">Saving…</span>
                        <span v-else-if="savedActive" class="text-brand-accent-dark font-medium">Saved</span>
                    </div>
                </div>
            </div>
</template>
