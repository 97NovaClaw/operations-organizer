# Stream Templates Directory (DEPRECATED)

**Note: This directory is no longer used as of version 1.5.3.12**

The job details stream tabs now use a fully dynamic approach that leverages the existing feature set system.

## How Stream Tabs Work Now

When displaying a stream tab on the job details page, the system:

1. **Checks for Feature Sets** - Gets all active feature sets assigned to the stream
2. **Operational Tools Check** - If the stream has the "operational_tools" feature set, it displays the standard job management interface (phase selector, logs, etc.)
3. **Renders Feature Sets** - All other feature sets are rendered in their designated areas
4. **Uses Hooks** - Provides hooks for extensions to add custom content

## Dynamic Content Areas

Each stream tab now has these dynamic areas:

- **Start Hook**: `oo_job_details_tab_start_{stream_slug}` - Add content at the beginning
- **Operational Tools**: Standard job management interface (if enabled)
- **Feature Sets**: Automatically rendered based on stream configuration
- **End Hook**: `oo_job_details_tab_end_{stream_slug}` - Add content at the end

## Benefits of the New Approach

- **No Manual Templates**: New streams work automatically based on their feature sets
- **Consistent with Stream Pages**: Uses the same feature set system as the main stream dashboard
- **Plugin Update Safe**: No custom templates to maintain across updates
- **Truly Modular**: Feature sets can be assigned/removed without code changes

## Example: Adding Custom Content

To add custom content to a specific stream's job details tab:

```php
// In your custom plugin or theme
add_action('oo_job_details_tab_start_art', function($job, $job_stream, $stream_id) {
    ?>
    <div class="custom-art-notice">
        <p>Special instructions for Art stream jobs...</p>
    </div>
    <?php
}, 10, 3);
```

## Legacy Support

If you have existing custom templates in this directory, they will be ignored. Please migrate any custom functionality to:
1. Feature sets (recommended)
2. Action hooks
3. Custom plugins 