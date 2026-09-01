# Mini Pasal

Mini Pasal is a PHP-based bookstore web application for browsing books, adding items to a cart, signing up or signing in, and checking out using the eSewa sandbox payment flow.

It includes a storefront, admin panel, MySQL database support, and a JSON fallback mode for local demo environments.

## Features

- Book catalog with search and filtering
- Book details modal
- Add to cart workflow
- User sign up and sign in
- Admin login and dashboard
- Add, edit, and delete books from admin
- Checkout with eSewa sandbox integration
- MySQL-backed storage with a local JSON fallback

## Tech Stack

- PHP 8+
- MySQL / MariaDB
- HTML, CSS, JavaScript
- Bootstrap 5
- Session-based cart and auth

## Project Structure

- [index.php](index.php) – storefront and catalog
- [admin.php](admin.php) – book management dashboard
- [signin.php](signin.php) – sign in page
- [signup.php](signup.php) – user registration
- [cart.php](cart.php) – shopping cart
- [checkout.php](checkout.php) – checkout and eSewa form
- [db.php](db.php) – database connection and data handling
- [schema.sql](schema.sql) – database schema
- [seeds.php](seeds.php) – default admin and sample books
- [config.php](config.php) – payment configuration
- [success.php](success.php) – payment success callback
- [failure.php](failure.php) – payment failure callback

## Requirements

Before running the app, make sure you have:

- PHP 8.0 or newer
- MySQL 8.0 or MariaDB
- A local web server or PHP built-in server

## Quick Start

From the project folder:

```bash
php -S localhost:8000
```

Then open:

```text
http://localhost:8000/index.php
```

## Database Setup

The app expects a database named `mini_pasal` with a user named `mini_pasal_user`.

### Create the database and user

#### Linux / macOS

```bash
sudo mysql -u root
```

Then run:

```sql
CREATE DATABASE IF NOT EXISTS mini_pasal;
CREATE USER IF NOT EXISTS 'mini_pasal_user'@'localhost' IDENTIFIED BY 'mini_pasal123';
GRANT ALL PRIVILEGES ON mini_pasal.* TO 'mini_pasal_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Windows

Open MySQL Command Line or phpMyAdmin and run:

```sql
CREATE DATABASE IF NOT EXISTS mini_pasal;
CREATE USER IF NOT EXISTS 'mini_pasal_user'@'localhost' IDENTIFIED BY 'mini_pasal123';
GRANT ALL PRIVILEGES ON mini_pasal.* TO 'mini_pasal_user'@'localhost';
FLUSH PRIVILEGES;
```

### Import the schema

```sql
USE mini_pasal;
SOURCE /path/to/mini-pasal/schema.sql;
```

If you are on Windows, use the correct file path such as:

```sql
USE mini_pasal;
SOURCE C:/path/to/mini-pasal/schema.sql;
```

### Database settings in the app

The default values in [db.php](db.php) are:

```php
$host = getenv('DB_HOST') ?: 'localhost';
$dbuser = getenv('DB_USER') ?: 'mini_pasal_user';
$dbpass = getenv('DB_PASS') ?: 'mini_pasal123';
$dbname = getenv('DB_NAME') ?: 'mini_pasal';
```

You can override these with environment variables if needed:

```bash
export DB_HOST=localhost
export DB_USER=mini_pasal_user
export DB_PASS=mini_pasal123
export DB_NAME=mini_pasal
```

## Default Admin Login

The seeded admin account is:

- Username: `admin`
- Password: `admin123`

Login URL:

```text
http://localhost:8000/signin.php
```

Admin users are redirected to the admin dashboard automatically.

## Registration and Login

Users can sign up from:

```text
http://localhost:8000/signup.php
```

After registration, they can log in with either their email or username.

## Payment Configuration

The eSewa test configuration is in [config.php](config.php).

Default callback URLs:

```php
define('SUCCESS_URL', 'http://localhost:8000/success.php');
define('FAILURE_URL', 'http://localhost:8000/failure.php');
```

If you run the project on another port or host, update those values accordingly.

## Default Book Seed Data

The sample books and admin account are defined in [seeds.php](seeds.php). The app automatically inserts these into the database if the tables are empty.

If you want to regenerate the initial books manually, run:

```bash
php seed.php
```

This clears the current `books` table and repopulates it with the seeded catalog from [seeds.php](seeds.php).

## Troubleshooting

### MySQL connection fails

Check:

- MySQL is running
- database name matches `mini_pasal`
- username/password match `mini_pasal_user` / `mini_pasal123`
- the user has privileges on the database

### App shows fallback data only

This usually means the PHP app could not connect to MySQL. Check [db.php](db.php) and verify the database user exists.

### Admin login does not work

Use the seeded admin details:

- username: `admin`
- password: `admin123`

### Books do not appear in the storefront

- Make sure the database has been imported
- Confirm that the `books` table is populated
- Check whether the app fell back to JSON instead of MySQL

### Images are not loading

The app uses inline SVG placeholders so the catalog still works without remote assets. If you add real image URLs, they should be valid and accessible from the browser.

## Notes

This project is designed for local development and demonstration. The payment flow uses the eSewa sandbox environment rather than a production payment setup.

---

If you are setting up this project for a fresh clone, the usual flow is:

1. Create the database and user
2. Import [schema.sql](schema.sql)
3. Start PHP with `php -S localhost:8000`
4. Open `http://localhost:8000/index.php`
5. Log in as the admin or create a normal user account

