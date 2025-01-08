# OneMigrator

OneMigrator is a database migration system with checksum support.

## Installation

```sh
composer require onemigrator/onemigrator
```

## Usage

### Creating a Migration

Create a migration file in the `migrations` directory:

```php
// migrations/2023_01_01_create_users.php
use OneMigrator\Migration;

return new Migration(
    '2023_01_01_create_users',
    'Create users table',
    "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        email VARCHAR(255) UNIQUE
    )"
);
```

### Running Migrations

```php
use OneMigrator\MigrationManager;

$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'user', 'password');
$manager = new MigrationManager($pdo, __DIR__ . '/migrations');

// Run migrations
$applied = $manager->migrate();

// Verify checksums
$invalid = $manager->verifyChecksums();
```

## Features

- Checksum verification for migration integrity
- Transaction support
- Version tracking
- Simple API
- PSR-4 autoloading
- PDO database support

## Suggested Improvements

1. Add rollback support
2. Implement dry-run mode
3. Add migration dependencies
4. Add migration status command
5. Add logging support
6. Add different database driver support
7. Add migration generation commands
