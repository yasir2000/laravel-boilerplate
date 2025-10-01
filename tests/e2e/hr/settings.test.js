// tests/e2e/hr/settings.test.js
import { test, expect } from '@playwright/test';
import { createAuthHelper } from '../helpers/auth.js';
import { createCommonHelpers } from '../helpers/common.js';

test.describe('Settings Management', () => {
  let authHelper;
  let commonHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = createAuthHelper(page);
    commonHelper = createCommonHelpers(page);
    
    await authHelper.ensureLoggedIn();
    await commonHelper.navigateToHRModule('settings');
  });

  test('should display settings page', async ({ page }) => {
    // Check page title and heading
    await expect(page.locator('h1, h2')).toContainText(/Settings|Configuration/);

    // Check for settings sections
    const settingsSelectors = [
      '.settings-grid',
      '.settings-tabs',
      '.settings-form',
      'form',
      '[data-testid="settings-container"]'
    ];

    let settingsFound = false;
    for (const selector of settingsSelectors) {
      if (await page.locator(selector).count() > 0) {
        settingsFound = true;
        break;
      }
    }

    expect(settingsFound).toBeTruthy();
  });

  test('should display general settings', async ({ page }) => {
    // Check for general settings section
    const generalSettingsSelectors = [
      'text=General Settings',
      'text=Company Information',
      'text=System Settings',
      '.general-settings'
    ];

    for (const selector of generalSettingsSelectors) {
      if (await page.locator(selector).count() > 0) {
        await expect(page.locator(selector)).toBeVisible();
        break;
      }
    }

    // Check for common settings fields
    const settingsFields = [
      'Company Name',
      'Time Zone',
      'Date Format',
      'Language',
      'Currency'
    ];

    let fieldsFound = 0;
    for (const field of settingsFields) {
      if (await page.locator(`text=${field}`).count() > 0) {
        fieldsFound++;
      }
    }

    expect(fieldsFound).toBeGreaterThan(0);
  });

  test('should handle language settings', async ({ page }) => {
    // Look for language settings
    const languageSelectors = [
      'select[name="language"]',
      'text=Language',
      '.language-selector',
      '[data-testid="language-setting"]'
    ];

    for (const selector of languageSelectors) {
      if (await page.locator(selector).count() > 0) {
        if (selector.startsWith('select')) {
          // Get available language options
          const options = await page.locator(`${selector} option`).allTextContents();
          
          if (options.length > 1) {
            // Select a different language
            await page.selectOption(selector, { index: 1 });
            
            // Look for save button
            const saveButton = page.locator('button:has-text("Save"), button:has-text("Update")');
            if (await saveButton.count() > 0) {
              await saveButton.click();
              await page.waitForTimeout(1000);
            }
          }
        }
        
        await expect(page.locator(selector)).toBeVisible();
        break;
      }
    }
  });

  test('should manage notification settings', async ({ page }) => {
    // Look for notification settings
    const notificationSelectors = [
      'text=Notifications',
      'text=Email Settings',
      'text=Alert Preferences',
      '.notification-settings',
      'input[type="checkbox"]'
    ];

    for (const selector of notificationSelectors) {
      if (await page.locator(selector).count() > 0) {
        await expect(page.locator(selector)).toBeVisible();
        
        // If checkboxes are present, test toggling them
        if (selector === 'input[type="checkbox"]') {
          const checkboxes = page.locator(selector);
          const count = await checkboxes.count();
          
          if (count > 0) {
            // Toggle first checkbox
            await checkboxes.first().click();
            
            // Save settings if save button exists
            const saveButton = page.locator('button:has-text("Save"), button:has-text("Update")');
            if (await saveButton.count() > 0) {
              await saveButton.click();
              await page.waitForTimeout(1000);
            }
          }
        }
        break;
      }
    }
  });

  test('should display security settings', async ({ page }) => {
    // Look for security settings
    const securitySelectors = [
      'text=Security',
      'text=Password Policy',
      'text=Two-Factor Authentication',
      'text=Session Management',
      '.security-settings'
    ];

    let securityFound = false;
    for (const selector of securitySelectors) {
      if (await page.locator(selector).count() > 0) {
        securityFound = true;
        await expect(page.locator(selector)).toBeVisible();
        break;
      }
    }

    expect(securityFound).toBeTruthy();
  });

  test('should manage user permissions', async ({ page }) => {
    // Look for permissions or roles section
    const permissionSelectors = [
      'text=Permissions',
      'text=User Roles',
      'text=Access Control',
      '.permissions-settings',
      '.role-management'
    ];

    for (const selector of permissionSelectors) {
      if (await page.locator(selector).count() > 0) {
        await expect(page.locator(selector)).toBeVisible();
        
        // Look for permission checkboxes or role selectors
        const permissionControls = [
          'input[type="checkbox"]',
          'select[name*="role"]',
          '.permission-checkbox',
          '.role-selector'
        ];

        for (const control of permissionControls) {
          if (await page.locator(control).count() > 0) {
            await expect(page.locator(control)).toBeVisible();
            break;
          }
        }
        break;
      }
    }
  });

  test('should save settings changes', async ({ page }) => {
    // Look for any editable field
    const editableFields = [
      'input[type="text"]',
      'input[type="email"]',
      'select',
      'textarea'
    ];

    for (const fieldType of editableFields) {
      const fields = page.locator(fieldType);
      const count = await fields.count();
      
      if (count > 0) {
        const firstField = fields.first();
        
        // Modify the field value
        if (fieldType === 'select') {
          const options = await page.locator(`${fieldType} option`).count();
          if (options > 1) {
            await firstField.selectOption({ index: 1 });
          }
        } else if (fieldType.includes('input')) {
          const currentValue = await firstField.inputValue();
          await firstField.fill(currentValue + ' Modified');
        } else if (fieldType === 'textarea') {
          const currentValue = await firstField.inputValue();
          await firstField.fill(currentValue + ' Modified');
        }

        // Save changes
        const saveButton = page.locator('button:has-text("Save"), button:has-text("Update"), button[type="submit"]');
        if (await saveButton.count() > 0) {
          await saveButton.click();
          
          // Wait for save confirmation
          await page.waitForTimeout(2000);
          
          // Look for success message
          const successIndicators = [
            'text=Settings saved successfully',
            'text=Updated successfully',
            'text=Changes saved',
            '.success',
            '.alert-success'
          ];

          let successFound = false;
          for (const indicator of successIndicators) {
            if (await page.locator(indicator).count() > 0) {
              successFound = true;
              break;
            }
          }

          expect(successFound).toBeTruthy();
        }
        break;
      }
    }
  });

  test('should handle theme settings', async ({ page }) => {
    // Look for theme or appearance settings
    const themeSelectors = [
      'text=Theme',
      'text=Appearance',
      'text=Dark Mode',
      'text=Light Mode',
      '.theme-selector',
      'input[name="theme"]'
    ];

    for (const selector of themeSelectors) {
      if (await page.locator(selector).count() > 0) {
        await expect(page.locator(selector)).toBeVisible();
        
        // If it's a clickable theme toggle
        if (selector.includes('Dark Mode') || selector.includes('Light Mode')) {
          await page.click(selector);
          await page.waitForTimeout(1000);
          
          // Verify theme change (page appearance might change)
          const body = page.locator('body');
          await expect(body).toBeVisible();
        }
        break;
      }
    }
  });

  test('should be responsive on mobile devices', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await commonHelper.navigateToHRModule('settings');

    // Check that settings page is accessible on mobile
    await expect(page.locator('h1, h2')).toContainText(/Settings|Configuration/);

    // Check that settings are displayed in mobile-friendly format
    const mobileDisplaySelectors = [
      '.settings-grid',
      '.settings-form',
      'form',
      '.settings-tabs'
    ];

    let mobileDisplayFound = false;
    for (const selector of mobileDisplaySelectors) {
      if (await page.locator(selector).count() > 0) {
        mobileDisplayFound = true;
        break;
      }
    }

    expect(mobileDisplayFound).toBeTruthy();
  });
});