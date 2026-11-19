<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Access rules for API Platform operations.
 */
final class ApiAccess
{
    /** The logged-in user owns the object; compared by id, not by object state. */
    public const OWNER = 'is_granted("ROLE_USER") and object.getUser() !== null'
        . ' and object.getUser().getId() === user.getId()';

    /** The object is the logged-in user, or the user is an administrator. */
    public const SELF_OR_ADMIN = 'is_granted("ROLE_USER")'
        . ' and (object.getId() === user.getId() or is_granted("ROLE_ADMIN"))';
}
