<template>
  <v-dialog v-model="model" max-width="480" persistent>
    <v-card rounded="xl">
      <v-card-title class="pa-6 d-flex align-center">
        <v-icon color="accent" class="mr-2">mdi-cloud-download</v-icon>
        Import Users
      </v-card-title>

      <v-card-text class="px-6 pb-2">
        <p class="text-medium-emphasis mb-4">
          This will fetch <strong>50 users</strong> from
          <a href="https://randomuser.me/api/?results=50" target="_blank" class="text-accent">randomuser.me/api</a>
          and upsert them into your database. Existing records will be updated, new ones created.
        </p>

        <v-alert
          v-if="result"
          :type="result.success ? 'success' : 'error'"
          rounded="lg"
          class="mb-0"
        >
          {{ result.message }}
          <template v-if="result.success && result.data">
            <div class="mt-2 text-caption">
              New: {{ result.data.imported }} &nbsp;|&nbsp;
              Updated: {{ result.data.updated }} &nbsp;|&nbsp;
              Total fetched: {{ result.data.total }}
            </div>
          </template>
        </v-alert>

        <v-progress-linear v-if="loading" indeterminate color="accent" class="mt-4" rounded />
      </v-card-text>

      <v-card-actions class="pa-6 pt-4">
        <v-spacer />
        <v-btn variant="tonal" :disabled="loading" @click="close">
          {{ result ? 'Close' : 'Cancel' }}
        </v-btn>
        <v-btn
          v-if="!result"
          color="accent"
          prepend-icon="mdi-cloud-download"
          :loading="loading"
          @click="doImport"
        >
          Import Now
        </v-btn>
        <v-btn
          v-else-if="result.success"
          color="primary"
          prepend-icon="mdi-refresh"
          @click="reset"
        >
          Import Again
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const model = defineModel({ type: Boolean });
const emit = defineEmits(['imported']);

const loading = ref(false);
const result = ref(null);

const doImport = async () => {
  loading.value = true;
  result.value = null;
  try {
    const res = await axios.post('/import');
    result.value = res.data;
    if (res.data.success) emit('imported');
  } catch (e) {
    result.value = { success: false, message: e.response?.data?.message || 'Import failed.' };
  } finally {
    loading.value = false;
  }
};

const reset = () => {
  result.value = null;
};

const close = () => {
  if (!loading.value) {
    model.value = false;
    result.value = null;
  }
};
</script>
