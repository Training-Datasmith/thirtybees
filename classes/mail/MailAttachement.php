<?php

declare(strict_types=1);

namespace Thirtybees\Core\Mail;

class MailAttachementCore
{
    public function __construct(protected string $content, protected string $name, protected string $mime)
    {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMime(): string
    {
        return $this->mime;
    }
}
