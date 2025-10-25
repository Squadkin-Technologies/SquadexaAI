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
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;

class Delete extends Action
{
    /**
     * @var AiProductRepositoryInterface
     */
    private $aiProductRepository;

    /**
     * Constructor
     *
     * @param Context $context
     * @param AiProductRepositoryInterface $aiProductRepository
     */
    public function __construct(
        Context $context,
        AiProductRepositoryInterface $aiProductRepository
    ) {
        parent::__construct($context);
        $this->aiProductRepository = $aiProductRepository;
    }

    /**
     * Delete action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $id = $this->getRequest()->getParam('aiproduct_id');

        if ($id) {
            try {
                $this->aiProductRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('AI Product has been deleted.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while deleting the AI product.'));
            }
        }

        $resultRedirect->setPath('*/*/');
        return $resultRedirect;
    }

    /**
     * Check if user has permission to access this controller
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::AiProduct_delete');
    }
} 