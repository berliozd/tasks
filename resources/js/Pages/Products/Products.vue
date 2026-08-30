<script>
import ProspectionLayout from '@/Layouts/ProspectionLayout.vue';

export default {
    layout: ProspectionLayout,
};
</script>

<script setup>
import {Head, router} from '@inertiajs/vue3';
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";
import DeleteConfirmPopover from "@/Pages/Directories/Partials/DeleteConfirmPopover.vue";
import {useStore} from "@/Composables/store.js";

const products = ref([]);
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
</script>

<template>
    <Head title="Prospection"/>

    <div class="surface-card overflow-hidden">
        <div class="p-4 text-sm font-medium text-gray-900 border-b border-gray-100">Products</div>
        <div v-if="!products.length" class="p-8 text-center text-sm text-gray-400">
            No products yet. Add one to start organizing directories.
        </div>
        <div v-else class="divide-y divide-gray-100">
            <div v-for="product in products" :key="product.id"
                 class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-brand-surface transition"
                 @click="openProduct(product)">
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium text-gray-900 truncate">
                        {{ product.name || 'Untitled product' }}
                    </div>
                    <div class="text-xs text-gray-500 truncate">
                        {{ product.website_url || product.brief || 'No details yet' }}
                    </div>
                </div>
                <span class="shrink-0 inline-flex items-center justify-center rounded-full bg-brand-accent/10 text-brand-accent-dark text-xs font-semibold tabular-nums min-w-6 h-6 px-2">
                    {{ product.directories_count ?? 0 }}
                </span>
                <div @click.stop>
                    <DeleteConfirmPopover @deleted="deleteProduct(product)"
                                           label="Delete this product? All its directories, prospects and logged actions will be deleted too."/>
                </div>
            </div>
        </div>
    </div>

    <button type="button" @click="openAddModal" title="Add a product"
            class="mt-4 w-1/2 mx-auto h-16 flex items-center justify-center rounded-lg border-2 border-brand-accent text-brand-accent text-3xl leading-none hover:bg-brand-accent/10 active:scale-95 transition">
        +
    </button>

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
