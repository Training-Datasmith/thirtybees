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
use Thirtybees\Core\Error\Error_Utils;
/**
 * Class DebugErrorPageCore
 */
class Debug_Error_Page_Core extends Abstract_Error_Page
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
        return static::display_error_template(_PS_ROOT_DIR_ . '/error500_debug.phtml', ['errorDescription' => $error_description, 'helper' => $this]);
    }
    /**
     * Helper function to render file lines
     *
     * @param array $lines array of file lines
     * @return string output
     */
    public function display_lines($lines)
    {
        $ret = '';
        if ($lines) {
            $ret = '<pre>';
            foreach ($lines as $current_line) {
                if ($current_line['highlighted']) {
                    $ret .= "<span class='selected'>";
                }
                $ret .= "<span class='line'>" . $current_line['number'] . ':</span>' . htmlentities((string) $current_line['line']);
                if ($current_line['highlighted']) {
                    $ret .= '</span>';
                }
            }
            $ret .= '</pre>';
        }
        return $ret;
    }
    /**
     * Helper function to escape input
     *
     * @param string|null $input
     * @return string
     */
    public function display_string($input)
    {
        if (is_null($input)) {
            return 'NULL';
        }
        if (is_string($input) && $input) {
            $value = html_entity_decode($input);
            return htmlentities($value);
        }
        return (string) $input;
    }
    /**
     * @param string|null $filePath
     *
     * @return string
     */
    public function display_file_path($file_path)
    {
        if ($file_path) {
            $file_path = Error_Utils::get_relative_file($file_path);
        }
        return $this->display_string($file_path);
    }
}