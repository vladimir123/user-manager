import './bootstrap';
import { createApp, h } from 'vue';
import { route, ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';

const vuetify = createVuetify({
    components,
    directives,
    theme: {
        defaultTheme: 'dark',
        themes: {
            dark: {
                dark: true,
                colors: {
                    primary: '#6366F1',
                    secondary: '#8B5CF6',
                    accent: '#06B6D4',
                    background: '#0F172A',
                    surface: '#1E293B',
                    'surface-variant': '#334155',
                    success: '#10B981',
                    warning: '#F59E0B',
                    error: '#EF4444',
                    info: '#3B82F6',
                },
            },
            light: {
                dark: false,
                colors: {
                    primary: '#6366F1',
                    secondary: '#8B5CF6',
                    accent: '#06B6D4',
                    background: '#F8FAFC',
                    surface: '#FFFFFF',
                    success: '#10B981',
                    warning: '#F59E0B',
                    error: '#EF4444',
                    info: '#3B82F6',
                },
            },
        },
    },
    defaults: {
        VBtn: { variant: 'elevated', rounded: 'lg' },
        VCard: { rounded: 'xl' },
        VTextField: { variant: 'outlined', density: 'comfortable' },
        VSelect: { variant: 'outlined', density: 'comfortable' },
        VTextarea: { variant: 'outlined', density: 'comfortable' },
    },
});

createInertiaApp({
    title: (title) => `${title} — User Manager`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.use(vuetify);
        app.use(ZiggyVue);
        app.mount(el);
    },
    progress: {
        color: '#6366F1',
    },
});
