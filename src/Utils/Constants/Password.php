<?php

namespace App\Utils\Constants;

class Password
{
    public const SET_PASSWORD_EXPIRATION = 604800; // 1 week in seconds
    public const RESET_PASSWORD_EXPIRATION = 86400; // 24 hours in seconds
    public const PASSWORD_INVALID = 'Current Password is invalid.|The password specified did not match with the user records and therefore he/she is not authorized to access the system.';
    public const PASSWORD_NOT_MATCH = 'Password and confirmed password don\'t match.|The password and confirmed password must have the same value.';
    public const PASSWORD_USED_BEFORE = 'Please do not use any of the last 5 previous passwords.|The user tried to use a password that he/she had previously used before.';
}
