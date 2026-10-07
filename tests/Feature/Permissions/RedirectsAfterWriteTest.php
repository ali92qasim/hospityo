<?php

use App\Http\Controllers\Concerns\RedirectsAfterWrite;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| RedirectsAfterWrite (RBAC wave 1, AD-1)
|--------------------------------------------------------------------------
| After a write, send the user to the list only if they may view it;
| otherwise to the fallback route (or back() when none). The flash is kept.
| Lives under Feature/ because it needs the router, session and auth.
*/

beforeEach(function () {
    Route::middleware('web')->group(function () {
        Route::get('/__raw-test/list', fn () => 'list')->name('raw-test.list');
        Route::get('/__raw-test/form/{thing}', fn () => 'form')->name('raw-test.form');
        Route::get('/__raw-test/origin', fn () => 'origin')->name('raw-test.origin');

        Route::post('/__raw-test/write/{mode}', function (string $mode) {
            $controller = new class
            {
                use RedirectsAfterWrite;

                public function go(?string $fallback, array $fallbackParams)
                {
                    return $this->redirectAfterWrite(
                        'raw-test.list',
                        [],
                        ['view things', 'view everything'],
                        'Thing saved.',
                        $fallback,
                        $fallbackParams,
                    );
                }
            };

            return $mode === 'fallback'
                ? $controller->go('raw-test.form', ['thing' => 7])
                : $controller->go(null, []);
        })->name('raw-test.write');
    });

    app('router')->getRoutes()->refreshNameLookups();

    foreach (['view things', 'view everything', 'write things'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
});

function rawTestUser(array $permissions): User
{
    $user = User::create([
        'name' => 'Redirect Trait User',
        'email' => 'raw-test-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo($permissions);

    return $user;
}

it('redirects to the route when the user holds a view permission', function (string $permission) {
    $this->actingAs(rawTestUser(['write things', $permission]));

    $this->from(route('raw-test.origin'))
        ->post(route('raw-test.write', ['mode' => 'fallback']))
        ->assertRedirect(route('raw-test.list'))
        ->assertSessionHas('success', 'Thing saved.');
})->with(['view things', 'view everything']);

it('redirects to the fallback with its params when the user lacks every view permission', function () {
    $this->actingAs(rawTestUser(['write things']));

    $this->from(route('raw-test.origin'))
        ->post(route('raw-test.write', ['mode' => 'fallback']))
        ->assertRedirect(route('raw-test.form', ['thing' => 7]))
        ->assertSessionHas('success', 'Thing saved.');
});

it('goes back when the user lacks every view permission and there is no fallback', function () {
    $this->actingAs(rawTestUser(['write things']));

    $this->from(route('raw-test.origin'))
        ->post(route('raw-test.write', ['mode' => 'back']))
        ->assertRedirect(route('raw-test.origin'))
        ->assertSessionHas('success', 'Thing saved.');
});
