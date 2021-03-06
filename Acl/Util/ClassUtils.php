<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\SecurityBundle\Acl\Util;

use Doctrine\Common\Util\ClassUtils as DoctrineClassUtils;

/**
 * Class related functionality for acl manipulation.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ClassUtils
{
    /**
     * Marker for Proxy class names.
     *
     * @var string
     */
    const MARKER = '__CG__';

    /**
     * Length of the proxy marker.
     *
     * @var int
     */
    const MARKER_LENGTH = 6;

    /**
     * This class should not be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Gets the real class name of a class name that could be a proxy.
     *
     * @param string|object $object
     *
     * @return string
     */
    public static function getRealClass($object)
    {
        $class = is_object($object) ? get_class($object) : $object;

        if (class_exists('Doctrine\Common\Util\ClassUtils')) {
            return DoctrineClassUtils::getRealClass($class);
        }

        // fallback in case doctrine common  is not installed
        if (false === $pos = strrpos($class, '\\'.self::MARKER.'\\')) {
            return $class;
        }

        return substr($class, $pos + self::MARKER_LENGTH + 2);
    }
}
