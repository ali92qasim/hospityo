<?php

use App\Services\SharedHosting\CpanelMysqlProvisioner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'shared_hosting.host' => 'https://s11.hosterpk.com:2083',
        'shared_hosting.user' => 'codushco',
        'shared_hosting.api_token' => 'secret-token-xyz',
        'shared_hosting.timeout' => 10,
        'database.connections.landlord.username' => 'codushco_app',
    ]);
});

it('creates a database then grants privileges to DB_USERNAME', function () {
    Http::fake([
        '*/execute/Mysql/create_database*' => Http::response(['status' => 1, 'errors' => null], 200),
        '*/execute/Mysql/set_privileges_on_database*' => Http::response(['status' => 1, 'errors' => null], 200),
    ]);

    app(CpanelMysqlProvisioner::class)->create('codushco_acme');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/execute/Mysql/create_database')
            && $request['name'] === 'codushco_acme'
            && $request->header('Authorization')[0] === 'cpanel codushco:secret-token-xyz';
    });

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/execute/Mysql/set_privileges_on_database')
            && $request['user'] === 'codushco_app'
            && $request['database'] === 'codushco_acme'
            && $request['privileges'] === 'ALL PRIVILEGES'
            && $request->header('Authorization')[0] === 'cpanel codushco:secret-token-xyz';
    });
});

it('deletes the database when privilege grant fails after create', function () {
    Http::fake([
        '*/execute/Mysql/create_database*' => Http::response(['status' => 1, 'errors' => null], 200),
        '*/execute/Mysql/set_privileges_on_database*' => Http::response(['status' => 0, 'errors' => ['nope']], 200),
        '*/execute/Mysql/delete_database*' => Http::response(['status' => 1, 'errors' => null], 200),
    ]);

    expect(fn () => app(CpanelMysqlProvisioner::class)->create('codushco_fail'))
        ->toThrow(RuntimeException::class);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/execute/Mysql/delete_database')
            && $request['name'] === 'codushco_fail';
    });
});

it('does not log the api token on failure', function () {
    Http::fake([
        '*/execute/Mysql/create_database*' => Http::response(['status' => 0, 'errors' => ['denied']], 200),
    ]);

    Log::spy();

    try {
        app(CpanelMysqlProvisioner::class)->create('codushco_log');
    } catch (RuntimeException) {
        // expected
    }

    Log::shouldHaveReceived('error')->atLeast()->once();
    Log::shouldHaveReceived('error')->withArgs(function ($message, $context = []) {
        $haystack = $message.json_encode($context);

        return ! str_contains($haystack, 'secret-token-xyz');
    });
});
