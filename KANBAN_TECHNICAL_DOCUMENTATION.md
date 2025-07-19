# Kanban Feature - Technical Documentation

## Project Status Overview

**Current State**: ✅ **FULLY IMPLEMENTED AND INTEGRATED**
- Database schema: Complete with migration system
- Backend PHP: Complete with AJAX handlers and database operations
- Frontend JavaScript: Complete with drag-and-drop functionality
- CSS Styling: Complete with responsive design
- Integration: Successfully integrated into Stream Dashboard → Phase Dashboard tab
- Documentation: Complete user and technical documentation
- Version: Plugin updated to v1.5.2.0

**What Works**:
- ✅ Kanban board displays all jobs for a stream organized by phases
- ✅ Drag-and-drop phase changes with confirmation dialogs
- ✅ Activity logging with detailed audit trail
- ✅ Add notes functionality with different note types
- ✅ View activity log modals
- ✅ Real-time UI updates after changes
- ✅ Responsive design for different screen sizes
- ✅ Security with nonce verification and capability checks

---

## Architecture Overview

### Integration Point
The Kanban board is integrated into the existing Stream Dashboard system:
- **Location**: Stream Dashboard → Phase Dashboard tab → "Checkpoint Progress" section
- **Hook System**: Uses WordPress action `oo_render_stream_kanban`
- **Context**: Shows all jobs assigned to the current stream

### Modular Structure
Following the established plugin architecture pattern:
```
features/kanban/
├── index.php              # Feature entry point and initialization
├── schema.php             # Database schema definitions
├── migration.php          # Database migration logic
├── database.php           # Database operations
├── ajax.php               # AJAX request handlers
└── views/
    ├── board-view.php         # Single job/stream Kanban view (legacy)
    └── stream-kanban-view.php # Multi-job stream view (active)
```

---

## Database Schema

### Tables Created

#### 1. `oo_stream_activity_log`
**Purpose**: Tracks all phase changes and activities for job streams
```sql
CREATE TABLE oo_stream_activity_log (
    activity_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    job_stream_id bigint(20) unsigned NOT NULL,
    activity_type varchar(50) NOT NULL,
    old_phase_id bigint(20) unsigned DEFAULT NULL,
    new_phase_id bigint(20) unsigned DEFAULT NULL,
    user_id bigint(20) unsigned NOT NULL,
    activity_date datetime NOT NULL,
    notes text DEFAULT NULL,
    note_type varchar(50) DEFAULT 'general',
    PRIMARY KEY (activity_id),
    KEY job_stream_id (job_stream_id),
    KEY activity_date (activity_date),
    KEY user_id (user_id)
)
```

**Key Fields**:
- `job_stream_id`: Links to `oo_job_streams_link.job_stream_id`
- `activity_type`: 'phase_change', 'note_added', 'job_started', 'job_completed'
- `old_phase_id`/`new_phase_id`: Track phase transitions
- `notes`: Free-text notes or system-generated descriptions
- `note_type`: 'general', 'progress', 'issue', 'resolution'

#### 2. Modified `oo_job_streams_link` Table
**Added Column**: `current_phase_id bigint(20) unsigned DEFAULT NULL`
**Purpose**: Tracks the current phase for each job-stream assignment

### Migration System
- **File**: `features/kanban/migration.php`
- **Versioning**: Uses `oo_kanban_db_version` option
- **Current Version**: 1.0
- **Process**: 
  1. Creates `oo_stream_activity_log` table
  2. Adds `current_phase_id` column to `oo_job_streams_link`
  3. Backfills existing job streams with initial activity log entries

---

## Data Flow Architecture

### 1. Page Load Flow
```
Stream Dashboard Load
    ↓
Check if Phase Dashboard tab
    ↓
Execute do_action('oo_render_stream_kanban', $stream_id, $stream_slug)
    ↓
OO_Kanban_Feature::render_stream_kanban()
    ↓
Query job streams for current stream
    ↓
Query phases for current stream
    ↓
Group jobs by current_phase_id
    ↓
Render stream-kanban-view.php
    ↓
Enqueue Kanban JavaScript and CSS
```

### 2. Drag-and-Drop Flow
```
User drags job card to new phase
    ↓
JavaScript dragend event fires
    ↓
Confirmation dialog: "Move job to {phase}?"
    ↓ (if confirmed)
AJAX POST to wp_ajax_oo_kanban_change_phase
    ↓
OO_Kanban_AJAX::handle_phase_change()
    ↓
Verify nonce and capabilities
    ↓
Begin database transaction
    ↓
Update oo_job_streams_link.current_phase_id
    ↓
Insert activity log entry in oo_stream_activity_log
    ↓
Commit transaction
    ↓
Return JSON success response
    ↓
JavaScript updates UI (move card, update counts)
```

### 3. Activity Log Flow
```
User clicks activity log icon on job card
    ↓
JavaScript opens modal and shows loading spinner
    ↓
AJAX POST to wp_ajax_oo_kanban_get_activity_log
    ↓
OO_Kanban_AJAX::handle_get_activity_log()
    ↓
Query oo_stream_activity_log for job_stream_id
    ↓
Join with users table for display names
    ↓
Join with phases table for phase names
    ↓
Return formatted HTML
    ↓
JavaScript populates modal content
```

### 4. Add Note Flow
```
User clicks note icon on job card
    ↓
JavaScript opens add note modal
    ↓
User fills form and submits
    ↓
AJAX POST to wp_ajax_oo_kanban_add_note
    ↓
OO_Kanban_AJAX::handle_add_note()
    ↓
Verify nonce and capabilities
    ↓
Insert activity log entry with type 'note_added'
    ↓
Return success response
    ↓
JavaScript closes modal and shows success message
```

---

## File Structure and Responsibilities

### Core Feature Files

#### `features/kanban/index.php`
**Purpose**: Feature initialization and coordination
**Key Functions**:
- `init()`: Main entry point, registers hooks and runs migration
- `run_migration()`: Ensures database is up to date
- `enqueue_assets()`: Loads CSS/JS on stream dashboard pages
- `render_stream_kanban()`: Main rendering function called by hook

**Hook Registrations**:
```php
add_action('oo_render_stream_kanban', array(__CLASS__, 'render_stream_kanban'));
add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
```

#### `features/kanban/schema.php`
**Purpose**: Database schema definitions
**Key Functions**:
- `get_activity_log_table_sql()`: Returns CREATE TABLE SQL for activity log
- `get_job_streams_column_sql()`: Returns ALTER TABLE SQL for current_phase_id

#### `features/kanban/migration.php`
**Purpose**: Database migration and setup
**Key Functions**:
- `run_migration()`: Main migration coordinator
- `create_activity_log_table()`: Creates activity log table
- `add_current_phase_column()`: Adds current_phase_id to job_streams_link
- `backfill_activity_log()`: Creates initial activity log entries

#### `features/kanban/database.php`
**Purpose**: Database operations for Kanban functionality
**Key Functions**:
- `change_job_phase()`: Updates phase and logs activity (with transaction)
- `add_activity_note()`: Adds note-type activity log entry
- `get_activity_log()`: Retrieves formatted activity log for a job stream
- `get_current_phase()`: Gets current phase for a job stream
- `get_phase_statistics()`: Gets job counts per phase for a stream

#### `features/kanban/ajax.php`
**Purpose**: AJAX request handling
**Key Functions**:
- `init()`: Registers all AJAX actions
- `handle_phase_change()`: Processes drag-and-drop phase changes
- `handle_get_activity_log()`: Returns activity log HTML
- `handle_add_note()`: Processes note additions

**Registered AJAX Actions**:
- `wp_ajax_oo_kanban_change_phase`
- `wp_ajax_oo_kanban_get_activity_log`  
- `wp_ajax_oo_kanban_add_note`

### View Files

#### `features/kanban/views/stream-kanban-view.php`
**Purpose**: Main Kanban board HTML template for stream dashboard
**Data Flow**:
1. Receives `$stream_id`, `$stream_slug`, and `$job_streams` variables
2. Queries stream and phases data
3. Groups job streams by current_phase_id
4. Renders Kanban columns and job cards
5. Includes modals for activity log and note addition

**Key Sections**:
- Phase columns with job cards
- Unassigned column for jobs without phases
- Activity log modal template
- Add note modal template

#### `features/kanban/views/board-view.php`
**Purpose**: Legacy single job/stream view (not currently used)
**Status**: Maintained for potential future use

### Asset Files

#### `assets/js/features/kanban/main.js`
**Purpose**: Frontend JavaScript functionality
**Key Features**:
- Drag-and-drop event handling
- AJAX request management
- Modal interactions
- UI updates and notifications

**Main Functions**:
- `initDragAndDrop()`: Sets up drag/drop event listeners
- `handlePhaseChange()`: Processes drag-and-drop completion
- `showActivityLog()`: Opens and populates activity log modal
- `showAddNoteModal()`: Opens add note modal
- `submitNote()`: Handles note form submission

#### `assets/css/features/kanban/kanban.css`
**Purpose**: Kanban board styling
**Key Features**:
- Flexbox-based responsive layout
- Card styling with hover effects
- Modal styling
- Drag-and-drop visual feedback
- Notification styling

---

## Integration Points

### Stream Dashboard Integration
**File**: `features/stream-dashboard/views/tab-phase-dashboard.php`
**Integration Code**:
```php
<div class="oo-kanban-wrapper">
    <?php 
    if (has_action('oo_render_stream_kanban')) {
        do_action('oo_render_stream_kanban', $current_stream_id, $current_stream_tab_slug);
    } else {
        echo '<p class="oo-notice oo-info">' . esc_html__('The Kanban board feature is not activated.', 'operations-organizer') . '</p>';
    }
    ?>
</div>
```

### Main Plugin Integration
**File**: `operations-organizer.php`
**Integration Code**:
```php
// Load Kanban feature
if (file_exists(OO_PLUGIN_DIR . 'features/kanban/index.php')) {
    require_once OO_PLUGIN_DIR . 'features/kanban/index.php';
    OO_Kanban_Feature::init();
} else {
    error_log('Kanban feature file not found: ' . OO_PLUGIN_DIR . 'features/kanban/index.php');
}
```

---

## Security Implementation

### Nonce Verification
All AJAX requests verify nonces:
```php
if (!wp_verify_nonce($_POST['nonce'], 'oo_kanban_phase_change_nonce')) {
    wp_send_json_error('Nonce verification failed');
}
```

### Capability Checks
All operations verify user permissions:
```php
if (!current_user_can(oo_get_capability())) {
    wp_send_json_error('Insufficient permissions');
}
```

### Data Sanitization
All inputs are sanitized:
```php
$job_stream_id = intval($_POST['job_stream_id']);
$new_phase_id = intval($_POST['new_phase_id']);
$notes = sanitize_textarea_field($_POST['notes']);
```

---

## Performance Considerations

### Database Optimization
- Indexes on frequently queried columns
- Use of prepared statements
- Transaction-based operations for consistency

### Frontend Optimization
- Conditional asset loading (only on stream dashboard pages)
- Minimal DOM manipulation during drag-and-drop
- Efficient AJAX request handling

### Caching Strategy
- No caching implemented yet (future enhancement opportunity)
- Activity logs could be cached for frequently accessed job streams

---

## Development Patterns Used

### WordPress Standards
- Follows WordPress Coding Standards
- Uses WordPress hooks and actions
- Proper internationalization with `__()` and `esc_html_e()`
- Secure AJAX implementation

### Plugin Architecture
- Modular feature structure in `features/` directory
- Separation of concerns (schema, migration, database, AJAX, views)
- Static class methods for feature encapsulation
- Consistent naming conventions

### Error Handling
- Try-catch blocks for database operations
- Graceful degradation when feature is not available
- Proper error logging and user feedback

---

## Future Enhancement Opportunities

### Immediate Improvements
1. **Caching**: Implement caching for activity logs and phase statistics
2. **Bulk Operations**: Add ability to move multiple jobs at once
3. **Filtering**: Activate the job number filter functionality
4. **Sorting**: Add sorting options for job cards

### Advanced Features
1. **Real-time Updates**: WebSocket integration for multi-user environments
2. **Custom Fields**: Display additional job information on cards
3. **Time Tracking**: Track time spent in each phase
4. **Reporting**: Generate reports based on activity log data

### Performance Optimizations
1. **Lazy Loading**: Load job details on demand
2. **Pagination**: Handle large numbers of jobs efficiently
3. **Background Processing**: Move heavy operations to background tasks

---

## Troubleshooting Guide

### Common Issues

#### "The Kanban board feature is not activated"
**Cause**: Feature not loaded in main plugin file
**Solution**: Check `operations-organizer.php` includes Kanban feature

#### AJAX requests return "0"
**Cause**: AJAX handlers not registered properly
**Solution**: Verify `OO_Kanban_AJAX::init()` is called in `operations-organizer.php`

#### Drag-and-drop not working
**Cause**: JavaScript not loaded or jQuery UI missing
**Solution**: Check browser console for errors, verify asset enqueuing

#### Database errors during migration
**Cause**: Insufficient database permissions or conflicts
**Solution**: Check `wp_options` table for `oo_kanban_db_version`, run migration manually

### Debug Information
- **Plugin Version**: 1.5.2.0
- **Database Version**: 1.0
- **Required WordPress Version**: 5.0+
- **Required PHP Version**: 7.4+

---

## Testing Checklist

### Functional Testing
- [ ] Kanban board displays on Phase Dashboard tab
- [ ] Jobs appear in correct phase columns
- [ ] Drag-and-drop updates phases correctly
- [ ] Activity log shows complete history
- [ ] Notes can be added successfully
- [ ] Confirmation dialogs work properly

### Security Testing
- [ ] Nonce verification prevents CSRF
- [ ] Capability checks prevent unauthorized access
- [ ] Input sanitization prevents XSS/injection
- [ ] Database transactions maintain data integrity

### Performance Testing
- [ ] Page loads quickly with many jobs
- [ ] AJAX requests complete promptly
- [ ] Database queries are optimized
- [ ] Memory usage is reasonable

---

## Deployment Notes

### Database Changes
The feature adds:
1. New table: `oo_stream_activity_log`
2. New column: `oo_job_streams_link.current_phase_id`
3. Option: `oo_kanban_db_version` for migration tracking

### File Changes
New files added:
- `features/kanban/` directory with all Kanban functionality
- `assets/js/features/kanban/main.js`
- `assets/css/features/kanban/kanban.css`

Modified files:
- `operations-organizer.php` (version update and feature loading)
- `features/stream-dashboard/views/tab-phase-dashboard.php` (integration)

### Rollback Plan
If issues arise:
1. Remove Kanban feature loading from `operations-organizer.php`
2. Restore original `tab-phase-dashboard.php` 
3. Database tables can remain (they don't interfere with existing functionality)

---

**Documentation Last Updated**: December 2024
**Feature Status**: Production Ready ✅
**Next Review Date**: After user testing feedback 