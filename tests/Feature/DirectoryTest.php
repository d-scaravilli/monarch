<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DirectoryTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        $this->module = Module::create([
            'slug' => 'palestra',
            'name' => 'Palestra',
            'icon' => 'fire',
            'color' => 'orange',
            'is_active' => true,
        ]);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function makeMember(): User
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $user->modules()->attach($this->module);

        return $user;
    }

    public function test_admin_can_open_the_bulk_directory_page(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();

        $response = $this->actingAs($admin)->get(route('directory.index'));

        $response->assertOk();
        $response->assertSee($member->name);
    }

    public function test_member_cannot_open_the_bulk_directory_page(): void
    {
        $member = $this->makeMember();

        $this->actingAs($member)->get(route('directory.index'))->assertForbidden();
    }

    /**
     * Every cell saves immediately on change — no "save all" step. This
     * mirrors what happens when a field's wire:model.blur/live fires.
     */
    public function test_editing_a_field_saves_immediately(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();

        $this->actingAs($admin);

        Livewire::test('bulk-profile-table')
            ->set("rows.{$member->id}.phone", '333 1234567')
            ->set("rows.{$member->id}.owns_sword", true);

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $member->id,
            'phone' => '333 1234567',
            'owns_sword' => true,
        ]);
    }

    /**
     * Unchecking "attrezzatura in prestito" must drop its description
     * with it — there's nothing left to describe once nothing is on loan.
     */
    public function test_unchecking_borrowed_equipment_clears_its_description(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        MemberProfile::create([
            'user_id' => $member->id,
            'has_borrowed_equipment' => true,
            'borrowed_equipment_notes' => 'Kimono taglia M',
        ]);

        $this->actingAs($admin);

        Livewire::test('bulk-profile-table')
            ->set("rows.{$member->id}.has_borrowed_equipment", false);

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $member->id,
            'has_borrowed_equipment' => false,
            'borrowed_equipment_notes' => null,
        ]);
    }

    public function test_search_filters_by_name(): void
    {
        $admin = $this->makeAdmin();
        $match = $this->makeMember();
        $match->update(['name' => 'Mario Rossi']);
        $other = $this->makeMember();
        $other->update(['name' => 'Luca Bianchi']);

        $this->actingAs($admin);

        Livewire::test('bulk-profile-table')
            ->set('search', 'Mario')
            ->assertSee('Mario Rossi')
            ->assertDontSee('Luca Bianchi');
    }
}
