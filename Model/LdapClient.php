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

    public function searchByEmail($email): \Magento\Framework\DataObject
    {
        $serverConnection = $this->serverConnectionProvider->getActiveServerConnection();
        $baseDn = $this->dataHelper->getBaseDn();
        $attributes = ['sAMAccountName', 'givenName', 'sn', 'mail'];

        $result = ldap_search(
            $serverConnection,
            $baseDn,
            sprintf('(|(userPrincipalName=%s)(proxyAddresses=*%s)(sAMAccountName=%s))', $email, $email, $email),
            $attributes
        );
        $entries = ldap_get_entries($serverConnection, $result);

        $dataObject = new \Magento\Framework\DataObject();
        if ($entries['count'] > 0) {
            foreach ($attributes as $attr) {
                if (isset($entries[0][strtolower($attr)])) {
                    $dataObject->setData($attr, $entries[0][strtolower($attr)][0]);
                }
            }
        }
        return $dataObject;
    }

}
