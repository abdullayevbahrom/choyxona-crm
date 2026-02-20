<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class BackupDatabase extends Command
{
    protected $signature = "backup:database {--prune-days=30 : Remove old backups after successful run}";

    protected $description = "Create a compressed database backup in storage/app/backups/databases";

    public function handle(): int
    {
        try {
            $backupPath = $this->createBackup();
            $this->pruneOldBackups((int) $this->option("prune-days"));
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Database backup created: {$backupPath}");

        return self::SUCCESS;
    }

    private function createBackup(): string
    {
        $driver = config("database.default");
        $connection = config("database.connections.{$driver}");

        if (!is_array($connection)) {
            throw new RuntimeException(
                "Unknown database connection [{$driver}].",
            );
        }

        $connectionDriver = $connection["driver"] ?? "unknown";

        return match ($connectionDriver) {
            "sqlite" => $this->backupSqlite($connection),
            "mysql", "mariadb" => $this->backupMysql($connection),
            default => throw new RuntimeException(
                "Unsupported driver [{$connectionDriver}].",
            ),
        };
    }

    private function backupSqlite(array $connection): string
    {
        $database = $connection["database"] ?? null;

        if (!is_string($database) || $database === "") {
            throw new RuntimeException(
                "SQLite database path is not configured.",
            );
        }

        $sourcePath = str_starts_with($database, DIRECTORY_SEPARATOR)
            ? $database
            : base_path($database);

        if (!File::exists($sourcePath)) {
            throw new RuntimeException(
                "SQLite database file not found at [{$sourcePath}].",
            );
        }

        $content = File::get($sourcePath);
        $compressed = gzencode($content, 9);

        if ($compressed === false) {
            throw new RuntimeException("Failed to compress sqlite database.");
        }

        $targetPath =
            $this->backupDirectory() .
            DIRECTORY_SEPARATOR .
            $this->backupFilename("sqlite");
        File::put($targetPath, $compressed);

        return $targetPath;
    }

    private function backupMysql(array $connection): string
    {
        $dumpBinary = $this->resolveMysqlDumpBinary();
        $database = (string) ($connection["database"] ?? "");
        $host = (string) ($connection["host"] ?? "127.0.0.1");
        $port = (string) ($connection["port"] ?? "3306");
        $username = (string) ($connection["username"] ?? "");
        $password = (string) ($connection["password"] ?? "");

        if ($database === "" || $username === "") {
            throw new RuntimeException("MySQL credentials are incomplete.");
        }

        $command = [
            $dumpBinary,
            "--host={$host}",
            "--port={$port}",
            "--user={$username}",
            "--password={$password}",
            "--single-transaction",
            "--quick",
            "--lock-tables=false",
        ];

        $sslMode = (string) env("DB_DUMP_SSL_MODE", "DISABLED");
        if (strtoupper($sslMode) === "DISABLED") {
            $command[] = "--skip-ssl";
        } elseif (strtoupper($sslMode) === "REQUIRED") {
            $command[] = "--ssl";
        }

        $command[] = $database;

        $result = Process::timeout(120)->run($command);

        if ($result->failed()) {
            throw new RuntimeException(
                trim($result->errorOutput()) ?: "mysqldump failed.",
            );
        }

        $compressed = gzencode($result->output(), 9);

        if ($compressed === false) {
            throw new RuntimeException("Failed to compress mysql dump.");
        }

        $targetPath =
            $this->backupDirectory() .
            DIRECTORY_SEPARATOR .
            $this->backupFilename("mysql");
        File::put($targetPath, $compressed);

        return $targetPath;
    }

    private function resolveMysqlDumpBinary(): string
    {
        $result = Process::run([
            "sh",
            "-lc",
            "command -v mysqldump || command -v mariadb-dump",
        ]);

        if ($result->successful()) {
            $binary = trim($result->output());
            if ($binary !== "") {
                return $binary;
            }
        }

        return "mysqldump";
    }

    private function backupDirectory(): string
    {
        $path = storage_path("app/backups/databases");

        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        return $path;
    }

    private function backupFilename(string $driver): string
    {
        return sprintf("db-%s-%s.sql.gz", $driver, now()->format("Ymd-His"));
    }

    private function pruneOldBackups(int $days): void
    {
        if ($days < 1) {
            return;
        }

        $cutoff = now()->subDays($days);

        foreach (
            File::glob(
                $this->backupDirectory() . DIRECTORY_SEPARATOR . "db-*.sql.gz",
            )
            as $file
        ) {
            if (File::lastModified($file) < $cutoff->getTimestamp()) {
                File::delete($file);
            }
        }
    }
}
