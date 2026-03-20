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

use Thirtybees\Core\Error\Error_Description;
/**
 * Class JSendErrorResponse
 */
class Cli_Error_Response_Core extends Abstract_Error_Page
{
    /**
     * Return content type
     * @return string
     */
    protected function get_content_type()
    {
        return 'text/plain';
    }
    /**
     * @return string
     */
    protected function render_error(Error_Description $error_description)
    {
        $message = $error_description->get_extended_message() . "\n";
        $message .= "Stacktrace:\n" . $error_description->get_trace_as_string() . "\n";
        $cause = $error_description->get_cause();
        while ($cause) {
            $message .= 'Cause by ' . $cause->get_extended_message() . "\n";
            $cause = $cause->get_cause();
        }
        return $message;
    }
}