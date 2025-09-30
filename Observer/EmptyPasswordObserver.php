<?php

declare(strict_types=1);

namespace Mqlogic\Ldap\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class EmptyPasswordObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $event->getDataObject()->setPassword('');
    }
}
