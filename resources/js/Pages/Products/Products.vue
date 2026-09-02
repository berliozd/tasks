<script>
import ProspectionLayout from '@/Layouts/ProspectionLayout.vue';

export default {
    layout: ProspectionLayout,
};
</script>

<script setup>
import {Head, Link, router} from '@inertiajs/vue3';
import {ref} from "vue";
import {format} from 'date-fns';
import Modal from "@/Components/Modal.vue";
import DeleteConfirmPopover from "@/Pages/Directories/Partials/DeleteConfirmPopover.vue";
import {useStore} from "@/Composables/store.js";
import {statusFlags} from "@/Composables/prospectActionStatus.js";

const products = ref([]);
const plannedActions = ref([]);
const plannedLimit = ref(20);
const plannedHasMore = ref(false);
const lastSentActions = ref([]);
const showAddModal = ref(false);
const newProduct = ref({name: '', website_url: '', brief: ''});
const adding = ref(false);
const errorMsg = ref('');

useStore().setProspectionActive({breadcrumb: [{label: 'Prospection', href: null}]});

const refreshProducts = () => {
    axios.get(route('products.index'))
        .then(response => {
            products.value = response.data;
        });
}

const refreshPlannedActions = () => {
    axios.get(route('prospect-actions.planned'), {params: {limit: plannedLimit.value}})
        .then(response => {
            plannedActions.value = response.data.items;
            plannedHasMore.value = response.data.has_more;
        });
}

const loadMorePlannedActions = () => {
    plannedLimit.value += 10;
    refreshPlannedActions();
}

const refreshLastSentActions = () => {
    axios.get(route('prospect-actions.last-sent'), {params: {limit: 20}})
        .then(response => {
            lastSentActions.value = response.data.items;
        });
}

const formatScheduled = (date) => date ? format(new Date(date), 'MMM d, HH:mm') : '';

const truncate = (text, length) => text && text.length > length ? text.slice(0, length) + '…' : text;

const openAddModal = () => {
    newProduct.value = {name: '', website_url: '', brief: ''};
    errorMsg.value = '';
    showAddModal.value = true;
}

const closeAddModal = () => {
    showAddModal.value = false;
}

const addProduct = async () => {
    if (!validateInput()) return
    adding.value = true;
    await axios.post(route('products.store'), newProduct.value)
        .then(() => {
            closeAddModal();
            refreshProducts();
            useStore().refreshProspectionTree();
        })
        .catch((error) => {
            errorMsg.value = error.response?.data?.message ?? 'Something went wrong';
        })
        .finally(() => adding.value = false);
}

const validateInput = () => {
    errorMsg.value = '';
    if (newProduct.value.name === '') {
        errorMsg.value = 'Product name is required';
        return false;
    }
    return true;
}

const deleteProduct = (product) => {
    axios.delete(route('products.delete', product.id)).then(() => {
        refreshProducts()
        useStore().refreshProspectionTree();
    })
}

const openProduct = (product) => {
    router.visit(route('products.view', product.id));
}

refreshProducts();
refreshPlannedActions();
refreshLastSentActions();
</script>

<template>
    <Head title="Prospection"/>

    <div class="surface-card overflow-hidden">
        <div class="p-4 flex items-center gap-2 border-b border-gray-100">
            <span class="text-sm font-medium text-gray-900">Products</span>
            <button type="button" @click="openAddModal" title="Add a product"
                    class="ml-auto shrink-0 inline-flex items-center justify-center size-9 rounded-full bg-brand-accent text-white text-xl leading-none hover:bg-brand-accent-dark active:scale-95 transition">
                +
            </button>
        </div>
        <div v-if="!products.length" class="p-8 text-center text-sm text-gray-400">
            No products yet. Add one to start organizing directories.
        </div>
        <div v-else class="divide-y divide-gray-100">
            <div v-for="product in products" :key="product.id"
                 class="flex flex-col gap-1.5 px-4 py-3 cursor-pointer hover:bg-brand-surface transition"
                 @click="openProduct(product)">
                <div class="flex items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-900 truncate">
                            {{ product.name || 'Untitled product' }}
                        </div>
                        <div class="text-xs text-gray-500 truncate">
                            {{ product.website_url || product.brief || 'No details yet' }}
                        </div>
                    </div>
                    <span class="shrink-0 flex items-center gap-1 text-xs text-gray-400 tabular-nums">
                        <span>{{ product.directories_count }} director{{ product.directories_count === 1 ? 'y' : 'ies' }}</span>
                        <span class="text-gray-300">/</span>
                        <span>{{ product.prospects_count }} prospect{{ product.prospects_count === 1 ? '' : 's' }}</span>
                    </span>
                    <div @click.stop>
                        <DeleteConfirmPopover @deleted="deleteProduct(product)"
                                               label="Delete this product? All its directories, prospects and logged actions will be deleted too."/>
                    </div>
                </div>
                <div v-if="statusFlags(product.action_status_counts).length" class="flex flex-wrap gap-1">
                    <span v-for="flag in statusFlags(product.action_status_counts)" :key="flag.status"
                          class="rounded-full text-[11px] font-medium px-2 py-0.5" :class="flag.colorClass">
                        {{ flag.label }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="surface-card overflow-hidden mt-4">
        <div class="p-4 text-sm font-medium text-gray-900 border-b border-gray-100">Planned actions</div>
        <div v-if="!plannedActions.length" class="p-8 text-center text-sm text-gray-400">
            No actions currently queued for auto-send.
        </div>
        <div v-else class="divide-y divide-gray-100">
            <Link v-for="action in plannedActions" :key="action.id"
                  :href="route('prospects.view', [action.prospect.directory.id, action.prospect.id])"
                  class="flex flex-col gap-1 px-4 py-3 text-sm hover:bg-brand-surface transition">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="shrink-0 font-medium text-gray-900 truncate max-w-[45%] sm:max-w-[180px]">
                        {{ action.prospect?.directory?.product?.name || 'Untitled product' }}
                    </span>
                    <span class="shrink-0 text-gray-500 truncate max-w-[45%] sm:max-w-[180px]">
                        {{ action.prospect?.directory?.name || 'Untitled directory' }}
                    </span>
                    <span class="shrink-0 text-gray-700 truncate max-w-[45%] sm:max-w-[180px]">
                        {{ action.prospect?.name || 'Untitled prospect' }}
                    </span>
                    <span class="ml-auto shrink-0 text-xs text-gray-400 tabular-nums">
                        {{ formatScheduled(action.scheduled_at) }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-400">
                    <span class="shrink-0 font-medium text-gray-500 capitalize">{{ action.type }}</span>
                    <span class="truncate max-w-[60%] sm:max-w-none">To: {{ action.prospect?.email || '—' }}</span>
                    <span v-if="action.subject" class="truncate max-w-[60%] sm:max-w-none">Subject: {{ truncate(action.subject, 30) }}</span>
                    <span v-if="action.from_label" class="truncate max-w-[60%] sm:max-w-none">From: {{ action.from_label }}</span>
                </div>
            </Link>
        </div>
        <div v-if="plannedHasMore" class="p-3 border-t border-gray-100 flex justify-center">
            <button type="button" @click="loadMorePlannedActions"
                    class="text-xs font-medium text-brand-navy hover:underline">
                See more
            </button>
        </div>
    </div>

    <div class="surface-card overflow-hidden mt-4">
        <div class="p-4 text-sm font-medium text-gray-900 border-b border-gray-100">Last actions</div>
        <div v-if="!lastSentActions.length" class="p-8 text-center text-sm text-gray-400">
            No actions sent yet.
        </div>
        <div v-else class="divide-y divide-gray-100">
            <Link v-for="action in lastSentActions" :key="action.id"
                  :href="route('prospects.view', [action.prospect.directory.id, action.prospect.id])"
                  class="flex flex-col gap-1 px-4 py-3 text-sm hover:bg-brand-surface transition">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="shrink-0 font-medium text-gray-900 truncate max-w-[45%] sm:max-w-[180px]">
                        {{ action.prospect?.directory?.product?.name || 'Untitled product' }}
                    </span>
                    <span class="shrink-0 text-gray-500 truncate max-w-[45%] sm:max-w-[180px]">
                        {{ action.prospect?.directory?.name || 'Untitled directory' }}
                    </span>
                    <span class="shrink-0 text-gray-700 truncate max-w-[45%] sm:max-w-[180px]">
                        {{ action.prospect?.name || 'Untitled prospect' }}
                    </span>
                    <span class="ml-auto shrink-0 text-xs text-gray-400 tabular-nums">
                        {{ formatScheduled(action.updated_at) }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-400">
                    <span class="shrink-0 font-medium text-gray-500 capitalize">{{ action.type }}</span>
                    <span class="truncate max-w-[60%] sm:max-w-none">To: {{ action.prospect?.email || '—' }}</span>
                    <span v-if="action.subject" class="truncate max-w-[60%] sm:max-w-none">Subject: {{ truncate(action.subject, 30) }}</span>
                    <span v-if="action.from_label" class="truncate max-w-[60%] sm:max-w-none">From: {{ action.from_label }}</span>
                </div>
            </Link>
        </div>
    </div>

    <Modal :show="showAddModal" @close="closeAddModal" max-width="md">
        <div class="p-4 flex flex-col gap-2">
            <div class="text-sm font-medium text-gray-900">New product</div>
            <input type="text" v-model="newProduct.name" placeholder="Product name"
                   class="w-full rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                   @keydown.enter="addProduct">
            <input type="text" v-model="newProduct.website_url" placeholder="Website URL"
                   class="w-full rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                   @keydown.enter="addProduct">
            <textarea v-model="newProduct.brief" rows="3" placeholder="Rapid brief"
                      class="w-full rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"/>
            <div v-if="errorMsg" class="text-sm text-red-600">{{ errorMsg }}</div>
            <div class="flex justify-end gap-2 mt-2">
                <button type="button" @click="closeAddModal"
                        class="inline-flex items-center px-4 py-2 rounded-lg font-semibold text-xs text-gray-600 uppercase tracking-widest hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="button" @click="addProduct" :disabled="adding"
                        class="inline-flex items-center px-4 py-2 bg-brand-navy border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light disabled:opacity-50 transition">
                    {{ adding ? 'Adding…' : 'Add product' }}
                </button>
            </div>
        </div>
    </Modal>
</template>
