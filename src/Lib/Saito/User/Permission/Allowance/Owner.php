<?php

declare(strict_types=1);

/**
 * Saito - The Threaded Web Forum
 *
 * @copyright Copyright (c) the Saito Project Developers
 * @link https://github.com/Schlaefer/Saito
 * @license http://opensource.org/licenses/MIT
 */

namespace Saito\User\Permission\Allowance;

use Saito\User\ForumsUserInterface;

class Owner
{
    /** @var string $resource */
    protected $resource;

    /**
     * Constructor
     *
     * @param string $resource What is granted permission to
     */
    public function __construct(string $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Check if allowed i.e. user matches.
     *
     * @param string $resource Resource to check
     * @param ForumsUserInterface $CurrentUser CurrentUser
     * @param ForumsUserInterface $user Owner of the resource
     * @return bool
     */
    public function check(string $resource, ForumsUserInterface $CurrentUser, ForumsUserInterface $user): bool
    {
        if ($this->resource !== $resource) {
            return false;
        }

        return $CurrentUser->isUser($user);
    }
}
