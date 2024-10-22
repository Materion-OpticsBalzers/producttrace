<?php

namespace App\Ldap;

use LdapRecord\Models\Entry;

class User extends Entry
{
    /**
     * The object classes of the LDAP model.
     *
     * @var array
     */
    public static array $objectClasses = [
        'top',
        'person',
        'organizationalperson',
        'user',
    ];

    protected array $dates = [];
}
