<?php

namespace Mqlogic\Ldap\Plugin;

use Magento\Framework\Exception\AuthenticationException;
use Magento\User\Model\User;
use Mqlogic\Ldap\Helper\Data;
use Mqlogic\Ldap\Model\LdapClient;

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
     * LdapLoginPlugin constructor.
     * @param LdapClient $ldapClient
     * @param Data $dataHelper
     */
    public function __construct(
        LdapClient $ldapClient,
        Data $dataHelper
    ) {
        $this->ldapClient = $ldapClient;
        $this->dataHelper = $dataHelper;
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
        if ($this->dataHelper->isActive()
            && $this->ldapClient->authenticate($userModel->getUserName(), $password)
        ) {
            if ($userModel->getIsActive() != '1') {
                throw new AuthenticationException(
                    __('You did not sign in correctly or your account is temporarily disabled.')
                );
            }
            if (!$userModel->hasAssigned2Role($userModel->getId())) {
                throw new AuthenticationException(__('You need more permissions to access this.'));
            }

            return true;
        }
        return $proceed($password);
    }

}