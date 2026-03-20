<?php

declare (strict_types=1);
namespace Thirtybees\Core\View\Model;

use Combination;
use Presta_Shop_Exception;
use Product;
use Stock_Available;
class Product_View_Model_Core extends Product
{
    public const LEGACY_PROPERTY_GETTER = ['id_image' => 'getCoverImageId', 'allow_oosp' => 'availableWhenOutOfStock', 'id_product_attribute' => 'getSelectedCombinationId'];
    /**
     * @var array
     */
    protected $legacy_property_values = [];
    /**
     * @var Combination|null
     */
    protected $selected_combination;
    /**
     * @var int|null
     */
    protected $cover_image_id;
    /**
     *
     * @throws PrestaShopException
     */
    public function __construct(int $product_id, int $combination_id, int $language_id, int $shop_id)
    {
        parent::__construct($product_id, true, $language_id, $shop_id);
        if ($combination_id) {
            $this->selected_combination = new Combination($combination_id, $language_id, $shop_id);
            // recalculate price
            $this->price = static::get_price_static((int) $this->id, false, $combination_id, _TB_PRICE_DATABASE_PRECISION_, null, false, true, 1, false, null, null, null, $this->specific_price);
            $this->unit_price = $this->unit_price_ratio != 0 ? round($this->price / $this->unit_price_ratio, _TB_PRICE_DATABASE_PRECISION_) : 0;
            // recalculate quantity
            $this->quantity = Stock_Available::get_quantity_available_by_product($this->id, $combination_id);
            $this->out_of_stock = Stock_Available::out_of_stock($this->id, $shop_id, $combination_id);
            $this->depends_on_stock = Stock_Available::depends_on_stock($this->id, $shop_id, $combination_id);
        }
    }
    /**
     * Get all available attribute groups
     *
     * @param int $idLang Language id
     *
     * @return array Attribute groups
     *
     * @throws PrestaShopException
     */
    public function get_attributes_groups($id_lang)
    {
        $attribute_groups = parent::get_attributes_groups($id_lang);
        if ($this->selected_combination) {
            $combination_attributes = $this->selected_combination->get_attributes();
            foreach ($attribute_groups as &$attribute_group) {
                $attribute_group_id = (int) $attribute_group['id_attribute_group'];
                $attribute_id = (int) $attribute_group['id_attribute'];
                $combination_attribute_id = $combination_attributes[$attribute_group_id] ?? 0;
                $attribute_group['default_on'] = $combination_attribute_id === $attribute_id ? 1 : 0;
            }
        }
        return $attribute_groups;
    }
    /**
     * Get product price
     * Same as static function getPriceStatic, no need to specify product id
     *
     * @param bool $tax With taxes or not (optional)
     * @param int $idProductAttribute Product attribute id (optional)
     * @param int $decimals Number of decimals (optional)
     * @param int $divisor Util when paying many time without fees (optional)
     *
     * @return float Product price in euros
     *
     * @throws PrestaShopException
     */
    public function get_price($tax = true, $id_product_attribute = null, $decimals = _TB_PRICE_DATABASE_PRECISION_, $divisor = null, $only_reduc = false, $usereduc = true, $quantity = 1)
    {
        if ($id_product_attribute === null) {
            $id_product_attribute = $this->get_selected_combination_id();
        }
        return parent::get_price($tax, $id_product_attribute, $decimals, $divisor, $only_reduc, $usereduc, $quantity);
    }
    /**
     * @return int|null
     */
    public function get_selected_combination_id()
    {
        if ($this->selected_combination) {
            return (int) $this->selected_combination->id;
        }
        return null;
    }
    /**
     * @param string $property
     *
     * @return mixed
     */
    public function &__get($property)
    {
        if (array_key_exists($property, $this->legacy_property_values)) {
            return $this->legacy_property_values[$property];
        }
        if (array_key_exists($property, static::LEGACY_PROPERTY_GETTER)) {
            $method_name = static::LEGACY_PROPERTY_GETTER[$property];
            $this->legacy_property_values[$property] = $this->{$method_name}();
            return $this->legacy_property_values[$property];
        }
        return parent::__get($property);
    }
    /**
     * @throws PrestaShopException
     */
    public function get_cover_image_id(): int
    {
        $cover = Product::get_cover($this->id);
        return $cover['id_image'] ?? 0;
    }
    /**
     * return bool|int
     *
     * @throws PrestaShopException
     */
    public function available_when_out_of_stock(): bool
    {
        return Product::is_available_when_out_of_stock($this->out_of_stock);
    }
}