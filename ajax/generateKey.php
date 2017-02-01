<?php

use QUI;
use PragmaRX\Google2FA\Google2FA;
use QUI\Utils\Security\Orthos;
use QUI\Security;
use QUI\Auth\Google2Fa\Auth;

/**
 * Create new google authenticator key for a user
 *
 * @param string $title - key title
 * @return bool - success
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_authgoogle2fa_ajax_generateKey',
    function ($userId, $title) {
        $Users       = QUI::getUsers();
        $SessionUser = QUI::getUserBySession();
        $AuthUser    = $Users->get((int)$userId);
        $title       = Orthos::clear($title);

        if ($Users->isNobodyUser($SessionUser)) {
            throw new QUI\Permissions\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.no.edit.rights'
                )
            );
        }

        $SessionUser->checkEditPermission();

        try {
            $Google2FA = new Google2FA();
            $secrets   = json_decode($AuthUser->getAttribute('quiqqer.auth.google2fa.secrets'), true);

            if (empty($secrets)) {
                $secrets = array();
            }

            if (isset($secrets[$title])) {
                throw new QUI\Auth\Google2Fa\Exception(array(
                    'quiqqer/authgoogle2fa',
                    'exception.ajax.generateKey.title.already.exists',
                    array(
                        'title' => $title
                    )
                ));
            }

            $secrets[$title] = array(
                'key'          => Security::encrypt($Google2FA->generateSecretKey(32)),
                'recoveryKeys' => Auth::generateRecoveryKeys(),
                'createUserId' => $SessionUser->getId(),
                'createDate'   => date('Y-m-d H:i:s')
            );

            $AuthUser->setAttribute(
                'quiqqer.auth.google2fa.secrets',
                json_encode($secrets)
            );

            $AuthUser->save();
        } catch (QUI\Auth\Google2Fa\Exception $Exception) {
            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get(
                    'quiqqer/authgoogle2fa',
                    'message.ajax.generateKey.error',
                    array(
                        'error' => $Exception->getMessage()
                    )
                )
            );

            return false;
        } catch (\Exception $Exception) {
            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get(
                    'quiqqer/authgoogle2fa',
                    'message.ajax.general.error'
                )
            );

            return false;
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/authgoogle2fa',
                'message.ajax.generateKey.success',
                array(
                    'title' => $title
                )
            )
        );

        return true;
    },
    array('userId', 'title'),
    'Permission::checkAdminUser'
);
