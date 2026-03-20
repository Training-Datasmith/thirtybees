<?php

declare (strict_types=1);
namespace Thirtybees\Core\Mail;

class Mail_Attachement_Core
{
    public function __construct(protected string $content, protected string $name, protected string $mime)
    {
    }
    public function get_content(): string
    {
        return $this->content;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function get_mime(): string
    {
        return $this->mime;
    }
}