<template>
  <Head title="HR Management - Teams" />
  
  <AppLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          🏆 Team Management
        </h2>
        <div class="flex space-x-4">
          <a href="/hr-vue/" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            🏠 Dashboard
          </a>
          <a href="/hr-vue/employees" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            👥 Employees
          </a>
          <a href="/hr-vue/departments" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            🏛️ Departments
          </a>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Teams Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl text-purple-500">🏆</div>
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ teamsStats.totalTeams }}</div>
                  <div class="text-gray-500">Active Teams</div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl text-green-500">👑</div>
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ teamsStats.totalLeads }}</div>
                  <div class="text-gray-500">Team Leads</div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl text-blue-500">👥</div>
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ teamsStats.totalMembers }}</div>
                  <div class="text-gray-500">Team Members</div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl text-yellow-500">📊</div>
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ teamsStats.avgPerformance }}%</div>
                  <div class="text-gray-500">Avg Performance</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="mb-6">
          <div class="flex space-x-4">
            <button
              @click="showCreateTeamModal = true"
              class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded flex items-center"
            >
              <span class="mr-2">➕</span> Create Team
            </button>
            <button
              @click="refreshData"
              class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded flex items-center"
            >
              <span class="mr-2">🔄</span> Refresh
            </button>
            <select 
              v-model="filterType" 
              class="border border-gray-300 rounded-md px-4 py-2"
              @change="filterTeams"
            >
              <option value="all">All Teams</option>
              <option value="active">Active Teams</option>
              <option value="on-hold">On Hold</option>
              <option value="completed">Completed</option>
            </select>
          </div>
        </div>

        <!-- Teams Table -->
        <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">🏆 Teams Directory</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team Lead</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Members</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="team in filteredTeams" :key="team.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="text-2xl mr-3">{{ team.icon }}</div>
                      <div>
                        <div class="text-sm font-medium text-gray-900">{{ team.name }}</div>
                        <div class="text-sm text-gray-500">{{ team.description }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="text-xl mr-2">👑</div>
                      <div>
                        <div class="text-sm font-medium text-gray-900">{{ team.lead?.name || 'No Lead' }}</div>
                        <div class="text-sm text-gray-500">{{ team.lead?.role || '-' }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <span class="text-2xl mr-2">👥</span>
                      <div>
                        <div class="text-sm font-medium text-gray-900">{{ team.members?.length || 0 }} members</div>
                        <div class="text-sm text-gray-500" v-if="team.members && team.members.length > 0">
                          {{ team.members.slice(0, 2).map(m => m.name).join(', ') }}
                          <span v-if="team.members.length > 2">... +{{ team.members.length - 2 }} more</span>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ team.department }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span 
                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="getStatusClass(team.status)"
                    >
                      {{ team.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                        <div 
                          class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                          :style="`width: ${team.performance || 0}%`"
                        ></div>
                      </div>
                      <span class="text-sm font-medium text-gray-900">{{ team.performance || 0 }}%</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                      <button
                        @click="viewTeam(team)"
                        class="bg-blue-500 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs"
                      >
                        👁️ View
                      </button>
                      <button
                        @click="editTeam(team)"
                        class="bg-green-500 hover:bg-green-700 text-white px-3 py-1 rounded text-xs"
                      >
                        ✏️ Edit
                      </button>
                      <button
                        @click="deleteTeam(team)"
                        class="bg-red-500 hover:bg-red-700 text-white px-3 py-1 rounded text-xs"
                      >
                        🗑️ Delete
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Create Team Modal -->
        <div v-if="showCreateTeamModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">➕ Create New Team</h3>
                <button @click="showCreateTeamModal = false" class="text-gray-400 hover:text-gray-600">
                  <span class="text-2xl">&times;</span>
                </button>
              </div>
              
              <form @submit.prevent="createTeam" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Team Name *</label>
                    <input
                      v-model="newTeam.name"
                      type="text"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                      placeholder="e.g., Development Team Alpha"
                    />
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Team Icon</label>
                    <select v-model="newTeam.icon" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                      <option value="🏆">🏆 Trophy</option>
                      <option value="⚡">⚡ Lightning</option>
                      <option value="🚀">🚀 Rocket</option>
                      <option value="💎">💎 Diamond</option>
                      <option value="🔥">🔥 Fire</option>
                      <option value="🌟">🌟 Star</option>
                      <option value="🎯">🎯 Target</option>
                      <option value="💪">💪 Strength</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Description</label>
                  <textarea
                    v-model="newTeam.description"
                    rows="3"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                    placeholder="Brief description of the team's purpose and goals..."
                  ></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Department</label>
                    <select v-model="newTeam.department" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                      <option value="">Select Department</option>
                      <option value="Engineering">Engineering</option>
                      <option value="Marketing">Marketing</option>
                      <option value="Sales">Sales</option>
                      <option value="HR">Human Resources</option>
                      <option value="Finance">Finance</option>
                      <option value="Operations">Operations</option>
                      <option value="Design">Design</option>
                      <option value="Product">Product</option>
                    </select>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Team Lead</label>
                    <select v-model="newTeam.leadId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                      <option value="">Select Team Lead</option>
                      <option v-for="employee in availableEmployees" :key="employee.id" :value="employee.id">
                        {{ employee.name }} - {{ employee.role }}
                      </option>
                    </select>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Team Members</label>
                  <div class="mt-2 max-h-48 overflow-y-auto border border-gray-200 rounded-md p-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                      <label v-for="employee in availableEmployees" :key="employee.id" class="flex items-center p-2 hover:bg-gray-50 rounded">
                        <input
                          type="checkbox"
                          :value="employee.id"
                          v-model="newTeam.memberIds"
                          class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200"
                        />
                        <div class="ml-3">
                          <div class="text-sm font-medium text-gray-900">{{ employee.name }}</div>
                          <div class="text-xs text-gray-500">{{ employee.role }} - {{ employee.department }}</div>
                        </div>
                      </label>
                    </div>
                  </div>
                  <div class="mt-2 text-sm text-gray-500">
                    Selected: {{ newTeam.memberIds.length }} member{{ newTeam.memberIds.length !== 1 ? 's' : '' }}
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Team Type</label>
                    <select v-model="newTeam.type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                      <option value="project">Project Team</option>
                      <option value="permanent">Permanent Team</option>
                      <option value="cross-functional">Cross-Functional</option>
                      <option value="task-force">Task Force</option>
                    </select>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select v-model="newTeam.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                      <option value="active">Active</option>
                      <option value="forming">Forming</option>
                      <option value="on-hold">On Hold</option>
                    </select>
                  </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                  <button
                    type="button"
                    @click="showCreateTeamModal = false"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    :disabled="isCreating"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="isCreating || !newTeam.name.trim()"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
                  >
                    <span v-if="isCreating">Creating...</span>
                    <span v-else>Create Team</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- View Team Modal -->
        <div v-if="showViewTeamModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
              <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                  <span class="text-4xl mr-4">{{ selectedTeam?.icon }}</span>
                  <div>
                    <h3 class="text-2xl font-medium text-gray-900">{{ selectedTeam?.name }}</h3>
                    <p class="text-gray-600">{{ selectedTeam?.description }}</p>
                  </div>
                </div>
                <button @click="showViewTeamModal = false" class="text-gray-400 hover:text-gray-600">
                  <span class="text-2xl">&times;</span>
                </button>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Team Info -->
                <div class="lg:col-span-2">
                  <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h4 class="text-lg font-semibold mb-4">📋 Team Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="text-sm font-medium text-gray-600">Department:</label>
                        <p class="text-gray-900">{{ selectedTeam?.department }}</p>
                      </div>
                      <div>
                        <label class="text-sm font-medium text-gray-600">Status:</label>
                        <span :class="getStatusClass(selectedTeam?.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                          {{ selectedTeam?.status }}
                        </span>
                      </div>
                      <div>
                        <label class="text-sm font-medium text-gray-600">Team Type:</label>
                        <p class="text-gray-900">{{ selectedTeam?.type }}</p>
                      </div>
                      <div>
                        <label class="text-sm font-medium text-gray-600">Performance:</label>
                        <div class="flex items-center">
                          <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                            <div class="bg-blue-600 h-2 rounded-full" :style="`width: ${selectedTeam?.performance || 0}%`"></div>
                          </div>
                          <span class="text-sm">{{ selectedTeam?.performance || 0 }}%</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Team Members -->
                  <div class="bg-gray-50 rounded-lg p-6">
                    <h4 class="text-lg font-semibold mb-4">👥 Team Members ({{ selectedTeam?.members?.length || 0 }})</h4>
                    <div class="space-y-3">
                      <div v-for="member in selectedTeam?.members" :key="member.id" class="flex items-center p-3 bg-white rounded-lg">
                        <div class="text-2xl mr-3">{{ member.id === selectedTeam?.leadId ? '👑' : '👤' }}</div>
                        <div class="flex-grow">
                          <div class="font-medium text-gray-900">{{ member.name }}</div>
                          <div class="text-sm text-gray-500">{{ member.role }} - {{ member.department }}</div>
                        </div>
                        <div class="text-right">
                          <div class="text-sm font-medium text-gray-900">{{ member.id === selectedTeam?.leadId ? 'Team Lead' : 'Member' }}</div>
                          <div class="text-xs text-gray-500">{{ member.experience }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Team Stats -->
                <div>
                  <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h4 class="text-lg font-semibold mb-4">📊 Team Stats</h4>
                    <div class="space-y-4">
                      <div class="text-center p-3 bg-blue-100 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">{{ selectedTeam?.members?.length || 0 }}</div>
                        <div class="text-sm text-blue-600">Total Members</div>
                      </div>
                      <div class="text-center p-3 bg-green-100 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">{{ selectedTeam?.performance || 0 }}%</div>
                        <div class="text-sm text-green-600">Performance Score</div>
                      </div>
                      <div class="text-center p-3 bg-purple-100 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600">{{ Math.floor(Math.random() * 30) + 1 }}</div>
                        <div class="text-sm text-purple-600">Active Projects</div>
                      </div>
                    </div>
                  </div>

                  <!-- Quick Actions -->
                  <div class="bg-gray-50 rounded-lg p-6">
                    <h4 class="text-lg font-semibold mb-4">⚡ Quick Actions</h4>
                    <div class="space-y-2">
                      <button class="w-full text-left p-2 bg-white hover:bg-gray-100 rounded-lg text-sm">
                        📧 Send Team Message
                      </button>
                      <button class="w-full text-left p-2 bg-white hover:bg-gray-100 rounded-lg text-sm">
                        📅 Schedule Meeting
                      </button>
                      <button class="w-full text-left p-2 bg-white hover:bg-gray-100 rounded-lg text-sm">
                        📈 View Analytics
                      </button>
                      <button class="w-full text-left p-2 bg-white hover:bg-gray-100 rounded-lg text-sm">
                        ✏️ Edit Team
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex justify-end pt-6">
                <button
                  @click="showViewTeamModal = false"
                  class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Team Modal -->
        <div v-if="showEditTeamModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">✏️ Edit Team</h3>
                <button @click="showEditTeamModal = false" class="text-gray-400 hover:text-gray-600">
                  <span class="text-2xl">&times;</span>
                </button>
              </div>
              
              <form @submit.prevent="updateTeam" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Team Name *</label>
                    <input
                      v-model="editingTeam.name"
                      type="text"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                    />
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Team Icon</label>
                    <select v-model="editingTeam.icon" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                      <option value="🏆">🏆 Trophy</option>
                      <option value="⚡">⚡ Lightning</option>
                      <option value="🚀">🚀 Rocket</option>
                      <option value="💎">💎 Diamond</option>
                      <option value="🔥">🔥 Fire</option>
                      <option value="🌟">🌟 Star</option>
                      <option value="🎯">🎯 Target</option>
                      <option value="💪">💪 Strength</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Description</label>
                  <textarea
                    v-model="editingTeam.description"
                    rows="3"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                  ></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Department</label>
                    <select v-model="editingTeam.department" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                      <option value="Engineering">Engineering</option>
                      <option value="Marketing">Marketing</option>
                      <option value="Sales">Sales</option>
                      <option value="HR">Human Resources</option>
                      <option value="Finance">Finance</option>
                      <option value="Operations">Operations</option>
                      <option value="Design">Design</option>
                      <option value="Product">Product</option>
                    </select>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select v-model="editingTeam.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                      <option value="active">Active</option>
                      <option value="forming">Forming</option>
                      <option value="on-hold">On Hold</option>
                      <option value="completed">Completed</option>
                    </select>
                  </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                  <button
                    type="button"
                    @click="showEditTeamModal = false"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700"
                  >
                    Update Team
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'Teams',
  components: {
    Head,
    Link,
    AppLayout,
  },
  data() {
    return {
      // Modal states
      showCreateTeamModal: false,
      showViewTeamModal: false,
      showEditTeamModal: false,
      isCreating: false,
      
      // Filter and search
      filterType: 'all',
      searchQuery: '',
      
      // Form data
      newTeam: {
        name: '',
        description: '',
        department: '',
        leadId: '',
        memberIds: [],
        icon: '🏆',
        type: 'project',
        status: 'active'
      },
      
      editingTeam: {},
      selectedTeam: null,
      
      // Mock data for teams
      teams: [
        {
          id: 1,
          name: 'Development Team Alpha',
          description: 'Frontend and backend development for the main product',
          department: 'Engineering',
          leadId: 1,
          lead: { name: 'John Smith', role: 'Senior Engineer' },
          status: 'active',
          type: 'project',
          icon: '🚀',
          performance: 92,
          members: [
            { id: 1, name: 'John Smith', role: 'Senior Engineer', department: 'Engineering', experience: '5+ years' },
            { id: 2, name: 'Sarah Johnson', role: 'Frontend Developer', department: 'Engineering', experience: '3+ years' },
            { id: 3, name: 'Mike Chen', role: 'Backend Developer', department: 'Engineering', experience: '4+ years' },
            { id: 4, name: 'Emily Davis', role: 'UI/UX Designer', department: 'Design', experience: '2+ years' }
          ]
        },
        {
          id: 2,
          name: 'Marketing Innovators',
          description: 'Digital marketing campaigns and brand strategy',
          department: 'Marketing',
          leadId: 5,
          lead: { name: 'Lisa Anderson', role: 'Marketing Manager' },
          status: 'active',
          type: 'permanent',
          icon: '🎯',
          performance: 87,
          members: [
            { id: 5, name: 'Lisa Anderson', role: 'Marketing Manager', department: 'Marketing', experience: '6+ years' },
            { id: 6, name: 'David Wilson', role: 'Content Creator', department: 'Marketing', experience: '2+ years' },
            { id: 7, name: 'Anna Brown', role: 'Social Media Specialist', department: 'Marketing', experience: '3+ years' }
          ]
        },
        {
          id: 3,
          name: 'Sales Champions',
          description: 'Enterprise sales and customer relationship management',
          department: 'Sales',
          leadId: 8,
          lead: { name: 'Robert Taylor', role: 'Sales Director' },
          status: 'active',
          type: 'permanent',
          icon: '💎',
          performance: 95,
          members: [
            { id: 8, name: 'Robert Taylor', role: 'Sales Director', department: 'Sales', experience: '8+ years' },
            { id: 9, name: 'Jennifer Lee', role: 'Account Manager', department: 'Sales', experience: '4+ years' },
            { id: 10, name: 'Mark Johnson', role: 'Business Development', department: 'Sales', experience: '3+ years' }
          ]
        },
        {
          id: 4,
          name: 'Design Studio',
          description: 'Product design and user experience optimization',
          department: 'Design',
          leadId: 11,
          lead: { name: 'Jessica Wong', role: 'Design Lead' },
          status: 'active',
          type: 'cross-functional',
          icon: '🌟',
          performance: 89,
          members: [
            { id: 11, name: 'Jessica Wong', role: 'Design Lead', department: 'Design', experience: '7+ years' },
            { id: 12, name: 'Alex Kim', role: 'Product Designer', department: 'Design', experience: '4+ years' },
            { id: 13, name: 'Sam Rodriguez', role: 'UX Researcher', department: 'Design', experience: '3+ years' }
          ]
        },
        {
          id: 5,
          name: 'Operations Excellence',
          description: 'Process optimization and operational efficiency',
          department: 'Operations',
          leadId: 14,
          lead: { name: 'Michael Chang', role: 'Operations Manager' },
          status: 'on-hold',
          type: 'task-force',
          icon: '⚡',
          performance: 75,
          members: [
            { id: 14, name: 'Michael Chang', role: 'Operations Manager', department: 'Operations', experience: '6+ years' },
            { id: 15, name: 'Rachel Green', role: 'Process Analyst', department: 'Operations', experience: '2+ years' }
          ]
        },
        {
          id: 6,
          name: 'Data Analytics Squad',
          description: 'Business intelligence and data-driven insights',
          department: 'Engineering',
          leadId: 16,
          lead: { name: 'Tom Liu', role: 'Data Scientist' },
          status: 'completed',
          type: 'project',
          icon: '📊',
          performance: 96,
          members: [
            { id: 16, name: 'Tom Liu', role: 'Data Scientist', department: 'Engineering', experience: '5+ years' },
            { id: 17, name: 'Maria Garcia', role: 'Data Analyst', department: 'Engineering', experience: '3+ years' },
            { id: 18, name: 'Kevin Park', role: 'BI Developer', department: 'Engineering', experience: '4+ years' }
          ]
        }
      ],
      
      // Mock available employees for team creation - Using realistic employee data
      availableEmployees: [
        { id: 1, name: 'John Doe', role: 'Senior Developer', department: 'Engineering' },
        { id: 2, name: 'Jane Smith', role: 'Marketing Manager', department: 'Marketing' },
        { id: 3, name: 'Mike Johnson', role: 'Sales Representative', department: 'Sales' },
        { id: 4, name: 'Sarah Wilson', role: 'HR Specialist', department: 'HR' },
        { id: 5, name: 'Tom Brown', role: 'Financial Analyst', department: 'Finance' },
        { id: 6, name: 'Lisa Davis', role: 'Product Manager', department: 'Engineering' },
        { id: 7, name: 'Alex Johnson', role: 'Content Creator', department: 'Marketing' },
        { id: 8, name: 'Maria Garcia', role: 'Account Manager', department: 'Sales' },
        { id: 9, name: 'David Wilson', role: 'Software Engineer', department: 'Engineering' },
        { id: 10, name: 'Emily Chen', role: 'UX Designer', department: 'Design' },
        { id: 11, name: 'Robert Taylor', role: 'DevOps Engineer', department: 'Engineering' },
        { id: 12, name: 'Jennifer Lee', role: 'Marketing Specialist', department: 'Marketing' },
        { id: 13, name: 'Mark Johnson', role: 'Business Analyst', department: 'Operations' },
        { id: 14, name: 'Anna Brown', role: 'Quality Assurance', department: 'Engineering' },
        { id: 15, name: 'Chris Williams', role: 'Data Scientist', department: 'R&D' },
        { id: 16, name: 'Michelle Davis', role: 'Project Manager', department: 'Operations' },
        { id: 17, name: 'Kevin Park', role: 'Frontend Developer', department: 'Engineering' },
        { id: 18, name: 'Rachel Green', role: 'Content Strategist', department: 'Marketing' },
        { id: 19, name: 'James Miller', role: 'Sales Manager', department: 'Sales' },
        { id: 20, name: 'Nicole Adams', role: 'HR Coordinator', department: 'HR' }
      ]
    }
  },
  
  computed: {
    teamsStats() {
      return {
        totalTeams: this.teams.length,
        totalLeads: this.teams.filter(team => team.lead).length,
        totalMembers: this.teams.reduce((sum, team) => sum + (team.members?.length || 0), 0),
        avgPerformance: Math.round(this.teams.reduce((sum, team) => sum + (team.performance || 0), 0) / this.teams.length)
      }
    },
    
    filteredTeams() {
      if (this.filterType === 'all') {
        return this.teams
      }
      return this.teams.filter(team => team.status === this.filterType)
    }
  },
  
  methods: {
    getStatusClass(status) {
      const classes = {
        'active': 'bg-green-100 text-green-800',
        'forming': 'bg-blue-100 text-blue-800', 
        'on-hold': 'bg-yellow-100 text-yellow-800',
        'completed': 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    },
    
    createTeam() {
      // Validate required fields
      if (!this.newTeam.name.trim()) {
        alert('❌ Please enter a team name')
        return
      }
      
      this.isCreating = true
      
      // Simulate API delay
      setTimeout(() => {
        // Get the team lead
        const teamLead = this.getEmployeeById(this.newTeam.leadId)
        
        // Get team members (excluding the lead to avoid duplication)
        const teamMembers = this.newTeam.memberIds
          .filter(id => id != this.newTeam.leadId) // Exclude lead from members list
          .map(id => {
            const employee = this.getEmployeeById(id)
            return employee ? {
              ...employee,
              role: employee.role || 'Member',
              experience: this.calculateExperience(employee)
            } : null
          })
          .filter(Boolean) // Remove null entries
        
        // Add team lead to members list with lead role
        if (teamLead) {
          teamMembers.unshift({
            ...teamLead,
            role: teamLead.role || 'Team Lead',
            experience: this.calculateExperience(teamLead)
          })
        }
        
        const newTeamData = {
          ...this.newTeam,
          id: this.teams.length + 1,
          performance: Math.floor(Math.random() * 30) + 70, // Random performance 70-99%
          lead: teamLead ? { name: teamLead.name, role: teamLead.role } : null,
          members: teamMembers
        }
        
        this.teams.unshift(newTeamData)
        this.showCreateTeamModal = false
        this.resetNewTeam()
        this.isCreating = false
        
        // Show success message
        this.$nextTick(() => {
          const memberCount = teamMembers.length
          const leadText = teamLead ? ` with ${teamLead.name} as team lead` : ''
          alert(`✅ Team "${newTeamData.name}" created successfully${leadText}!\n👥 ${memberCount} member${memberCount !== 1 ? 's' : ''} assigned.`)
        })
      }, 800) // Simulate network delay
    },
    
    calculateExperience(employee) {
      // Simple experience calculation based on role
      const experienceMap = {
        'Senior Developer': '5+ years',
        'Marketing Manager': '4+ years', 
        'Project Manager': '6+ years',
        'Product Manager': '5+ years',
        'Team Lead': '6+ years',
        'Sales Representative': '2+ years',
        'HR Specialist': '3+ years',
        'Financial Analyst': '3+ years',
        'Software Engineer': '3+ years',
        'DevOps Engineer': '4+ years',
        'UX Designer': '3+ years',
        'Content Creator': '2+ years',
        'Account Manager': '4+ years'
      }
      return experienceMap[employee.role] || '1-2 years'
    },
    
    viewTeam(team) {
      this.selectedTeam = team
      this.showViewTeamModal = true
    },
    
    editTeam(team) {
      this.editingTeam = { ...team }
      this.showEditTeamModal = true
    },
    
    updateTeam() {
      const index = this.teams.findIndex(t => t.id === this.editingTeam.id)
      if (index !== -1) {
        this.teams[index] = { ...this.editingTeam }
        this.showEditTeamModal = false
        alert(`✅ Team "${this.editingTeam.name}" updated successfully!`)
      }
    },
    
    deleteTeam(team) {
      if (confirm(`Are you sure you want to delete "${team.name}"? This action cannot be undone.`)) {
        this.teams = this.teams.filter(t => t.id !== team.id)
        alert(`🗑️ Team "${team.name}" deleted successfully!`)
      }
    },
    
    refreshData() {
      // Simulate data refresh
      alert('🔄 Team data refreshed successfully!')
    },
    
    filterTeams() {
      // Filtering is handled by computed property
    },
    
    getEmployeeById(id) {
      // First check available employees
      let employee = this.availableEmployees.find(emp => emp.id == id)
      if (employee) return employee
      
      // Then check existing team members
      const allTeamMembers = this.teams.flatMap(t => t.members || [])
      employee = allTeamMembers.find(emp => emp.id == id)
      if (employee) return employee
      
      // Return null if not found
      console.warn(`Employee with id ${id} not found`)
      return null
    },
    
    resetNewTeam() {
      this.newTeam = {
        name: '',
        description: '',
        department: '',
        leadId: '',
        memberIds: [],
        icon: '🏆',
        type: 'project',
        status: 'active'
      }
      this.isCreating = false
    }
  }
}
</script>

<style scoped>
/* Custom styles for team management */
.team-card {
  transition: transform 0.2s ease-in-out;
}

.team-card:hover {
  transform: translateY(-2px);
}

.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>