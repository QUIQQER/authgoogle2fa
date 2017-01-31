<?php

use QUI\Utils\Security\Orthos;

/**
 * Create new google authenticator key for a user
 *
 * @param array $titles - titles of the keys that should be deleted
 * @return bool - success
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_authgoogle2fa_ajax_deleteKeys',
    function ($userId, $titles) {
        $AuthUser = QUI::getUsers()->get((int)$userId);
        $titles   = Orthos::clearArray(json_decode($titles, true));

        // @todo Check user edit permission of session user

        try {
            $secrets = json_decode($AuthUser->getAttribute('quiqqer.auth.google2fa.secrets'), true);

            foreach ($titles as $title) {
                if (isset($secrets[$title])) {
                    unset($secrets[$title]);
                }
            }

            $AuthUser->setAttribute(
                'quiqqer.auth.google2fa.secrets',
                json_encode($secrets)
            );

            $AuthUser->save();
        } catch (QUI\Auth\Google2Fa\Exception $Exception) {
            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get(
                    'quiqqer/authgoogle2fa',
                    'message.ajax.deleteKeys.error',
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
                'message.ajax.deleteKeys.success'
            )
        );

        return true;
    },
    array('userId', 'titles'),
    'Permission::checkAdminUser'
);
