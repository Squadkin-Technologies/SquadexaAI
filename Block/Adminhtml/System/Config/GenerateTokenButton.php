<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;

class GenerateTokenButton extends Field
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param Context $context
     * @param UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        UrlInterface $urlBuilder,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $data);
    }

    /**
     * Render generate token button
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $buttonId = 'generate_access_token_btn';
        $generateUrl = $this->urlBuilder->getUrl('squadkin_squadexaai/auth/generateToken');
        
        $html = '<div id="generate-token-container">';
        $html .= '<button type="button" id="' . $buttonId . '" class="action-primary" onclick="generateAccessToken()">';
        $html .= '<span>Generate API Key</span>';
        $html .= '</button>';
        $html .= '<div id="token-generation-result" style="margin-top: 10px;"></div>';
        $html .= '</div>';

        $html .= '<script type="text/javascript">
        require(["jquery", "mage/url"], function($, urlBuilder) {
            window.generateAccessToken = function() {
                var username = $("#squadexaiproductcreator_authentication_username").val();
                var password = $("#squadexaiproductcreator_authentication_password").val();
                var resultDiv = $("#token-generation-result");
                var button = $("#' . $buttonId . '");
                
                if (!username || !password) {
                    resultDiv.html("<div class=\"message message-error\">Please enter both username and password.</div>");
                    return;
                }
                
                button.prop("disabled", true).find("span").text("Generating...");
                resultDiv.html("<div class=\"message message-notice\">Step 1: Logging in... Step 2: Generating API key...</div>");
                
                $.ajax({
                    url: "' . $generateUrl . '",
                    type: "POST",
                    data: {
                        username: username,
                        password: password,
                        form_key: window.FORM_KEY
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            // Populate both access token and API key fields
                            if (response.access_token) {
                                $("#squadexaiproductcreator_authentication_access_token").val(response.access_token);
                            }
                            if (response.api_key) {
                                $("#squadexaiproductcreator_authentication_api_key").val(response.api_key);
                            }
                            
                            // Update token created timestamp
                            if (response.token_created) {
                                $("#squadexaiproductcreator_authentication_token_created").val(response.token_created);
                            }
                            
                            resultDiv.html("<div class=\"message message-success\">" + response.message + "</div>");
                        } else {
                            resultDiv.html("<div class=\"message message-error\">Error: " + response.message + "</div>");
                        }
                    },
                    error: function(xhr, status, error) {
                        resultDiv.html("<div class=\"message message-error\">Error generating token: " + error + "</div>");
                    },
                    complete: function() {
                        button.prop("disabled", false).find("span").text("Generate API Key");
                    }
                });
            };
        });
        </script>';

        return $html;
    }
}
