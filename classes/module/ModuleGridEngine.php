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
 * Class ModuleGridEngineCore
 */
class Module_Grid_Engine_Core extends Module
{
    /**
     * @var array
     */
    protected $_values;
    /**
     * @var int
     */
    protected $_width;
    /**
     * @var int
     */
    protected $_height;
    /**
     * @var int
     */
    protected $_start;
    /**
     * @var int
     */
    protected $_limit;
    /**
     * @var int
     */
    protected $_total_count;
    /**
     * @var string
     */
    protected $_title;
    /**
     * ModuleGridEngineCore constructor.
     *
     * @param string|null $_type
     *
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(protected $_type)
    {
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }
        return Configuration::update_value('PS_STATS_GRID_RENDER', $this->name);
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_grid_engines()
    {
        $result = Db::read_only()->get_array((new Db_Query())->select('m.`name`')->from('module', 'm')->left_join('module', 'm')->left_join('hook', 'h', 'hm.`id_hook` = h.`id_hook`')->where('h.`name` = \'displayAdminStatsGridEngine\''));
        $array_engines = [];
        foreach ($result as $module) {
            $instance = Module::get_instance_by_name($module['name']);
            if (!$instance) {
                continue;
            }
            $array_engines[$module['name']] = [$instance->display_name, $instance->description];
        }
        return $array_engines;
    }
    /**
     * @param array $params
     * @param string $grider
     * @return string
     */
    public static function hook_grid_engine($params, $grider)
    {
        if (!isset($params['emptyMsg'])) {
            $params['emptyMsg'] = 'Empty';
        }
        $custom_params = '';
        if (isset($params['customParams'])) {
            foreach ($params['customParams'] as $name => $value) {
                $custom_params .= '&' . $name . '=' . urlencode((string) $value);
            }
        }
        $html = '
		<div class="table-responsive">
		<table class="table" id="grid_1">
			<thead>
				<tr>';
        foreach ($params['columns'] as $column) {
            $html .= '<th class="center"><span class="title_box active">' . $column['header'] . '</span></th>';
        }
        $html .= '</tr>
			</thead>
			<tbody></tbody>
			<tfoot><tr><th colspan="' . count($params['columns']) . '"></th></tr></tfoot>
		</table>
		</div>
		<script type="text/javascript">
			function getGridData(url)
			{
				$("#grid_1 tbody").html("<tr><td style=\"text-align:center\" colspan=\"" + ' . count($params['columns']) . ' + "\"><img src=\"../img/loadingAnimation.gif\" /></td></tr>");
				$.get(url, "", function(json) {
					$("#grid_1 tbody").html("");
					var array = $.parseJSON(json);
					$("#grid_1 tfoot tr th").html("' . addslashes((string) $params['pagingMessage']) . '");
					$("#grid_1 tfoot tr th").html($("#grid_1 tfoot tr th").html().replace("{0}", array["from"]));
					$("#grid_1 tfoot tr th").html($("#grid_1 tfoot tr th").html().replace("{1}", array["to"]));
					$("#grid_1 tfoot tr th").html($("#grid_1 tfoot tr th").html().replace("{2}", array["total"]));
					if (array["from"] > 1)
						$("#grid_1 tfoot tr th").html($("#grid_1 tfoot tr th").html() + " &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a style=\"cursor:pointer;text-decoration:none\" onclick=\"gridPrevPage(\'"+ url +"\');\">&lt;&lt;</a>");
					if (array["to"] < array["total"])
						$("#grid_1 tfoot tr th").html($("#grid_1 tfoot tr th").html() + " | <a style=\"cursor:pointer;text-decoration:none\" onclick=\"gridNextPage(\'"+ url +"\');\">&gt;&gt;</a>");
					var values = array["values"];
					if (values.length > 0)
						$.each(values, function(index, row){
							var newLine = "<tr>";';
        foreach ($params['columns'] as $column) {
            $html .= '	newLine += "<td' . (isset($column['align']) ? ' align=\"' . $column['align'] . '\"' : '') . '>" + row["' . $column['dataIndex'] . '"] + "</td>";';
        }
        if (!isset($params['defaultSortColumn'])) {
            $params['defaultSortColumn'] = false;
        }
        if (!isset($params['defaultSortDirection'])) {
            $params['defaultSortDirection'] = false;
        }
        $limit = 40;
        if (isset($params['limit']) && Validate::is_unsigned_int($params['limit'])) {
            $limit = (int) $params['limit'];
        }
        return $html . ('		$("#grid_1 tbody").append(newLine);
						});
					else
						$("#grid_1 tbody").append("<tr><td class=\"center\" colspan=\"" + ' . count($params['columns']) . ' + "\">' . $params['emptyMsg'] . '</td></tr>");
				});
			}
			
			function gridNextPage(url)
			{
				var from = url.match(/&start=[0-9]+/i);
				if (from && from[0] && parseInt(from[0].replace("&start=", "")) > 0)
					from = "&start=" + (parseInt(from[0].replace("&start=", "")) + ' . $limit . ');
				else
					from = "&start=' . $limit . '";
				url = url.replace(/&start=[0-9]+/i, "") + from;
				getGridData(url);
			}
			
			function gridPrevPage(url)
			{
				var from = url.match(/&start=[0-9]+/i);
				if (from && from[0] && parseInt(from[0].replace("&start=", "")) > 0)
				{
					var fromInt = parseInt(from[0].replace("&start=", "")) - ' . $limit . ';
					if (fromInt > 0)
						from = "&start=" + fromInt;
					else
						from = "&start=0";
				}
				else
					from = "&start=0";
				url = url.replace(/&start=[0-9]+/i, "") + from;
				getGridData(url);
			}
			$(document).ready(function(){getGridData("' . $grider . '&sort=' . urlencode((string) $params['defaultSortColumn']) . '&dir=' . urlencode($params['defaultSortDirection']) . $custom_params . '");});
		</script>');
    }
    /**
     * @param mixed $infos
     * @return void
     */
    public function set_columns_infos(&$infos)
    {
    }
    /**
     * @param array $values
     */
    public function set_values($values): void
    {
        $this->_values = $values;
    }
    /**
     * @param string $title
     */
    public function set_title($title): void
    {
        $this->_title = $title;
    }
    /**
     * @param int $width
     * @param int $height
     */
    public function set_size($width, $height): void
    {
        $this->_width = $width;
        $this->_height = $height;
    }
    /**
     * @param int $totalCount
     */
    public function set_total_count($total_count): void
    {
        $this->_total_count = (int) $total_count;
    }
    /**
     * @param int $start
     * @param int $limit
     */
    public function set_limit($start, $limit): void
    {
        $this->_start = (int) $start;
        $this->_limit = (int) $limit;
    }
    public function render(): void
    {
        echo json_encode(['total' => $this->_total_count, 'from' => min($this->_start + 1, $this->_total_count), 'to' => min($this->_start + $this->_limit, $this->_total_count), 'values' => $this->_values]);
        exit;
    }
}