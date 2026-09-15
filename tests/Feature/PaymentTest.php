<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    /**
     * Enrollment::paidAmount()/balance() sum the live payments relation,
     * with no cached total anywhere — editing a payment's amount must be
     * reflected the moment the row changes, with no extra bookkeeping.
     */
    public function test_admin_can_update_a_payment_and_the_balance_reflects_it(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::factory()->create(['annual_cost' => 200, 'monthly_cost' => 20]);
        $enrollment = Enrollment::factory()->create(['course_id' => $course->id, 'billing_frequency' => 'annual']);
        $payment = Payment::factory()->create(['enrollment_id' => $enrollment->id, 'amount' => 50, 'method' => 'contanti']);

        $this->assertSame(50.0, $enrollment->fresh()->paidAmount());

        $response = $this->actingAs($admin)->put(route('payments.update', $payment), [
            'amount' => 120,
            'method' => 'bonifico',
            'date' => '2026-02-01',
            'notes' => 'Corretto importo',
        ]);

        $response->assertRedirect();
        $payment->refresh();
        $this->assertEquals(120, $payment->amount);
        $this->assertSame('bonifico', $payment->method);
        $this->assertSame('2026-02-01', $payment->date->toDateString());
        $this->assertSame('Corretto importo', $payment->notes);

        $this->assertSame(120.0, $enrollment->fresh()->paidAmount());
    }

    public function test_admin_can_delete_a_payment_and_the_balance_reflects_it(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::factory()->create(['annual_cost' => 200, 'monthly_cost' => 20]);
        $enrollment = Enrollment::factory()->create(['course_id' => $course->id, 'billing_frequency' => 'annual']);
        Payment::factory()->create(['enrollment_id' => $enrollment->id, 'amount' => 50]);
        $toDelete = Payment::factory()->create(['enrollment_id' => $enrollment->id, 'amount' => 30]);

        $this->assertSame(80.0, $enrollment->fresh()->paidAmount());

        $response = $this->actingAs($admin)->delete(route('payments.destroy', $toDelete));

        $response->assertRedirect();
        $this->assertDatabaseMissing('payments', ['id' => $toDelete->id]);
        $this->assertSame(50.0, $enrollment->fresh()->paidAmount());
    }

    public function test_non_admin_cannot_update_or_delete_a_payment(): void
    {
        Role::create(['name' => 'member']);
        $member = User::factory()->create();
        $member->assignRole('member');

        $payment = Payment::factory()->create();

        $this->actingAs($member)->put(route('payments.update', $payment), [
            'amount' => 999,
            'method' => 'carta',
            'date' => '2026-01-01',
        ])->assertForbidden();

        $this->actingAs($member)->delete(route('payments.destroy', $payment))->assertForbidden();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'amount' => $payment->amount]);
    }
}
