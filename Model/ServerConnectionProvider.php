<?php

declare(strict_types=1);

namespace Mqlogic\Ldap\Model;

use LDAP\Connection;
use Mqlogic\Ldap\Helper\Data;
use Psr\Log\LoggerInterface;

class ServerConnectionProvider
{
    private ?Connection $activeServerConnection;

    public function __construct(
        private readonly Data $dataHelper,
        private readonly LoggerInterface $logger
    ) {
        $this->activeServerConnection = null;
    }

    /**
     * @throws LdapException
     */
    public function getActiveServerConnection(): Connection
    {
        if ($this->activeServerConnection) {
            return $this->activeServerConnection;
        }
        $servers = $this->dataHelper->getServers();
        \shuffle($servers);
        foreach ($servers as $address) {
            if (!$this->isServerAlive($serverAddress = $this->parseAddress($address))) {
                $this->logger->debug('LDAP doesn\'t respond: ' . $address);
                continue;
            }
            $ldap = ldap_connect($serverAddress->protocol . '://' . $serverAddress->host . ':' . $serverAddress->port);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);

            $this->activeServerConnection = $ldap;
            return $this->activeServerConnection;
        }
        throw new LdapException('No alive LDAP server found');
    }

    private function isServerAlive(\stdClass $serverAddress): bool
    {
        try {
            $errorCode = null;
            $errorMessage = null;
            fclose(fsockopen($serverAddress->host, $serverAddress->port, $errorCode, $errorMessage, 1));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Parsuje adres typu ldap://example.com:389
     * @throws LdapException
     */
    private function parseAddress(string $address): \stdClass
    {
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

    public function __destruct()
    {
        if (!$this->activeServerConnection) {
            return;
        }
        ldap_close($this->activeServerConnection);
        $this->activeServerConnection = null;
    }
}
