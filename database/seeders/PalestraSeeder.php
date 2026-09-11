<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Discipline;
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
        $module = Module::firstOrCreate(
            ['slug' => 'palestra'],
            ['name' => 'Palestra', 'icon' => 'fire', 'color' => 'orange', 'is_active' => true],
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

        $testMember = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password'), 'email_verified_at' => now()],
        );
        $testMember->assignRole('member');
        MemberProfile::firstOrCreate(['user_id' => $testMember->id], [
            'fiscal_code' => 'TSTUSR85M01H501Z',
            'emergency_contact' => 'Mario Rossi - 333 1234567',
        ]);
        MedicalCertificate::firstOrCreate(
            ['user_id' => $testMember->id],
            ['issue_date' => now()->subMonths(3), 'expiry_date' => now()->addMonths(9)],
        );

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

        // The test account is always enrolled, so the member views have real data to show.
        $members->push($testMember);

        $module->users()->syncWithoutDetaching(
            $instructors->merge($members)->push($admin)->pluck('id'),
        );

        $rooms = Room::factory(3)->create();

        $disciplines = collect(['Yoga', 'Pilates', 'Karate', 'Nuoto'])
            ->map(fn (string $name) => Discipline::factory()->create(['name' => $name]));

        $disciplines->each(function (Discipline $discipline, int $disciplineIndex) use ($rooms, $instructors, $members, $testMember) {
            $course = Course::factory()->create([
                'discipline_id' => $discipline->id,
                'room_id' => $rooms->random()->id,
            ]);

            $course->instructors()->attach($instructors->random(1)->pluck('id'));

            $lessons = collect(range(-4, 4))->map(
                fn (int $week) => Lesson::factory()->create([
                    'course_id' => $course->id,
                    'date' => now()->addWeeks($week),
                ])
            );

            $enrolledMembers = $members->random(3);
            if ($disciplineIndex === 0) {
                $enrolledMembers->push($testMember);
            }

            $enrolledMembers->unique('id')->each(function (User $member) use ($course, $lessons) {
                $enrollment = Enrollment::factory()->create([
                    'user_id' => $member->id,
                    'course_id' => $course->id,
                ]);

                $lessons->filter(fn (Lesson $lesson) => $lesson->date->isPast())
                    ->each(fn (Lesson $lesson) => Attendance::factory()->create([
                        'enrollment_id' => $enrollment->id,
                        'lesson_id' => $lesson->id,
                    ]));

                Payment::factory()->create([
                    'enrollment_id' => $enrollment->id,
                    'amount' => $course->monthly_cost,
                ]);
            });
        });
    }
}
