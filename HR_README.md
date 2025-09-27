# HR Management System - ExtJS Desktop Application

## Overview
A comprehensive HR Management System built with Laravel backend and ExtJS desktop-style frontend. The application provides a Windows-like desktop experience for managing human resources operations.

## Features Implemented

### 🖥️ Desktop Interface
- **Windows-Style Desktop**: Full desktop metaphor with taskbar, desktop icons, and window management
- **Taskbar**: Start button with HR menu, system tray with user info and clock
- **Desktop Shortcuts**: Quick access icons for HR modules
- **Window Management**: Minimizable, maximizable windows with taskbar integration

### 🗄️ Database Schema (PostgreSQL)
- **HR Departments**: Hierarchical department structure with managers and budgets
- **HR Positions**: Job positions with salary ranges and requirements
- **HR Employees**: Comprehensive employee profiles with personal and employment data
- **HR Attendance**: Time tracking with check-in/out, breaks, and overtime calculation
- **HR Leave Requests**: Leave management with approval workflow
- **HR Evaluations**: Performance evaluation system

### 🔧 Backend API (Laravel)
- **Department Management**: CRUD operations, hierarchy management, statistics
- **Employee Management**: Full employee lifecycle management
- **Position Management**: Job position management and analytics
- **Attendance Tracking**: Real-time attendance monitoring and reporting
- **Authentication**: Integrated with existing Laravel auth system

### 📱 Frontend Modules

#### Employee Management
- **Grid View**: Sortable, filterable employee list with photos and status
- **Search & Filter**: Real-time search by name, email, department
- **Employee Cards**: Visual employee information with avatars
- **Status Management**: Employment status tracking and color coding

## Technology Stack

### Backend
- **Laravel 10.x**: PHP web framework
- **PostgreSQL**: Primary database
- **Laravel Sanctum**: API authentication
- **RESTful API**: JSON-based API endpoints

### Frontend
- **ExtJS 7.6.0**: JavaScript application framework
- **Desktop Theme**: Windows-like UI components
- **FontAwesome**: Icon library for UI elements
- **CSS3**: Custom styling for desktop experience

### Infrastructure
- **Docker**: Containerized development environment
- **Redis**: Caching and session storage
- **Mailpit**: Email testing in development

## Installation & Setup

### Prerequisites
- Docker Desktop installed and running
- Git for version control

### Quick Start

1. **Start Docker Services**
   ```bash
   cd laravel-boilerplate
   docker-compose up -d
   ```

2. **Run Database Migrations**
   ```bash
   docker exec laravel-app php artisan migrate
   ```

3. **Seed HR Data** (optional)
   ```bash
   docker exec laravel-app php artisan db:seed --class=HRSeeder
   ```

4. **Access the Application**
   - Main Laravel App: http://localhost:8000
   - HR Desktop App: http://localhost:8000/hr-app
   - Login with existing credentials or register new account

### Sample Data
The HR seeder creates:
- 5 departments (HR, IT, Finance, Marketing, Sales)
- 16 positions across different levels
- 6 sample employees with realistic data

## Application Structure

### ExtJS Application (`public/hr-app/`)
```
hr-app/
├── app/
│   ├── Application.js          # Main application class
│   ├── model/
│   │   ├── Employee.js         # Employee data model
│   │   └── Department.js       # Department data model
│   ├── store/
│   │   ├── EmployeeStore.js    # Employee data store
│   │   └── DepartmentStore.js  # Department data store
│   └── view/
│       ├── main/               # Main desktop interface
│       └── employee/           # Employee management UI
└── index.html                  # Application entry point
```

### API Endpoints (`/api/hr/`)
- `GET|POST /departments` - Department CRUD
- `GET|POST /employees` - Employee CRUD  
- `GET|POST /positions` - Position CRUD
- `GET|POST /attendance` - Attendance tracking
- `GET|POST /leave-requests` - Leave management
- `GET /dashboard` - HR dashboard statistics

## Current Status

### ✅ Completed
- [x] ExtJS application structure and desktop UI
- [x] PostgreSQL database schema for HR system
- [x] Laravel API controllers with full CRUD operations
- [x] Employee management grid with search and filtering
- [x] Department and position models with relationships
- [x] Windows-style desktop interface with taskbar
- [x] Authentication integration with Laravel

### 🔄 In Progress
- [ ] Employee form for adding/editing employees
- [ ] Department management tree view
- [ ] Attendance tracking interface
- [ ] Real-time dashboard with charts

### 📋 Planned
- [ ] Leave request management
- [ ] Performance evaluation system
- [ ] Reporting and analytics
- [ ] File upload for employee documents
- [ ] Advanced filtering and search

## Usage Instructions

### Accessing HR System
1. Navigate to http://localhost:8000
2. Login with your credentials (or register)
3. Click "HR System" in the navigation or go to `/hr`
4. Desktop interface loads with HR shortcuts

### Using Employee Management
1. Click "Employees" desktop icon or start menu
2. Employee grid opens with current employee data
3. Use search box to filter employees
4. Use department dropdown to filter by department
5. Click action buttons to view/edit/delete employees

### Desktop Features
- **Start Menu**: Click HR System button for module access
- **Taskbar**: Shows open windows, current user, and system time
- **Window Management**: Minimize/maximize windows, click taskbar to restore
- **Desktop Icons**: Double-click or single-click for quick access

## API Testing
Test the API endpoints using tools like Postman:

```bash
# Get all employees
GET http://localhost:8000/api/hr/employees
Authorization: Bearer {your-token}

# Get departments
GET http://localhost:8000/api/hr/departments

# Get HR dashboard stats  
GET http://localhost:8000/api/hr/dashboard
```

## Development Notes

### Database Models
- All HR models use soft deletes for data integrity
- Relationships properly defined with foreign keys
- JSON fields for flexible metadata storage
- Comprehensive validation rules in API controllers

### ExtJS Architecture
- MVC pattern with models, views, and controllers
- Store-based data management with REST proxy
- Desktop metaphor with window management
- Responsive grid components with pagination

### Security Features
- CSRF token protection on all API calls
- Laravel Sanctum authentication
- Input validation and sanitization
- Proper HTTP status codes and error handling

## Next Steps
1. Complete employee form implementation
2. Add department tree view for hierarchy management
3. Implement attendance clock-in/out interface
4. Create dashboard with charts and statistics
5. Add reporting features with PDF export

## Support
For technical issues or feature requests, please refer to the Laravel and ExtJS documentation or create issues in the project repository.