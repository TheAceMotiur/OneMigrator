# OneMigrator

OneMigrator is a database migration system with checksum support.

## Installation

```sh
composer require onemigrator/onemigrator
```

## Usage

### Creating a Migration

Create a migration file in your migrations directory. You can use either PHP or SQL files:

#### PHP Migration
```php
// migrations/001_create_users.php
use OneMigrator\Migration;

return new Migration(
    '001',                    // Version must be 3 digits
    'Create users table',     // Description
    "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        email VARCHAR(255) UNIQUE
    )"
);
```

#### SQL Migration
```sql
-- Description: Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE
);
```
Save SQL files with version prefix: `001_create_users.sql`

### Running Migrations

```php
use OneMigrator\MigrationManager;

$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'user', 'password');
$manager = new MigrationManager($pdo, __DIR__ . '/migrations');

// Run migrations
$applied = $manager->migrate();

// Verify migration integrity
$invalid = $manager->verifyChecksums();
```

## Features

- Supports both PHP and SQL migration files
- Automatic checksum verification for migration integrity
- Transaction support for safe migration updates
- Version-based migration tracking
- Migrations table auto-creation
- PSR-4 autoloading
- PDO database support

## Requirements

- PHP 7.4 or higher
- PDO extension