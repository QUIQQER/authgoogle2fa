<?php

use QUI;
use PragmaRX\Google2FA\Google2FA;
use QUI\Utils\Security\Orthos;
use QUI\Security;
use QUI\Auth\Google2Fa\Auth;

/**
 * Re-generate a set of recovery keys for a user authentication key
 *
 * @param string $title - key title
 * @return bool - success
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_authgoogle2fa_ajax_regenerateRecoveryKeys',
    function ($userId, $title) {
        $AuthUser = QUI::getUsers()->get((int)$userId);
        $title    = Orthos::clear($title);
        $EditUser = QUI::getUserBySession();

        // @todo Check user edit permission of session user

        try {
            $secrets = json_decode($AuthUser->getAttribute('quiqqer.auth.google2fa.secrets'), true);

            if (empty($secrets)) {
                $secrets = array();
            }

            if (!isset($secrets[$title])) {
                throw new QUI\Auth\Google2Fa\Exception(array(
                    'quiqqer/authgoogle2fa',
                    'exception.ajax.getKey.title.not.found',
                    array(
                        'title'  => $title,
                        'user'   => $AuthUser->getUsername(),
                        'userId' => $AuthUser->getId()
                    )
                ));
            }

            $secrets[$title]['recoveryKeys'] = Auth::generateRecoveryKeys();

            $AuthUser->setAttribute(
                'quiqqer.auth.google2fa.secrets',
                json_encode($secrets)
            );

            $AuthUser->save();
        } catch (QUI\Auth\Google2Fa\Exception $Exception) {
            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get(
                    'quiqqer/authgoogle2fa',
                    'message.ajax.regenerateRecoveryKeys.error',
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
                'message.ajax.regenerateRecoveryKeys.success',
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
