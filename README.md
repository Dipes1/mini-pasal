# Mini Pasal

Mini Pasal is a PHP bookstore project built for local development. It includes a storefront, book search/filter, admin panel, cart, signup/sign-in, checkout, and eSewa sandbox integration.

This project is designed so that anyone who pulls it from GitHub can set it up locally without confusion.

## Project Features

- Book catalog with search and filter
- Book details modal
- Add to cart flow
- User sign up and sign in
- Admin login and admin dashboard
- Add, edit, and delete books from admin
- Checkout with eSewa sandbox
- MySQL database support with JSON fallback for local demo setups

## Requirements

Before running the project, install:

- PHP 8.0+
- MySQL 8.0+
- Apache or a local PHP server
- Composer is not required for this project

## Local Setup

Choose the setup below that matches your machine.

### Option A: Windows Setup

#### 1. Install PHP and MySQL

Install these tools:

- PHP 8.0+
- MySQL 8.0+
- Apache or XAMPP/WAMP

A common choice is XAMPP, which gives you PHP + MySQL + Apache together.

#### 2. Clone the project

```powershell
git clone https://github.com/<your-username>/<your-repo>.git
cd mini-pasal
```

#### 3. Start MySQL and create the database

Open MySQL Command Line or phpMyAdmin and run:

```sql
CREATE DATABASE IF NOT EXISTS mini_pasal;

CREATE USER IF NOT EXISTS 'mini_pasal_user'@'localhost' IDENTIFIED BY 'mini_pasal123';
GRANT ALL PRIVILEGES ON mini_pasal.* TO 'mini_pasal_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 4. Import the schema

In MySQL:

```sql
USE mini_pasal;
SOURCE C:/path/to/mini-pasal/schema.sql;
```

If using phpMyAdmin, import the file from the project folder.

#### 5. Run the project

From the project folder:

```powershell
php -S localhost:8000
```

Then open:

```text
http://localhost:8000/index.php
```

### Option B: Linux / macOS Setup

#### 1. Clone the project

```bash
git clone https://github.com/<your-username>/<your-repo>.git
cd mini-pasal
```

#### 2. Start MySQL

Make sure MySQL is running locally.

If you use a system with MySQL installed:

```bash
sudo systemctl start mysql
```

Or with MariaDB:

```bash
sudo systemctl start mariadb
```

#### 3. Create the database and app user

Open MySQL as root:

```bash
sudo mysql -u root
```

Run:

```sql
CREATE DATABASE IF NOT EXISTS mini_pasal;

CREATE USER IF NOT EXISTS 'mini_pasal_user'@'localhost' IDENTIFIED BY 'mini_pasal123';
GRANT ALL PRIVILEGES ON mini_pasal.* TO 'mini_pasal_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 4. Import the schema

Run this in MySQL:

```sql
USE mini_pasal;
SOURCE /home/dipesh/mini-pasal/schema.sql;
```

If you are using the project in a different folder, replace the path with the correct location of `schema.sql`.

#### 5. Confirm database config

Open [db.php](db.php) and make sure the values match your environment:

```php
$host = getenv('DB_HOST') ?: 'localhost';
$dbuser = getenv('DB_USER') ?: 'mini_pasal_user';
$dbpass = getenv('DB_PASS') ?: 'mini_pasal123';
$dbname = getenv('DB_NAME') ?: 'mini_pasal';
```

If needed, you can override with environment variables:

```bash
export DB_HOST=localhost
export DB_USER=mini_pasal_user
export DB_PASS=mini_pasal123
export DB_NAME=mini_pasal
```

### 6. Start the PHP server

From the project folder:

```bash
php -S localhost:8000
```

Then open:

```text
http://localhost:8000/index.php
```

## Default Admin Login

The seeded admin account is:

- Username: `admin`
- Password: `admin123`

Log in from:

```text
http://localhost:8000/signin.php
```

Admin users are redirected to the admin page automatically.

## Default User Registration

Users can sign up from:

```text
http://localhost:8000/signup.php
```

Then log in with their email or username.

## Project Files

- `index.php` — storefront and book listing
- `admin.php` — admin dashboard to add/edit/delete books
- `signin.php` — login page
- `signup.php` — user registration
- `cart.php` — cart view
- `checkout.php` — checkout and eSewa form submission
- `db.php` — MySQL connection and JSON fallback
- `schema.sql` — database schema
- `seeds.php` — default books and admin info
- `config.php` — eSewa configuration
- `success.php` — payment success callback
- `failure.php` — payment failure callback

## eSewa Sandbox Payment Setup

The payment configuration is in [config.php](config.php).

Important local settings:

```php
define('SUCCESS_URL', 'http://localhost:8000/success.php');
define('FAILURE_URL', 'http://localhost:8000/failure.php');
```

If you run the site on a different local port or host, update those values accordingly.

## eSewa Test Flow

1. Add books to cart.
2. Go to checkout.
3. Enter customer details.
4. Submit the form.
5. The app creates a pending transaction and redirects to the eSewa sandbox payment page.
6. eSewa redirects back to the success or failure page.

## Troubleshooting

### MySQL connection fails

Check:

- MySQL is running
- database name is correct
- username/password are correct
- database user has privileges on `mini_pasal`

### App still uses fallback mode

This means the PHP connection to MySQL failed. Check the values in [db.php](db.php) and verify the database user exists.

### Admin login does not work

Use the seeded admin credentials:

- username: `admin`
- password: `admin123`

### Books do not show up

Make sure the `books` table is populated and the project can read from MySQL. You can verify with:

```bash
mysql -u mini_pasal_user -pmini_pasal123 -e "USE mini_pasal; SELECT * FROM books;"
```

## Notes

This project includes a JSON fallback mode for local demo use when MySQL is unavailable. In a full setup, MySQL is the primary database source.

## License

This project is for educational and local development use.
