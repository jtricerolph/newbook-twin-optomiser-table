# Newbook Twin Optomiser Table

A WordPress plugin that displays optimized twin booking data using shortcodes.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- **Booking Match API plugin** (required dependency)

## Installation

1. Upload the plugin files to `/wp-content/plugins/newbook-twin-optomiser-table/`
2. Ensure the Booking Match API plugin is installed and activated
3. Activate the Newbook Twin Optomiser Table plugin through the 'Plugins' menu in WordPress

## Usage

Use the shortcode in any page or post:

```
[twin_optomiser_table]
```

### Shortcode Parameters

- `property_id` - The property ID to filter results (optional)
- `date_from` - Start date for booking range (optional)
- `date_to` - End date for booking range (optional)
- `max_results` - Maximum number of results to display (default: 10)

### Example

```
[twin_optomiser_table property_id="123" max_results="20"]
```

## Development

### Directory Structure

```
newbook-twin-optomiser-table/
├── assets/
│   ├── css/
│   │   └── table-style.css
│   └── js/
│       └── table-script.js
├── includes/
│   └── (additional classes)
├── newbook-twin-optomiser-table.php
├── README.md
└── .gitignore
```

### Integration with Booking Match API

The plugin checks for the Booking Match API dependency on activation. You can access the Booking Match API instance in your code:

```php
$bma_instance = Booking_Match_API::get_instance();
```

## License

GPL v2 or later

## Version

1.0.0
