<?php
declare(strict_types=1);

/**
 * Task 3 router tests.
 */

$routes = [
    'GET /api/me' => static fn (): string => 'me',
    'POST /api/notifications/{id}/read' => static fn (): string => 'read',
    'GET /api/attendance/photo/{uuid}' => static fn (): string => 'photo',
    'POST /api/login' => ['public' => true, 'handler' => static fn (): string => 'login'],
];

return [
    'exact route matches' => function () use ($routes): void {
        $match = portal_route_match('GET', '/api/me', $routes);

        portal_assert_true($match !== null, 'Expected an exact match.');
        portal_assert_same([], $match['params']);
        portal_assert_true(is_callable($match['handler']), 'Handler must be callable.');
        portal_assert_true(!$match['public'], 'GET /api/me must require auth.');
    },

    'placeholder route captures id' => function () use ($routes): void {
        $match = portal_route_match('POST', '/api/notifications/42/read', $routes);

        portal_assert_true($match !== null, 'Expected a placeholder match.');
        portal_assert_same('42', $match['params']['id'] ?? null);
    },

    'uuid placeholder captures a uuid' => function () use ($routes): void {
        $uuid = '6f1c2f7e-0d2b-4a4c-9f5e-1a2b3c4d5e6f';
        $match = portal_route_match('GET', '/api/attendance/photo/' . $uuid, $routes);

        portal_assert_true($match !== null, 'Expected a uuid match.');
        portal_assert_same($uuid, $match['params']['uuid'] ?? null);
    },

    'public route is flagged' => function () use ($routes): void {
        $login = portal_route_match('POST', '/api/login', $routes);
        $me = portal_route_match('GET', '/api/me', $routes);

        portal_assert_true($login !== null);
        portal_assert_true($login['public'] === true, 'POST /api/login must be public.');
        portal_assert_true($me !== null);
        portal_assert_true($me['public'] === false, 'GET /api/me must not be public.');
    },

    'unknown path returns null' => function () use ($routes): void {
        portal_assert_null(portal_route_match('GET', '/api/nope', $routes));
    },

    'wrong method is not a match' => function () use ($routes): void {
        portal_assert_null(portal_route_match('DELETE', '/api/me', $routes));
    },

    'trailing slash is ignored' => function () use ($routes): void {
        portal_assert_true(portal_route_match('GET', '/api/me/', $routes) !== null);
    },

    'allowed methods lists matches' => function () use ($routes): void {
        portal_assert_same(['GET'], portal_route_allowed_methods('/api/me', $routes));
        portal_assert_same(['POST'], portal_route_allowed_methods('/api/login', $routes));
        portal_assert_same([], portal_route_allowed_methods('/api/nope', $routes));
    },
];
