<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Document;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\MemberNote;
use App\Models\Module;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentRegisteredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModuleResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reproduces the production incident: an admin with no relation
     * whatsoever to the module (not a member, not an instructor, not
     * even granted explicit module_user access — admins bypass that
     * gate globally) triggers "Azzera dati modulo". Their own account
     * must survive, and the module's data must actually be wiped.
     */
    public function test_resetting_module_data_does_not_delete_the_triggering_admin(): void
    {
        Role::create(['name' => 'admin']);

        $module = Module::create([
            'slug' => 'palestra',
            'name' => 'Palestra',
            'icon' => 'fire',
            'color' => 'orange',
            'is_active' => true,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create();
        $course = Course::factory()->create();
        $enrollment = Enrollment::factory()->create(['course_id' => $course->id, 'user_id' => $member->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);
        Attendance::factory()->create(['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id]);
        Payment::factory()->create(['enrollment_id' => $enrollment->id]);
        Document::create([
            'user_id' => $member->id,
            'type' => 'certificato_medico',
            'uploaded_at' => now(),
        ]);
        MemberNote::create([
            'user_id' => $member->id,
            'created_by' => $member->id,
            'type' => 'altro',
            'description' => 'Nota di prova',
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('modules.settings.reset', $module), [
                'confirm_name' => $module->name,
            ]);

        $response->assertRedirect(route('modules.settings.edit', $module));
        $response->assertSessionHasNoErrors();

        $admin->refresh();
        $this->assertNotNull($admin, 'The admin who triggered the reset must still exist.');
        $this->assertFalse($admin->trashed(), 'The triggering admin must not be soft-deleted by the reset.');
        $this->assertTrue($admin->hasRole('admin'), 'The triggering admin must keep their role.');

        // The member account itself is untouched too — only their
        // module *data* is wiped, per "Account utente ... restano intatti."
        $this->assertNotNull($member->fresh());

        $this->assertSame(0, Course::withTrashed()->count());
        $this->assertSame(0, Enrollment::count());
        $this->assertSame(0, Lesson::count());
        $this->assertSame(0, Attendance::count());
        $this->assertSame(0, Payment::count());
        $this->assertSame(0, Document::count());
        $this->assertSame(0, MemberNote::count());
    }

    /**
     * "Elimina tutte le notifiche" must wipe every stored notification
     * (any user's) but leave every other kind of data — messages,
     * payments, notes — completely untouched.
     */
    public function test_resetting_notifications_wipes_all_notifications_but_nothing_else(): void
    {
        Role::create(['name' => 'admin']);

        $module = Module::create([
            'slug' => 'palestra',
            'name' => 'Palestra',
            'icon' => 'fire',
            'color' => 'orange',
            'is_active' => true,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $member = User::factory()->create();

        $member->notify(new PaymentRegisteredNotification(
            Payment::factory()->create(['enrollment_id' => Enrollment::factory()->create(['user_id' => $member->id])->id])
        ));
        $admin->notify(new PaymentRegisteredNotification(
            Payment::factory()->create(['enrollment_id' => Enrollment::factory()->create()->id])
        ));

        $this->assertSame(2, DatabaseNotification::count());
        $this->assertSame(2, Payment::count());

        $response = $this->actingAs($admin)->delete(route('modules.settings.notifications.reset', $module));

        $response->assertRedirect(route('modules.settings.edit', $module));
        $this->assertSame(0, DatabaseNotification::count());
        // Untouched: this action must never cascade into unrelated data.
        $this->assertSame(2, Payment::count());
    }

    public function test_non_admin_cannot_reset_notifications(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        $module = Module::create([
            'slug' => 'palestra',
            'name' => 'Palestra',
            'icon' => 'fire',
            'color' => 'orange',
            'is_active' => true,
        ]);

        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->delete(route('modules.settings.notifications.reset', $module))
            ->assertForbidden();
    }
}
