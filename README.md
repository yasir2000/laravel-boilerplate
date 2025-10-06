# Laravel Business Boilerplate

A comprehensive Laravel boilerplate application for business scenarios with PostgreSQL integration, featuring advanced authentication, workflow engine, real-time notifications, file storage, and multi-language support.

## 🚀 Features

### Core Business Features
- **Multi-tenant Company Management** - Complete company structure with users and roles
- **Project Management** - Full project lifecycle with team collaboration
- **Task Management** - Advanced task tracking with assignments and status updates
- **User Management** - Comprehensive user profiles with hierarchical relationships

### Authentication & Security
- **Laravel Sanctum** - API token authentication
- **Laravel Fortify** - Complete authentication scaffolding
- **Two-Factor Authentication (2FA)** - Time-based OTP support
- **Email Verification** - Automated email verification workflow
- **Phone Verification** - SMS-based phone number verification
- **Password Reset** - Secure password reset functionality
- **Role-based Permissions** - Spatie Permission package integration

### Real-time Features
- **WebSocket Support** - Laravel Reverb for real-time communication
- **Live Notifications** - Instant notification delivery
- **User Presence** - Online/offline status tracking
- **Real-time Updates** - Live task and workflow updates

### Workflow Engine
- **Business Process Automation** - Configurable workflow definitions
- **Multi-step Approvals** - Sequential and parallel approval processes
- **Step Assignment** - Dynamic step assignment to users
- **Action Tracking** - Complete audit trail of workflow actions
- **Delegation Support** - Workflow step delegation capabilities

### File Storage & Media
- **Spatie Media Library** - Advanced file management
- **Image Processing** - Automatic image resizing and optimization
- **Multiple Storage Disks** - Local, S3, and custom storage support
- **File Validation** - Type and size validation
- **Avatar Management** - User avatar upload and management

### Notification System
- **Multi-channel Notifications** - Database, email, and real-time
- **Priority Levels** - Low, medium, high, and urgent priorities
- **Expiration Support** - Time-based notification expiration
- **Read Status Tracking** - Mark as read/unread functionality
- **Notification Templates** - Pre-built notification templates

### Internationalization
- **Multi-language Support** - Arabic and English
- **Spatie Translatable** - Model field translations
- **RTL Support** - Right-to-left language support
- **Locale Management** - Per-user locale preferences

### Performance & Scalability
- **Redis Integration** - Caching and queue management
- **Background Jobs** - Asynchronous task processing
- **Database Optimization** - Proper indexing and relationships
- **API Rate Limiting** - Built-in API protection

## 🛠 Technology Stack

### 🐳 Docker Architecture

- **Application Container:** Laravel app with Sail-compatible setup
- **Database:** MySQL 8.0 (configured for Laravel Sail)
- **Cache/Session:** Redis 7
- **Search:** Meilisearch
- **Email Testing:** Mailpit
- **Queue Management:** Built-in with Redis backend

## Installation

### 🚀 Quick Start with Laravel Sail (Recommended)

**Prerequisites:**
- Docker Desktop for Windows
- Git

**Installation:**
```bash
# 1. Clone the repository
git clone https://github.com/yasir2000/laravel-boilerplate.git
cd laravel-boilerplate

# 2. Copy environment file
cp .env.example .env

# 3. Start Laravel Sail containers
docker-compose up -d

# 4. Install dependencies and setup
docker-compose exec laravel.test composer install
docker-compose exec laravel.test php artisan key:generate
docker-compose exec laravel.test php artisan migrate
docker-compose exec laravel.test php artisan db:seed
```

**Access Points:**
- **Application:** http://localhost
- **Mailpit (Email Testing):** http://localhost:8025
- **Meilisearch:** http://localhost:7700

### ⚡ Laravel Octane Performance Boost

For high-performance serving with FrankenPHP:
```bash
# Install Octane
docker-compose exec laravel.test php artisan octane:install --server=frankenphp

# Start high-performance server
docker-compose exec laravel.test php artisan octane:frankenphp --host=0.0.0.0 --port=80
```

### 🛠 Laravel Sail Commands

```bash
# Run Artisan commands
docker-compose exec laravel.test php artisan [command]

# Run tests
docker-compose exec laravel.test php artisan test

# Access container shell
docker-compose exec laravel.test bash

# View logs
docker-compose logs -f laravel.test

# Stop containers
docker-compose down
```

### Manual Installation

1. Clone the repository:
```bash
git clone https://github.com/yasir2000/laravel-boilerplate.git
cd laravel-boilerplate
```

2. Install PHP dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Configure your `.env` file with PostgreSQL credentials:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel_boilerplate
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Run database migrations and seeders:
```bash
php artisan migrate --seed
```

7. Start the development server:
```bash
php artisan serve
```

### Docker Installation

1. Clone the repository:
```bash
git clone https://github.com/yasir2000/laravel-boilerplate.git
cd laravel-boilerplate
```

2. Build and start the containers:
```bash
docker-compose up -d
```

3. Install dependencies inside the container:
```bash
docker-compose exec app composer install
```

4. Generate application key:
```bash
docker-compose exec app php artisan key:generate
```

5. Run migrations and seeders:
```bash
docker-compose exec app php artisan migrate --seed
```

The application will be available at `http://localhost:8000`

## API Documentation

### Authentication

All API endpoints (except health check) require authentication using Laravel Sanctum.

### Endpoints

#### Companies
- `GET /api/companies` - List companies
- `POST /api/companies` - Create company
- `GET /api/companies/{id}` - Get company details
- `PUT /api/companies/{id}` - Update company
- `DELETE /api/companies/{id}` - Delete company
- `GET /api/companies/{id}/statistics` - Get company statistics

#### Projects
- `GET /api/projects` - List projects
- `POST /api/projects` - Create project
- `GET /api/projects/{id}` - Get project details
- `PUT /api/projects/{id}` - Update project
- `DELETE /api/projects/{id}` - Delete project
- `GET /api/projects/{id}/dashboard` - Get project dashboard
- `PATCH /api/projects/{id}/status` - Update project status

#### Tasks
- `GET /api/tasks` - List tasks
- `POST /api/tasks` - Create task
- `GET /api/tasks/{id}` - Get task details
- `PUT /api/tasks/{id}` - Update task
- `DELETE /api/tasks/{id}` - Delete task
- `PATCH /api/tasks/{id}/complete` - Mark task as completed
- `PATCH /api/tasks/{id}/assign` - Assign task to user
- `GET /api/my-tasks` - Get current user's tasks

#### Dashboard & Reports
- `GET /api/dashboard/overview` - Get dashboard overview
- `GET /api/reports/tasks/summary` - Get task summary report
- `GET /api/reports/projects/summary` - Get project summary report

### Sample API Requests

#### Create a Company
```bash
curl -X POST http://localhost:8000/api/companies \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "name": "Tech Solutions Inc.",
    "email": "contact@techsolutions.com",
    "phone": "+1-555-0123",
    "address": "123 Tech Street",
    "city": "San Francisco",
    "state": "California",
    "country": "United States",
    "postal_code": "94105",
    "website": "https://techsolutions.com",
    "subscription_plan": "enterprise"
  }'
```

#### Create a Project
```bash
curl -X POST http://localhost:8000/api/projects \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "name": "Website Redesign",
    "description": "Complete redesign of the company website",
    "status": "planning",
    "priority": "high",
    "start_date": "2024-01-15",
    "end_date": "2024-03-15",
    "budget": 50000.00,
    "client_name": "John Doe",
    "client_email": "john.doe@client.com"
  }'
```

## Default Users

After running the seeders, you can use these default accounts:

### Super Admin
- **Email**: superadmin@laravel-boilerplate.com
- **Password**: password
- **Role**: super-admin

### Company Admins
- **Email**: admin@{company-email}
- **Password**: password
- **Role**: admin

### Company Managers
- **Email**: manager@{company-email}
- **Password**: password
- **Role**: manager

### Company Employees
- **Email**: employee{1-3}@{company-email}
- **Password**: password
- **Role**: employee

## Roles & Permissions

### Super Admin
- Full access to all companies, users, projects, and tasks
- Can create/update/delete companies
- System-wide administrative privileges

### Admin
- Manage users, projects, and tasks within their company
- Company-level administrative privileges
- Cannot delete companies

### Manager
- Create and manage projects within their company
- Assign tasks to team members
- View reports and statistics

### Employee
- View and update assigned tasks
- View projects they're involved in
- Limited access to company data

### Client
- View projects they're associated with
- Read-only access to project information

## Database Schema

The application uses the following main entities:

### Companies
- Basic company information
- Subscription management
- Multi-tenancy support

### Users
- User authentication and profile
- Company association
- Role-based permissions

### Projects
- Project management with status tracking
- Budget and timeline management
- Client information

### Tasks
- Task assignment and tracking
- Priority and status management
- Time estimation and logging

## Best Practices Implemented

1. **Repository Pattern**: Service classes for business logic
2. **Policy-based Authorization**: Fine-grained access control
3. **Request Validation**: Form request classes for validation
4. **Database Optimization**: Proper indexing and relationships
5. **Activity Logging**: Comprehensive audit trail
6. **API Responses**: Consistent JSON response format
7. **Error Handling**: Graceful error responses
8. **Code Organization**: Clean, maintainable code structure

## Testing

Run the test suite:
```bash
php artisan test
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Write tests for new features
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For support, email hello@laravel-boilerplate.com or create an issue in the repository.