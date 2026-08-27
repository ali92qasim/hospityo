<?php

use App\Models\User;
use App\Services\Backup\DatabaseDumper;
use Illuminate\Support\Facades\Schema;

it('only dumps tables that exist on the tenant connection', function () {
    $dumper = new DatabaseDumper;
    $sql = $dumper->dump('tenant');

    expect(Schema::connection('tenant')->hasTable('currencies'))->toBeFalse()
        ->and($sql)->not->toContain('currencies')
        ->and($sql)->toContain('users');
});

it('restores rows that contain semicolons', function () {
    $user = User::create([
        'name' => 'Foo; Bar',
        'email' => 'semicolon@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $dumper = new DatabaseDumper;
    $sql = $dumper->dump('tenant');

    $user->update(['name' => 'changed']);
    $dumper->restore('tenant', $sql);

    expect(User::where('email', 'semicolon@example.com')->first()->name)->toBe('Foo; Bar');
});

it('dumps tenant rows and restores them', function () {
    $user = User::create([
        'name' => 'Backup User',
        'email' => 'backup-user@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $dumper = new DatabaseDumper;
    $sql = $dumper->dump('tenant');

    expect($sql)->toContain('backup-user@example.com');

    $user->update(['name' => 'Changed']);
    User::create([
        'name' => 'Extra',
        'email' => 'extra@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $dumper->restore('tenant', $sql);

    expect(User::where('email', 'backup-user@example.com')->first()->name)->toBe('Backup User')
        ->and(User::where('email', 'extra@example.com')->exists())->toBeFalse();
});
