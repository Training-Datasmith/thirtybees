<?php

declare(strict_types=1);
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
 * Class TreeCore
 */
class TreeCore implements \Stringable
{
    public const DEFAULT_TEMPLATE_DIRECTORY = 'helpers/tree';
    public const DEFAULT_TEMPLATE = 'tree.tpl';
    public const DEFAULT_HEADER_TEMPLATE = 'tree_header.tpl';
    public const DEFAULT_NODE_FOLDER_TEMPLATE = 'tree_node_folder.tpl';
    public const DEFAULT_NODE_ITEM_TEMPLATE = 'tree_node_item.tpl';

    /**
     * @var array
     */
    protected $_attributes;

    /**
     * @var Context
     */
    private $_context;

    /**
     * @var array
     */
    protected $_data;

    /**
     * @var array
     */
    protected $_data_search;

    /**
     * @var string
     */
    protected $_headerTemplate;

    /**
     * @var string
     */
    protected $_id_tree;

    /**
     * @var int
     */
    private $_id;

    /**
     * @var string
     */
    protected $_node_folder_template;

    /**
     * @var string
     */
    protected $_node_item_template;

    /**
     * @var string
     */
    protected $_template;

    /**
     * @var string
     */
    private $_template_directory;

    private string $_title = '';

    /**
     * @var bool
     */
    private $_no_js;

    private ?\ITreeToolbarCore $_toolbar = null;

    /**
     * TreeCore constructor.
     *
     * @param string|int $id
     * @param array $data
     *
     * @throws PrestaShopException
     */
    public function __construct($id, $data = null)
    {
        $this->setId($id);

        if (isset($data)) {
            $this->setData($data);
        }
    }

    /**
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * @param ITreeToolbarButtonCore[] $value
     */
    public function setActions($value): static
    {
        if (!isset($this->_toolbar)) {
            $this->setToolbar(new TreeToolbar());
        }

        $this->getToolbar()->setTemplateDirectory($this->getTemplateDirectory())->setActions($value);

        return $this;
    }

    /**
     * @return ITreeToolbarButtonCore[]
     */
    public function getActions()
    {
        if (!isset($this->_toolbar)) {
            $this->setToolbar(new TreeToolbar());
        }

        return $this->getToolbar()->setTemplateDirectory($this->getTemplateDirectory())->getActions();
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setAttribute($name, $value): static
    {
        if (!isset($this->_attributes)) {
            $this->_attributes = [];
        }

        $this->_attributes[$name] = $value;

        return $this;
    }

    /**
     * @param array $value
     *
     * @throws PrestaShopException
     */
    public function setAttributes($value): static
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new PrestaShopException('Data value must be an traversable array');
        }

        $this->_attributes = $value;

        return $this;
    }

    /**
     * @param string $idTree
     */
    public function setIdTree($idTree): static
    {
        $this->_id_tree = $idTree;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdTree()
    {
        return $this->_id_tree;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        if (!isset($this->_attributes)) {
            $this->_attributes = [];
        }

        return $this->_attributes;
    }

    /**
     * @param Context $value
     */
    public function setContext($value): static
    {
        $this->_context = $value;

        return $this;
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        if (!isset($this->_context)) {
            $this->_context = Context::getContext();
        }

        return $this->_context;
    }

    /**
     * @param array $value
     *
     * @throws PrestaShopException
     */
    public function setDataSearch($value): static
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new PrestaShopException('Data value must be an traversable array');
        }

        $this->_data_search = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getDataSearch()
    {
        if (!isset($this->_data_search)) {
            $this->_data_search = [];
        }

        return $this->_data_search;
    }

    /**
     * @param array $value
     *
     * @throws PrestaShopException
     */
    public function setData($value): static
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new PrestaShopException('Data value must be an traversable array');
        }

        $this->_data = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        if (!isset($this->_data)) {
            $this->_data = [];
        }

        return $this->_data;
    }

    /**
     * @param string $value
     */
    public function setHeaderTemplate($value): static
    {
        $this->_headerTemplate = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getHeaderTemplate()
    {
        if (!isset($this->_headerTemplate)) {
            $this->setHeaderTemplate(static::DEFAULT_HEADER_TEMPLATE);
        }

        return $this->_headerTemplate;
    }

    /**
     * @param int $value
     */
    public function setId($value): static
    {
        $this->_id = $value;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param string $value
     */
    public function setNodeFolderTemplate($value): static
    {
        $this->_node_folder_template = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getNodeFolderTemplate()
    {
        if (!isset($this->_node_folder_template)) {
            $this->setNodeFolderTemplate(static::DEFAULT_NODE_FOLDER_TEMPLATE);
        }

        return $this->_node_folder_template;
    }

    /**
     * @param string $value
     */
    public function setNodeItemTemplate($value): static
    {
        $this->_node_item_template = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getNodeItemTemplate()
    {
        if (!isset($this->_node_item_template)) {
            $this->setNodeItemTemplate(static::DEFAULT_NODE_ITEM_TEMPLATE);
        }

        return $this->_node_item_template;
    }

    /**
     * @param string $value
     */
    public function setTemplate($value): static
    {
        $this->_template = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        if (!isset($this->_template)) {
            $this->setTemplate(static::DEFAULT_TEMPLATE);
        }

        return $this->_template;
    }

    /**
     * @param string $value
     */
    public function setTemplateDirectory($value): static
    {
        $this->_template_directory = $this->_normalizeDirectory($value);

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplateDirectory()
    {
        if (!isset($this->_template_directory)) {
            $this->_template_directory = $this->_normalizeDirectory(
                static::DEFAULT_TEMPLATE_DIRECTORY
            );
        }

        return $this->_template_directory;
    }

    /**
     *
     *
     * @throws PrestaShopException
     */
    public function getTemplateFile(string $template): string
    {
        if (preg_match_all('/((?:^|[A-Z])[a-z]+)/', $this->getContext()->controller::class, $matches) !== false) {
            $controllerName = strtolower($matches[0][1]);
        }
        if ($this->getContext()->controller instanceof ModuleAdminController && isset($controllerName) && file_exists(
            $this->_normalizeDirectory($this->getContext()->controller->getTemplatePath()).$controllerName.DIRECTORY_SEPARATOR.$this->getTemplateDirectory().$template
        )) {
            return $this->_normalizeDirectory($this->getContext()->controller->getTemplatePath()).$controllerName.DIRECTORY_SEPARATOR.$this->getTemplateDirectory().$template;
        }
        if ($this->getContext()->controller instanceof ModuleAdminController && file_exists(
            $this->_normalizeDirectory(
                $this->getContext()->controller->getTemplatePath()
            ).$this->getTemplateDirectory().$template
        )) {
            return $this->_normalizeDirectory($this->getContext()->controller->getTemplatePath()).$this->getTemplateDirectory().$template;
        }
        if ($this->getContext()->controller instanceof AdminController && isset($controllerName)
            && file_exists($this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(0)).'controllers'.DIRECTORY_SEPARATOR.$controllerName.DIRECTORY_SEPARATOR.$this->getTemplateDirectory().$template)) {
            return $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(0)).'controllers'
                .DIRECTORY_SEPARATOR.$controllerName.DIRECTORY_SEPARATOR.$this->getTemplateDirectory().$template;
        }
        if (file_exists(
            $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(1))
            .$this->getTemplateDirectory().$template
        )) {
            return $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(1))
                .$this->getTemplateDirectory().$template;
        }

        if (file_exists(
            $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(0))
            .$this->getTemplateDirectory().$template
        )) {
            return $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(0))
                .$this->getTemplateDirectory().$template;
        }
        return $this->getTemplateDirectory().$template;
    }

    /**
     * @param bool $value
     */
    public function setNoJS($value): static
    {
        $this->_no_js = $value;

        return $this;
    }

    /**
     * @param string $value
     */
    public function setTitle($value): static
    {
        if ($value) {
            $this->_title = trim($value);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    public function setToolbar(ITreeToolbarCore $value): static
    {
        $this->_toolbar = $value;
        return $this;
    }

    /**
     * @return ITreeToolbarCore
     */
    public function getToolbar()
    {
        if (isset($this->_toolbar)) {
            if ($this->getDataSearch()) {
                $this->_toolbar->setData($this->getDataSearch());
            } else {
                $this->_toolbar->setData($this->getData());
            }
        }

        return $this->_toolbar;
    }

    /**
     * @param ITreeToolbarButtonCore $action
     */
    public function addAction($action): static
    {
        if (!isset($this->_toolbar)) {
            $this->setToolbar(new TreeToolbar());
        }

        $this->getToolbar()->setTemplateDirectory($this->getTemplateDirectory())->addAction($action);

        return $this;
    }

    public function removeActions(): static
    {
        if (!isset($this->_toolbar)) {
            $this->setToolbar(new TreeToolbar());
        }

        $this->getToolbar()->setTemplateDirectory($this->getTemplateDirectory())->removeActions();

        return $this;
    }

    /**
     * @param array|null $data
     *
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render($data = null): string
    {
        //Adding tree.js
        $adminWebpath = str_ireplace(_PS_CORE_DIR_, '', _PS_ADMIN_DIR_);
        $adminWebpath = preg_replace('/^'.preg_quote(DIRECTORY_SEPARATOR, '/').'/', '', $adminWebpath);
        $boTheme = ((Validate::isLoadedObject($this->getContext()->employee)
            && $this->getContext()->employee->bo_theme) ? $this->getContext()->employee->bo_theme : 'default');

        if (!file_exists(_PS_BO_ALL_THEMES_DIR_.$boTheme.DIRECTORY_SEPARATOR.'template')) {
            $boTheme = 'default';
        }

        $jsPath = Media::getUriWithVersion(__PS_BASE_URI__.$adminWebpath.'/themes/'.$boTheme.'/js/tree.js');
        if ($this->getContext()->controller->ajax) {
            if (!$this->_no_js) {
                $html = '<script type="text/javascript">$(function(){ $.ajax({url: "'.$jsPath.'",cache:true,dataType: "script"})});</script>';
            }
        } else {
            $this->getContext()->controller->addJs($jsPath);
        }

        //Create Tree Template
        $template = $this->getContext()->smarty->createTemplate(
            $this->getTemplateFile($this->getTemplate()),
            $this->getContext()->smarty
        );

        if ($this->getTitle() || $this->useToolbar()) {
            //Create Tree Header Template
            $headerTemplate = $this->getContext()->smarty->createTemplate(
                $this->getTemplateFile($this->getHeaderTemplate()),
                $this->getContext()->smarty
            );
            $headerTemplate->assign($this->getAttributes())
                ->assign(
                    [
                        'title'   => $this->getTitle(),
                        'toolbar' => $this->useToolbar() ? $this->renderToolbar() : null,
                    ]
                );
            $template->assign('header', $headerTemplate->fetch());
        }

        //Assign Tree nodes
        $template->assign($this->getAttributes())->assign(
            [
                'id'      => $this->getId(),
                'nodes'   => $this->renderNodes($data),
                'id_tree' => $this->getIdTree(),
            ]
        );

        return ($html ?? '').$template->fetch();
    }

    /**
     * @param array|null $data
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderNodes($data = null): string
    {
        if (!isset($data)) {
            $data = $this->getData();
        }

        if (!is_array($data) && !$data instanceof Traversable) {
            throw new PrestaShopException('Data value must be an traversable array');
        }

        $html = '';

        foreach ($data as $item) {
            if (array_key_exists('children', $item)
                && !empty($item['children'])
            ) {
                $html .= $this->getContext()->smarty->createTemplate(
                    $this->getTemplateFile($this->getNodeFolderTemplate()),
                    $this->getContext()->smarty
                )->assign(
                    [
                        'children' => $this->renderNodes($item['children']),
                        'node'     => $item,
                    ]
                )->fetch();
            } else {
                $html .= $this->getContext()->smarty->createTemplate(
                    $this->getTemplateFile($this->getNodeItemTemplate()),
                    $this->getContext()->smarty
                )->assign(
                    [
                        'node' => $item,
                    ]
                )->fetch();
            }
        }

        return $html;
    }

    /**
     * @return string
     */
    public function renderToolbar()
    {
        return $this->getToolbar()->render();
    }

    public function useInput(): bool
    {
        return isset($this->_input_type);
    }

    public function useToolbar(): bool
    {
        return isset($this->_toolbar);
    }

    /**
     *
     *
     * @deprecated 2.0.0
     */
    protected function _normalizeDirectory(string $directory): string
    {
        $last = $directory[strlen($directory) - 1];

        if (in_array($last, ['/', '\\'])) {
            $directory[strlen($directory) - 1] = DIRECTORY_SEPARATOR;

            return $directory;
        }

        return $directory . DIRECTORY_SEPARATOR;
    }
}
