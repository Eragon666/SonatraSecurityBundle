<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\SecurityBundle\Doctrine\ORM\Listener;

use Doctrine\ORM\Events;
use Sonatra\Bundle\SecurityBundle\Acl\Model\AclManagerInterface;
use Sonatra\Bundle\SecurityBundle\Acl\Model\AclObjectFilterInterface;
use Sonatra\Bundle\SecurityBundle\Acl\Model\AclRuleManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Sonatra\Bundle\SecurityBundle\Core\Token\ConsoleToken;
use Sonatra\Bundle\SecurityBundle\Exception\AccessDeniedException;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * This class listens to all database activity and automatically adds constraints as acls / aces.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class AclListener implements EventSubscriber
{
    /**
     * @var ContainerInterface
     */
    public $container;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authChecker;

    /**
     * @var AclManagerInterface
     */
    protected $aclManager;

    /**
     * @var AclRuleManagerInterface
     */
    protected $aclRuleManager;

    /**
     * @var AclObjectFilterInterface
     */
    protected $aclObjectFilter;

    /**
     * @var array
     */
    protected $postResetAcls = array();

    /**
     * Specifies the list of listened events.
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::postLoad,
            Events::onFlush,
            Events::postFlush,
        );
    }

    /**
     * This method is executed after every load that doctrine performs.
     *
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $token = $this->getTokenStorage()->getToken();

        if ($this->aclManager->isDisabled()
                || null === $token || $token instanceof ConsoleToken) {
            return;
        }

        $object = $args->getEntity();
        $this->getAclObjectFilter()->filter($object);
    }

    /**
     * This method is executed each time doctrine does a flush on an entitymanager.
     *
     * @param OnFlushEventArgs $args
     *
     * @throws AccessDeniedException When insufficient privilege for called action
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $this->postResetAcls = array();
        $token = $this->getTokenStorage()->getToken();

        if ($this->aclManager->isDisabled()
                || null === $token || $token instanceof ConsoleToken) {
            return;
        }

        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $this->getAclObjectFilter()->beginTransaction();

        // check all scheduled insertions
        foreach ($uow->getScheduledEntityInsertions() as $object) {
            $this->postResetAcls[] = $object;
            $this->getAclObjectFilter()->restore($object);

            if (!$this->getAuthorizationChecker()->isGranted(BasicPermissionMap::PERMISSION_CREATE, $object)) {
                throw new AccessDeniedException('Insufficient privilege to create the entity');
            }
        }

        // check all scheduled updates
        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $this->postResetAcls[] = $object;
            $this->getAclObjectFilter()->restore($object);

            if (!$this->getAuthorizationChecker()->isGranted(BasicPermissionMap::PERMISSION_EDIT, $object)) {
                throw new AccessDeniedException('Insufficient privilege to update the entity');
            }
        }

        // check all scheduled deletations
        foreach ($uow->getScheduledEntityDeletions() as $object) {
            if (!$this->getAuthorizationChecker()->isGranted(BasicPermissionMap::PERMISSION_DELETE, $object)) {
                throw new AccessDeniedException('Insufficient privilege to delete the entity');
            }
        }

        $this->getAclObjectFilter()->commit();
    }

    /**
     * Reset the preloaded acls used for the insertions.
     */
    public function postFlush()
    {
        $this->getAclManager()->resetPreloadAcls($this->postResetAcls);
        $this->postResetAcls = array();
    }

    /**
     * Gets security token storage.
     *
     * @return TokenStorageInterface
     */
    public function getTokenStorage()
    {
        $this->init();

        return $this->tokenStorage;
    }

    /**
     * Gets security authorization checker.
     *
     * @return AuthorizationCheckerInterface
     */
    public function getAuthorizationChecker()
    {
        $this->init();

        return $this->authChecker;
    }

    /**
     * Get the ACL Manager.
     *
     * @return AclManagerInterface
     */
    public function getAclManager()
    {
        $this->init();

        return $this->aclManager;
    }

    /**
     * Get the ACL Rule Manager.
     *
     * @return AclRuleManagerInterface
     */
    public function getAclRuleManager()
    {
        $this->init();

        return $this->aclRuleManager;
    }

    /**
     * Get the ACL Object Filter.
     *
     * @return AclObjectFilterInterface
     */
    public function getAclObjectFilter()
    {
        $this->init();

        return $this->aclObjectFilter;
    }

    /**
     * Get the security identities.
     *
     * @return SecurityIdentityInterface[]
     */
    public function getSecurityIdentities()
    {
        $token = $this->getTokenStorage()->getToken();

        return $this->aclManager->getSecurityIdentities($token);
    }

    /**
     * Init listener.
     */
    private function init()
    {
        if (null !== $this->container) {
            $this->tokenStorage = $this->container->get('security.token_storage');
            $this->authChecker = $this->container->get('security.authorization_checker');
            $this->aclManager = $this->container->get('sonatra_security.acl.manager');
            $this->aclRuleManager = $this->container->get('sonatra_security.acl.rule_manager');
            $this->aclObjectFilter = $this->container->get('sonatra_security.acl.object_filter');
            $this->container = null;
        }
    }
}
