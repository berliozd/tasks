<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';

defineProps({
    showHomeLink: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div class="min-h-screen">
        <header class="bg-gradient-to-r from-brand-navy to-brand-navy-light shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="h-16 flex items-center justify-between">
                    <Link href="/" class="shrink-0">
                        <ApplicationMark class="block h-9" />
                    </Link>

                    <slot name="header-right">
                        <Link
                            v-if="showHomeLink"
                            href="/"
                            class="text-sm font-medium text-slate-200 hover:text-white transition"
                        >
                            Home
                        </Link>
                    </slot>
                </div>
            </div>
        </header>

        <main class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>

