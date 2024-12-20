=== Corporate Documents ===
Contributors: evenhouseconsulting
Tags: documents, corporate, file management
Requires at least: 5.9
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin for managing and displaying corporate documents.

== Description ==

Corporate Documents is a powerful document management plugin designed for organizations that need to maintain and display various types of corporate documents on their WordPress website.

Features:

* Document type organization
* Secure document storage
* Document filtering
* Shortcode support
* AJAX-powered updates
* Responsive design
* Accessibility support

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/corporate-documents` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Documents menu item to configure and manage your documents

== Frequently Asked Questions ==

= How do I display documents on a page? =

The plugin provides several shortcodes for displaying documents:

1. Basic Document List:
[cdox_list_documents]

2. Filtered Document List:
[cdox_list_documents type="annual-report,press-release"]

3. Interactive Filtered List:
[cdox_list_filtered_documents]

4. Document Types List:
[cdox_list_documenttypes]

5. Years List:
[cdox_list_years]

= What shortcode attributes are available? =

The plugin provides several shortcodes with various attributes for customization:

1. [cdox_list_documents] Attributes:
* type - Filter by document type(s) (comma-separated)
* show_date_column - Show/hide the date column (true/false, default: false)
Example:
[cdox_list_documents type="annual-report,press-release" show_date_column="true"]

2. [cdox_list_filtered_documents] Attributes:
* type - Initial document type filter (comma-separated)
* show_year_filter - Show/hide the year filter (true/false, default: true)
* show_date_column - Show/hide the date column (true/false, default: true)
* initial_current_year - Start with current year selected (true/false, default: false)
* show_type_filter - Show/hide the document type filter (true/false, default: true)
* show_order_filter - Show/hide the sort order filter (true/false, default: true)
Example:
[cdox_list_filtered_documents type="annual-report" show_year_filter="true" initial_current_year="true"]

3. [cdox_list_documenttypes] Attributes:
* hide_empty - Hide document types with no documents (true/false, default: false)
* orderby - Sort by 'name' or 'count' (default: name)
* order - Sort order 'ASC' or 'DESC' (default: ASC)
Example:
[cdox_list_documenttypes hide_empty="true" orderby="count" order="DESC"]

4. [cdox_list_years] Attributes:
* order - Sort order 'ASC' or 'DESC' (default: DESC)
* show_count - Show document count per year (true/false, default: true)
* link_years - Make years clickable links (true/false, default: true)
Example:
[cdox_list_years order="DESC" show_count="true" link_years="true"]

= Can I customize the document display? =

Yes, you can customize the display in several ways:

1. CSS Classes:
The plugin provides specific CSS classes for styling:
* .cdox-document-list - Main document list container
* .cdox-document-item - Individual document item
* .cdox-document-title - Document title
* .cdox-document-date - Document date
* .cdox-document-type - Document type
* .cdox-filter-form - Filter form container
* .cdox-filter-section - Filter section
* .cdox-loading - Loading indicator

2. Filters:
The plugin provides WordPress filters for customizing the output:
* cdox_document_title_format - Customize document title format
* cdox_date_format - Customize date format
* cdox_document_types_order - Customize document type order
* cdox_document_list_classes - Add custom CSS classes

3. Template Override:
You can override the default templates by copying them from the plugin's `templates` directory to your theme's `corporate-documents` directory.

= How do I organize documents by type? =

1. Go to Documents > Document Types
2. Create document types as needed (e.g., Annual Reports, Press Releases)
3. When adding documents, assign them to the appropriate type(s)
4. Use the type attribute in shortcodes to display specific document types

= How secure are the documents? =

The plugin implements several security measures:
* Documents are stored in a protected directory
* Direct file access is prevented
* File permissions are properly set
* Download links are temporary and secured
* User capabilities are checked for access

== Screenshots ==

1. Document management interface
2. Document list display
3. Document filtering options

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of the Corporate Documents plugin.

== CSS Customization Examples ==

Here are some common CSS customization examples:

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

== Advanced Usage ==

= PHP Filters Example =

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

= JavaScript Integration =

The plugin provides JavaScript events for custom integrations:

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