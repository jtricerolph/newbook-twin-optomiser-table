# Newbook Twin Optimizer Table

A WordPress plugin that displays a visual grid of hotel room bookings over a 14-day period, highlighting twin bed bookings for easy identification.

## Features

- **14-Day Booking Grid**: Displays room bookings across 14 consecutive days
- **Twin Bed Highlighting**: Yellow background for twin bed rooms, making them instantly visible
- **Multi-Day Bookings**: Bookings spanning multiple days are shown as merged cells
- **Interactive Date Picker**: Select any start date to view bookings from that point forward
- **Hover Tooltips**: Hover over any booking to see the guest name
- **Room Filtering**: Automatically excludes "overflow" rooms from the display
- **Responsive Design**: Table adapts to different screen sizes with horizontal scrolling

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- **Booking Match API plugin** (required dependency)

## Installation

1. Upload the plugin files to `/wp-content/plugins/newbook-twin-optomiser-table/`
2. Ensure the Booking Match API plugin is installed and activated
3. Activate the Newbook Twin Optimizer Table plugin through the 'Plugins' menu in WordPress

## Usage

### Basic Shortcode

Add the shortcode to any page or post:

```
[twin_optomiser_table]
```

This will display a table starting from today with 14 days visible.

### Shortcode Parameters

- `start_date` - The start date for the table (format: YYYY-MM-DD, default: today)
- `days` - Number of days to display (default: 14)

### Examples

**Start from a specific date:**
```
[twin_optomiser_table start_date="2024-12-01"]
```

**Show 21 days instead of 14:**
```
[twin_optomiser_table days="21"]
```

**Custom start date and days:**
```
[twin_optomiser_table start_date="2024-12-01" days="21"]
```

## How It Works

### Data Integration

The plugin integrates with the Booking Match API plugin to fetch:

1. **Room List**: Uses `BMA_NewBook_Search::fetch_sites()` to get all hotel rooms
   - Filters out "overflow" rooms
   - Sorts rooms in ascending order (e.g., 101, 102, 103...)

2. **Booking Data**: Uses `BMA_NewBook_Search::fetch_staying_bookings($date)` for each date
   - Fetches bookings staying on each date in the 14-day window
   - Extracts bed type from custom fields (label: "Bed Type")
   - Calculates multi-day spans for each booking

### Visual Indicators

| Cell Type | Appearance | Description |
|-----------|------------|-------------|
| **Twin Bed** | Yellow background (#FFD700) | Booking with "Bed Type: Twin" custom field |
| **Double Bed** | White/light background | Booking without twin bed type |
| **Vacant** | Grey background (#E0E0E0) | No booking for this room/date |

### Cell Content

Each booked cell displays:
- **Booking Reference**: The booking ID or reference number
- **Bed Type**: "Twin" or "Double" label
- **Guest Name** (on hover): Full name of the primary guest

### Multi-Day Bookings

Bookings that span multiple nights are displayed as a single merged cell across the applicable dates using HTML colspan. For example, a 3-night booking will span 3 columns in the table.

## Date Picker

The interactive date picker at the top of the table allows you to:
- Select any start date
- Automatically refresh the table via AJAX
- View bookings for different time periods without page reload

## Technical Details

### Integration with Booking Match API

The plugin accesses the Booking Match API's `BMA_NewBook_Search` class:

```php
$this->bma_search = new BMA_NewBook_Search();

// Get all rooms
$sites = $this->bma_search->fetch_sites();

// Get bookings for a specific date
$bookings = $this->bma_search->fetch_staying_bookings($date);
```

### Bed Type Detection

The plugin checks each booking's custom fields for bed type:

```php
foreach ($custom_fields as $field) {
    if ($field['label'] === 'Bed Type') {
        $bed_type = $field['value']; // "Twin" or "Double"
    }
}
```

### Caching

The plugin leverages the Booking Match API's built-in caching:
- Room list: Cached for 1 hour
- Bookings: Tiered caching (60 seconds for fresh data, up to 15 minutes for older dates)

## File Structure

```
newbook-twin-optomiser-table/
├── assets/
│   ├── css/
│   │   └── table-style.css       # Grid styling, colors, tooltips
│   └── js/
│       └── table-script.js        # Date picker, AJAX refresh
├── newbook-twin-optomiser-table.php  # Main plugin file
├── README.md
└── .gitignore
```

## Development

### Adding Custom Styling

Edit `assets/css/table-style.css` to customize:
- Twin bed highlight color (currently #FFD700 yellow)
- Vacant cell color (currently #E0E0E0 grey)
- Table headers and layout
- Responsive breakpoints

### Modifying the Table Layout

The main rendering logic is in the `render_booking_grid()` method in the main plugin file. Key functions:

- `get_filtered_rooms()` - Fetch and filter room list
- `get_bookings_for_date_range()` - Fetch bookings for all dates
- `calculate_colspan()` - Calculate multi-day booking spans
- `get_bed_type()` - Extract bed type from custom fields
- `get_guest_name()` - Extract primary guest name

### AJAX Endpoint

The plugin registers an AJAX endpoint for table refresh:

**Action**: `ntot_refresh_table`

**Parameters**:
- `start_date`: Date in Y-m-d format
- `days`: Number of days to display
- `nonce`: Security nonce

## Browser Compatibility

- Modern browsers with CSS Grid support
- JavaScript required for date picker and AJAX functionality
- Horizontal scrolling enabled for smaller screens

## Troubleshooting

### Table shows "No rooms available"

- Verify Booking Match API plugin is activated
- Check that the NewBook API is configured correctly in BMA settings
- Ensure there are rooms in the NewBook system

### No bookings showing

- Verify bookings exist in NewBook for the selected date range
- Check BMA cache - try clearing it via the BMA plugin
- Inspect browser console for JavaScript errors

### Twin beds not highlighting

- Check that the booking has a custom field with label "Bed Type"
- Verify the field value contains "Twin" (case-insensitive)
- Inspect the booking data in the Chrome extension to verify custom fields

## License

GPL v2 or later

## Version

1.0.0

## Support

For issues related to:
- **This plugin**: Check the plugin code and documentation
- **Booking Match API**: Refer to the BMA plugin documentation
- **NewBook API**: Contact NewBook support
