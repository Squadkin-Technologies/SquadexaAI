# AI-Generated Product Creation & Update Feature

## Overview

This feature allows creating and updating Magento products directly from AI-generated data. It includes a comprehensive mapping system, UI components, and seamless integration with Magento's native product management.

## Features Implemented

### 1. Database Schema
- **Table**: `squadkin_squadexaai_field_mapping`
  - Stores mapping profiles for AI fields to Magento attributes
  - Supports multiple profiles with default selection
  - Links to product types and attribute sets

### 2. Core Components

#### API Layer
- `FieldMappingInterface` - Data interface for mapping profiles
- `FieldMappingRepositoryInterface` - Repository interface
- `FieldMappingSearchResultsInterface` - Search results interface

#### Model Layer
- `FieldMapping` - Model for mapping profiles
- `FieldMappingRepository` - Repository implementation
- `ResourceModel\FieldMapping` - Resource model
- `ResourceModel\FieldMapping\Collection` - Collection model

#### Service Layer
- `AiFieldMappingService` - Core service for mapping AI data to Magento products
  - `mapAiProductToMagento()` - Maps AI product to Magento product data
  - `updateProductFromAi()` - Updates existing product with AI data

### 3. Controllers

#### AI Product Controllers
- `CreateMagentoProduct` - Creates Magento product from AI data
  - Route: `squadkin_squadexaai/aiproduct/createMagentoProduct`
  - Returns mapped data and redirect URL
  
- `GetAttributeSets` - Returns attribute sets for AJAX
  - Route: `squadkin_squadexaai/aiproduct/getAttributeSets`

#### Product Controllers
- `UpdateFromAi` - Updates existing product from AI data
  - Route: `squadkin_squadexaai/product/updateFromAi`
  - Supports two actions: `get_data` and `update`

### 4. UI Components

#### Buttons
- **AI Product Grid**: "Create Product from AI" button in actions column
- **AI Product Edit Form**: "Create Product from AI" button in button section
- **Magento Product Edit**: "Update from AI" button (via plugin)

#### JavaScript Components
- `create-product-modal.js` - Modal for product type/attribute set selection
- `update-product-from-ai.js` - Modal for updating products from AI

#### Templates
- `create-product-modal.html` - Template for create product modal
- `update-product-from-ai.html` - Template for update product modal (to be created)

### 5. Plugins
- `AddUpdateFromAiButton` - Adds "Update from AI" button to Magento product edit page

## Installation & Setup

### 1. Database Setup
```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

### 2. Create Default Mapping Profile

You can create mapping profiles via:
- Admin UI (to be implemented)
- Direct database insert
- Data patch

Example mapping rules JSON:
```json
{
  "map": {
    "product_name": "name",
    "meta_title": "meta_title",
    "meta_description": "meta_description",
    "short_description": "short_description",
    "description": "description",
    "upc": "sku",
    "pricing_usd_min": "price",
    "key_features": "custom_attribute_code"
  }
}
```

## Usage

### Creating a Product from AI Data

1. Navigate to **AI Generated Products** grid
2. Click **Actions** → **Create Product from AI** on any AI product
3. Select:
   - Product Type (Simple, Configurable, etc.)
   - Attribute Set
   - Mapping Profile (optional, uses default if not selected)
4. Click **Create Product**
5. You'll be redirected to Magento's product creation page with pre-filled data
6. Review and save the product

### Updating an Existing Product from AI Data

1. Open any existing product in Magento admin
2. Click **Update from AI** button
3. Select an AI product from the list
4. Review/edit the AI data in the popup
5. Confirm to replace existing product data
6. Product will be updated and page refreshed

## Mapping System

### Mapping Rules Format

```json
{
  "map": {
    "ai_field_name": "magento_attribute_code",
    "nested.field": "magento_attribute_code",
    "array_field": "multiselect_attribute"
  }
}
```

### Supported Field Types

- **Simple fields**: Direct mapping (e.g., `product_name` → `name`)
- **Nested fields**: Dot notation (e.g., `attributes.color` → `color`)
- **Array fields**: Automatically converted to comma-separated values
- **JSON fields**: Parsed and formatted appropriately

### Default Mappings

If no mapping profile is specified, the system:
1. Tries to find a profile by product type and attribute set
2. Falls back to the default profile
3. Uses basic mappings if no profile exists

## ACL Permissions

- `Squadkin_SquadexaAI::AiProduct` - Access to AI products
- `Squadkin_SquadexaAI::squadkin_squadexaai_aiproduct_create` - Create products from AI
- `Squadkin_SquadexaAI::field_mapping` - Field mapping management
- `Magento_Catalog::products` - Required for product updates

## Code Structure

```
app/code/Squadkin/SquadexaAI/
├── Api/
│   ├── Data/
│   │   ├── FieldMappingInterface.php
│   │   └── FieldMappingSearchResultsInterface.php
│   └── FieldMappingRepositoryInterface.php
├── Controller/
│   └── Adminhtml/
│       ├── AiProduct/
│       │   ├── CreateMagentoProduct.php
│       │   └── GetAttributeSets.php
│       └── Product/
│           └── UpdateFromAi.php
├── Model/
│   ├── FieldMapping.php
│   ├── FieldMappingRepository.php
│   └── ResourceModel/
│       └── FieldMapping/
│           ├── Collection.php
│           └── FieldMapping.php
├── Plugin/
│   └── Product/
│       └── Edit/
│           └── AddUpdateFromAiButton.php
├── Service/
│   └── AiFieldMappingService.php
└── view/adminhtml/
    ├── web/js/
    │   ├── create-product-modal.js
    │   └── update-product-from-ai.js
    └── web/template/
        └── create-product-modal.html
```

## Future Enhancements

1. **Mapping Management UI**: Admin grid and form for managing mapping profiles
2. **Bulk Operations**: Create/update multiple products at once
3. **Mapping Validation**: Validate mappings before applying
4. **Field Preview**: Preview mapped data before creating/updating
5. **Mapping Templates**: Pre-built mapping templates for common product types
6. **History Tracking**: Track which products were created/updated from AI data

## Testing

### Unit Tests
- Test mapping service with various data structures
- Test repository operations
- Test field extraction and formatting

### Integration Tests
- Test product creation flow
- Test product update flow
- Test mapping profile selection

## Troubleshooting

### Common Issues

1. **No mapping profile found**
   - Create a default mapping profile
   - Ensure product type matches mapping profile

2. **Attributes not mapping**
   - Check attribute codes in mapping rules
   - Verify attributes exist in selected attribute set

3. **Button not appearing**
   - Check ACL permissions
   - Clear cache: `php bin/magento cache:clean`
   - Recompile: `php bin/magento setup:di:compile`

## Support

For issues or questions, refer to the main module documentation or contact the development team.

