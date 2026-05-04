# Delete Button - Debug & Testing Guide

## Changes Made

### 1. **Fixed CSRF Token Issue** (`http.js`)

The axios instance now includes the CSRF token from the meta tag in all requests:

```javascript
const http = axios.create({
    headers: {
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN":
            document.querySelector('meta[name="csrf-token"]')?.content || "",
    },
});
```

### 2. **Enhanced Error Logging**

All delete handlers now include detailed console logging:

- "Initializing delete handlers..." - When module loads
- "Delete button clicked!" - When user clicks delete
- "Deleting [item] [id]..." - Before API call
- Full error details if something goes wrong

### 3. **Fixed Formatting**

- Removed trailing space in http.js error handling

## Testing Steps

### Step 1: Build Assets

```bash
# In your WSL terminal:
npm run build
# OR
npm run dev  # for development with hot reload
```

### Step 2: Open Browser Console

1. Navigate to your TaskTracker application
2. Press `F12` or `Ctrl+Shift+I` to open Developer Tools
3. Go to the **Console** tab
4. Keep it open while testing

### Step 3: Test Delete Functionality

#### For Tasks Page:

1. Go to `/tasks` page
2. Look for the Console output: `"Initializing delete handlers for tasks..."`
3. Click any **Delete** button
4. You should see in console:
    - "Delete button clicked!"
    - A confirmation dialog appears
5. Click "OK" in confirmation
6. In console you should see:
    - "Deleting task [id]..."
    - "Task [id] deleted successfully" OR error details

#### For Categories Page:

1. Go to `/categories` page
2. Same steps, but look for: `"Initializing delete handlers for categories..."`
3. Data attribute used: `data-delete-category`

#### For Recurring Tasks Page:

1. Go to `/recurring-tasks` page
2. Same steps, but look for: `"Initializing delete handlers for recurring tasks..."`
3. Data attribute used: `data-delete-recurring-task`

## Troubleshooting

### Issue: Console shows "Initializing..." but clicking delete does nothing

**Solution:**

1. Check that the button has the correct `data-delete-*` attribute
2. Try clicking directly on the Delete button text
3. Check that the button is not inside another form element

### Issue: Console shows "Delete button clicked!" but then error appears

**Check these in Browser Console:**

- Look for the exact error message in red
- Common errors:
    - **419 error**: CSRF token issue - try `npm run build` again
    - **403 error**: Permission issue - check if you own the resource
    - **404 error**: Wrong URL path - verify routes are correct
    - **500 error**: Server error - check Laravel logs

### Issue: Row doesn't disappear even on success

**Solution:**

1. Hard refresh page (Ctrl+Shift+R)
2. Ensure you built with: `npm run build`
3. Check that `row.remove()` line wasn't modified

## Console Log Examples

### Successful Delete:

```
Initializing delete handlers for tasks...
Delete button clicked!
Deleting task 5...
Task 5 deleted successfully
```

### Failed Delete (419 CSRF Error):

```
Initializing delete handlers for tasks...
Delete button clicked!
Deleting task 5...
Delete failed: Error: Request failed with status code 419
Delete error details: {errors: {...}}
```

### Failed Delete (403 Permission):

```
Initializing delete handlers for tasks...
Delete button clicked!
Deleting task 5...
Delete failed: Error: Request failed with status code 403
Delete error details: {...}
```

## Next Steps

1. **Build assets**: `npm run build`
2. **Test each page** (Tasks, Categories, Recurring Tasks)
3. **Open browser console** and check for errors
4. **Share any error messages** you see in the console

If you see error messages in the console, please share:

- The exact error message
- Which page you were on
- Which item you were trying to delete
