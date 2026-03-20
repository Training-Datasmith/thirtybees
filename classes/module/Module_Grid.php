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
 * Class ModuleGridCore
 */
abstract class Module_Grid_Core extends Module
{
    /**
     * @var Employee
     */
    protected $_employee;
    /**
     * @var array of strings graph data
     */
    protected $_values = [];
    /**
     * @var int total number of values *
     */
    protected $_total_count = 0;
    /**
     * @var string graph titles
     */
    protected $_title;
    /**
     * @var int start
     */
    protected $_start;
    /**
     * @var int limit
     */
    protected $_limit;
    /**
     * @var string column name on which to sort
     */
    protected $_sort;
    /**
     * @var string sort direction DESC/ASC
     */
    protected $_direction;
    /**
     * @var ModuleGridEngine grid engine
     */
    protected $_render;
    /**
     * @var string csv content
     */
    protected $_csv = '';
    /**
     * @var int language context
     */
    protected $_id_lang;
    /**
     * @return void
     */
    abstract protected function get_data();
    /**
     * @param int $idEmployee
     *
     * @throws PrestaShopException
     */
    public function set_employee($id_employee): void
    {
        $this->_employee = new Employee((int) $id_employee);
    }
    /**
     * @param int $idLang
     */
    public function set_lang($id_lang): void
    {
        $this->_id_lang = (int) $id_lang;
    }
    /**
     * @param string $render
     * @param string|null $type
     * @param int $width
     * @param int $height
     * @param int $start
     * @param int $limit
     * @param int $sort
     * @param string $dir
     *
     * @throws PrestaShopException
     */
    public function create($render, $type, $width, $height, $start, $limit, $sort, $dir): void
    {
        if (!Validate::is_module_name($render)) {
            throw new Presta_Shop_Exception('Failed to resolve renderer module');
        }
        if (!file_exists($file = _PS_ROOT_DIR_ . '/modules/' . $render . '/' . $render . '.php')) {
            throw new Presta_Shop_Exception('Invalid renderer module: ' . $render);
        }
        require_once $file;
        $this->_render = new $render($type);
        $this->_start = $start;
        $this->_limit = $limit;
        $this->_sort = $sort;
        $this->_direction = $dir;
        $this->get_data();
        $this->_render->set_title($this->_title);
        $this->_render->set_size($width, $height);
        $this->_render->set_values($this->_values);
        $this->_render->set_total_count($this->_total_count);
        $this->_render->set_limit($this->_start, $this->_limit);
    }
    public function render(): void
    {
        $this->_render->render();
    }
    /**
     * @param array $params
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function engine($params)
    {
        if (!$render = Configuration::get('PS_STATS_GRID_RENDER')) {
            return Tools::display_error('No grid engine selected');
        }
        if (!Validate::is_module_name($render)) {
            return Tools::display_error('Invalid grid engine.');
        }
        if (!file_exists(_PS_ROOT_DIR_ . '/modules/' . $render . '/' . $render . '.php')) {
            return Tools::display_error('Grid engine selected is unavailable.');
        }
        $grider = 'grider.php?render=' . $render . '&module=' . Tools::safe_output(Tools::get_value('module'));
        $context = Context::get_context();
        $grider .= '&id_employee=' . (int) $context->employee->id;
        $grider .= '&id_lang=' . (int) $context->language->id;
        if (!isset($params['width']) || !Validate::is_unsigned_int($params['width'])) {
            $params['width'] = 600;
        }
        if (!isset($params['height']) || !Validate::is_unsigned_int($params['height'])) {
            $params['height'] = 920;
        }
        if (!isset($params['start']) || !Validate::is_unsigned_int($params['start'])) {
            $params['start'] = 0;
        }
        if (!isset($params['limit']) || !Validate::is_unsigned_int($params['limit'])) {
            $params['limit'] = 40;
        }
        $grider .= '&width=' . $params['width'];
        $grider .= '&height=' . $params['height'];
        if (Validate::is_unsigned_int($params['start'])) {
            $grider .= '&start=' . $params['start'];
        }
        if (Validate::is_unsigned_int($params['limit'])) {
            $grider .= '&limit=' . $params['limit'];
        }
        if (isset($params['type']) && Validate::is_name($params['type'])) {
            $grider .= '&type=' . $params['type'];
        }
        if (isset($params['option']) && Validate::is_generic_name($params['option'])) {
            $grider .= '&option=' . $params['option'];
        }
        if (isset($params['sort']) && Validate::is_name($params['sort'])) {
            $grider .= '&sort=' . $params['sort'];
        }
        if (isset($params['dir']) && Validate::is_sort_direction($params['dir'])) {
            $grider .= '&dir=' . $params['dir'];
        }
        require_once _PS_ROOT_DIR_ . '/modules/' . $render . '/' . $render . '.php';
        return call_user_func([$render, 'hookGridEngine'], $params, $grider);
    }
    /**
     * @param array $datas
     */
    protected function csv_export($datas)
    {
        $this->_sort = $datas['defaultSortColumn'];
        $this->set_lang(Context::get_context()->language->id);
        $this->get_data();
        $layers = $datas['layers'] ?? 1;
        if (isset($datas['option'])) {
            $this->set_option($datas['option'], $layers);
        }
        if (count($datas['columns'])) {
            foreach ($datas['columns'] as $column) {
                $this->_csv .= $column['header'] . ';';
            }
            $this->_csv = rtrim($this->_csv, ';') . "\n";
            foreach ($this->_values as $value) {
                foreach ($datas['columns'] as $column) {
                    $this->_csv .= $value[$column['dataIndex']] . ';';
                }
                $this->_csv = rtrim($this->_csv, ';') . "\n";
            }
        }
        $this->_display_csv();
    }
    /**
     * @return void
     */
    protected function _display_csv()
    {
        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $this->display_name . ' - ' . time() . '.csv"');
        echo $this->_csv;
        exit;
    }
    /**
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_date()
    {
        return Module_Graph::get_date_between($this->_employee);
    }
    /**
     * @return int
     */
    public function get_lang()
    {
        return (int) $this->_id_lang;
    }
    /**
     * @param mixed $option
     * @param int $layers
     */
    public function set_option($option, $layers = 1)
    {
    }
}