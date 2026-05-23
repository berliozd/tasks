<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    terms: String,
});
</script>

<template>
    <Head title="Terms of Service" />

    <GuestLayout :show-home-link="false">
        <div class="flex flex-col items-center">
            <div class="mt-4">
                <AuthenticationCardLogo />
            </div>

            <div
                class="w-full sm:max-w-2xl mt-6 p-6 bg-white shadow-md overflow-hidden sm:rounded-lg prose"
                v-html="terms"
            />
        </div>
    </GuestLayout>
</template>
