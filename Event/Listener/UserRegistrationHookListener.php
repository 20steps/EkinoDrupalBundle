<?php

/*
 * This file is part of the Ekino Drupal package.
 *
 * (c) 2011 Ekino
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ekino\Bundle\DrupalBundle\Event\Listener;

use Ekino\Bundle\DrupalBundle\Event\DrupalEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * These methods are called by the drupal user hook
 *
 * see for more information about parameters http://api.drupal.org/api/drupal/modules--user--user.api.php/7
 */
class UserRegistrationHookListener
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $providerKeys;

    /**
     * @param LoggerInterface $logger
     * @param Request         $request
     * @param array           $providerKeys
     */
    public function __construct(LoggerInterface $logger, $providerKeys)
    {
        $this->logger       = $logger;
        // FIXME
        // $this->request      = $request;
        $this->providerKeys = $providerKeys;
    }

    /**
     * http://api.drupal.org/api/drupal/modules--user--user.api.php/function/hook_user_login/7
     *
     * @param DrupalEvent $event
     */
    public function onLogin(DrupalEvent $event)
    {
        $user = $event->getParameter(1);

        if (!$user instanceof UserInterface) {
            throw new \RuntimeException('An instance of UserInterface is expected');
        }

        // The ContextListener from the Security component is hijacked to insert a valid token into session
        // so next time the user go to a valid symfony2 url with a proper security context, then the following token
        // will be used
        foreach ($this->providerKeys as $providerKey) {
            $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());
            $this->request->getSession()->set('_security_'.$providerKey, serialize($token));
        }
    }

    /**
     * @param DrupalEvent $event
     */
    public function onLogout(DrupalEvent $event)
    {
        foreach ($this->providerKeys as $providerKey) {
            $this->request->getSession()->set('_security_'.$providerKey, null);
        }
    }
}
