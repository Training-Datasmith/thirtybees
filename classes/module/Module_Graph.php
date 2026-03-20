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
 * Class ModuleGraphCore
 */
abstract class Module_Graph_Core extends Module
{
    /**
     * @var Employee $_employee
     */
    protected $_employee;
    /**
     * @var int[] graph data
     */
    protected $_values = [];
    /**
     * @var string[] graph legends (X axis)
     */
    protected $_legend = [];
    /**
     * @var string[] graph titles
     */
    protected $_titles = ['main' => null, 'x' => null, 'y' => null];
    /**
     * @var ModuleGraphEngine graph engine
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
     * @param int $layers
     * @param bool $legend
     */
    protected function set_date_graph($layers, $legend = false)
    {
        // Get dates in a manageable format
        $from_array = getdate(strtotime((string) $this->_employee->stats_date_from));
        $to_array = getdate(strtotime((string) $this->_employee->stats_date_to));
        // If the granularity is inferior to 1 day
        if ($this->_employee->stats_date_from == $this->_employee->stats_date_to) {
            if ($legend) {
                for ($i = 0; $i < 24; $i++) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; $j++) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = $i % 2 ? '' : sprintf('%02dh', $i);
                }
            }
            if (is_callable([$this, 'setDayValues'])) {
                $this->set_day_values($layers);
            }
        } elseif (strtotime((string) $this->_employee->stats_date_to) - strtotime((string) $this->_employee->stats_date_from) <= 2678400) {
            // If the granularity is inferior to 1 month
            // @TODO : change to manage 28 to 31 days
            if ($legend) {
                $days = [];
                if ($from_array['mon'] == $to_array['mon']) {
                    for ($i = $from_array['mday']; $i <= $to_array['mday']; ++$i) {
                        $days[] = $i;
                    }
                } else {
                    $imax = date('t', mktime(0, 0, 0, $from_array['mon'], 1, $from_array['year']));
                    for ($i = $from_array['mday']; $i <= $imax; ++$i) {
                        $days[] = $i;
                    }
                    for ($i = 1; $i <= $to_array['mday']; ++$i) {
                        $days[] = $i;
                    }
                }
                foreach ($days as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; $j++) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = $i % 2 ? '' : sprintf('%02d', $i);
                }
            }
            if (is_callable([$this, 'setMonthValues'])) {
                $this->set_month_values($layers);
            }
        } elseif (strtotime('-1 year', strtotime((string) $this->_employee->stats_date_to)) < strtotime((string) $this->_employee->stats_date_from)) {
            // If the granularity is less than 1 year
            if ($legend) {
                $months = [];
                if ($from_array['year'] == $to_array['year']) {
                    for ($i = $from_array['mon']; $i <= $to_array['mon']; ++$i) {
                        $months[] = $i;
                    }
                } else {
                    for ($i = $from_array['mon']; $i <= 12; ++$i) {
                        $months[] = $i;
                    }
                    for ($i = 1; $i <= $to_array['mon']; ++$i) {
                        $months[] = $i;
                    }
                }
                foreach ($months as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; $j++) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = sprintf('%02d', $i);
                }
            }
            if (is_callable([$this, 'setYearValues'])) {
                $this->set_year_values($layers);
            }
        } else {
            // If the granularity is greater than 1 year
            if ($legend) {
                $years = [];
                for ($i = $from_array['year']; $i <= $to_array['year']; ++$i) {
                    $years[] = $i;
                }
                foreach ($years as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; $j++) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = sprintf('%04d', $i);
                }
            }
            if (is_callable([$this, 'setAllTimeValues'])) {
                $this->set_all_time_values($layers);
            }
        }
    }
    /**
     * @param array $datas
     *
     * @throws PrestaShopException
     */
    protected function csv_export($datas)
    {
        $context = Context::get_context();
        $this->set_employee($context->employee->id);
        $this->set_lang($context->language->id);
        $layers = $datas['layers'] ?? 1;
        if (isset($datas['option'])) {
            $this->set_option($datas['option'], $layers);
        }
        $this->get_data($layers);
        // @todo use native CSV PHP functions ?
        // Generate first line (column titles)
        if (is_array($this->_titles['main'])) {
            for ($i = 0, $total_main = count($this->_titles['main']); $i <= $total_main; $i++) {
                if ($i > 0) {
                    $this->_csv .= ';';
                }
                if (isset($this->_titles['main'][$i])) {
                    $this->_csv .= $this->_titles['main'][$i];
                }
            }
        } else {
            // If there is only one column title, there is in fast two column (the first without title)
            $this->_csv .= ';' . $this->_titles['main'];
        }
        $this->_csv .= "\n";
        if (count($this->_legend)) {
            $total = 0;
            if ($datas['type'] == 'pie') {
                foreach ($this->_legend as $key => $legend) {
                    for ($i = 0, $total_main = is_array($this->_titles['main']) ? count($this->_values) : 1; $i < $total_main; ++$i) {
                        $total += is_array($this->_values[$i]) ? $this->_values[$i][$key] : $this->_values[$key];
                    }
                }
            }
            foreach ($this->_legend as $key => $legend) {
                $this->_csv .= $legend . ';';
                for ($i = 0, $total_main = is_array($this->_titles['main']) ? count($this->_values) : 1; $i < $total_main; ++$i) {
                    if (!isset($this->_values[$i]) || !is_array($this->_values[$i])) {
                        if (isset($this->_values[$key])) {
                            // We don't want strings to be divided. Example: product name
                            if (is_numeric($this->_values[$key])) {
                                $this->_csv .= $this->_values[$key] / ($datas['type'] == 'pie' ? $total : 1);
                            } else {
                                $this->_csv .= $this->_values[$key];
                            }
                        } else {
                            $this->_csv .= '0';
                        }
                    } else if (is_numeric($this->_values[$i][$key])) {
                        $this->_csv .= $this->_values[$i][$key] / ($datas['type'] == 'pie' ? $total : 1);
                    } else {
                        $this->_csv .= $this->_values[$i][$key];
                    }
                    $this->_csv .= ';';
                }
                $this->_csv .= "\n";
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
     * @param string $render
     * @param string|null $type
     * @param int $width
     * @param int $height
     * @param int $layers
     *
     * @throws PrestaShopException
     */
    public function create($render, $type, $width, $height, $layers): void
    {
        if (!Validate::is_module_name($render)) {
            throw new Presta_Shop_Exception('Failed to resolve renderer module');
        }
        if (!file_exists($file = _PS_ROOT_DIR_ . '/modules/' . $render . '/' . $render . '.php')) {
            throw new Presta_Shop_Exception('Invalid renderer module: ' . $render);
        }
        require_once $file;
        $this->_render = new $render($type);
        $this->get_data($layers);
        $this->_render->create_values($this->_values);
        $this->_render->set_size($width, $height);
        $this->_render->set_legend($this->_legend);
        $this->_render->set_titles($this->_titles);
    }
    public function draw(): void
    {
        $this->_render->draw();
    }
    /**
     * @param mixed $option
     * @param int $layers
     */
    public function set_option($option, $layers = 1)
    {
    }
    /**
     * @param array $params
     *
     * @return array|mixed|string
     *
     * @throws PrestaShopException
     */
    public function engine($params)
    {
        $context = Context::get_context();
        $render = Configuration::get('PS_STATS_RENDER');
        $id_employee = (int) $context->employee->id;
        $id_lang = (int) $context->language->id;
        if (!isset($params['layers'])) {
            $params['layers'] = 1;
        }
        if (!isset($params['type'])) {
            $params['type'] = 'column';
        }
        if (!isset($params['width'])) {
            $params['width'] = '100%';
        }
        if (!isset($params['height'])) {
            $params['height'] = 270;
        }
        $url_params = $params;
        $url_params['render'] = $render;
        $url_params['module'] = Tools::get_value('module');
        $url_params['id_employee'] = $id_employee;
        $url_params['id_lang'] = $id_lang;
        $drawer = 'drawer.php?' . http_build_query(array_map(Tools::safe_output(...), $url_params), '', '&');
        if (file_exists(_PS_ROOT_DIR_ . '/modules/' . $render . '/' . $render . '.php')) {
            require_once _PS_ROOT_DIR_ . '/modules/' . $render . '/' . $render . '.php';
            return call_user_func([$render, 'hookGraphEngine'], $params, $drawer);
        }
        return call_user_func(['ModuleGraphEngine', 'hookGraphEngine'], $params, $drawer);
    }
    /**
     * @param Employee|null $employee
     *
     * @return Employee | false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_employee($employee = null, ?Context $context = null)
    {
        if (!Validate::is_loaded_object($employee)) {
            if (!$context) {
                $context = Context::get_context();
            }
            if (!Validate::is_loaded_object($context->employee)) {
                return false;
            }
            $employee = $context->employee;
        }
        if (empty($employee->stats_date_from) || empty($employee->stats_date_to) || $employee->stats_date_from == '0000-00-00' || $employee->stats_date_to == '0000-00-00') {
            if (empty($employee->stats_date_from) || $employee->stats_date_from == '0000-00-00') {
                $employee->stats_date_from = date('Y') . '-01-01';
            }
            if (empty($employee->stats_date_to) || $employee->stats_date_to == '0000-00-00') {
                $employee->stats_date_to = date('Y') . '-12-31';
            }
            $employee->update();
        }
        return $employee;
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
     * @param Employee|null $employee
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_date_between($employee = null)
    {
        if ($employee = Module_Graph::get_employee($employee)) {
            return ' \'' . p_sql($employee->stats_date_from . ' 00:00:00') . '\' AND \'' . p_sql($employee->stats_date_to . ' 23:59:59') . '\' ';
        }
        return ' \'' . p_sql(date('Y-m') . '-01 00:00:00') . '\' AND \'' . p_sql(date('Y-m-t') . ' 23:59:59') . '\' ';
    }
    /**
     * @return int
     */
    public function get_lang()
    {
        return (int) $this->_id_lang;
    }
    /**
     * @param int $layers
     *
     * @return void
     */
    abstract protected function get_data($layers);
}