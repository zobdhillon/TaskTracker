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
        Schema::table('tasks', function (Blueprint $table) {

            $table->dropForeign('tasks_category_id_foreign');

            // 👇 make column nullable FIRST
            $table->unsignedBigInteger('category_id')->nullable()->change();

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });

        Schema::table('recurring_tasks', function (Blueprint $table) {

            $table->dropForeign('recurring_tasks_category_id_foreign');

            // 👇 THIS is what your error is about
            $table->unsignedBigInteger('category_id')->nullable()->change();

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });
    }
};
