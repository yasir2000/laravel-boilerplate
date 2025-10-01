// tests/e2e/helpers/common.js
import { expect } from '@playwright/test';

export class CommonHelpers {
  constructor(page) {
    this.page = page;
  }

  async waitForPageLoad() {
    await this.page.waitForLoadState('networkidle');
  }

  async navigateToHRModule(module) {
    const moduleUrls = {
      dashboard: '/hr/dashboard',
      employees: '/hr/employees',
      teams: '/hr/teams',
      departments: '/hr/departments',
      attendance: '/hr/attendance',
      reports: '/hr/reports',
      settings: '/hr/settings'
    };

    const url = moduleUrls[module.toLowerCase()];
    if (!url) {
      throw new Error(`Unknown HR module: ${module}`);
    }

    await this.page.goto(url);
    await this.waitForPageLoad();
  }

  async waitForModal(modalSelector = '.modal, .fixed.inset-0') {
    await this.page.waitForSelector(modalSelector, { state: 'visible' });
  }

  async closeModal() {
    // Try different common close button selectors
    const closeSelectors = [
      '[data-testid="close-modal"]',
      '.modal-close',
      'button:has-text("Close")',
      'button:has-text("Cancel")',
      '.close',
      '[aria-label="Close"]'
    ];

    for (const selector of closeSelectors) {
      try {
        await this.page.click(selector, { timeout: 1000 });
        return;
      } catch (error) {
        // Continue to next selector
      }
    }

    // If no close button found, try pressing Escape
    await this.page.keyboard.press('Escape');
  }

  async fillForm(formData) {
    for (const [field, value] of Object.entries(formData)) {
      const input = this.page.locator(`input[name="${field}"], select[name="${field}"], textarea[name="${field}"]`);
      
      if (await input.count() > 0) {
        const tagName = await input.first().evaluate(el => el.tagName.toLowerCase());
        
        if (tagName === 'select') {
          await input.selectOption(value);
        } else {
          await input.fill(value.toString());
        }
      }
    }
  }

  async submitForm(submitButtonText = 'Submit') {
    await this.page.click(`button:has-text("${submitButtonText}"), input[type="submit"]`);
  }

  async waitForSuccessMessage(message = '') {
    if (message) {
      await this.page.waitForSelector(`text=${message}`, { timeout: 10000 });
    } else {
      // Wait for any success indicator
      await this.page.waitForSelector('.alert-success, .success, .bg-green, text=Success', { timeout: 10000 });
    }
  }

  async waitForErrorMessage(message = '') {
    if (message) {
      await this.page.waitForSelector(`text=${message}`, { timeout: 10000 });
    } else {
      // Wait for any error indicator
      await this.page.waitForSelector('.alert-error, .error, .bg-red, text=Error', { timeout: 10000 });
    }
  }

  async checkTableContainsData(tableSelector = 'table', expectedData) {
    const table = this.page.locator(tableSelector);
    await expect(table).toBeVisible();

    for (const data of expectedData) {
      await expect(table.locator(`text=${data}`)).toBeVisible();
    }
  }

  async takeScreenshot(name) {
    await this.page.screenshot({ 
      path: `playwright-report/screenshots/${name}-${Date.now()}.png`,
      fullPage: true 
    });
  }
}

export const createCommonHelpers = (page) => new CommonHelpers(page);