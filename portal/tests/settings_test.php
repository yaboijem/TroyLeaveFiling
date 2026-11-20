<?php
declare(strict_types=1);

return [
    'settings returns a data envelope' => function (): void {
        portal_assert_same(['data' => ['breaks_enabled' => true]], portal_handle_settings(portal_test_db(), ['idno' => '1015'], []));
    },
    'breaks disabled is reflected' => function (): void {
        portal_test_db()->query('UPDATE app_settings SET breaks_enabled=0 WHERE id=1');
        portal_assert_same(['breaks_enabled' => false], portal_settings(portal_test_db()));
    },
];
