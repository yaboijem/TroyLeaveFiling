<?php
declare(strict_types=1);

return [
    'admin passes admin gate' => function (): void {
        $auth = ['idno' => '1015', 'usertype' => 'admin'];
        portal_assert_same($auth, portal_require_role($auth, ['admin']));
    },

    'approver passes approver gate' => function (): void {
        $auth = ['idno' => '1016', 'usertype' => 'approver'];
        portal_assert_same($auth, portal_require_role($auth, ['admin', 'approver']));
    },

    'approver fails admin gate' => function (): void {
        $error = portal_assert_api_error(
            fn () => portal_require_role(['usertype' => 'approver'], ['admin']),
            'forbidden',
            403
        );
        portal_assert_same('You do not have access to this area.', $error->getMessage());
    },

    'missing auth is unauthenticated' => function (): void {
        portal_assert_api_error(fn () => portal_require_role(null, ['admin']), 'unauthenticated', 401);
    },

    'role match ignores case and whitespace' => function (): void {
        $auth = ['usertype' => ' Admin '];
        portal_assert_same($auth, portal_require_role($auth, ['admin']));
    },
];
