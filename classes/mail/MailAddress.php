<?php

declare(strict_types=1);

namespace Thirtybees\Core\Mail;

use Tools;

class MailAddressCore
{
    /**
     * @var string
     */
    protected $address;

    public function __construct(string $address, protected ?string $name)
    {
        $this->address = Tools::convertEmailToIdn($address);
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

}
