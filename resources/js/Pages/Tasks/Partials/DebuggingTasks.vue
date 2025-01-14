<script setup>
const props = defineProps({todayTasks: Array, lateTasks: Array, completedTodayTasks: Array});
</script>

<template>
    <div>
        <div class="border-2 border-black mb-2">
            Scheduled Today
            <div class="grid grid-cols-4 bg-gray-400 p-2">
                <div>label</div>
                <div>scheduled_at</div>
                <div>completed_at</div>
                <div>created_at</div>
            </div>
            <div v-for="task in todayTasks" class="grid grid-cols-4 p-2">
                <div>{{ task.label }}</div>
                <div>{{ task.scheduled_at }}</div>
                <div>{{ task.completed_at }}</div>
                <div>{{ task.created_at }}</div>
            </div>
        </div>

        <div class="border-2 border-black mb-2">
            Late today
            <div class="grid grid-cols-4 bg-gray-400 p-2">
                <div>label</div>
                <div>scheduled_at</div>
                <div>completed_at</div>
                <div>created_at</div>
            </div>
            <div v-for="task in lateTasks" class="grid grid-cols-4 p-2">
                <div>{{ task.label }}</div>
                <div>{{ task.scheduled_at }}</div>
                <div>{{ task.completed_at }}</div>
                <div>{{ task.created_at }}</div>

            </div>
        </div>

        <div class="border-2 border-black mb-2">
            Completed Today
            <div class="grid grid-cols-4 bg-gray-400 p-2">
                <div>label</div>
                <div>scheduled_at</div>
                <div>completed_at</div>
                <div>created_at</div>
            </div>
            <div v-for="task in completedTodayTasks" class="grid grid-cols-4 p-2">
                <div>{{ task.label }}</div>
                <div>{{ task.scheduled_at }}</div>
                <div>{{ task.completed_at }}</div>
                <div>{{ task.created_at }}</div>
            </div>
        </div>
    </div>
</template>