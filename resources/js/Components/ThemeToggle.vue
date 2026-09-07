<script setup lang="ts">
import { useTheme } from '@/composables/useTheme';
import { Sun, Moon } from 'lucide-vue-next';
import { useI18n } from '@/i18n';

withDefaults(defineProps<{
    compact?: boolean;
}>(), {
    compact: false,
});

const { isDark, toggleTheme } = useTheme();
const { t } = useI18n();
</script>

<template>
    <button
        type="button"
        @click="toggleTheme"
        class="relative inline-flex items-center justify-center p-2 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-1 focus:ring-indigo-500"
        :class="isDark
            ? 'bg-slate-800/60 hover:bg-slate-800 border-slate-700/60 text-amber-400 hover:text-amber-300 shadow-sm'
            : 'bg-white hover:bg-slate-100 border-slate-200 text-indigo-600 hover:text-indigo-700 shadow-sm'"
        :title="isDark ? t('common.theme_light', 'Aydınlık Temaya Geç') : t('common.theme_dark', 'Karanlık Temaya Geç')"
        :aria-label="isDark ? t('common.theme_light', 'Aydınlık Temaya Geç') : t('common.theme_dark', 'Karanlık Temaya Geç')"
    >
        <!-- Sun Icon (shown in dark mode to switch to light) -->
        <Sun
            v-if="isDark"
            class="w-4 h-4 transition-transform duration-300 rotate-0 hover:rotate-45"
        />
        <!-- Moon Icon (shown in light mode to switch to dark) -->
        <Moon
            v-else
            class="w-4 h-4 transition-transform duration-300 -rotate-12 hover:rotate-0"
        />
        <span class="sr-only">{{ isDark ? 'Switch to light mode' : 'Switch to dark mode' }}</span>
    </button>
</template>
