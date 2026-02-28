<template>
  <AppLayout :title="`Edit — ${user.first_name} ${user.last_name}`">
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" :href="route('users.index')" class="mr-3" />
      <v-avatar size="52" class="mr-4">
        <v-img v-if="user.picture_large" :src="user.picture_large" />
        <v-icon v-else size="32">mdi-account</v-icon>
      </v-avatar>
      <div>
        <h1 class="text-h4 font-weight-bold">{{ user.first_name }} {{ user.last_name }}</h1>
        <p class="text-medium-emphasis">@{{ user.username }} · {{ user.email }}</p>
      </div>
    </div>

    <v-form @submit.prevent="submit">
      <v-row>
        <v-col cols="12" md="8">
          <v-tabs v-model="tab" color="primary" class="mb-4">
            <v-tab value="personal">
              <v-icon class="mr-2">mdi-account</v-icon>Personal
            </v-tab>
            <v-tab value="contact">
              <v-icon class="mr-2">mdi-phone</v-icon>Contact
            </v-tab>
            <v-tab value="address">
              <v-icon class="mr-2">mdi-map-marker</v-icon>Address
            </v-tab>
          </v-tabs>

          <v-tabs-window v-model="tab">
            <!-- Personal Tab -->
            <v-tabs-window-item value="personal">
              <v-card elevation="0" border rounded="xl">
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
                      <v-text-field v-model="form.nationality" label="Nationality" maxlength="10" :error-messages="errors.nationality" />
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-tabs-window-item>

            <!-- Contact Tab -->
            <v-tabs-window-item value="contact">
              <v-card elevation="0" border rounded="xl">
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
            </v-tabs-window-item>

            <!-- Address Tab -->
            <v-tabs-window-item value="address">
              <v-card elevation="0" border rounded="xl">
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
            </v-tabs-window-item>
          </v-tabs-window>
        </v-col>

        <v-col cols="12" md="4">
          <v-card elevation="0" border rounded="xl" class="mb-4">
            <v-card-title class="pa-6 pb-2">Actions</v-card-title>
            <v-card-text class="pa-6 pt-2">
              <v-btn type="submit" color="primary" block size="large" :loading="submitting" class="mb-3">
                <v-icon class="mr-2">mdi-content-save</v-icon>
                Save Changes
              </v-btn>
              <v-btn variant="tonal" block :href="route('users.index')">Cancel</v-btn>
            </v-card-text>
          </v-card>

          <v-card v-if="user.picture_large" elevation="0" border rounded="xl">
            <v-card-title class="pa-6 pb-2">Profile Photo</v-card-title>
            <v-card-text class="pa-6 pt-2 d-flex justify-center">
              <v-avatar size="120">
                <v-img :src="user.picture_large" />
              </v-avatar>
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

const props = defineProps({
  user: Object,
  errors: { type: Object, default: () => ({}) },
});

const tab = ref('personal');
const genderOptions = ['male', 'female', 'other'];
const submitting = ref(false);

const form = ref({
  first_name: props.user.first_name || '',
  last_name: props.user.last_name || '',
  email: props.user.email || '',
  username: props.user.username || '',
  gender: props.user.gender || null,
  date_of_birth: props.user.date_of_birth ? props.user.date_of_birth.substring(0, 10) : '',
  nationality: props.user.nationality || '',
  contact: {
    phone: props.user.contact?.phone || '',
    cell: props.user.contact?.cell || '',
  },
  address: {
    street_number: props.user.address?.street_number || '',
    street_name: props.user.address?.street_name || '',
    city: props.user.address?.city || '',
    state: props.user.address?.state || '',
    postcode: props.user.address?.postcode || '',
    country: props.user.address?.country || '',
  },
});

const submit = () => {
  submitting.value = true;
  router.put(route('users.update', props.user.id), form.value, {
    onFinish: () => { submitting.value = false; },
  });
};
</script>
