<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\Messages;

use Magento\Backend\Block\Template;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Block to display HTML success messages
 */
class HtmlSuccessMessage extends Template
{
    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * @param Template\Context $context
     * @param SessionManagerInterface $session
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SessionManagerInterface $session,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->session = $session;
    }

    /**
     * Get HTML message from session
     *
     * @return string|null
     */
    public function getHtmlMessage(): ?string
    {
        $message = $this->session->getData('squadexa_html_success_message');
        $this->session->unsetData('squadexa_html_success_message');
        return $message;
    }

    /**
     * Check if message exists
     *
     * @return bool
     */
    public function hasMessage(): bool
    {
        return (bool)$this->session->getData('squadexa_html_success_message');
    }
}

