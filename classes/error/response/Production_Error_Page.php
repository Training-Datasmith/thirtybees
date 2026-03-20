<?php

declare (strict_types=1);
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
namespace Thirtybees\Core\Error\Response;

use Configuration;
use Thirtybees\Core\Error\Error_Description;
use Throwable;
/**
 * Class DebugErrorPageCore
 */
class Production_Error_Page_Core extends Abstract_Error_Page
{
    /**
     * Return content type
     * @return string
     */
    protected function get_content_type()
    {
        return 'text/html';
    }
    /**
     * @return string
     */
    protected function render_error(Error_Description $error_description)
    {
        return static::display_error_template(_PS_ROOT_DIR_ . '/error500.phtml', ['shopEmail' => $this->get_shop_email(), 'encrypted' => $this->get_encrypted_message($error_description)]);
    }
    protected function get_shop_email(): string
    {
        try {
            $email = Configuration::get('PS_SHOP_EMAIL');
            if ($email) {
                return $email;
            }
        } catch (Throwable) {
        }
        return 'contact@thirtybees.com';
    }
    /**
     * @return string
     */
    private function get_encrypted_message(Error_Description $error_description)
    {
        try {
            $msg = $error_description->encrypt();
            if ($msg) {
                return $msg;
            }
            return 'Failed to generate encrypted message';
        } catch (Throwable $e) {
            return 'Failed to generate encrypted message: ' . $e->get_message();
        }
    }
}