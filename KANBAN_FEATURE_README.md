# Phase Kanban Board Feature

## Overview
The Phase Kanban Board feature provides a drag-and-drop visual interface for tracking jobs through their phases within a stream. It includes comprehensive activity logging and note-taking capabilities.

## Features
- **Drag-and-Drop Interface**: Move jobs between phases by dragging cards
- **Activity Logging**: Every phase change is logged with user, timestamp, and details
- **Notes System**: Add timestamped notes to job streams
- **Real-time Updates**: Phase counts update automatically
- **Visual Feedback**: Success notifications and error handling
- **Responsive Design**: Works on desktop and mobile devices

## Database Changes
The feature adds:
1. **New Table**: `wp_oo_stream_activity_log` - Stores all activity history
2. **New Column**: `current_phase_id` in `wp_oo_job_streams_link` - Tracks current phase

## How to Use

### Accessing the Kanban Board

1. **From WordPress Admin**:
   - Go to Jobs → Phase Kanban
   - Select a job and stream
   - Click "View Kanban Board"

2. **Direct Link**:
   - Use URL: `/wp-admin/admin.php?page=oo_kanban_board&job_id=X&stream=SLUG`
   - Replace X with job ID and SLUG with stream slug

### Using the Board

1. **Moving Jobs Between Phases**:
   - Drag a job card from one phase column to another
   - Confirm the move when prompted
   - The change is logged automatically

2. **Viewing Activity Log**:
   - Click the history icon on any job card
   - See all phase changes and notes
   - Includes user names and timestamps

3. **Adding Notes**:
   - Click the edit icon on any job card
   - Enter your note and select a type
   - Notes are saved to the activity log

## Migration
The feature includes automatic migration that:
- Creates the activity log table
- Adds the current_phase_id column
- Backfills existing jobs to their first phase
- Creates initial activity entries

## Technical Details

### File Structure
```
features/kanban/
├── index.php         # Main entry point
├── ajax.php          # AJAX handlers
├── schema.php        # Database schema
├── migration.php     # Migration logic
├── database.php      # Database operations
└── views/
    └── board-view.php # UI template

assets/
├── js/features/kanban/
│   └── main.js       # Drag-drop logic
└── css/features/kanban/
    └── kanban.css    # Styling
```

### Activity Types
- `PHASE_CHANGE` - Job moved between phases
- `JOB_CREATED` - Initial phase assignment
- `NOTE_ADDED` - User added a note
- `STATUS_CHANGE` - Status updated
- `ASSIGNMENT_CHANGE` - Manager changed
- `DATE_CHANGE` - Dates modified
- `CUSTOM` - Other activities

### JavaScript Events
The feature fires these WordPress actions:
- `oo_kanban_phase_changed` - After successful phase change

### Security
- All actions require `oo_get_capability()` permission
- Nonce verification on all AJAX requests
- Data sanitization and validation
- SQL injection prevention

## Troubleshooting

### Jobs Not Showing
- Ensure job has the selected stream assigned
- Check that stream has active phases
- Verify job_stream record has current_phase_id

### Drag-Drop Not Working
- Check browser console for JavaScript errors
- Ensure jQuery UI is loaded
- Verify user has proper permissions

### Activity Log Empty
- Run migration manually if needed
- Check database table exists
- Verify user permissions

## Future Enhancements
- Multi-job selection and bulk moves
- Filtering by assignee or date
- Column work-in-progress limits
- Time tracking integration
- Export activity reports
- Email notifications on phase changes 