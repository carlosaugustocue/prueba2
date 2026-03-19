<script setup>
import { computed, defineComponent, h, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    currentRoute: { type: String, required: true },
    user:         { type: Object, default: null },
    badges:       { type: Object, default: () => ({}) },
});

const PRIMARY = '#0F6E56';

// Exact match for /create and /edit routes; prefix match (with trailing slash)
// for everything else — prevents /affiliates matching /affiliates/create.
const isActive = (href) => {
    if (href === '/') return props.currentRoute === '/';
    if (href.endsWith('/create') || href.endsWith('/edit')) {
        return props.currentRoute === href;
    }
    return props.currentRoute === href || props.currentRoute.startsWith(href + '/');
};

const navItemClass = (active) =>
    active
        ? 'bg-[#E1F5EE] text-[#0F6E56] ring-1 ring-[#BFE8DB]'
        : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900';

const iconClass = (active) =>
    active ? 'text-[#0F6E56]' : 'text-gray-400 group-hover:text-gray-600';

const ariaCurrent = (active) => (active ? 'page' : undefined);

const formatBadge = (kind, value) => {
    if (value === null || value === undefined) return null;
    const n = Number(value);
    if (!Number.isFinite(n)) return String(value);
    if (kind === 'pending') return `${n} pend.`;
    if (kind === 'total')   return new Intl.NumberFormat('es-CO').format(n);
    // 'count': cap at 99 to avoid pill overflow on mobile
    return n > 99 ? '99+' : String(n);
};

const sections = computed(() => {
    const isAdmin = props.user?.role === 'admin';

    const all = [
        {
            label: 'GENERAL',
            items: [
                { key: 'dashboard', label: 'Resumen general', href: '/dashboard', icon: 'home' },
            ],
        },
        {
            label: 'AFILIADOS',
            items: [
                {
                    key: 'affiliates',
                    label: 'Mis afiliados',
                    href: '/affiliates',
                    icon: 'users',
                    badge: props.badges?.affiliates_total ?? null,
                    badgeKind: 'total',
                },
                {
                    key: 'affiliates_create',
                    label: 'Registrar afiliado',
                    href: '/affiliates/create',
                    icon: 'user_plus',
                },
            ],
        },
        {
            label: 'SEGURIDAD SOCIAL (PILA)',
            items: [
                { key: 'pila_affiliations', label: 'Afiliaciones PILA', href: '/pila/affiliations', icon: 'shield_check' },
                { key: 'pila_employers',    label: 'Empleadores',       href: '/pila/employers',    icon: 'building'      },
                { key: 'payers',            label: 'Pagadores',         href: '/payers',            icon: 'id_card'       },
                {
                    key: 'payrolls',
                    label: 'Planillas',
                    href: '/payrolls',
                    icon: 'file_money',
                    badge: props.badges?.payrolls_pending ?? null,
                    badgeKind: 'pending',
                },
            ],
        },
        {
            label: 'ATENCIÓN',
            items: [
                {
                    key: 'appointment_requests',
                    label: 'Solicitudes',
                    href: '/appointment-requests',
                    icon: 'inbox',
                    badge: props.badges?.appointment_requests_pending ?? null,
                    badgeKind: 'count',
                },
                { key: 'appointments', label: 'Citas', href: '/appointments', icon: 'calendar' },
                { key: 'authorizations', label: 'Autorizaciones', href: '/authorizations', icon: 'file_check' },
            ],
        },
        {
            label: 'SISTEMA',
            items: [
                ...(isAdmin
                    ? [
                          { key: 'admin_config', label: 'Administración', href: '/admin/configuracion', icon: 'settings' },
                          { key: 'admin_users', label: 'Usuarios', href: '/admin/usuarios', icon: 'users' },
                          { key: 'admin_metrics', label: 'Métricas', href: '/admin/metricas/operadores', icon: 'chart' },
                          { key: 'admin_comms', label: 'Comunicaciones', href: '/admin/comunicaciones', icon: 'chat' },
                          { key: 'admin_whatsapp', label: 'Envíos WhatsApp', href: '/admin/whatsapp-envios', icon: 'whatsapp' },
                      ]
                    : []),
            ],
        },
    ];

    return all.filter((s) => s.items.length > 0);
});

// ─── Collapsible sections (desktop) ─────────────────────────────────────
const STORAGE_KEY = 'serviconli.sidebar.openSection';

const activeSectionLabel = computed(() => {
    for (const section of sections.value) {
        if (section.items.some((it) => isActive(it.href))) return section.label;
    }
    return sections.value?.[0]?.label ?? null;
});

const openSection = ref(null);

const setOpenSection = (label) => {
    openSection.value = openSection.value === label ? null : label;
    try {
        if (openSection.value) localStorage.setItem(STORAGE_KEY, openSection.value);
        else localStorage.removeItem(STORAGE_KEY);
    } catch (_) {}
};

try {
    const saved = localStorage.getItem(STORAGE_KEY);
    openSection.value = saved || activeSectionLabel.value;
} catch (_) {
    openSection.value = activeSectionLabel.value;
}

watch(
    () => props.currentRoute,
    () => {
        if (!activeSectionLabel.value) return;
        if (openSection.value === null) openSection.value = activeSectionLabel.value;
    }
);

const bottomNav = computed(() => [
    { key: 'dashboard',            label: 'Inicio',     href: '/dashboard',             icon: 'home',         badge: null },
    { key: 'affiliates',           label: 'Afiliados',  href: '/affiliates',            icon: 'users',        badge: null },
    { key: 'payrolls',             label: 'Planillas',  href: '/payrolls',              icon: 'file_money',   badge: props.badges?.payrolls_pending ?? null },
    { key: 'appointment_requests', label: 'Solicitudes',href: '/appointment-requests',  icon: 'inbox',        badge: props.badges?.appointment_requests_pending ?? null },
]);

// ─── SVG icon component (render function, runtime-safe) ───────────────
const SvgIcon = defineComponent({
    name: 'SvgIcon',
    props: {
        name: { type: String, required: true },
        active: { type: Boolean, default: false },
    },
    setup(p) {
        const stroke = computed(() => (p.active ? PRIMARY : 'currentColor'));

        const svg = (children) =>
            h(
                'svg',
                {
                    viewBox: '0 0 24 24',
                    class: 'w-5 h-5',
                    fill: 'none',
                    stroke: stroke.value,
                    'stroke-width': '1.8',
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                },
                children
            );

        return () => {
            switch (p.name) {
                case 'home':
                    return svg([
                        h('path', { d: 'M3 10.5L12 3l9 7.5' }),
                        h('path', { d: 'M5 9.8V21h14V9.8' }),
                        h('path', { d: 'M9.5 21v-7h5v7' }),
                    ]);
                case 'users':
                    return svg([
                        h('circle', { cx: '9', cy: '7', r: '4' }),
                        h('path', { d: 'M3 21c0-3.3 2.7-6 6-6s6 2.7 6 6' }),
                        h('path', { d: 'M16 3.1a4 4 0 0 1 0 7.8' }),
                        h('path', { d: 'M21 21c0-2.9-1.9-5.4-4.6-6.3' }),
                    ]);
                case 'user_plus':
                    return svg([
                        h('circle', { cx: '10', cy: '7', r: '4' }),
                        h('path', { d: 'M3 21c0-3.3 2.7-6 7-6s7 2.7 7 6' }),
                        h('path', { d: 'M19 8v6' }),
                        h('path', { d: 'M16 11h6' }),
                    ]);
                case 'shield_check':
                    return svg([
                        h('path', { d: 'M12 2l8 4v6c0 5-3.2 9.3-8 10C7.2 21.3 4 17 4 12V6l8-4z' }),
                        h('path', { d: 'M8.5 12.2l2.4 2.4 4.7-4.7' }),
                    ]);
                case 'building':
                    return svg([
                        h('rect', { x: '2', y: '7', width: '20', height: '15', rx: '1' }),
                        h('path', { d: 'M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2' }),
                        h('path', { d: 'M12 12v4' }),
                        h('path', { d: 'M8 12h8' }),
                    ]);
                case 'id_card':
                    return svg([
                        h('rect', { x: '2', y: '6', width: '20', height: '12', rx: '2' }),
                        h('circle', { cx: '8', cy: '12', r: '2' }),
                        h('path', { d: 'M14 10h5' }),
                        h('path', { d: 'M14 14h4' }),
                    ]);
                case 'file_money':
                    return svg([
                        h('path', { d: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z' }),
                        h('path', { d: 'M14 2v6h6' }),
                        h('path', { d: 'M8 13h8' }),
                        h('path', { d: 'M9 17h6' }),
                        h('path', { d: 'M9 9h1' }),
                    ]);
                case 'inbox':
                    return svg([
                        h('polyline', { points: '22 12 16 12 14 15 10 15 8 12 2 12' }),
                        h('path', { d: 'M5.5 5.1L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2A2 2 0 0 0 5.5 5.1z' }),
                    ]);
                case 'calendar':
                    return svg([
                        h('rect', { x: '3', y: '5', width: '18', height: '16', rx: '2' }),
                        h('path', { d: 'M16 3v4' }),
                        h('path', { d: 'M8 3v4' }),
                        h('path', { d: 'M3 11h18' }),
                        h('path', { d: 'M8 15h.01' }),
                        h('path', { d: 'M12 15h.01' }),
                        h('path', { d: 'M16 15h.01' }),
                    ]);
                case 'file_check':
                    return svg([
                        h('path', { d: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z' }),
                        h('path', { d: 'M14 2v6h6' }),
                        h('path', { d: 'M9 14l2 2 4-4' }),
                    ]);
                case 'chart':
                    return svg([
                        h('path', { d: 'M4 19V5' }),
                        h('path', { d: 'M4 19h16' }),
                        h('path', { d: 'M8 17v-6' }),
                        h('path', { d: 'M12 17V7' }),
                        h('path', { d: 'M16 17v-9' }),
                    ]);
                case 'chat':
                    return svg([
                        h('path', { d: 'M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z' }),
                        h('path', { d: 'M8 9h8' }),
                        h('path', { d: 'M8 13h6' }),
                    ]);
                case 'whatsapp':
                    return svg([
                        h('path', { d: 'M20 11.5a8.5 8.5 0 1 1-15.5 5.1L4 21l4.6-1.5A8.5 8.5 0 0 1 20 11.5z' }),
                        h('path', { d: 'M9.2 8.8c.2-.4.4-.4.6-.4h.5c.2 0 .4.1.5.4l.7 1.7c.1.3.1.6-.1.8l-.4.5c-.1.1-.1.3 0 .4.4.8 1.3 1.6 2.2 2 .1.1.3 0 .4-.1l.6-.4c.2-.2.5-.2.8-.1l1.7.7c.3.1.4.3.4.5v.5c0 .2 0 .4-.4.6-.4.2-1.2.5-2.3.2-1.1-.3-2.6-1-3.9-2.3-1.3-1.3-2-2.8-2.3-3.9-.3-1.1 0-1.9.2-2.3z' }),
                    ]);
                case 'settings':
                    return svg([
                        h('circle', { cx: '12', cy: '12', r: '3' }),
                        h('path', { d: 'M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z' }),
                    ]);
                default:
                    return svg([h('circle', { cx: '12', cy: '12', r: '10' }), h('path', { d: 'M12 8v4l3 3' })]);
            }
        };
    },
});
</script>

<template>
    <!-- ═══════════════════════════════════════════════════
         DESKTOP SIDEBAR
         h-screen + flex-col + min-h-0 on the nav wrapper
         ensures the scrollable area never overflows and
         all sections (including Atención / Sistema) are
         always reachable via scroll.
    ════════════════════════════════════════════════════ -->
    <aside
        class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-40 lg:flex lg:w-64 lg:flex-col"
        aria-label="Barra lateral Serviconli"
    >
        <!-- Outer shell: full height, white background, right border -->
        <div class="flex flex-col h-full bg-white border-r border-gray-200">

            <!-- ── Logo / brand ─────────────────────────── -->
            <Link
                href="/dashboard"
                class="flex items-center gap-3 h-16 px-5 border-b border-gray-200 shrink-0
                       hover:bg-gray-50 transition-colors
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0F6E56] focus-visible:ring-inset"
            >
                <div class="h-9 w-9 rounded-xl bg-[#0F6E56] flex items-center justify-center shrink-0">
                    <span class="text-white font-bold text-base leading-none">S</span>
                </div>
                <div class="leading-tight min-w-0">
                    <div class="text-base font-extrabold text-gray-900 truncate">Serviconli</div>
                    <div class="text-[11px] text-gray-500 truncate">Operación diaria</div>
                </div>
            </Link>

            <!-- ── Nav: min-h-0 + flex-1 + overflow-y-auto ─
                 min-h-0 is the key fix: inside a flex-col
                 container a child with flex-1 alone can still
                 overflow the parent. min-h-0 resets the
                 implicit min-height so overflow-y-auto kicks in.
            ─────────────────────────────────────────────── -->
            <nav
                class="flex-1 min-h-0 overflow-y-auto px-3 py-3"
                aria-label="Navegación principal"
            >
                <template v-for="(section, idx) in sections" :key="section.label">
                    <!-- Section header (collapsible) -->
                    <button
                        type="button"
                        class="w-full flex items-center gap-2 px-2 py-2 rounded-lg
                               hover:bg-gray-50 transition-colors
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0F6E56] focus-visible:ring-offset-1"
                        :class="idx === 0 ? 'mb-1' : 'mt-3 mb-1'"
                        @click="setOpenSection(section.label)"
                        :aria-expanded="openSection === section.label"
                        :aria-controls="`section-${idx}`"
                    >
                        <span class="text-[10px] font-bold tracking-widest text-gray-400 uppercase whitespace-nowrap">
                            {{ section.label }}
                        </span>
                        <div class="h-px bg-gray-100 flex-1" />
                        <svg
                            viewBox="0 0 24 24"
                            class="w-4 h-4 text-gray-400 transition-transform"
                            :class="openSection === section.label ? 'rotate-180' : ''"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M6 9l6 6 6-6" />
                        </svg>
                    </button>

                    <!-- Nav items -->
                    <div v-show="openSection === section.label" :id="`section-${idx}`" class="space-y-0.5">
                        <Link
                            v-for="item in section.items"
                            :key="item.key"
                            :href="item.href"
                            class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium
                                   transition-colors duration-150
                                   focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0F6E56] focus-visible:ring-offset-1"
                            :class="navItemClass(isActive(item.href))"
                            :aria-current="ariaCurrent(isActive(item.href))"
                        >
                            <!-- Icon wrapper — fixed 20 × 20 so text never shifts -->
                            <span class="shrink-0 w-5 h-5 flex items-center justify-center" :class="iconClass(isActive(item.href))">
                                <SvgIcon :name="item.icon" :active="isActive(item.href)" />
                            </span>

                            <!-- Label -->
                            <span class="flex-1 truncate">{{ item.label }}</span>

                            <!-- Badge -->
                            <span
                                v-if="item.badge !== null && item.badge !== undefined"
                                class="shrink-0 h-5 min-w-[36px] px-2 inline-flex items-center justify-center
                                       rounded-full text-[11px] font-semibold"
                                :class="
                                    item.badgeKind === 'total'
                                        ? 'bg-gray-100 text-gray-600'
                                        : isActive(item.href)
                                            ? 'bg-white text-[#0F6E56] ring-1 ring-[#BFE8DB]'
                                            : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
                                "
                            >
                                {{ formatBadge(item.badgeKind, item.badge) }}
                            </span>
                        </Link>
                    </div>
                </template>

                <!-- Bottom spacer so last item isn't flush with the footer -->
                <div class="h-4" />
            </nav>

            <!-- ── Footer: user info + logout ──────────── -->
            <footer class="shrink-0 p-3 border-t border-gray-200 bg-white">
                <div class="flex items-center gap-3">
                    <!-- Avatar -->
                    <div
                        class="h-9 w-9 rounded-full bg-[#E1F5EE] flex items-center justify-center
                               text-[#0F6E56] text-sm font-bold shrink-0"
                        aria-hidden="true"
                    >
                        {{ user?.name?.charAt(0)?.toUpperCase() || 'U' }}
                    </div>

                    <!-- Name + role -->
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-gray-900 truncate">
                            {{ user?.name || 'Usuario' }}
                        </div>
                        <div class="text-xs text-gray-500 truncate capitalize">
                            {{ user?.role || '' }}
                        </div>
                    </div>

                    <!-- Logout button -->
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="shrink-0 inline-flex items-center justify-center h-9 w-9 rounded-lg border border-gray-200
                               text-gray-400 transition-colors duration-150
                               hover:text-red-600 hover:border-red-200 hover:bg-red-50
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-1"
                        aria-label="Cerrar sesión"
                        title="Cerrar sesión"
                        @click.prevent="$event.currentTarget.closest('form')?.submit() ?? $event.currentTarget.click()"
                    >
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none"
                             stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </Link>
                </div>
            </footer>
        </div>
    </aside>

    <!-- ═══════════════════════════════════════════════════
         MOBILE BOTTOM NAVIGATION
         4-column grid, badges as absolute red dots.
    ════════════════════════════════════════════════════ -->
    <nav
        class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-gray-200"
        aria-label="Navegación inferior"
    >
        <div class="grid grid-cols-4 h-16">
            <Link
                v-for="item in bottomNav"
                :key="item.key"
                :href="item.href"
                class="relative flex flex-col items-center justify-center gap-1 px-1
                       transition-colors duration-150
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0F6E56] focus-visible:ring-inset"
                :class="isActive(item.href) ? 'text-[#0F6E56]' : 'text-gray-400'"
                :aria-current="ariaCurrent(isActive(item.href))"
                :aria-label="item.label"
            >
                <!-- Active indicator pill -->
                <span
                    v-if="isActive(item.href)"
                    class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-0.5 rounded-b-full bg-[#0F6E56]"
                    aria-hidden="true"
                />

                <SvgIcon :name="item.icon" :active="isActive(item.href)" />

                <span class="text-[10px] font-semibold leading-none">{{ item.label }}</span>

                <!-- Notification dot -->
                <span
                    v-if="item.badge !== null && item.badge !== undefined"
                    class="absolute top-2 right-[18%] min-w-[16px] h-4 px-1
                           rounded-full text-[9px] font-extrabold
                           flex items-center justify-center
                           bg-red-500 text-white"
                    aria-hidden="true"
                >
                    {{ Number(item.badge) > 99 ? '99+' : item.badge }}
                </span>
            </Link>
        </div>
    </nav>
</template>