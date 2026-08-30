<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProspectionTree from '@/Components/ProspectionTree.vue';
import {Link} from '@inertiajs/vue3';
import {useStore} from '@/Composables/store.js';
import {storeToRefs} from 'pinia';

const {activeProductId, activeDirectoryId, activeProspectId, breadcrumb} = storeToRefs(useStore());
</script>

<template>
    <AppLayout title="Prospection">
        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <template v-for="(crumb, index) in breadcrumb" :key="index">
                    <Link v-if="crumb.href" :href="crumb.href" class="text-sm text-gray-500 hover:text-gray-700">
                        {{ crumb.label }}
                    </Link>
                    <h2 v-else-if="index === breadcrumb.length - 1"
                        class="font-semibold text-xl leading-tight text-slate-900">
                        {{ crumb.label }}
                    </h2>
                    <span v-else class="text-sm text-gray-500">{{ crumb.label }}</span>
                    <span v-if="index < breadcrumb.length - 1" class="text-gray-300">/</span>
                </template>
            </div>
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
