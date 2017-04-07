<?php
namespace Mqlogic\Ldap\Plugin;

use Magento\Framework\Exception\AuthenticationException;
use Magento\User\Model\User;
use Mqlogic\Ldap\Model\LdapClient;

class LdapAuthenticationPlugin
{

    /**
     * @var LdapClient
     */
    private $ldapClient;

    /**
     * LdapLoginPlugin constructor.
     * @param LdapClient $ldapClient
     */
    public function __construct(
        LdapClient $ldapClient
    ) {
        $this->ldapClient = $ldapClient;
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
        if ($this->ldapClient->authenticate($userModel->getUserName(), $password)) {
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