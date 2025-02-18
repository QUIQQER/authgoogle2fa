<?php

/**
 * This file contains
 */

namespace QUI\Auth\Google2Fa\Controls;

use QUI\Control;

/**
 * Class QUIQQERLogin
 *
 * @package QUI
 */
class Settings extends Control
{
    /**
     * @return string
     */
    public function getBody(): string
    {
        return '<div class="quiqqer-auth-google2fa-settings"     
                  data-qui="package/quiqqer/authgoogle2fa/bin/controls/Settings">
            </div>';
    }
}
