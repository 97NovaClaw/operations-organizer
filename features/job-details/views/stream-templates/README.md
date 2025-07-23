# Stream-Specific Templates for Job Details

This directory contains stream-specific templates for the job details page tabs.

## How It Works

When displaying a stream tab on the job details page, the system will:

1. First check if a stream-specific template exists in this directory
2. If found, use the stream-specific template
3. If not found, fall back to the generic `tab-content-view.php` template

## Creating a Stream-Specific Template

To create a custom template for a stream:

1. Create a new PHP file in this directory named: `{stream-slug}-tab.php`
   - For example: `art-tab.php`, `content-tab.php`, `electronics-tab.php`
   
2. The template will have access to these variables:
   - `$job` - The job object
   - `$job_stream` - The job stream link object (contains job_stream_id, status_in_stream, etc.)
   - `$stream_id` - The stream ID
   - `$stream_slug` - The stream slug

3. You can include any custom functionality, layouts, or features specific to that stream

## Example Template Structure

```php
<?php
/**
 * Custom tab template for [Stream Name] stream
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Your custom stream-specific content here
?>

<div class="custom-stream-tab">
    <h3><?php echo esc_html($job_stream->stream_name); ?> Details</h3>
    
    <!-- Custom content specific to this stream -->
    
</div>
```

## Benefits

- Each stream can have its own unique layout and functionality
- New streams automatically work with the generic template
- Easy to add custom features for specific streams without affecting others
- Maintains backward compatibility 