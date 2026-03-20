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
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Encoding;
use Defuse\Crypto\Exception\Bad_Format_Exception;
use Defuse\Crypto\Exception\Environment_Is_Broken_Exception;
use Defuse\Crypto\Key;
/**
 * Class PhpEncryptionCore
 */
class Php_Encryption_Core
{
    /**
     * @var Key
     */
    protected $key;
    /**
     * PhpEncryptionCore constructor.
     *
     * @param string $asciiKey
     *
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     */
    public function __construct($ascii_key)
    {
        $this->key = Key::load_from_ascii_safe_string($ascii_key);
    }
    /**
     * @param string $plaintext
     *
     * @return string Ciphertext
     * @throws EnvironmentIsBrokenException
     */
    public function encrypt($plaintext)
    {
        return Crypto::encrypt($plaintext, $this->key);
    }
    /**
     * @param string $ciphertext
     *
     * @return string|null Plaintext
     */
    public function decrypt($ciphertext)
    {
        if (!is_string($ciphertext)) {
            return null;
        }
        try {
            return Crypto::decrypt($ciphertext, $this->key);
        } catch (Exception) {
            return null;
        }
    }
    /**
     *
     * @return string
     * @throws EnvironmentIsBrokenException
     */
    public static function create_key_from_salt(string $salt)
    {
        $bytes = str_pad('', Key::KEY_BYTE_SIZE, hash('sha256', 'KeyFromSalt' . $salt . __FILE__));
        return Encoding::save_bytes_to_checksummed_ascii_safe_string(Key::KEY_CURRENT_VERSION, $bytes);
    }
}