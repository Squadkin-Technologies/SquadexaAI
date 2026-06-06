<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * phpcs:ignoreFile Generic.Files.LineLength -- Config block with embedded HTML/JS
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

        $html = '<div id="generate-token-container" style="margin: 20px 0;">';
        $html .= '<button type="button" id="' . $buttonId . '" class="action-primary" onclick="generateAccessToken()" style="display: inline-flex; align-items: center; gap: 8px;">';
        $html .= '<span>🚀</span>';
        $html .= '<span>Generate API Key</span>';
        $html .= '</button>';
        $html .= '<div id="token-generation-result" style="margin-top: 20px;"></div>';
        $html .= '</div>';

        $html .= '<style>
        #token-generation-result .step-indicator {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            align-items: center;
        }
        #token-generation-result .step {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #666;
        }
        #token-generation-result .step.active {
            color: #333;
            font-weight: 600;
        }
        #token-generation-result .step.done {
            color: #28a745;
        }
        #token-generation-result .step-circle {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            color: #666;
        }
        #token-generation-result .step.active .step-circle {
            background: #4f46e5;
            color: white;
        }
        #token-generation-result .step.done .step-circle {
            background: #28a745;
            color: white;
        }
        #token-generation-result .success-card {
            background: #f0f9ff;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        #token-generation-result .success-card .api-key-display {
            background: white;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
            font-family: monospace;
            word-break: break-all;
            font-size: 12px;
            color: #666;
        }
        #token-generation-result .copy-button {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 8px;
        }
        #token-generation-result .copy-button:hover {
            background: #3c35d0;
        }
        #token-generation-result .credit-info {
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 3px;
            font-size: 13px;
            color: #666;
        }
        #token-generation-result .error-card {
            background: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        #token-generation-result .email-verify-card {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        #token-generation-result .topup-card {
            background: #fff5f5;
            border-left: 4px solid #e11d48;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        #token-generation-result a.cta-link {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
        }
        #token-generation-result a.cta-link:hover {
            text-decoration: underline;
        }
        </style>';

        $html .= '<script type="text/javascript">
        require(["jquery", "mage/url"], function($, urlBuilder) {
            window.generateAccessToken = function() {
                var username = $("#squadexaiproductcreator_authentication_username").val();
                var password = $("#squadexaiproductcreator_authentication_password").val();
                var resultDiv = $("#token-generation-result");
                var button = $("#' . $buttonId . '");

                if (!username || !password) {
                    resultDiv.html("<div class=\"error-card\"><strong>⚠️ Error:</strong> Please enter both email and password.</div>");
                    return;
                }

                button.prop("disabled", true);
                var steps = "<div class=\"step-indicator\">" +
                    "<div class=\"step active\"><div class=\"step-circle\">1</div> <span>Logging in...</span></div>" +
                    "<div class=\"step\"><div class=\"step-circle\">2</div> <span>Generating key...</span></div>" +
                    "</div>";
                resultDiv.html(steps);

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
                        if (response.success && response.api_key) {
                            if (response.api_key) {
                                $("#squadexaiproductcreator_authentication_api_key").val(response.api_key);
                            }
                            if (response.api_key_created) {
                                $("#squadexaiproductcreator_authentication_token_created").val(response.api_key_created);
                            }

                            var keyDisplay = response.api_key.substring(0, 6) + "..." + response.api_key.substring(response.api_key.length - 4);
                            var creditHtml = "";
                            if (response.credits_remaining !== undefined) {
                                creditHtml = "<div class=\"credit-info\">💳 Credits remaining: " + response.credits_remaining + "</div>";
                            }

                            var successHtml = "<div class=\"step-indicator\">" +
                                "<div class=\"step done\"><div class=\"step-circle\">✓</div> <span>Login successful</span></div>" +
                                "<div class=\"step done\"><div class=\"step-circle\">✓</div> <span>API key generated</span></div>" +
                                "</div>" +
                                "<div class=\"success-card\">" +
                                "<div style=\"color: #10b981; font-weight: 800; margin-bottom: 12px; font-size: 16px; display: flex; align-items: center; gap: 8px;\">✅ API Key Generated Successfully!</div>" +
                                "<div style=\"font-size: 14px; margin-bottom: 14px; color: #666; line-height: 1.5;\">Your permanent API key has been generated and secured. Keep this key safe and never share it.</div>" +
                                "<div style=\"background: white; padding: 12px 14px; border-radius: 8px; margin-bottom: 12px; border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 10px;\">" +
                                "<span style=\"color: #999; font-size: 20px;\">🔑</span>" +
                                "<div class=\"api-key-display\" id=\"api-key-masked\" style=\"margin: 0; padding: 0; border: none; background: transparent; color: #4f46e5; font-weight: 600;\">" + keyDisplay + "</div>" +
                                "<button type=\"button\" class=\"copy-button\" onclick=\"copyToClipboard()\" style=\"margin: 0; padding: 6px 12px; font-size: 12px; white-space: nowrap;\">📋 Copy</button>" +
                                "</div>" +
                                creditHtml +
                                "<div style=\"margin-top: 12px; padding: 12px; background: #f0fdf4; border: 1px solid #d1fae5; border-radius: 8px; font-size: 12px; color: #047857; line-height: 1.5;\">💾 The key has been saved automatically. Click <strong>Save Config</strong> at the top of the page to finalize your configuration.</div>" +
                                "</div>";
                            resultDiv.html(successHtml);
                        } else if (response.error_code === "EMAIL_NOT_VERIFIED") {
                            resultDiv.html("<div class=\"email-verify-card\">" +
                                "<div style=\"font-weight: 800; margin-bottom: 12px; font-size: 15px; display: flex; align-items: center; gap: 8px;\">📧 Email Verification Required</div>" +
                                "<div style=\"margin-bottom: 12px; line-height: 1.6;\">Your email address has not been verified yet. Please follow these steps:</div>" +
                                "<ol style=\"margin: 0 0 14px 25px; padding-left: 0; line-height: 1.8;\">" +
                                "<li>Check your inbox for a verification email from Squadexa AI</li>" +
                                "<li>Click the verification link in the email</li>" +
                                "<li>Return here and click \"Generate API Key\" again</li>" +
                                "</ol>" +
                                "<a href=\"https://www.squadexa.ai/\" target=\"_blank\" class=\"cta-link\" style=\"display: inline-flex; align-items: center; gap: 6px; color: #b45309; font-weight: 600; text-decoration: none; padding: 8px 12px; background: rgba(180, 83, 9, 0.1); border-radius: 6px;\">🌐 Visit Squadexa AI Portal</a>" +
                                "</div>");
                        } else if (response.error === "insufficient_credits") {
                            var ctaLink = response.cta_link || "https://www.squadexa.ai/account#credits";
                            resultDiv.html("<div class=\"topup-card\">" +
                                "<div style=\"font-weight: 800; margin-bottom: 12px; font-size: 15px; display: flex; align-items: center; gap: 8px;\">💳 Credits Required</div>" +
                                "<div style=\"margin-bottom: 14px; line-height: 1.6;\">" + (response.message || "Your API key has been generated successfully, but you need credits to use our AI tools.") + "</div>" +
                                "<a href=\"" + ctaLink + "\" target=\"_blank\" style=\"display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;\">💰 Top Up Credits Now</a>" +
                                "</div>");
                            if (response.api_key) {
                                $("#squadexaiproductcreator_authentication_api_key").val(response.api_key);
                            }
                        } else {
                            resultDiv.html("<div class=\"error-card\">" +
                                "<div style=\"font-weight: 800; margin-bottom: 8px; font-size: 15px; display: flex; align-items: center; gap: 8px;\">❌ Error</div>" +
                                "<div style=\"line-height: 1.6;\">" + (response.message || response.error || "Failed to generate API key. Please try again or contact support.") + "</div>" +
                                "</div>");
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMsg = "Error generating token: " + error;
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        resultDiv.html("<div class=\"error-card\">" +
                            "<div style=\"font-weight: bold; margin-bottom: 8px;\">❌ Error</div>" +
                            errorMsg +
                            "</div>");
                    },
                    complete: function() {
                        button.prop("disabled", false);
                    }
                });
            };

            window.copyToClipboard = function() {
                var apiKey = $("#squadexaiproductcreator_authentication_api_key").val();
                if (apiKey) {
                    navigator.clipboard.writeText(apiKey).then(function() {
                        alert("API Key copied to clipboard!");
                    });
                }
            };
        });
        </script>';

        return $html;
    }
}
