<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\AiProduct;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;

class Edit extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var AiProductRepositoryInterface
     */
    private $aiProductRepository;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param AiProductRepositoryInterface $aiProductRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        AiProductRepositoryInterface $aiProductRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->aiProductRepository = $aiProductRepository;
        parent::__construct($context);
    }

    /**
     * Edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('aiproduct_id');
        $resultPage = $this->resultPageFactory->create();
        
        if ($id) {
            try {
                $aiProduct = $this->aiProductRepository->get($id);
                $productName = $aiProduct->getProductName() ?: __('AI Product');
                $resultPage->getConfig()->getTitle()->prepend(__('Edit AI Product: %1', $productName));
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This AI product no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('An error occurred while loading the AI product: %1', $e->getMessage())
                );
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New AI Product'));
        }
        
        return $resultPage;
    }

    /**
     * Check if user has permission to access this controller
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::AiProduct_update');
    }
}
