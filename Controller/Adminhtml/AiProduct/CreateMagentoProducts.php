<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\AiProduct;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Squadkin\SquadexaAI\Service\MagentoProductCreator;

class CreateMagentoProducts extends Action
{
    /**
     * @var MagentoProductCreator
     */
    private $magentoProductCreator;

    /**
     * @param Context $context
     * @param MagentoProductCreator $magentoProductCreator
     */
    public function __construct(
        Context $context,
        MagentoProductCreator $magentoProductCreator
    ) {
        parent::__construct($context);
        $this->magentoProductCreator = $magentoProductCreator;
    }

    /**
     * Create Magento products from AI products
     *
     * @return Json
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        
        try {
            $aiProductIds = $this->getRequest()->getParam('selected', []);
            
            if (empty($aiProductIds)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('No AI products selected.')
                ]);
            }
            
            $results = $this->magentoProductCreator->createMultipleMagentoProducts($aiProductIds);
            
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            foreach ($results as $result) {
                if ($result['status'] === 'success') {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errors[] = $result['error'];
                }
            }
            
            $message = __('%1 products created successfully.', $successCount);
            if ($errorCount > 0) {
                $message .= ' ' . __('%1 products failed to create.', $errorCount);
            }
            
            return $resultJson->setData([
                'success' => true,
                'message' => $message,
                'results' => $results,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors
            ]);
            
        } catch (LocalizedException $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while creating Magento products.')
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
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::AiProduct_save');
    }
} 