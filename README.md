# Squadkin SquadexaAI Magento 2 Extension

`squadkin/module-squadexaai`

**Copyright © 2025 Squadkin Technologies Pvt. Ltd. All Rights Reserved.**  
**PROPRIETARY SOFTWARE – DO NOT COPY OR DISTRIBUTE**

This module is the official and exclusive Magento 2 integration for **SquadexaAI**, Squadkin's AI-driven product content generation platform. The extension connects Magento merchants to SquadexaAI so they can create product descriptions, attributes, and media-ready content directly inside the Magento admin.

Please review `LICENSE.txt` and `COPYING.txt` for full license details.

---

## Introduction
SquadexaAI delivers AI-powered product creation tools to Magento merchants. The extension provides a complete admin experience for authenticating with SquadexaAI, generating single or bulk products, reviewing generation history, and exporting data for catalog operations.

## Key Features
- **AI Product Generation** – Create product titles, descriptions, SEO data, and attributes using SquadexaAI models from within Magento.
- **Bulk & CSV Workflows** – Generate multiple products at once and download structured CSV outputs for further processing.
- **Usage Dashboard** – Monitor API usage, plan limits, recent activity logs, and processing statistics.
- **Admin Tools Integration** – Custom admin menus, grids, and UI components that blend seamlessly with Magento backend UX.
- **Secure Authentication** – OAuth/API key workflow with encrypted credential storage inside Magento.
- **Activity Tracking** – Detailed logs for generation jobs, word counts, processing times, and endpoints.

## Dependencies & Requirements
- **Magento Version**: 2.4.x or newer (tested on Magento 2.4.8)
- **PHP Version**: 8.0+
- **Magento Modules**: `Magento_Backend`, `Magento_Catalog`, `Magento_Ui`
- **Squadkin Base Module**: `Squadkin_Base` (provides shared menu + branding)
- **SquadexaAI Account**: Active SquadexaAI subscription with API access.
  - Create/manage your account and plans via the [SquadexaAI Pricing Portal](https://squadexa.ai/pricing).
  - Each Magento store that uses the extension needs its own API key generated from a SquadexaAI account.
- Outbound HTTPS connectivity to `https://squadexa.ai` for API calls

## Installation
> For production environments, run deployment commands with the `--keep-generated` flag.

> **Important:** Before configuring the module in Magento, make sure you have a SquadexaAI account and plan. API keys are issued inside the SquadexaAI dashboard after purchasing a plan at [https://squadexa.ai/pricing](https://squadexa.ai/pricing).

### Option 1: Manual (Zip/Tar)
1. Copy the contents to `app/code/Squadkin/SquadexaAI`.
2. Enable the module:
   ```bash
   php bin/magento module:enable Squadkin_SquadexaAI
   ```
3. Run setup upgrade:
   ```bash
   php bin/magento setup:upgrade
   ```
4. Deploy static content if required:
   ```bash
   php bin/magento setup:static-content:deploy -f
   ```
5. Flush cache:
   ```bash
   php bin/magento cache:flush
   ```

### Option 2: Composer
1. Add your private repository that contains `squadkin/module-squadexaai`.
2. Require the module:
   ```bash
   composer require squadkin/module-squadexaai
   ```
3. Repeat steps 2–5 from the manual installation (enable module, upgrade, deploy static content, flush cache).

## License
This software is proprietary and confidential. Unauthorized copying, distribution, resale, or reverse engineering is strictly prohibited and will be prosecuted to the maximum extent permitted under law. Refer to `LICENSE.txt` for the complete license agreement.

## Support
For licensing, technical assistance, or enterprise onboarding:
- **Company**: Squadkin Technologies Pvt. Ltd.
- **Email**: support@squadkin.com
- **Website**: https://www.squadkin.com/

## Copyright
© 2025 Squadkin Technologies Pvt. Ltd. All rights reserved. This extension is protected by international copyright and intellectual property laws.

