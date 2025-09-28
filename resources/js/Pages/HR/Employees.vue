<template>
  <Head title="HR - Employees" />
  
  <AppLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          👥 Employee Management
        </h2>
        <div class="flex space-x-4">
          <a href="/hr-vue" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            🏠 Dashboard
          </a>
          <a href="/hr-vue/departments" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            🏛️ Departments
          </a>
          <a href="/hr-vue/teams" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
            🏆 Teams
          </a>
          <button @click="showCreateModal = true" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            ➕ Add New Employee
          </button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Filters and Search -->
        <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg mb-6">
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Employees</label>
                <input 
                  v-model="searchTerm"
                  type="text" 
                  placeholder="Search by name or email..."
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
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="on-leave">On Leave</option>
                </select>
              </div>
              
              <div class="flex items-end">
                <button @click="resetFilters" class="w-full bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                  🔄 Reset Filters
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Employee Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <div class="bg-blue-500 text-white p-6 rounded-lg shadow-lg">
            <div class="text-2xl font-bold">{{ filteredEmployees.length }}</div>
            <div>Total Employees</div>
          </div>
          <div class="bg-green-500 text-white p-6 rounded-lg shadow-lg">
            <div class="text-2xl font-bold">{{ activeEmployees }}</div>
            <div>Active</div>
          </div>
          <div class="bg-yellow-500 text-white p-6 rounded-lg shadow-lg">
            <div class="text-2xl font-bold">{{ onLeaveEmployees }}</div>
            <div>On Leave</div>
          </div>
          <div class="bg-red-500 text-white p-6 rounded-lg shadow-lg">
            <div class="text-2xl font-bold">{{ inactiveEmployees }}</div>
            <div>Inactive</div>
          </div>
        </div>

        <!-- Employee Table -->
        <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">👥 Employee Directory</h3>
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
                    Position
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Hire Date
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="employee in paginatedEmployees" :key="employee.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                          <span class="text-sm font-medium text-gray-700">{{ getInitials(employee.name) }}</span>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ employee.name }}</div>
                        <div class="text-sm text-gray-500">{{ employee.email }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ employee.department }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ employee.position }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusClass(employee.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ employee.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(employee.hireDate) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <button @click="viewEmployee(employee)" class="text-blue-600 hover:text-blue-900">👁️ View</button>
                    <button @click="editEmployee(employee)" class="text-green-600 hover:text-green-900">✏️ Edit</button>
                    <button @click="deleteEmployee(employee)" class="text-red-600 hover:text-red-900">🗑️ Delete</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex justify-between items-center">
              <div>
                <p class="text-sm text-gray-700">
                  Showing {{ ((currentPage - 1) * perPage) + 1 }} to {{ Math.min(currentPage * perPage, filteredEmployees.length) }} of {{ filteredEmployees.length }} employees
                </p>
              </div>
              <div class="flex space-x-2">
                <button 
                  @click="currentPage = Math.max(1, currentPage - 1)"
                  :disabled="currentPage === 1"
                  class="px-3 py-1 rounded border text-sm"
                  :class="currentPage === 1 ? 'bg-gray-100 text-gray-400' : 'bg-white text-gray-700 hover:bg-gray-50'"
                >
                  Previous
                </button>
                <span class="px-3 py-1 text-sm text-gray-700">
                  Page {{ currentPage }} of {{ totalPages }}
                </span>
                <button 
                  @click="currentPage = Math.min(totalPages, currentPage + 1)"
                  :disabled="currentPage === totalPages"
                  class="px-3 py-1 rounded border text-sm"
                  :class="currentPage === totalPages ? 'bg-gray-100 text-gray-400' : 'bg-white text-gray-700 hover:bg-gray-50'"
                >
                  Next
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Employee Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">➕ Add New Employee</h3>
            <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="createEmployee" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Personal Information -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                <input v-model="newEmployee.name" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Email Address *</label>
                <input v-model="newEmployee.email" type="email" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Position *</label>
                <input v-model="newEmployee.position" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Department *</label>
                <select v-model="newEmployee.department" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                  <option value="">Select Department</option>
                  <option v-for="dept in availableDepartments" :key="dept" :value="dept">{{ dept }}</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Salary</label>
                <input v-model="newEmployee.salary" type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="50000">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Hire Date *</label>
                <input v-model="newEmployee.hireDate" type="date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <input v-model="newEmployee.phone" type="tel" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="+1 (555) 123-4567">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select v-model="newEmployee.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="on-leave">On Leave</option>
                </select>
              </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
              <button type="button" @click="showCreateModal = false" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">
                Cancel
              </button>
              <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                ➕ Create Employee
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Edit Employee Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">✏️ Edit Employee</h3>
            <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="updateEmployee" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Personal Information -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                <input v-model="editingEmployee.name" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Email Address *</label>
                <input v-model="editingEmployee.email" type="email" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Position *</label>
                <input v-model="editingEmployee.position" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Department *</label>
                <select v-model="editingEmployee.department" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                  <option value="">Select Department</option>
                  <option v-for="dept in availableDepartments" :key="dept" :value="dept">{{ dept }}</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Salary</label>
                <input v-model="editingEmployee.salary" type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Hire Date *</label>
                <input v-model="editingEmployee.hireDate" type="date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <input v-model="editingEmployee.phone" type="tel" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select v-model="editingEmployee.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="on-leave">On Leave</option>
                </select>
              </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
              <button type="button" @click="showEditModal = false" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">
                Cancel
              </button>
              <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                ✏️ Update Employee
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Employee Details Modal -->
    <div v-if="showEmployeeDetails" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">👤 {{ selectedEmployee?.name }} - Employee Details</h3>
            <button @click="closeEmployeeDetails" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Personal Information -->
            <div class="space-y-4">
              <div>
                <h4 class="text-sm font-medium text-gray-900">Personal Information</h4>
                <div class="mt-2 space-y-2">
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Full Name:</span>
                    <span class="text-sm font-medium">{{ selectedEmployee?.name }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Email:</span>
                    <span class="text-sm font-medium">{{ selectedEmployee?.email }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Employee ID:</span>
                    <span class="text-sm font-medium">EMP-{{ String(selectedEmployee?.id).padStart(4, '0') }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Status:</span>
                    <span :class="selectedEmployee?.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 text-xs font-semibold rounded-full">
                      {{ selectedEmployee?.status }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Employment Details -->
            <div class="space-y-4">
              <div>
                <h4 class="text-sm font-medium text-gray-900">Employment Details</h4>
                <div class="mt-2 space-y-2">
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Position:</span>
                    <span class="text-sm font-medium">{{ selectedEmployee?.position }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Department:</span>
                    <span class="text-sm font-medium">{{ selectedEmployee?.department }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Hire Date:</span>
                    <span class="text-sm font-medium">{{ new Date(selectedEmployee?.hireDate || Date.now()).toLocaleDateString() }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Salary:</span>
                    <span class="text-sm font-medium text-green-600">${{ selectedEmployee?.salary?.toLocaleString() || 'Not Disclosed' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <button @click="closeEmployeeDetails" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">
              Close
            </button>
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
              ✏️ Edit Employee
            </button>
          </div>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed } from 'vue'

// Demo employee data
const employees = ref([
  { id: 1, name: 'John Doe', email: 'john.doe@company.com', department: 'Engineering', position: 'Senior Developer', status: 'active', hireDate: '2022-03-15' },
  { id: 2, name: 'Jane Smith', email: 'jane.smith@company.com', department: 'Marketing', position: 'Marketing Manager', status: 'active', hireDate: '2021-08-20' },
  { id: 3, name: 'Mike Johnson', email: 'mike.j@company.com', department: 'Sales', position: 'Sales Representative', status: 'on-leave', hireDate: '2023-01-10' },
  { id: 4, name: 'Sarah Wilson', email: 'sarah.w@company.com', department: 'HR', position: 'HR Specialist', status: 'active', hireDate: '2020-11-05' },
  { id: 5, name: 'Tom Brown', email: 'tom.brown@company.com', department: 'Finance', position: 'Financial Analyst', status: 'inactive', hireDate: '2019-06-12' },
  { id: 6, name: 'Lisa Davis', email: 'lisa.d@company.com', department: 'Engineering', position: 'Product Manager', status: 'active', hireDate: '2022-09-01' },
  { id: 7, name: 'Alex Johnson', email: 'alex.j@company.com', department: 'Marketing', position: 'Content Creator', status: 'active', hireDate: '2023-04-18' },
  { id: 8, name: 'Maria Garcia', email: 'maria.g@company.com', department: 'Sales', position: 'Account Manager', status: 'active', hireDate: '2021-12-03' }
])

// Filter states
const searchTerm = ref('')
const selectedDepartment = ref('')
const selectedStatus = ref('')

// Pagination
const currentPage = ref(1)
const perPage = ref(5)

// Modal state
const showCreateModal = ref(false)

// New employee form data
const newEmployee = ref({
  name: '',
  email: '',
  position: '',
  department: '',
  salary: '',
  hireDate: new Date().toISOString().split('T')[0], // Today's date
  phone: '',
  status: 'active'
})

// Available departments
const availableDepartments = ['Engineering', 'Marketing', 'Sales', 'HR', 'Finance', 'Operations', 'Design', 'R&D']

// Computed properties
const filteredEmployees = computed(() => {
  return employees.value.filter(employee => {
    const matchesSearch = employee.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
                         employee.email.toLowerCase().includes(searchTerm.value.toLowerCase())
    const matchesDepartment = !selectedDepartment.value || employee.department === selectedDepartment.value
    const matchesStatus = !selectedStatus.value || employee.status === selectedStatus.value
    
    return matchesSearch && matchesDepartment && matchesStatus
  })
})

const paginatedEmployees = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredEmployees.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredEmployees.value.length / perPage.value)
})

const activeEmployees = computed(() => {
  return filteredEmployees.value.filter(emp => emp.status === 'active').length
})

const onLeaveEmployees = computed(() => {
  return filteredEmployees.value.filter(emp => emp.status === 'on-leave').length
})

const inactiveEmployees = computed(() => {
  return filteredEmployees.value.filter(emp => emp.status === 'inactive').length
})

// Methods
const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getStatusClass = (status) => {
  const classes = {
    'active': 'bg-green-100 text-green-800',
    'inactive': 'bg-red-100 text-red-800',
    'on-leave': 'bg-yellow-100 text-yellow-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

const resetFilters = () => {
  searchTerm.value = ''
  selectedDepartment.value = ''
  selectedStatus.value = ''
  currentPage.value = 1
}

// Modal states
const showEmployeeDetails = ref(false)
const showEditModal = ref(false)
const selectedEmployee = ref(null)

// Employee being edited
const editingEmployee = ref(null)

const viewEmployee = (employee) => {
  selectedEmployee.value = employee
  showEmployeeDetails.value = true
}

const editEmployee = (employee) => {
  // Create a copy for editing
  editingEmployee.value = { ...employee }
  showEditModal.value = true
}

const closeEmployeeDetails = () => {
  showEmployeeDetails.value = false
  selectedEmployee.value = null
}

const deleteEmployee = (employee) => {
  if (confirm(`Are you sure you want to delete ${employee.name}?`)) {
    employees.value = employees.value.filter(emp => emp.id !== employee.id)
  }
}

// Create new employee
const createEmployee = () => {
  // Generate new ID
  const newId = Math.max(...employees.value.map(emp => emp.id)) + 1
  
  // Create new employee object
  const employeeToAdd = {
    id: newId,
    name: newEmployee.value.name,
    email: newEmployee.value.email,
    department: newEmployee.value.department,
    position: newEmployee.value.position,
    status: newEmployee.value.status,
    hireDate: newEmployee.value.hireDate,
    salary: newEmployee.value.salary ? parseInt(newEmployee.value.salary) : null,
    phone: newEmployee.value.phone
  }
  
  // Add to employees list
  employees.value.push(employeeToAdd)
  
  // Reset form
  newEmployee.value = {
    name: '',
    email: '',
    position: '',
    department: '',
    salary: '',
    hireDate: new Date().toISOString().split('T')[0],
    phone: '',
    status: 'active'
  }
  
  // Close modal
  showCreateModal.value = false
  
  // Show success message
  alert(`✅ Employee "${employeeToAdd.name}" has been created successfully!`)
}

// Update existing employee
const updateEmployee = () => {
  // Find the employee index in the array
  const index = employees.value.findIndex(emp => emp.id === editingEmployee.value.id)
  
  if (index !== -1) {
    // Update the employee
    employees.value[index] = { ...editingEmployee.value }
    
    // Close modal
    showEditModal.value = false
    
    // Clear editing employee
    editingEmployee.value = null
    
    // Show success message
    alert(`✅ Employee "${employees.value[index].name}" has been updated successfully!`)
  }
}
</script>