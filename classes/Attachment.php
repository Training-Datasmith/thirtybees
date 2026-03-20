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
/**
 * Class AttachmentCore
 */
class Attachment_Core extends Object_Model
{
    /** @var string $file */
    public $file;
    /** @var string $file_name */
    public $file_name;
    /** @var int $file_size */
    public $file_size;
    /** @var string|string[] $name */
    public $name;
    /** @var string $mime */
    public $mime;
    /** @var string|string[] $description */
    public $description;
    /** @var int position */
    public $position;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'attachment', 'primary' => 'id_attachment', 'multilang' => true, 'fields' => [
        'file' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 40],
        'file_name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 128, 'dbNullable' => false],
        'file_size' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'bigint(11) unsigned', 'dbDefault' => '0'],
        'mime' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 128],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128, 'dbNullable' => true],
        'description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_TEXT],
    ]];
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        $this->file_size = $this->get_file_size();
        return parent::add($auto_date, $null_values);
    }
    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        $this->file_size = $this->get_file_size();
        return parent::update($null_values);
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if ($this->file_exists()) {
            unlink($this->get_file_path());
        }
        $products = Db::read_only()->get_array((new Db_Query())->select('`id_product`')->from('product_attachment')->where('`id_attachment` = ' . (int) $this->id));
        Db::get_instance()->delete('product_attachment', '`id_attachment` = ' . (int) $this->id);
        foreach ($products as $product) {
            Product::update_cache_attachment((int) $product['id_product']);
        }
        return parent::delete();
    }
    /**
     * @param array $attachments
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_selection($attachments)
    {
        if (empty($attachments)) {
            return true;
        }
        $return = true;
        $attachments_data = Db::read_only()->get_array((new Db_Query())->select('*')->from(bq_sql(Attachment::$definition['table']))->where('`id_attachment` IN (' . implode(',', $attachments) . ')'));
        if (empty($attachments_data)) {
            return true;
        }
        foreach ($attachments_data as $attachment_data) {
            $attachment = new Attachment();
            $attachment->hydrate($attachment_data);
            $return = $attachment->delete() && $return;
        }
        return $return;
    }
    /**
     * @param int $idLang
     * @param int $idProduct
     * @param bool $include
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_attachments($id_lang, $id_product, $include = true)
    {
        return Db::read_only()->get_array('
            SELECT *
            FROM ' . _DB_PREFIX_ . 'attachment a
            LEFT JOIN ' . _DB_PREFIX_ . 'attachment_lang al
                ON (a.id_attachment = al.id_attachment AND al.id_lang = ' . (int) $id_lang . ')
            WHERE a.id_attachment ' . ($include ? 'IN' : 'NOT IN') . ' (
                SELECT pa.id_attachment
                FROM ' . _DB_PREFIX_ . 'product_attachment pa
                WHERE id_product = ' . (int) $id_product . '
            )');
    }
    /**
     * Unassociate $id_product from the current object
     *
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function delete_product_attachments($id_product)
    {
        $res = Db::get_instance()->delete('product_attachment', '`id_product` = ' . (int) $id_product);
        Product::update_cache_attachment((int) $id_product);
        return $res;
    }
    /**
     * associate $id_product to the current object.
     *
     * @param int $idProduct id of the product to associate
     *
     * @return bool true if succed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function attach_product($id_product)
    {
        $res = Db::get_instance()->insert('product_attachment', ['id_attachment' => (int) $this->id, 'id_product' => (int) $id_product]);
        Product::update_cache_attachment((int) $id_product);
        return $res;
    }
    /**
     * Associate an array of id_attachment $array to the product $id_product
     * and remove eventual previous association
     *
     * @param int $idProduct
     * @param array $array
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function attach_to_product($id_product, $array)
    {
        $result1 = Attachment::delete_product_attachments($id_product);
        if (is_array($array)) {
            $ids = [];
            foreach ($array as $id_attachment) {
                if ((int) $id_attachment > 0) {
                    $ids[] = ['id_product' => (int) $id_product, 'id_attachment' => (int) $id_attachment];
                }
            }
            if (!empty($ids)) {
                $result2 = Db::get_instance()->insert('product_attachment', $ids);
            }
        }
        Product::update_cache_attachment((int) $id_product);
        if (is_array($array)) {
            return $result1 && (!isset($result2) || $result2);
        }
        return $result1;
    }
    /**
     * @param int $idLang
     * @param array $list
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_product_attached($id_lang, $list)
    {
        $id_attachments = [];
        if (is_array($list)) {
            foreach ($list as $attachment) {
                $id_attachments[] = $attachment['id_attachment'];
            }
            $tmp = Db::read_only()->get_array((new Db_Query())->select('*')->from('product_attachment', 'pa')->left_join('product_lang', 'pl', 'pa.`id_product` = pl.`id_product`')->where('pa.`id_attachment` IN (' . implode(',', array_map(intval(...), $id_attachments)) . ')')->where('pl.`id_shop` = ' . (int) Context::get_context()->shop->id)->where('pl.`id_lang` = ' . (int) $id_lang));
            $product_attachments = [];
            foreach ($tmp as $t) {
                $product_attachments[$t['id_attachment']][] = $t['name'];
            }
            return $product_attachments;
        }
        return false;
    }
    /**
     * Return a sha1 filename
     *
     * @return string Sha1 unique filename
     */
    public static function get_new_filename()
    {
        do {
            $filename = sha1(microtime());
        } while (file_exists(_PS_DOWNLOAD_DIR_ . $filename));
        return $filename;
    }
    public function get_file_path(): string
    {
        return _PS_DOWNLOAD_DIR_ . basename($this->file);
    }
    public function file_exists(): bool
    {
        return file_exists($this->get_file_path()) && is_file($this->get_file_path());
    }
    protected function get_file_size(): int
    {
        if ($this->file_exists()) {
            return (int) filesize($this->get_file_path());
        }
        return 0;
    }
}