<?php

declare (strict_types=1);
namespace Thirtybees\Core\Mail\Template;

use Mail;
use Presta_Shop_Exception;
use Thirtybees\Core\Mail\Mail_Template;
class Simple_Mail_Template_Core implements Mail_Template
{
    public function __construct(protected string $template_name, protected string $content_type, protected string $template)
    {
    }
    public function get_template_name(): string
    {
        return $this->template_name;
    }
    public function get_content_type(): string
    {
        return $this->content_type;
    }
    public function get_template(): string
    {
        return $this->template;
    }
    /**
     * @throws PrestaShopException
     */
    public function render_template(array $parameters): string
    {
        return Mail::substitute_template_vars($this->get_template(), $parameters);
    }
}