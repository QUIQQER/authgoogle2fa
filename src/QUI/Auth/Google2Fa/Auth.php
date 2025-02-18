<?php

namespace QUI\Auth\Google2Fa;

use PragmaRX\Google2FA\Google2FA;
use QUI;
use QUI\Auth\Google2Fa\Exception as Google2FaException;
use QUI\Control;
use QUI\Locale;
use QUI\Security\Encryption;
use QUI\Users\AbstractAuthenticator;

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
    protected Google2FA | null $Google2FA = null;

    /**
     * User that is to be authenticated
     *
     * @var QUI\Interfaces\Users\User | null
     */
    protected QUI\Interfaces\Users\User | null $User = null;

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
            } catch (\Exception) {
                $this->User = QUI::getUsers()->getNobody();
            }
        }

        $this->Google2FA = new Google2FA();
    }

    /**
     * @param null|Locale $Locale
     * @return string
     */
    public function getTitle(null | Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/authgoogle2fa', 'google2fa.title');
    }

    /**
     * @param null|Locale $Locale
     * @return string
     */
    public function getDescription(null | Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/authgoogle2fa', 'google2fa.title');
    }

    /**
     * Authenticate the user
     *
     * @param string|array|integer $authParams
     *
     * @throws QUI\Auth\Google2Fa\Exception
     */
    public function auth(string | array | int $authParams): void
    {
        if (
            !is_array($authParams)
            || !isset($authParams['code'])
        ) {
            throw new Google2FaException([
                'quiqqer/authgoogle2fa',
                'exception.auth.wrong.auth.code'
            ]);
        }

        $authCode = $authParams['code'];
        $authSecrets = json_decode($this->User->getAttribute('quiqqer.auth.google2fa.secrets'), true);

        // if no secret keys have been generated -> automatically authenticate the user
        if (empty($authSecrets)) {
            return;
        }

        foreach ($authSecrets as $k => $secretData) {
            $key = trim(Encryption::decrypt($secretData['key']));

            if ($this->Google2FA->verifyKey($key, $authCode)) {
                return;
            }

            // if key did not work check for recovery keys
            foreach ($secretData['recoveryKeys'] as $k2 => $recoveryKeyData) {
                if ($recoveryKeyData['used']) {
                    continue;
                }

                $recoveryKey = trim(Encryption::decrypt($recoveryKeyData['key']));

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
     * @return QUI\Interfaces\Users\User
     */
    public function getUser(): QUI\Interfaces\Users\User
    {
        return $this->User;
    }

    /**
     * Return the quiqqer user id
     *
     * @return integer
     */
    public function getUserId(): int
    {
        return $this->User->getId();
    }

    /**
     * Generate 16-bit (encrypted) recovery keys as alternative logins
     *
     * @param int $count (optional) - number of key [default: 10]
     * @return array
     */
    public static function generateRecoveryKeys(int $count = 10): array
    {
        $recoveryKeys = [];
        $Google2FA = new Google2FA();

        for ($i = 0; $i < $count; $i++) {
            $recoveryKeys[] = [
                'key' => Encryption::encrypt(md5($Google2FA->generateSecretKey(16))),
                'used' => false,
                'usedDate' => false
            ];
        }

        return $recoveryKeys;
    }

    /**
     * @return Control|null
     */
    public static function getLoginControl(): ?Control
    {
        return new QUI\Auth\Google2Fa\Controls\Login();
    }

    /**
     * @return Control|null
     */
    public static function getSettingsControl(): ?Control
    {
        return new QUI\Auth\Google2Fa\Controls\Settings();
    }

    /**
     * @return Control|null
     */
    public static function getPasswordResetControl(): ?Control
    {
        return null;
    }

    /**
     * @return bool
     */
    public static function isCLICompatible(): bool
    {
        return true;
    }

    /**
     * @param QUI\System\Console $Console
     * @throws Exception
     */
    public function cliAuthentication(QUI\System\Console $Console): void
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
