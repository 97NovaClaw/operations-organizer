# Autocomplete Feature Documentation

## Overview

The autocomplete feature provides a reusable, configurable dropdown component for search-as-you-type functionality. It can be used for customers, companies, or any other data type.

## Quick Start

### Basic Usage

```php
// For companies
echo oo_get_company_autocomplete_html(array(
    'input_id' => 'company_search',
    'hidden_field_id' => 'selected_company_id',
    'hidden_field_name' => 'company_id'
));

// For customers  
echo oo_get_customer_autocomplete_html(array(
    'input_id' => 'customer_search',
    'hidden_field_id' => 'selected_customer_id',
    'hidden_field_name' => 'customer_id'
));
```

### Custom Implementation

```php
echo oo_get_autocomplete_html(array(
    'input_id' => 'my_search',
    'ajax_action' => 'my_search_action',
    'render_item_callback' => 'renderMyItem',
    'on_select_callback' => 'onMyItemSelect',
    'nonce' => wp_create_nonce('my_search_nonce')
));
```

## Configuration Options

### Required Parameters

- `input_id` or `input_selector`: Target input field
- `ajax_action`: WordPress AJAX action name
- `render_item_callback`: JavaScript function to render each item
- `on_select_callback`: JavaScript function called when item is selected
- `nonce`: Security nonce for AJAX requests

### Optional Parameters

- `on_add_new_callback`: Function for "Add New" button
- `placeholder`: Input placeholder text
- `min_chars`: Minimum characters before search (default: 2)
- `delay`: Search delay in milliseconds (default: 300)
- `add_new_text`: Text for "Add New" button
- `hidden_field_id`: ID for hidden field to store selected value
- `hidden_field_name`: Name for hidden field
- `query_param`: Parameter name for search query (default: 'query')
- `response_data_path`: Path to data in AJAX response
- `data_mapping`: Map response fields to expected fields

## JavaScript Callbacks

### Render Item Callback

**CRITICAL**: Always return HTML wrapped in a single container element to prevent display issues.

```javascript
// ✅ CORRECT - Single container
window.OO_Autocomplete_Callbacks.renderMyItem = function(item) {
    return '<div class="oo-autocomplete-suggestion-item">' +
           '<div class="item-icon">' + item.initials + '</div>' +
           '<div class="item-details-container">' +
           '<div class="item-primary-text">' + item.name + '</div>' +
           '</div>' +
           '</div>';
};

// ❌ INCORRECT - Multiple top-level elements
window.OO_Autocomplete_Callbacks.renderMyItem = function(item) {
    return '<div class="item-icon">' + item.initials + '</div>' +
           '<div class="item-details-container">' + item.name + '</div>';
};
```

### Selection Callback

```javascript
window.OO_Autocomplete_Callbacks.onMyItemSelect = function(item, $input) {
    // Set input value
    $input.val(item.name);
    
    // Set hidden field if needed
    $('#my_hidden_field').val(item.id);
    
    console.log('Selected item:', item);
};
```

### Add New Callback

```javascript
window.OO_Autocomplete_Callbacks.onAddNewMyItem = function(searchTerm, $input) {
    // Open modal, redirect, or handle "Add New" action
    $('#myModal').show();
    $('#myModal input[name="name"]').val(searchTerm);
};
```

## CSS Classes

### Available Classes

- `.oo-autocomplete-suggestion-item`: Main container for item content
- `.item-icon`: Circular icon/initials display
- `.item-details-container`: Container for text content
- `.item-primary-text`: Main text (name, title)
- `.item-secondary-text`: Subtitle or additional info
- `.oo-item-main-text`: Alternative main text class
- `.oo-item-sub-text`: Alternative sub text class

### Styling Guidelines

```css
/* Custom item styling */
.my-autocomplete .oo-autocomplete-suggestion-item {
    padding: 8px 12px;
}

.my-autocomplete .item-icon {
    background: #your-color;
    color: white;
}

.my-autocomplete .item-primary-text {
    font-weight: bold;
    color: #333;
}
```

## Backend AJAX Handler

### Basic Handler Structure

```php
public static function ajax_search_my_items() {
    check_ajax_referer('my_search_nonce', 'nonce');
    
    if (!current_user_can('required_capability')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    $search_term = sanitize_text_field($_POST['query']);
    
    if (strlen($search_term) < 2) {
        wp_send_json_success([]);
        return;
    }

    // Perform search
    $items = My_DB::search_items($search_term);
    
    // Format for frontend
    $formatted_items = array();
    foreach ($items as $item) {
        $formatted_items[] = array(
            'id' => $item->id,
            'name' => $item->name,
            'initials' => substr($item->name, 0, 1),
            // Add other fields as needed
        );
    }

    wp_send_json_success($formatted_items);
}
```

### Register AJAX Actions

```php
// In your main plugin file or appropriate hook
add_action('wp_ajax_my_search_action', 'My_Class::ajax_search_my_items');
add_action('wp_ajax_nopriv_my_search_action', 'My_Class::ajax_search_my_items'); // If needed for logged-out users
```

## Common Issues and Solutions

### Issue: Initials Showing as Separate Selectable Items

**Problem**: Render callback returns multiple top-level elements
**Solution**: Always wrap content in a single container element

```javascript
// Fix the render callback
window.OO_Autocomplete_Callbacks.renderMyItem = function(item) {
    return '<div class="oo-autocomplete-suggestion-item">' +
           /* your content here */ +
           '</div>';
};
```

### Issue: Hidden Field Not Populated

**Problem**: Selection callback not setting hidden field value
**Solution**: Ensure callback sets the hidden field

```javascript
window.OO_Autocomplete_Callbacks.onMyItemSelect = function(item, $input) {
    $input.val(item.name);
    $('#my_hidden_field_id').val(item.id); // Don't forget this!
};
```

### Issue: No Search Results

**Problem**: Backend not returning proper response format
**Solution**: Ensure AJAX handler returns success response

```php
wp_send_json_success($formatted_items); // ✅ Correct
wp_send_json($formatted_items); // ❌ Incorrect
```

## Best Practices

1. **Always use helper functions** (`oo_get_company_autocomplete_html`, `oo_get_customer_autocomplete_html`) when possible
2. **Wrap render callback HTML** in a single container element
3. **Validate and sanitize** all input in AJAX handlers
4. **Use proper nonces** for security
5. **Handle errors gracefully** in both frontend and backend
6. **Test with various data** including empty results and special characters
7. **Use semantic HTML** and proper CSS classes for accessibility

## Examples

### Complete Company Autocomplete

```php
// PHP
echo oo_get_company_autocomplete_html(array(
    'input_id' => 'customer_company',
    'hidden_field_id' => 'selected_company_id',
    'hidden_field_name' => 'company_id',
    'on_select_callback' => 'onCompanySelect'
));
```

```javascript
// JavaScript
window.OO_Autocomplete_Callbacks.onCompanySelect = function(company, $input) {
    $input.val(company.name);
    $('#selected_company_id').val(company.id);
    console.log('Selected company:', company);
};
```

### Custom Item Type

```php
// PHP
echo oo_get_autocomplete_html(array(
    'input_id' => 'product_search',
    'ajax_action' => 'search_products',
    'render_item_callback' => 'renderProductItem',
    'on_select_callback' => 'onProductSelect',
    'nonce' => wp_create_nonce('search_products_nonce'),
    'hidden_field_id' => 'selected_product_id',
    'hidden_field_name' => 'product_id'
));
```

```javascript
// JavaScript
window.OO_Autocomplete_Callbacks.renderProductItem = function(item) {
    return '<div class="oo-autocomplete-suggestion-item">' +
           '<div class="item-icon">📦</div>' +
           '<div class="item-details-container">' +
           '<div class="item-primary-text">' + item.name + '</div>' +
           '<div class="item-secondary-text">$' + item.price + '</div>' +
           '</div>' +
           '</div>';
};

window.OO_Autocomplete_Callbacks.onProductSelect = function(product, $input) {
    $input.val(product.name);
    $('#selected_product_id').val(product.id);
    $('#product_price').val(product.price);
};
```

## Troubleshooting

### Debug Mode

Enable debug mode by adding this to your callback:

```javascript
window.OO_Autocomplete_Callbacks.renderMyItem = function(item) {
    console.log('Rendering item:', item);
    var html = /* your render code */;
    console.log('Generated HTML:', html);
    return html;
};
```

### Common Debug Steps

1. Check browser console for JavaScript errors
2. Verify AJAX handler is registered and accessible
3. Confirm nonce is valid
4. Test with simple HTML in render callback
5. Verify hidden field IDs match between HTML and JavaScript

## Version History

- v1.0: Initial implementation
- v1.1: Added wrapper container fix for initials display issue
- v1.2: Enhanced documentation and best practices 