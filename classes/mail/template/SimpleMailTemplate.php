<?php

declare(strict_types=1);

namespace Thirtybees\Core\Mail\Template;

use Mail;
use PrestaShopException;
use Thirtybees\Core\Mail\MailTemplate;

class SimpleMailTemplateCore implements MailTemplate
{
    public function __construct(protected string $templateName, protected string $contentType, protected string $template)
    {
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @throws PrestaShopException
     */
    public function renderTemplate(array $parameters): string
    {
        return Mail::substituteTemplateVars($this->getTemplate(), $parameters);
    }

}
