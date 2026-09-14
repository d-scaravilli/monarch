<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BulkUserDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_soft_delete_multiple_selected_users_at_once(): void
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $users = User::factory()->count(3)->create();

        Livewire::actingAs($admin)
            ->test('users-table')
            ->set('selected', [$users[0]->id, $users[1]->id])
            ->call('bulkDelete');

        $this->assertTrue($users[0]->fresh()->trashed());
        $this->assertTrue($users[1]->fresh()->trashed());
        $this->assertFalse($users[2]->fresh()->trashed());
    }

    public function test_selecting_all_on_page_never_includes_the_acting_admin(): void
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $others = User::factory()->count(2)->create();

        Livewire::actingAs($admin)
            ->test('users-table')
            ->call('toggleSelectAllOnPage', $others->pluck('id')->all())
            ->assertSet('selected', $others->pluck('id')->all());

        // Even if something did try to slip the admin's own id in, bulkDelete
        // must not be able to delete it: the component excludes it from the
        // page ids it ever hands to toggleSelectAllOnPage (see the view),
        // so this asserts the admin survives regardless.
        Livewire::actingAs($admin)
            ->test('users-table')
            ->set('selected', [$admin->id, $others[0]->id])
            ->call('bulkDelete');

        $this->assertFalse($admin->fresh()->trashed(), 'bulkDelete must never delete the acting admin.');
        $this->assertTrue($others[0]->fresh()->trashed());
    }

    public function test_non_admin_cannot_bulk_delete_users(): void
    {
        $member = User::factory()->create();
        $target = User::factory()->create();

        Livewire::actingAs($member)
            ->test('users-table')
            ->set('selected', [$target->id])
            ->call('bulkDelete')
            ->assertForbidden();

        $this->assertFalse($target->fresh()->trashed());
    }
}
