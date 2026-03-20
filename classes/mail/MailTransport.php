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
namespace Thirtybees\Core\Mail;

use Throwable;
/**
 * Interface MailTransport
 */
interface Mail_Transport
{
    public function get_name(): string;
    public function get_description(): string;
    /**
     * @return string|null
     */
    public function get_config_url();
    /**
     * @param MailAddress[] $toAddresses
     * @param MailAddress[] $bccAddresses
     * @param MailTemplate[] $templates ,
     * @param MailAttachement[] $attachements
     *
     *
     * @throws Throwable
     */
    public function send_mail(int $id_shop, int $id_lang, Mail_Address $from_address, array $to_addresses, array $bcc_addresses, Mail_Address $reply_to, string $subject, array $templates, array $template_vars, array $attachements): bool;
}