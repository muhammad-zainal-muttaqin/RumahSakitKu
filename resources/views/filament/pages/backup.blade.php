<x-filament-panels::page>
    <x-filament-panels::form wire:submit="createBackup">
        {{ $this->form }}
    </x-filament-panels::form>

    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-archive-box class="w-5 h-5" />
                Daftar Backup
            </div>
        </x-slot>
        
        <x-slot name="description">
            Kelola backup database sistem Anda. Backup otomatis dilakukan sesuai jadwal yang diatur.
        </x-slot>

        {{ $this->table }}
    </x-filament::section>

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-information-circle class="w-5 h-5" />
                Informasi Backup
            </div>
        </x-slot>

        <div class="prose dark:prose-invert max-w-none">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Database Only</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Backup hanya berisi struktur dan data database. Ukuran file lebih kecil dan proses backup lebih cepat.
                    </p>
                </div>

                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Full Backup</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Backup mencakup database dan semua file upload (foto, dokumen, dll). Ukuran file lebih besar.
                    </p>
                </div>

                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <h4 class="font-medium text-yellow-900 dark:text-yellow-100 mb-2 flex items-center gap-2">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                        Perhatian Restore
                    </h4>
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        Proses restore akan menggantikan seluruh database saat ini dengan data dari backup. Pastikan Anda sudah membuat backup terbaru sebelum melakukan restore.
                    </p>
                </div>

                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2 flex items-center gap-2">
                        <x-heroicon-o-clock class="w-4 h-4" />
                        Jadwal Backup Otomatis
                    </h4>
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Backup otomatis dapat diatur untuk berjalan harian, mingguan, atau bulanan. File backup lama akan dihapus secara otomatis berdasarkan kebijakan retensi.
                    </p>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
