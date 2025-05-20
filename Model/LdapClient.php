<?php

declare(strict_types=1);

namespace Mqlogic\Ldap\Model;

use Mqlogic\Ldap\Helper\Data;

readonly class LdapClient
{
    public function __construct(
        private Data $dataHelper,
        private ServerConnectionProvider $serverConnectionProvider
    ) {
    }

    /**
     * @throws LdapException
     */
    public function authenticate(string $login, string $password): bool
    {
        $serverConnection = $this->serverConnectionProvider->getActiveServerConnection();
        $dn = $this->dataHelper->getDn($login);
        try {
            return ldap_bind($serverConnection, $dn, $password);
        } catch (\Exception $e) {
            throw new LdapException('Incorrect LDAP authentication');
        }
    }
}
