<?php

/**
 * Re-generate a set of recovery keys for a user authentication key
 *
 * @param string $title - key title
 * @return bool - success
 */

use QUI\Auth\Google2Fa\Auth;
use QUI\Utils\Security\Orthos;

QUI::$Ajax->registerFunction(
    'package_quiqqer_authgoogle2fa_ajax_regenerateRecoveryKeys',
    function ($userId, $title) {
        $Users = QUI::getUsers();
        $SessionUser = QUI::getUserBySession();
        $AuthUser = $Users->get((int)$userId);
        $title = Orthos::clear($title);

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
            $secrets = json_decode($AuthUser->getAttribute('quiqqer.auth.google2fa.secrets'), true);

            if (empty($secrets)) {
                $secrets = [];
            }

            if (!isset($secrets[$title])) {
                throw new QUI\Auth\Google2Fa\Exception([
                    'quiqqer/authgoogle2fa',
                    'exception.ajax.getKey.title.not.found',
                    [
                        'title' => $title,
                        'user' => $AuthUser->getUsername(),
                        'userId' => $AuthUser->getId()
                    ]
                ]);
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
                    [
                        'error' => $Exception->getMessage()
                    ]
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
                [
                    'title' => $title
                ]
            )
        );

        return true;
    },
    ['userId', 'title'],
    'Permission::checkAdminUser'
);
