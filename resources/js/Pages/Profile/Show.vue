<template>
  <Head title="Profile" />

  <AppLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          👤 User Profile
        </h2>
        <button @click="showEditModal = true" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
          ✏️ Edit Profile
        </button>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Profile Information -->
        <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg mb-6">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">📋 Profile Information</h3>
          </div>
          
          <div class="p-6">
            <div class="flex items-center space-x-6">
              <!-- Profile Photo -->
              <div class="flex-shrink-0">
                <div class="h-20 w-20 rounded-full bg-gray-300 flex items-center justify-center">
                  <span class="text-2xl font-medium text-gray-700">{{ getInitials(userProfile.name) }}</span>
                </div>
              </div>
              
              <!-- Basic Info -->
              <div class="flex-1">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Full Name</label>
                    <p class="text-lg font-medium text-gray-900">{{ userProfile.name }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Email Address</label>
                    <p class="text-lg text-gray-900">{{ userProfile.email }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Role</label>
                    <p class="text-lg text-gray-900">{{ userProfile.role || 'HR Administrator' }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Department</label>
                    <p class="text-lg text-gray-900">{{ userProfile.department || 'Human Resources' }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Account Settings -->
        <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg mb-6">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">⚙️ Account Settings</h3>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-500">Language Preference</label>
                <p class="text-lg text-gray-900">{{ userProfile.language === 'ar' ? 'Arabic (العربية)' : 'English' }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-500">Time Zone</label>
                <p class="text-lg text-gray-900">{{ userProfile.timezone || 'UTC+03:00 (Riyadh)' }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-500">Date Format</label>
                <p class="text-lg text-gray-900">{{ userProfile.dateFormat || 'DD/MM/YYYY' }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-500">Notifications</label>
                <p class="text-lg text-gray-900">{{ userProfile.notifications ? 'Enabled' : 'Disabled' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Account Activity -->
        <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">📊 Account Activity</h3>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">{{ activityStats.loginCount }}</div>
                <div class="text-sm text-gray-600">Total Logins</div>
              </div>
              <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ activityStats.reportsGenerated }}</div>
                <div class="text-sm text-gray-600">Reports Generated</div>
              </div>
              <div class="text-center p-4 bg-purple-50 rounded-lg">
                <div class="text-2xl font-bold text-purple-600">{{ activityStats.lastActive }}</div>
                <div class="text-sm text-gray-600">Last Active</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Edit Profile Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">✏️ Edit Profile</h3>
            <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="updateProfile" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                <input v-model="editingProfile.name" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Email Address *</label>
                <input v-model="editingProfile.email" type="email" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Department</label>
                <select v-model="editingProfile.department" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                  <option value="Human Resources">Human Resources</option>
                  <option value="Engineering">Engineering</option>
                  <option value="Marketing">Marketing</option>
                  <option value="Sales">Sales</option>
                  <option value="Finance">Finance</option>
                  <option value="Operations">Operations</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Language</label>
                <select v-model="editingProfile.language" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                  <option value="en">English</option>
                  <option value="ar">Arabic (العربية)</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Time Zone</label>
                <select v-model="editingProfile.timezone" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                  <option value="UTC+03:00">UTC+03:00 (Riyadh)</option>
                  <option value="UTC+00:00">UTC+00:00 (London)</option>
                  <option value="UTC-05:00">UTC-05:00 (New York)</option>
                  <option value="UTC+01:00">UTC+01:00 (Berlin)</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Date Format</label>
                <select v-model="editingProfile.dateFormat" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                  <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                  <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                  <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                </select>
              </div>
            </div>
            
            <div>
              <label class="flex items-center">
                <input v-model="editingProfile.notifications" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700">Enable email notifications</span>
              </label>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
              <button type="button" @click="showEditModal = false" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">
                Cancel
              </button>
              <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                ✏️ Update Profile
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed } from 'vue'

// Modal state
const showEditModal = ref(false)

// User profile data (in a real app, this would come from props or API)
const userProfile = ref({
  name: 'Admin User',
  email: 'admin@hr-system.com',
  role: 'HR Administrator',
  department: 'Human Resources',
  language: 'en',
  timezone: 'UTC+03:00',
  dateFormat: 'DD/MM/YYYY',
  notifications: true
})

// Editing profile data
const editingProfile = ref({ ...userProfile.value })

// Activity statistics
const activityStats = ref({
  loginCount: 42,
  reportsGenerated: 18,
  lastActive: '2 hours ago'
})

// Get user initials
const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

// Update profile
const updateProfile = () => {
  // Update the main profile
  userProfile.value = { ...editingProfile.value }
  
  // Close modal
  showEditModal.value = false
  
  // Show success message
  alert('✅ Profile updated successfully!\n\nYour profile information has been saved.')
}

// Watch for language changes to update editing profile
const resetEditingProfile = () => {
  editingProfile.value = { ...userProfile.value }
}

// Reset editing profile when modal opens
const openEditModal = () => {
  resetEditingProfile()
  showEditModal.value = true
}
</script>