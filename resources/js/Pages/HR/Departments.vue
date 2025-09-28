<template>
  <Head title="HR - Departments" />
  
  <AppLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          🏛️ Department Management
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
          <button @click="showCreateModal = true" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            🏗️ Add New Department
          </button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Department Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <div class="bg-blue-500 text-white p-6 rounded-lg shadow-lg">
            <div class="text-2xl font-bold">{{ departments.length }}</div>
            <div>Total Departments</div>
          </div>
          <div class="bg-green-500 text-white p-6 rounded-lg shadow-lg">
            <div class="text-2xl font-bold">{{ activeDepartments }}</div>
            <div>Active</div>
          </div>
          <div class="bg-purple-500 text-white p-6 rounded-lg shadow-lg">
            <div class="text-2xl font-bold">{{ totalEmployees }}</div>
            <div>Total Employees</div>
          </div>
          <div class="bg-orange-500 text-white p-6 rounded-lg shadow-lg">
            <div class="text-2xl font-bold">{{ averageSize }}</div>
            <div>Avg Dept Size</div>
          </div>
        </div>

        <!-- Department Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="department in departments" 
            :key="department.id" 
            class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition-shadow duration-300"
          >
            <!-- Department Header -->
            <div :class="department.color" class="px-6 py-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <span class="text-2xl mr-3">{{ department.icon }}</span>
                  <h3 class="text-lg font-bold text-white">{{ department.name }}</h3>
                </div>
                <div class="flex space-x-2">
                  <button @click="editDepartment(department)" class="text-white hover:text-gray-200">
                    ✏️
                  </button>
                  <button @click="deleteDepartment(department)" class="text-white hover:text-gray-200">
                    🗑️
                  </button>
                </div>
              </div>
            </div>

            <!-- Department Info -->
            <div class="px-6 py-4">
              <div class="space-y-3">
                <div>
                  <div class="text-sm text-gray-500">Manager</div>
                  <div class="font-medium">{{ department.manager || 'Not Assigned' }}</div>
                </div>
                
                <div>
                  <div class="text-sm text-gray-500">Employees</div>
                  <div class="flex items-center">
                    <span class="font-medium mr-2">{{ department.employeeCount }}</span>
                    <span class="text-sm text-gray-400">employees</span>
                  </div>
                </div>
                
                <div>
                  <div class="text-sm text-gray-500">Budget</div>
                  <div class="font-medium text-green-600">{{ formatCurrency(department.budget) }}</div>
                </div>
                
                <div>
                  <div class="text-sm text-gray-500">Status</div>
                  <span :class="getStatusClass(department.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                    {{ department.status }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Department Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t">
              <div class="flex justify-between items-center">
                <button 
                  @click="viewDepartmentDetails(department)" 
                  class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                >
                  📋 View Details
                </button>
                <button 
                  @click="manageDepartmentEmployees(department)" 
                  class="text-green-600 hover:text-green-800 text-sm font-medium"
                >
                  👥 Manage Team
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Department Hierarchy -->
        <div class="mt-8 bg-white overflow-hidden shadow-lg sm:rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">🏗️ Organization Structure</h3>
          </div>
          
          <div class="p-6">
            <div class="text-center">
              <div class="inline-block bg-blue-100 rounded-lg p-4 mb-6">
                <div class="text-lg font-bold text-blue-900">Executive Level</div>
                <div class="text-sm text-blue-700">CEO & C-Suite</div>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div v-for="dept in mainDepartments" :key="dept.id" class="text-center">
                <div class="bg-gray-100 rounded-lg p-4 mb-2">
                  <div class="text-lg font-bold text-gray-900">{{ dept.name }}</div>
                  <div class="text-sm text-gray-600">{{ dept.employeeCount }} employees</div>
                </div>
                <div v-if="dept.subDepartments" class="space-y-2">
                  <div v-for="sub in dept.subDepartments" :key="sub" class="bg-gray-50 rounded p-2 text-sm">
                    {{ sub }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Department Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">🏗️ Create New Department</h3>
            <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="createDepartment" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Department Name *</label>
              <input v-model="newDepartment.name" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Icon</label>
              <select v-model="newDepartment.icon" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                <option value="🏛️">🏛️ Government</option>
                <option value="💻">💻 Technology</option>
                <option value="💰">💰 Finance</option>
                <option value="📈">📈 Sales</option>
                <option value="🎨">🎨 Design</option>
                <option value="⚙️">⚙️ Operations</option>
                <option value="👥">👥 HR</option>
                <option value="📊">📊 Analytics</option>
                <option value="🔬">🔬 Research</option>
                <option value="🛡️">🛡️ Security</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Manager</label>
              <input v-model="newDepartment.manager" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500" placeholder="Manager Name">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Annual Budget</label>
              <input v-model="newDepartment.budget" type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500" placeholder="100000">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Color Theme</label>
              <select v-model="newDepartment.color" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                <option value="bg-blue-600">Blue</option>
                <option value="bg-green-600">Green</option>
                <option value="bg-purple-600">Purple</option>
                <option value="bg-red-600">Red</option>
                <option value="bg-yellow-600">Yellow</option>
                <option value="bg-indigo-600">Indigo</option>
                <option value="bg-pink-600">Pink</option>
                <option value="bg-teal-600">Teal</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Status</label>
              <select v-model="newDepartment.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="pending">Pending</option>
              </select>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
              <button type="button" @click="showCreateModal = false" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">
                Cancel
              </button>
              <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                🏗️ Create Department
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Edit Department Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">✏️ Edit Department</h3>
            <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="updateDepartment" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Department Name *</label>
              <input v-model="editingDepartment.name" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Icon</label>
              <select v-model="editingDepartment.icon" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                <option value="🏛️">🏛️ Government</option>
                <option value="💻">💻 Technology</option>
                <option value="💰">💰 Finance</option>
                <option value="📈">📈 Sales</option>
                <option value="🎨">🎨 Design</option>
                <option value="⚙️">⚙️ Operations</option>
                <option value="👥">👥 HR</option>
                <option value="📊">📊 Analytics</option>
                <option value="🔬">🔬 Research</option>
                <option value="🛡️">🛡️ Security</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Manager</label>
              <input v-model="editingDepartment.manager" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Annual Budget</label>
              <input v-model="editingDepartment.budget" type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Color Theme</label>
              <select v-model="editingDepartment.color" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                <option value="bg-blue-600">Blue</option>
                <option value="bg-green-600">Green</option>
                <option value="bg-purple-600">Purple</option>
                <option value="bg-red-600">Red</option>
                <option value="bg-yellow-600">Yellow</option>
                <option value="bg-indigo-600">Indigo</option>
                <option value="bg-pink-600">Pink</option>
                <option value="bg-teal-600">Teal</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Status</label>
              <select v-model="editingDepartment.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="pending">Pending</option>
              </select>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
              <button type="button" @click="showEditModal = false" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">
                Cancel
              </button>
              <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                ✏️ Update Department
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Employee Management Modal -->
    <div v-if="showEmployeeManagement" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">👥 {{ selectedDepartment?.name }} Team Management</h3>
            <button @click="closeEmployeeManagement" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <div class="mb-4">
            <div class="flex justify-between items-center">
              <p class="text-sm text-gray-600">Managing {{ getDepartmentEmployees(selectedDepartment?.id).length }} employees in {{ selectedDepartment?.name }}</p>
              <div class="flex space-x-2">
                <Link :href="route('hr.teams')" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                  👥 Go to Teams
                </Link>
                <button @click="showAddEmployeeDialog" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                  ➕ Add Employee
                </button>
              </div>
            </div>
          </div>

          <div class="max-h-96 overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salary</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hire Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="employee in getDepartmentEmployees(selectedDepartment?.id)" :key="employee.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                          <span class="text-sm font-medium text-gray-700">{{ employee.name.split(' ').map(n => n[0]).join('') }}</span>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ employee.name }}</div>
                        <div class="text-sm text-gray-500">{{ employee.email }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ employee.role }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ employee.salary.toLocaleString() }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ new Date(employee.hireDate).toLocaleDateString() }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                    <button class="text-red-600 hover:text-red-900">Remove</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Department Details Modal -->
    <div v-if="showDepartmentDetails" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-3xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">📋 {{ selectedDepartment?.name }} Details</h3>
            <button @click="closeDepartmentDetails" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Department Info -->
            <div class="space-y-4">
              <div>
                <h4 class="text-sm font-medium text-gray-900">Department Information</h4>
                <div class="mt-2 space-y-2">
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Manager:</span>
                    <span class="text-sm font-medium">{{ selectedDepartment?.manager || 'Not Assigned' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Status:</span>
                    <span :class="getStatusClass(selectedDepartment?.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                      {{ selectedDepartment?.status }}
                    </span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Total Employees:</span>
                    <span class="text-sm font-medium">{{ getDepartmentEmployees(selectedDepartment?.id).length }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Annual Budget:</span>
                    <span class="text-sm font-medium text-green-600">{{ formatCurrency(selectedDepartment?.budget) }}</span>
                  </div>
                </div>
              </div>

              <!-- Sub-departments -->
              <div v-if="selectedDepartment?.subDepartments">
                <h4 class="text-sm font-medium text-gray-900">Sub-departments</h4>
                <div class="mt-2 space-y-1">
                  <div v-for="sub in selectedDepartment.subDepartments" :key="sub" class="text-sm text-gray-600">
                    • {{ sub }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Team Overview -->
            <div>
              <h4 class="text-sm font-medium text-gray-900">Team Overview</h4>
              <div class="mt-2 space-y-2">
                <div v-for="employee in getDepartmentEmployees(selectedDepartment?.id).slice(0, 5)" :key="employee.id" class="flex items-center space-x-3">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                      <span class="text-xs font-medium text-gray-700">{{ employee.name.split(' ').map(n => n[0]).join('') }}</span>
                    </div>
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">{{ employee.name }}</p>
                    <p class="text-xs text-gray-500">{{ employee.role }}</p>
                  </div>
                </div>
                <div v-if="getDepartmentEmployees(selectedDepartment?.id).length > 5" class="text-xs text-gray-500 mt-2">
                  ... and {{ getDepartmentEmployees(selectedDepartment?.id).length - 5 }} more employees
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <button @click="closeDepartmentDetails" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">
              Close
            </button>
            <Link :href="route('hr.teams')" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
              👥 Manage Teams
            </Link>
            <button @click="manageDepartmentEmployees(selectedDepartment)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
              👥 Manage Employees
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Employee Modal -->
    <div v-if="showAddEmployeeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">➕ Add Employee to {{ selectedDepartment?.name }}</h3>
            <button @click="closeAddEmployeeModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="addEmployeeToDepartment">
            <div class="mb-4">
              <label class="block text-gray-700 text-sm font-bold mb-2">
                Employee Name
              </label>
              <input 
                v-model="newEmployee.name" 
                type="text" 
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                placeholder="Enter employee name"
                required
              >
            </div>

            <div class="mb-4">
              <label class="block text-gray-700 text-sm font-bold mb-2">
                Role
              </label>
              <input 
                v-model="newEmployee.role" 
                type="text" 
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                placeholder="Enter role"
                required
              >
            </div>

            <div class="mb-4">
              <label class="block text-gray-700 text-sm font-bold mb-2">
                Email
              </label>
              <input 
                v-model="newEmployee.email" 
                type="email" 
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                placeholder="Enter email address"
                required
              >
            </div>

            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">
                Salary
              </label>
              <input 
                v-model="newEmployee.salary" 
                type="number" 
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                placeholder="Enter salary"
                required
              >
            </div>

            <div class="flex justify-end space-x-3">
              <button 
                type="button" 
                @click="closeAddEmployeeModal" 
                class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded"
              >
                Cancel
              </button>
              <button 
                type="submit" 
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
              >
                Add Employee
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
import { ref, computed } from 'vue'

// Modal states
const showDepartmentDetails = ref(false)
const showEmployeeManagement = ref(false)
const showEditModal = ref(false)
const showAddEmployeeModal = ref(false)
const selectedDepartment = ref(null)

// Department being edited
const editingDepartment = ref(null)

// New employee form
const newEmployee = ref({
  name: '',
  email: '',
  role: '',
  salary: ''
})

// Employee data with department assignments
const employees = ref([
  // Engineering Department
  { id: 1, name: 'John Smith', email: 'john.smith@company.com', departmentId: 1, role: 'Engineering Manager', salary: 120000, hireDate: '2020-01-15', status: 'active' },
  { id: 2, name: 'Sarah Johnson', email: 'sarah.johnson@company.com', departmentId: 1, role: 'Senior Frontend Developer', salary: 95000, hireDate: '2021-03-10', status: 'active' },
  { id: 3, name: 'Mike Chen', email: 'mike.chen@company.com', departmentId: 1, role: 'Backend Developer', salary: 85000, hireDate: '2021-07-20', status: 'active' },
  { id: 4, name: 'Lisa Wang', email: 'lisa.wang@company.com', departmentId: 1, role: 'DevOps Engineer', salary: 90000, hireDate: '2020-11-05', status: 'active' },
  { id: 5, name: 'Tom Anderson', email: 'tom.anderson@company.com', departmentId: 1, role: 'QA Engineer', salary: 75000, hireDate: '2022-02-14', status: 'active' },
  
  // Marketing Department
  { id: 6, name: 'Emily Davis', email: 'emily.davis@company.com', departmentId: 2, role: 'Marketing Manager', salary: 85000, hireDate: '2019-05-12', status: 'active' },
  { id: 7, name: 'James Wilson', email: 'james.wilson@company.com', departmentId: 2, role: 'Digital Marketing Specialist', salary: 65000, hireDate: '2021-09-08', status: 'active' },
  { id: 8, name: 'Anna Rodriguez', email: 'anna.rodriguez@company.com', departmentId: 2, role: 'Content Manager', salary: 70000, hireDate: '2020-12-03', status: 'active' },
  
  // Sales Department
  { id: 9, name: 'Robert Taylor', email: 'robert.taylor@company.com', departmentId: 3, role: 'Sales Manager', salary: 95000, hireDate: '2018-08-22', status: 'active' },
  { id: 10, name: 'Jessica Martinez', email: 'jessica.martinez@company.com', departmentId: 3, role: 'Sales Representative', salary: 55000, hireDate: '2021-01-18', status: 'active' },
  { id: 11, name: 'Chris Brown', email: 'chris.brown@company.com', departmentId: 3, role: 'Account Executive', salary: 70000, hireDate: '2020-06-15', status: 'active' },
  
  // HR Department
  { id: 12, name: 'Karen Johnson', email: 'karen.johnson@company.com', departmentId: 4, role: 'HR Manager', salary: 80000, hireDate: '2019-02-20', status: 'active' },
  { id: 13, name: 'Daniel Kim', email: 'daniel.kim@company.com', departmentId: 4, role: 'HR Specialist', salary: 60000, hireDate: '2021-11-10', status: 'active' },
  
  // Finance Department
  { id: 14, name: 'Tom Brown', email: 'tom.brown@company.com', departmentId: 5, role: 'Finance Manager', salary: 90000, hireDate: '2018-03-15', status: 'active' },
  { id: 15, name: 'Sophie Green', email: 'sophie.green@company.com', departmentId: 5, role: 'Accountant', salary: 65000, hireDate: '2020-07-08', status: 'active' },
  { id: 16, name: 'Mark Davis', email: 'mark.davis@company.com', departmentId: 5, role: 'Financial Analyst', salary: 70000, hireDate: '2021-04-12', status: 'active' }
])

// Demo department data
const departments = ref([
  {
    id: 1,
    name: 'Engineering',
    icon: '💻',
    color: 'bg-blue-600',
    manager: 'John Smith',
    employeeCount: 25,
    budget: 500000,
    status: 'active',
    subDepartments: ['Frontend', 'Backend', 'DevOps', 'QA']
  },
  {
    id: 2,
    name: 'Marketing',
    icon: '📈',
    color: 'bg-green-600',
    manager: 'Sarah Johnson',
    employeeCount: 12,
    budget: 200000,
    status: 'active',
    subDepartments: ['Digital Marketing', 'Content', 'Social Media']
  },
  {
    id: 3,
    name: 'Sales',
    icon: '💼',
    color: 'bg-purple-600',
    manager: 'Mike Wilson',
    employeeCount: 18,
    budget: 300000,
    status: 'active',
    subDepartments: ['Inside Sales', 'Enterprise Sales', 'Customer Success']
  },
  {
    id: 4,
    name: 'Human Resources',
    icon: '👥',
    color: 'bg-orange-600',
    manager: 'Lisa Davis',
    employeeCount: 8,
    budget: 150000,
    status: 'active',
    subDepartments: ['Recruiting', 'Benefits', 'Training']
  },
  {
    id: 5,
    name: 'Finance',
    icon: '💰',
    color: 'bg-indigo-600',
    manager: 'Tom Brown',
    employeeCount: 10,
    budget: 180000,
    status: 'active',
    subDepartments: ['Accounting', 'Financial Planning', 'Payroll']
  },
  {
    id: 6,
    name: 'Operations',
    icon: '⚙️',
    color: 'bg-red-600',
    manager: 'Alex Garcia',
    employeeCount: 15,
    budget: 250000,
    status: 'active',
    subDepartments: ['IT Support', 'Facilities', 'Procurement']
  },
  {
    id: 7,
    name: 'Design',
    icon: '🎨',
    color: 'bg-pink-600',
    manager: 'Emma Wilson',
    employeeCount: 8,
    budget: 120000,
    status: 'active',
    subDepartments: ['UI/UX', 'Graphic Design', 'Brand']
  },
  {
    id: 8,
    name: 'Research & Development',
    icon: '🔬',
    color: 'bg-teal-600',
    manager: 'David Lee',
    employeeCount: 12,
    budget: 400000,
    status: 'active',
    subDepartments: ['Product Research', 'Innovation Lab', 'Data Science']
  }
])

// Modal state
const showCreateModal = ref(false)

// New department form data
const newDepartment = ref({
  name: '',
  icon: '🏛️',
  manager: '',
  budget: '',
  color: 'bg-blue-600',
  status: 'active'
})

// Computed properties
const activeDepartments = computed(() => {
  return departments.value.filter(dept => dept.status === 'active').length
})

const totalEmployees = computed(() => {
  return departments.value.reduce((sum, dept) => sum + dept.employeeCount, 0)
})

const averageSize = computed(() => {
  const avg = totalEmployees.value / departments.value.length
  return Math.round(avg)
})

const mainDepartments = computed(() => {
  return departments.value.slice(0, 4) // Show first 4 for hierarchy display
})

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const getStatusClass = (status) => {
  const classes = {
    'active': 'bg-green-100 text-green-800',
    'inactive': 'bg-red-100 text-red-800',
    'pending': 'bg-yellow-100 text-yellow-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const viewDepartmentDetails = (department) => {
  selectedDepartment.value = department
  showDepartmentDetails.value = true
}

const manageDepartmentEmployees = (department) => {
  selectedDepartment.value = department
  showEmployeeManagement.value = true
}

// Get employees for a specific department
const getDepartmentEmployees = (departmentId) => {
  return employees.value.filter(emp => emp.departmentId === departmentId)
}

// Close modals
const closeDepartmentDetails = () => {
  showDepartmentDetails.value = false
  selectedDepartment.value = null
}

const closeEmployeeManagement = () => {
  showEmployeeManagement.value = false
  selectedDepartment.value = null
}

const showAddEmployeeDialog = () => {
  showAddEmployeeModal.value = true
}

const closeAddEmployeeModal = () => {
  showAddEmployeeModal.value = false
  // Reset form
  newEmployee.value = {
    name: '',
    email: '',
    role: '',
    salary: ''
  }
}

const addEmployeeToDepartment = () => {
  // Generate new employee ID
  const newId = Math.max(...employees.value.map(emp => emp.id)) + 1
  
  const employeeToAdd = {
    id: newId,
    name: newEmployee.value.name,
    email: newEmployee.value.email,
    role: newEmployee.value.role,
    salary: parseInt(newEmployee.value.salary),
    departmentId: selectedDepartment.value.id,
    hireDate: new Date().toISOString().split('T')[0],
    status: 'active'
  }
  
  // Add to employees list
  employees.value.push(employeeToAdd)
  
  // Update department employee count
  const departmentIndex = departments.value.findIndex(dept => dept.id === selectedDepartment.value.id)
  if (departmentIndex !== -1) {
    departments.value[departmentIndex].employeeCount++
  }
  
  // Close modal and reset form
  closeAddEmployeeModal()
  
  // Show success message
  alert(`✅ Employee "${employeeToAdd.name}" has been added to ${selectedDepartment.value.name} successfully!`)
}

const editDepartment = (department) => {
  // Create a copy for editing
  editingDepartment.value = { ...department }
  showEditModal.value = true
}

const deleteDepartment = (department) => {
  if (confirm(`Are you sure you want to delete the ${department.name} department?`)) {
    departments.value = departments.value.filter(dept => dept.id !== department.id)
  }
}

// Create new department
const createDepartment = () => {
  // Generate new ID
  const newId = Math.max(...departments.value.map(dept => dept.id)) + 1
  
  // Create new department object
  const departmentToAdd = {
    id: newId,
    name: newDepartment.value.name,
    icon: newDepartment.value.icon,
    color: newDepartment.value.color,
    manager: newDepartment.value.manager || 'Not Assigned',
    employeeCount: 0, // New department starts with 0 employees
    budget: newDepartment.value.budget ? parseInt(newDepartment.value.budget) : 0,
    status: newDepartment.value.status,
    subDepartments: [] // Empty array for new departments
  }
  
  // Add to departments list
  departments.value.push(departmentToAdd)
  
  // Reset form
  newDepartment.value = {
    name: '',
    icon: '🏛️',
    manager: '',
    budget: '',
    color: 'bg-blue-600',
    status: 'active'
  }
  
  // Close modal
  showCreateModal.value = false
  
  // Show success message
  alert(`✅ Department "${departmentToAdd.name}" has been created successfully!`)
}

// Update existing department
const updateDepartment = () => {
  // Find the department index in the array
  const index = departments.value.findIndex(dept => dept.id === editingDepartment.value.id)
  
  if (index !== -1) {
    // Update the department
    departments.value[index] = { ...editingDepartment.value }
    
    // Close modal
    showEditModal.value = false
    
    // Clear editing department
    editingDepartment.value = null
    
    // Show success message
    alert(`✅ Department "${departments.value[index].name}" has been updated successfully!`)
  }
}
</script>