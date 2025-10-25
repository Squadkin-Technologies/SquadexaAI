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
     * Constructor
     *
     * @param Context $context
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param AiProductInterfaceFactory $aiProductFactory
     * @param CustomAttributeProcessor $customAttributeProcessor
     */
    public function __construct(
        Context $context,
        AiProductRepositoryInterface $aiProductRepository,
        AiProductInterfaceFactory $aiProductFactory,
        CustomAttributeProcessor $customAttributeProcessor
    ) {
        parent::__construct($context);
        $this->aiProductRepository = $aiProductRepository;
        $this->aiProductFactory = $aiProductFactory;
        $this->customAttributeProcessor = $customAttributeProcessor;
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

            // Set data from form
            $aiProduct->setSku($data['sku']);
            $aiProduct->setName($data['name']);
            $aiProduct->setDescription($data['description'] ?? '');
            $aiProduct->setShortDescription($data['short_description'] ?? '');
            $aiProduct->setPrice((float)($data['price'] ?? 0));
            $aiProduct->setSpecialPrice(!empty($data['special_price']) ? (float)$data['special_price'] : null);
            $aiProduct->setWeight((float)($data['weight'] ?? 0));
            $aiProduct->setQty((int)($data['qty'] ?? 0));
            $aiProduct->setCategory($data['category'] ?? '');
            $aiProduct->setStatus($data['status'] ?? 'Enabled');
            $aiProduct->setVisibility($data['visibility'] ?? 'Catalog, Search');
            $aiProduct->setType($data['type'] ?? 'simple');
            $aiProduct->setAttributeSet($data['attribute_set'] ?? 'Default');
            $aiProduct->setTaxClass($data['tax_class'] ?? '');
            $aiProduct->setMetaTitle($data['meta_title'] ?? '');
            $aiProduct->setMetaDescription($data['meta_description'] ?? '');
            $aiProduct->setMetaKeywords($data['meta_keywords'] ?? '');
            $aiProduct->setUrlKey($data['url_key'] ?? '');

            // Process custom attributes
            $customAttributes = $this->customAttributeProcessor->processCustomAttributesFromForm($data);
            
            // Validate custom attributes
            $validationErrors = $this->customAttributeProcessor->validateCustomAttributes($customAttributes);
            if (!empty($validationErrors)) {
                foreach ($validationErrors as $error) {
                    $this->messageManager->addErrorMessage($error);
                }
                throw new LocalizedException(__('Custom attributes validation failed.'));
            }
            
            // Set custom attributes
            $aiProduct->setCustomAttributes($customAttributes);

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