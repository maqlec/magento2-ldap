<?php

declare(strict_types=1);

namespace Mqlogic\Ldap\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Data
{
    public const string XML_PATH_LDAP_GENERAL_ACTIVE = 'ldap/general/active';
    public const string XML_PATH_LDAP_CONNECTION_SERVERS = 'ldap/connection/servers';
    public const string XML_PATH_LDAP_CONNECTION_PROTOCOL = 'ldap/connection/protocol';
    public const string XML_PATH_LDAP_CONNECTION_PORT = 'ldap/connection/port';
    public const string XML_PATH_LDAP_CONNECTION_DN_PATTERN = 'ldap/connection/dnPattern';
    public const string XML_PATH_LDAP_CONNECTION_DOMAIN = 'ldap/connection/domain';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isActive(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_LDAP_GENERAL_ACTIVE);
    }

    public function getServers(): array
    {
        $servers = $this->scopeConfig->getValue(self::XML_PATH_LDAP_CONNECTION_SERVERS);
        if (empty($servers)) {
            return [];
        }
        if (str_contains($servers, ';')) {
            return explode(';', $servers);
        }
        return explode(',', $servers);
    }

    public function getProtocol(): mixed
    {
        return $this->scopeConfig->getValue(self::XML_PATH_LDAP_CONNECTION_PROTOCOL);
    }

    public function getPort(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_LDAP_CONNECTION_PORT);
    }

    public function getDnPattern(): mixed
    {
        return $this->scopeConfig->getValue(self::XML_PATH_LDAP_CONNECTION_DN_PATTERN);
    }

    public function getDn(string $login): string
    {
        return sprintf($this->getDnPattern(), str_replace('@' . $this->getDomain(), '', $login));
    }

    public function getDomain(): mixed
    {
        return $this->scopeConfig->getValue(self::XML_PATH_LDAP_CONNECTION_DOMAIN);
    }

}
