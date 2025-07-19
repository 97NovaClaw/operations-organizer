# Stream Slug Fix Instructions

## The Problem
The stream feature sets were not showing on streams other than Storage because of a slug mismatch:
- Database slugs were generated with underscores: `soft_content`
- System expected slugs with dashes: `soft-content`

## New Debug Log System
A dedicated debug logging system has been added to track stream feature issues:

### Access Methods:
1. **Via Admin Menu**: Go to WordPress Admin → Stream Management → Debug Log
2. **Direct URL**: Visit `https://your-site.com/debug/view-stream-debug-log.php`

### Features:
- **Auto-refresh**: Enable auto-refresh to monitor logs in real-time
- **Clear Log**: Clear the log when troubleshooting is complete
- **Filter**: View last 50, 100, 500 lines or all entries

## The Solution
1. **Upload the fix files** to your WordPress root directory:
   - `debug_stream_slugs.php` - Shows current vs expected slugs
   - `fix_stream_slugs.php` - Fixes the incorrect slugs

2. **Run the debug script** (optional):
   - Visit: `https://your-site.com/debug_stream_slugs.php`
   - This will show you which slugs need fixing

3. **Run the fix script**:
   - Visit: `https://your-site.com/fix_stream_slugs.php`
   - This will update all stream slugs to the correct format

4. **Verify the fix**:
   - Visit each stream dashboard (Art, Content, Electronics, Soft Content)
   - You should now see the Operational Tools tabs (Phase Log Actions, Phase Dashboard, Phase & KPI Settings)

5. **Clean up**:
   - Delete both PHP files from your server after verification

## What was Fixed in the Code
- Updated slug generation in `includes/class-oo-db.php` migration
- Updated slug generation in `features/stream-management/form-handler.php`
- Both now use `sanitize_key()` directly for consistent slug generation

## Prevention
All new streams created after this fix will have the correct slug format automatically. 