<?php
declare(strict_types=1);
return [
    'stale logout does not clear newer device token' => function (): void {
        $db = portal_test_db();
        $db->query("UPDATE employeefiletbl SET session_token='older-token' WHERE idno='1015'");
        $authenticated = portal_test_employee('1015');
        $db->query("UPDATE employeefiletbl SET session_token='newer-token' WHERE idno='1015'");
        $_SESSION = ['idno' => '1015', 'session_token' => 'older-token'];
        portal_handle_logout($db, $authenticated, []);
        portal_assert_same('newer-token', portal_test_employee('1015')['session_token']);
    },
];
