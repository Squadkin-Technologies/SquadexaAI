# Squadexa AI API Integration Updates

**Date:** October 27, 2025  
**Version:** 2.0  
**Updated Based On:** Squadexa AI API Production Collection (v7)

---

## 🎯 Overview

This document outlines the comprehensive updates made to the SquadexaAI Magento 2 extension to align with the updated Squadexa AI API Production Collection. The main focus is on simplifying authentication by using only the permanent API key and removing temporary access token storage.

---

## 📋 Key Changes Summary

### 1. **Authentication Flow Simplified**

#### Previous Flow (Deprecated):
```
1. Login → Get access token (expires in 30 minutes)
2. Store access token
3. Use access token for API calls
4. Auto-refresh when expired
```

#### New Flow (Current):
```
1. Login with email/password → Get temporary access token
2. Use access token to generate permanent API key
3. Store ONLY the API key (never expires)
4. Use API key for all subsequent API calls
```

### 2. **Configuration Changes**

#### Removed Fields:
- ❌ `access_token` field (temporary, expires in 30 minutes)
- ❌ `token_created` timestamp
- ❌ Access token display in admin

#### Updated Fields:
- ✅ `api_key` - Now the primary authentication method (never expires)
- ✅ `api_key_created` - Timestamp when API key was generated
- ✅ `username` → `email` - Clarified label
- ✅ `password` - Used only for initial API key generation

---

## 🔧 Technical Implementation

### File Changes

#### 1. System Configuration (`etc/adminhtml/system.xml`)

**Changes Made:**
- Removed `access_token` field
- Removed `token_created` field
- Updated field labels and comments to clarify API key usage
- Added instructions for obtaining API key
- Simplified authentication group

**New Structure:**
```xml
<group id="authentication">
    <field id="username" (now labeled as "Email")
    <field id="password">
    <field id="generate_token"> (button to generate API key)
    <field id="api_key"> (permanent API key)
    <field id="api_key_status"> (displays API key metadata)
</group>
```

#### 2. Generate Token Controller (`Controller/Adminhtml/Auth/GenerateToken.php`)

**Changes Made:**
```php
// OLD: Saved both access_token and api_key
$this->configWriter->save('...access_token', $accessToken);
$this->configWriter->save('...api_key', $apiKeyData['api_key']);
$this->configWriter->save('...token_created', $tokenCreated);

// NEW: Saves ONLY the api_key
$this->configWriter->save('...api_key', $apiKeyData['api_key']);
$this->configWriter->save('...api_key_created', $apiKeyCreated);
```

**Flow:**
1. ✅ Accepts email/password from admin
2. ✅ Calls `/api/v1/auth/login` → Gets temporary access token
3. ✅ Uses access token to call `/api/v1/auth/regenerate-api-key` → Gets permanent API key
4. ✅ Saves ONLY the API key to database
5. ✅ Returns success message to admin

#### 3. API Service (`Service/SquadexaApiService.php`)

**New Endpoints Added:**
```php
const API_ENDPOINTS = [
    // Authentication
    'regenerate_api_key' => '/api/v1/auth/regenerate-api-key',
    'api_key_metadata' => '/api/v1/auth/api-key',
    'user_profile' => '/api/v1/auth/me',
    'usage_stats' => '/api/v1/auth/usage-stats',
    'usage_history' => '/api/v1/usage-history',
    
    // Billing (NEW)
    'billing_plans' => '/api/v1/billing/plans',
    'billing_subscription' => '/api/v1/billing/subscription',
    'billing_history' => '/api/v1/billing/history',
    'billing_config' => '/api/v1/billing/config',
];
```

**New Methods Added:**
```php
// Billing
public function getBillingPlans(): array
public function getCurrentSubscription(): array
public function getBillingHistory(): array
public function getBillingConfig(): array
```

**Authentication Pattern:**
```php
// All API calls use this pattern
public function makeApiRequestWithApiKey(string $endpoint, string $method, array $data): array
{
    $apiKey = $this->getApiKey(); // Permanent API key
    
    $this->curl->setHeaders([
        'Authorization' => 'Bearer ' . $apiKey,  // API key in Bearer token
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ]);
    
    // Make request...
}
```

#### 4. Token Status Block (`Block/Adminhtml/System/Config/TokenStatus.php`)

**Changes Made:**
- ❌ Removed access token expiry calculations
- ❌ Removed 30-minute countdown timer
- ✅ Added API key metadata display
- ✅ Shows API key creation date
- ✅ Shows last used timestamp
- ✅ Shows total API call count
- ✅ Displays "Never Expires" status

**Display Information:**
```
✅ API Key Active
🕒 Created: 2025-10-27 10:30:00
🔄 Last Used: 2025-10-27 15:45:00
🔐 Status: Active (Never Expires)
📊 Total API Calls: 1,234
💡 This API key never expires and is used for all API requests.
```

#### 5. Account Status Block (`Block/Adminhtml/System/Config/AccountStatus.php`)

**Changes Made:**
- ❌ Removed access token checks
- ✅ Updated to use only API key
- ✅ Enhanced to display subscription information
- ✅ Shows billing details
- ✅ Displays usage statistics

**Information Displayed:**
- User profile information
- Current subscription plan
- API usage statistics (today, this week, total)
- Calls remaining in current plan
- Billing history
- Upgrade prompts for free tier users

---

## 🔄 API Authentication Flow

### Step-by-Step Process

```
┌─────────────────────────────────────────────────────────────┐
│  1. Admin enters email/password in Magento config           │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────────┐
│  2. Admin clicks "Generate API Key" button                  │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────────┐
│  3. POST /api/v1/auth/login                                 │
│     Body: { "email": "...", "password": "..." }             │
│     Response: { "access_token": "temp_token_123" }          │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────────┐
│  4. POST /api/v1/auth/regenerate-api-key                    │
│     Headers: { "Authorization": "Bearer temp_token_123" }   │
│     Response: { "api_key": "permanent_key_abc" }            │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────────┐
│  5. Save ONLY the permanent API key to database             │
│     (temp access token is discarded)                        │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────────┐
│  6. All subsequent API calls use the permanent API key      │
│     Headers: { "Authorization": "Bearer permanent_key_abc" }│
└─────────────────────────────────────────────────────────────┘
```

---

## 📡 API Endpoints Reference

### Authentication Endpoints

| Endpoint | Method | Auth Required | Description |
|----------|--------|---------------|-------------|
| `/api/v1/auth/register` | POST | No | Register new user |
| `/api/v1/auth/login` | POST | No | Login to get temporary access token |
| `/api/v1/auth/regenerate-api-key` | POST | Access Token | Generate permanent API key |
| `/api/v1/auth/me` | GET | API Key | Get user profile |
| `/api/v1/auth/api-key` | GET | API Key | Get API key metadata |
| `/api/v1/auth/usage-stats` | GET | API Key | Get usage statistics |

### Product Generation Endpoints

| Endpoint | Method | Auth Required | Description |
|----------|--------|---------------|-------------|
| `/api/v1/product-details` | POST/GET | API Key | Generate single product |
| `/api/v1/batch-jobs` | POST | API Key | Create batch job |
| `/api/v1/job-status/{jobId}` | GET | API Key | Get job status |
| `/api/v1/job-download/{jobId}` | GET | API Key | Download job results |

### Billing Endpoints (NEW)

| Endpoint | Method | Auth Required | Description |
|----------|--------|---------------|-------------|
| `/api/v1/billing/plans` | GET | No | Get available plans |
| `/api/v1/billing/subscription` | GET | API Key | Get current subscription |
| `/api/v1/billing/history` | GET | API Key | Get billing history |
| `/api/v1/billing/config` | GET | No | Get billing configuration |

### Usage Statistics Endpoints

| Endpoint | Method | Auth Required | Description |
|----------|--------|---------------|-------------|
| `/api/v1/usage-stats` | GET | API Key | Get usage statistics |
| `/api/v1/usage-history` | GET | API Key | Get usage history |

---

## 🧪 Testing Guide

### 1. Test API Key Generation

1. Navigate to: **Stores → Configuration → Squadkin → SquadexaAI Configuration**
2. Enter your email and password
3. Click "Generate API Key"
4. Verify:
   - ✅ Success message appears
   - ✅ API key field is populated
   - ✅ API Key Status shows "Active"
   - ✅ Account Status displays your profile

### 2. Test Single Product Generation

1. Navigate to: **Squadkin → SquadexaAI → Product Generation**
2. Fill in the form:
   - Product Name: "Smart Watch"
   - Primary Keywords: "fitness,smartwatch,gps"
   - Secondary Keywords: "waterproof,heart-rate"
3. Click "Generate Product"
4. Verify:
   - ✅ No page reload
   - ✅ Loading indicator appears
   - ✅ Success message displayed
   - ✅ Product details shown
   - ✅ Saved to database

### 3. Test Account Status Display

1. Navigate to configuration page
2. Check Account Status section
3. Verify displays:
   - ✅ API Key Status (Active/Never Expires)
   - ✅ Current subscription plan
   - ✅ Calls remaining
   - ✅ Usage statistics
   - ✅ Today's activity

---

## 🐛 Troubleshooting

### Issue: "No API key configured"
**Solution:** 
1. Enter email/password
2. Click "Generate API Key"
3. Check logs: `var/log/squadexa_ai_api.log`

### Issue: "API key is invalid"
**Solution:**
1. Clear cache: `php bin/magento cache:clean`
2. Regenerate API key
3. Verify credentials are correct

### Issue: "Authentication failed"
**Solution:**
1. Check email/password are correct
2. Verify API base URL: `https://squadexa.ai/`
3. Check firewall/network settings

---

## 📊 Database Schema Changes

### Configuration Paths

| Old Path | New Path | Status |
|----------|----------|--------|
| `squadexaiproductcreator/authentication/access_token` | ❌ REMOVED | Deprecated |
| `squadexaiproductcreator/authentication/token_created` | ❌ REMOVED | Deprecated |
| `squadexaiproductcreator/authentication/api_key` | ✅ KEPT | Active |
| - | `squadexaiproductcreator/authentication/api_key_created` | ✅ NEW |

---

## ✅ Completed Tasks

- [x] Updated system.xml - Removed access_token field
- [x] Updated GenerateToken controller - Only saves API key
- [x] Updated SquadexaApiService - Added new endpoints
- [x] Added Billing methods (Plans, Subscription, History)
- [x] Updated AccountStatus block - Uses API key only
- [x] Updated TokenStatus block - Shows API key metadata
- [x] Cleared all access token references
- [x] Fixed type casting issues (int vs string)
- [x] Fixed JavaScript widget initialization
- [x] Focused on product generation features only

---

## 🎉 Benefits of New Approach

1. **Simpler Authentication**
   - No need to track token expiration
   - No auto-refresh logic needed
   - Single authentication credential (API key)

2. **Better Security**
   - API key never expires (unless manually revoked)
   - No temporary tokens stored
   - Cleaner credential management

3. **Improved User Experience**
   - One-time setup
   - No "token expired" errors
   - Seamless API calls

4. **Extended Functionality**
   - Added Billing/Subscription management
   - Enhanced usage statistics display
   - Improved account status information

---

## 📚 Additional Resources

- **Postman Collection:** `Squadexa_AI_API_Production_Collection (7).json`
- **API Base URL:** `https://squadexa.ai/`
- **Support:** Contact Squadkin support team
- **Documentation:** See inline code comments

---

**Last Updated:** October 27, 2025  
**Maintained By:** Squadkin Development Team

