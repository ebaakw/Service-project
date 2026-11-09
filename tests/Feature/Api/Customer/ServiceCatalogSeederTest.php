<?php

namespace Tests\Feature\Api\Customer;

use App\Models\ServiceCategory;
use Database\Seeders\ServiceCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_fills_leaf_root_problems_and_keeps_ac_on_children(): void
    {
        $this->seed(ServiceCatalogSeeder::class);

        $ac = ServiceCategory::query()->where('slug', 'ac')->firstOrFail();
        $tanks = ServiceCategory::query()->where('slug', 'water-tanks')->firstOrFail();
        $electrical = ServiceCategory::query()->where('slug', 'electrical')->firstOrFail();

        $acChildren = $this->getJson('/api/v1/customer/service-categories/'.$ac->getKey().'/children');
        $acChildren->assertOk();
        $acChildren->assertJsonCount(3, 'data');

        $acProblems = $this->getJson('/api/v1/customer/service-categories/'.$ac->getKey().'/problems');
        $acProblems->assertOk();
        $acProblems->assertJsonCount(0, 'data');

        $centralId = $acChildren->json('data.0.id');
        $centralProblems = $this->getJson('/api/v1/customer/service-categories/'.$centralId.'/problems');
        $centralProblems->assertOk();
        $this->assertGreaterThanOrEqual(2, count($centralProblems->json('data')));
        $this->assertTrue(collect($centralProblems->json('data'))->contains(fn (array $row): bool => $row['is_other'] === true));

        $tanksChildren = $this->getJson('/api/v1/customer/service-categories/'.$tanks->getKey().'/children');
        $tanksChildren->assertOk();
        $tanksChildren->assertJsonCount(0, 'data');

        $tanksProblems = $this->getJson('/api/v1/customer/service-categories/'.$tanks->getKey().'/problems');
        $tanksProblems->assertOk();
        $this->assertGreaterThanOrEqual(3, count($tanksProblems->json('data')));
        $this->assertSame($tanks->getKey(), $tanksProblems->json('data.0.service_category_id'));
        $this->assertTrue(collect($tanksProblems->json('data'))->last()['is_other']);

        $electricalProblems = $this->getJson('/api/v1/customer/service-categories/'.$electrical->getKey().'/problems');
        $electricalProblems->assertOk();
        $this->assertGreaterThanOrEqual(3, count($electricalProblems->json('data')));

        $roots = $this->getJson('/api/v1/customer/service-categories');
        $roots->assertOk();
        $slugs = collect($roots->json('data'))->pluck('slug')->all();
        $this->assertContains('ac', $slugs);
        $this->assertContains('water-tanks', $slugs);
        $this->assertNotContains('electricity', $slugs);
        $this->assertNotContains('plumbing', $slugs);
    }

    public function test_seeder_attaches_problems_to_existing_named_roots_without_duplicating(): void
    {
        // Migration may already have seeded water-tanks; reuse that root with a custom slug/icon.
        $existing = ServiceCategory::query()
            ->whereNull('parent_id')
            ->where('name_ar', 'خزانات')
            ->first();

        if ($existing === null) {
            $existing = ServiceCategory::factory()->create([
                'parent_id' => null,
                'slug' => 'custom-tanks-slug',
                'name_ar' => 'خزانات',
                'name_en' => 'Tanks',
                'is_active' => true,
                'sort_order' => 1,
            ]);
        }

        $existing->forceFill([
            'slug' => 'custom-tanks-slug',
            'icon_path' => 'service-categories/existing-icon.png',
            'is_active' => true,
        ])->save();

        $this->seed(ServiceCatalogSeeder::class);

        $this->assertSame(1, ServiceCategory::query()->where('name_ar', 'خزانات')->whereNull('parent_id')->count());
        $existing->refresh();
        $this->assertSame('custom-tanks-slug', $existing->slug);
        $this->assertSame('service-categories/existing-icon.png', $existing->icon_path);

        $problems = $this->getJson('/api/v1/customer/service-categories/'.$existing->getKey().'/problems');
        $problems->assertOk();
        $this->assertGreaterThanOrEqual(3, count($problems->json('data')));
        $this->assertSame($existing->getKey(), $problems->json('data.0.service_category_id'));
    }
}
