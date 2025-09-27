<template>
  <Head title="Log in" />

  <GuestLayout>
    <div>
      <div class="mb-4 text-sm text-green-600" v-if="status">
        {{ status }}
      </div>

      <form @submit.prevent="submit">
        <div>
          <label for="email" class="block font-medium text-sm text-gray-700">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
            required
            autofocus
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
            autocomplete="current-password"
          />
          <div class="text-red-600 text-sm mt-2" v-if="form.errors.password">{{ form.errors.password }}</div>
        </div>

        <div class="block mt-4">
          <label class="flex items-center">
            <input
              v-model="form.remember"
              name="remember"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
            />
            <span class="ml-2 text-sm text-gray-600">Remember me</span>
          </label>
        </div>

        <div class="flex items-center justify-end mt-4">
          <Link
            v-if="canResetPassword"
            href="/forgot-password"
            class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Forgot your password?
          </Link>

          <button
            type="submit"
            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 ml-3"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
          >
            Log in
          </button>
        </div>

        <div class="flex items-center justify-center mt-6">
          <span class="text-sm text-gray-600">Don't have an account?</span>
          <Link
            href="/register"
            class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ml-1"
          >
            Sign up
          </Link>
        </div>
      </form>
    </div>
  </GuestLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

defineProps({
  canResetPassword: Boolean,
  status: String,
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>