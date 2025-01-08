<?php
namespace OneMigrator;

class MigrationManager
{
    private \PDO $pdo;
    private string $migrationPath;
    private string $table = 'migrations';

    public function __construct(\PDO $pdo, string $migrationPath)
    {
        $this->pdo = $pdo;
        $this->migrationPath = $migrationPath;
        $this->initialize();
    }

    public function initialize(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            version VARCHAR(180) PRIMARY KEY,
            description VARCHAR(255),
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            checksum VARCHAR(64)
        )";
        $this->pdo->exec($sql);
    }

    public function migrate(): array
    {
        $applied = [];
        $migrations = $this->loadMigrationFiles();
        
        foreach ($migrations as $migration) {
            $existingChecksum = $this->getStoredChecksum($migration->getVersion());
            
            if ($existingChecksum === null) {
                // New migration
                $this->executeMigration($migration);
                $applied[] = $migration->getVersion();
            } else if ($existingChecksum !== $migration->getChecksum()) {
                // Changed migration - update it
                $this->updateMigration($migration);
                $applied[] = $migration->getVersion();
            }
        }
        
        return $applied;
    }

    private function executeMigration(Migration $migration): void
    {
        // Execute migration SQL
        $this->pdo->exec($migration->getSql());
        
        // Record migration
        $stmt = $this->pdo->prepare("INSERT INTO {$this->table} 
            (version, checksum) VALUES (?, ?)");
        $stmt->execute([
            $migration->getVersion(),
            $migration->getChecksum()
        ]);
    }

    private function loadMigrationFiles(): array 
    {
        $migrations = [];
        
        // Load PHP migrations
        $phpFiles = glob($this->migrationPath . '/*.php');
        foreach ($phpFiles as $file) {
            $migration = require $file;
            if (!$migration instanceof Migration) {
                throw new \RuntimeException("Migration file must return Migration instance: $file");
            }
            $migrations[$migration->getVersion()] = $migration;
        }
        
        // Load SQL migrations
        $sqlFiles = glob($this->migrationPath . '/*.sql');
        foreach ($sqlFiles as $file) {
            $version = $this->extractVersionFromFilename($file);
            $sql = file_get_contents($file);
            $description = $this->extractDescriptionFromSql($sql);
            $migrations[$version] = new Migration($version, $description, $sql);
        }
        
        ksort($migrations); // Sort by version number
        return $migrations;
    }

    private function extractVersionFromFilename(string $file): string
    {
        $basename = basename($file, '.sql');
        if (preg_match('/^(\d{3})/', $basename, $matches)) {
            return $matches[1];
        }
        throw new \RuntimeException("SQL migration filename must start with 3 digits: $file");
    }

    private function extractDescriptionFromSql(string $sql): string
    {
        // Extract description from SQL comment if exists
        if (preg_match('/^-- Description: (.+)$/m', $sql, $matches)) {
            return trim($matches[1]);
        }
        return 'SQL Migration';
    }

    private function getStoredChecksum(string $version): ?string 
    {
        $stmt = $this->pdo->prepare("SELECT checksum FROM {$this->table} WHERE version = ?");
        $stmt->execute([$version]);
        $result = $stmt->fetch(\PDO::FETCH_COLUMN);
        return $result ?: null;
    }

    private function updateMigration(Migration $migration): void 
    {
        $this->pdo->beginTransaction();
        
        try {
            // Store the original table state
            $backupTable = $this->table . '_backup_' . $migration->getVersion();
            $this->pdo->exec("CREATE TABLE {$backupTable} LIKE {$this->table}");
            $this->pdo->exec("INSERT INTO {$backupTable} SELECT * FROM {$this->table}");
            
            // Execute the modified migration SQL
            $this->pdo->exec($migration->getSql());
            
            // Update the checksum in migrations table
            $stmt = $this->pdo->prepare("UPDATE {$this->table} SET 
                checksum = ?, 
                executed_at = CURRENT_TIMESTAMP 
                WHERE version = ?");
            $stmt->execute([
                $migration->getChecksum(), 
                $migration->getVersion()
            ]);
            
            // If successful, drop backup
            $this->pdo->exec("DROP TABLE IF EXISTS {$backupTable}");
            $this->pdo->commit();
            
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            // Restore from backup if exists
            if ($this->tableExists($backupTable)) {
                $this->pdo->exec("DROP TABLE IF EXISTS {$this->table}");
                $this->pdo->exec("RENAME TABLE {$backupTable} TO {$this->table}");
            }
            throw new \RuntimeException("Failed to update migration {$migration->getVersion()}: " . $e->getMessage());
        }
    }

    private function tableExists(string $tableName): bool
    {
        try {
            $result = $this->pdo->query("SELECT 1 FROM {$tableName} LIMIT 1");
            return $result !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function verifyChecksums(): array
    {
        $invalid = [];
        $stmt = $this->pdo->prepare("SELECT version, checksum FROM {$this->table}");
        $stmt->execute();
        
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            // Compare stored checksum with current file checksum
            // Add to invalid array if mismatch
        }
        
        return $invalid;
    }

    private function getPendingMigrations(): array
    {
        // Logic to get pending migrations
        return [];
    }
}
