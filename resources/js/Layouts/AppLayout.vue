<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    Activity,
    LayoutDashboard,
    Globe,
    Search,
    FileText,
    TrendingUp,
    CreditCard,
    Users,
    Shield,
    Menu,
    X,
    ChevronDown,
    ChevronRight,
    LogOut,
    User as UserIcon,
    AlertCircle,
    CheckCircle2,
    Info,
    Sparkles,
    Tag,
    DollarSign
} from 'lucide-vue-next';
import LanguageSelector from '@/Components/LanguageSelector.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { useTheme } from '@/composables/useTheme';
import { useI18n } from '@/i18n';

defineProps<{
    title?: string;
}>();

const page = usePage();
const auth = computed(() => ((page.props as any)?.auth) || {});
const flash = computed(() => ((page.props as any)?.flash) || {});
const errors = computed(() => ((page.props as any)?.errors) || {});
const { t } = useI18n();
const { isDark } = useTheme();

const systemSettings = computed(() => ((page.props as any)?.system_settings) || {});
const systemLogoDark = computed(() => systemSettings.value.logo_dark || systemSettings.value.logo || null);
const systemLogoLight = computed(() => systemSettings.value.logo_light || systemSettings.value.logo || null);
const activeLogo = computed(() => (isDark.value ? systemLogoDark.value : (systemLogoLight.value || systemLogoDark.value)));
const brandName = computed(() => systemSettings.value.brand_name || 'Seovy');
const logoFailed = ref(false);

watch(activeLogo, () => {
    logoFailed.value = false;
});

watch(
    () => systemSettings.value.favicon,
    (newFavicon) => {
        if (newFavicon && typeof document !== 'undefined') {
            const link = document.getElementById('app-favicon') as HTMLLinkElement | null;
            if (link) {
                link.href = newFavicon;
            }
        }
    },
    { immediate: true }
);

const currentPlanName = computed(() => {
    if (auth.value.current_workspace?.plan_name) {
        return auth.value.current_workspace.plan_name;
    }
    const code = auth.value.current_workspace?.subscription?.plan_name || auth.value.current_workspace?.plan_code || 'free';
    if (code === 'free') return t('billing.plan_free_title', 'Ücretsiz Plan');
    if (code === 'pro') return t('billing.plan_pro_title', 'Pro Plan');
    if (code === 'agency') return t('billing.plan_agency_title', 'Ajans Planı');
    return code.charAt(0).toUpperCase() + code.slice(1);
});

const mobileMenuOpen = ref(false);
const workspaceDropdownOpen = ref(false);
const userDropdownOpen = ref(false);

const switchWorkspace = (workspaceId: number) => {
    router.post(`/workspaces/${workspaceId}/switch`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            workspaceDropdownOpen.value = false;
        }
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col md:flex-row antialiased">
        <!-- Sidebar Navigation (Desktop) -->
        <aside class="hidden md:flex flex-col w-64 bg-slate-900/60 border-r border-slate-800/80 p-4 justify-between backdrop-blur-md sticky top-0 h-screen z-30">
            <div class="space-y-6">
                <!-- Brand / Logo & Plan Badge -->
                <div class="px-2 space-y-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <Link href="/dashboard" class="flex items-center space-x-3 group min-w-0 flex-1">
                            <template v-if="activeLogo && !logoFailed">
                                <img
                                    :key="activeLogo"
                                    :src="activeLogo"
                                    :alt="brandName"
                                    class="h-[50px] max-w-[185px] object-contain object-left rounded-lg shadow-sm"
                                    @error="logoFailed = true"
                                    @load="logoFailed = false"
                                />
                            </template>
                            <template v-else>
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform shrink-0">
                                    <Activity class="w-5 h-5 text-white" />
                                </div>
                                <span class="text-xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-200 to-slate-400 dark:from-white dark:via-slate-200 dark:to-slate-400 light:from-slate-900 light:to-slate-700 truncate">
                                    {{ brandName }}
                                </span>
                            </template>
                        </Link>
                        <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 shrink-0 self-start mt-1">
                            {{ auth.current_workspace?.role ?? t('nav.member') }}
                        </span>
                    </div>

                    <!-- Active Subscription Plan Badge directly below the Logo -->
                    <div>
                        <Link
                            href="/billing"
                            class="flex items-center justify-between px-2.5 py-1.5 rounded-xl bg-gradient-to-r from-indigo-950/40 via-purple-950/20 to-slate-900 border border-indigo-500/20 hover:border-indigo-500/50 hover:bg-slate-800/80 transition-all group/plan shadow-sm"
                            :title="t('nav.manage_subscription', 'Aboneliği ve Kotaları Yönet')"
                        >
                            <div class="flex items-center space-x-2 min-w-0">
                                <span class="relative flex h-2 w-2 shrink-0">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[9px] uppercase tracking-wider font-semibold text-slate-400 leading-none mb-0.5">
                                        {{ t('nav.active_plan', 'Aktif Paket') }}
                                    </span>
                                    <span class="text-xs font-bold text-white group-hover/plan:text-indigo-300 transition-colors truncate">
                                        {{ currentPlanName }}
                                    </span>
                                </div>
                            </div>
                            <span class="text-[10px] font-medium text-indigo-400/80 group-hover/plan:text-indigo-300 flex items-center space-x-0.5 shrink-0 ml-1">
                                <span>{{ t('nav.upgrade', 'Yükselt') }}</span>
                                <ChevronRight class="w-3 h-3" />
                            </span>
                        </Link>
                    </div>
                </div>

                <!-- Workspace Switcher Dropdown -->
                <div class="relative px-2">
                    <button
                        @click="workspaceDropdownOpen = !workspaceDropdownOpen"
                        class="w-full flex items-center justify-between px-3 py-2 text-xs font-medium rounded-lg bg-slate-800/60 hover:bg-slate-800 border border-slate-700/60 transition-all text-slate-300"
                    >
                        <span class="truncate">{{ auth.current_workspace?.name ?? t('nav.select_workspace') }}</span>
                        <ChevronDown class="w-3.5 h-3.5 text-slate-400 ml-1 shrink-0" />
                    </button>

                    <!-- Switcher Menu -->
                    <div
                        v-if="workspaceDropdownOpen"
                        class="absolute left-2 right-2 mt-1 py-1 rounded-xl bg-slate-900 border border-slate-800 shadow-2xl z-50 text-xs space-y-0.5"
                    >
                        <div class="px-3 py-1.5 font-semibold text-slate-500 uppercase tracking-wider text-[10px]">
                            {{ t('nav.my_workspaces') }}
                        </div>
                        <button
                            v-for="ws in auth.workspaces"
                            :key="ws.id"
                            @click="switchWorkspace(ws.id)"
                            class="w-full text-left px-3 py-2 hover:bg-indigo-600/20 hover:text-indigo-300 transition-colors flex items-center justify-between"
                            :class="{'text-indigo-400 font-semibold bg-indigo-500/10': ws.id === auth.current_workspace?.id}"
                        >
                            <span class="truncate">{{ ws.name }}</span>
                            <span class="text-[10px] text-slate-500 capitalize">{{ ws.role }}</span>
                        </button>
                        <div class="border-t border-slate-800 my-1"></div>
                        <Link
                            href="/workspaces"
                            class="block px-3 py-1.5 text-indigo-400 hover:text-indigo-300 font-medium"
                            @click="workspaceDropdownOpen = false"
                        >
                            {{ t('nav.new_workspace') }}
                        </Link>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="space-y-1">
                    <Link
                        href="/dashboard"
                        class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
                        :class="$page.url.startsWith('/dashboard') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/40'"
                    >
                        <LayoutDashboard class="w-4 h-4" />
                        <span>{{ t('nav.dashboard') }}</span>
                    </Link>

                    <Link
                        href="/projects"
                        class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
                        :class="$page.url.startsWith('/projects') && !$page.url.includes('/keywords') && !$page.url.includes('/reports') && !$page.url.includes('/integrations') && !$page.url.includes('/tasks') && !$page.url.includes('/on-page') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/40'"
                    >
                        <Globe class="w-4 h-4" />
                        <span>{{ t('nav.websites') }}</span>
                    </Link>

                    <div class="pt-2 pb-1 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        {{ t('nav.seo_workspace') }}
                    </div>

                    <Link
                        v-if="auth.current_workspace"
                        :href="`/projects`"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all text-slate-400 hover:text-white hover:bg-slate-800/40"
                    >
                        <Search class="w-4 h-4 text-violet-400" />
                        <span>{{ t('nav.technical_crawler') }}</span>
                    </Link>

                    <Link
                        href="/projects"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all text-slate-400 hover:text-white hover:bg-slate-800/40"
                    >
                        <FileText class="w-4 h-4 text-cyan-400" />
                        <span>{{ t('nav.onpage_serp') }}</span>
                    </Link>

                    <Link
                        href="/projects"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all text-slate-400 hover:text-white hover:bg-slate-800/40"
                    >
                        <TrendingUp class="w-4 h-4 text-emerald-400" />
                        <span>{{ t('nav.rank_keywords') }}</span>
                    </Link>

                    <div class="pt-2 pb-1 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        {{ t('nav.management_report') }}
                    </div>

                    <Link
                        v-if="auth.current_workspace"
                        :href="`/workspaces/${auth.current_workspace.id}/members`"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all"
                        :class="$page.url.includes('/members') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/40'"
                    >
                        <Users class="w-4 h-4" />
                        <span>{{ t('nav.team_roles') }}</span>
                    </Link>

                    <Link
                        href="/billing"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all"
                        :class="$page.url.startsWith('/billing') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/40'"
                    >
                        <CreditCard class="w-4 h-4 text-amber-400" />
                        <span>{{ t('nav.plan_billing') }}</span>
                    </Link>

                    <!-- Platform Admin Links -->
                    <div v-if="auth.user?.is_platform_admin" class="space-y-1 pt-1">
                        <Link
                            href="/admin"
                            class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all"
                            :class="$page.url === '/admin' || ($page.url.startsWith('/admin') && !$page.url.includes('payments')) ? 'bg-pink-600/20 text-pink-300 border border-pink-500/40' : 'text-pink-400 hover:text-pink-300 hover:bg-pink-500/10 border border-pink-500/20'"
                        >
                            <Shield class="w-4 h-4" />
                            <span>{{ t('nav.system_admin') }}</span>
                        </Link>
                        <Link
                            href="/admin?tab=payments"
                            class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all"
                            :class="$page.url.includes('tab=payments') || $page.url.includes('/admin/payments') ? 'bg-emerald-600/20 text-emerald-300 border border-emerald-500/40' : 'text-emerald-400 hover:text-emerald-300 hover:bg-emerald-500/10 border border-emerald-500/20'"
                        >
                            <CreditCard class="w-4 h-4" />
                            <span>{{ t('admin.virtual_pos_gateways') }}</span>
                        </Link>
                        <Link
                            href="/admin?tab=plans"
                            class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all"
                            :class="$page.url.includes('tab=plans') || $page.url.includes('/admin/plans') ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/40' : 'text-cyan-400 hover:text-cyan-300 hover:bg-cyan-500/10 border border-cyan-500/20'"
                        >
                            <Tag class="w-4 h-4" />
                            <span>{{ t('admin.tab_plans') }}</span>
                        </Link>
                        <Link
                            href="/admin?tab=sales"
                            class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all"
                            :class="$page.url.includes('tab=sales') || $page.url.includes('/admin/sales') ? 'bg-amber-600/20 text-amber-300 border border-amber-500/40' : 'text-amber-400 hover:text-amber-300 hover:bg-amber-500/10 border border-amber-500/20'"
                        >
                            <DollarSign class="w-4 h-4" />
                            <span>{{ t('admin.tab_sales') }}</span>
                        </Link>
                    </div>
                </nav>
            </div>

            <!-- Footer: Language Selector & User Profile -->
            <div class="border-t border-slate-800/80 pt-3 px-2 space-y-2 relative">
                <!-- Language Selector & Theme Toggle -->
                <div class="flex items-center space-x-2">
                    <div class="flex-1 min-w-0">
                        <LanguageSelector placement="top" />
                    </div>
                    <ThemeToggle />
                </div>

                <!-- User Profile Button -->
                <button
                    @click="userDropdownOpen = !userDropdownOpen"
                    class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-slate-800/60 transition-colors text-left"
                >
                    <div class="flex items-center space-x-2.5 truncate">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-violet-600 flex items-center justify-center text-xs font-bold text-white shrink-0">
                            {{ auth.user?.name ? auth.user.name.charAt(0).toUpperCase() : 'U' }}
                        </div>
                        <div class="truncate">
                            <div class="text-xs font-medium text-white truncate">{{ auth.user?.name }}</div>
                            <div class="text-[11px] text-slate-500 truncate">{{ auth.user?.email }}</div>
                        </div>
                    </div>
                    <ChevronDown class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                </button>

                <!-- User Dropdown Menu -->
                <div
                    v-if="userDropdownOpen"
                    class="absolute bottom-16 left-2 right-2 py-1 rounded-xl bg-slate-900 border border-slate-800 shadow-2xl z-50 text-xs"
                >
                    <Link
                        href="/profile"
                        class="flex items-center space-x-2 px-3 py-2 hover:bg-slate-800 text-slate-300 hover:text-white transition-colors"
                        @click="userDropdownOpen = false"
                    >
                        <UserIcon class="w-3.5 h-3.5" />
                        <span>{{ t('nav.profile_settings') }}</span>
                    </Link>
                    <div class="border-t border-slate-800 my-1"></div>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="w-full text-left flex items-center space-x-2 px-3 py-2 hover:bg-rose-500/10 text-rose-400 transition-colors"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                        <span>{{ t('nav.logout') }}</span>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Mobile Top Bar -->
            <header class="md:hidden flex items-center justify-between px-4 py-3 bg-slate-900/80 border-b border-slate-800 sticky top-0 z-40">
                <Link href="/dashboard" class="flex items-center space-x-2 min-w-0">
                    <template v-if="activeLogo && !logoFailed">
                        <img
                            :key="activeLogo"
                            :src="activeLogo"
                            :alt="brandName"
                            class="h-7 max-w-[110px] object-contain rounded"
                            @error="logoFailed = true"
                            @load="logoFailed = false"
                        />
                    </template>
                    <template v-else>
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center shrink-0">
                            <Activity class="w-4 h-4 text-white" />
                        </div>
                        <span class="font-bold text-lg text-white truncate">{{ brandName }}</span>
                    </template>
                </Link>

                <div class="flex items-center space-x-2">
                    <ThemeToggle compact />
                    <LanguageSelector compact placement="bottom" />
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="p-2 rounded-lg bg-slate-800 text-slate-400 hover:text-white"
                    >
                        <Menu v-if="!mobileMenuOpen" class="w-5 h-5" />
                        <X v-else class="w-5 h-5" />
                    </button>
                </div>
            </header>

            <!-- Mobile Menu Drawer -->
            <div
                v-if="mobileMenuOpen"
                class="md:hidden bg-slate-900/95 border-b border-slate-800 p-4 space-y-3 z-30"
            >
                <!-- Mobile Plan Badge -->
                <Link
                    href="/billing"
                    class="flex items-center justify-between px-3 py-2 rounded-xl bg-indigo-950/40 border border-indigo-500/20 text-xs text-white"
                    @click="mobileMenuOpen = false"
                >
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span class="text-slate-400">{{ t('nav.active_plan', 'Aktif Paket') }}:</span>
                        <span class="font-bold text-indigo-300">{{ currentPlanName }}</span>
                    </div>
                    <span class="text-[10px] text-indigo-400 font-semibold flex items-center space-x-0.5">
                        <span>{{ t('nav.upgrade', 'Yükselt') }}</span>
                        <ChevronRight class="w-3 h-3" />
                    </span>
                </Link>

                <nav class="space-y-1">
                    <Link
                        href="/dashboard"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800"
                        @click="mobileMenuOpen = false"
                    >
                        <LayoutDashboard class="w-4 h-4" />
                        <span>{{ t('nav.dashboard') }}</span>
                    </Link>
                    <Link
                        href="/projects"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800"
                        @click="mobileMenuOpen = false"
                    >
                        <Globe class="w-4 h-4" />
                        <span>{{ t('nav.websites') }}</span>
                    </Link>
                    <Link
                        href="/billing"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800"
                        @click="mobileMenuOpen = false"
                    >
                        <CreditCard class="w-4 h-4" />
                        <span>{{ t('nav.plan_billing') }}</span>
                    </Link>
                    <Link
                        href="/profile"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800"
                        @click="mobileMenuOpen = false"
                    >
                        <UserIcon class="w-4 h-4" />
                        <span>{{ t('nav.profile_settings') }}</span>
                    </Link>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="w-full text-left flex items-center space-x-3 px-3 py-2 rounded-xl text-sm font-medium text-rose-400 hover:bg-rose-500/10"
                        @click="mobileMenuOpen = false"
                    >
                        <LogOut class="w-4 h-4" />
                        <span>{{ t('nav.logout') }}</span>
                    </Link>
                </nav>
            </div>

            <!-- Flash Notifications -->
            <div v-if="flash?.success || flash?.error || flash?.info || errors?.error" class="p-4 pb-0 max-w-7xl mx-auto w-full">
                <div
                    v-if="flash?.success"
                    class="flex items-center space-x-3 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm"
                >
                    <CheckCircle2 class="w-4 h-4 shrink-0" />
                    <span>{{ flash.success }}</span>
                </div>
                <div
                    v-if="flash?.error || errors?.error"
                    class="flex items-center space-x-3 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm"
                >
                    <AlertCircle class="w-4 h-4 shrink-0" />
                    <span>{{ flash?.error || errors?.error }}</span>
                </div>
                <div
                    v-if="flash?.info"
                    class="flex items-center space-x-3 p-3.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-sm"
                >
                    <Info class="w-4 h-4 shrink-0" />
                    <span>{{ flash.info }}</span>
                </div>
            </div>

            <!-- Page Body -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
                <slot />
            </main>
        </div>
    </div>
</template>
