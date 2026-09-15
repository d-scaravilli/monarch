<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Discipline;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_admin_sees_every_payment_row_by_row(): void
    {
        $admin = $this->makeAdmin();

        $discipline = Discipline::factory()->create(['name' => 'Karate']);
        $course = Course::factory()->create(['discipline_id' => $discipline->id]);
        $member = User::factory()->create(['name' => 'Mario Rossi']);
        $enrollment = Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id]);
        Payment::factory()->create(['enrollment_id' => $enrollment->id, 'amount' => 42, 'notes' => 'Saldo iscrizione']);

        $response = $this->actingAs($admin)->get(route('accounting.payments'));

        $response->assertOk();
        $response->assertSee('Mario Rossi');
        $response->assertSee('Karate');
        $response->assertSee('Saldo iscrizione');
        $response->assertSee('42');
    }

    public function test_non_admin_cannot_view_the_payments_history(): void
    {
        Role::create(['name' => 'member']);
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)->get(route('accounting.payments'))->assertForbidden();
    }
}
