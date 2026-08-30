<script setup>
import {reactive, ref, watch} from "vue";
import {Link} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";

const props = defineProps({
    activeProductId: {type: Number, default: null},
    activeDirectoryId: {type: Number, default: null},
    activeProspectId: {type: Number, default: null},
});

const products = ref([]);
const loading = ref(true);
const expandedProducts = ref(new Set());
const expandedDirectories = ref(new Set());
const prospectsByDirectory = reactive({});
const loadingDirectories = ref(new Set());

const expandProduct = (productId) => {
    if (expandedProducts.value.has(productId)) return;
    expandedProducts.value = new Set(expandedProducts.value).add(productId);
}

const toggleProduct = (productId) => {
    const next = new Set(expandedProducts.value);
    if (next.has(productId)) next.delete(productId);
    else next.add(productId);
    expandedProducts.value = next;
}

const loadProspects = (directoryId, force = false) => {
    if (loadingDirectories.value.has(directoryId)) return;
    if (!force && prospectsByDirectory[directoryId]) return;
    loadingDirectories.value = new Set(loadingDirectories.value).add(directoryId);
    axios.get(route('prospects.tree', directoryId)).then(response => {
        prospectsByDirectory[directoryId] = response.data;
    }).finally(() => {
        const next = new Set(loadingDirectories.value);
        next.delete(directoryId);
        loadingDirectories.value = next;
    });
}

const expandDirectory = (directoryId) => {
    if (!expandedDirectories.value.has(directoryId)) {
        expandedDirectories.value = new Set(expandedDirectories.value).add(directoryId);
    }
    loadProspects(directoryId);
}

const toggleDirectory = (directoryId) => {
    const next = new Set(expandedDirectories.value);
    if (next.has(directoryId)) {
        next.delete(directoryId);
        expandedDirectories.value = next;
    } else {
        next.add(directoryId);
        expandedDirectories.value = next;
        loadProspects(directoryId);
    }
}

const refreshTree = (silent = false) => {
    if (!silent) loading.value = true;
    axios.get(route('products.tree')).then(response => {
        products.value = response.data;
        if (props.activeProductId) expandProduct(props.activeProductId);
        if (props.activeDirectoryId) expandDirectory(props.activeDirectoryId);
        // Force-refetch prospects for any directory already expanded, so a
        // prospect added/removed on the current page shows up here too.
        expandedDirectories.value.forEach(id => loadProspects(id, true));
    }).finally(() => {
        if (!silent) loading.value = false;
    });
}

watch(() => props.activeProductId, (id) => {
    if (id) expandProduct(id);
});

watch(() => props.activeDirectoryId, (id) => {
    if (id) expandDirectory(id);
});

// Sibling components (add/delete forms elsewhere on the page) bump this
// after a mutation, since the tree is a separately-mounted component that
// otherwise has no way to know about changes made outside of it.
watch(() => useStore().prospectionTreeVersion, () => refreshTree(true));

refreshTree();
</script>

<template>
    <nav class="surface-card p-2 flex flex-col gap-0.5 lg:sticky lg:top-6">
        <Link :href="route('products')"
              class="px-2 py-1.5 rounded-lg text-sm font-medium transition"
              :class="!activeProductId && !activeDirectoryId
                  ? 'bg-brand-accent/10 text-brand-accent-dark'
                  : 'text-gray-700 hover:bg-brand-surface'">
            Prospection
        </Link>

        <div v-if="loading" class="px-2 py-2 text-xs text-gray-400">Loading…</div>
        <div v-else-if="!products.length" class="px-2 py-2 text-xs text-gray-400">No products yet.</div>

        <div v-for="product in products" :key="product.id" class="flex flex-col">
            <div class="flex items-center gap-0.5">
                <button type="button" @click="toggleProduct(product.id)"
                        class="shrink-0 w-5 h-5 flex items-center justify-center rounded text-gray-400 hover:text-gray-600 transition">
                    <svg class="size-3 transition-transform" :class="expandedProducts.has(product.id) ? 'rotate-90' : ''"
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="3" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <Link :href="route('products.view', product.id)"
                      class="min-w-0 flex-1 truncate px-1.5 py-1.5 rounded-lg text-sm font-medium transition"
                      :class="activeProductId === product.id && !activeDirectoryId
                          ? 'bg-brand-accent/10 text-brand-accent-dark'
                          : 'text-gray-700 hover:bg-brand-surface'">
                    {{ product.name || 'Untitled product' }}
                </Link>
            </div>
            <div v-if="expandedProducts.has(product.id)" class="ml-5 flex flex-col gap-0.5 border-l border-gray-100 pl-2">
                <div v-if="!product.directories.length" class="px-2 py-1 text-xs text-gray-400">
                    No directories yet
                </div>
                <div v-for="directory in product.directories" :key="directory.id" class="flex flex-col">
                    <div class="flex items-center gap-0.5">
                        <button type="button" @click="toggleDirectory(directory.id)"
                                class="shrink-0 w-4 h-4 flex items-center justify-center rounded text-gray-400 hover:text-gray-600 transition">
                            <svg class="size-2.5 transition-transform"
                                 :class="expandedDirectories.has(directory.id) ? 'rotate-90' : ''"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <Link :href="route('directories.view', directory.id)"
                              class="min-w-0 flex-1 truncate px-1.5 py-1 rounded-lg text-xs font-medium transition"
                              :class="activeDirectoryId === directory.id && !activeProspectId
                                  ? 'bg-brand-accent/10 text-brand-accent-dark'
                                  : 'text-gray-500 hover:bg-brand-surface hover:text-gray-700'">
                            {{ directory.name || 'Untitled directory' }}
                        </Link>
                    </div>
                    <div v-if="expandedDirectories.has(directory.id)"
                         class="ml-4 flex flex-col gap-0.5 border-l border-gray-100 pl-2">
                        <div v-if="loadingDirectories.has(directory.id)" class="px-2 py-1 text-[11px] text-gray-400">
                            Loading…
                        </div>
                        <div v-else-if="!(prospectsByDirectory[directory.id] ?? []).length"
                             class="px-2 py-1 text-[11px] text-gray-400">
                            No prospects yet
                        </div>
                        <Link v-for="prospect in prospectsByDirectory[directory.id]" :key="prospect.id"
                              :href="route('prospects.view', [directory.id, prospect.id])"
                              class="min-w-0 truncate px-2 py-1 rounded-lg text-[11px] font-medium transition"
                              :class="activeProspectId === prospect.id
                                  ? 'bg-brand-accent/10 text-brand-accent-dark'
                                  : 'text-gray-400 hover:bg-brand-surface hover:text-gray-700'">
                            {{ prospect.name || 'Untitled prospect' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</template>
