# Kaveri Bank – Banking Management System
PHP 8.1+ · MySQL 8 · HTML5 · CSS3 (PHP is the server-side glue HTML needs to talk to MySQL)

## Setup (XAMPP / WAMP / any PHP + MySQL host)
1. Copy this folder to `htdocs/bank` (or run `php -S localhost:8000` inside it).
2. Import the schema: `mysql -u root -p < database.sql`
3. Edit `config.php` with your MySQL user and password.
4. Create an admin: `php create_admin.php admin@bank.com "Admin" "StrongPass123"`
5. Open http://localhost/bank/ and register a customer.

## Features
- Customer registration, sign-in, sign-out, several accounts per customer (savings/current)
- Deposit, withdraw and transfer between accounts, with a paginated statement and unique references
- Admin: overview of all accounts, freeze/unfreeze, security log

## Security design
| Risk | Control |
|---|---|
| SQL injection | PDO prepared statements everywhere (emulation off) |
| Password theft | `password_hash` (bcrypt/argon), never stored in plain text |
| Brute force | 5 failed sign-ins lock the user for 15 minutes |
| Session fixation/hijack | `session_regenerate_id`, HttpOnly + SameSite=Strict cookies, 15-minute idle timeout |
| CSRF | Per-session token checked on every POST (including sign out) |
| XSS | All output escaped with `htmlspecialchars`; CSP header |
| Lost or doubled money | InnoDB transactions with `SELECT … FOR UPDATE`; balance changes happen in SQL, not floats; `CHECK (balance >= 0)` |
| Deadlocks | Transfer locks both accounts in id order |
| Unauthorised moves | Account ownership checked on every operation; password re-entry for withdraw/transfer |
| Tampering trail | `audit_log` records sign-ins, failures and money movements with IP |

## Before going live
Serve over HTTPS, use a MySQL user with only SELECT/INSERT/UPDATE rights, and disable PHP error display.
