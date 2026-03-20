<?php

declare (strict_types=1);
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
use Thirtybees\Core\Dependency_Injection\Service_Locator;
use Thirtybees\Core\Error\Error_Utils;
use Thirtybees\Core\Mail\Mail_Address;
use Thirtybees\Core\Mail\Mail_Attachement;
use Thirtybees\Core\Mail\Mail_Template;
use Thirtybees\Core\Mail\Mail_Transport;
use Thirtybees\Core\Mail\Template\Simple_Mail_Template;
use Thirtybees\Core\Mail\Transport\Mail_Transport_None;
/**
 * Class MailCore
 */
class Mail_Core extends Object_Model
{
    public const TYPE_HTML = 1;
    public const TYPE_TEXT = 2;
    public const TYPE_BOTH = 3;
    public const TRANSPORT_NONE = 'core:none';
    public const RECIPIENT_TYPE_TO = 'to';
    public const RECIPIENT_TYPE_BCC = 'bcc';
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'mail', 'primary' => 'id_mail', 'fields' => ['recipient_type' => ['type' => self::TYPE_STRING, 'copy_post' => false, 'required' => true, 'values' => [self::RECIPIENT_TYPE_TO, self::RECIPIENT_TYPE_BCC], 'dbDefault' => self::RECIPIENT_TYPE_TO], 'recipient' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'copy_post' => false, 'required' => true, 'size' => 126], 'from' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'copy_post' => false, 'required' => true, 'size' => 126], 'template' => ['type' => self::TYPE_STRING, 'validate' => 'isTplName', 'copy_post' => false, 'required' => true, 'size' => 62], 'subject' => ['type' => self::TYPE_STRING, 'validate' => 'isMailSubject', 'copy_post' => false, 'required' => true, 'size' => 254], 'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false, 'required' => true], 'transport' => ['type' => self::TYPE_STRING, 'copy_post' => false, 'required' => false, 'size' => 100], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false, 'required' => true, 'dbType' => 'timestamp', 'dbDefault' => Object_Model::DEFAULT_CURRENT_TIMESTAMP]], 'keys' => ['mail' => ['recipient' => ['type' => Object_Model::KEY, 'columns' => ['recipient'], 'subParts' => [10]]]]];
    /**
     * @var string Recipient type
     */
    public $recipient_type = self::RECIPIENT_TYPE_TO;
    /**
     * @var string Recipient
     */
    public $recipient;
    /**
     * @var string From
     */
    public $from;
    /**
     * @var string Template
     */
    public $template;
    /**
     * @var string Subject
     */
    public $subject;
    /**
     * @var int Language ID
     */
    public $id_lang;
    /**
     * @var string
     */
    public $transport;
    /**
     * @var string Timestamp
     */
    public $date_add;
    /**
     * Send Email
     *
     * @param int $idLang Language ID of the email (to translate the template)
     * @param string $template Template: the name of template not be a var but a string !
     * @param string $subject Subject of the email
     * @param array $templateVars Template variables for the email
     * @param string|string[] $to To email
     * @param string|string[] $toName To name
     * @param string $from From email
     * @param string $fromName To email
     * @param array $fileAttachment Array with three parameters (content, mime and name). You can use an array of array to attach multiple files
     * @param bool $modeSmtp SMTP mode (deprecated)
     * @param string $templatePath Template path
     * @param bool $die Die after error
     * @param int $idShop Shop ID
     * @param string|string[]|null $bcc Bcc recipient (email address)
     * @param string $replyTo Email address for setting the Reply-To header
     *
     * @return bool Whether sending was successful
     *
     * @throws PrestaShopException
     */
    public static function Send($id_lang, $template, $subject, $template_vars, $to, $to_name = null, $from = null, $from_name = null, $file_attachment = null, $mode_smtp = null, $template_path = _PS_MAIL_DIR_, $die = false, $id_shop = null, $bcc = null, $reply_to = null)
    {
        try {
            // allow hooks to modify input parameters
            $result = Hook::get_responses('actionEmailSendBefore', ['idLang' => &$id_lang, 'template' => &$template, 'subject' => &$subject, 'templateVars' => &$template_vars, 'to' => &$to, 'toName' => &$to_name, 'from' => &$from, 'fromName' => &$from_name, 'fileAttachment' => &$file_attachment, 'modeSmtp' => &$mode_smtp, 'templatePath' => &$template_path, 'die' => &$die, 'idShop' => &$id_shop, 'bcc' => &$bcc, 'replyTo' => &$reply_to]);
            // do NOT continue if any module returned false
            if (in_array(false, $result, true)) {
                return true;
            }
            $id_lang = (int) $id_lang;
            // Resolve shop context
            if (!$id_shop) {
                $shop = Context::get_context()->shop;
                $id_shop = (int) $shop->id;
            } else {
                $id_shop = (int) $id_shop;
                $shop = new Shop($id_shop);
            }
            // resolve addresses
            $from_address = static::get_from_email_address($from, $from_name, $id_shop);
            $to_addresses = static::get_to_email_addresses($to, $to_name);
            $bcc_addresses = static::get_bcc_email_addresses($bcc, $id_shop);
            $reply_to = static::get_reply_to($reply_to, $from_address);
            // resolve template content
            $templates = static::get_mail_templates($template, $template_path, $shop, $id_lang);
            // resolve template variables
            $template_vars = static::get_template_vars($template, $template_vars, $id_shop, $id_lang);
            // resolve subject
            $subject = static::format_subject($subject, $id_shop, $template_vars);
            $attachements = static::get_file_attachements($file_attachment);
            // get email transport
            $transport_id = static::get_selected_transport();
            $transport = static::get_transport($transport_id);
            // send email via transport
            $success = $transport->send_mail($id_shop, $id_lang, $from_address, $to_addresses, $bcc_addresses, $reply_to, $subject, $templates, $template_vars, $attachements);
            if ($success && Configuration::get(Configuration::LOG_EMAILS)) {
                foreach ($to_addresses as $address) {
                    static::log_mail($from_address, static::RECIPIENT_TYPE_TO, $address, $template, $subject, $id_lang, $transport_id);
                }
                foreach ($bcc_addresses as $address) {
                    static::log_mail($from_address, static::RECIPIENT_TYPE_BCC, $address, $template, $subject, $id_lang, $transport_id);
                }
            }
            return $success;
        } catch (Throwable $e) {
            return static::handle_error($die, $e);
        }
    }
    /**
     * Returns from email address
     *
     * @param string|null $from
     * @param string|null $fromName
     * @param int $idShop
     *
     *
     * @throws PrestaShopException
     */
    protected static function get_from_email_address($from, $from_name, $id_shop): Mail_Address
    {
        if (!Validate::is_email($from)) {
            $from = Configuration::get(Configuration::SHOP_EMAIL, null, null, $id_shop);
        }
        if (!isset($from_name) || !Validate::is_mail_name($from_name)) {
            $from_name = Configuration::get(Configuration::SHOP_NAME, null, null, $id_shop);
        }
        return new Mail_Address($from, $from_name);
    }
    /**
     * Resolve primary recipient addresses
     *
     * @param string|string[] $to
     * @param string|string[]|null $toName
     *
     * @return MailAddress[]
     *
     * @throws PrestaShopException
     */
    protected static function get_to_email_addresses($to, $to_name)
    {
        $result = [];
        $to = static::to_string_array($to);
        if (!$to) {
            throw new Presta_Shop_Exception(Tools::display_error('Parameter "to" not provided'));
        }
        $to_name = static::to_string_array($to_name);
        foreach ($to as $key => $address) {
            if (Validate::is_email($address)) {
                $name = $to_name[$key] ?? null;
                $result[] = new Mail_Address($address, $name);
            } else {
                throw new Presta_Shop_Exception(Tools::display_error('Parameter "to" is corrupted'));
            }
        }
        return $result;
    }
    /**
     * @param string|string[]|null $input
     *
     * @return string[]
     */
    private static function to_string_array($input): array
    {
        if (is_null($input)) {
            return [];
        }
        if (is_string($input)) {
            return [$input];
        }
        if (is_array($input)) {
            return $input;
        }
        throw new RuntimeException('Invalid string array input');
    }
    /**
     * Resolve BCC email addresses
     *
     * @param string|string[]|null $bcc
     * @param int $idShop
     *
     * @return MailAddress[]
     *
     * @throws PrestaShopException
     */
    protected static function get_bcc_email_addresses($bcc, $id_shop)
    {
        $addresses = [];
        $bcc = static::to_string_array($bcc);
        foreach ($bcc as $address) {
            if (Validate::is_email($address)) {
                $addresses[] = $address;
            } else {
                throw new Presta_Shop_Exception(Tools::display_error('Parameter "bcc" is corrupted'));
            }
        }
        // Check if there is any configuration for emails to add as BCC to all outgoing emails
        $bcc_all_mails_to = Configuration::get('TB_BCC_ALL_MAILS_TO', null, null, $id_shop);
        if (!empty($bcc_all_mails_to)) {
            $bcc_all_mails_to = explode(';', $bcc_all_mails_to);
            foreach ($bcc_all_mails_to as $address) {
                if (Validate::is_email($address)) {
                    $addresses[] = $address;
                }
            }
        }
        return array_map(fn(string $address) => new Mail_Address($address, null), array_unique($addresses));
    }
    /**
     * Resolves reply-to address
     *
     * @param string|null $replyTo
     *
     * @return MailAddress
     */
    protected static function get_reply_to($reply_to, Mail_Address $from_address)
    {
        if (Validate::is_email($reply_to)) {
            return new Mail_Address($reply_to, null);
        }
        return $from_address;
    }
    /**
     * Resolve template content
     *
     * @param string $template
     * @param Shop $shop
     * @param int $idLang
     *
     * @return MailTemplate[]
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function get_mail_templates($template, string $template_path, $shop, $id_lang): array
    {
        if (!Validate::is_tpl_name($template)) {
            throw new Presta_Shop_Exception(Tools::display_error('invalid e-mail template'));
        }
        $iso = Language::get_iso_by_id((int) $id_lang);
        if (!$iso) {
            throw new Presta_Shop_Exception(Tools::display_error('No ISO code for email'));
        }
        $mail_type = (int) Configuration::get(Configuration::MAIL_TYPE, null, null, $shop->id);
        if (!in_array($mail_type, [static::TYPE_BOTH, static::TYPE_TEXT, static::TYPE_HTML])) {
            $mail_type = static::TYPE_BOTH;
        }
        $send_txt_content = $mail_type === static::TYPE_BOTH || $mail_type === static::TYPE_TEXT;
        $send_html_content = $mail_type === static::TYPE_BOTH || $mail_type === static::TYPE_HTML;
        $template_html = '';
        $template_txt = '';
        Hook::trigger_event('actionEmailAddBeforeContent', ['template' => $template, 'template_html' => &$template_html, 'template_txt' => &$template_txt, 'id_lang' => (int) $id_lang]);
        // load html template content
        if ($send_html_content) {
            $file_path = static::get_template_path($template, '.html', $iso, $shop, $template_path);
            if ($file_path) {
                $template_html .= file_get_contents($file_path);
            }
        }
        // load txt template content
        if ($send_txt_content) {
            $file_path = static::get_template_path($template, '.txt', $iso, $shop, $template_path);
            if ($file_path) {
                $template_txt .= strip_tags(html_entity_decode(file_get_contents($file_path), ENT_NOQUOTES, 'utf-8'));
            }
        }
        Hook::trigger_event('actionEmailAddAfterContent', ['template' => $template, 'template_html' => &$template_html, 'template_txt' => &$template_txt, 'id_lang' => (int) $id_lang]);
        $templates = [];
        if ($template_html) {
            $templates[] = new Simple_Mail_Template($template, 'text/html', $template_html);
        }
        if ($template_txt) {
            $templates[] = new Simple_Mail_Template($template, 'text/plain', $template_txt);
        }
        if (!$templates) {
            throw new Presta_Shop_Exception(sprintf("No templates found for email '%s' in language '%s'", $template, $iso));
        }
        return $templates;
    }
    /**
     * This method finds file path for email template in given language. If template does not exists, it fallbacks
     * to english version. Returns null, if no email template can be used
     *
     * @param string $template template name
     * @param string $suffix template suffix, either .txt or .html
     * @param string $iso language iso code
     * @param Shop $shop shop for which we are sending email
     * @param string $baseTemplatePath base template path
     *
     * @return string | null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function get_template_path($template, $suffix, $iso, Shop $shop, $base_template_path)
    {
        $relative_path = $iso . '/' . $template . $suffix;
        $theme_path = _PS_ALL_THEMES_DIR_ . $shop->get_theme() . '/';
        // create candidate file paths list
        $paths = [];
        $module_name = static::get_module_name($base_template_path, $shop);
        if ($module_name) {
            $paths[] = $theme_path . 'modules/' . $module_name . '/mails/' . $relative_path;
        }
        $paths[] = $theme_path . 'mails/' . $relative_path;
        $paths[] = $base_template_path . $relative_path;
        $paths[] = _PS_MAIL_DIR_ . $relative_path;
        $paths = array_unique($paths);
        // return first template file in paths
        foreach ($paths as $path) {
            if (file_exists($path) && filesize($path)) {
                return $path;
            }
        }
        // email template was not found, log missing template
        static::log_missing_template($template, $suffix, $iso, $paths);
        // If template wasn't found, let's try to fallback to english template
        if ($iso !== 'en') {
            return static::get_template_path($template, $suffix, 'en', $shop, $base_template_path);
        }
        return null;
    }
    /**
     * Derives module name from template path
     *
     * @param string $baseTemplatePath
     *
     */
    private static function get_module_name($base_template_path, Shop $shop): ?string
    {
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $base_template_path);
        $res = [];
        if (preg_match('#' . $shop->physical_uri . 'modules/#', $path) && preg_match('#modules/([a-z0-9_-]+)/#ui', $path, $res)) {
            return $res[1];
        }
        return null;
    }
    /**
     * Logs information about missing email template to system log
     *
     * @param string $template template name
     * @param string $suffix template suffix
     * @param string $iso language iso code
     * @param string[] $paths searched paths
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private static function log_missing_template(string $template, string $suffix, $iso, array $paths): void
    {
        $filename = $template . $suffix;
        $local_paths = array_map(fn(string $path) => str_replace(_PS_ROOT_DIR_, '', $path), $paths);
        Logger::add_log(sprintf('Email template %s for language %s not found in [%s]', $filename, $iso, implode(', ', $local_paths)), 3);
    }
    /**
     * Resolve template variables
     *
     * @param string $template
     * @param array|null $templateVars
     * @param int $idShop
     * @param int $idLang
     *
     * @return array
     * @throws PrestaShopException
     */
    protected static function get_template_vars($template, $template_vars, $id_shop, $id_lang)
    {
        $link = Context::get_context()->link;
        if (!is_array($template_vars)) {
            $template_vars = [];
        } else {
            $template_vars = array_map(['Tools', 'htmlentitiesDecodeUTF8'], $template_vars);
        }
        $template_vars['{shop_logo}'] = ['type' => 'imageFile', 'filepath' => static::get_logo_file_path($id_shop)];
        $template_vars['{shop_name}'] = Tools::safe_output(Configuration::get('PS_SHOP_NAME', null, null, $id_shop));
        $template_vars['{shop_url}'] = $link->get_page_link('index', true, $id_lang, null, false, $id_shop);
        $template_vars['{my_account_url}'] = $link->get_page_link('my-account', true, $id_lang, null, false, $id_shop);
        $template_vars['{guest_tracking_url}'] = $link->get_page_link('guest-tracking', true, $id_lang, null, false, $id_shop);
        $template_vars['{history_url}'] = $link->get_page_link('history', true, $id_lang, null, false, $id_shop);
        $template_vars['{color}'] = Tools::safe_output(Configuration::get('PS_MAIL_COLOR', null, null, $id_shop));
        // Get extra template_vars
        $extra_template_vars = [];
        Hook::trigger_event('actionGetExtraMailTemplateVars', ['template' => $template, 'template_vars' => $template_vars, 'extra_template_vars' => &$extra_template_vars, 'id_lang' => (int) $id_lang]);
        return array_merge($template_vars, $extra_template_vars);
    }
    /**
     * Returns path to logo file
     *
     * @param int $idShop
     *
     * @return string
     * @throws PrestaShopException
     */
    protected static function get_logo_file_path($id_shop)
    {
        // return first logo
        foreach (['PS_LOGO_MAIL', 'PS_LOGO'] as $config_key) {
            $logo = Configuration::get($config_key, null, null, $id_shop);
            if ($logo && file_exists(_PS_IMG_DIR_ . $logo)) {
                return _PS_IMG_DIR_ . $logo;
            }
        }
        // logo not found
        return '';
    }
    /**
     * Format email subject using email subject template
     *
     * @param string $subject email subject
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected static function format_subject($subject, int $id_shop, array $template_vars)
    {
        if (!Validate::is_mail_subject($subject)) {
            throw new Presta_Shop_Exception(Tools::display_error('Error: invalid e-mail subject'));
        }
        // replace template vars inside subject
        $subject = static::substitute_template_vars($subject, $template_vars);
        $template = Configuration::get('TB_MAIL_SUBJECT_TEMPLATE', null, null, $id_shop);
        if (!$template || !str_contains($template, '{subject}')) {
            $template = '[{shop_name}] {subject}';
        }
        if (preg_match_all('#\{[a-z0-9_]+\}#i', $template, $m)) {
            for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
                $key = $m[0][$i];
                switch ($key) {
                    case '{shop_name}':
                        $template = str_replace($key, Configuration::get('PS_SHOP_NAME', null, null, $id_shop), $template);
                        break;
                    case '{subject}':
                        $template = str_replace($key, $subject, $template);
                        break;
                }
            }
        }
        return $template;
    }
    /**
     *
     *
     * @throws PrestaShopException
     */
    public static function get_transport(string $trasport_id): Mail_Transport
    {
        $transports = static::get_available_transports();
        if (isset($transports[$trasport_id])) {
            return $transports[$trasport_id];
        }
        throw new Presta_Shop_Exception("Mail transport {$trasport_id} not found");
    }
    /**
     * Returns string identifier of selected email transport
     *
     *
     * @throws PrestaShopException
     */
    public static function get_selected_transport(): string
    {
        $selected = (string) Configuration::get(Configuration::MAIL_TRANSPORT);
        if ($selected) {
            $transports = static::get_available_transports();
            if (isset($transports[$selected])) {
                return $selected;
            }
            trigger_error("Mail transport '{$selected}' not found", E_USER_WARNING);
        }
        return static::TRANSPORT_NONE;
    }
    /**
     * @return MailTransport[]
     *
     * @throws PrestaShopException
     */
    public static function get_available_transports()
    {
        $transports = null;
        if (is_null($transports)) {
            $transports = [static::TRANSPORT_NONE => new Mail_Transport_None()];
            $res = Hook::get_responses('actionRegisterMailTransport');
            foreach ($res as $mod => $mod_transports) {
                if (!is_array($mod_transports)) {
                    $mod_transports = ['default' => $mod_transports];
                }
                foreach ($mod_transports as $transport_id => $transport) {
                    if ($transport instanceof Mail_Transport) {
                        $key = $mod . ':' . $transport_id;
                        $transports[$key] = $transport;
                    } else {
                        trigger_error("Module {$mod} returned invalid mail transport: {$transport_id}", E_USER_WARNING);
                    }
                }
            }
        }
        return $transports;
    }
    /**
     * @param int $idMail Mail ID
     *
     * @return bool Whether removal succeeded
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function erase_log($id_mail)
    {
        return Db::get_instance()->delete('mail', 'id_mail = ' . (int) $id_mail);
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function erase_all_logs()
    {
        return Db::get_instance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . 'mail');
    }
    /**
     * This method is used to get the translation for email Object.
     * For an object is forbidden to use htmlentities,
     * we have to return a sentence with accents.
     *
     * @param string $string raw sentence (write directly in file)
     * @param int|null $idLang
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function l($string, $id_lang = null, ?Context $context = null)
    {
        global $_LANGMAIL;
        if (!$context) {
            $context = Context::get_context();
        }
        if ($id_lang == null) {
            $id_lang = !isset($context->language) || !is_object($context->language) ? (int) Configuration::get('PS_LANG_DEFAULT') : (int) $context->language->id;
        }
        $iso_code = Language::get_iso_by_id((int) $id_lang);
        $file_core = _PS_ROOT_DIR_ . '/mails/' . $iso_code . '/lang.php';
        if (file_exists($file_core) && empty($_LANGMAIL)) {
            include $file_core;
        }
        $file_theme = _PS_THEME_DIR_ . 'mails/' . $iso_code . '/lang.php';
        if (file_exists($file_theme)) {
            include $file_theme;
        }
        if (!is_array($_LANGMAIL)) {
            return str_replace('"', '&quot;', $string);
        }
        $key = str_replace('\'', '\\\'', $string);
        return str_replace('"', '&quot;', array_key_exists($key, $_LANGMAIL) && !empty($_LANGMAIL[$key]) ? $_LANGMAIL[$key] : $string);
    }
    /**
     *
     * @throws PrestaShopException
     */
    protected static function log_mail(Mail_Address $from_address, string $recipient_type, Mail_Address $recipient, string $template, string $subject, int $id_lang, string $transport_id)
    {
        $mail = new static();
        $mail->recipient_type = $recipient_type;
        $mail->recipient = mb_substr($recipient->get_address(), 0, 126);
        $mail->from = mb_substr($from_address->get_address(), 0, 126);
        $mail->template = mb_substr($template, 0, 62);
        $mail->subject = mb_substr($subject, 0, 254);
        $mail->id_lang = $id_lang;
        $mail->transport = $transport_id;
        $mail->add();
    }
    /**
     * @param array|MailAttachement $input
     *
     * @return MailAttachement[]
     */
    protected static function get_file_attachements($input)
    {
        $attachments = [];
        if ($input instanceof Mail_Attachement) {
            $input = [$input];
        }
        if (is_array($input)) {
            if (isset($input['content'])) {
                $input = [$input];
            }
            foreach ($input as $attachment) {
                if ($attachment instanceof Mail_Attachement) {
                    $attachments[] = $attachment;
                } elseif (isset($attachment['content']) && isset($attachment['name']) && isset($attachment['mime'])) {
                    $attachments[] = new Mail_Attachement($attachment['content'], $attachment['name'], $attachment['mime']);
                } else {
                    trigger_error('Warning: invalid file attachement: ' . json_encode($attachment), E_USER_WARNING);
                }
            }
        }
        return $attachments;
    }
    /**
     * @param bool $die
     *
     * @return false
     *
     * @throws PrestaShopException
     *
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected static function handle_error($die, Throwable $e)
    {
        $message = 'Send Email Error: ' . $e->get_message();
        Logger::add_log($message, 3, null, Logger::MAIL_ERROR, 0, true);
        if ($die) {
            if ($e instanceof Presta_Shop_Exception) {
                throw $e;
            }
            throw new Presta_Shop_Exception('Failed to send email', 0, $e);
        }
        $error_handler = Service_Locator::get_instance()->get_error_handler();
        $error_handler->log_fatal_error(Error_Utils::describe_exception($e));
        return false;
    }
    /**
     *
     * @throws PrestaShopException
     */
    public static function substitute_template_vars(string $content, array $template_vars): string
    {
        // convert iamgeFile parameters to url. This is used, for example, by {shop_logo} parameter
        $vars = [];
        foreach ($template_vars as $name => $parameter) {
            if (is_array($parameter) && isset($parameter['type']) && $parameter['type'] === 'imageFile') {
                $filepath = $parameter['filepath'] ?? '';
                $filepath = str_replace(_PS_ROOT_DIR_, '', $filepath);
                $vars[$name] = Context::get_context()->link->get_media_link($filepath);
            } else {
                $vars[$name] = $parameter;
            }
        }
        $search = array_keys($vars);
        $replace = array_values($vars);
        return str_replace($search, $replace, $content);
    }
}