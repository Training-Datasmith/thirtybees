<?php

declare (strict_types=1);
namespace Thirtybees\Core\Mail;

use Tools;
class Mail_Address_Core
{
    /**
     * @var string
     */
    protected $address;
    public function __construct(string $address, protected ?string $name)
    {
        $this->address = Tools::convert_email_to_idn($address);
    }
    public function get_address(): string
    {
        return $this->address;
    }
    public function get_name(): ?string
    {
        return $this->name;
    }
}