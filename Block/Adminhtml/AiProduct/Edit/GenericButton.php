<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\AiProduct\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;

class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var AiProductRepositoryInterface
     */
    protected $aiProductRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        AiProductRepositoryInterface $aiProductRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->context = $context;
        $this->aiProductRepository = $aiProductRepository;
        $this->logger = $logger;
    }

    /**
     * Return AI Product ID
     *
     * @return int|null
     */
    public function getAiProductId()
    {
        try {
            return $this->aiProductRepository->get(
                $this->context->getRequest()->getParam('aiproduct_id')
            )->getAiproductId();
        } catch (NoSuchEntityException $e) {
            $this->logger->debug('AI Product ID not found: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }

    /**
     * Check if AI product is created in Magento
     *
     * @return bool
     */
    public function isCreatedInMagento(): bool
    {
        try {
            $aiproductId = $this->context->getRequest()->getParam('aiproduct_id');
            if ($aiproductId) {
                $aiProduct = $this->aiProductRepository->get($aiproductId);
                return (bool)$aiProduct->getIsCreatedInMagento();
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->debug('Error checking if AI product is created in Magento: ' . $e->getMessage());
        }
        return false;
    }
}
