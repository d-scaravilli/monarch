<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\Discipline;
use App\Models\Document;
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
            ['email' => 'admin@monarch.test'],
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
        // Mirrors the legacy certificate above into the new admin-facing
        // Documenti section (see the documents migration for how real
        // historical data gets carried over the same way).
        Document::firstOrCreate(
            ['user_id' => $testMember->id, 'type' => 'certificato_medico'],
            ['uploaded_at' => now()->subMonths(3), 'expiry_date' => now()->addMonths(9)],
        );
        Document::firstOrCreate(
            ['user_id' => $testMember->id, 'type' => 'modulo_iscrizione'],
            ['uploaded_at' => now()->subMonths(3)],
        );

        $members = User::factory(8)->create()->each(function (User $user, int $i) {
            $user->forceFill(['name' => 'Iscritto '.($i + 1)])->save();
            $user->assignRole('member');

            MemberProfile::factory()->create(['user_id' => $user->id]);

            $issueDate = $i === 0 ? now()->subMonths(13) : now()->subMonths(3);
            $expiryDate = $i === 0 ? now()->subMonth() : now()->addMonths(9);

            MedicalCertificate::factory()->create([
                'user_id' => $user->id,
                'issue_date' => $issueDate,
                'expiry_date' => $expiryDate,
            ]);
            Document::create([
                'user_id' => $user->id,
                'type' => 'certificato_medico',
                'uploaded_at' => $issueDate,
                'expiry_date' => $expiryDate,
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

            // Matches the weekday the sample lessons below land on, so
            // "genera lezioni" produces sensible results out of the box.
            CourseSchedule::create([
                'course_id' => $course->id,
                'weekday' => now()->dayOfWeek,
                'start_time' => '18:00',
                'end_time' => '19:00',
            ]);

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

                $lessons->filter(fn (Lesson $lesson) => $lesson->date->isPast() && $lesson->date->gte($enrollment->enrollment_date))
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

        // One "evento" course, so the corso/evento distinction has
        // something real to show out of the box: specific dates instead
        // of a recurring schedule, plus a one-time enrollment fee.
        $eventDiscipline = Discipline::factory()->create(['name' => 'Seminario Autunnale']);
        $eventCourse = Course::create([
            'discipline_id' => $eventDiscipline->id,
            'room_id' => $rooms->random()->id,
            'type' => 'evento',
            'year' => now()->addMonths(2)->translatedFormat('d').'-'.now()->addMonths(2)->addDay()->translatedFormat('d M Y'),
            'annual_cost' => 0,
            'monthly_cost' => 0,
            'enrollment_cost' => 25,
        ]);
        $eventCourse->instructors()->attach($instructors->random(1)->pluck('id'));

        foreach ([now()->addMonths(2), now()->addMonths(2)->addDay()] as $date) {
            Lesson::create(['course_id' => $eventCourse->id, 'date' => $date->toDateString()]);
        }

        $members->random(2)->each(fn (User $member) => Enrollment::factory()->create([
            'user_id' => $member->id,
            'course_id' => $eventCourse->id,
        ]));
    }
}
