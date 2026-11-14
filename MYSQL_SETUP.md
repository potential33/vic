# MySQL setup

The application requires PHP's `pdo_mysql` extension and a MySQL 8+ database.
The one-time SQLite importer additionally requires `pdo_sqlite`.
Configure these environment variables for the PHP process:

| Variable | Required | Default |
| --- | --- | --- |
| `DB_HOST` | No | `localhost` |
| `DB_PORT` | No | `3306` |
| `DB_NAME` | Yes | — |
| `DB_USER` | Yes | — |
| `DB_PASSWORD` | No | Empty |
| `DB_CHARSET` | No | `utf8mb4` |

Create a database and a dedicated application user in MySQL, then apply
[schema.sql](./schema.sql) to that database. For example, from a shell with the
MySQL client installed:

```sh
mysql -h DB_HOST -u DB_USER -p DB_NAME < schema.sql
```

The account applying the schema needs permission to create and drop triggers;
the application does not need those permissions at runtime.

Set the connection variables in the hosting provider's environment settings;
do not commit database credentials to the repository. For a new production
database, initialize the application's defaults with:

```sh
php migrate_settings.php
php migrate_results.php
php migrate_reports.php
```

Create the first administrator with your deployment's account-management
process. `seed_admin.php` is a development helper that creates the predictable
`admin` / `admin123` account; if you use it, change that password immediately.
`init.php` adds the schema without deleting records and inserts demo school
data only if the database has no sessions; do not use demo seed data for a
production school.

To create login accounts for existing students and teachers, run:

```sh
php create_student_users.php
php create_teacher_users.php
```

Generated students sign in with their admission number, and teachers with
their email address. Both account scripts set the initial password to
`password123`; student accounts require a password change after sign-in.
Accounts already linked to a student or teacher are left unchanged by these
scripts.

## Importing an existing SQLite database

Back up the SQLite file and create an empty MySQL database with the schema
already applied. Set the MySQL connection variables above and, if the source
file is not `school.sqlite` in the project directory, set `SQLITE_SOURCE` to its
path. Run:

```sh
php migrate_sqlite_to_mysql.php
```

The importer preserves the SQLite IDs and imports tables in foreign-key order.
It refuses to import into any database whose application tables already
contain records. Review the printed row counts and test the application before
switching production traffic to MySQL.

## Assessment weights

The active components are CA1 (10), CA2 (20), and Exam (70). To apply these
weights to an existing database, back it up and run:

```sh
php migrate_ca_weights.php
```

This migration disables other grading components, normalizes Exam's internal
name, recalculates saved totals from CA1, CA2, and Exam, and clears legacy CA3
scores. The student report and grade-entry screens no longer display CA3.
