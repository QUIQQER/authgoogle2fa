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
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        $this->addCSSFile(dirname(__FILE__) . '/Login.css');
    }

    /**
     * @return string
     */
    public function getBody()
    {
        $this->addCSSFile(dirname(__FILE__) . '/Login.css');

        $Engine = QUI::getTemplateManager()->getEngine();
        return $Engine->fetch(dirname(__FILE__) . '/Login.html');
    }
}
