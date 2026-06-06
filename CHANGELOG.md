# SquadexaAI Magento 2 Extension - Changelog

All notable changes to the SquadexaAI extension are documented in this file.

## [2.0.0] - 2026-06-06

### ✨ MAJOR UPDATE - New Authentication Flow & Wallet-Based Billing

#### 🔐 Authentication & API Changes
- **Complete rewrite** of authentication flow to support new Squadexa AI backend
- **New API key format**: `pk_*` (permanent API keys) replacing temporary tokens
- **Bearer token authentication** for all API requests
- **Automatic API key generation** through secure OAuth-like flow
- **Better error handling** with HTTP 402 (insufficient_credits) and 401 (EMAIL_NOT_VERIFIED) responses

#### 💳 New Wallet-Based Credit System
- **Replaced per-call billing** with wallet-based credits
- **Credit wallet display** on Dashboard and Configuration pages
- **Real-time credit balance** fetching from API
- **Credit usage tracking** from activity history
- **Low credit warnings** when balance ≤ 5 credits
- **Redirect to top-up page** (https://www.squadexa.ai/account#credits)

#### 🎨 Dashboard Complete Redesign
- **Modern premium UI** with:
  - Gradients and shadow effects
  - Clean typography with Inter and JetBrains Mono fonts
  - Smooth animations and transitions
  - Responsive design (1080px, 768px, 640px breakpoints)

- **New KPI Cards** showing:
  - Credits Used (calculated from tool usage data)
  - Credits Remaining (from API)
  - Usage Percentage (of wallet utilization)
  - Words Generated (from activity history)
  - Sparkline charts for trend visualization

- **Enhanced Chart Rendering**:
  - SVG-based monthly usage trend chart
  - Gradient fills and responsive sizing
  - Interactive data visualization

- **Credit Wallet Banner**:
  - Prominent display of available credits
  - Current balance in large, readable format
  - "Add Credits" CTA button linked to Squadexa AI account

- **Account Status Section**:
  - User email display
  - Subscription plan info
  - Account status indicator

- **Recent Activity Table** with Advanced Filtering:
  - Date range filter (From Date / To Date)
  - Tool type filter (Product Generator, AI Humanizer, AI Detector, Plagiarism Checker)
  - Activity counter showing filtered results
  - Reset filters button
  - Time display in readable format (Mon dd, YYYY · HH:mm)

#### 📊 Data Accuracy Improvements
- **Credits Remaining**: Correctly extracted from API `credits.credit_balance`
- **Credits Used**: Calculated from actual `tool_usage[].used` data
- **Usage Percentage**: Mathematically correct calculation from credit balance
- **Configuration Page**: Displays accurate credit wallet information using `extractSubscriptionPlan()`
- **Error Message Filtering**: API error messages (e.g., "Not found in reliable online sources") no longer appear in product SKU fields

#### 🔧 Field Mapping Enhancements
- **Automatic error message filtering** from API responses
- **Intelligent SKU generation** fallback when AI generation fails
- **Proper credit calculation** from tool usage rates
- **Backward compatibility** with existing field mappings

#### 🛡️ Code Quality & Security
- **100% PHP syntax validation** (PHPCS Magento2 standard)
- **All files have copyright headers** (© 2024 Squadkin)
- **No development files** (.git removed)
- **No hardcoded credentials** or secrets
- **Proper XSS escaping** using Magento Framework Escaper
- **SQL injection prevention** with prepared statements
- **Comprehensive error handling** and logging

#### 📦 Module Structure
- **97 PHP files** with valid syntax
- **29 XML configuration files** properly structured
- **13 PHTML templates** with responsive design
- **17 JavaScript files** with interactive features
- **10 CSS files** with design tokens and components

#### 📋 Version Updates
- **module.xml**: Updated to version 2.0.0
- **composer.json**: Updated to version 2.0.0
- **Package name**: squadkintechnologies/module-squadexaai

### 🐛 Bug Fixes
- Fixed credit balance display showing 0 instead of actual available credits
- Fixed configuration page not showing credit wallet information
- Fixed credit calculations for users with purchased credits (not just signup bonus)
- Fixed SKU field showing API error messages instead of generated values
- Fixed sparkline indicators not displaying in KPI cards
- Fixed activity filters not working properly
- Fixed missing date/time information in activity records

### 🎯 Marketplace Compliance
- ✅ Follows Magento 2 coding standards
- ✅ PSR-2 compliant code structure
- ✅ Proper dependency injection
- ✅ No deprecated functions
- ✅ Comprehensive error handling
- ✅ Security best practices
- ✅ Extensible architecture

### 📚 Documentation
- Comprehensive in-code documentation
- API error handling documentation
- Dashboard feature documentation
- Field mapping configuration guide

### 🚀 Tested & Verified
- Dashboard data accuracy verified against API
- Credit calculations validated mathematically
- Filter functionality tested and working
- Responsive design tested on multiple screen sizes
- Security validation complete

---

## [1.0.0] - Previous Version

Original release with basic AI product generation features.

---

## Installation & Upgrade

### For New Installations (v2.0.0)
```bash
composer require squadkintechnologies/module-squadexaai:^2.0.0
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:clean
```

### For Upgrades from v1.0.0
```bash
composer update squadkintechnologies/module-squadexaai
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:clean
```

### Configuration
1. Go to **Admin > Stores > Configuration > SquadexaAI**
2. **Authentication Section**: 
   - Enter your Squadexa AI email and password
   - Click "Generate API Key" button
   - System will create permanent API key automatically
3. **Field Mapping Section**: 
   - Map AI-generated fields to Magento product attributes
4. **Dashboard**: 
   - View real-time credit balance
   - Monitor usage trends
   - Filter activity history by date and tool

---

## Support
For support and feature requests, visit: https://www.squadkin.com/support

---

**Created:** 2026-06-06  
**Author:** Squadkin Technologies Pvt. Ltd.  
**License:** Proprietary
