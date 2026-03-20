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
use Throwable;
/**
 * Class AbstractErrorPageCore
 */
abstract class Abstract_Error_Page_Core implements Error_Response_Interface
{
    /**
     * @var string | null
     */
    private $content_type;
    public function send_response(Error_Description $error_description): void
    {
        // get error page content
        $content = $this->render_error($error_description);
        // output content
        $this->before_render($error_description);
        if (!headers_sent()) {
            if (!$this->content_type) {
                $this->content_type = $this->get_content_type();
            }
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: ' . $this->content_type);
        }
        //clean any output buffer there might be
        while (ob_get_level()) {
            ob_end_clean();
        }
        // render error page content
        echo $content;
        $this->after_render($error_description);
        exit;
    }
    /**
     * @return string
     */
    public function get_page_content(Error_Description $error_description)
    {
        try {
            return $this->render_error($error_description);
        } catch (Throwable $t) {
            // It's very unlikely that exception will be thrown during error message rendering. If that happen,
            // simply give up
            $this->content_type = 'text/plain';
            if (_PS_MODE_DEV_) {
                $message = "Failed to display exception:\n";
                $message .= $error_description->get_message();
                $message .= "\n\nFailure reason:\n";
                return $message . $t;
            }
            return 'Fatal error';
        }
    }
    /**
     * Display a phtml template file
     *
     * @param string $file
     * @param array $params
     *
     * @return string Content
     */
    protected function display_error_template($file, $params)
    {
        foreach ($params as $name => $param) {
            ${$name} = $param;
        }
        ob_start();
        include $file;
        $content = ob_get_contents();
        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }
        return $content;
    }
    /**
     * Called at the start of error page rendering, before content is sent to client.
     * Subclasses can use it to add its own content to server response
     *
     * @return void
     */
    protected function before_render(Error_Description $error_description)
    {
        // noop
    }
    /**
     * Called at the end of error page rendering, after content was send to client.
     * Subclasses can use this to implement various logging, cleanup, etc.
     *
     * @return void
     */
    protected function after_render(Error_Description $error_description)
    {
        // noop
    }
    /**
     * @return string
     */
    abstract protected function get_content_type();
    /**
     * @return string
     */
    abstract protected function render_error(Error_Description $error_description);
}