<?php

declare(strict_types=1);

namespace Mqlogic\Ldap\Plugin;

use Magento\Framework\Exception\AuthenticationException;
use Magento\User\Model\User;
use Mqlogic\Ldap\Helper\Data;
use Mqlogic\Ldap\Model\LdapClient;
use Mqlogic\Ldap\Model\LdapException;
use Psr\Log\LoggerInterface;

class LdapAuthenticationPlugin
{
    public function __construct(
        private readonly LdapClient $ldapClient,
        private readonly Data $dataHelper,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws AuthenticationException
     */
    public function aroundVerifyIdentity(User $userModel, callable $proceed, $password): bool
    {
        if (!$this->dataHelper->isActive()) {
            return $proceed($password);
        }

        try {
            $this->ldapClient->authenticate($userModel->getUserName(), $password);
            if ($userModel->getIsActive() != '1') {
                throw new AuthenticationException(
                    __(
                        'The account sign-in was incorrect or your account is disabled temporarily. '
                        . 'Please wait and try again later.'
                    )
                );
            }
            if (!$userModel->hasAssigned2Role($userModel->getId())) {
                throw new AuthenticationException(__('More permissions are needed to access this.'));
            }
            return true;
        } catch (LdapException $e) {
            $this->logger->warning($e->getMessage());
        }

        return $proceed($password);
    }
}
