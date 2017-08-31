<?php

namespace Mqlogic\Ldap\Plugin;

use Magento\Framework\Exception\AuthenticationException;
use Magento\User\Model\User;
use Mqlogic\Ldap\Helper\Data;
use Mqlogic\Ldap\Model\LdapClient;
use Mqlogic\Ldap\Model\LdapException;
use Psr\Log\LoggerInterface;

class LdapAuthenticationPlugin
{

    /**
     * @var LdapClient
     */
    private $ldapClient;

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * LdapAuthenticationPlugin constructor.
     * @param LdapClient $ldapClient
     * @param Data $dataHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        LdapClient $ldapClient,
        Data $dataHelper,
        LoggerInterface $logger
    ) {
        $this->ldapClient = $ldapClient;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
    }

    /**
     * @param User $userModel
     * @param callable $proceed
     * @param $password
     * @return bool
     * @throws AuthenticationException
     */
    public function aroundVerifyIdentity(User $userModel, callable $proceed, $password)
    {
        if (!$this->dataHelper->isActive()) {
            return $proceed($password);
        }

        try {
            $this->ldapClient->authenticate($userModel->getUserName(), $password);
            if ($userModel->getIsActive() != '1') {
                throw new AuthenticationException(
                    __('You did not sign in correctly or your account is temporarily disabled.')
                );
            }
            if (!$userModel->hasAssigned2Role($userModel->getId())) {
                throw new AuthenticationException(__('You need more permissions to access this.'));
            }
            return true;
        } catch (LdapException $e) {
            $this->logger->warning($e->getMessage());
            return $proceed($password);
        }

        return $proceed($password);
    }

}