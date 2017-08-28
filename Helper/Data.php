<?php

namespace Mqlogic\Ldap\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Klasa wyciagania konfiguracji
 * @package Mqlogic\Ldap\Helper
 */
class Data
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Data constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->scopeConfig->getValue('ldap/general/active');
    }

    /**
     * @return array
     */
    public function getServers()
    {
        $servers = $this->scopeConfig->getValue('ldap/connection/servers');
        if (empty($servers)) {
            return [];
        }
        return explode(';', $servers);
    }

    /**
     * @return mixed
     */
    public function getProtocol()
    {
        return $this->scopeConfig->getValue('ldap/connection/protocol');
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return (int)$this->scopeConfig->getValue('ldap/connection/port');
    }

    /**
     * @return mixed
     */
    public function getDnPattern()
    {
        return $this->scopeConfig->getValue('ldap/connection/dnPattern');
    }

    /**
     * @param $login
     * @return string
     */
    public function getDn($login)
    {
        return sprintf($this->getDnPattern(), str_replace('@' . $this->getDomain(), '', $login));
    }

    /**
     * @return mixed
     */
    public function getDomain()
    {
        return $this->scopeConfig->getValue('ldap/connection/domain');
    }

}