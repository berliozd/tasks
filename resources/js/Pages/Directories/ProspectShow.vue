<script>
import ProspectionLayout from '@/Layouts/ProspectionLayout.vue';

export default {
    layout: ProspectionLayout,
};
</script>

<script setup>
import {ref, watch, watchEffect} from "vue";
import SavedLabel from "@/Components/SavedLabel.vue";
import CollapsibleSection from "@/Components/CollapsibleSection.vue";
import ProspectActions from "@/Pages/Directories/Partials/ProspectActions.vue";
import debounce from "lodash/debounce";
import {Head} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";

const props = defineProps({directoryId: Number, prospectId: Number});

const prospect = ref(null);
const loading = ref(true);

watchEffect(() => {
    const crumbs = [{label: 'Prospection', href: route('products')}];
    if (prospect.value?.directory?.product) {
        crumbs.push({
            label: prospect.value.directory.product.name,
            href: route('products.view', prospect.value.directory.product.id),
        });
    }
    crumbs.push({label: prospect.value?.directory?.name || '...', href: route('directories.view', props.directoryId)});
    crumbs.push({label: prospect.value?.name || (loading.value ? '...' : 'Prospect'), href: null});
    useStore().setProspectionActive({
        productId: prospect.value?.directory?.product?.id,
        directoryId: props.directoryId,
        prospectId: props.prospectId,
        breadcrumb: crumbs,
    });
});
const savingActive = ref(false);
const savedActive = ref(false);
let savedActiveTimer = null;
let prospectSnapshot = null;

const cleanProspect = (p) => JSON.stringify({name: p.name, website: p.website, email: p.email, won: p.won});

const refreshProspect = () => {
    loading.value = true;
    axios.get(route('prospects.show', props.prospectId)).then(response => {
        prospect.value = response.data;
        prospectSnapshot = cleanProspect(prospect.value);
    }).finally(() => loading.value = false);
}

watch(() => prospect.value && [prospect.value.name, prospect.value.website, prospect.value.email, prospect.value.won], () => {
    if (!prospect.value) return;
    if (cleanProspect(prospect.value) === prospectSnapshot) return;
    debouncedUpdateProspect();
});

const updateProspect = () => {
    if (!prospect.value) return;
    savingActive.value = true;
    axios.patch(route('prospects.update', prospect.value.id), {
        name: prospect.value.name,
        website: prospect.value.website,
        email: prospect.value.email,
        won: prospect.value.won,
    }).then(() => {
        prospectSnapshot = cleanProspect(prospect.value);
        savingActive.value = false;
        savedActive.value = true;
        if (savedActiveTimer) clearTimeout(savedActiveTimer);
        savedActiveTimer = setTimeout(() => savedActive.value = false, 1500);
    }).catch(() => {
        savingActive.value = false;
    });
}
const debouncedUpdateProspect = debounce(updateProspect, 600);

refreshProspect();
</script>

<template>
    <Head :title="prospect?.name || 'Prospect'"/>

    <SavedLabel/>

    <div v-if="loading" class="p-8 text-center text-sm text-gray-400">Loading…</div>
        <template v-else-if="prospect">
                <CollapsibleSection title="Prospect details">
                    <label class="text-xs font-medium text-gray-500">Name</label>
                    <input type="text" v-model="prospect.name"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                    <label class="text-xs font-medium text-gray-500 mt-1">Website</label>
                    <input type="text" v-model="prospect.website"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                    <label class="text-xs font-medium text-gray-500 mt-1">Email</label>
                    <input type="email" v-model="prospect.email"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                    <label class="flex items-center gap-2 mt-1 text-sm text-gray-700">
                        <input type="checkbox" v-model="prospect.won"
                               class="rounded border-gray-300 text-brand-accent focus:ring-brand-accent transition">
                        Won
                    </label>
                    <div class="text-[11px] leading-3 text-gray-500 h-3">
                        <span v-if="savingActive" class="text-gray-400">Saving…</span>
                        <span v-else-if="savedActive" class="text-brand-accent-dark font-medium">Saved</span>
                    </div>
                </CollapsibleSection>

                <div class="surface-card p-4">
                    <div class="text-sm font-medium text-gray-900 mb-2">Actions</div>
                    <ProspectActions :prospect-id="prospect.id" :directory-id="directoryId"
                                      @counts-changed="Object.assign(prospect, $event)"/>
                </div>
            </template>
</template>
