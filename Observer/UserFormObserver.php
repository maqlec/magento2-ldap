<?php

declare(strict_types=1);

namespace Mqlogic\Ldap\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UserFormObserver implements ObserverInterface
{
    public function __construct(private readonly \Magento\Framework\Registry $registry)
    {
    }

    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $block = $event->getBlock();
        if (!$block instanceof \Magento\User\Block\User\Edit\Tab\Main) {
            return;
        }

        $model = $this->registry->registry('permissions_user');

        $form = $block->getForm();
        $baseFieldset = $form->getElement('base_fieldset');
        $elements = $baseFieldset->getElements();
        foreach ($elements as $element) {
            if ($element->getId() === 'username'
                || $element->getId() === 'firstname'
                || $element->getId() === 'lastname'
            ) {
                $element->setRequired(false)->setDisabled(true);
            }
            if (($element->getId() === 'password'
                || $element->getId() === 'confirmation')
                && !$model->getCanUsePass()
            ) {
                $baseFieldset->removeField($element->getId());
            }
        }
    }
}
