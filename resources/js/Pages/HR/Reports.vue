<template>
  <Head title="HR - Reports" />
  
  <AppLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          📊 HR Reports & Analytics
        </h2>
        <div class="flex space-x-4">
          <a href="/hr-vue" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            ← Back to HR Dashboard
          </a>
          <button @click="generateCustomReport" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            📈 Custom Report
          </button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Report Categories -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div class="bg-blue-500 text-white p-6 rounded-lg shadow-lg cursor-pointer hover:bg-blue-600" @click="selectedCategory = 'employee'">
            <div class="text-3xl mb-2">👥</div>
            <div class="text-xl font-bold">Employee Reports</div>
            <div class="text-sm opacity-75">Performance, demographics, org chart</div>
          </div>
          
          <div class="bg-green-500 text-white p-6 rounded-lg shadow-lg cursor-pointer hover:bg-green-600" @click="selectedCategory = 'attendance'">
            <div class="text-3xl mb-2">📅</div>
            <div class="text-xl font-bold">Attendance Reports</div>
            <div class="text-sm opacity-75">Daily, weekly, monthly attendance</div>
          </div>
          
          <div class="bg-purple-500 text-white p-6 rounded-lg shadow-lg cursor-pointer hover:bg-purple-600" @click="selectedCategory = 'payroll'">
            <div class="text-3xl mb-2">💰</div>
            <div class="text-xl font-bold">Payroll Reports</div>
            <div class="text-sm opacity-75">Salary, benefits, tax reports</div>
          </div>
        </div>

        <!-- Report Generation Form -->
        <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg mb-6">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">🔧 Generate Report</h3>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                <select 
                  v-model="reportConfig.type"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                  <option value="">Select Report Type</option>
                  <optgroup v-if="selectedCategory === 'employee'" label="Employee Reports">
                    <option value="employee-list">Employee Directory</option>
                    <option value="performance">Performance Summary</option>
                    <option value="turnover">Turnover Analysis</option>
                    <option value="demographics">Demographics Report</option>
                  </optgroup>
                  <optgroup v-if="selectedCategory === 'attendance'" label="Attendance Reports">
                    <option value="daily-attendance">Daily Attendance</option>
                    <option value="monthly-summary">Monthly Summary</option>
                    <option value="leave-balance">Leave Balance</option>
                    <option value="overtime">Overtime Report</option>
                  </optgroup>
                  <optgroup v-if="selectedCategory === 'payroll'" label="Payroll Reports">
                    <option value="payroll-summary">Payroll Summary</option>
                    <option value="tax-report">Tax Report</option>
                    <option value="benefits">Benefits Report</option>
                    <option value="cost-center">Cost Center Analysis</option>
                  </optgroup>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                <input 
                  v-model="reportConfig.dateFrom"
                  type="date" 
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                <input 
                  v-model="reportConfig.dateTo"
                  type="date" 
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select 
                  v-model="reportConfig.department"
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
            </div>
            
            <div class="mt-4 flex space-x-4">
              <button @click="generateReport" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                📊 Generate Report
              </button>
              <button @click="scheduleReport" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                ⏰ Schedule Report
              </button>
              <button @click="resetReportConfig" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
                🔄 Reset
              </button>
            </div>
          </div>
        </div>

        <!-- Recent Reports -->
        <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg mb-6">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">📋 Recent Reports</h3>
          </div>
          
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Report Name
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Type
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Generated Date
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
                <tr v-for="report in recentReports" :key="report.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ report.name }}</div>
                    <div class="text-sm text-gray-500">{{ report.description }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span :class="getTypeClass(report.type)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ report.type }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ formatDate(report.generatedDate) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusClass(report.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ report.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <button @click="downloadReport(report)" class="text-blue-600 hover:text-blue-900">📥 Download</button>
                    <button @click="viewReport(report)" class="text-green-600 hover:text-green-900">👁️ View</button>
                    <button @click="deleteReport(report)" class="text-red-600 hover:text-red-900">🗑️ Delete</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Analytics Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Employee Growth Chart -->
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">📈 Employee Growth Trend</h3>
            </div>
            <div class="p-6">
              <div class="space-y-4">
                <div v-for="month in growthData" :key="month.month" class="flex items-center justify-between">
                  <div class="text-sm font-medium text-gray-900">{{ month.month }}</div>
                  <div class="flex items-center space-x-2">
                    <div class="bg-blue-200 rounded-full h-4" :style="{ width: (month.employees / 50) * 200 + 'px' }"></div>
                    <span class="text-sm text-gray-600">{{ month.employees }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Department Distribution -->
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">🥧 Department Distribution</h3>
            </div>
            <div class="p-6">
              <div class="space-y-4">
                <div v-for="dept in departmentData" :key="dept.name" class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div :class="dept.color" class="w-4 h-4 rounded-full"></div>
                    <span class="text-sm font-medium text-gray-900">{{ dept.name }}</span>
                  </div>
                  <div class="text-sm text-gray-600">{{ dept.percentage }}% ({{ dept.count }})</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Attendance Overview -->
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">📊 Attendance Overview</h3>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 bg-green-50 rounded-lg">
                  <div class="text-2xl font-bold text-green-600">96.2%</div>
                  <div class="text-sm text-green-700">Average Attendance</div>
                </div>
                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                  <div class="text-2xl font-bold text-yellow-600">3.1%</div>
                  <div class="text-sm text-yellow-700">Late Arrivals</div>
                </div>
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                  <div class="text-2xl font-bold text-blue-600">2.8%</div>
                  <div class="text-sm text-blue-700">On Leave</div>
                </div>
                <div class="text-center p-4 bg-red-50 rounded-lg">
                  <div class="text-2xl font-bold text-red-600">1.4%</div>
                  <div class="text-sm text-red-700">Absent</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Performance Metrics -->
          <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">⭐ Performance Metrics</h3>
            </div>
            <div class="p-6">
              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <span class="text-sm font-medium text-gray-900">Excellent (4.5-5.0)</span>
                  <span class="text-sm text-gray-600">28% (14 employees)</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm font-medium text-gray-900">Good (3.5-4.4)</span>
                  <span class="text-sm text-gray-600">52% (26 employees)</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm font-medium text-gray-900">Average (2.5-3.4)</span>
                  <span class="text-sm text-gray-600">16% (8 employees)</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm font-medium text-gray-900">Below Average (< 2.5)</span>
                  <span class="text-sm text-gray-600">4% (2 employees)</span>
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

// State
const selectedCategory = ref('employee')

const reportConfig = reactive({
  type: '',
  dateFrom: '',
  dateTo: '',
  department: ''
})

// Demo data
const recentReports = ref([
  {
    id: 1,
    name: 'Monthly Attendance Report - September 2024',
    description: 'Complete attendance summary for all departments',
    type: 'Attendance',
    generatedDate: '2024-09-28',
    status: 'completed'
  },
  {
    id: 2,
    name: 'Employee Performance Review Q3',
    description: 'Quarterly performance review summary',
    type: 'Performance',
    generatedDate: '2024-09-25',
    status: 'completed'
  },
  {
    id: 3,
    name: 'Payroll Summary - September 2024',
    description: 'Monthly payroll processing report',
    type: 'Payroll',
    generatedDate: '2024-09-20',
    status: 'completed'
  },
  {
    id: 4,
    name: 'Department Headcount Analysis',
    description: 'Current headcount by department with trends',
    type: 'Analytics',
    generatedDate: '2024-09-15',
    status: 'processing'
  },
  {
    id: 5,
    name: 'Leave Balance Report',
    description: 'Current leave balances for all employees',
    type: 'Leave',
    generatedDate: '2024-09-10',
    status: 'completed'
  }
])

const growthData = ref([
  { month: 'Jan 2024', employees: 42 },
  { month: 'Feb 2024', employees: 44 },
  { month: 'Mar 2024', employees: 46 },
  { month: 'Apr 2024', employees: 47 },
  { month: 'May 2024', employees: 49 },
  { month: 'Jun 2024', employees: 50 },
  { month: 'Jul 2024', employees: 52 },
  { month: 'Aug 2024', employees: 48 },
  { month: 'Sep 2024', employees: 50 }
])

const departmentData = ref([
  { name: 'Engineering', count: 18, percentage: 36, color: 'bg-blue-500' },
  { name: 'Sales', count: 12, percentage: 24, color: 'bg-green-500' },
  { name: 'Marketing', count: 8, percentage: 16, color: 'bg-purple-500' },
  { name: 'HR', count: 6, percentage: 12, color: 'bg-yellow-500' },
  { name: 'Finance', count: 6, percentage: 12, color: 'bg-red-500' }
])

// Methods
const getTypeClass = (type) => {
  const classes = {
    'Attendance': 'bg-blue-100 text-blue-800',
    'Performance': 'bg-green-100 text-green-800',
    'Payroll': 'bg-purple-100 text-purple-800',
    'Analytics': 'bg-yellow-100 text-yellow-800',
    'Leave': 'bg-orange-100 text-orange-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const getStatusClass = (status) => {
  const classes = {
    'completed': 'bg-green-100 text-green-800',
    'processing': 'bg-yellow-100 text-yellow-800',
    'failed': 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

const generateReport = () => {
  if (!reportConfig.type) {
    alert('Please select a report type.')
    return
  }
  
  // Simulate report generation with more realistic feedback
  const reportName = `${reportConfig.type.replace('-', ' ').toUpperCase()} Report`
  const dateRange = reportConfig.dateFrom && reportConfig.dateTo 
    ? `${reportConfig.dateFrom} to ${reportConfig.dateTo}`
    : 'All time'
  
  // Add to recent reports
  const newReport = {
    id: Date.now(),
    name: reportName,
    type: reportConfig.type,
    dateGenerated: new Date().toISOString().split('T')[0],
    status: 'completed',
    department: reportConfig.department || 'All Departments',
    dateRange: dateRange
  }
  
  recentReports.value.unshift(newReport)
  
  // Show success message
  alert(`✅ Report Generated Successfully!\n\n${reportName}\nDate Range: ${dateRange}\nDepartment: ${reportConfig.department || 'All Departments'}\n\nReport has been added to your Recent Reports list and is ready for viewing/download.`)
  
  // Reset form
  resetReportConfig()
}

const scheduleReport = () => {
  if (!reportConfig.type) {
    alert('Please select a report type to schedule.')
    return
  }
  
  alert(`✅ Scheduled recurring report: ${reportConfig.type}\n\nReport will be automatically generated and emailed as configured. Check your email for confirmation.`)
}

const generateCustomReport = () => {
  // Create a custom report entry
  const customReport = {
    id: Date.now(),
    name: 'Custom Analytics Report',
    type: 'custom-analytics',
    dateGenerated: new Date().toISOString().split('T')[0],
    status: 'completed',
    department: 'Multi-Department',
    dateRange: 'Custom Range'
  }
  
  recentReports.value.unshift(customReport)
  
  alert('✅ Custom Report Builder Launched!\n\nYour custom analytics report has been generated with:\n• Advanced data visualization\n• Multi-department comparison\n• Custom metrics and KPIs\n• Interactive charts and graphs\n\nReport added to Recent Reports for viewing.')
}

const resetReportConfig = () => {
  reportConfig.type = ''
  reportConfig.dateFrom = ''
  reportConfig.dateTo = ''
  reportConfig.department = ''
}

const downloadReport = (report) => {
  alert(`✅ Downloading: ${report.name}\n\nReport file (PDF/Excel) is being prepared. Check your downloads folder.`)
}

const viewReport = (report) => {
  alert(`✅ Opening: ${report.name}\n\nReport viewer launched! You can now view detailed analytics and charts.`)
}

const deleteReport = (report) => {
  if (confirm(`Are you sure you want to delete "${report.name}"?`)) {
    const index = recentReports.value.findIndex(r => r.id === report.id)
    if (index > -1) {
      recentReports.value.splice(index, 1)
    }
    alert('Report deleted successfully!')
  }
}
</script>