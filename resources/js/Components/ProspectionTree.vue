<script setup>
import {ref, watch} from "vue";
import {Link} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";

const props = defineProps({
    activeProductId: {type: Number, default: null},
    activeDirectoryId: {type: Number, default: null},
});

const products = ref([]);
const loading = ref(true);
const expandedProducts = ref(new Set());

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

const refreshTree = (silent = false) => {
    if (!silent) loading.value = true;
    axios.get(route('products.tree')).then(response => {
        products.value = response.data;
        if (props.activeProductId) expandProduct(props.activeProductId);
    }).finally(() => {
        if (!silent) loading.value = false;
    });
}

watch(() => props.activeProductId, (id) => {
    if (id) expandProduct(id);
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
                <Link v-for="directory in product.directories" :key="directory.id"
                      :href="route('directories.view', directory.id)"
                      class="min-w-0 truncate px-1.5 py-1 rounded-lg text-xs font-medium transition"
                      :class="activeDirectoryId === directory.id
                          ? 'bg-brand-accent/10 text-brand-accent-dark'
                          : 'text-gray-500 hover:bg-brand-surface hover:text-gray-700'">
                    {{ directory.name || 'Untitled directory' }}
                </Link>
            </div>
        </div>
    </nav>
</template>
