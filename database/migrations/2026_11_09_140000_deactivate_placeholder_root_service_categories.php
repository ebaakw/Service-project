<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Placeholder roots from ServiceCatalogSeeder (electricity/plumbing/refrigerators/decoration)
     * that lack icons and duplicate the curated catalog (ids 1–8).
     *
     * @var list<string>
     */
    private const PLACEHOLDER_ROOT_SLUGS = [
        'electricity',
        'plumbing',
        'refrigerators',
        'decoration',
    ];

    public function up(): void
    {
        DB::table('service_categories')
            ->whereNull('parent_id')
            ->whereIn('slug', self::PLACEHOLDER_ROOT_SLUGS)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('service_categories')
            ->whereNull('parent_id')
            ->whereIn('slug', self::PLACEHOLDER_ROOT_SLUGS)
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
