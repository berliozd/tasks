<script setup>
import ProspectionLayout from '@/Layouts/ProspectionLayout.vue';
import {ref} from "vue";
import {Link} from "@inertiajs/vue3";
import EmailTemplates from "@/Pages/Directories/Partials/EmailTemplates.vue";

const props = defineProps({directoryId: Number});

const directory = ref({name: '', product: null});

const refreshDirectory = () => {
    axios.get(route('directories.show', props.directoryId)).then(response => {
        directory.value = response.data;
    });
}

refreshDirectory();
</script>

<template>
    <ProspectionLayout title="Email templates" :active-product-id="directory.product?.id" :active-directory-id="directoryId">

        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('products')" class="text-sm text-gray-500 hover:text-gray-700">
                    Prospection
                </Link>
                <span class="text-gray-300">/</span>
                <Link v-if="directory.product" :href="route('products.view', directory.product.id)"
                      class="text-sm text-gray-500 hover:text-gray-700">
                    {{ directory.product.name }}
                </Link>
                <span class="text-gray-300">/</span>
                <Link :href="route('directories.view', directoryId)" class="text-sm text-gray-500 hover:text-gray-700">
                    {{ directory.name || '...' }}
                </Link>
                <span class="text-gray-300">/</span>
                <h2 class="font-semibold text-xl leading-tight text-slate-900">Email templates</h2>
            </div>
        </template>

        <div class="surface-card overflow-hidden">
            <EmailTemplates :directory-id="directoryId"/>
        </div>
    </ProspectionLayout>
</template>
