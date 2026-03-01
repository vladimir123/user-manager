<template>
  <AppLayout title="Users" @open-import="importModal = true">
    <!-- Header -->
    <div class="d-flex align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">Users</h1>
        <p class="text-medium-emphasis">Manage imported users from randomuser.me</p>
      </div>
      <v-spacer />
      <v-btn
        v-if="selected.length"
        color="error"
        prepend-icon="mdi-delete-sweep"
        variant="tonal"
        class="mr-3"
        @click="bulkDeleteDialog = true"
      >
        Delete ({{ selected.length }})
      </v-btn>
      <v-btn color="accent" prepend-icon="mdi-cloud-download" class="mr-3" @click="importModal = true">
        Import
      </v-btn>
      <v-btn color="primary" prepend-icon="mdi-plus" :href="route('users.create')">
        Add User
      </v-btn>
    </div>

    <!-- Search & Stats -->
    <v-row class="mb-4">
      <v-col cols="12" md="5">
        <v-text-field
          v-model="search"
          prepend-inner-icon="mdi-magnify"
          label="Search users..."
          clearable
          hide-details
          @input="debouncedSearch"
          @click:clear="clearSearch"
        />
      </v-col>
      <v-col cols="12" md="7" class="d-flex align-center justify-end">
        <v-chip v-if="selected.length" color="error" variant="tonal" class="mr-2">
          {{ selected.length }} selected
        </v-chip>
        <v-chip color="primary" variant="tonal">
          {{ users.total }} total users
        </v-chip>
      </v-col>
    </v-row>

    <!-- Table -->
    <v-card elevation="0" border>
      <v-data-table-virtual
        :headers="headers"
        :items="users.data"
        item-value="id"
        v-model="selected"
        show-select
        no-data-text="No users found."
        hover
      >
        <!-- Avatar + Name -->
        <template #item.name="{ item }">
          <div class="d-flex align-center py-2">
            <v-avatar size="38" class="mr-3">
              <v-img v-if="item.picture_thumbnail" :src="item.picture_thumbnail" />
              <v-icon v-else icon="mdi-account" />
            </v-avatar>
            <div>
              <div class="font-weight-medium">{{ item.first_name }} {{ item.last_name }}</div>
              <div class="text-caption text-medium-emphasis">@{{ item.username }}</div>
            </div>
          </div>
        </template>

        <!-- Gender chip -->
        <template #item.gender="{ item }">
          <v-chip
            :color="item.gender === 'male' ? 'info' : item.gender === 'female' ? 'secondary' : 'surface-variant'"
            size="small"
            variant="tonal"
          >
            {{ item.gender }}
          </v-chip>
        </template>

        <!-- Nationality -->
        <template #item.nationality="{ item }">
          <v-chip v-if="item.nationality" size="small" variant="outlined">{{ item.nationality }}</v-chip>
          <span v-else class="text-medium-emphasis text-caption">—</span>
        </template>

        <!-- Contact -->
        <template #item.contact="{ item }">
          <div v-if="item.contact">
            <div class="text-caption">{{ item.contact.phone }}</div>
            <div class="text-caption text-medium-emphasis">{{ item.contact.cell }}</div>
          </div>
          <span v-else class="text-medium-emphasis text-caption">—</span>
        </template>

        <!-- Born -->
        <template #item.date_of_birth="{ item }">
          <span v-if="item.date_of_birth">{{ formatDate(item.date_of_birth) }}</span>
          <span v-else class="text-medium-emphasis text-caption">—</span>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" :href="route('users.edit', item.id)" class="mr-1" />
          <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="confirmDelete(item)" />
        </template>
      </v-data-table-virtual>
    </v-card>

    <!-- Pagination -->
    <div class="d-flex justify-center mt-4">
      <v-pagination
        v-if="users.last_page > 1"
        :model-value="users.current_page"
        :length="users.last_page"
        rounded="lg"
        @update:model-value="changePage"
      />
    </div>

    <!-- Single Delete Confirm Dialog -->
    <v-dialog v-model="deleteDialog" max-width="420" persistent>
      <v-card rounded="xl">
        <v-card-title class="pa-6">
          <v-icon color="error" class="mr-2">mdi-alert-circle</v-icon>
          Confirm Delete
        </v-card-title>
        <v-card-text class="px-6 pb-4">
          Are you sure you want to delete <strong>{{ deleteTarget?.first_name }} {{ deleteTarget?.last_name }}</strong>?
          This will also remove their contact and address data.
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer />
          <v-btn variant="tonal" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="error" :loading="deleting" @click="doDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Bulk Delete Confirm Dialog -->
    <v-dialog v-model="bulkDeleteDialog" max-width="440" persistent>
      <v-card rounded="xl">
        <v-card-title class="pa-6">
          <v-icon color="error" class="mr-2">mdi-delete-sweep</v-icon>
          Delete {{ selected.length }} user(s)?
        </v-card-title>
        <v-card-text class="px-6 pb-4">
          This will permanently delete <strong>{{ selected.length }}</strong> selected user(s)
          along with all their contact and address data. This cannot be undone.
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer />
          <v-btn variant="tonal" @click="bulkDeleteDialog = false">Cancel</v-btn>
          <v-btn color="error" :loading="bulkDeleting" @click="doBulkDelete">Delete All</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Import Modal -->
    <ImportModal v-model="importModal" @imported="$inertia.reload()" />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ImportModal from '@/Components/ImportModal.vue';

const props = defineProps({
  users: Object,
  filters: Object,
});

const search         = ref(props.filters?.search || '');
const importModal    = ref(false);
const deleteDialog   = ref(false);
const deleteTarget   = ref(null);
const deleting       = ref(false);
const selected       = ref([]);          // array of selected user IDs
const bulkDeleteDialog = ref(false);
const bulkDeleting   = ref(false);

const headers = [
  { title: 'User', key: 'name', sortable: false, minWidth: '220px' },
  { title: 'Email', key: 'email' },
  { title: 'Gender', key: 'gender', width: '110px' },
  { title: 'Nationality', key: 'nationality', width: '110px' },
  { title: 'Contact', key: 'contact', sortable: false },
  { title: 'Born', key: 'date_of_birth', width: '120px' },
  { title: 'Actions', key: 'actions', sortable: false, width: '100px', align: 'end' },
];

let searchTimeout = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get('/users', { search: search.value }, { preserveState: true, replace: true });
  }, 350);
};

const clearSearch = () => {
  search.value = '';
  router.get('/users', {}, { preserveState: true, replace: true });
};

const changePage = (page) => {
  router.get('/users', { search: search.value, page }, { preserveState: true });
};

// Single delete
const confirmDelete = (user) => {
  deleteTarget.value = user;
  deleteDialog.value = true;
};

const doDelete = () => {
  deleting.value = true;
  router.delete(route('users.destroy', deleteTarget.value.id), {
    onFinish: () => { deleting.value = false; deleteDialog.value = false; },
  });
};

// Bulk delete
const doBulkDelete = () => {
  bulkDeleting.value = true;
  router.delete(route('users.bulk-destroy'), {
    data: { ids: selected.value },
    onSuccess: () => { selected.value = []; bulkDeleteDialog.value = false; },
    onFinish: () => { bulkDeleting.value = false; },
  });
};

const formatDate = (dateStr) => {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  if (isNaN(d)) return dateStr;
  return d.toISOString().slice(0, 10);
};
</script>
