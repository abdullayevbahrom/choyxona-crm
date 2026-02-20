<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_database_command_creates_gzip_backup_for_sqlite_file(): void
    {
        $sqlitePath = database_path('backup-test.sqlite');

        File::put($sqlitePath, 'test-sqlite-content');

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', $sqlitePath);

        $this->artisan('backup:database --prune-days=30')
            ->expectsOutputToContain('Database backup created:')
            ->assertExitCode(0);

        $files = File::glob(storage_path('app/backups/databases/db-sqlite-*.sql.gz'));

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            File::delete($file);
        }

        File::delete($sqlitePath);
    }
}
