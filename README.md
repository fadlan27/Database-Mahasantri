# Database Mahasantri & EduCal Pro

## Project info

**URL**: https://github.com/fadlan27/Database-Mahasantri

## What is this?

A comprehensive **Student Management System** for Islamic Boarding Schools (Pondok Pesantren), featuring **EduCal Pro**—an advanced academic calendar with Hijri support, priority-based event scheduling, and student data management (Bio, Violations, Achievements).

## What technologies are used for this project?

This project is built with robust, industry-standard technologies:

- **PHP 8.x** (Native Backend)
- **MySQL / MariaDB** (Database)
- **Tailwind CSS** (Modern Styling)
- **Vanilla JavaScript** (Interactive Frontend)
- **Lucide Icons** (UI Assets)

## How can I run this locally?

You need a local server environment like **Laragon**, **XAMPP**, or **MAMP**.

**Follow these steps:**

```sh
# Step 1: Clone the repository into your web root (e.g., c:/laragon/www)
git clone https://github.com/fadlan27/Database-Mahasantri.git

# Step 2: Navigate to the project directory
cd "Database Mahasantri"

# Step 3: Database Setup
# Open your database manager (phpMyAdmin/HeidiSQL).
# Create a new database named 'mahasantri_db' (or matching config).
# Import the provided SQL structure/data.

# Step 4: Run
# Open your browser and visit: http://database-mahasantri.test
# (Or http://localhost/Database-Mahasantri depending on your setup)
```

## How can I edit this code?

**Use your preferred IDE**

We recommend using **VS Code** with PHP extensions.
- Clone the repo.
- Open the folder in your IDE.
- Make changes to `calendar.php` or other modules.
- Changes are reflected instantly on refresh (no build step required for PHP).

**Edit a file directly in GitHub**

- Navigate to the desired file(s).
- Click the "Edit" button (pencil icon) at the top right of the file view.
- Make your changes and commit the changes.

## How can I deploy this project?

1.  **Upload Files**: Upload the contents of the root folder to your hosting's `public_html` via FTP or File Manager.
2.  **Database**: Export your local database (`.sql`) and import it to your live hosting's Database.
3.  **Configuration**: Open `config/database.php` (or connection file) and update the credentials:
    ```php
    $host = 'localhost';
    $user = 'your_hosting_user';
    $pass = 'your_hosting_pass';
    $db   = 'your_hosting_dbname';
    ```
4.  **Done**: Your application is now live!

## Can I connect a custom domain?

Yes! If using cPanel or a VPS, simply point your domain's **A Record** to your server IP or set the Nameservers to your hosting provider.

---
*Maintained by [Fadlan27](https://github.com/fadlan27)*