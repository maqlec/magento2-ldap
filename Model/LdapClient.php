<?php

namespace Mqlogic\Ldap\Model;

use Mqlogic\Ldap\Helper\Data;

/**
 * Klasa klienta LDAP
 */
class LdapClient
{

    /**
     * @var Data
     */
    private $config;

    /**
     * @var ServerProvider
     */
    private $serverProvider;

    /**
     * LdapClient constructor.
     * @param Data $dataHelper
     * @param ServerProvider $serverProvider
     * @throws LdapException
     */
    public function __construct(
        Data $dataHelper,
        ServerProvider $serverProvider
    ) {
        $this->config = $dataHelper;
        $this->serverProvider = $serverProvider;
    }

    /**
     * Autoryzuje po DN i haśle
     * @param string $login login lub dn
     * @param string $password
     * @return boolean
     * @throws LdapException błędy parametów (nie logowania)
     */
    public function authenticate($login, $password)
    {
        $server = $this->serverProvider->getActiveServer();
        $dn = $this->config->getDn($login);
        try {
            //autoryzacja sklejonym loginem
            return ldap_bind($server, $dn, $password);
        } catch (\Exception $e) {
            //niepoprawne dane logowania
            throw new LdapException('Incorrect authentication');
        }
    }

    public function searchByEmail($email)
    {
        $server = $this->serverProvider->getActiveServer();
        $baseDn = $this->config->getBaseDn();
        $attributes = ['sAMAccountName', 'givenName', 'sn', 'mail'];

        $result = ldap_search(
            $server,
            $baseDn,
            sprintf('(|(userPrincipalName=%s)(proxyAddresses=*%s)(sAMAccountName=%s))', $email, $email, $email),
            $attributes
        );
        $entries = ldap_get_entries($server, $result);

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
