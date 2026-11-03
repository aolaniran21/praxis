<?php

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UniversityDemoSeeder;
use Illuminate\Support\Facades\Hash;

it('requires a configured demo password when seeding accounts in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    config([
        'app.demo_password' => null,
    ]);

    expect(fn () => app(UniversityDemoSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'DEMO_PASSWORD must be configured');
});

it('seeds demo accounts and academic records idempotently with the configured password', function () {
    config([
        'app.demo_password' => 'render-demo-password',
    ]);

    $this->seed(RolePermissionSeeder::class);
    $seeder = app(UniversityDemoSeeder::class);
    $seeder->run();
    $seeder->run();

    $student = User::query()->where('email', 'student@university.test')->firstOrFail();

    expect(User::query()->count())->toBe(4)
        ->and(Course::query()->count())->toBe(3)
        ->and($student->hasRole(UserRole::Student->value))->toBeTrue()
        ->and(Hash::check('render-demo-password', $student->password))->toBeTrue();
});
