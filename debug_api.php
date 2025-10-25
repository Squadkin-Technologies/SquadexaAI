<?php
/**
 * Debug script to test Squadexa AI API connection
 * Run this from the Magento root directory: php app/code/Squadkin/SquadexaAI/debug_api.php
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

try {
    echo "=== Squadexa AI API Debug Test ===\n\n";
    
    // Get the API service
    $apiService = $objectManager->get(\Squadkin\SquadexaAI\Service\SquadexaApiService::class);
    
    echo "1. Testing API Base URL: " . $apiService->getApiBaseUrl() . "\n";
    echo "2. Testing Redirect URL: " . $apiService->getRedirectUrl() . "\n";
    
    $apiKey = $apiService->getApiKey();
    if (empty($apiKey)) {
        echo "3. API Key: NOT CONFIGURED\n";
        echo "   Please configure the API key in System Configuration.\n";
    } else {
        echo "3. API Key: " . substr($apiKey, 0, 10) . "... (length: " . strlen($apiKey) . ")\n";
        
        echo "\n4. Testing Health Check (no auth required):\n";
        try {
            $healthResult = $apiService->checkHealth();
            echo "   ✓ Health check successful\n";
            echo "   Response: " . json_encode($healthResult, JSON_PRETTY_PRINT) . "\n";
        } catch (Exception $e) {
            echo "   ✗ Health check failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n5. Testing API Key Validation:\n";
        try {
            $isValid = $apiService->validateApiKey();
            if ($isValid) {
                echo "   ✓ API key is valid\n";
                
                echo "\n6. Testing Account Information:\n";
                try {
                    $accountInfo = $apiService->getAccountInformation();
                    echo "   ✓ Account information retrieved\n";
                    echo "   API Key Valid: " . ($accountInfo['api_key_valid'] ? 'Yes' : 'No') . "\n";
                    if (isset($accountInfo['user_profile'])) {
                        echo "   User Profile: Available\n";
                    }
                    if (isset($accountInfo['usage_stats'])) {
                        echo "   Usage Stats: Available\n";
                    }
                    if (isset($accountInfo['subscription_plan'])) {
                        echo "   Subscription Plan: Available\n";
                    }
                } catch (Exception $e) {
                    echo "   ✗ Account information failed: " . $e->getMessage() . "\n";
                }
            } else {
                echo "   ✗ API key is invalid\n";
            }
        } catch (Exception $e) {
            echo "   ✗ API key validation failed: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Debug Test Complete ===\n";
    
} catch (Exception $e) {
    echo "Debug script error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
