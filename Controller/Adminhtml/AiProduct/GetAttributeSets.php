<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\AiProduct;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Get Attribute Sets for AJAX
 */
class GetAttributeSets extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Squadkin_SquadexaAI::AiProduct';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var AttributeSetRepositoryInterface
     */
    private $attributeSetRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AttributeSetRepositoryInterface $attributeSetRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Execute action
     *
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $attributeSets = $this->attributeSetRepository->getList($searchCriteria);
            
            $options = [];
            foreach ($attributeSets->getItems() as $attributeSet) {
                $options[] = [
                    'value' => $attributeSet->getAttributeSetId(),
                    'label' => $attributeSet->getAttributeSetName()
                ];
            }

            return $result->setData([
                'success' => true,
                'attribute_sets' => $options
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

