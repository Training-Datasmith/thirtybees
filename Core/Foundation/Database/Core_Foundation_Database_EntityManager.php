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
 * Class Core_Foundation_Database_EntityManager
 */
class Core_foundation_database_entity_Manager
{
    /**
     * @var Core_Foundation_Database_DatabaseInterface
     */
    protected $db;
    /**
     * @var Core_Business_ConfigurationInterface
     */
    protected $configuration;
    /**
     * @var array
     */
    protected $entity_meta_data = [];
    /**
     * Core_Foundation_Database_EntityManager constructor.
     *
     * @param Core_Foundation_Database_DatabaseInterface $db
     * @param Core_Business_ConfigurationInterface $configuration
     */
    public function __construct(Core_foundation_database_database_Interface $db, Core_business_configuration_Interface $configuration)
    {
        $this->db = $db;
        $this->configuration = $configuration;
    }
    /**
     * Return current database object used
     *
     * @return Core_Foundation_Database_DatabaseInterface
     */
    public function get_database()
    {
        return $this->db;
    }
    /**
     * Return current repository used
     *
     * @param string $className
     *
     * @return mixed
     *
     * @throws PrestaShopException
     */
    public function get_repository($class_name)
    {
        if (is_callable([$class_name, 'getRepositoryClassName'])) {
            $repository_class = call_user_func([$class_name, 'getRepositoryClassName']);
        } else {
            $repository_class = null;
        }
        if (!$repository_class) {
            $repository_class = 'Core_Foundation_Database_EntityRepository';
        }
        return new $repository_class($this, $this->configuration->get('_DB_PREFIX_'), $this->get_entity_meta_data($class_name));
    }
    /**
     * Return entity's meta data
     *
     * @param string $className
     *
     * @return mixed
     * @throws PrestaShopException
     */
    public function get_entity_meta_data($class_name)
    {
        if (!array_key_exists($class_name, $this->entity_meta_data)) {
            $meta_data_retriever = new Adapter_entity_Meta_Data_Retriever();
            $this->entity_meta_data[$class_name] = $meta_data_retriever->get_entity_meta_data($class_name);
        }
        return $this->entity_meta_data[$class_name];
    }
    /**
     * Flush entity to DB
     *
     * @param Core_Foundation_Database_EntityInterface $entity
     *
     * @return static
     */
    public function save(Core_foundation_database_entity_Interface $entity)
    {
        $entity->save();
        return $this;
    }
    /**
     * DElete entity from DB
     *
     * @param Core_Foundation_Database_EntityInterface $entity
     *
     * @return static
     */
    public function delete(Core_foundation_database_entity_Interface $entity)
    {
        $entity->delete();
        return $this;
    }
}