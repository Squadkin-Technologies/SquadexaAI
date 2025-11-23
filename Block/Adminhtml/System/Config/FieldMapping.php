<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Json\DecoderInterface;

class FieldMapping extends Field
{
    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var DecoderInterface
     */
    private $jsonDecoder;

    /**
     * @param Context $context
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param EncoderInterface $jsonEncoder
     * @param DecoderInterface $jsonDecoder
     * @param array $data
     */
    public function __construct(
        Context $context,
        AttributeCollectionFactory $attributeCollectionFactory,
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        array $data = []
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->jsonEncoder = $jsonEncoder;
        $this->jsonDecoder = $jsonDecoder;
        parent::__construct($context, $data);
    }

    /**
     * Render field mapping configuration
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $element->setType('hidden');
        $html = parent::_getElementHtml($element);

        // Get AI field list
        $aiFields = $this->getAiFieldList();

        // Get Magento product attributes (text, textarea, select, multiselect, price)
        $attributes = $this->getProductAttributes();

        // Get existing mappings from config
        $existingMappings = $this->getExistingMappings($element);

        // Render the mapping interface
        $html .= $this->renderMappingInterface($aiFields, $attributes, $existingMappings, $element->getHtmlId());

        return $html;
    }

    /**
     * Get list of AI-generated fields
     *
     * @return array
     */
    private function getAiFieldList(): array
    {
        return [
            'product_name' => __('Product Name'),
            'meta_title' => __('Meta Title'),
            'meta_description' => __('Meta Description'),
            'short_description' => __('Short Description'),
            'description' => __('Description'),
            'key_features' => __('Key Features'),
            'how_to_use' => __('How to Use'),
            'ingredients' => __('Ingredients'),
            'upc' => __('UPC'),
            'keywords' => __('Keywords'),
            'primary_keywords' => __('Primary Keywords'),
            'secondary_keywords' => __('Secondary Keywords'),
            'pricing_usd_min' => __('Pricing USD Min'),
            'pricing_usd_max' => __('Pricing USD Max'),
            'pricing_cad_min' => __('Pricing CAD Min'),
            'pricing_cad_max' => __('Pricing CAD Max'),
        ];
    }

    /**
     * Get product attributes (text, textarea, select, multiselect, price)
     *
     * @return array
     */
    private function getProductAttributes(): array
    {
        $allowedTypes = ['text', 'textarea', 'select', 'multiselect', 'price'];
        $excludedAttributes = ['tier_price', 'gallery', 'media_gallery', 'category_ids', 'quantity_and_stock_status'];
        
        $collection = $this->attributeCollectionFactory->create();
        $collection->addFieldToFilter('frontend_input', ['in' => $allowedTypes])
            ->addFieldToFilter('is_user_defined', 1)
            ->addFieldToFilter('attribute_code', ['nin' => $excludedAttributes])
            ->setOrder('frontend_label', 'ASC');

        $attributes = [];
        foreach ($collection as $attribute) {
            $frontendLabel = $attribute->getFrontendLabel();
            if (empty($frontendLabel)) {
                continue; // Skip attributes without labels
            }
            
            $attributes[] = [
                'code' => $attribute->getAttributeCode(),
                'label' => $frontendLabel,
                'type' => $attribute->getFrontendInput()
            ];
        }

        // Add system attributes that are commonly used and mappable
        $systemAttributes = [
            ['code' => 'name', 'label' => __('Product Name'), 'type' => 'text'],
            ['code' => 'sku', 'label' => __('SKU'), 'type' => 'text'],
            ['code' => 'description', 'label' => __('Description'), 'type' => 'textarea'],
            ['code' => 'short_description', 'label' => __('Short Description'), 'type' => 'textarea'],
            ['code' => 'meta_title', 'label' => __('Meta Title'), 'type' => 'text'],
            ['code' => 'meta_description', 'label' => __('Meta Description'), 'type' => 'textarea'],
            ['code' => 'meta_keyword', 'label' => __('Meta Keywords'), 'type' => 'text'],
            ['code' => 'price', 'label' => __('Price'), 'type' => 'price'],
        ];

        // Merge system attributes with user-defined attributes and sort by label
        $allAttributes = array_merge($systemAttributes, $attributes);
        usort($allAttributes, function ($a, $b) {
            // Convert Phrase objects to strings for comparison
            $labelA = (string)$a['label'];
            $labelB = (string)$b['label'];
            return strcmp($labelA, $labelB);
        });

        return $allAttributes;
    }

    /**
     * Get existing mappings from config
     *
     * @param AbstractElement $element
     * @return array
     */
    private function getExistingMappings(AbstractElement $element): array
    {
        $value = $element->getValue();
        if (empty($value)) {
            return [];
        }

        try {
            $mappings = $this->jsonDecoder->decode($value);
            return is_array($mappings) ? $mappings : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Render mapping interface
     *
     * @param array $aiFields
     * @param array $attributes
     * @param array $existingMappings
     * @param string $fieldId
     * @return string
     */
    private function renderMappingInterface(
        array $aiFields,
        array $attributes,
        array $existingMappings,
        string $fieldId
    ): string {
        $attributesJson = $this->jsonEncoder->encode($attributes);
        $existingMappingsJson = $this->jsonEncoder->encode($existingMappings);

        $html = '<div id="field-mapping-container" style="margin-top: 20px;">';
        $html .= '<style>';
        $html .= '#field-mapping-table { border: 1px solid #ddd; }';
        $html .= '#field-mapping-table th { background: #f8f9fa; font-weight: 600; }';
        $html .= '#field-mapping-table td { vertical-align: middle; }';
        $html .= '#field-mapping-table tr:hover { background: #f8f9fa; }';
        $html .= '.attribute-select.error { border-color: #e02b27; }';
        $html .= '</style>';
        $html .= '<div class="admin__field" style="margin-bottom: 20px;">';
        $html .= '<div class="admin__field-label"><label><span>';
        $html .= __('AI Field to Magento Attribute Mapping') . '</span></label></div>';
        $html .= '<div class="admin__field-control" style="margin-top: 10px;">';
        $html .= '<table class="admin__table-primary" id="field-mapping-table" ';
        $html .= 'style="width: 100%; border-collapse: collapse;">';
        $html .= '<thead>';
        $html .= '<tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">';
        $html .= '<th style="padding: 12px; text-align: left; width: 50%;">';
        $html .= __('AI Generated Field') . '</th>';
        $html .= '<th style="padding: 12px; text-align: left; width: 50%;">';
        $html .= __('Magento Product Attribute') . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($aiFields as $fieldCode => $fieldLabel) {
            $selectedAttribute = isset($existingMappings[$fieldCode]) ? $existingMappings[$fieldCode] : '';
            $rowId = 'mapping-row-' . $fieldCode;
            
            // Convert Phrase objects to strings
            $fieldLabelString = (string)$fieldLabel;
            
            $html .= '<tr id="' . $rowId . '" style="border-bottom: 1px solid #dee2e6;">';
            $html .= '<td style="padding: 12px; vertical-align: middle;">';
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $html .= '<strong>' . htmlspecialchars($fieldLabelString) . '</strong><br/>'; // phpcs:ignore
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $html .= '<small style="color: #666;">(' . htmlspecialchars($fieldCode) . ')</small>'; // phpcs:ignore
            $html .= '</td>';
            $html .= '<td style="padding: 12px;">';
            $html .= '<select class="admin__control-select attribute-select" ';
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $html .= 'data-ai-field="' . htmlspecialchars($fieldCode) . '" '; // phpcs:ignore
            $html .= 'style="width: 100%;" ';
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $html .= 'id="attr-select-' . htmlspecialchars($fieldCode) . '">'; // phpcs:ignore
            $html .= '<option value="">-- ' . __('Select Attribute') . ' --</option>';
            
            foreach ($attributes as $attribute) {
                $selected = ($selectedAttribute === $attribute['code']) ? 'selected' : '';
                // Convert Phrase objects to strings
                $attributeLabelString = (string)$attribute['label'];
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $html .= '<option value="' . htmlspecialchars($attribute['code']) . '" '; // phpcs:ignore
                $html .= $selected . '>';
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $html .= htmlspecialchars($attributeLabelString) . ' ('; // phpcs:ignore
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $html .= htmlspecialchars($attribute['code']) . ')'; // phpcs:ignore
                $html .= '</option>';
            }
            
            $html .= '</select>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';

        // Hidden input to store JSON (will be synced with main config field)
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $html .= '<input type="hidden" id="' . $fieldId . '_json" value="';
        $html .= htmlspecialchars($existingMappingsJson) . '"/>'; // phpcs:ignore

        // JavaScript initialization
        $html .= '<script type="text/x-magento-init">';
        $html .= '{"#field-mapping-container": {';
        $html .= '"Squadkin_SquadexaAI/js/field-mapping-config": {';
        $html .= '"fieldId": "' . $fieldId . '",';
        $html .= '"attributes": ' . $attributesJson . ',';
        $html .= '"existingMappings": ' . $existingMappingsJson;
        $html .= '}}}';
        $html .= '</script>';

        // Add script to sync hidden field with main config field on form submit and before save
        $html .= '<script>';
        $html .= 'require(["jquery"], function($) {';
        $html .= '$(document).ready(function() {';
        $html .= 'var configForm = $("#config-edit-form");';
        $html .= 'if (configForm.length) {';
        $html .= '// Sync on form submit';
        $html .= 'configForm.on("submit", function() {';
        $html .= 'var jsonValue = $("#' . $fieldId . '_json").val();';
        $html .= 'if (jsonValue) {';
        $html .= '$("#' . $fieldId . '").val(jsonValue);';
        $html .= '}';
        $html .= '});';
        $html .= '// Also sync before save button click';
        $html .= '$(".save").on("click", function() {';
        $html .= 'var jsonValue = $("#' . $fieldId . '_json").val();';
        $html .= 'if (jsonValue) {';
        $html .= '$("#' . $fieldId . '").val(jsonValue);';
        $html .= '}';
        $html .= '});';
        $html .= '// Sync on any attribute change';
        $html .= '$(document).on("change", ".attribute-select", function() {';
        $html .= 'setTimeout(function() {';
        $html .= 'var jsonValue = $("#' . $fieldId . '_json").val();';
        $html .= 'if (jsonValue) {';
        $html .= '$("#' . $fieldId . '").val(jsonValue);';
        $html .= '}';
        $html .= '}, 100);';
        $html .= '});';
        $html .= '}';
        $html .= '});';
        $html .= '});';
        $html .= '</script>';

        $html .= '</div>';

        return $html;
    }
}
