<?php

/**
 * This file contains
 */
namespace QUI\Auth\Google2Fa\Controls;

use QUI;
use QUI\Control;

/**
 * Class Login
 *
 * @package QUI
 */
class Login extends Control
{
    /**
     * @return string
     */
    public function getBody()
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        return $Engine->fetch(dirname(__FILE__) . '/Login.html');
    }
}
