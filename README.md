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

Save SQL files with version prefix like `001_create_users.sql`. The version number (001) will be automatically extracted from the filename. The description will be parsed from the SQL comment starting with `-- Description:`.

The [`Migration`](src/Migration.php) class will automatically calculate a SHA-256 checksum of your SQL to ensure integrity.

When using the [`MigrationManager`](src/MigrationManager.php), it will:
- Create a migrations table if it doesn't exist
- Execute new migrations in order by version number
- Track executed migrations with their checksums
- Handle updates to existing migrations safely with automatic backup/rollback
- Support both .php and .sql migration files