<template>
  <AppLayout title="Create User">
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" :href="route('users.index')" class="mr-3" />
      <div>
        <h1 class="text-h4 font-weight-bold">Create User</h1>
        <p class="text-medium-emphasis">Add a new user manually</p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row>
        <!-- User Info -->
        <v-col cols="12" md="8">
          <v-card elevation="0" border rounded="xl" class="mb-4">
            <v-card-title class="pa-6 pb-2">
              <v-icon class="mr-2" color="primary">mdi-account</v-icon>
              Personal Information
            </v-card-title>
            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" sm="6">
                  <v-text-field v-model="form.first_name" label="First Name" :error-messages="errors.first_name" required />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field v-model="form.last_name" label="Last Name" :error-messages="errors.last_name" required />
                </v-col>
                <v-col cols="12" sm="8">
                  <v-text-field v-model="form.email" label="Email" type="email" :error-messages="errors.email" required />
                </v-col>
                <v-col cols="12" sm="4">
                  <v-text-field v-model="form.username" label="Username" :error-messages="errors.username" />
                </v-col>
                <v-col cols="12" sm="4">
                  <v-select v-model="form.gender" label="Gender" :items="genderOptions" :error-messages="errors.gender" />
                </v-col>
                <v-col cols="12" sm="4">
                  <v-text-field v-model="form.date_of_birth" label="Date of Birth" type="date" :error-messages="errors.date_of_birth" />
                </v-col>
                <v-col cols="12" sm="4">
                  <v-text-field v-model="form.nationality" label="Nationality (2-letter)" maxlength="10" :error-messages="errors.nationality" />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Contact -->
          <v-card elevation="0" border rounded="xl" class="mb-4">
            <v-card-title class="pa-6 pb-2">
              <v-icon class="mr-2" color="secondary">mdi-phone</v-icon>
              Contact Information
            </v-card-title>
            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" sm="6">
                  <v-text-field v-model="form.contact.phone" label="Phone" :error-messages="errors['contact.phone']" />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field v-model="form.contact.cell" label="Cell" :error-messages="errors['contact.cell']" />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Address -->
          <v-card elevation="0" border rounded="xl">
            <v-card-title class="pa-6 pb-2">
              <v-icon class="mr-2" color="accent">mdi-map-marker</v-icon>
              Address
            </v-card-title>
            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" sm="3">
                  <v-text-field v-model="form.address.street_number" label="Street No." :error-messages="errors['address.street_number']" />
                </v-col>
                <v-col cols="12" sm="9">
                  <v-text-field v-model="form.address.street_name" label="Street Name" :error-messages="errors['address.street_name']" />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field v-model="form.address.city" label="City" :error-messages="errors['address.city']" />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field v-model="form.address.state" label="State / Region" :error-messages="errors['address.state']" />
                </v-col>
                <v-col cols="12" sm="4">
                  <v-text-field v-model="form.address.postcode" label="Postcode" :error-messages="errors['address.postcode']" />
                </v-col>
                <v-col cols="12" sm="8">
                  <v-text-field v-model="form.address.country" label="Country" :error-messages="errors['address.country']" />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Actions sidebar -->
        <v-col cols="12" md="4">
          <v-card elevation="0" border rounded="xl">
            <v-card-title class="pa-6 pb-2">Actions</v-card-title>
            <v-card-text class="pa-6 pt-2">
              <v-btn type="submit" color="primary" block size="large" :loading="submitting" class="mb-3">
                <v-icon class="mr-2">mdi-content-save</v-icon>
                Save User
              </v-btn>
              <v-btn variant="tonal" block :href="route('users.index')">Cancel</v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-form>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ errors: { type: Object, default: () => ({}) } });

const genderOptions = ['male', 'female', 'other'];
const submitting = ref(false);

const form = ref({
  first_name: '', last_name: '', email: '', username: '',
  gender: null, date_of_birth: '', nationality: '',
  contact: { phone: '', cell: '' },
  address: { street_number: '', street_name: '', city: '', state: '', postcode: '', country: '' },
});

const submit = () => {
  submitting.value = true;
  router.post(route('users.store'), form.value, {
    onFinish: () => { submitting.value = false; },
  });
};
</script>
