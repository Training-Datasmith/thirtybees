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
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
use Thirtybees\Core\Error\Error_Description;
use Thirtybees\Core\Error\Response\Error_Response_Interface;
class Webservice_Fatal_Error_Response_Core implements Error_Response_Interface
{
    /**
     * @var WebserviceOutputBuilder
     */
    protected $output_builder;
    /**
     * @var WebserviceLogger
     */
    protected $logger;
    public function __construct(Webservice_Output_Builder $output_builder, Webservice_Logger $logger, protected bool $send_error_message, protected float $start_time)
    {
        $this->output_builder = $output_builder;
        $this->logger = $logger;
    }
    /**
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    public function send_response(Error_Description $error_description): void
    {
        $time = round(microtime(true) - $this->start_time, 3);
        $this->output_builder->set_status(500);
        $this->output_builder->set_header_params('Execution-Time', $time);
        foreach ($this->output_builder->build_header() as $header) {
            header($header);
        }
        //clean any output buffer there might be
        while (ob_get_level()) {
            ob_end_clean();
        }
        $extra = [];
        if ($this->send_error_message) {
            $message = $error_description->get_extended_message();
            foreach ($error_description->get_extra_sections() as $section) {
                $extra[$section['label']] = $section['content'];
            }
            $extra['stacktrace'] = $error_description->get_trace_as_string();
        } else {
            $message = Tools::display_error('Internal server error');
            $extra['notice'] = Tools::display_error('You can decrypt error message in the back office');
            $extra['encrypted_error'] = $error_description->encrypt();
        }
        $content = $this->output_builder->get_errors([[2, $message, $extra]]);
        echo $content;
        // log error
        $this->logger->log_response('', [[2, $error_description->get_extended_message()]], $time);
        exit;
    }
}