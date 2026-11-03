<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class CreateInitialSuperAdmin extends Command
{
    protected $signature = 'app:create-initial-super-admin';

    protected $description = 'Create the first super administrator account';

    public function handle(): int
    {
        if (User::query()->role(UserRole::SuperAdmin->value)->exists()) {
            $this->components->error('A super administrator already exists; bootstrap is disabled.');

            return self::FAILURE;
        }

        $name = trim($this->ask('Administrator name') ?? '');
        $email = mb_strtolower(trim($this->ask('Administrator email') ?? ''));
        $password = $this->secret('Password');
        $passwordConfirmation = $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first());

            return self::FAILURE;
        }

        $created = DB::transaction(function () use ($name, $email, $password): bool {
            $role = Role::query()
                ->where('name', UserRole::SuperAdmin->value)
                ->where('guard_name', 'web')
                ->lockForUpdate()
                ->firstOrFail();

            if (User::query()->role(UserRole::SuperAdmin->value)->exists()) {
                return false;
            }

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
            $user->assignRole($role);

            return true;
        });

        if (! $created) {
            $this->components->error('A super administrator was created while bootstrap was in progress.');

            return self::FAILURE;
        }

        $this->components->info('Initial super administrator created.');

        return self::SUCCESS;
    }
}
