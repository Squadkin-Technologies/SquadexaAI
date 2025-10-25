<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\AiProduct;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Squadkin\SquadexaAI\Service\AttributeService;

class AttributeDetails extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var AttributeService
     */
    private $attributeService;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param AttributeService $attributeService
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        AttributeService $attributeService
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->attributeService = $attributeService;
    }

    /**
     * Get attribute details via AJAX
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        
        try {
            $attributeCode = $this->getRequest()->getParam('attribute_code');
            
            if (!$attributeCode) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Attribute code is required.')
                ]);
            }
            
            $attributeDetails = $this->attributeService->getAttributeDetails($attributeCode);
            
            if (!$attributeDetails) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Attribute not found.')
                ]);
            }
            
            return $result->setData([
                'success' => true,
                'attribute' => $attributeDetails
            ]);
            
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }

    /**
     * Check if user has permission to access this controller
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::AiProduct');
    }
} 