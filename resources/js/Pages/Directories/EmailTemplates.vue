<script>
import ProspectionLayout from '@/Layouts/ProspectionLayout.vue';

export default {
    layout: ProspectionLayout,
};
</script>

<script setup>
import {ref, watchEffect} from "vue";
import {Head} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";
import EmailTemplates from "@/Pages/Directories/Partials/EmailTemplates.vue";

const props = defineProps({directoryId: Number});

const directory = ref({name: '', product: null});

watchEffect(() => {
    const crumbs = [{label: 'Prospection', href: route('products')}];
    if (directory.value.product) {
        crumbs.push({label: directory.value.product.name, href: route('products.view', directory.value.product.id)});
    }
    crumbs.push({label: directory.value.name || '...', href: route('directories.view', props.directoryId)});
    crumbs.push({label: 'Email templates', href: null});
    useStore().setProspectionActive({
        productId: directory.value.product?.id,
        directoryId: props.directoryId,
        breadcrumb: crumbs,
    });
});

const refreshDirectory = () => {
    axios.get(route('directories.show', props.directoryId)).then(response => {
        directory.value = response.data;
    });
}

refreshDirectory();
</script>

<template>
    <Head title="Email templates"/>

    <div class="surface-card overflow-hidden">
        <EmailTemplates :directory-id="directoryId"/>
    </div>
</template>
