# Corporate Documents

A WordPress plugin for managing and displaying corporate documents.

## Description

Corporate Documents is a powerful document management plugin designed for organizations that need to maintain and display various types of corporate documents on their WordPress website.

### Features

* Document type organization
* Secure document storage
* Document filtering
* Shortcode support
* AJAX-powered updates
* Responsive design
* Accessibility support

## Requirements

* WordPress 5.9 or higher
* PHP 7.4 or higher

## Installation

1. Upload the plugin files to the `/wp-content/plugins/corporate-documents` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Documents menu item to configure and manage your documents

## Shortcodes

The plugin provides several shortcodes for displaying documents:

### Basic Document List
```
[cdox_list_documents]
```

### Filtered Document List
```
[cdox_list_documents type="annual-report,press-release"]
```

### Interactive Filtered List
```
[cdox_list_filtered_documents]
```

### Document Types List
```
[cdox_list_documenttypes]
```

### Years List
```
[cdox_list_years]
```

## Shortcode Attributes

### [cdox_list_documents]
* `type` - Filter by document type(s) (comma-separated)
* `show_date_column` - Show/hide the date column (true/false, default: false)

Example:
```
[cdox_list_documents type="annual-report,press-release" show_date_column="true"]
```

### [cdox_list_filtered_documents]
* `type` - Initial document type filter (comma-separated)
* `show_year_filter` - Show/hide the year filter (true/false, default: true)
* `show_date_column` - Show/hide the date column (true/false, default: true)
* `initial_current_year` - Start with current year selected (true/false, default: false)
* `show_type_filter` - Show/hide the document type filter (true/false, default: true)
* `show_order_filter` - Show/hide the sort order filter (true/false, default: true)

Example:
```
[cdox_list_filtered_documents type="annual-report" show_year_filter="true" initial_current_year="true"]
```

### [cdox_list_documenttypes]
* `hide_empty` - Hide document types with no documents (true/false, default: false)
* `orderby` - Sort by 'name' or 'count' (default: name)
* `order` - Sort order 'ASC' or 'DESC' (default: ASC)

Example:
```
[cdox_list_documenttypes hide_empty="true" orderby="count" order="DESC"]
```

### [cdox_list_years]
* `order` - Sort order 'ASC' or 'DESC' (default: DESC)
* `show_count` - Show document count per year (true/false, default: true)
* `link_years` - Make years clickable links (true/false, default: true)

Example:
```
[cdox_list_years order="DESC" show_count="true" link_years="true"]
```

## Customization

### CSS Classes
The plugin provides specific CSS classes for styling:
* `.cdox-document-list` - Main document list container
* `.cdox-document-item` - Individual document item
* `.cdox-document-title` - Document title
* `.cdox-document-date` - Document date
* `.cdox-document-type` - Document type
* `.cdox-filter-form` - Filter form container
* `.cdox-filter-section` - Filter section
* `.cdox-loading` - Loading indicator

### CSS Examples
```css
/* Style document list */
.cdox-document-list {
    max-width: 800px;
    margin: 2em auto;
}

/* Style document items */
.cdox-document-item {
    padding: 1em;
    margin-bottom: 1em;
    border: 1px solid #eee;
    border-radius: 4px;
}

/* Style document title */
.cdox-document-title {
    font-weight: bold;
    color: #333;
}

/* Style filter form */
.cdox-filter-form {
    background: #f5f5f5;
    padding: 1.5em;
    border-radius: 4px;
    margin-bottom: 2em;
}
```

### PHP Filters
```php
// Customize document title format
add_filter('cdox_document_title_format', function($title, $document) {
    return sprintf('%s (%s)', $title, $document->get_type());
}, 10, 2);

// Customize date format
add_filter('cdox_date_format', function($format) {
    return 'F j, Y';
});

// Add custom CSS classes
add_filter('cdox_document_list_classes', function($classes) {
    $classes[] = 'my-custom-class';
    return $classes;
});
```

### JavaScript Events
```javascript
// Document download tracking
document.addEventListener('cdox:document:download', function(e) {
    console.log('Document downloaded:', e.detail.documentId);
});

// Filter change tracking
document.addEventListener('cdox:filter:change', function(e) {
    console.log('Filter changed:', e.detail.filters);
});
```

## Security

The plugin implements several security measures:
* Documents are stored in a protected directory
* Direct file access is prevented
* File permissions are properly set
* Download links are temporary and secured
* User capabilities are checked for access

## License

This plugin is licensed under GPLv2 or later - see [GNU General Public License](http://www.gnu.org/licenses/gpl-2.0.html) for details.
