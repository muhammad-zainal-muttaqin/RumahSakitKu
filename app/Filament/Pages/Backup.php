<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;
use Illuminate\Database\Eloquent\Collection;
use Exception;
use App\Models\AuditLog;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class Backup extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static ?string $navigationLabel = 'Backup & Restore';

    protected static ?string $title = 'Backup & Restore Database';

    protected static ?int $navigationSort = 202;

    protected static string | UnitEnum | null $navigationGroup = 'Sistem';

    protected string $view = 'filament.pages.backup';

    protected ?string $disk = 'local';

    protected ?string $backupPath = 'backups';

    public function mount(): void
    {
        // Ensure backup directory exists
        if (!Storage::disk($this->disk)->exists($this->backupPath)) {
            Storage::disk($this->disk)->makeDirectory($this->backupPath);
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getBackupQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Nama File')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-document')
                    ->iconColor('primary'),

                TextColumn::make('size')
                    ->label('Ukuran')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),

                IconColumn::make('is_automated')
                    ->label('Otomatis')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn (array $data) => $this->downloadBackup($data['name'])),

                Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Restore Database?')
                    ->modalDescription('Database saat ini akan diganti dengan backup ini. Pastikan Anda sudah membuat backup terbaru.')
                    ->modalSubmitActionLabel('Ya, Restore')
                    ->visible(fn (): bool => Auth::user()?->hasRole('admin') || Auth::user()?->hasRole('super_admin'))
                    ->action(fn (array $data) => $this->restoreBackup($data['name'])),

                Action::make('delete')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (array $data) => $this->deleteBackup($data['name'])),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkAction::make('delete')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $this->deleteMultipleBackups($records)),
            ])
            ->emptyStateHeading('Belum ada backup')
            ->emptyStateDescription('Buat backup database pertama Anda.')
            ->emptyStateIcon('heroicon-o-archive-box');
    }

    protected function getBackupQuery()
    {
        $files = collect(Storage::disk($this->disk)->files($this->backupPath))
            ->filter(fn ($file) => str_ends_with($file, '.sql') || str_ends_with($file, '.sql.gz') || str_ends_with($file, '.zip'))
            ->map(function ($file) {
                return [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => $this->formatBytes(Storage::disk($this->disk)->size($file)),
                    'created_at' => now()->setTimestamp(Storage::disk($this->disk)->lastModified($file)),
                    'is_automated' => str_contains($file, 'scheduled'),
                ];
            });

        // Convert to a query-compatible format
        return new class($files) extends Collection {
            public function __construct($items)
            {
                parent::__construct($items);
            }
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_backup')
                ->label('Buat Backup')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->schema([
                    Select::make('type')
                        ->label('Tipe Backup')
                        ->options([
                            'database' => 'Database Only',
                            'full' => 'Database + File Uploads',
                        ])
                        ->default('database')
                        ->required()
                        ->native(false),

                    Toggle::make('compress')
                        ->label('Kompres File')
                        ->default(true)
                        ->helperText('Mengurangi ukuran file backup'),
                ])
                ->action(function (array $data) {
                    $this->createBackup($data['type'], $data['compress']);
                }),

            Action::make('schedule_backup')
                ->label('Jadwalkan Backup')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->schema([
                    Select::make('frequency')
                        ->label('Frekuensi')
                        ->options([
                            'daily' => 'Setiap Hari',
                            'weekly' => 'Setiap Minggu',
                            'monthly' => 'Setiap Bulan',
                        ])
                        ->required()
                        ->native(false),

                    Select::make('time')
                        ->label('Waktu')
                        ->options(array_combine(
                            array_map(fn ($h) => sprintf('%02d:00', $h), range(0, 23)),
                            array_map(fn ($h) => sprintf('%02d:00', $h), range(0, 23))
                        ))
                        ->default('02:00')
                        ->required()
                        ->native(false),

                    Toggle::make('enabled')
                        ->label('Aktifkan Jadwal')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    // Store schedule in cache or settings
                    cache()->put('backup_schedule', $data);

                    Notification::make()
                        ->title('Jadwal backup diatur')
                        ->body("Backup otomatis akan berjalan {$data['frequency']} jam {$data['time']}")
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => Auth::user()?->hasRole('admin') || Auth::user()?->hasRole('super_admin')),
        ];
    }

    /**
     * Create a new backup.
     */
    protected function createBackup(string $type, bool $compress): void
    {
        try {
            $filename = 'backup_' . now()->format('Y-m-d_His') . ($type === 'full' ? '_full' : '') . '.sql';
            $filepath = storage_path("app/{$this->backupPath}/{$filename}");

            // Get database configuration
            $dbConfig = config('database.connections.' . config('database.default'));

            if ($dbConfig['driver'] === 'mysql') {
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
                if ($compress && file_exists($filepath)) {
                    $gzFilepath = $filepath . '.gz';
                    $gz = gzopen($gzFilepath, 'w9');
                    $fp = fopen($filepath, 'r');
                    while (!feof($fp)) {
                        gzwrite($gz, fread($fp, 1024 * 512));
                    }
                    fclose($fp);
                    gzclose($gz);
                    unlink($filepath);
                    $filename .= '.gz';
                }

                // If full backup, include uploads
                if ($type === 'full') {
                    $this->createFullBackup($filename);
                }

                // Log activity
                if (class_exists('App\Models\AuditLog')) {
                    AuditLog::create([
                        'user_id' => Auth::id(),
                        'event' => 'BACKUP_CREATED',
                        'auditable_type' => 'System',
                        'auditable_id' => 0,
                        'old_values' => null,
                        'new_values' => ['filename' => $filename, 'type' => $type],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'url' => request()->fullUrl(),
                        'created_at' => now(),
                    ]);
                }

                Notification::make()
                    ->title('Backup berhasil dibuat')
                    ->body("File: {$filename}")
                    ->success()
                    ->send();
            } else {
                throw new Exception('Database driver not supported for backup.');
            }
        } catch (Exception $e) {
            Notification::make()
                ->title('Backup gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Create a full backup including uploads.
     */
    protected function createFullBackup(string $sqlFilename): void
    {
        $zipFilename = str_replace('.sql.gz', '.zip', $sqlFilename);
        $zipPath = storage_path("app/{$this->backupPath}/{$zipFilename}");
        $sqlPath = storage_path("app/{$this->backupPath}/{$sqlFilename}");

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            $zip->addFile($sqlPath, $sqlFilename);

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
            unlink($sqlPath);
        }
    }

    /**
     * Download a backup file.
     */
    protected function downloadBackup(string $filename): void
    {
        $filepath = "{$this->backupPath}/{$filename}";

        if (!Storage::disk($this->disk)->exists($filepath)) {
            Notification::make()
                ->title('File tidak ditemukan')
                ->danger()
                ->send();
            return;
        }

        $this->redirect(Storage::disk($this->disk)->url($filepath));
    }

    /**
     * Restore database from backup.
     */
    protected function restoreBackup(string $filename): void
    {
        try {
            // Only admin can restore
            if (!Auth::user()?->hasRole('admin') && !Auth::user()?->hasRole('super_admin')) {
                throw new Exception('Unauthorized');
            }

            $filepath = storage_path("app/{$this->backupPath}/{$filename}");

            if (!file_exists($filepath)) {
                throw new Exception('Backup file not found');
            }

            // Extract if compressed
            $sqlFile = $filepath;
            if (str_ends_with($filename, '.gz')) {
                $sqlFile = str_replace('.gz', '', $filepath);
                $gz = gzopen($filepath, 'r');
                $fp = fopen($sqlFile, 'w');
                while (!gzeof($gz)) {
                    fwrite($fp, gzread($gz, 1024 * 512));
                }
                gzclose($gz);
                fclose($fp);
            } elseif (str_ends_with($filename, '.zip')) {
                $zip = new ZipArchive();
                $zip->open($filepath);
                $sqlFile = storage_path("app/{$this->backupPath}/temp_restore.sql");
                $zip->extractTo(dirname($sqlFile), '*.sql*');
                $zip->close();
            }

            // Get database configuration
            $dbConfig = config('database.connections.' . config('database.default'));

            if ($dbConfig['driver'] === 'mysql') {
                $command = sprintf(
                    'mysql -h%s -P%s -u%s -p%s %s < %s',
                    escapeshellarg($dbConfig['host'] ?? 'localhost'),
                    escapeshellarg($dbConfig['port'] ?? '3306'),
                    escapeshellarg($dbConfig['username']),
                    escapeshellarg($dbConfig['password'] ?? ''),
                    escapeshellarg($dbConfig['database']),
                    escapeshellarg($sqlFile)
                );

                $process = Process::fromShellCommandline($command);
                $process->setTimeout(300);
                $process->run();

                // Cleanup temp file
                if ($sqlFile !== $filepath && file_exists($sqlFile)) {
                    unlink($sqlFile);
                }

                if (!$process->isSuccessful()) {
                    throw new Exception('mysql restore failed: ' . $process->getErrorOutput());
                }

                // Log activity
                if (class_exists('App\Models\AuditLog')) {
                    AuditLog::create([
                        'user_id' => Auth::id(),
                        'event' => 'BACKUP_RESTORED',
                        'auditable_type' => 'System',
                        'auditable_id' => 0,
                        'old_values' => null,
                        'new_values' => ['filename' => $filename],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'url' => request()->fullUrl(),
                        'created_at' => now(),
                    ]);
                }

                Notification::make()
                    ->title('Database berhasil direstore')
                    ->body("Backup {$filename} telah direstore ke database.")
                    ->success()
                    ->send();
            } else {
                throw new Exception('Database driver not supported for restore.');
            }
        } catch (Exception $e) {
            Notification::make()
                ->title('Restore gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Delete a backup file.
     */
    protected function deleteBackup(string $filename): void
    {
        $filepath = "{$this->backupPath}/{$filename}";

        if (Storage::disk($this->disk)->exists($filepath)) {
            Storage::disk($this->disk)->delete($filepath);

            Notification::make()
                ->title('Backup dihapus')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('File tidak ditemukan')
                ->danger()
                ->send();
        }
    }

    /**
     * Delete multiple backup files.
     */
    protected function deleteMultipleBackups($records): void
    {
        $count = 0;
        foreach ($records as $record) {
            $filepath = "{$this->backupPath}/{$record['name']}";
            if (Storage::disk($this->disk)->exists($filepath)) {
                Storage::disk($this->disk)->delete($filepath);
                $count++;
            }
        }

        Notification::make()
            ->title("{$count} backup dihapus")
            ->success()
            ->send();
    }

    /**
     * Format bytes to human readable.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('manage_backups') ||
               Auth::user()?->hasRole('admin') ||
               Auth::user()?->hasRole('super_admin');
    }
}
