<template>
  <Head title="HR - Settings" />
  
  <AppLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          ⚙️ HR System Settings
        </h2>
        <div class="flex space-x-4">
          <a href="/hr-vue" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            ← Back to HR Dashboard
          </a>
          <button @click="saveAllSettings" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            💾 Save All Settings
          </button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Settings Navigation -->
        <div class="mb-6">
          <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
              <button 
                v-for="tab in settingsTabs" 
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  activeTab === tab.id 
                    ? 'border-blue-500 text-blue-600' 
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                  'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                ]"
              >
                {{ tab.icon }} {{ tab.name }}
              </button>
            </nav>
          </div>
        </div>

        <!-- General Settings -->
        <div v-show="activeTab === 'general'" class="space-y-6">
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">🏢 Company Information</h3>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                  <input 
                    v-model="settings.general.companyName"
                    type="text" 
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Company Code</label>
                  <input 
                    v-model="settings.general.companyCode"
                    type="text" 
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                  <select 
                    v-model="settings.general.industry"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="technology">Technology</option>
                    <option value="finance">Finance</option>
                    <option value="healthcare">Healthcare</option>
                    <option value="manufacturing">Manufacturing</option>
                    <option value="retail">Retail</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Time Zone</label>
                  <select 
                    v-model="settings.general.timezone"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="UTC">UTC</option>
                    <option value="America/New_York">Eastern Time</option>
                    <option value="America/Chicago">Central Time</option>
                    <option value="America/Denver">Mountain Time</option>
                    <option value="America/Los_Angeles">Pacific Time</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">📧 System Preferences</h3>
            </div>
            <div class="p-6 space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-900">Email Notifications</label>
                  <p class="text-sm text-gray-500">Send email notifications for important events</p>
                </div>
                <input 
                  v-model="settings.general.emailNotifications" 
                  type="checkbox" 
                  class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
              </div>
              
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-900">Auto Backup</label>
                  <p class="text-sm text-gray-500">Automatically backup data daily</p>
                </div>
                <input 
                  v-model="settings.general.autoBackup" 
                  type="checkbox" 
                  class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
              </div>
              
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-900">Two-Factor Authentication</label>
                  <p class="text-sm text-gray-500">Require 2FA for admin accounts</p>
                </div>
                <input 
                  v-model="settings.general.twoFactorAuth" 
                  type="checkbox" 
                  class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Employee Settings -->
        <div v-show="activeTab === 'employee'" class="space-y-6">
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">👤 Employee Configuration</h3>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID Format</label>
                  <input 
                    v-model="settings.employee.idFormat"
                    type="text" 
                    placeholder="EMP-{YYYY}-{####}"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                  <p class="text-xs text-gray-500 mt-1">Use {YYYY} for year, {####} for sequence</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Probation Period (days)</label>
                  <input 
                    v-model="settings.employee.probationPeriod"
                    type="number" 
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Retirement Age</label>
                  <input 
                    v-model="settings.employee.retirementAge"
                    type="number" 
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Performance Review Cycle</label>
                  <select 
                    v-model="settings.employee.performanceReviewCycle"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="quarterly">Quarterly</option>
                    <option value="semi-annual">Semi-Annual</option>
                    <option value="annual">Annual</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Attendance Settings -->
        <div v-show="activeTab === 'attendance'" class="space-y-6">
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">⏰ Attendance Configuration</h3>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Standard Work Hours</label>
                  <input 
                    v-model="settings.attendance.standardHours"
                    type="number" 
                    step="0.5"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Late Threshold (minutes)</label>
                  <input 
                    v-model="settings.attendance.lateThreshold"
                    type="number" 
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Overtime Threshold (hours)</label>
                  <input 
                    v-model="settings.attendance.overtimeThreshold"
                    type="number" 
                    step="0.5"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Grace Period (minutes)</label>
                  <input 
                    v-model="settings.attendance.gracePeriod"
                    type="number" 
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
              </div>
              
              <div class="mt-6 space-y-4">
                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-sm font-medium text-gray-900">Auto Clock Out</label>
                    <p class="text-sm text-gray-500">Automatically clock out employees after work hours</p>
                  </div>
                  <input 
                    v-model="settings.attendance.autoClockOut" 
                    type="checkbox" 
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                
                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-sm font-medium text-gray-900">Weekend Work</label>
                    <p class="text-sm text-gray-500">Allow attendance marking on weekends</p>
                  </div>
                  <input 
                    v-model="settings.attendance.weekendWork" 
                    type="checkbox" 
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Leave Settings -->
        <div v-show="activeTab === 'leave'" class="space-y-6">
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">🏖️ Leave Management</h3>
            </div>
            <div class="p-6">
              <div class="space-y-6">
                <div v-for="leaveType in settings.leave.types" :key="leaveType.id" class="border border-gray-200 rounded-lg p-4">
                  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Leave Type</label>
                      <input 
                        v-model="leaveType.name"
                        type="text" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      >
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Annual Allocation</label>
                      <input 
                        v-model="leaveType.allocation"
                        type="number" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      >
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Carryover Limit</label>
                      <input 
                        v-model="leaveType.carryover"
                        type="number" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      >
                    </div>
                    <div class="flex items-end">
                      <button @click="removeLeaveType(leaveType.id)" class="w-full bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                        🗑️ Remove
                      </button>
                    </div>
                  </div>
                </div>
                
                <button @click="addLeaveType" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                  ➕ Add Leave Type
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Payroll Settings -->
        <div v-show="activeTab === 'payroll'" class="space-y-6">
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">💰 Payroll Configuration</h3>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Pay Frequency</label>
                  <select 
                    v-model="settings.payroll.frequency"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="weekly">Weekly</option>
                    <option value="bi-weekly">Bi-Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="semi-monthly">Semi-Monthly</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                  <select 
                    v-model="settings.payroll.currency"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="USD">USD ($)</option>
                    <option value="EUR">EUR (€)</option>
                    <option value="GBP">GBP (£)</option>
                    <option value="CAD">CAD (C$)</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Overtime Rate (%)</label>
                  <input 
                    v-model="settings.payroll.overtimeRate"
                    type="number" 
                    step="0.1"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Tax Rate (%)</label>
                  <input 
                    v-model="settings.payroll.taxRate"
                    type="number" 
                    step="0.1"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Security Settings -->
        <div v-show="activeTab === 'security'" class="space-y-6">
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">🔒 Security Configuration</h3>
            </div>
            <div class="p-6 space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Password Min Length</label>
                  <input 
                    v-model="settings.security.passwordMinLength"
                    type="number" 
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Session Timeout (minutes)</label>
                  <input 
                    v-model="settings.security.sessionTimeout"
                    type="number" 
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Max Login Attempts</label>
                  <input 
                    v-model="settings.security.maxLoginAttempts"
                    type="number" 
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Account Lock Duration (minutes)</label>
                  <input 
                    v-model="settings.security.lockDuration"
                    type="number" 
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
              </div>
              
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-sm font-medium text-gray-900">Require Password Complexity</label>
                    <p class="text-sm text-gray-500">Passwords must contain uppercase, lowercase, numbers, and symbols</p>
                  </div>
                  <input 
                    v-model="settings.security.passwordComplexity" 
                    type="checkbox" 
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                
                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-sm font-medium text-gray-900">Audit Logging</label>
                    <p class="text-sm text-gray-500">Log all user actions for security auditing</p>
                  </div>
                  <input 
                    v-model="settings.security.auditLogging" 
                    type="checkbox" 
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>
                
                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-sm font-medium text-gray-900">IP Whitelisting</label>
                    <p class="text-sm text-gray-500">Restrict access to specific IP addresses</p>
                  </div>
                  <input 
                    v-model="settings.security.ipWhitelisting" 
                    type="checkbox" 
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
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
import { ref, reactive } from 'vue'

const activeTab = ref('general')

const settingsTabs = [
  { id: 'general', name: 'General', icon: '🏢' },
  { id: 'employee', name: 'Employee', icon: '👤' },
  { id: 'attendance', name: 'Attendance', icon: '⏰' },
  { id: 'leave', name: 'Leave', icon: '🏖️' },
  { id: 'payroll', name: 'Payroll', icon: '💰' },
  { id: 'security', name: 'Security', icon: '🔒' }
]

const settings = reactive({
  general: {
    companyName: 'TechCorp Solutions',
    companyCode: 'TECH001',
    industry: 'technology',
    timezone: 'America/New_York',
    emailNotifications: true,
    autoBackup: true,
    twoFactorAuth: false
  },
  employee: {
    idFormat: 'EMP-{YYYY}-{####}',
    probationPeriod: 90,
    retirementAge: 65,
    performanceReviewCycle: 'annual'
  },
  attendance: {
    standardHours: 8,
    lateThreshold: 15,
    overtimeThreshold: 8,
    gracePeriod: 5,
    autoClockOut: true,
    weekendWork: false
  },
  leave: {
    types: [
      { id: 1, name: 'Annual Leave', allocation: 20, carryover: 5 },
      { id: 2, name: 'Sick Leave', allocation: 10, carryover: 2 },
      { id: 3, name: 'Personal Leave', allocation: 5, carryover: 0 },
      { id: 4, name: 'Maternity/Paternity Leave', allocation: 90, carryover: 0 }
    ]
  },
  payroll: {
    frequency: 'monthly',
    currency: 'USD',
    overtimeRate: 150,
    taxRate: 25
  },
  security: {
    passwordMinLength: 8,
    sessionTimeout: 30,
    maxLoginAttempts: 5,
    lockDuration: 15,
    passwordComplexity: true,
    auditLogging: true,
    ipWhitelisting: false
  }
})

const addLeaveType = () => {
  const newId = Math.max(...settings.leave.types.map(t => t.id)) + 1
  settings.leave.types.push({
    id: newId,
    name: 'New Leave Type',
    allocation: 0,
    carryover: 0
  })
}

const removeLeaveType = (id) => {
  if (settings.leave.types.length > 1) {
    const index = settings.leave.types.findIndex(t => t.id === id)
    if (index > -1) {
      settings.leave.types.splice(index, 1)
    }
  } else {
    alert('At least one leave type must be configured.')
  }
}

const saveAllSettings = () => {
  alert('All settings saved successfully!\n\nChanges have been applied to the HR system. Some changes may require a system restart to take effect.')
}
</script>