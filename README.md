# TaskFlow – Task Management System

A simple, secure task manager built with **Laravel 12 (PHP)**, **MySQL**, **HTML5**, **CSS3** and **Vanilla JavaScript** (no frontend frameworks, no build step).

Users log in, see their task statistics, and add / edit / delete / search / filter their own tasks. Task changes go through `fetch()`, so the page never reloads.

---

## Requirements

- PHP **8.2+** with the `pdo_mysql`, `mbstring`, `openssl` and `fileinfo` extensions
- Composer 2
- MySQL 5.7+ or MariaDB 10.3+ (XAMPP works)

---

## Setup

```bash
# 1. Install PHP dependencies
composer install

# 2. Create the environment file and app key
cp .env.example .env
php artisan key:generate
```

Then open `.env` and check the database settings:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_manager
DB_USERNAME=root
DB_PASSWORD=
```

---

## Database setup

Pick **one** of the two options below.

**Option A – import the SQL file**

```bash
mysql -u root -p < database.sql
```

`database.sql` creates the `task_manager` database with all tables and the test data.
You can also import it from phpMyAdmin (Import tab).

**Option B – Laravel migrations and seeder**

```bash
# Create an empty database first, e.g.:
#   CREATE DATABASE task_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
php artisan migrate --seed
```

---

## Running the app

**With the built-in server:**

```bash
php artisan serve
```

Then open <http://127.0.0.1:8000>.

**With XAMPP / Apache:** put the project inside `htdocs` and open the `public` folder, e.g.
<http://localhost/projects/task/public>. `mod_rewrite` must be enabled (it is by default in XAMPP).

---

## Test login credentials

| Name       | Email              | Password      | Data     |
|------------|--------------------|---------------|----------|
| John Doe   | `john@example.com` | `password123` | 10 tasks |
| Jane Smith | `jane@example.com` | `password123` | 2 tasks  |

Log in as both users to check that each one only sees their own tasks.

---

## Features

- **Login and logout** with Laravel's session guard. Wrong credentials show an error message. Guests are redirected away from the dashboard.
- **Dashboard** with a welcome message and counters for Total, Pending, In Progress and Completed tasks. The counters update live.
- **Task CRUD** (title, description, priority, status, due date) in a modal built on the native `<dialog>` element. Deleting a task asks for confirmation first.
- **Search by title** and **filter by status and priority**, all done in the browser with Vanilla JS.
- **Validation on both sides.** JS gives instant inline errors. Laravel Form Requests are the source of truth, and their errors are shown on the same form fields.
- **Responsive UI.** On small screens the task table turns into stacked cards.
- Overdue tasks are highlighted. Toast messages confirm each action.

---

## Project structure

The backend follows a **Controller → Repository Interface → Repository** design:

```
app/
├── Enums/
│   ├── TaskPriority.php            # low | medium | high
│   └── TaskStatus.php              # pending | in_progress | completed
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php      # login form, login, logout
│   │   ├── DashboardController.php # dashboard page + initial stats
│   │   └── TaskController.php      # JSON CRUD endpoints used by fetch()
│   ├── Middleware/SecurityHeaders.php
│   ├── Requests/
│   │   ├── LoginRequest.php        # login validation + rate limiting
│   │   └── TaskRequest.php         # task validation rules
│   └── Resources/TaskResource.php  # JSON shape of a task
├── Models/ (User, Task)
├── Policies/TaskPolicy.php         # a user may only modify their own tasks
├── Providers/RepositoryServiceProvider.php  # binds interface -> implementation
└── Repositories/
    ├── Contracts/TaskRepositoryInterface.php
    └── TaskRepository.php          # Eloquent implementation

resources/views/                    # Blade templates (HTML5)
├── layouts/app.blade.php
├── auth/login.blade.php
└── dashboard.blade.php

public/
├── css/app.css                     # all styles (design tokens, components, responsive)
└── js/
    ├── login.js                    # login form validation
    ├── dashboard.js                # state, rendering, search/filter, modals
    └── modules/                    # http (fetch wrapper), validation, form-errors, toast

database/
├── migrations/                     # users + tasks tables
├── factories/
└── seeders/
    ├── DatabaseSeeder.php          # runs UserSeeder, then TaskSeeder
    ├── UserSeeder.php              # test accounts (john@ / jane@example.com)
    └── TaskSeeder.php              # sample tasks for those accounts
```

Controllers depend only on `TaskRepositoryInterface`. `RepositoryServiceProvider` binds that interface to the Eloquent `TaskRepository`, so you can swap the storage layer without touching the controllers. The task logic is light, so there is no service layer. If the business rules grow, you can add one between the controllers and the repository.

### Routes

| Method | URI            | Description                         |
|--------|----------------|-------------------------------------|
| GET    | `/login`       | Login page                          |
| POST   | `/login`       | Authenticate                        |
| POST   | `/logout`      | Log out                             |
| GET    | `/dashboard`   | Dashboard page                      |
| GET    | `/tasks`       | JSON: the user's tasks + stats      |
| POST   | `/tasks`       | JSON: create a task                 |
| PUT    | `/tasks/{id}`  | JSON: update a task (owner only)    |
| DELETE | `/tasks/{id}`  | JSON: delete a task (owner only)    |

---

## Security

| Concern              | How it is handled |
|----------------------|-------------------|
| SQL injection        | All queries go through Eloquent and the query builder, which use PDO prepared statements. |
| Password storage     | Passwords are hashed with bcrypt (`hashed` cast / `Hash`). |
| Backend validation   | Form Requests check required fields, lengths, enum values and real calendar dates. |
| Session handling     | The session ID is regenerated on login, and the session is invalidated and the token regenerated on logout. Cookies are `HttpOnly` and `SameSite=Lax`. |
| Brute force          | Login is limited to 5 attempts per email and IP address. |
| CSRF                 | Every POST/PUT/DELETE requires the token, sent in the form or in the `X-CSRF-TOKEN` header for `fetch`. |
| Authorization        | Queries are scoped to the logged-in user. `TaskPolicy` blocks access to other users' tasks and returns 404, so it does not reveal that they exist. `user_id` cannot be mass-assigned. |
| XSS                  | Blade `{{ }}` escapes all output. JS writes user data only with `textContent`, never `innerHTML`. A CSP header is sent when `APP_DEBUG=false`. |
| Other headers        | `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`. |

---

## Tests

Unit tests (`tests/Unit`) cover each module on its own: enums, models, policy, repository, form requests (including login rate limiting), the JSON resource and the security-headers middleware. Feature tests (`tests/Feature`) cover authentication, authorization (users cannot see or change other users' tasks), validation, CRUD and the seeders. They use an in-memory SQLite database, so you need the `pdo_sqlite` extension.

```bash
php artisan test
```
