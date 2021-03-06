<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\SecurityBundle\Exception;

use Symfony\Component\Security\Core\Exception\AccessDeniedException as BaseAccessDeniedException;

/**
 * Base AccessDeniedException for the Security component.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class AccessDeniedException extends BaseAccessDeniedException implements ExceptionInterface
{
}
