# Charts Release Procedure

Follow these steps to create a clean, professional update for the Kontentainment Charts plugin.

## 1. Version Bump
1. Open `charts.php`.
2. Update the `Version:` header.
3. Update the `CHARTS_VERSION` constant.
4. Commit both changes.

## 2. Packaging (Avoiding __MACOSX)
WordPress and the Plugin Update Checker work best with clean ZIP files. Avoid using the MacOS right-click "Compress" feature as it adds hidden junk.

### The Standard Command (Terminal)
Run this command from the parent directory of the plugin:
```bash
zip -r kontentainment-charts.zip kontentainment-charts -x "*.DS_Store" -x "__MACOSX*" -x "*.git*"
```

### Key Packaging Rules:
- **Root Folder**: The ZIP must contain a single root folder named `kontentainment-charts`.
- **Exclusions**: Always exclude `.git`, `.DS_Store`, and `__MACOSX`.

## 3. GitHub Release
1. Push your latest code to the `main` branch.
2. Create a new Tag (e.g., `v1.6.7`) via the GitHub UI or command line.
3. Create a **New Release** matching that tag.
4. **Important**: Manually upload your `kontentainment-charts.zip` asset to the release.
   - The integrated Plugin Update Checker is configured to look for these ZIP assets.

## 4. Verification
1. Go to a WordPress site where the plugin is installed.
2. Navigate to **Dashboard > Updates**.
3. Click "Check Again".
4. The new version should appear with a "View Details" link and an "Update Now" option.

---
**Note**: The plugin now includes an `Integrity` service that will automatically try to purge `__MACOSX` folders if they accidentally make it into a zip, but following the packaging command above is the safest practice.
