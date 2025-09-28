<template>
  <Head title="HR - Attendance" />
  
  <AppLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          📅 Attendance Management
        </h2>
        <div class="flex space-x-4">
          <a href="/hr-vue" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            🏠 Dashboard
          </a>
          <a href="/hr-vue/employees" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            👥 Employees
          </a>
          <a href="/hr-vue/teams" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
            🏆 Teams
          </a>
          <button @click="downloadReport" class="bg-orange-500 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded">
            📊 Export Report
          </button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Attendance Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <div class="bg-green-500 text-white p-6 rounded-lg shadow-lg">
            <div class="text-2xl font-bold">{{ todayStats.present }}</div>
            <div>Present Today</div>
            <div class="text-sm opacity-75">{{ todayStats.presentPercentage }}%</div>
          </div>
          <div class="bg-red-500 text-white p-6 rounded-lg shadow-lg">
            <div class="text-2xl font-bold">{{ todayStats.absent }}</div>
            <div>Absent Today</div>
            <div class="text-sm opacity-75">{{ todayStats.absentPercentage }}%</div>
          </div>
          <div class="bg-yellow-500 text-white p-6 rounded-lg shadow-lg">
            <div class="text-2xl font-bold">{{ todayStats.late }}</div>
            <div>Late Arrivals</div>
            <div class="text-sm opacity-75">{{ todayStats.latePercentage }}%</div>
          </div>
          <div class="bg-blue-500 text-white p-6 rounded-lg shadow-lg">
            <div class="text-2xl font-bold">{{ todayStats.onLeave }}</div>
            <div>On Leave</div>
            <div class="text-sm opacity-75">{{ todayStats.leavePercentage }}%</div>
          </div>
        </div>

        <!-- Date Filter and Controls -->
        <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg mb-6">
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                <input 
                  v-model="selectedDate"
                  type="date" 
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select 
                  v-model="selectedDepartment"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                  <option value="">All Departments</option>
                  <option value="Engineering">Engineering</option>
                  <option value="Marketing">Marketing</option>
                  <option value="Sales">Sales</option>
                  <option value="HR">Human Resources</option>
                  <option value="Finance">Finance</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select 
                  v-model="selectedStatus"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                  <option value="">All Status</option>
                  <option value="present">Present</option>
                  <option value="absent">Absent</option>
                  <option value="late">Late</option>
                  <option value="on-leave">On Leave</option>
                </select>
              </div>
              
              <div class="flex items-end">
                <button @click="resetFilters" class="w-full bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                  🔄 Reset
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Attendance Table -->
        <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg mb-6">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">📋 Daily Attendance - {{ formatDate(selectedDate) }}</h3>
          </div>
          
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Employee
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Department
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Check In
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Check Out
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Hours Worked
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="record in filteredAttendance" :key="record.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                          <span class="text-sm font-medium text-gray-700">{{ getInitials(record.employeeName) }}</span>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ record.employeeName }}</div>
                        <div class="text-sm text-gray-500">{{ record.employeeId }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ record.department }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ record.checkIn || '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ record.checkOut || '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ record.hoursWorked || '0.0' }}h
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusClass(record.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ getStatusIcon(record.status) }} {{ record.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <button @click="viewDetails(record)" class="text-blue-600 hover:text-blue-900">👁️ View</button>
                    <button @click="editRecord(record)" class="text-green-600 hover:text-green-900">✏️ Edit</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Weekly Summary -->
        <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">📈 Weekly Attendance Summary</h3>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-7 gap-4">
              <div v-for="day in weeklyData" :key="day.date" class="text-center">
                <div class="text-sm font-medium text-gray-900 mb-2">{{ day.day }}</div>
                <div class="text-xs text-gray-500 mb-2">{{ formatShortDate(day.date) }}</div>
                <div class="space-y-1">
                  <div class="bg-green-100 rounded p-2">
                    <div class="text-lg font-bold text-green-700">{{ day.present }}</div>
                    <div class="text-xs text-green-600">Present</div>
                  </div>
                  <div class="bg-red-100 rounded p-2">
                    <div class="text-lg font-bold text-red-700">{{ day.absent }}</div>
                    <div class="text-xs text-red-600">Absent</div>
                  </div>
                  <div class="bg-yellow-100 rounded p-2">
                    <div class="text-lg font-bold text-yellow-700">{{ day.late }}</div>
                    <div class="text-xs text-yellow-600">Late</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Attendance Details Modal -->
    <div v-if="showDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">👁️ Attendance Details</h3>
            <button @click="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Employee Information -->
            <div class="space-y-4">
              <div>
                <h4 class="text-sm font-medium text-gray-900">Employee Information</h4>
                <div class="mt-2 space-y-2">
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Name:</span>
                    <span class="text-sm font-medium">{{ selectedRecord?.employeeName }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Employee ID:</span>
                    <span class="text-sm font-medium">{{ selectedRecord?.employeeId }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Department:</span>
                    <span class="text-sm font-medium">{{ selectedRecord?.department }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Date:</span>
                    <span class="text-sm font-medium">{{ selectedDate }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Attendance Details -->
            <div class="space-y-4">
              <div>
                <h4 class="text-sm font-medium text-gray-900">Attendance Details</h4>
                <div class="mt-2 space-y-2">
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Check In:</span>
                    <span class="text-sm font-medium">{{ selectedRecord?.checkIn || 'Not recorded' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Check Out:</span>
                    <span class="text-sm font-medium">{{ selectedRecord?.checkOut || 'Not recorded' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Hours Worked:</span>
                    <span class="text-sm font-medium">{{ selectedRecord?.hoursWorked }} hours</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Status:</span>
                    <span :class="getStatusClass(selectedRecord?.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                      {{ selectedRecord?.status }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <button @click="closeDetailsModal" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">
              Close
            </button>
            <button @click="editRecord(selectedRecord)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
              ✏️ Edit Record
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Attendance Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">✏️ Edit Attendance - {{ editingRecord?.employeeName }}</h3>
            <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="updateRecord" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Employee Name</label>
                <input :value="editingRecord?.employeeName" readonly class="mt-1 block w-full bg-gray-100 border-gray-300 rounded-md shadow-sm">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Employee ID</label>
                <input :value="editingRecord?.employeeId" readonly class="mt-1 block w-full bg-gray-100 border-gray-300 rounded-md shadow-sm">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Check In Time</label>
                <input v-model="editingRecord.checkIn" type="time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Check Out Time</label>
                <input v-model="editingRecord.checkOut" type="time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select v-model="editingRecord.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                  <option value="present">Present</option>
                  <option value="absent">Absent</option>
                  <option value="late">Late</option>
                  <option value="on-leave">On Leave</option>
                  <option value="half-day">Half Day</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Hours Worked</label>
                <input v-model="editingRecord.hoursWorked" type="number" step="0.1" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
              <button type="button" @click="closeEditModal" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">
                Cancel
              </button>
              <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                ✏️ Update Attendance
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed, onMounted } from 'vue'

// Demo attendance data
const attendanceRecords = ref([
  { id: 1, employeeName: 'John Doe', employeeId: 'EMP001', department: 'Engineering', checkIn: '09:00', checkOut: '17:30', hoursWorked: '8.5', status: 'present' },
  { id: 2, employeeName: 'Jane Smith', employeeId: 'EMP002', department: 'Marketing', checkIn: '09:15', checkOut: '17:45', hoursWorked: '8.5', status: 'late' },
  { id: 3, employeeName: 'Mike Johnson', employeeId: 'EMP003', department: 'Sales', checkIn: null, checkOut: null, hoursWorked: '0.0', status: 'on-leave' },
  { id: 4, employeeName: 'Sarah Wilson', employeeId: 'EMP004', department: 'HR', checkIn: '08:45', checkOut: '17:15', hoursWorked: '8.5', status: 'present' },
  { id: 5, employeeName: 'Tom Brown', employeeId: 'EMP005', department: 'Finance', checkIn: null, checkOut: null, hoursWorked: '0.0', status: 'absent' },
  { id: 6, employeeName: 'Lisa Davis', employeeId: 'EMP006', department: 'Engineering', checkIn: '09:30', checkOut: '18:00', hoursWorked: '8.5', status: 'late' },
  { id: 7, employeeName: 'Alex Johnson', employeeId: 'EMP007', department: 'Marketing', checkIn: '08:50', checkOut: '17:20', hoursWorked: '8.5', status: 'present' },
  { id: 8, employeeName: 'Maria Garcia', employeeId: 'EMP008', department: 'Sales', checkIn: '09:05', checkOut: '17:35', hoursWorked: '8.5', status: 'present' }
])

// Filter states
const selectedDate = ref(new Date().toISOString().split('T')[0])
const selectedDepartment = ref('')
const selectedStatus = ref('')

// Modal states
const showDetailsModal = ref(false)
const showEditModal = ref(false)
const selectedRecord = ref(null)
const editingRecord = ref(null)

// Weekly data for summary
const weeklyData = ref([
  { date: '2025-09-22', day: 'Mon', present: 24, absent: 2, late: 1 },
  { date: '2025-09-23', day: 'Tue', present: 25, absent: 1, late: 2 },
  { date: '2025-09-24', day: 'Wed', present: 23, absent: 3, late: 1 },
  { date: '2025-09-25', day: 'Thu', present: 26, absent: 1, late: 0 },
  { date: '2025-09-26', day: 'Fri', present: 22, absent: 4, late: 1 },
  { date: '2025-09-27', day: 'Sat', present: 0, absent: 0, late: 0 },
  { date: '2025-09-28', day: 'Sun', present: 0, absent: 0, late: 0 }
])

// Computed properties
const filteredAttendance = computed(() => {
  return attendanceRecords.value.filter(record => {
    const matchesDepartment = !selectedDepartment.value || record.department === selectedDepartment.value
    const matchesStatus = !selectedStatus.value || record.status === selectedStatus.value
    return matchesDepartment && matchesStatus
  })
})

const todayStats = computed(() => {
  const total = filteredAttendance.value.length
  const present = filteredAttendance.value.filter(r => r.status === 'present').length
  const absent = filteredAttendance.value.filter(r => r.status === 'absent').length
  const late = filteredAttendance.value.filter(r => r.status === 'late').length
  const onLeave = filteredAttendance.value.filter(r => r.status === 'on-leave').length
  
  return {
    present,
    absent,
    late,
    onLeave,
    presentPercentage: total > 0 ? Math.round((present / total) * 100) : 0,
    absentPercentage: total > 0 ? Math.round((absent / total) * 100) : 0,
    latePercentage: total > 0 ? Math.round((late / total) * 100) : 0,
    leavePercentage: total > 0 ? Math.round((onLeave / total) * 100) : 0
  }
})

// Methods
const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getStatusClass = (status) => {
  const classes = {
    'present': 'bg-green-100 text-green-800',
    'absent': 'bg-red-100 text-red-800',
    'late': 'bg-yellow-100 text-yellow-800',
    'on-leave': 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusIcon = (status) => {
  const icons = {
    'present': '✅',
    'absent': '❌',
    'late': '⏰',
    'on-leave': '🏖️'
  }
  return icons[status] || '❓'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

const formatShortDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const resetFilters = () => {
  selectedDate.value = new Date().toISOString().split('T')[0]
  selectedDepartment.value = ''
  selectedStatus.value = ''
}

const viewDetails = (record) => {
  selectedRecord.value = record
  showDetailsModal.value = true
}

const editRecord = (record) => {
  editingRecord.value = { ...record }
  showEditModal.value = true
}

// Close modals
const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedRecord.value = null
}

const closeEditModal = () => {
  showEditModal.value = false
  editingRecord.value = null
}

// Update attendance record
const updateRecord = () => {
  const index = attendanceRecords.value.findIndex(record => record.id === editingRecord.value.id)
  
  if (index !== -1) {
    // Calculate hours worked if both check in and check out are provided
    if (editingRecord.value.checkIn && editingRecord.value.checkOut) {
      const checkIn = new Date(`2025-01-01 ${editingRecord.value.checkIn}`)
      const checkOut = new Date(`2025-01-01 ${editingRecord.value.checkOut}`)
      const diffMs = checkOut - checkIn
      const diffHours = (diffMs / (1000 * 60 * 60)).toFixed(1)
      editingRecord.value.hoursWorked = diffHours
    }
    
    // Update the record
    attendanceRecords.value[index] = { ...editingRecord.value }
    
    // Close modal
    closeEditModal()
    
    // Show success message
    alert(`✅ Attendance updated for ${editingRecord.value.employeeName}!`)
  }
}

const downloadReport = () => {
  alert('✅ Downloading attendance report...\n\nExcel report with attendance data for selected period is being generated. Check your downloads folder.')
}
</script>