<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\CourseEdition;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\MedicalCertificate;
use App\Models\MemberProfile;
use App\Models\Module;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class PalestraSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Module::firstOrCreate(
            ['slug' => 'palestra'],
            ['name' => 'Palestra', 'is_active' => true],
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@beru.test'],
            ['name' => 'Amministratore', 'password' => bcrypt('password'), 'email_verified_at' => now()],
        );
        $admin->assignRole('admin');

        $instructors = User::factory(2)->create()->each(function (User $user, int $i) {
            $user->forceFill(['name' => 'Istruttore '.($i + 1)])->save();
            $user->assignRole('instructor');
        });

        $members = User::factory(8)->create()->each(function (User $user, int $i) {
            $user->forceFill(['name' => 'Iscritto '.($i + 1)])->save();
            $user->assignRole('member');

            MemberProfile::factory()->create(['user_id' => $user->id]);

            MedicalCertificate::factory()->create([
                'user_id' => $user->id,
                'issue_date' => $i === 0 ? now()->subMonths(13) : now()->subMonths(3),
                'expiry_date' => $i === 0 ? now()->subMonth() : now()->addMonths(9),
            ]);
        });

        $rooms = Room::factory(3)->create();

        $courses = collect(['Yoga', 'Pilates', 'Karate', 'Nuoto'])
            ->map(fn (string $name) => Course::factory()->create(['name' => $name]));

        $courses->each(function (Course $course) use ($rooms, $instructors, $members) {
            $edition = CourseEdition::factory()->create([
                'course_id' => $course->id,
                'room_id' => $rooms->random()->id,
            ]);

            $edition->instructors()->attach($instructors->random(1)->pluck('id'));

            $lessons = collect(range(-4, 4))->map(
                fn (int $week) => Lesson::factory()->create([
                    'course_edition_id' => $edition->id,
                    'date' => now()->addWeeks($week),
                ])
            );

            $members->random(4)->each(function (User $member) use ($edition, $lessons) {
                $enrollment = Enrollment::factory()->create([
                    'user_id' => $member->id,
                    'course_edition_id' => $edition->id,
                ]);

                $lessons->filter(fn (Lesson $lesson) => $lesson->date->isPast())
                    ->each(fn (Lesson $lesson) => Attendance::factory()->create([
                        'enrollment_id' => $enrollment->id,
                        'lesson_id' => $lesson->id,
                    ]));

                Payment::factory()->create([
                    'enrollment_id' => $enrollment->id,
                    'amount' => $edition->monthly_cost,
                ]);
            });
        });
    }
}
