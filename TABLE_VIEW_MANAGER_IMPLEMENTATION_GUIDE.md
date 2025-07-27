# Table View Manager - Implementation Guide

## Quick Start: Adding to Your Table

### 1. Add `data-table-id` to Your Table

```html
<table id="your-table-id" class="wp-list-table" data-table-id="unique_table_identifier">
```

Table ID Format: `[page]_[content]_[dynamic_part]`
- `job_details_activity_log`
- `phase_dashboard_logs_art`
- `job_details_stream_logs_electronics`

### 2. Include the Modal Template

Add this at the end of your view file:

```php
<?php 
// Include Table View Manager modal if the feature exists
if (file_exists(OO_PLUGIN_DIR . 'features/table-view-manager/views/configure-view-modal.php')) {
    include OO_PLUGIN_DIR . 'features/table-view-manager/views/configure-view-modal.php';
}
?>
```

### 3. Define Available Columns

In `features/table-view-manager/ajax.php`, add your table's column definition:

```php
// In get_available_columns() method
elseif (strpos($table_id, 'your_table_prefix') === 0) {
    $columns = self::get_your_table_columns();
}

// Add your column definition method
private static function get_your_table_columns() {
    return array(
        // Simple columns
        array('id' => 'column_id', 'title' => __('Column Title', 'operations-organizer')),
        
        // Grouped columns (for KPIs, phases, etc.)
        array(
            'id' => 'group_kpi_assembly',
            'title' => __('Assembly KPIs', 'operations-organizer'),
            'is_group' => true,
            'children' => array(
                array('id' => 'kpi_time', 'title' => __('Time', 'operations-organizer')),
                array('id' => 'kpi_cost', 'title' => __('Cost', 'operations-organizer'))
            )
        )
    );
}
```

### 4. That's It!

The Table View Manager will automatically:
- Add a "Configure View" button to your DataTable
- Apply saved preferences on page load
- Show first 20 columns by default
- Handle all preference saving/loading

## Dynamic Column Generation

For stream-specific tables with dynamic columns:

```php
private static function get_stream_columns($stream_slug) {
    global $wpdb;
    $columns = array();
    
    // Add static columns
    $columns[] = array('id' => 'job_number', 'title' => __('Job Number', 'operations-organizer'));
    
    // Add dynamic phases
    $phases = $wpdb->get_results($wpdb->prepare(
        "SELECT phase_id, phase_name FROM {$wpdb->prefix}oo_phases 
         WHERE stream_id = (SELECT stream_id FROM {$wpdb->prefix}oo_streams WHERE stream_slug = %s)",
        $stream_slug
    ));
    
    if ($phases) {
        $phase_children = array();
        foreach ($phases as $phase) {
            $phase_children[] = array(
                'id' => 'phase_' . $phase->phase_id,
                'title' => $phase->phase_name
            );
        }
        
        $columns[] = array(
            'id' => 'group_phases',
            'title' => __('Phases', 'operations-organizer'),
            'is_group' => true,
            'children' => $phase_children
        );
    }
    
    // Add dynamic KPIs (similar pattern)
    
    return $columns;
}
```

## Testing Your Implementation

1. Load your page with the table
2. Look for "Configure View" button next to "Show X entries"
3. Click to open the modal
4. Toggle column visibility
5. Switch to Order tab and drag columns
6. Click Apply and verify changes persist on reload

## Troubleshooting

- **No Configure View button**: Check that table has `data-table-id` attribute
- **Modal doesn't open**: Verify modal template is included and JS/CSS loaded
- **Columns don't save**: Check browser console for AJAX errors
- **Wrong columns shown**: Verify your column definition matches actual DataTable columns 