<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technicians', function (Blueprint $table): void {
            $table->string('first_name_ar')->nullable()->after('user_id');
            $table->string('second_name_ar')->nullable()->after('first_name_ar');
            $table->string('family_name_ar')->nullable()->after('second_name_ar');
            $table->string('first_name_en')->nullable()->after('family_name_ar');
            $table->string('second_name_en')->nullable()->after('first_name_en');
            $table->string('family_name_en')->nullable()->after('second_name_en');
            $table->string('civil_id_number', 32)->nullable()->unique()->after('family_name_en');
            $table->json('pending_changes')->nullable()->after('rejection_reason');
        });

        $rows = DB::table('technicians')
            ->join('users', 'users.id', '=', 'technicians.user_id')
            ->select([
                'technicians.id',
                'users.name_ar',
                'users.name_en',
            ])
            ->get();

        foreach ($rows as $row) {
            DB::table('technicians')->where('id', $row->id)->update([
                'first_name_ar' => $row->name_ar ?: null,
                'first_name_en' => $row->name_en ?: null,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table): void {
            $table->dropUnique(['civil_id_number']);
            $table->dropColumn([
                'first_name_ar',
                'second_name_ar',
                'family_name_ar',
                'first_name_en',
                'second_name_en',
                'family_name_en',
                'civil_id_number',
                'pending_changes',
            ]);
        });
    }
};
