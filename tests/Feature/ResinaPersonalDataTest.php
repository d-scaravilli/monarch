<?php

namespace Tests\Feature;

use App\Models\Resina\Brush;
use App\Models\Resina\Character;
use App\Models\Resina\PathStep;
use App\Models\Resina\SavedMix;
use App\Models\Resina\StepProgress;
use App\Models\Resina\UserPaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResinaPersonalDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relations_reach_their_own_resin_data(): void
    {
        $user = User::factory()->create();
        $seiya = Character::where('slug', 'seiya')->firstOrFail();
        $version = $seiya->versions()->where('slug', 'a1')->firstOrFail();

        $user->resinCharacterVersions()->attach($version->id, ['character_id' => $seiya->id]);
        $user->resinCompletedPathSteps()->attach(PathStep::where('position', 1)->value('id'), ['done_at' => now()]);
        Brush::factory()->create(['user_id' => $user->id]);
        UserPaint::factory()->create(['user_id' => $user->id]);
        SavedMix::create(['user_id' => $user->id, 'name' => 'Pelle', 'mix' => ['p1' => 2, 'u3' => 1]]);
        StepProgress::create(['user_id' => $user->id, 'character_id' => $seiya->id, 'zone_key' => 'auto:eyes', 'step_position' => 1, 'done_at' => now()]);
        $figure = Character::factory()->personal($user)->create();

        $this->assertSame($version->id, $user->resinCharacterVersions()->wherePivot('character_id', $seiya->id)->value('resin_character_versions.id'));
        $this->assertSame(1, $user->resinCompletedPathSteps()->count());
        $this->assertSame(1, $user->resinBrushes()->count());
        $this->assertSame(1, $user->resinPaints()->count());
        $this->assertSame(['p1' => 2, 'u3' => 1], $user->resinSavedMixes()->first()->mix);
        $this->assertSame(1, $user->resinStepProgress()->count());
        $this->assertTrue($user->resinFigures()->first()->is($figure));
        $this->assertFalse(Character::catalog()->whereKey($figure->id)->exists());
    }

    public function test_deleting_an_account_for_good_removes_its_resin_data(): void
    {
        $user = User::factory()->create();
        $figure = Character::factory()->personal($user)->create();
        $brush = Brush::factory()->create(['user_id' => $user->id]);

        $user->forceDelete();

        $this->assertModelMissing($figure);
        $this->assertModelMissing($brush);
    }
}
