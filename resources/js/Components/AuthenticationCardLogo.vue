<script setup>
import {Link} from '@inertiajs/vue3';
</script>

<template>
    <template v-if="$page.props.auth.user">
        <Link :href="route('tasks')">
            <span class="inline-flex items-center justify-center rounded-2xl bg-brand-navy p-4 shadow-card-hover">
                <img src="/img/tasks-nobg.png" class="w-36 h-auto object-contain" alt="">
            </span>
        </Link>
    </template>
    <template v-else>
        <span class="inline-flex items-center justify-center rounded-xl bg-brand-navy p-4">
            <img src="/img/tasks-nobg.png" class="w-36 h-auto object-contain" alt="">
        </span>
    </template>
</template>
