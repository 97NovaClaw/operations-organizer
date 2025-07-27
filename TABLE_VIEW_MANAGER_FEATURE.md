# Table View Manager Feature Documentation

## Overview

The Table View Manager is a comprehensive feature that allows users to customize how data tables appear throughout the Operations Organizer plugin. Each user can save their own preferences for column visibility, column order, and other display settings on a per-table basis.

## Core Concepts

### 1. User Stories

- **As a Project Manager**, I want to see only the KPIs relevant to my projects when viewing the phase dashboard
- **As a Senior Manager**, I want to reorder columns to prioritize financial data first
- **As an Operations Manager**, I want different column layouts for different streams (Art vs Electronics)
- **As any user**, I want my table preferences to persist across sessions

### 2. Key Features

- **Column Visibility Toggle**: Show/hide specific columns with nested group support
- **Column Reordering**: Drag-and-drop columns to preferred positions
- **Persistent Storage**: Save preferences per user, per table
- **Stream-Specific Settings**: Different preferences for each stream's tables
- **Hierarchical Column Selection**: Parent-child relationships for KPI groups

## Architecture (Following airules.mdc)

### Feature Location
```
/features/table-view-manager/
```

This is a self-contained feature module that provides services to other features in the plugin.

### Database Schema

**Table: `wp_oo_user_table_preferences`**

| Column | Type | Description |
|--------|------|-------------|
| preference_id | BIGINT(20) UNSIGNED | Primary key, auto-increment |
| user_id | BIGINT(20) UNSIGNED | WordPress user ID |
| table_id | VARCHAR(255) | Unique identifier for each table |
| preferences | JSON | Serialized DataTables state object |
| last_updated | DATETIME | Timestamp of last modification |

**Indexes:**
- Primary: `preference_id`
- Unique: `(user_id, table_id)`

### Table ID Standards

Format: `[page]_[content]_[dynamic_part]`

Examples:
- `job_details_activity_log` - Job Details page, Activity Log table
- `phase_dashboard_logs_art` - Phase Dashboard, Art stream logs
- `job_details_stream_logs_electronics` - Job Details, Electronics stream logs

### Data Structures

#### 1. Available Columns (PHP → JS)
```json
[
  {
    "id": "job_number",
    "title": "Job Number"
  },
  {
    "id": "group_kpi_assembly",
    "title": "Assembly KPIs",
    "is_group": true,
    "children": [
      {
        "id": "kpi_assembly_time",
        "title": "Assembly Time"
      },
      {
        "id": "kpi_assembly_cost",
        "title": "Assembly Cost"
      }
    ]
  }
]
```

#### 2. Saved Preferences (JS → Database)
```json
{
  "columns": [
    {"visible": true, "search": ""},
    {"visible": false, "search": ""},
    {"visible": true, "search": ""}
  ],
  "order": [0, 2, 1, 3],
  "search": {"search": "", "smart": true},
  "start": 0,
  "length": 25
}
```

## Implementation Flow

### 1. Page Load Sequence

1. PHP identifies all tables on the current page
2. PHP fetches available columns for each table (dynamic based on stream/KPIs)
3. PHP fetches user's saved preferences from database
4. PHP localizes both datasets to JavaScript via `wp_localize_script`
5. JavaScript initializes DataTables with saved state

### 2. User Interaction Flow

1. User clicks "Configure View" button
2. Modal opens with two panels:
   - **Visibility Panel**: Hierarchical checkboxes
   - **Order Panel**: Draggable list of visible columns
3. User makes changes
4. Clicking "Apply":
   - Updates DataTable display immediately
   - Sends AJAX request to save preferences
   - Database stores new state

### 3. Integration Points

The feature integrates with existing pages by:

1. Adding `data-table-id` attribute to tables
2. Including the modal template
3. Enqueuing the table-view-manager assets
4. Passing column data via localization

## File Structure

```
/features/table-view-manager/
├── index.php                    # Feature initialization
├── database.php                 # Database operations class
├── ajax.php                     # AJAX handlers
├── migration.php                # Database table creation
├── assets/
│   ├── css/
│   │   └── table-view-manager.css
│   └── js/
│       └── table-view-manager.js
└── views/
    └── configure-view-modal.php  # Reusable modal template
```

## Required Dependencies

- **DataTables**: Core table functionality
- **DataTables ColReorder**: Column reordering
- **jQuery UI Sortable**: Drag-and-drop in configuration modal
- **WordPress Core**: Ajax, user management, database API

## Security Considerations

1. **Nonce Verification**: All AJAX requests must verify nonces
2. **Capability Checks**: Users can only save their own preferences
3. **Data Sanitization**: All inputs sanitized before database storage
4. **SQL Injection Prevention**: Use WordPress prepared statements

## Performance Optimizations

1. **Lazy Loading**: Only load preferences for tables on current page
2. **Caching**: Consider caching available columns list (changes infrequently)
3. **Batch Operations**: Save all preference changes in single AJAX call
4. **Indexed Lookups**: Database indexes ensure fast preference retrieval

## Future Enhancements

### Phase 2
- Column width persistence
- Export/import view configurations
- Shared views between users
- Admin-defined default views

### Phase 3
- Advanced filtering preferences
- Conditional formatting rules
- Custom column calculations
- Mobile-responsive preferences

## Testing Checklist

- [ ] Preferences save correctly for each table
- [ ] Preferences persist across sessions
- [ ] Stream-specific tables maintain separate preferences
- [ ] Nested column selection works correctly
- [ ] Column reordering updates properly
- [ ] No conflicts between different users' preferences
- [ ] Performance acceptable with 50+ columns
- [ ] Modal works on all target pages
- [ ] Graceful handling of missing preferences
- [ ] Reset to default functionality

## Known Limitations

1. Column preferences are tied to column IDs - if a KPI is deleted, its preference becomes orphaned
2. No bulk management interface for administrators
3. No way to share view configurations between users
4. Maximum practical limit of ~100 columns per table

## Related Features

- **Dynamic Streams**: Tables adapt based on stream configuration
- **KPI Management**: Available columns change as KPIs are added/removed
- **Phase Management**: Phase columns in logs are dynamically generated

---

*This document should be updated as the feature evolves. Always ensure changes maintain the modular, self-contained nature outlined in airules.mdc.* 