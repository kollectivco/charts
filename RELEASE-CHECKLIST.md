# Release Checklist: Charts Plugin

Follow these steps for every new version release to ensure the updater finds the update correctly.

## 1. Version Bump
- [ ] Update `Version` in `charts.php` header.
- [ ] Update `CHARTS_VERSION` constant in `charts.php`.
- [ ] (Optional) Update version in internal docs if any.

## 2. Commit & Tag
- [ ] Commit all changes with a descriptive message like "Bump version to 1.1.0".
- [ ] Create a local tag with the `v` prefix:
  ```bash
  git tag v1.1.0
  ```
- [ ] Push the tag to GitHub:
  ```bash
  git push origin v1.1.0
  ```

## 3. GitHub Automation
- [ ] Monitor GitHub Actions. Once the `push --tags` occurs, the `Release Plugin` workflow will trigger.
- [ ] Verify the action builds `charts-v1.1.0.zip`.
- [ ] Verify the ZIP is attached as a **Release Asset** in the newly created GitHub Release.

## 4. Verification
- [ ] Log into a WordPress site with an OLDER version of the plugin.
- [ ] Go to **Dashboard > Updates**.
- [ ] Click **Check Again**.
- [ ] The Charts plugin should offer an update to the new version.
- [ ] Click **Update Now** and verify it completes without error.
- [ ] Verify the folder name remains `charts` (or your chosen plugin folder) post-update.

## 5. Troubleshooting
- If update is not detected:
  - Check `https://api.github.com/repos/kollectivco/charts/releases/latest`.
  - Ensure `tag_name` is correct (`v1.1.0`).
  - Ensure at least one `.zip` asset exists.
- If update fails to unpack:
  - Verify the ZIP structure (it should contain a single `charts/` folder at root, or just the files – but the workflow creates `charts/` for safety).
  ```
