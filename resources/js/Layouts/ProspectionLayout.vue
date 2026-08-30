<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProspectionTree from '@/Components/ProspectionTree.vue';

defineProps({
    title: String,
    activeProductId: {type: Number, default: null},
    activeDirectoryId: {type: Number, default: null},
    activeProspectId: {type: Number, default: null},
});
</script>

<template>
    <AppLayout :title="title">
        <template #header>
            <slot name="header"/>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6">
                <aside class="lg:w-64 shrink-0">
                    <ProspectionTree :active-product-id="activeProductId" :active-directory-id="activeDirectoryId"
                                      :active-prospect-id="activeProspectId"/>
                </aside>
                <div class="min-w-0 flex-1 flex flex-col gap-6">
                    <slot/>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
