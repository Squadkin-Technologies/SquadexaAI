<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\AiProduct;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Api\Data\AiProductInterfaceFactory;
use Squadkin\SquadexaAI\Service\CustomAttributeProcessor;

class Save extends Action
{
    /**
     * @var AiProductRepositoryInterface
     */
    private $aiProductRepository;

    /**
     * @var AiProductInterfaceFactory
     */
    private $aiProductFactory;

    /**
     * @var CustomAttributeProcessor
     */
    private $customAttributeProcessor;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * Constructor
     *
     * @param Context $context
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param AiProductInterfaceFactory $aiProductFactory
     * @param CustomAttributeProcessor $customAttributeProcessor
     * @param Json $jsonSerializer
     */
    public function __construct(
        Context $context,
        AiProductRepositoryInterface $aiProductRepository,
        AiProductInterfaceFactory $aiProductFactory,
        CustomAttributeProcessor $customAttributeProcessor,
        Json $jsonSerializer
    ) {
        parent::__construct($context);
        $this->aiProductRepository = $aiProductRepository;
        $this->aiProductFactory = $aiProductFactory;
        $this->customAttributeProcessor = $customAttributeProcessor;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Save action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        $id = $this->getRequest()->getParam('aiproduct_id');

        try {
            if ($id) {
                $aiProduct = $this->aiProductRepository->get($id);
            } else {
                $aiProduct = $this->aiProductFactory->create();
            }

            // Set basic fields
            $aiProduct->setProductName($data['product_name'] ?? '');
            $aiProduct->setPrimaryKeywords($data['primary_keywords'] ?? '');
            $aiProduct->setSecondaryKeywords($data['secondary_keywords'] ?? '');
            $aiProduct->setDescription($data['description'] ?? '');
            $aiProduct->setShortDescription($data['short_description'] ?? '');
            $aiProduct->setMetaTitle($data['meta_title'] ?? '');
            $aiProduct->setMetaDescription($data['meta_description'] ?? '');
            $aiProduct->setUpc($data['upc'] ?? '');
            
            // Handle include_pricing checkbox
            $includePricing = isset($data['include_pricing']) && $data['include_pricing'] == '1';
            // Note: include_pricing is not stored in database, it's only used during generation
            
            // Process JSON fields - convert newline/comma-separated strings to JSON arrays
            // key_features, how_to_use, ingredients: newline-separated
            $jsonArrayFields = ['key_features', 'how_to_use', 'ingredients'];
            foreach ($jsonArrayFields as $field) {
                if (isset($data[$field]) && !empty(trim($data[$field]))) {
                    // Split by newlines and filter empty lines
                    $lines = array_filter(array_map('trim', explode("\n", $data[$field])));
                    if (!empty($lines)) {
                        $aiProduct->setData($field, $this->jsonSerializer->serialize(array_values($lines)));
                    } else {
                        $aiProduct->setData($field, null);
                    }
                } else {
                    $aiProduct->setData($field, null);
                }
            }
            
            // keywords: comma-separated
            if (isset($data['keywords']) && !empty(trim($data['keywords']))) {
                $keywords = array_filter(array_map('trim', explode(',', $data['keywords'])));
                if (!empty($keywords)) {
                    $aiProduct->setKeywords($this->jsonSerializer->serialize(array_values($keywords)));
                } else {
                    $aiProduct->setKeywords(null);
                }
            } else {
                $aiProduct->setKeywords(null);
            }
            
            // Set pricing fields
            $aiProduct->setPricingUsdMin(!empty($data['pricing_usd_min']) ? (float)$data['pricing_usd_min'] : null);
            $aiProduct->setPricingUsdMax(!empty($data['pricing_usd_max']) ? (float)$data['pricing_usd_max'] : null);
            $aiProduct->setPricingCadMin(!empty($data['pricing_cad_min']) ? (float)$data['pricing_cad_min'] : null);
            $aiProduct->setPricingCadMax(!empty($data['pricing_cad_max']) ? (float)$data['pricing_cad_max'] : null);

            $this->aiProductRepository->save($aiProduct);
            $this->messageManager->addSuccessMessage(__('AI Product has been saved.'));

            if ($this->getRequest()->getParam('back')) {
                $resultRedirect->setPath('*/*/edit', ['aiproduct_id' => $aiProduct->getAiproductId()]);
                return $resultRedirect;
            }

            $resultRedirect->setPath('*/*/');
            return $resultRedirect;

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while saving the AI product.'));
        }

        $this->_getSession()->setFormData($data);
        $resultRedirect->setPath('*/*/edit', ['aiproduct_id' => $this->getRequest()->getParam('aiproduct_id')]);
        return $resultRedirect;
    }

    /**
     * Check if user has permission to access this controller
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::AiProduct_save');
    }
} 