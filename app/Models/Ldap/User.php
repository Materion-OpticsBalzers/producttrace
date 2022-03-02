<?php

namespace App\Models\Ldap;

class User extends \LdapRecord\Models\Model
{

    public static $objectClasses = [
        'user'
    ];
}
