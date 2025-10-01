<?php

declare(strict_types=1);

namespace Mqlogic\Ldap\Plugin;

use Magento\Framework\Validator\EmailAddress;
use Magento\Framework\Validator\DataObjectFactory;

class UserFormValidationPlugin
{
    protected DataObjectFactory $_validatorObject;

    public function __construct(
        DataObjectFactory $validatorObjectFactory,
    ) {
        $this->_validatorObject = $validatorObjectFactory;
    }

    public function aroundAddUserInfoRules(\Magento\User\Model\UserValidationRules $subject, callable $proceed)
    {
        $validator = $this->_validatorObject->create();

        $emailValidity = new EmailAddress();
        $emailValidity->setMessage(
            __('Please enter a valid email.'),
            EmailAddress::INVALID
        );

        $validator->addRule(
            $emailValidity,
            'email'
        );
        return $validator;
    }
}
