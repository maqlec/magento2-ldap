<?php

namespace Mqlogic\Ldap\Model;

use Mqlogic\Ldap\Helper\Data;
use Psr\Log\LoggerInterface;

/**
 * Klasa dostawcy serwerów do klienta LDAP
 * @package Mqlogic\Ldap\Model
 */
class ServerProvider
{
    /**
     * Zasób aktywnego serwera
     * @var resource
     */
    private $_activeServerResource;

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ServerProvider constructor.
     * @param Data $dataHelper
     * @param LoggerInterface $logger
     * @throws LdapException
     */
    public function __construct(
        Data $dataHelper,
        LoggerInterface $logger
    ) {
        //brak modułu LDAP
        if (!function_exists('ldap_connect')) {
            throw new LdapException('LDAP extension not installed');
        }
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
    }

    /**
     * Destruktor zamykający połączenia
     */
    public function __destruct()
    {
        $this->_close();
    }

    /**
     * Wybiera aktywny serwer z puli serwerów
     * @return resource
     * @throws LdapException
     */
    public function getActiveServer()
    {
        //jeśli serwer jest już wybrany - zwrot
        if ($this->_activeServerResource) {
            return $this->_activeServerResource;
        }
        //jeśli adres nie jest tablicą - tworzy tablicę 1 elementową z nim w środku
        $servers = $this->dataHelper->getServers();
        //logowanie kolejności serwerów
        \shuffle($servers);
        //wybór akrywnego serwera
        foreach ($servers as $address) {
            //parsowanie adresu i ping do serwera
            if (!$this->_isServerAlive($serverAddress = $this->_parseAddress($address))) {
                $this->logger->debug('LDAP doesn\'t respond: ' . $address);
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
    private function _isServerAlive(\stdClass $serverAddress)
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
        $serverAddress = new \stdClass();
        $serverAddress->protocol = $this->dataHelper->getProtocol();
        $serverAddress->port = $this->dataHelper->getPort();
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