<template>
  <v-app :theme="theme">
    <v-navigation-drawer v-model="drawer" :rail="rail" permanent color="surface">
      <v-list-item
        prepend-icon="mdi-account-group"
        title="User Manager"
        nav
        class="py-4"
      />

      <v-divider />

      <v-list density="compact" nav class="mt-2">
        <v-list-item
          prepend-icon="mdi-account-multiple"
          title="Users"
          :href="route('users.index')"
          rounded="lg"
          color="primary"
          :active="$page.url.startsWith('/users')"
        />
        <v-list-item
          prepend-icon="mdi-cloud-download"
          title="Import Data"
          rounded="lg"
          color="accent"
          @click="$emit('open-import')"
        />
      </v-list>
    </v-navigation-drawer>

    <v-app-bar flat color="surface" border="b">
      <template #prepend>
        <v-btn
          :icon="rail ? 'mdi-menu' : 'mdi-chevron-left'"
          variant="text"
          @click="rail = !rail"
        />
      </template>
      <v-app-bar-title>
        <span class="text-h6 font-weight-bold text-primary">{{ title || $page.props.app_name || 'User Manager' }}</span>
      </v-app-bar-title>
      <template #append>
        <v-btn
          :icon="theme === 'dark' ? 'mdi-weather-sunny' : 'mdi-weather-night'"
          variant="text"
          @click="toggleTheme"
        />
      </template>
    </v-app-bar>

    <v-main>
      <v-container fluid class="pa-6">
        <v-snackbar
          v-model="snackbar.show"
          :color="snackbar.color"
          :timeout="4000"
          location="top right"
          rounded="lg"
        >
          <v-icon class="mr-2">{{ snackbar.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
          {{ snackbar.text }}
        </v-snackbar>

        <slot />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({ title: String });
defineEmits(['open-import']);

const page = usePage();
const drawer = ref(true);
const rail = ref(false);
const theme = ref('dark');

const snackbar = ref({ show: false, text: '', color: 'success' });

const toggleTheme = () => {
  theme.value = theme.value === 'dark' ? 'light' : 'dark';
};

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) {
      snackbar.value = { show: true, text: flash.success, color: 'success' };
    } else if (flash?.error) {
      snackbar.value = { show: true, text: flash.error, color: 'error' };
    }
  },
  { deep: true, immediate: true }
);
</script>
