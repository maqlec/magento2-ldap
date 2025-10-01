<?php

declare(strict_types=1);

namespace Mqlogic\Ldap\Controller\Adminhtml\User;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Security\Model\SecurityCookie;
use Magento\User\Model\Spi\NotificationExceptionInterface;
use Mqlogic\Ldap\Model\LdapClient;

class Save extends \Magento\User\Controller\Adminhtml\User\Save implements HttpPostActionInterface
{
    private SecurityCookie $securityCookie;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\User\Model\UserFactory $userFactory,
        private readonly LdapClient $ldapClient,
    ) {
        parent::__construct($context, $coreRegistry, $userFactory);
    }

    public function execute()
    {
        $userId = (int)$this->getRequest()->getParam('user_id');
        $data = $this->getRequest()->getPostValue();
        if (array_key_exists('form_key', $data)) {
            unset($data['form_key']);
        }
        if (!$data) {
            $this->_redirect('adminhtml/*/');
            return;
        }

        /** @var $model \Magento\User\Model\User */
        $model = $this->_userFactory->create()->load($userId);
        if ($userId && $model->isObjectNew()) {
            $this->messageManager->addError(__('This user no longer exists.'));
            $this->_redirect('adminhtml/*/');
            return;
        }
        $data['can_use_pass'] = (bool)$model->getCanUsePass();
        $model->setData($this->_getAdminUserData($data));
        $userRoles = $this->getRequest()->getParam('roles', []);
        if (count($userRoles)) {
            $model->setRoleId($userRoles[0]);
        }

        /** @var $currentUser \Magento\User\Model\User */
        $currentUser = $this->_objectManager->get(\Magento\Backend\Model\Auth\Session::class)->getUser();
        if ($userId == $currentUser->getId()
            && $this->_objectManager->get(\Magento\Framework\Validator\Locale::class)
                ->isValid($data['interface_locale'])
        ) {
            $this->_objectManager->get(
                \Magento\Backend\Model\Locale\Manager::class
            )->switchBackendInterfaceLocale(
                $data['interface_locale']
            );
        }

        /** Before updating admin user data, ensure that password of current admin user is entered and is correct */
        $currentUserPasswordField = \Magento\User\Block\User\Edit\Tab\Main::CURRENT_USER_PASSWORD_FIELD;
        $isCurrentUserPasswordValid = isset($data[$currentUserPasswordField])
            && !empty($data[$currentUserPasswordField]) && is_string($data[$currentUserPasswordField]);
        try {
            if (!($isCurrentUserPasswordValid)) {
                throw new AuthenticationException(
                    __('The password entered for the current user is invalid. Verify the password and try again.')
                );
            }
            $currentUser->performIdentityCheck($data[$currentUserPasswordField]);

            // LDAP search and update user model
            $ldapUser = $this->ldapClient->searchByEmail($model->getEmail());
            if (!$ldapUser->getData('mail')) {
                throw new LocalizedException(__('No LDAP user found with this email address.'));
            }
            $previousEmail = $model->getEmail();
            $model->setEmail($ldapUser->getData('mail'));
            $model->setUsername($ldapUser->getData('sAMAccountName'));
            $model->setFirstName($ldapUser->getData('givenName'));
            $model->setLastName($ldapUser->getData('sn'));

            $model->save();

            if ($previousEmail != $model->getEmail()) {
                $this->messageManager->addNotice(
                    __(
                        'Email has been changed from %1 to %2 based on LDAP data.',
                        $previousEmail,
                        $model->getEmail()
                    )
                );
            }
            // end LDAP and update user model

            $this->messageManager->addSuccess(__('You saved the user.'));
            $this->_getSession()->setUserData(false);
            $this->_redirect('adminhtml/*/');

//            $model->sendNotificationEmailsIfRequired();
        } catch (UserLockedException $e) {
            $this->_auth->logout();
            $this->getSecurityCookie()->setLogoutReasonCookie(
                \Magento\Security\Model\AdminSessionsManager::LOGOUT_REASON_USER_LOCKED
            );
            $this->_redirect('*');
        } catch (NotificationExceptionInterface $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Magento\Framework\Exception\AuthenticationException $e) {
            $this->messageManager->addError(
                __('The password entered for the current user is invalid. Verify the password and try again.')
            );
            $this->redirectToEdit($model, $data);
        } catch (\Magento\Framework\Validator\Exception $e) {
            $messages = $e->getMessages();
            $this->messageManager->addMessages($messages);
            $this->redirectToEdit($model, $data);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($e->getMessage()) {
                $this->messageManager->addError($e->getMessage());
            }
            $this->redirectToEdit($model, $data);
        }
    }

    private function getSecurityCookie()
    {
        if (!($this->securityCookie instanceof SecurityCookie)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(SecurityCookie::class);
        } else {
            return $this->securityCookie;
        }
    }
}
