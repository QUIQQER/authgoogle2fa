<?php

namespace QUI\Auth\Google2Fa;

use PragmaRX\Google2FA\Google2FA;
use QUI;
use QUI\Auth\Google2Fa\Exception as Google2FaException;
use QUI\Security;
use QUI\Users\AbstractAuthenticator;
use QUI\Users\User;

/**
 * Class Auth
 *
 * Authentication handler for Google Authenticator
 *
 * @package QUI\Authe\Google2Fa
 */
class Auth extends AbstractAuthenticator
{
    /**
     * Google2FA class
     *
     * @var Google2FA
     */
    protected $Google2FA = null;

    /**
     * User that is to be authenticated
     *
     * @var User
     */
    protected $User = null;

    /**
     * Auth Constructor.
     *
     * @param string|array|integer $user - name of the user, or user id
     *
     * @throws QUI\Auth\Google2Fa\Exception
     */
    public function __construct($user = '')
    {
        if (!empty($user)) {
            try {
                $this->User = QUI::getUsers()->getUserByName($user);
            } catch (\Exception $Exception) {
                $this->User = QUI::getUsers()->getNobody();
            }
        }

        $this->Google2FA = new Google2FA();
    }

    /**
     * @param null|\QUI\Locale $Locale
     * @return string
     */
    public function getTitle($Locale = null)
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/authgoogle2fa', 'google2fa.title');
    }

    /**
     * @param null|\QUI\Locale $Locale
     * @return string
     */
    public function getDescription($Locale = null)
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/authgoogle2fa', 'google2fa.title');
    }

    /**
     * Authenticate the user
     *
     * @param string|array|integer $authData
     *
     * @throws QUI\Auth\Google2Fa\Exception
     */
    public function auth($authData)
    {
        if (
            !is_array($authData)
            || !isset($authData['code'])
        ) {
            throw new Google2FaException([
                'quiqqer/authgoogle2fa',
                'exception.auth.wrong.auth.code'
            ]);
        }

        $authCode = $authData['code'];
        $authSecrets = json_decode($this->User->getAttribute('quiqqer.auth.google2fa.secrets'), true);

        // if no secret keys have been generated -> automatically authenticate the user
        if (empty($authSecrets)) {
            return;
        }

        foreach ($authSecrets as $k => $secretData) {
            $key = trim(Security::decrypt($secretData['key']));

            if ($this->Google2FA->verifyKey($key, $authCode)) {
                return;
            }

            // if key did not work check for recovery keys
            foreach ($secretData['recoveryKeys'] as $k2 => $recoveryKeyData) {
                if ($recoveryKeyData['used']) {
                    continue;
                }

                $recoveryKey = trim(Security::decrypt($recoveryKeyData['key']));

                if ($recoveryKey != $authCode) {
                    continue;
                }

                // set used status of recovery key to true
                $recoveryKeyData['used'] = true;
                $recoveryKeyData['usedDate'] = date('Y-m-d H:i:s');

                $secretData['recoveryKeys'][$k2] = $recoveryKeyData;
                $authSecrets[$k] = $secretData;

                $this->User->setAttribute('quiqqer.auth.google2fa.secrets', json_encode($authSecrets));
                $this->User->save(QUI::getUsers()->getSystemUser());

                return;
            }
        }

        throw new Google2FaException([
            'quiqqer/authgoogle2fa',
            'exception.auth.wrong.auth.code'
        ]);
    }

    /**
     * Return the user object
     *
     * @return \QUI\Interfaces\Users\User
     */
    public function getUser()
    {
        return $this->User;
    }

    /**
     * Return the quiqqer user id
     *
     * @return integer|boolean
     */
    public function getUserId()
    {
        return $this->User->getId();
    }

    /**
     * Generate 16-bit (encrypted) recovery keys as alternative logins
     *
     * @param int $count (optional) - number of key [default: 10]
     * @return array
     */
    public static function generateRecoveryKeys($count = 10)
    {
        $recoveryKeys = [];
        $Google2FA = new Google2FA();

        for ($i = 0; $i < $count; $i++) {
            $recoveryKeys[] = [
                'key' => Security::encrypt(md5($Google2FA->generateSecretKey(16))),
                'used' => false,
                'usedDate' => false
            ];
        }

        return $recoveryKeys;
    }

    /**
     * @return \QUI\Control
     */
    public static function getLoginControl()
    {
        return new QUI\Auth\Google2Fa\Controls\Login();
    }

    /**
     * @return \QUI\Control
     */
    public static function getSettingsControl()
    {
        return new QUI\Auth\Google2Fa\Controls\Settings();
    }

    /**
     * @return \QUI\Control
     */
    public static function getPasswordResetControl()
    {
        return null;
    }

    /**
     * @return bool
     */
    public static function isCLICompatible()
    {
        return true;
    }

    /**
     * @param QUI\System\Console $Console
     */
    public function cliAuthentication(QUI\System\Console $Console)
    {
        $Console->clearMsg();

        $Console->writeLn();
        $Console->writeLn(
            QUI::getLocale()->get('quiqqer/authgoogle2fa', 'message.insert.code')
        );

        $Console->writeLn(
            QUI::getLocale()->get('quiqqer/authgoogle2fa', 'message.insert.code.title'),
            'green'
        );

        $code = $Console->readInput();

        $this->auth([
            'code' => $code
        ]);
    }
}
