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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
/**
 * Class Core_Business_CMS_CMSRepository
 */
class Core_business_cms_cms_Repository extends Core_foundation_database_entity_Repository
{
    /**
     * Return all CMSRepositories depending on $id_lang/$id_shop tuple
     *
     * @param int $idLang
     * @param int $idShop
     *
     * @return array|null
     */
    public function i10n_find_all($id_lang, $id_shop)
    {
        $sql = '
			SELECT *
			FROM `' . $this->get_table_name_with_prefix() . '` c
			JOIN `' . $this->get_prefix() . 'cms_lang` cl ON c.`id_cms`= cl.`id_cms`
			WHERE cl.`id_lang` = ' . (int) $id_lang . '
			AND cl.`id_shop` = ' . (int) $id_shop . '

		';
        return $this->hydrate_many($this->db->select($sql));
    }
    /**
     * Return all CMSRepositories depending on $id_lang/$id_shop tuple
     *
     * @param int $idCms
     * @param int $idLang
     * @param int $idShop
     *
     * @return CMS|null
     * @throws Core_Foundation_Database_Exception
     */
    public function i10n_find_one_by_id($id_cms, $id_lang, $id_shop)
    {
        $sql = '
			SELECT *
			FROM `' . $this->get_table_name_with_prefix() . '` c
			JOIN `' . $this->get_prefix() . 'cms_lang` cl ON c.`id_cms`= cl.`id_cms`
			WHERE c.`id_cms` = ' . (int) $id_cms . '
			AND cl.`id_lang` = ' . (int) $id_lang . '
			AND cl.`id_shop` = ' . (int) $id_shop . '
			LIMIT 0 , 1
		';
        return $this->hydrate_one($this->db->select($sql));
    }
    /**
     * Return CMSRepository lang associative table name
     *
     * @return string
     */
    protected function get_language_table_name_with_prefix()
    {
        return $this->get_table_name_with_prefix() . '_lang';
    }
}