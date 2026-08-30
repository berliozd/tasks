<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProspectionTree from '@/Components/ProspectionTree.vue';
import {Link, router} from '@inertiajs/vue3';
import {useStore} from '@/Composables/store.js';
import {storeToRefs} from 'pinia';
import {nextTick, onMounted, onUnmounted} from 'vue';

const {activeProductId, activeDirectoryId, breadcrumb} = storeToRefs(useStore());

// On mobile the tree renders above the page content (stacked layout), so
// after navigating to a prospect, bring the content into view instead of
// leaving the user looking at the sidebar they just picked it from. Hooked
// on the router itself (rather than the tree's Link) so it reliably fires
// after Inertia has actually swapped in the new page, regardless of which
// link (tree, breadcrumb, list row...) triggered the visit.
let stopFinishListener = null;
onMounted(() => {
    stopFinishListener = router.on('finish', () => {
        if (!route().current('prospects.view')) return;
        nextTick(() => {
            document.getElementById('prospection-content')?.scrollIntoView({behavior: 'smooth', block: 'start'});
        });
    });
});
onUnmounted(() => {
    stopFinishListener?.();
});
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
                    <ProspectionTree :active-product-id="activeProductId" :active-directory-id="activeDirectoryId"/>
                </aside>
                <div id="prospection-content" class="min-w-0 flex-1 flex flex-col gap-6">
                    <slot/>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
