<template>
  <Head title="HR Management - Dashboard" />
  
  <AppLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          🏢 HR Management Dashboard
        </h2>
        <div class="flex space-x-4">
          <a href="/hr-vue/employees" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            👥 Employees
          </a>
          <a href="/hr-vue/departments" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            🏛️ Departments
          </a>
          <a href="/hr-vue/teams" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
            🏆 Teams
          </a>
          <a href="/hr-vue/attendance" class="bg-orange-500 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded">
            📅 Attendance
          </a>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 overflow-hidden shadow-xl sm:rounded-lg mb-6">
          <div class="p-6 text-white">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="text-4xl">🏢</div>
              </div>
              <div class="ml-4">
                <h3 class="text-2xl font-bold">Welcome to HR Management System</h3>
                <p class="text-blue-100 mt-2">Manage your organization's human resources efficiently</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <!-- Total Employees -->
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl text-blue-500">👥</div>
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ dashboardData.totalEmployees || 127 }}</div>
                  <div class="text-gray-500">Total Employees</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Active Departments -->
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl text-green-500">🏛️</div>
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ dashboardData.totalDepartments || 12 }}</div>
                  <div class="text-gray-500">Departments</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Today's Attendance -->
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl text-purple-500">📅</div>
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ dashboardData.attendanceRate || '94%' }}</div>
                  <div class="text-gray-500">Today's Attendance</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Performance Rating -->
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl text-yellow-500">⭐</div>
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ dashboardData.avgPerformance || '8.5' }}</div>
                  <div class="text-gray-500">Avg Performance</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <!-- Recent Activities -->
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">📋 Recent Activities</h3>
            </div>
            <div class="p-6">
              <div class="space-y-4">
                <div v-for="activity in recentActivities" :key="activity.id" class="flex items-center p-3 bg-gray-50 rounded-lg">
                  <div class="flex-shrink-0">
                    <span class="text-xl">{{ activity.icon }}</span>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">{{ activity.title }}</p>
                    <p class="text-sm text-gray-500">{{ activity.time }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">⚡ Quick Actions</h3>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="/hr-vue/employees" class="flex items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition duration-150">
                  <span class="text-2xl mr-3">➕</span>
                  <div>
                    <div class="font-medium text-blue-900">Add Employee</div>
                    <div class="text-blue-600 text-sm">Create new employee record</div>
                  </div>
                </a>
                
                <a href="/hr-vue/departments" class="flex items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition duration-150">
                  <span class="text-2xl mr-3">🏗️</span>
                  <div>
                    <div class="font-medium text-green-900">New Department</div>
                    <div class="text-green-600 text-sm">Create department</div>
                  </div>
                </a>
                
                <a href="/hr-vue/teams" class="flex items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition duration-150">
                  <span class="text-2xl mr-3">🏆</span>
                  <div>
                    <div class="font-medium text-purple-900">Manage Teams</div>
                    <div class="text-purple-600 text-sm">Create & manage teams</div>
                  </div>
                </a>
                
                <a href="/hr-vue/attendance" class="flex items-center p-4 bg-orange-50 hover:bg-orange-100 rounded-lg transition duration-150">
                  <span class="text-2xl mr-3">📊</span>
                  <div>
                    <div class="font-medium text-orange-900">View Attendance</div>
                    <div class="text-orange-600 text-sm">Today's attendance</div>
                  </div>
                </a>
                
                <a href="/hr-vue/reports" class="flex items-center p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition duration-150">
                  <span class="text-2xl mr-3">📈</span>
                  <div>
                    <div class="font-medium text-yellow-900">Generate Reports</div>
                    <div class="text-yellow-600 text-sm">HR analytics & reports</div>
                  </div>
                </a>
                
                <a href="/hr-vue/settings" class="flex items-center p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition duration-150">
                  <span class="text-2xl mr-3">⚙️</span>
                  <div>
                    <div class="font-medium text-gray-900">HR Settings</div>
                    <div class="text-gray-600 text-sm">System configuration</div>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- System Status -->
        <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">🔧 System Status</h3>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="flex items-center p-3 bg-green-50 rounded-lg">
                <span class="text-green-500 text-xl mr-3">✅</span>
                <div>
                  <div class="font-medium text-green-900">Database Connected</div>
                  <div class="text-green-600 text-sm">All systems operational</div>
                </div>
              </div>
              
              <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                <span class="text-blue-500 text-xl mr-3">🔐</span>
                <div>
                  <div class="font-medium text-blue-900">Authentication Active</div>
                  <div class="text-blue-600 text-sm">Secure login verified</div>
                </div>
              </div>
              
              <div class="flex items-center p-3 bg-yellow-50 rounded-lg">
                <span class="text-yellow-500 text-xl mr-3">⚠️</span>
                <div>
                  <div class="font-medium text-yellow-900">HR Tables Setup</div>
                  <div class="text-yellow-600 text-sm">Database migration needed</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, onMounted } from 'vue'

// Dashboard data
const dashboardData = ref({
  totalEmployees: 127,
  totalDepartments: 12,
  attendanceRate: '94%',
  avgPerformance: '8.5'
})

// Recent activities (demo data)
const recentActivities = ref([
  {
    id: 1,
    icon: '👤',
    title: 'New employee John Doe added to Engineering',
    time: '2 hours ago'
  },
  {
    id: 2,
    icon: '📅',
    title: 'Monthly attendance report generated',
    time: '4 hours ago'
  },
  {
    id: 3,
    icon: '⭐',
    title: 'Performance reviews completed for Q3',
    time: '1 day ago'
  },
  {
    id: 4,
    icon: '🏛️',
    title: 'New Marketing department created',
    time: '2 days ago'
  }
])

// Load dashboard data on component mount
onMounted(async () => {
  try {
    // You can uncomment this when HR API is working
    // const response = await axios.get('/api/hr/dashboard')
    // dashboardData.value = response.data
  } catch (error) {
    console.log('Using demo data - API not available yet')
  }
})
</script>

<style scoped>
/* Custom styles if needed */
</style>