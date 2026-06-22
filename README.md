# Class Manager

A PHP web app for managing student course schedules. Students can view their schedule and browse the course catalog; advisors can manage all students, update enrollment statuses, and add courses on their behalf.

---

## Features

- **Student view** — see your schedule by term, track completed vs remaining courses, and get a suggested revised schedule if you've missed a class
- **Advisor view** — list all students, see who's off track, edit enrollment statuses, and add courses for any student
- **Course catalog** — filter by department and quarter, prerequisite enforcement, add courses directly to a student's plan
- **Messaging** — in-app messaging between students and advisors with unread badges
- **Profile editor** — update name, email, password, and upload a profile picture
- **Auth** — session-based login and registration with bcrypt password hashing

---

## Stack

- PHP (no framework)
- MySQL via `mysqli`
- [Gradio](https://www.gradio.app/) — just kidding, plain HTML/CSS
- [Tabler Icons](https://tabler.io/icons) for icons

---

## Setup

**1. Clone the repo**
```bash
git clone https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
cd YOUR_REPO_NAME
```

**2. Configure the database**

Copy `includes/db.php` and fill in your credentials:
```php
$host = "your_db_host";
$db   = "your_db_name";
$user = "your_db_user";
$pass = "your_db_password";
```

> **Never commit real credentials.** Add `includes/db.php` to your `.gitignore` and use environment variables or a config file outside the web root in production.

**3. Import the database schema**

Run the SQL schema against your database (tables needed: `users`, `courses`, `enrollments`, `messages`).

**4. Set up the uploads directory**

Make sure `uploads/avatars/` is writable by your web server:
```bash
chmod 755 uploads/avatars/
```

**5. Point your web server at the project root**

Works with Apache, Nginx, or PHP's built-in server:
```bash
php -S localhost:8000
```

---

## Project Structure

```
├── index.php             # Login / registration
├── student.php           # Student schedule view
├── advisor.php           # Advisor student list
├── student_detail.php    # Advisor view of a single student
├── edit_student.php      # Advisor enrollment status editor
├── course_catalog.php    # Course browser and enrollment
├── messages.php          # In-app messaging
├── profile_editor.php    # Profile / password / avatar
├── logout.php
├── eula.html
├── css/style.css
├── uploads/avatars/      # User-uploaded profile pictures
└── includes/
    ├── db.php            # Database connection (fill in your credentials)
    ├── auth.php          # Session auth guard
    ├── avatar.php        # Avatar HTML helper
    ├── sidebar.php       # Sidebar nav component
    ├── prereq.php        # Prerequisite check logic
    └── scheduler.php     # Revised schedule suggestion logic
```

---

## Roles

There are two user roles: `student` and `advisor`. Registration always creates a student account. Advisor accounts need to be set manually in the database:

```sql
UPDATE users SET role = 'advisor' WHERE email = 'advisor@example.com';
```

---

## Known Issues / To-Do

- Several queries use string interpolation instead of prepared statements — susceptible to SQL injection, should be converted
- No CSRF protection on forms
- `display_errors` was previously on in production code — removed, but double-check your PHP config
- No email verification on registration
- Sessions are not regenerated after login (session fixation risk)
