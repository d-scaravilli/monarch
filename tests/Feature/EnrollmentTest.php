<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);
        Role::create(['name' => 'instructor']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_enrollment_date_can_be_set_when_adding_an_enrollment(): void
    {
        $admin = $this->admin();
        $course = Course::factory()->create();
        $member = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('courses.enrollments.store', $course), [
            'user_id' => $member->id,
            'enrollment_date' => '2026-01-15',
            'discount' => 0,
            'billing_frequency' => 'annual',
        ]);

        $response->assertRedirect(route('courses.show', $course));

        $this->assertSame('2026-01-15', $course->enrollments()->first()->enrollment_date->toDateString());
    }

    public function test_enrollment_date_defaults_to_today_when_not_given(): void
    {
        $admin = $this->admin();
        $course = Course::factory()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)->post(route('courses.enrollments.store', $course), [
            'user_id' => $member->id,
        ]);

        $this->assertSame(now()->toDateString(), $course->enrollments()->first()->enrollment_date->toDateString());
    }

    public function test_admin_can_update_an_enrollments_date_discount_and_billing_frequency(): void
    {
        $admin = $this->admin();
        $enrollment = Enrollment::factory()->create([
            'enrollment_date' => '2025-01-01',
            'discount' => 0,
            'billing_frequency' => 'annual',
        ]);

        $response = $this->actingAs($admin)->patch(route('enrollments.update', $enrollment), [
            'enrollment_date' => '2026-03-10',
            'discount' => 15,
            'billing_frequency' => 'monthly',
        ]);

        $response->assertRedirect(route('courses.show', $enrollment->course));

        $enrollment->refresh();
        $this->assertSame('2026-03-10', $enrollment->enrollment_date->toDateString());
        $this->assertSame('15.00', $enrollment->discount);
        $this->assertSame('monthly', $enrollment->billing_frequency);
    }

    public function test_non_admin_cannot_update_an_enrollment(): void
    {
        Role::create(['name' => 'member']);
        $user = User::factory()->create();
        $user->assignRole('member');

        $enrollment = Enrollment::factory()->create();

        $response = $this->actingAs($user)->patch(route('enrollments.update', $enrollment), [
            'enrollment_date' => '2026-03-10',
        ]);

        $response->assertForbidden();
    }
}
