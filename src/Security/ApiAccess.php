<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Access rules for API Platform operations.
 */
final class ApiAccess
{
    /** The logged-in user owns the object. */
    public const OWNER = 'is_granted("ROLE_USER") and object.getUser() == user';

    /** The object is the logged-in user, or the user is an administrator. */
    public const SELF_OR_ADMIN = 'object == user or is_granted("ROLE_ADMIN")';
}
