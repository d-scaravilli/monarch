<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Single place that knows how to create a login-capable user account with
 * a generated password — used by both Amministrazione (any role) and the
 * Palestra "nuovo iscritto" flow (always role "member"), so the two never
 * drift apart.
 */
class CreateUserAccount
{
    /**
     * @param  array<int, string>  $roles
     * @param  array<int, int>  $moduleIds
     * @return array{0: User, 1: string}
     */
    public function handle(string $name, string $email, array $roles = [], array $moduleIds = []): array
    {
        $password = Str::password(12);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($roles);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($moduleIds !== []) {
            $user->modules()->syncWithoutDetaching($moduleIds);
        }

        return [$user, $password];
    }
}
