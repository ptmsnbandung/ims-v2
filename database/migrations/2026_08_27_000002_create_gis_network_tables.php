<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('gis_projects')) {
            Schema::create('gis_projects', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
            });

            // Insert initial default project
            DB::table('gis_projects')->insert([
                'code' => 'PRJ-DEFAULT',
                'name' => 'Jaringan Utama FTTH',
                'description' => 'Proyek pemetaan jaringan fiber optik utama',
                'created_by' => 'SYSTEM',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!Schema::hasTable('gis_elements')) {
            Schema::create('gis_elements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id')->default(1)->index();
                $table->enum('category', ['marker', 'line'])->default('marker');
                $table->string('element_type'); // pole, joint_box, odc, olt, customer, feeder, distribution, dropcore, etc.
                $table->string('name');
                $table->string('color')->nullable();
                $table->decimal('latitude', 11, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->longText('coordinates')->nullable(); // JSON array of [lat, lng] for lines
                $table->decimal('length_meters', 12, 2)->nullable();
                $table->decimal('line_width', 4, 1)->default(3.0);
                $table->string('line_dash')->default('solid');
                $table->longText('metadata')->nullable(); // JSON specs, photos, notes, splicing matrix
                $table->string('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gis_elements');
        Schema::dropIfExists('gis_projects');
    }
};
