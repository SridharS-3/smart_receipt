<?php
// database/migrate.php - SmartReceipt Database Migration & Seed Manager

// Only allow execution from CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Error: This script can only be run from the command line.\n");
}

require_once __DIR__ . '/../config/env.php';

// ANSI Color helper for terminal output
class CliColor {
    public static function green(string $text): string {
        return "\033[32m{$text}\033[0m";
    }
    public static function red(string $text): string {
        return "\033[31m{$text}\033[0m";
    }
    public static function yellow(string $text): string {
        return "\033[33m{$text}\033[0m";
    }
    public static function cyan(string $text): string {
        return "\033[36m{$text}\033[0m";
    }
    public static function bold(string $text): string {
        return "\033[1m{$text}\033[0m";
    }
}

class MigrationManager {
    private PDO $pdo;
    private string $dbName;
    private string $migrationsDir;
    private string $seedsDir;

    public function __construct() {
        $this->migrationsDir = __DIR__ . '/migrations';
        $this->seedsDir = __DIR__ . '/seeds';

        $dbHost = env('DB_HOST', '127.0.0.1');
        $dbPort = env('DB_PORT', '3306');
        $this->dbName = env('DB_DATABASE', 'smart_receipt_db');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');
        $dbCharset = env('DB_CHARSET', 'utf8mb4');
        $dbSocket = env('DB_SOCKET', '/opt/lampp/var/mysql/mysql.sock');

        // First connect to MySQL server (without selecting DB) in case DB needs creation
        $serverDsn = "mysql:host={$dbHost};port={$dbPort};charset={$dbCharset}";
        $pdoOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ];

        try {
            $initialPdo = new PDO($serverDsn, $dbUser, $dbPass, $pdoOptions);
        } catch (\PDOException $e) {
            if (!empty($dbSocket) && file_exists($dbSocket)) {
                $serverDsn = "mysql:unix_socket={$dbSocket};charset={$dbCharset}";
                $initialPdo = new PDO($serverDsn, $dbUser, $dbPass, $pdoOptions);
            } else {
                echo CliColor::red("Database connection failed: ") . $e->getMessage() . "\n";
                exit(1);
            }
        }

        // Ensure database exists
        $initialPdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Connect directly to the database
        $dbDsn = "mysql:host={$dbHost};port={$dbPort};dbname={$this->dbName};charset={$dbCharset}";
        try {
            $this->pdo = new PDO($dbDsn, $dbUser, $dbPass, $pdoOptions);
        } catch (\PDOException $e) {
            if (!empty($dbSocket) && file_exists($dbSocket)) {
                $dbDsn = "mysql:unix_socket={$dbSocket};dbname={$this->dbName};charset={$dbCharset}";
                $this->pdo = new PDO($dbDsn, $dbUser, $dbPass, $pdoOptions);
            } else {
                echo CliColor::red("Failed to select database '{$this->dbName}': ") . $e->getMessage() . "\n";
                exit(1);
            }
        }

        $this->initMigrationsTable();
    }

    /**
     * Create the migrations tracking table if not exists.
     */
    private function initMigrationsTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT(11) NOT NULL,
            `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_migrations_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->pdo->exec($sql);
    }

    /**
     * Get list of already executed migrations.
     * @return array<string, array>
     */
    public function getExecutedMigrations(): array {
        $stmt = $this->pdo->query("SELECT migration, batch, executed_at FROM `migrations` ORDER BY id ASC");
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[$row['migration']] = $row;
        }
        return $results;
    }

    /**
     * Get list of all migration files on disk.
     * @return array<string>
     */
    public function getMigrationFiles(): array {
        if (!is_dir($this->migrationsDir)) {
            return [];
        }
        $files = scandir($this->migrationsDir);
        $migrations = [];
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $migrations[] = $file;
            }
        }
        sort($migrations);
        return $migrations;
    }

    /**
     * Get the next batch number.
     */
    private function getNextBatchNumber(): int {
        $stmt = $this->pdo->query("SELECT COALESCE(MAX(batch), 0) + 1 AS next_batch FROM `migrations`");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Run all pending migrations.
     */
    public function migrate(): void {
        echo CliColor::bold("\n⚡ SmartReceipt Migration Manager\n");
        echo "Database: " . CliColor::cyan($this->dbName) . "\n\n";

        $executed = $this->getExecutedMigrations();
        $files = $this->getMigrationFiles();

        if (empty($files)) {
            echo CliColor::yellow("No migration files found in {$this->migrationsDir}.\n");
            return;
        }

        $pending = [];
        foreach ($files as $file) {
            if (!isset($executed[$file])) {
                $pending[] = $file;
            }
        }

        if (empty($pending)) {
            echo CliColor::green("✓ Nothing to migrate. Database is already up to date!\n\n");
            return;
        }

        $batch = $this->getNextBatchNumber();
        echo "Running " . count($pending) . " pending migration(s) [Batch #{$batch}]...\n\n";

        $insertStmt = $this->pdo->prepare("INSERT INTO `migrations` (`migration`, `batch`, `executed_at`) VALUES (?, ?, NOW())");

        foreach ($pending as $migrationFile) {
            $filePath = $this->migrationsDir . '/' . $migrationFile;
            $sql = file_get_contents($filePath);

            if ($sql === false || trim($sql) === '') {
                echo CliColor::yellow("  [!] Skipping empty file: {$migrationFile}\n");
                continue;
            }

            echo "  migrating: {$migrationFile} ... ";
            $startTime = microtime(true);

            try {
                // Execute multi-statement SQL
                $this->pdo->exec($sql);
                // Flush multi-statement buffers
                while ($this->pdo->query("SELECT 1")->fetch()) { break; }

                $insertStmt->execute([$migrationFile, $batch]);
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                echo CliColor::green("DONE") . " ({$duration} ms)\n";
            } catch (\PDOException $e) {
                echo CliColor::red("FAILED\n");
                echo CliColor::red("\nMigration Error in {$migrationFile}:\n") . $e->getMessage() . "\n\n";
                exit(1);
            }
        }

        echo CliColor::bold(CliColor::green("\n✓ All migrations successfully applied!\n\n"));
    }

    /**
     * Show migration status table.
     */
    public function status(): void {
        echo CliColor::bold("\n⚡ SmartReceipt Migration Status\n");
        echo "Database: " . CliColor::cyan($this->dbName) . "\n\n";

        $executed = $this->getExecutedMigrations();
        $files = $this->getMigrationFiles();

        printf("%-45s | %-10s | %-8s | %-20s\n", "Migration File", "Status", "Batch", "Executed At");
        echo str_repeat('-', 92) . "\n";

        foreach ($files as $file) {
            if (isset($executed[$file])) {
                $status = CliColor::green("Ran");
                $batch = (string)$executed[$file]['batch'];
                $time = $executed[$file]['executed_at'];
            } else {
                $status = CliColor::yellow("Pending");
                $batch = "-";
                $time = "-";
            }
            printf("%-45s | %-19s | %-8s | %-20s\n", $file, $status, $batch, $time);
        }
        echo "\n";
    }

    /**
     * Run seeds.
     */
    public function seed(): void {
        echo CliColor::bold("\n🌱 SmartReceipt Database Seeder\n");
        echo "Database: " . CliColor::cyan($this->dbName) . "\n\n";

        if (!is_dir($this->seedsDir)) {
            echo CliColor::yellow("No seeds directory found at {$this->seedsDir}.\n\n");
            return;
        }

        $seedFiles = glob($this->seedsDir . '/*.sql');
        if (empty($seedFiles)) {
            echo CliColor::yellow("No .sql seed files found in {$this->seedsDir}.\n\n");
            return;
        }

        foreach ($seedFiles as $file) {
            $baseName = basename($file);
            echo "  seeding: {$baseName} ... ";
            $sql = file_get_contents($file);

            try {
                $this->pdo->exec($sql);
                echo CliColor::green("DONE\n");
            } catch (\PDOException $e) {
                echo CliColor::red("FAILED\n");
                echo CliColor::red("Seeder Error in {$baseName}: ") . $e->getMessage() . "\n";
            }
        }

        echo CliColor::bold(CliColor::green("\n✓ Seeding completed successfully!\n\n"));
    }

    /**
     * Fresh migration: drop all tables and re-run migrations from scratch.
     */
    public function fresh(bool $force = false): void {
        if (!$force) {
            echo CliColor::red(CliColor::bold("WARNING: This will DROP ALL TABLES in '{$this->dbName}' and re-run all migrations!\n"));
            echo "Are you sure you want to proceed? (yes/no): ";
            $handle = fopen("php://stdin", "r");
            $line = trim(fgets($handle));
            fclose($handle);

            if (strtolower($line) !== 'yes') {
                echo "Operation cancelled.\n";
                return;
            }
        }

        echo "\nDropping all existing tables in database '{$this->dbName}'...\n";

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $stmt = $this->pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            echo "  dropped table: {$table}\n";
        }
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // Re-init migrations table and run
        $this->initMigrationsTable();
        $this->migrate();
    }
}

// Command dispatcher
$action = $argv[1] ?? 'migrate';
$manager = new MigrationManager();

switch ($action) {
    case 'migrate':
    case 'up':
    case 'run':
        $manager->migrate();
        break;

    case 'status':
        $manager->status();
        break;

    case 'seed':
        $manager->seed();
        break;

    case 'fresh':
        $force = in_array('--force', $argv, true);
        $manager->fresh($force);
        break;

    case 'help':
    case '--help':
    case '-h':
    default:
        echo CliColor::bold("\nSmartReceipt Database CLI\n\n");
        echo "Usage: php migrate.php [command] [options]\n\n";
        echo "Available Commands:\n";
        echo "  " . CliColor::cyan("migrate") . "     Run all pending migrations (default)\n";
        echo "  " . CliColor::cyan("status") . "      Show status of all migrations\n";
        echo "  " . CliColor::cyan("seed") . "        Run database seeds (sample data)\n";
        echo "  " . CliColor::cyan("fresh") . "       Drop all tables and re-run all migrations\n";
        echo "  " . CliColor::cyan("help") . "        Show this help message\n\n";
        echo "Options:\n";
        echo "  --force        Skip confirmation prompt for destructive actions (e.g. fresh)\n\n";
        break;
}

