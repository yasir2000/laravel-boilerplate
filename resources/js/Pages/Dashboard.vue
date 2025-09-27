<template>
  <Head title="Dashboard" />

  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Dashboard
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <div class="mb-6">
              <h3 class="text-lg font-medium text-gray-900 mb-2">
                Welcome back, {{ $page.props.auth.user.name }}! 👋
              </h3>
              <p class="text-gray-600">
                Your Laravel Boilerplate with Vue.js and Inertia.js is ready to use.
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
              <!-- User Stats Card -->
              <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <div class="text-2xl font-bold">{{ stats.users || 0 }}</div>
                    <div class="text-blue-100">Total Users</div>
                  </div>
                </div>
              </div>

              <!-- Notifications Card -->
              <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-5" />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <div class="text-2xl font-bold">{{ stats.notifications || 0 }}</div>
                    <div class="text-green-100">Notifications</div>
                  </div>
                </div>
              </div>

              <!-- Workflows Card -->
              <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <div class="text-2xl font-bold">{{ stats.workflows || 0 }}</div>
                    <div class="text-purple-100">Active Workflows</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow">
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
              </div>
              <div class="p-6">
                <div class="space-y-4">
                  <div class="flex items-start space-x-4" v-for="activity in recentActivity" :key="activity.id">
                    <div class="flex-shrink-0">
                      <div class="h-8 w-8 bg-gray-200 rounded-full flex items-center justify-center">
                        <svg class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900">{{ activity.title }}</p>
                      <p class="text-sm text-gray-500">{{ activity.description }}</p>
                      <p class="text-xs text-gray-400 mt-1">{{ activity.time }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
              <div class="flex flex-wrap gap-4">
                <Link
                  href="/api/workflows"
                  class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                  View Workflows
                </Link>
                <Link
                  href="/api/notifications"
                  class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                  Check Notifications
                </Link>
                <a
                  href="http://localhost:8025"
                  target="_blank"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                  Mail Testing
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

// Sample data - in real app, this would come from props or API
const stats = ref({
  users: 1,
  notifications: 0,
  workflows: 0,
})

const recentActivity = ref([
  {
    id: 1,
    title: 'Welcome to Laravel Boilerplate!',
    description: 'Your application with Vue.js and Inertia.js is ready.',
    time: 'Just now'
  },
  {
    id: 2,
    title: 'Database Connected',
    description: 'PostgreSQL database is connected and ready.',
    time: '5 minutes ago'
  },
  {
    id: 3,
    title: 'Features Available',
    description: 'All advanced features are configured and available.',
    time: '10 minutes ago'
  }
])

// You can define props here if passed from controller
defineProps({
  stats: Object,
  recentActivity: Array,
})
</script>