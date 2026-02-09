<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS satu_sehat_logs');
        DB::statement('CREATE VIEW satu_sehat_logs AS SELECT * FROM satusehat_logs');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS satu_sehat_logs');
    }
};

