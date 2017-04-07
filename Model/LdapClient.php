<?php

namespace Mqlogic\Ldap\Model;

/**
 * Klasa klienta LDAP
 */
class LdapClient
{

    /**
     * Konfiguracja serwera
     * @var LdapConfig
     */
    private $_config;

    /**
     * Zasób aktywnego serwera
     * @var resource
     */
    private $_activeServerResource;

    /**
     * Konstruktor sprawdza istnienie modułu ldap
     * @param LdapConfig $config konfiguracja serwera
     * @throws LdapException brak modułu LDAP
     */
    public function __construct(
        LdapConfig $config
    ) {
        //brak modułu LDAP
        if (!function_exists('ldap_connect')) {
            throw new LdapException('LDAP extension not installed');
        }
        $this->_config = $config;
    }

    /**
     * Destruktor zamykający połączenia
     */
    public function __destruct()
    {
        $this->_close();
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
        $server = $this->_getActiveServer();
        $dn = sprintf($this->_config->dnPattern, str_replace('@' . $this->_config->domain, '', $login));
        try {
            //autoryzacja sklejonym loginem
            return ldap_bind($server, $dn, $password);
        } catch (\Exception $e) {
            //niepoprawne dane logowania
            return false;
        }
    }

    /**
     * Wybiera aktywny serwer z puli serwerów
     * @return type
     * @throws LdapException
     */
    private function _getActiveServer()
    {
        //jeśli serwer jest już wybrany - zwrot
        if ($this->_activeServerResource) {
            return $this->_activeServerResource;
        }
        //jeśli adres nie jest tablicą - tworzy tablicę 1 elementową z nim w środku
        $servers = is_array($this->_config->address) ? $this->_config->address : [$this->_config->address];
        //logowanie kolejności serwerów
        \shuffle($servers);
        //wybór akrywnego serwera
        foreach ($servers as $address) {
            //parsowanie adresu i ping do serwera
            if (!$this->_isServerAlive($serverAddress = $this->_parseAddress($address))) {
                continue;
            }
            //powoływanie zasobu ldap
            $ldap = ldap_connect($serverAddress->protocol . '://' . $serverAddress->host, $serverAddress->port);
            //ustawianie opcji ldap
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            //zwrot zasobu
            return $this->_activeServerResource = $ldap;
        }
        //brak aktywnego serwera
        throw new LdapException('No alive LDAP server found');
    }

    /**
     * Określa aktywność serwera
     * @param $serverAddress adres serwera
     * @return boolean
     */
    private function _isServerAlive(LdapServerAddress $serverAddress)
    {
        try {
            $errno = null;
            $errstr = null;
            //próba połączenia i zamknięcia połączenia
            fclose(fsockopen($serverAddress->host, $serverAddress->port, $errno, $errstr, 1));
            return true;
        } catch (\Exception $e) {
            //nie można połączyć
            return false;
        }
    }

    /**
     * Parsuje adres typu ldap://example.com:389
     * @param string adres
     * @return LdapServerAddress
     * @throws LdapException
     */
    private function _parseAddress($address)
    {
        //nowy obiekt adresu
        $serverAddress = new LdapServerAddress();
        $matches = [];
        //ustawianie protokołu
        if (preg_match('/^([a-z]+):\/\//', $address, $matches)) {
            $address = str_replace($matches[1] . '://', '', $address);
            $serverAddress->protocol = $matches[1];
        }
        //błędny protokół
        if ($serverAddress->protocol != 'ldap' && $serverAddress->protocol != 'ldaps') {
            throw new LdapException('Invalid server protocol: ' . $serverAddress->protocol);
        }
        //ustalanie portu
        if (preg_match('/:([0-9]+)$/', $address, $matches)) {
            $address = str_replace(':' . $matches[1], '', $address);
            $serverAddress->port = $matches[1];
        }
        //ustalanie hosta
        if (preg_match('/([a-z\.0-9]+)/', $address, $matches)) {
            $serverAddress->host = $matches[1];
            return $serverAddress;
        }
        //niepoprawny adres
        throw new LdapException('Invalid server address: ' . $address);
    }

    /**
     * Zamyka połączenie z aktywnym serwerem
     */
    private function _close()
    {
        //połączenie nie jest otwarte
        if (!$this->_activeServerResource) {
            return;
        }
        //zamykanie połączenia
        ldap_close($this->_activeServerResource);
        $this->_activeServerResource = null;
    }

}
