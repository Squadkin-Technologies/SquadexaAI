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
     * @param Context $context
     * @param AiProductRepositoryInterface $aiProductRepository
     */
    public function __construct(
        Context $context,
        AiProductRepositoryInterface $aiProductRepository
    ) {
        $this->context = $context;
        $this->aiProductRepository = $aiProductRepository;
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
} 