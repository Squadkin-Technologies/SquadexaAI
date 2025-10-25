<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Service;

use Magento\Framework\Exception\LocalizedException;

class AiGenerationOptionsService
{
    /**
     * Available AI generation options
     */
    public const AVAILABLE_OPTIONS = [
        'name' => 'Name',
        'short_description' => 'Short Description',
        'description' => 'Description',
        'meta_title' => 'Meta Title',
        'meta_description' => 'Meta Description',
        'meta_keywords' => 'Meta Keywords',
        'ingredients' => 'Ingredients',
        'how_to_use' => 'How to Use',
        'highest_price_google' => 'Highest Price on Google Search Results',
        'lowest_price_google' => 'Lowest Price on Google Search Results'
    ];

    /**
     * Minimum number of required options
     */
    public const MIN_REQUIRED_OPTIONS = 3;

    /**
     * Get all available AI generation options
     *
     * @return array
     */
    public function getAvailableOptions(): array
    {
        return self::AVAILABLE_OPTIONS;
    }

    /**
     * Get minimum required options count
     *
     * @return int
     */
    public function getMinRequiredOptions(): int
    {
        return self::MIN_REQUIRED_OPTIONS;
    }

    /**
     * Validate selected AI generation options
     *
     * @param array $selectedOptions
     * @return array
     * @throws LocalizedException
     */
    public function validateSelectedOptions(array $selectedOptions): array
    {
        $errors = [];
        $validOptions = [];

        // Check if at least minimum required options are selected
        if (count($selectedOptions) < self::MIN_REQUIRED_OPTIONS) {
            throw new LocalizedException(
                __('Please select at least %1 AI generation options.', self::MIN_REQUIRED_OPTIONS)
            );
        }

        // Validate each selected option
        foreach ($selectedOptions as $option) {
            if (!array_key_exists($option, self::AVAILABLE_OPTIONS)) {
                $errors[] = __('Invalid AI generation option: %1', $option);
            } else {
                $validOptions[] = $option;
            }
        }

        if (!empty($errors)) {
            throw new LocalizedException(
                __('Validation errors: %1', implode(', ', $errors))
            );
        }

        return $validOptions;
    }

    /**
     * Check if specific option is selected
     *
     * @param string $option
     * @param array $selectedOptions
     * @return bool
     */
    public function isOptionSelected(string $option, array $selectedOptions): bool
    {
        return in_array($option, $selectedOptions);
    }

    /**
     * Get selected options with their labels
     *
     * @param array $selectedOptions
     * @return array
     */
    public function getSelectedOptionsWithLabels(array $selectedOptions): array
    {
        $result = [];
        foreach ($selectedOptions as $option) {
            if (array_key_exists($option, self::AVAILABLE_OPTIONS)) {
                $result[$option] = self::AVAILABLE_OPTIONS[$option];
            }
        }
        return $result;
    }

    /**
     * Filter AI response data based on selected options
     *
     * @param array $aiResponseData
     * @param array $selectedOptions
     * @return array
     */
    public function filterAiResponseBySelectedOptions(array $aiResponseData, array $selectedOptions): array
    {
        $filteredData = [];

        foreach ($aiResponseData as $productData) {
            $filteredProduct = [];
            
            // Always include required base fields
            $baseFields = ['sku'];
            foreach ($baseFields as $field) {
                if (isset($productData[$field])) {
                    $filteredProduct[$field] = $productData[$field];
                }
            }
            
            // Include only selected AI-generated fields
            foreach ($selectedOptions as $option) {
                if (isset($productData[$option])) {
                    $filteredProduct[$option] = $productData[$option];
                }
            }
            
            $filteredData[] = $filteredProduct;
        }

        return $filteredData;
    }

    /**
     * Generate default options (for backwards compatibility)
     *
     * @return array
     */
    public function getDefaultSelectedOptions(): array
    {
        return ['name', 'description', 'short_description'];
    }

    /**
     * Check if Name field is selected (required for AI generation)
     *
     * @param array $selectedOptions
     * @return bool
     */
    public function isNameFieldSelected(array $selectedOptions): bool
    {
        return $this->isOptionSelected('name', $selectedOptions);
    }

    /**
     * Prepare options for form display
     *
     * @return array
     */
    public function getOptionsForForm(): array
    {
        $options = [];
        foreach (self::AVAILABLE_OPTIONS as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label
            ];
        }
        return $options;
    }
} 