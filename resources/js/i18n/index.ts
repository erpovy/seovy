import { ref, computed, type App } from 'vue';
import en from './locales/en.json';
import tr from './locales/tr.json';
import es from './locales/es.json';
import de from './locales/de.json';
import fr from './locales/fr.json';
import it from './locales/it.json';
import pt from './locales/pt.json';
import ru from './locales/ru.json';
import zh from './locales/zh.json';
import ar from './locales/ar.json';

export interface LanguageInfo {
    code: string;
    name: string;
    nativeName: string;
    flag: string;
    dir: 'ltr' | 'rtl';
}

export const supportedLocales: LanguageInfo[] = [
    { code: 'en', name: 'English', nativeName: 'English', flag: '🇬🇧', dir: 'ltr' },
    { code: 'tr', name: 'Turkish', nativeName: 'Türkçe', flag: '🇹🇷', dir: 'ltr' },
    { code: 'es', name: 'Spanish', nativeName: 'Español', flag: '🇪🇸', dir: 'ltr' },
    { code: 'de', name: 'German', nativeName: 'Deutsch', flag: '🇩🇪', dir: 'ltr' },
    { code: 'fr', name: 'French', nativeName: 'Français', flag: '🇫🇷', dir: 'ltr' },
    { code: 'it', name: 'Italian', nativeName: 'Italiano', flag: '🇮🇹', dir: 'ltr' },
    { code: 'pt', name: 'Portuguese', nativeName: 'Português', flag: '🇵🇹', dir: 'ltr' },
    { code: 'ru', name: 'Russian', nativeName: 'Русский', flag: '🇷🇺', dir: 'ltr' },
    { code: 'zh', name: 'Chinese (Simplified)', nativeName: '简体中文', flag: '🇨🇳', dir: 'ltr' },
    { code: 'ar', name: 'Arabic', nativeName: 'العربية', flag: '🇸🇦', dir: 'rtl' }
];

export type SupportedLocale = 'en' | 'tr' | 'es' | 'de' | 'fr' | 'it' | 'pt' | 'ru' | 'zh' | 'ar';

const messages: Record<SupportedLocale, any> = {
    en,
    tr,
    es,
    de,
    fr,
    it,
    pt,
    ru,
    zh,
    ar
};

const STORAGE_KEY = 'seovy_locale';
export const DEFAULT_LOCALE: SupportedLocale = 'en';

function getInitialLocale(): SupportedLocale {
    if (typeof window === 'undefined') return DEFAULT_LOCALE;
    
    const saved = localStorage.getItem(STORAGE_KEY) as SupportedLocale;
    if (saved && messages[saved]) {
        return saved;
    }
    
    // Default to English as requested
    return DEFAULT_LOCALE;
}

export const currentLocale = ref<SupportedLocale>(getInitialLocale());

export const activeLanguage = computed(() => {
    return supportedLocales.find(l => l.code === currentLocale.value) || supportedLocales[0];
});

export function setLocale(locale: SupportedLocale) {
    if (!messages[locale]) return;
    
    currentLocale.value = locale;
    
    if (typeof window !== 'undefined') {
        localStorage.setItem(STORAGE_KEY, locale);
        document.documentElement.lang = locale;
        const lang = supportedLocales.find(l => l.code === locale);
        document.documentElement.dir = lang ? lang.dir : 'ltr';
        
        // Also set cookie so backend can read it if needed
        document.cookie = `seovy_locale=${locale};path=/;max-age=31536000;SameSite=Lax`;
    }
}

// Initialize document attributes on load
if (typeof window !== 'undefined') {
    const lang = supportedLocales.find(l => l.code === currentLocale.value);
    document.documentElement.lang = currentLocale.value;
    document.documentElement.dir = lang ? lang.dir : 'ltr';
}

export function t(key: string, params?: Record<string, string | number>, fallback?: string): string {
    const keys = key.split('.');
    
    // 1. Try current locale
    let value = getNestedValue(messages[currentLocale.value], keys);
    
    // 2. Try default English locale as fallback
    if (value === undefined && currentLocale.value !== DEFAULT_LOCALE) {
        value = getNestedValue(messages[DEFAULT_LOCALE], keys);
    }
    
    // 3. Try provided fallback or return key
    if (value === undefined) {
        value = fallback !== undefined ? fallback : key;
    }
    
    if (typeof value !== 'string') {
        return String(value);
    }
    
    // 4. Interpolate params {name}
    if (params) {
        return value.replace(/\{(\w+)\}/g, (_, k) => {
            return params[k] !== undefined ? String(params[k]) : `{${k}}`;
        });
    }
    
    return value;
}

function getNestedValue(obj: any, keys: string[]): any {
    if (!obj) return undefined;
    let curr = obj;
    for (const k of keys) {
        if (curr && typeof curr === 'object' && k in curr) {
            curr = curr[k];
        } else {
            return undefined;
        }
    }
    return curr;
}

export const i18nPlugin = {
    install(app: App) {
        app.config.globalProperties.$t = t;
        app.config.globalProperties.$locale = currentLocale;
        app.config.globalProperties.$setLocale = setLocale;
        app.provide('t', t);
        app.provide('currentLocale', currentLocale);
        app.provide('setLocale', setLocale);
        app.provide('supportedLocales', supportedLocales);
    }
};

export function useI18n() {
    return {
        t,
        locale: currentLocale,
        activeLanguage,
        setLocale,
        supportedLocales
    };
}
