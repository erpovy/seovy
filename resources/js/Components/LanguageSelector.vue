<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useI18n, type SupportedLocale } from '@/i18n';
import { Globe, Check, ChevronDown } from 'lucide-vue-next';
import FlagIcon from '@/Components/FlagIcon.vue';

const props = withDefaults(defineProps<{
    compact?: boolean;
    placement?: 'top' | 'bottom';
}>(), {
    compact: false,
    placement: 'bottom'
});

const { locale, activeLanguage, supportedLocales, setLocale } = useI18n();
const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const selectLanguage = (code: SupportedLocale) => {
    setLocale(code);
    isOpen.value = false;
};

const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative inline-block text-left" ref="dropdownRef">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="toggleDropdown"
            class="flex items-center space-x-2 px-3 py-2 rounded-xl bg-slate-800/60 hover:bg-slate-800 border border-slate-700/60 text-slate-300 hover:text-white transition-all text-xs font-medium focus:outline-none focus:ring-1 focus:ring-indigo-500"
            :class="{ 'w-full justify-between': !compact }"
            :title="activeLanguage.name"
        >
            <div class="flex items-center space-x-2 truncate">
                <FlagIcon :code="activeLanguage.code" size="w-4 h-4" />
                <span v-if="!compact" class="truncate font-medium">{{ activeLanguage.nativeName }}</span>
                <span v-else class="text-[11px] font-bold uppercase">{{ activeLanguage.code }}</span>
            </div>
            <ChevronDown class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" />
        </button>

        <!-- Dropdown Menu -->
        <div
            v-if="isOpen"
            class="absolute z-50 w-56 rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-1.5 space-y-0.5 text-xs max-h-72 overflow-y-auto"
            :class="placement === 'top' ? 'bottom-full mb-2' : 'mt-1.5'"
            style="min-width: 13rem;"
        >
            <div class="px-2.5 py-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                {{ activeLanguage.name === 'English' ? 'Select Language' : 'Language / Dil' }}
            </div>

            <button
                v-for="lang in supportedLocales"
                :key="lang.code"
                type="button"
                @click="selectLanguage(lang.code as SupportedLocale)"
                class="w-full text-left px-2.5 py-2 rounded-xl transition-colors flex items-center justify-between group"
                :class="locale === lang.code ? 'bg-indigo-600/15 text-indigo-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            >
                <div class="flex items-center space-x-2.5 truncate">
                    <FlagIcon :code="lang.code" size="w-4 h-4" />
                    <div class="truncate">
                        <div class="truncate text-xs">{{ lang.nativeName }}</div>
                        <div class="text-[10px] text-slate-500 group-hover:text-slate-400 truncate">{{ lang.name }}</div>
                    </div>
                </div>

                <Check v-if="locale === lang.code" class="w-4 h-4 text-indigo-400 shrink-0" />
            </button>
        </div>
    </div>
</template>
