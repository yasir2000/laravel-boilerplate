<template>
  <Head title="Register" />

  <GuestLayout>
    <form @submit.prevent="submit">
      <div>
        <label for="name" class="block font-medium text-sm text-gray-700">Name</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
          required
          autofocus
          autocomplete="name"
        />
        <div class="text-red-600 text-sm mt-2" v-if="form.errors.name">{{ form.errors.name }}</div>
      </div>

      <div class="mt-4">
        <label for="email" class="block font-medium text-sm text-gray-700">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
          required
          autocomplete="username"
        />
        <div class="text-red-600 text-sm mt-2" v-if="form.errors.email">{{ form.errors.email }}</div>
      </div>

      <div class="mt-4">
        <label for="password" class="block font-medium text-sm text-gray-700">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
          required
          autocomplete="new-password"
        />
        <div class="text-red-600 text-sm mt-2" v-if="form.errors.password">{{ form.errors.password }}</div>
      </div>

      <div class="mt-4">
        <label for="password_confirmation" class="block font-medium text-sm text-gray-700">Confirm Password</label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
          required
          autocomplete="new-password"
        />
        <div class="text-red-600 text-sm mt-2" v-if="form.errors.password_confirmation">{{ form.errors.password_confirmation }}</div>
      </div>

      <div class="flex items-center justify-end mt-4">
        <Link
          href="/login"
          class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Already registered?
        </Link>

        <button
          type="submit"
          class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 ml-4"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Register
        </button>
      </div>
    </form>
  </GuestLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>