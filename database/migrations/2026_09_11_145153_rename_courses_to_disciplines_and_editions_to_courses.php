<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Course" (Karate, Yoga...) becomes "Discipline"; what used to be
     * a "Course Edition" (discipline + year + room + costs) becomes the
     * new "Course" — matching the renamed vocabulary in the UI.
     */
    public function up(): void
    {
        Schema::rename('courses', 'disciplines');
        Schema::rename('course_editions', 'courses');
        DB::statement('ALTER TABLE courses RENAME COLUMN course_id TO discipline_id');
        DB::statement('ALTER TABLE enrollments RENAME COLUMN course_edition_id TO course_id');
        DB::statement('ALTER TABLE lessons RENAME COLUMN course_edition_id TO course_id');

        Schema::rename('course_edition_instructor', 'course_instructor');
        DB::statement('ALTER TABLE course_instructor RENAME COLUMN course_edition_id TO course_id');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE course_instructor RENAME COLUMN course_id TO course_edition_id');
        Schema::rename('course_instructor', 'course_edition_instructor');

        DB::statement('ALTER TABLE lessons RENAME COLUMN course_id TO course_edition_id');
        DB::statement('ALTER TABLE enrollments RENAME COLUMN course_id TO course_edition_id');
        DB::statement('ALTER TABLE courses RENAME COLUMN discipline_id TO course_id');
        Schema::rename('courses', 'course_editions');
        Schema::rename('disciplines', 'courses');
    }
};
