import { ref, computed } from 'vue';

export type ThemeMode = 'dark' | 'light' | 'system';

const currentTheme = ref<ThemeMode>('dark');

export function useTheme() {
    const isDark = computed(() => {
        if (currentTheme.value === 'dark') return true;
        if (currentTheme.value === 'light') return false;
        if (typeof window !== 'undefined') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        return true;
    });

    const applyTheme = (theme: ThemeMode) => {
        if (typeof document === 'undefined') return;

        const root = document.documentElement;
        let effectiveDark = false;

        if (theme === 'dark') {
            effectiveDark = true;
        } else if (theme === 'light') {
            effectiveDark = false;
        } else {
            effectiveDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        if (effectiveDark) {
            root.classList.add('dark');
            root.classList.remove('light');
            root.setAttribute('data-theme', 'dark');
        } else {
            root.classList.add('light');
            root.classList.remove('dark');
            root.setAttribute('data-theme', 'light');
        }
    };

    const setTheme = (theme: ThemeMode) => {
        currentTheme.value = theme;
        if (typeof window !== 'undefined') {
            localStorage.setItem('seovy_theme', theme);
            // Also store in cookie so server or subsequent visits know
            document.cookie = `seovy_theme=${theme};path=/;max-age=31536000;SameSite=Lax`;
        }
        applyTheme(theme);
    };

    const toggleTheme = () => {
        const next = isDark.value ? 'light' : 'dark';
        setTheme(next);
    };

    const initTheme = () => {
        if (typeof window === 'undefined') return;

        const saved = (localStorage.getItem('seovy_theme') as ThemeMode) || 'dark';
        currentTheme.value = saved;
        applyTheme(saved);

        // Listen for system theme changes if mode is 'system'
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (currentTheme.value === 'system') {
                applyTheme('system');
            }
        });
    };

    return {
        currentTheme,
        isDark,
        setTheme,
        toggleTheme,
        initTheme,
    };
}
