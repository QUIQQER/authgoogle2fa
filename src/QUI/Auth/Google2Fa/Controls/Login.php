<?php

/**
 * This file contains QUI\Auth\Google2Fa\Controls\Login
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
     * Login constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->addCSSFile(dirname(__FILE__) . '/Login.css');
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        $username = QUI::getSession()->get('username');
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'username' => $username
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/Login.html');
    }
}
