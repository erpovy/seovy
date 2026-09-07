<script setup lang="ts">
import SkyToggle from '@/Components/SkyToggle.vue';

withDefaults(defineProps<{
    compact?: boolean;
}>(), {
    compact: false,
});
</script>

<template>
    <div class="flex items-center">
        <SkyToggle :size="compact ? '6.5px' : '7.5px'" />
    </div>
</template>

