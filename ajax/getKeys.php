<?php

/**
 * Get google authentication keys for a user
 *
 * @param string $title - key title
 * @return array - key data
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_authgoogle2fa_ajax_getKeys',
    function ($userId) {
        $Users = QUI::getUsers();
        $SessionUser = QUI::getUserBySession();
        $AuthUser = $Users->get((int)$userId);

        if ($Users->isNobodyUser($SessionUser)) {
            throw new QUI\Permissions\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.no.edit.rights'
                )
            );
        }

        $SessionUser->checkEditPermission();

        $keys = [];

        try {
            $secrets = json_decode($AuthUser->getAttribute('quiqqer.auth.google2fa.secrets'), true);

            if (empty($secrets)) {
                return $keys;
            }

            foreach ($secrets as $title => $secret) {
                $CreateUser = QUI::getUsers()->get($secret['createUserId']);

                $keys[] = [
                    'title' => $title,
                    'created' => $secret['createDate']
                        . ' - '
                        . $CreateUser->getUsername()
                        . ' ('
                        . $CreateUser->getId()
                        . ')'
                ];
            }
        } catch (QUI\Auth\Google2Fa\Exception $Exception) {
            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get(
                    'quiqqer/authgoogle2fa',
                    'message.ajax.getKeys.error',
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

        return $keys;
    },
    ['userId'],
    'Permission::checkAdminUser'
);
