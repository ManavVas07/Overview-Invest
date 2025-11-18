# Overview Invest

Overview Invest is a lightweight stock market simulation game built with PHP, MySQL, HTML, CSS, and JavaScript. It provides individual users with a practice trading environment and offers administrators tools to manage listed stocks, supervise users, and trigger price updates.

## Features
- User registration and login with secure password hashing.
- Virtual balance with the ability to buy and sell listed stocks.
- Automatic stock price simulation that refreshes on a schedule or on-demand.
- User portfolio and transaction history tracking.
- Administrator portal to add/update/delete stocks and manage user status.

## Getting Started (XAMPP)

1. **Clone or copy** this project into your XAMPP `htdocs` directory, e.g.
   ```
   C:\xampp\htdocs\overview-invest
   ```

2. **Create the database schema**:
   - Start Apache and MySQL via the XAMPP control panel.
   - Open phpMyAdmin and run the SQL script in `initialize_db.sql`, or use the MySQL command line:
     ```
     mysql -u root < initialize_db.sql
     ```
   - Re-run the script if you previously imported an older version so the new `admins` table is created.

3. **Configure database credentials**:
   - Default credentials in `config.php` assume `root` with no password and the database name `ov`. Adjust the constants at the top of the file if your environment differs.

4. **Access the site**:
   - Visit `http://localhost/overview-invest/` for the landing page.
   - Admin login only asks for the password; use the seeded password `admin123`.
   - Create regular user accounts via the registration page.

## Automatic Price Updates
- `assets/app.js` pings `auto_update.php` every 15 seconds while a user dashboard is open, applying random percentage changes to each stock.
- Administrators can also trigger the same routine manually from the dashboard with the "Run Automatic Update" button.

## Project Structure
```
config.php           // Database connection and helpers
index.php            // Landing page
login.php            // User login
register.php         // User registration
dashboard.php        // User trading dashboard
admin_login.php      // Admin authentication
admin_dashboard.php  // Admin management portal
auto_update.php      // Automatic price updater & helper function
logout.php           // Session teardown
assets/
  ├─ styles.css      // Shared styling
  └─ app.js          // Client-side auto-update logic
initialize_db.sql    // Database schema and seed data (creates users, admins, stocks, portfolios, transactions)
```

## Security Notes
- Passwords are stored using PHP's `password_hash`/`password_verify`.
- Form submissions use prepared statements to mitigate SQL injection.
- Additional safeguards (CSRF tokens, stronger auth policies, audit logs) can be added based on deployment requirements.

## License
This project is provided as-is for educational purposes. Customize and extend it to suit your needs.

