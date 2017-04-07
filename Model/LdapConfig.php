<?php

namespace Mqlogic\Ldap\Model;

/**
 * Klasa konfiguracji klienta LDAP
 */
class LdapConfig
{

    /**
     * Aktywny
     * @var boolean
     */
    public $active = true;

    /**
     * Adres lub tablica adresów
     * @var string|array
     */
    public $address = [
    ];

    /**
     * Użytkownik
     * @var string
     */
    public $user = '';

    /**
     * Hasło
     * @var string
     */
    public $password = '';

    /**
     * Domena
     * @var string
     */
    public $domain = '';

    /**
     * Wzorzec logowania (domyślnie %s)
     * np. %s@example.com
     * np. uid=%s,dc=example,dc=com
     * @var string
     */
    public $dnPattern = '%s';

}
