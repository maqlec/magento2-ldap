<?php

namespace Mqlogic\Ldap\Model;

/**
 * Klasa konfiguracji klienta LDAP
 */
class LdapServerAddress
{

    /**
     * Protokół np. ldap, https itp. (domyślny ldap)
     * @var string
     */
    public $protocol = 'ldap';

    /**
     * Adres hosta np. ldap.example.com
     * @var string
     */
    public $host;

    /**
     * Port usługi (domyślny 389)
     * @var integer
     */
    public $port = 389;

}
