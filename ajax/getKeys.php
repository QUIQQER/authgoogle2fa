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
        $AuthUser = QUI::getUsers()->get((int)$userId);
        $keys     = array();

        // @todo Check user edit permission of session user

        try {
            $secrets = json_decode($AuthUser->getAttribute('quiqqer.auth.google2fa.secrets'), true);

            if (empty($secrets)) {
                return $keys;
            }

            foreach ($secrets as $title => $secret) {
                $CreateUser = QUI::getUsers()->get($secret['createUserId']);

                $keys[] = array(
                    'title'   => $title,
                    'created' => $secret['createDate']
                                 . ' - '
                                 . $CreateUser->getUsername()
                                 . ' ('
                                 . $CreateUser->getId()
                                 . ')'
                );
            }
        } catch (QUI\Auth\Google2Fa\Exception $Exception) {
            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get(
                    'quiqqer/authgoogle2fa',
                    'message.ajax.getKeys.error',
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

        return $keys;
    },
    array('userId'),
    'Permission::checkAdminUser'
);
