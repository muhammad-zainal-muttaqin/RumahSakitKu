<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database
                            {--type=database : Type of backup (database or full)}
                            {--compress : Compress the backup file}
                            {--cleanup : Clean up old backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $compress = $this->option('compress');
        $cleanup = $this->option('cleanup');

        $this->info('Starting database backup...');

        try {
            $backupPath = 'backups';
            $filename = 'scheduled_backup_' . now()->format('Y-m-d_His') . ($type === 'full' ? '_full' : '') . '.sql';
            $filepath = storage_path("app/{$backupPath}/{$filename}");

            // Ensure backup directory exists
            if (!Storage::disk('local')->exists($backupPath)) {
                Storage::disk('local')->makeDirectory($backupPath);
            }

            // Get database configuration
            $dbConfig = config('database.connections.' . config('database.default'));

            if ($dbConfig['driver'] !== 'mysql') {
                $this->error('Only MySQL database driver is supported.');
                return self::FAILURE;
            }

            // Create backup using mysqldump
            $command = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s %s > %s',
                escapeshellarg($dbConfig['host'] ?? 'localhost'),
                escapeshellarg($dbConfig['port'] ?? '3306'),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['password'] ?? ''),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($filepath)
            );

            $process = Process::fromShellCommandline($command);
            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new Exception('mysqldump failed: ' . $process->getErrorOutput());
            }

            // Compress if requested
            if ($compress) {
                $this->info('Compressing backup...');
                $gzFilepath = $filepath . '.gz';
                $gz = gzopen($gzFilepath, 'w9');
                $fp = fopen($filepath, 'r');
                while (!feof($fp)) {
                    gzwrite($gz, fread($fp, 1024 * 512));
                }
                fclose($fp);
                gzclose($gz);
                unlink($filepath);
                $filepath = $gzFilepath;
                $filename .= '.gz';
            }

            // If full backup, include uploads
            if ($type === 'full') {
                $this->info('Creating full backup with uploads...');
                $zipFilename = str_replace('.sql.gz', '.zip', $filename);
                $zipPath = storage_path("app/{$backupPath}/{$zipFilename}");

                $zip = new ZipArchive();
                if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                    $zip->addFile($filepath, $filename);

                    // Add uploads directory
                    $uploadsPath = storage_path('app/public');
                    if (is_dir($uploadsPath)) {
                        $files = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($uploadsPath),
                            RecursiveIteratorIterator::LEAVES_ONLY
                        );

                        foreach ($files as $file) {
                            if (!$file->isDir()) {
                                $filePath = $file->getRealPath();
                                $relativePath = 'uploads/' . substr($filePath, strlen($uploadsPath) + 1);
                                $zip->addFile($filePath, $relativePath);
                            }
                        }
                    }

                    $zip->close();
                    unlink($filepath);
                }
            }

            // Clean up old backups if requested
            if ($cleanup) {
                $this->cleanupOldBackups($backupPath);
            }

            // Log success
            Log::channel('audit')->info('Scheduled backup completed', [
                'filename' => $filename,
                'type' => $type,
                'compressed' => $compress,
            ]);

            $this->info("Backup completed successfully: {$filename}");
            return self::SUCCESS;
        } catch (Exception $e) {
            Log::channel('audit')->error('Scheduled backup failed', [
                'error' => $e->getMessage(),
            ]);

            $this->error('Backup failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Clean up old backups (keep last 30 days).
     */
    protected function cleanupOldBackups(string $backupPath): void
    {
        $this->info('Cleaning up old backups...');

        $files = collect(Storage::disk('local')->files($backupPath))
            ->filter(fn ($file) => str_ends_with($file, '.sql') || str_ends_with($file, '.sql.gz') || str_ends_with($file, '.zip'))
            ->filter(function ($file) {
                $modified = Storage::disk('local')->lastModified($file);
                return $modified < now()->subDays(30)->timestamp;
            });

        $count = 0;
        foreach ($files as $file) {
            Storage::disk('local')->delete($file);
            $count++;
        }

        $this->info("Deleted {$count} old backup(s).");
    }
}
