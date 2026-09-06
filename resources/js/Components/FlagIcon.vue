<script setup lang="ts">
defineProps<{
    code: string;
    size?: string;
}>();
</script>

<template>
    <!-- UK / English (en) -->
    <svg
        v-if="code === 'en' || code === 'gb'"
        viewBox="0 0 640 480"
        :class="size || 'w-4 h-4'"
        class="rounded-full object-cover shrink-0 shadow-xs ring-1 ring-white/10 inline-block align-middle"
    >
        <path fill="#012169" d="M0 0h640v480H0z"/>
        <path fill="#FFF" d="m75 0 244 181L562 0h78v62L439 240l201 178v62h-78L320 299 78 480H0v-62l201-178L0 62V0h75z"/>
        <path fill="#C8102E" d="m424 288 216 156v36h-46L378 316l46-28zm-208-96L0 36V0h46l216 160-46 32zM640 0v36L450 178l44 32L640 36V0zM0 480v-36l190-142-44-32L0 444v36z"/>
        <path fill="#FFF" d="M240 0h160v480H240zM0 160h640v160H0z"/>
        <path fill="#C8102E" d="M267 0h106v480H267zM0 187h640v106H0z"/>
    </svg>

    <!-- Turkey (tr) -->
    <svg
        v-else-if="code === 'tr'"
        viewBox="0 0 1200 800"
        :class="size || 'w-4 h-4'"
        class="rounded-full object-cover shrink-0 shadow-xs ring-1 ring-white/10 inline-block align-middle"
    >
        <rect width="1200" height="800" fill="#E30A17"/>
        <circle cx="425" cy="400" r="200" fill="#ffffff"/>
        <circle cx="475" cy="400" r="160" fill="#E30A17"/>
        <polygon points="583,400 706,440 630,335 630,465 706,360" fill="#ffffff"/>
    </svg>

    <!-- Spain (es) -->
    <svg
        v-else-if="code === 'es'"
        viewBox="0 0 750 500"
        :class="size || 'w-4 h-4'"
        class="rounded-full object-cover shrink-0 shadow-xs ring-1 ring-white/10 inline-block align-middle"
    >
        <rect width="750" height="500" fill="#c60b1e"/>
        <rect width="750" height="250" y="125" fill="#ffc400"/>
    </svg>

    <!-- Germany (de) -->
    <svg
        v-else-if="code === 'de'"
        viewBox="0 0 5 3"
        :class="size || 'w-4 h-4'"
        class="rounded-full object-cover shrink-0 shadow-xs ring-1 ring-white/10 inline-block align-middle"
    >
        <rect width="5" height="3" y="0" fill="#000000"/>
        <rect width="5" height="2" y="1" fill="#DD0000"/>
        <rect width="5" height="1" y="2" fill="#FFCE00"/>
    </svg>

    <!-- France (fr) -->
    <svg
        v-else-if="code === 'fr'"
        viewBox="0 0 3 2"
        :class="size || 'w-4 h-4'"
        class="rounded-full object-cover shrink-0 shadow-xs ring-1 ring-white/10 inline-block align-middle"
    >
        <rect width="1" height="2" x="0" fill="#002395"/>
        <rect width="1" height="2" x="1" fill="#ffffff"/>
        <rect width="1" height="2" x="2" fill="#ed2939"/>
    </svg>

    <!-- Italy (it) -->
    <svg
        v-else-if="code === 'it'"
        viewBox="0 0 3 2"
        :class="size || 'w-4 h-4'"
        class="rounded-full object-cover shrink-0 shadow-xs ring-1 ring-white/10 inline-block align-middle"
    >
        <rect width="1" height="2" x="0" fill="#009246"/>
        <rect width="1" height="2" x="1" fill="#ffffff"/>
        <rect width="1" height="2" x="2" fill="#ce2b37"/>
    </svg>

    <!-- Portugal (pt) -->
    <svg
        v-else-if="code === 'pt'"
        viewBox="0 0 600 400"
        :class="size || 'w-4 h-4'"
        class="rounded-full object-cover shrink-0 shadow-xs ring-1 ring-white/10 inline-block align-middle"
    >
        <rect width="600" height="400" fill="#DA291C"/>
        <rect width="240" height="400" fill="#006600"/>
        <circle cx="240" cy="200" r="60" fill="#FFD100" stroke="#000" stroke-width="2"/>
    </svg>

    <!-- Russia (ru) -->
    <svg
        v-else-if="code === 'ru'"
        viewBox="0 0 9 6"
        :class="size || 'w-4 h-4'"
        class="rounded-full object-cover shrink-0 shadow-xs ring-1 ring-white/10 inline-block align-middle"
    >
        <rect width="9" height="6" fill="#ffffff"/>
        <rect width="9" height="4" y="2" fill="#0039A6"/>
        <rect width="9" height="2" y="4" fill="#D52B1E"/>
    </svg>

    <!-- China (zh / cn) -->
    <svg
        v-else-if="code === 'zh' || code === 'cn'"
        viewBox="0 0 900 600"
        :class="size || 'w-4 h-4'"
        class="rounded-full object-cover shrink-0 shadow-xs ring-1 ring-white/10 inline-block align-middle"
    >
        <rect width="900" height="600" fill="#de2910"/>
        <polygon points="150,75 168,131 226,131 179,165 197,221 150,187 103,221 121,165 74,131 132,131" fill="#ffde00"/>
    </svg>

    <!-- Saudi Arabia / Arabic (ar / sa) -->
    <svg
        v-else-if="code === 'ar' || code === 'sa'"
        viewBox="0 0 600 400"
        :class="size || 'w-4 h-4'"
        class="rounded-full object-cover shrink-0 shadow-xs ring-1 ring-white/10 inline-block align-middle"
    >
        <rect width="600" height="400" fill="#006C35"/>
        <circle cx="300" cy="200" r="50" fill="#ffffff" fill-opacity="0.2"/>
        <path d="M150 250 h300 v8 h-300 z" fill="#ffffff"/>
        <polygon points="300,150 315,185 350,185 320,205 330,240 300,220 270,240 280,205 250,185 285,185" fill="#ffffff"/>
    </svg>

    <!-- Fallback Globe -->
    <svg
        v-else
        viewBox="0 0 24 24"
        :class="size || 'w-4 h-4'"
        class="rounded-full object-cover shrink-0 text-indigo-400 inline-block align-middle"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
    >
        <circle cx="12" cy="12" r="10"/>
        <path d="M12 2a14.5 14.5 0 0 0 0 20M2 12h20"/>
    </svg>
</template>
