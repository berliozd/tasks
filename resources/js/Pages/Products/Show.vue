<script>
import ProspectionLayout from '@/Layouts/ProspectionLayout.vue';

export default {
    layout: ProspectionLayout,
};
</script>

<script setup>
import {ref, watch, watchEffect} from "vue";
import SaveButton from "@/Components/SaveButton.vue";
import SavedLabel from "@/Components/SavedLabel.vue";
import CollapsibleSection from "@/Components/CollapsibleSection.vue";
import DeleteConfirmPopover from "@/Pages/Directories/Partials/DeleteConfirmPopover.vue";
import debounce from "lodash/debounce";
import {Head, router} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";

const props = defineProps({productId: Number});

const product = ref({name: '', website_url: '', brief: '', from_label: '', default_reply_to_email: '', directories: []});
const newDirectoryName = ref('');
const newDirectoryPrompt = ref('');
const errorMsg = ref('');
let storedProductSnapshot = null;
let watchProductActive = false;
const savingProduct = ref(false);
const savedProduct = ref(false);
let savedProductTimer = null;

watchEffect(() => {
    useStore().setProspectionActive({
        productId: props.productId,
        breadcrumb: [
            {label: 'Prospection', href: route('products')},
            {label: product.value.name || '...', href: null},
        ],
    });
});

const refreshProduct = () => {
    axios.get(route('products.show', props.productId)).then(response => {
        product.value = response.data;
        storedProductSnapshot = cleanProduct(product.value);
        watchProductActive = true;
    });
}

const cleanProduct = (p) => JSON.stringify({
    name: p.name, website_url: p.website_url, brief: p.brief,
    from_label: p.from_label, default_reply_to_email: p.default_reply_to_email,
});

watch(() => [
    product.value.name, product.value.website_url, product.value.brief,
    product.value.from_label, product.value.default_reply_to_email,
], () => {
    if (!watchProductActive) return;
    if (cleanProduct(product.value) === storedProductSnapshot) return;
    debouncedUpdateProduct();
});

const updateProduct = () => {
    savingProduct.value = true;
    axios.patch(route('products.update', props.productId), {
        name: product.value.name,
        website_url: product.value.website_url,
        brief: product.value.brief,
        from_label: product.value.from_label,
        default_reply_to_email: product.value.default_reply_to_email,
    }).then(() => {
        storedProductSnapshot = cleanProduct(product.value);
        savingProduct.value = false;
        savedProduct.value = true;
        if (savedProductTimer) clearTimeout(savedProductTimer);
        savedProductTimer = setTimeout(() => savedProduct.value = false, 1500);
    }).catch(() => {
        savingProduct.value = false;
    });
}
const debouncedUpdateProduct = debounce(updateProduct, 600);

const addDirectory = async () => {
    if (!newDirectoryName.value) return;
    errorMsg.value = '';
    await axios.post(route('directories.store'), {
        name: newDirectoryName.value,
        prompt: newDirectoryPrompt.value,
        product_id: props.productId,
    }).then(() => {
        newDirectoryName.value = '';
        newDirectoryPrompt.value = '';
        refreshProduct();
        useStore().refreshProspectionTree();
    }).catch((error) => {
        errorMsg.value = error.response?.data?.message ?? 'Something went wrong';
    });
}

const deleteDirectory = (directory) => {
    axios.delete(route('directories.delete', directory.id)).then(() => {
        refreshProduct()
        useStore().refreshProspectionTree();
    })
}

const openDirectory = (directory) => {
    router.visit(route('directories.view', directory.id));
}

refreshProduct();
</script>

<template>
    <Head :title="product.name || 'Product'"/>

    <SavedLabel/>

    <CollapsibleSection title="Product details" default-open>
        <label class="text-xs font-medium text-gray-500">Name</label>
        <input type="text" v-model="product.name"
               class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
        <label class="text-xs font-medium text-gray-500 mt-2">Website URL</label>
        <input type="text" v-model="product.website_url"
               class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
        <label class="text-xs font-medium text-gray-500 mt-2">Rapid brief</label>
        <textarea v-model="product.brief" rows="3"
                  class="px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"/>
        <div class="flex flex-col sm:flex-row gap-2 mt-2">
            <div class="w-full sm:flex-1 flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">From label</label>
                <input type="text" v-model="product.from_label" placeholder="e.g. Acme Team"
                       class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                <span class="text-[11px] text-gray-400">
                    Default sender name for this product's directories. Emails send from no-reply@addeos.com.
                </span>
            </div>
            <div class="w-full sm:flex-1 flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">Default reply-to email</label>
                <input type="email" v-model="product.default_reply_to_email" placeholder="Optional"
                       class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                <span class="text-[11px] text-gray-400">Default for this product's directories.</span>
            </div>
        </div>
        <div class="w-16 text-[11px] leading-3 text-gray-500">
            <span v-if="savingProduct" class="text-gray-400">Saving…</span>
            <span v-else-if="savedProduct" class="text-brand-accent-dark font-medium">Saved</span>
        </div>
    </CollapsibleSection>

    <div class="surface-card">
        <div class="p-4 flex flex-col gap-2 border-b border-gray-100">
            <div class="text-sm font-medium text-gray-900">Directories</div>
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" v-model="newDirectoryName" placeholder="Directory name"
                       class="w-full sm:flex-1 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                       @keydown.enter="addDirectory">
                <input type="text" v-model="newDirectoryPrompt"
                       placeholder="AI prompt (e.g. SaaS companies in Paris)"
                       class="w-full sm:flex-1 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                       @keydown.enter="addDirectory">
                <SaveButton @click="addDirectory"/>
            </div>
            <div v-if="errorMsg" class="text-sm text-red-600">{{ errorMsg }}</div>
        </div>

        <div v-if="!(product.directories ?? []).length" class="p-8 text-center text-sm text-gray-400">
            No directories yet. Add one above to start prospecting.
        </div>
        <div v-else class="divide-y divide-gray-100">
            <div v-for="directory in product.directories" :key="directory.id"
                 class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-brand-surface transition"
                 @click="openDirectory(directory)">
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium text-gray-900 truncate">
                        {{ directory.name || 'Untitled directory' }}
                    </div>
                    <div class="text-xs text-gray-500 truncate">
                        {{ directory.prompt || 'No AI prompt set' }}
                    </div>
                </div>
                <span class="shrink-0 inline-flex items-center justify-center rounded-full bg-brand-accent/10 text-brand-accent-dark text-xs font-semibold tabular-nums min-w-6 h-6 px-2">
                    {{ directory.prospects_count ?? 0 }}
                </span>
                <div @click.stop>
                    <DeleteConfirmPopover @deleted="deleteDirectory(directory)"
                                           label="Delete this directory? All its prospects and logged actions will be deleted too."/>
                </div>
            </div>
        </div>
    </div>
</template>
