<?php

declare(strict_types=1);

namespace Thirtybees\Core\Mail;

interface MailTemplate
{
    /**
     * Returns name of template
     */
    public function getTemplateName(): string;

    /**
     * Returns template content type
     */
    public function getContentType(): string;

    /**
     * Returns template content
     */
    public function getTemplate(): string;

    /**
     * Renders mail content from parameters
     *
     * @param array $parameters template paramters
     */
    public function renderTemplate(array $parameters): string;
}
