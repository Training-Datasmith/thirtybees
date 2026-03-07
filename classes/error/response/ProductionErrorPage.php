<?php

declare(strict_types=1);
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
use Thirtybees\Core\Error\ErrorDescription;
use Throwable;

/**
 * Class DebugErrorPageCore
 */
class ProductionErrorPageCore extends AbstractErrorPage
{
    /**
     * Return content type
     * @return string
     */
    protected function getContentType()
    {
        return 'text/html';
    }

    /**
     * @return string
     */
    protected function renderError(ErrorDescription $errorDescription)
    {
        return static::displayErrorTemplate(
            _PS_ROOT_DIR_.'/error500.phtml',
            [
                'shopEmail' => $this->getShopEmail(),
                'encrypted' => $this->getEncryptedMessage($errorDescription),
            ]
        );
    }

    protected function getShopEmail(): string
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
    private function getEncryptedMessage(ErrorDescription $errorDescription)
    {
        try {
            $msg = $errorDescription->encrypt();
            if ($msg) {
                return $msg;
            }
            return 'Failed to generate encrypted message';
        } catch (Throwable $e) {
            return 'Failed to generate encrypted message: ' . $e->getMessage();
        }
    }
}
