define([
    'jquery',
    'mage/url',
    'mage/translate'
], function ($, urlBuilder, $t) {
    'use strict';

    return function (config, element) {
        var $element = $(element);
        var $apiKeyField = $element.find('input[name*="api_key"]');
        var $validateButton = $('<button type="button" class="action-secondary" id="validate-api-key">' + $t('Validate API Key') + '</button>');
        var $statusDiv = $('<div id="api-key-status" style="margin-top: 10px;"></div>');
        
        // Add validate button after API key field
        $apiKeyField.after($validateButton);
        $validateButton.after($statusDiv);
        
        $validateButton.on('click', function () {
            var apiKey = $apiKeyField.val();
            
            if (!apiKey) {
                showStatus('error', $t('Please enter an API key first.'));
                return;
            }
            
            showStatus('loading', $t('Validating API key...'));
            
            $.ajax({
                url: urlBuilder.build('squadkin_squadexaai/auth/validateApiKey'),
                type: 'POST',
                data: {
                    api_key: apiKey,
                    form_key: $('input[name="form_key"]').val()
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showStatus('success', $t('API key is valid!'));
                        if (response.account_info) {
                            showAccountInfo(response.account_info);
                        }
                    } else {
                        showStatus('error', response.message || $t('API key validation failed.'));
                    }
                },
                error: function () {
                    showStatus('error', $t('An error occurred while validating the API key.'));
                }
            });
        });
        
        function showStatus(type, message)
        {
            var statusClass = type === 'success' ? 'success' : (type === 'error' ? 'error' : 'loading');
            var icon = type === 'success' ? '✓' : (type === 'error' ? '✗' : '⏳');
            
            $statusDiv.html(
                '<div class="message message-' + statusClass + ' ' + statusClass + '">' +
                '<div data-ui-id="messages-message-' + statusClass + '">' +
                icon + ' ' + message +
                '</div></div>'
            );
        }
        
        function showAccountInfo(accountInfo)
        {
            if (accountInfo.api_key_valid && accountInfo.usage_stats) {
                var usageStats = accountInfo.usage_stats;
                var subscriptionPlan = accountInfo.subscription_plan || {};
                
                var infoHtml = '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 10px;">';
                infoHtml += '<h4>📊 Account Information</h4>';
                infoHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">';
                
                // Credit Wallet
                var creditsRemaining = subscriptionPlan.credits_remaining || subscriptionPlan.calls_remaining || 0;
                var creditsUsed = subscriptionPlan.credits_used || 0;
                var creditsTotal = creditsRemaining + creditsUsed;
                var percentage = creditsTotal > 0 ? (creditsUsed / creditsTotal) * 100 : 0;

                infoHtml += '<div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #dee2e6;">';
                infoHtml += '<h5>⚡ Credit Wallet</h5>';
                infoHtml += '<div style="background: #e9ecef; border-radius: 10px; height: 20px; overflow: hidden; margin: 10px 0;">';
                infoHtml += '<div style="background: linear-gradient(90deg, #28a745, #20c997); height: 100%; width: ' + percentage + '%;"></div>';
                infoHtml += '</div>';
                infoHtml += '<p><strong>' + creditsRemaining + '</strong> credits remaining</p>';
                infoHtml += '</div>';

                // Current Plan
                var planName = subscriptionPlan.name || 'FREE';
                infoHtml += '<div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #dee2e6;">';
                infoHtml += '<h5>📊 Current Plan</h5>';
                infoHtml += '<p><strong>' + planName + '</strong></p>';
                infoHtml += '<p>Wallet-based credits</p>';
                infoHtml += '</div>';
                
                // Today's Activity
                infoHtml += '<div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #dee2e6;">';
                infoHtml += '<h5>📈 Today\'s Activity</h5>';
                infoHtml += '<p><strong>' + (usageStats.descriptions_today || 0) + '</strong> Product Descriptions</p>';
                infoHtml += '<p><strong>' + (usageStats.ai_humanizer_today || 0) + '</strong> AI Humanizer</p>';
                infoHtml += '<p><strong>' + (usageStats.ai_detector_today || 0) + '</strong> AI Detector</p>';
                infoHtml += '</div>';
                
                infoHtml += '</div>';
                infoHtml += '</div>';
                
                $statusDiv.append(infoHtml);
            }
        }
    };
});
