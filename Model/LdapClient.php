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
            return false;
        }
    }

}
