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
abstract class Module_Stats_Core extends Module
{
    public const ENGINE_GRAPH = 'graph';
    public const ENGINE_GRID = 'grid';
    /**
     * @var Employee $_employee
     */
    protected $_employee;
    /**
     * @var int[] | int[][] graph data
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
     * @var ModuleGraphEngine|ModuleGridEngine graph engine
     */
    protected $_render;
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
     * @param int $id_lang
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
    protected function csv_export_graph($datas)
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
     * @param array $datas
     * @return void
     */
    protected function csv_export_grid($datas)
    {
        $this->_sort = $datas['defaultSortColumn'];
        $this->set_lang(Context::get_context()->language->id);
        $layers = $datas['layers'] ?? 1;
        $this->get_data($layers);
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
     * @param string $render
     * @param string|null $type
     * @param int $width
     * @param int $height
     * @param int $layers
     *
     * @throws PrestaShopException
     */
    public function create_graph($render, $type, $width, $height, $layers): void
    {
        $this->_render = static::get_rendering_engine(static::ENGINE_GRAPH, $type);
        $this->get_data($layers);
        $this->_render->create_values($this->_values);
        $this->_render->set_size($width, $height);
        $this->_render->set_legend($this->_legend);
        $this->_render->set_titles($this->_titles);
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
     * @throws PrestaShopException
     */
    public function create_grid($render, $type, $width, $height, $start, $limit, $sort, $dir): void
    {
        $this->_render = static::get_rendering_engine(static::ENGINE_GRID, $type);
        $this->_start = $start;
        $this->_limit = $limit;
        $this->_sort = $sort;
        $this->_direction = $dir;
        $this->get_data(1);
        $this->_render->set_title($this->_title);
        $this->_render->set_size($width, $height);
        $this->_render->set_values($this->_values);
        $this->_render->set_total_count($this->_total_count);
        $this->_render->set_limit($this->_start, $this->_limit);
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
     * @return string
     *
     * @throws PrestaShopException
     */
    public function engine_graph($params)
    {
        $context = Context::get_context();
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
        $url_params['module'] = Tools::get_value('module');
        $url_params['id_employee'] = $id_employee;
        $url_params['id_lang'] = $id_lang;
        $drawer = 'drawer.php?' . http_build_query(array_map(Tools::safe_output(...), $url_params), '', '&');
        $type = $params['type'] ?? null;
        $engine = static::get_rendering_engine(static::ENGINE_GRAPH, $type);
        return $engine->hook_graph_engine($params, $drawer);
    }
    /**
     * @return string
     *
     * @throws PrestaShopException
     */
    public function engine_grid($params)
    {
        $grider = 'grider.php?&module=' . Tools::safe_output(Tools::get_value('module'));
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
        $type = $params['type'] ?? null;
        $engine = static::get_rendering_engine(static::ENGINE_GRID, $type);
        return $engine->hook_grid_engine($params, $grider);
    }
    /**
     * @param Employee|null $employee
     *
     * @return Employee|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function get_employee($employee = null, ?Context $context = null)
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
            return ' \'' . $employee->stats_date_from . ' 00:00:00\' AND \'' . $employee->stats_date_to . ' 23:59:59\' ';
        }
        return ' \'' . date('Y-m') . '-01 00:00:00\' AND \'' . date('Y-m-t') . ' 23:59:59\' ';
    }
    /**
     * @return int
     */
    public function get_lang()
    {
        return (int) $this->_id_lang;
    }
    /**
     * Instantiates rendering engine
     *
     * @param string $engineType type of engine, either ENGINE_GRAPH or ENGINE_GRID
     * @param string $type type parameter for engine
     *
     * @return ModuleGraphEngine|ModuleGridEngine
     *
     * @throws PrestaShopException
     */
    protected static function get_rendering_engine($engine_type, $type)
    {
        if ($engine_type === static::ENGINE_GRAPH) {
            $custom_engine = Configuration::get('PS_STATS_RENDER');
            $default_engine = 'ModuleGraphEngine';
        } elseif ($engine_type === static::ENGINE_GRID) {
            $custom_engine = Configuration::get('PS_STATS_GRID_RENDER');
            $default_engine = 'ModuleGridEngine';
        } else {
            throw new Presta_Shop_Exception('Invalid rendering engine: ' . $engine_type);
        }
        // try to instantiate custom rendering engine
        if ($custom_engine) {
            if (!class_exists($custom_engine) && file_exists($file = _PS_ROOT_DIR_ . '/modules/' . $custom_engine . '/' . $custom_engine . '.php')) {
                require_once $file;
            }
            // check that custom rendering engine is subclass of default engine
            if (class_exists($custom_engine)) {
                $reflection = new ReflectionClass($custom_engine);
                if ($reflection->is_subclass_of($default_engine . 'Core')) {
                    return new $custom_engine($type);
                }
            }
        }
        // fallback to default rendering engine
        return new $default_engine($type);
    }
    /**
     * @param int $layers
     *
     * @return void
     */
    abstract protected function get_data($layers);
}