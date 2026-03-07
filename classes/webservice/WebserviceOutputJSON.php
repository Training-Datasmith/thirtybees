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
 * Class WebserviceOutputJSON
 */
class WebserviceOutputJSONCore implements WebserviceOutputInterface
{
    /**
     * @var string
     */
    public $docUrl = '';

    /**
     * @var string
     */
    protected $wsUrl;

    /**
     * @var string
     */
    protected $schemaToDisplay;

    /**
     * @var array Current entity
     */
    protected $currentEntity;

    /**
     * @var array Current association
     */
    protected $currentAssociatedEntity = [];

    /**
     * @var array Json content
     */
    protected $content = [];

    /**
     * WebserviceOutputJSON constructor.
     *
     * @param array $languages
     */
    public function __construct(public $languages = [])
    {
    }

    /**
     * @param string $schema
     */
    public function setSchemaToDisplay($schema): static
    {
        if (is_string($schema)) {
            $this->schemaToDisplay = $schema;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getSchemaToDisplay()
    {
        return $this->schemaToDisplay;
    }

    /**
     * @param string $url
     */
    public function setWsUrl($url): static
    {
        $this->wsUrl = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getWsUrl()
    {
        return $this->wsUrl;
    }

    public function getContentType(): string
    {
        return 'application/json';
    }

    /**
     * @param string $message
     * @param int|null $code
     * @param array $extra
     */
    public function renderErrors($message, $code = null, $extra = []): string
    {
        $error = [ 'message' => $message ];
        if (! is_null($code)) {
            $error['code'] = $code;
        }
        if (! is_null($extra)) {
            $error = array_merge($extra, $error);
        }
        $this->content['errors'][] = $error;

        return '';
    }

    /**
     * @param array $field
     */
    public function renderField($field): string
    {
        $isAssociation = (isset($field['is_association']) && $field['is_association'] == true);

        if (!$isAssociation) {
            // Case 1 : fields of the current entity (not an association)
            $this->currentEntity[$field['sqlId']] = $this->getFieldValue($field);
        } else {
            // Case 2 : fields of an associated entity to the current one
            $this->currentAssociatedEntity[] = [
                'name' => $field['entities_name'],
                'key' => $field['sqlId'],
                'value' => $this->getFieldValue($field),
            ];
        }

        return '';
    }

    /**
     * @param string $nodeName
     * @param array $params
     * @param array|null $moreAttr
     * @param bool $hasChild
     */
    public function renderNodeHeader($nodeName, $params, $moreAttr = null, $hasChild = true): string
    {
        // api ?
        static $isAPICall = false;
        if ($nodeName == 'api' && ($isAPICall == false)) {
            $isAPICall = true;
        }
        if ($isAPICall && !in_array($nodeName, ['description', 'schema', 'api'])) {
            $this->content[] = $nodeName;
        }
        if (isset($moreAttr, $moreAttr['id'])) {
            $this->content[$params['objectsNodeName']][] = ['id' => $moreAttr['id']];
        }

        return '';
    }

    /**
     * @return string
     */
    public function getNodeName(array $params)
    {
        return $params['objectNodeName'] ?? '';
    }

    /**
     * @param string $nodeName
     * @param array $params
     */
    public function renderNodeFooter($nodeName, $params): string
    {
        if (isset($params['objectNodeName']) && $params['objectNodeName'] == $nodeName) {
            if (array_key_exists('display', $_GET)) {
                $this->content[$params['objectsNodeName']][] = $this->currentEntity;
            } else {
                $this->content[$params['objectNodeName']] = $this->currentEntity;
            }
            $this->currentEntity = [];
        }
        if (count($this->currentAssociatedEntity) > 0) {
            $current = [];
            $name = $this->currentAssociatedEntity[0]['name'];
            foreach ($this->currentAssociatedEntity as $element) {
                $current[$element['key']] = $element['value'];
            }
            $this->currentEntity['associations'][$name][] = $current;
            $this->currentAssociatedEntity = [];
        }
        return '';
    }

    /**
     * @param string $content
     */
    public function overrideContent($content): string
    {
        $options = 0;
        if (Tools::getValue('unescaped') === 'true') {
            $options |= JSON_UNESCAPED_UNICODE;
        }
        if (Tools::getValue('pretty') === 'true') {
            $options |= JSON_PRETTY_PRINT;
        }

        $content = '';

        return $content . json_encode($this->content, $options);
    }

    /**
     * @param array $languages
     */
    public function setLanguages($languages): static
    {
        $this->languages = $languages;

        return $this;
    }

    public function renderAssociationWrapperHeader(): string
    {
        return '';
    }

    public function renderAssociationWrapperFooter(): string
    {
        return '';
    }

    /**
     * @param ObjectModel$obj
     * @param array $params
     * @param string $assocName
     * @param bool $closedTags
     */
    public function renderAssociationHeader($obj, $params, $assocName, $closedTags = false): string
    {
        return '';
    }

    /**
     * @param ObjectModel $obj
     * @param array $params
     * @param string $assocName
     */
    public function renderAssociationFooter($obj, $params, $assocName): string
    {
        return '';
    }

    public function renderErrorsHeader(): string
    {
        return '';
    }

    public function renderErrorsFooter(): string
    {
        return '';
    }

    /**
     * @param array $field
     */
    public function renderAssociationField($field): string
    {
        return '';
    }

    /**
     * @param array $field
     */
    public function renderi18nField($field): string
    {
        return '';
    }

    /**
     * Returns field value
     *
     * @return string
     */
    protected function getFieldValue(array $field)
    {
        $value = $field['value'] ?? null;

        if (is_array($value)) {
            $tmp = [];
            foreach ($this->languages as $idLang) {
                $tmp[] = [
                    'id' => $idLang,
                    'value' => array_key_exists($idLang, $value) ? $value[$idLang] : '',
                ];
            }
            if (count($tmp) == 1) {
                $value = $tmp[0]['value'];
            } else {
                $value = $tmp;
            }
        }

        return $value;
    }
}
