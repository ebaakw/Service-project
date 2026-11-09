<?php

use Database\Seeders\ServiceCatalogSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Fill problems for curated leaf roots (خزانات/صحي/…) and keep AC on children.
     * Idempotent via ServiceCatalogSeeder updateOrCreate.
     */
    public function up(): void
    {
        (new ServiceCatalogSeeder)->run();
    }

    public function down(): void
    {
        // Data backfill — no automatic rollback of seeded problems.
    }
};
