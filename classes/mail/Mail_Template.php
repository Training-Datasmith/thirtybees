<?php

declare (strict_types=1);
namespace Thirtybees\Core\Mail;

interface Mail_Template
{
    /**
     * Returns name of template
     */
    public function get_template_name(): string;
    /**
     * Returns template content type
     */
    public function get_content_type(): string;
    /**
     * Returns template content
     */
    public function get_template(): string;
    /**
     * Renders mail content from parameters
     *
     * @param array $parameters template paramters
     */
    public function render_template(array $parameters): string;
}