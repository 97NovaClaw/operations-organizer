# Stream Slug Fix Instructions

## The Problem
The stream feature sets were not showing on streams other than Storage because of a slug mismatch:
- Database slugs may have underscores or dashes: `soft_content` or `soft-content`
- System expects slugs from `sanitize_key()`: `softcontent` (no separators)

## Integrated Debug Tools

### 1. Stream Debug Log
Access via: **WordPress Admin → Stream Management → Debug Log**
- Shows real-time stream lookup attempts
- Displays database vs expected slugs
- Auto-refresh capability for monitoring
- Clear log functionality

### 2. Debug Stream Slugs
Access via: **WordPress Admin → Stream Management → Debug Slugs**
- Shows all streams with their current vs expected slugs
- Highlights mismatches with visual indicators
- Direct link to fix tool when issues detected

### 3. Fix Stream Slugs
Access from Debug Slugs page when mismatches are found
- Shows before/after slug changes
- One-click fix for all mismatches
- Safe duplicate checking
- Success/error reporting

## How to Use

1. **Check the Debug Log**:
   - Go to Stream Management → Debug Log
   - Enable auto-refresh
   - Visit problem streams to see lookup failures

2. **View Slug Status**:
   - Go to Stream Management → Debug Slugs
   - Review the comparison table
   - Look for red "Mismatch" indicators

3. **Fix Mismatches**:
   - Click "Fix Stream Slugs" button if mismatches exist
   - Review the changes that will be made
   - Click "Fix All Stream Slugs" to apply

4. **Verify**:
   - Visit each stream dashboard
   - Confirm Operational Tools tabs now appear
   - Check Debug Slugs page shows all green

## Technical Details
The system uses WordPress's `sanitize_key()` function which:
- Converts to lowercase
- Removes spaces and special characters
- "Soft Content" → `softcontent`
- "Vendor Management" → `vendormanagement`

## What was Fixed in the Code
- Updated slug generation in `includes/class-oo-db.php` migration
- Updated slug generation in `features/stream-management/form-handler.php`
- Both now use `sanitize_key()` directly for consistent slug generation
- Added integrated debug and fix tools in admin menu

## Prevention
All new streams created after this fix will have the correct slug format automatically. 