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
namespace Thirtybees\Core\Mail\Transport;

use Thirtybees\Core\Mail\Mail_Address;
use Thirtybees\Core\Mail\Mail_Attachement;
use Thirtybees\Core\Mail\Mail_Template;
use Thirtybees\Core\Mail\Mail_Transport;
use Translate;
/**
 * Class EMailTransportNoneCore
 */
class Mail_Transport_None_Core implements Mail_Transport
{
    public function get_name(): string
    {
        return Translate::get_admin_translation('None', 'Mail');
    }
    public function get_config_url(): null
    {
        return null;
    }
    public function get_description(): string
    {
        return Translate::get_admin_translation('Never send emails (may be useful for testing purposes)', 'Mail');
    }
    /**
     * @param MailAddress[] $toAddresses
     * @param MailAddress[] $bccAddresses
     * @param MailTemplate[] $templates
     * @param MailAttachement[] $attachements
     *
     */
    public function send_mail(int $id_shop, int $id_lang, Mail_Address $from_address, array $to_addresses, array $bcc_addresses, Mail_Address $reply_to, string $subject, array $templates, array $template_vars, array $attachements): bool
    {
        return true;
    }
}