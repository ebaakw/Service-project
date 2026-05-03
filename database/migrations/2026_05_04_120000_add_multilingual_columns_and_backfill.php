<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->string('short_description_ar', 500)->nullable()->after('short_description');
            $table->string('short_description_en', 500)->nullable()->after('short_description_ar');
            $table->longText('description_ar')->nullable()->after('description');
            $table->longText('description_en')->nullable()->after('description_ar');

            $table->index('name_ar');
            $table->index('name_en');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
        });

        Schema::table('technicians', function (Blueprint $table) {
            $table->string('display_name_ar')->nullable()->after('employee_code');
            $table->string('display_name_en')->nullable()->after('display_name_ar');
            $table->text('bio_ar')->nullable()->after('bio');
            $table->text('bio_en')->nullable()->after('bio_ar');
            $table->string('job_title_ar')->nullable()->after('job_title');
            $table->string('job_title_en')->nullable()->after('job_title_ar');

            $table->index('display_name_ar');
            $table->index('display_name_en');
        });

        Schema::table('company_price_lists', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ar');
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ar');
        });

        Schema::table('portfolio_projects', function (Blueprint $table) {
            $table->string('location_ar')->nullable()->after('location');
            $table->string('location_en')->nullable()->after('location_ar');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->string('label_ar')->nullable()->after('key');
            $table->string('label_en')->nullable()->after('label_ar');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ar');
        });

        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('label_ar')->nullable()->after('key');
            $table->string('label_en')->nullable()->after('label_ar');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ar');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ar');
        });

        Schema::table('service_categories', function (Blueprint $table) {
            $table->index('name_ar');
            $table->index('name_en');
        });

        Schema::table('company_price_items', function (Blueprint $table) {
            $table->index('name_ar');
            $table->index('name_en');
        });

        $this->backfill();
        $this->migrateIntakeFieldOptions();
    }

    private function backfill(): void
    {
        foreach (DB::table('companies')->orderBy('id')->cursor() as $row) {
            DB::table('companies')->where('id', $row->id)->update([
                'name_ar' => $row->name,
                'name_en' => $row->name,
                'short_description_ar' => $row->short_description,
                'short_description_en' => $row->short_description,
                'description_ar' => $row->description,
                'description_en' => $row->description,
            ]);
        }

        foreach (DB::table('users')->orderBy('id')->cursor() as $row) {
            DB::table('users')->where('id', $row->id)->update([
                'name_ar' => $row->name,
                'name_en' => $row->name,
            ]);
        }

        foreach (DB::table('technicians')->orderBy('id')->cursor() as $row) {
            DB::table('technicians')->where('id', $row->id)->update([
                'bio_ar' => $row->bio,
                'bio_en' => $row->bio,
                'job_title_ar' => $row->job_title,
                'job_title_en' => $row->job_title,
            ]);
        }

        foreach (DB::table('company_price_lists')->orderBy('id')->cursor() as $row) {
            DB::table('company_price_lists')->where('id', $row->id)->update([
                'name_ar' => $row->name,
                'name_en' => $row->name,
                'description_ar' => $row->description,
                'description_en' => $row->description,
            ]);
        }

        foreach (DB::table('discounts')->orderBy('id')->cursor() as $row) {
            DB::table('discounts')->where('id', $row->id)->update([
                'name_ar' => $row->name,
                'name_en' => $row->name,
                'description_ar' => $row->description,
                'description_en' => $row->description,
            ]);
        }

        foreach (DB::table('portfolio_projects')->orderBy('id')->cursor() as $row) {
            $loc = $row->location;
            DB::table('portfolio_projects')->where('id', $row->id)->update([
                'location_ar' => $loc,
                'location_en' => $loc,
            ]);
        }

        foreach (DB::table('settings')->orderBy('id')->cursor() as $row) {
            $desc = $row->description;
            DB::table('settings')->where('id', $row->id)->update([
                'label_ar' => null,
                'label_en' => null,
                'description_ar' => $desc,
                'description_en' => $desc,
            ]);
        }

        foreach (DB::table('company_settings')->orderBy('id')->cursor() as $row) {
            $desc = $row->description;
            DB::table('company_settings')->where('id', $row->id)->update([
                'label_ar' => null,
                'label_en' => null,
                'description_ar' => $desc,
                'description_en' => $desc,
            ]);
        }

        foreach (DB::table('order_items')->orderBy('id')->cursor() as $row) {
            DB::table('order_items')->where('id', $row->id)->update([
                'name_ar' => $row->name,
                'name_en' => $row->name,
                'description_ar' => $row->description,
                'description_en' => $row->description,
            ]);
        }
    }

    private function migrateIntakeFieldOptions(): void
    {
        foreach (DB::table('service_intake_fields')->whereNotNull('options')->orderBy('id')->cursor() as $row) {
            $options = json_decode($row->options, true);
            if (! is_array($options)) {
                continue;
            }

            $normalized = [];
            foreach ($options as $opt) {
                if (! is_array($opt)) {
                    continue;
                }
                $value = $opt['value'] ?? $opt['label'] ?? null;
                if ($value === null) {
                    continue;
                }
                $labelAr = $opt['label_ar'] ?? $opt['label'] ?? (string) $value;
                $labelEn = $opt['label_en'] ?? $opt['label'] ?? (string) $value;
                $normalized[] = [
                    'value' => (string) $value,
                    'label_ar' => is_string($labelAr) ? $labelAr : (string) $value,
                    'label_en' => is_string($labelEn) ? $labelEn : (string) $value,
                ];
            }

            if ($normalized !== []) {
                DB::table('service_intake_fields')->where('id', $row->id)->update([
                    'options' => json_encode($normalized),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('company_price_items', function (Blueprint $table) {
            $table->dropIndex(['name_ar']);
            $table->dropIndex(['name_en']);
        });

        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropIndex(['name_ar']);
            $table->dropIndex(['name_en']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'name_ar',
                'name_en',
                'description_ar',
                'description_en',
            ]);
        });

        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'label_ar',
                'label_en',
                'description_ar',
                'description_en',
            ]);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'label_ar',
                'label_en',
                'description_ar',
                'description_en',
            ]);
        });

        Schema::table('portfolio_projects', function (Blueprint $table) {
            $table->dropColumn(['location_ar', 'location_en']);
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn([
                'name_ar',
                'name_en',
                'description_ar',
                'description_en',
            ]);
        });

        Schema::table('company_price_lists', function (Blueprint $table) {
            $table->dropColumn([
                'name_ar',
                'name_en',
                'description_ar',
                'description_en',
            ]);
        });

        Schema::table('technicians', function (Blueprint $table) {
            $table->dropIndex(['display_name_ar']);
            $table->dropIndex(['display_name_en']);
            $table->dropColumn([
                'display_name_ar',
                'display_name_en',
                'bio_ar',
                'bio_en',
                'job_title_ar',
                'job_title_en',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'name_en']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['name_ar']);
            $table->dropIndex(['name_en']);
            $table->dropColumn([
                'name_ar',
                'name_en',
                'short_description_ar',
                'short_description_en',
                'description_ar',
                'description_en',
            ]);
        });
    }
};
