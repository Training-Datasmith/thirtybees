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

use Thirtybees\Core\Error\ErrorDescription;
use Tools;

/**
 * Class JSendErrorResponse
 */
class JSendErrorResponseCore extends AbstractErrorPage
{
    public function __construct(protected bool $sendErrorMessage)
    {
    }

    /**
     * Return content type
     * @return string
     */
    protected function getContentType()
    {
        return 'application/json';
    }

    /**
     * @return string
     */
    protected function renderError(ErrorDescription $errorDescription)
    {
        return json_encode([
            'status' => 'error',
            'message' => $this->getResponseMessage($errorDescription),
        ], JSON_PRETTY_PRINT);
    }

    /**
     * @return string
     */
    protected function getResponseMessage(ErrorDescription $errorDescription)
    {
        if ($this->sendErrorMessage) {
            return $errorDescription->getExtendedMessage();
        }
        return Tools::displayError('Internal server error');
    }
}
