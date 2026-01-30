Migration Fixes for MyRVM-Server/public/vendor

1. Fixed TypeError in helpers.js regarding 'settings' of undefined.
   - Added optional chaining to safely access window.templateCustomizer.settings.

2. Updated main.js for robustness:
   - Added checks for window.Helpers to prevent crashes if helpers.js is not loaded.
   - Added checks for Menu class to prevent crashes if menu.js is not loaded.
   - Added optional chaining for templateCustomizer settings.

3. Updated config.js:
   - Added a warning if data-assets-path is missing in HTML, which helps debug path issues.

4. Verification:
   - Open verification_test.html in your browser to verify that the scripts load correctly without errors.
   - If you see green boxes, the core logic is working.
   - If you see red boxes, check the console for details.

5. Instructions for HTML files:
   - Ensure your HTML files point to the correct location of assets.
   - Update <script src="..."> tags to point to "vendor/assets/..." or wherever the files are served from.
   - Ensure <html data-assets-path="..."> matches the new structure.
