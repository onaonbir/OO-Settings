<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('oo-settings.table_names.oo_settings', 'oo_settings'), function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->longText('description')->nullable();
            $table->string('key');
            $table->json('value')->nullable();
            $table->nullableMorphs('settingable'); // Polymorphic field
            $table->timestamps();

            $table->unique(['key', 'settingable_id', 'settingable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('oo-settings.table_names.oo_settings', 'oo_settings'));
    }
};
