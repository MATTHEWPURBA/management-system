# Task Management System

A complete RESTful API system for managing users and tasks with role-based access control, built with Laravel and Vanilla JS.

## Features

- Role-based access control (Admin, Manager, Staff)
- User management (CRUD operations)
- Task management (CRUD operations)
- Activity logging for audit trails
- Laravel Sanctum for authentication
- Docker environment with MySQL and phpMyAdmin
- Comprehensive test suite

## System Architecture

### Entity Relationship Diagram

```
+---------------+       +---------------+       +---------------+
|     User      |       |     Task      |       | Activity Log  |
+---------------+       +---------------+       +---------------+
| id (UUID)     |       | id (UUID)     |       | id (UUID)     |
| name          |       | title         |       | user_id       |
| email         |       | description   |       | action        |
| password      |       | assigned_to   |<------|--+            |
| role          |<------|-+ created_by  |       | description   |
| status        |       | status        |       | logged_at     |
| timestamps    |       | due_date      |       | timestamps    |
+---------------+       | timestamps    |       +---------------+
                        +---------------+
```

### Role Permissions

| Role    | View Users | Manage Tasks    | Assign Tasks   | View Logs |
|---------|------------|-----------------|----------------|-----------|
| Admin   | ✅         | ✅ (all)        | ✅ (anyone)    | ✅        |
| Manager | ✅         | ✅ (own team)   | ✅ (staff)     | ❌        |
| Staff   | ❌         | ✅ (self only)  | ❌             | ❌        |

### Business Rules

- Inactive users cannot log in
- Managers can only assign tasks to staff members
- Users can only see tasks created or assigned to them
- Overdue tasks are automatically logged via a scheduled command

## Technology Stack

- **Backend**: Laravel 10+
- **Frontend**: Vanilla JavaScript, Bootstrap 5
- **Database**: MySQL
- **Authentication**: Laravel Sanctum
- **Containerization**: Docker
- **Testing**: PHPUnit

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/task-management-system.git
   cd task-management-system
   ```

2. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

3. Update the `.env` file with your database credentials.

4. Start the Docker environment:
   ```bash
   docker-compose up -d
   ```

5. Install dependencies:
   ```bash
   docker-compose exec app composer install
   ```

6. Generate application key:
   ```bash
   docker-compose exec app php artisan key:generate
   ```

7. Run migrations and seed the database:
   ```bash
   docker-compose exec app php artisan migrate --seed
   ```

8. Set up the scheduler for checking overdue tasks:
   - Add the following to your server's crontab:
   ```
   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
   ```

## Default Users

After seeding the database, you can log in with the following credentials:

- **Admin**:
  - Email: admin@example.com
  - Password: password

- **Manager**:
  - Email: manager@example.com
  - Password: password

- **Staff**:
  - Email: staff1@example.com
  - Password: password

## API Endpoints

### Authentication
- `POST /api/login` - User login
- `POST /api/logout` - User logout (requires authentication)

### Users
- `GET /api/users` - List all users (admin, manager only)
- `GET /api/users/{id}` - Get a specific user (admin, manager only)
- `POST /api/users` - Create a new user (admin only)
- `PUT /api/users/{id}` - Update a user (admin only)
- `DELETE /api/users/{id}` - Delete a user (admin only)

### Tasks
- `GET /api/tasks` - List tasks (filtered by role permissions)
- `GET /api/tasks/{id}` - Get a specific task (if permitted)
- `POST /api/tasks` - Create a new task (with role-based assignment rules)
- `PUT /api/tasks/{id}` - Update a task (if permitted)
- `DELETE /api/tasks/{id}` - Delete a task (admin or creator only)
- `GET /api/tasks/export` - Export tasks to CSV (admin only)

### Activity Logs
- `GET /api/logs` - List activity logs (admin only)
- `GET /api/logs/{id}` - Get a specific log entry (admin only)

## Running Tests

```bash
docker-compose exec app php artisan test
```

For coverage report:
```bash
docker-compose exec app php artisan test --coverage
```

## Frontend

The frontend is a single-page application built with Vanilla JavaScript and Bootstrap 5, found in the `public/frontend` directory. It communicates with the Laravel backend API to provide a user-friendly interface for managing users and tasks.

To access the frontend, navigate to `http://localhost:8000/frontend` in your browser after starting the Docker environment.

## Screenshots

![Login Screen](screenshots/login.png)
![Task Dashboard](screenshots/dashboard.png)
![Task Management](screenshots/tasks.png)
![User Management](screenshots/users.png)

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).