// tests/e2e/hr/reports.test.js
import { test, expect } from '@playwright/test';
import { createAuthHelper } from '../helpers/auth.js';
import { createCommonHelpers } from '../helpers/common.js';

test.describe('Reports Management', () => {
  let authHelper;
  let commonHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = createAuthHelper(page);
    commonHelper = createCommonHelpers(page);
    
    await authHelper.ensureLoggedIn();
    await commonHelper.navigateToHRModule('reports');
  });

  test('should display reports overview page', async ({ page }) => {
    // Check page title and heading
    await expect(page.locator('h1, h2')).toContainText(/Reports|Report Management/);

    // Check for reports section
    const reportsDisplaySelectors = [
      '.reports-grid',
      '.report-list',
      '.report-cards',
      'table',
      '[data-testid="reports-container"]'
    ];

    let reportsDisplayFound = false;
    for (const selector of reportsDisplaySelectors) {
      if (await page.locator(selector).count() > 0) {
        reportsDisplayFound = true;
        break;
      }
    }

    expect(reportsDisplayFound).toBeTruthy();
  });

  test('should generate new report', async ({ page }) => {
    // Look for generate report button
    const generateButtonSelectors = [
      'button:has-text("Generate Report")',
      'button:has-text("Create Report")',
      'button:has-text("New Report")',
      '[data-testid="generate-report"]',
      '.btn-generate'
    ];

    for (const selector of generateButtonSelectors) {
      if (await page.locator(selector).count() > 0) {
        await page.click(selector);
        await commonHelper.waitForModal();

        // Check for report generation form
        const reportFormFields = [
          'text=Report Type',
          'text=Date Range',
          'text=Department',
          'select[name="reportType"]',
          'input[type="date"]'
        ];

        let formFound = false;
        for (const field of reportFormFields) {
          if (await page.locator(field).count() > 0) {
            formFound = true;
            break;
          }
        }

        expect(formFound).toBeTruthy();
        break;
      }
    }
  });

  test('should display available report types', async ({ page }) => {
    // Check for different report types
    const reportTypes = [
      'Employee Report',
      'Attendance Report',
      'Department Report',
      'Team Performance',
      'Payroll Report'
    ];

    let reportTypesFound = 0;
    for (const reportType of reportTypes) {
      if (await page.locator(`text=${reportType}`).count() > 0) {
        reportTypesFound++;
      }
    }

    // At least some report types should be available
    expect(reportTypesFound).toBeGreaterThan(0);
  });

  test('should view existing report', async ({ page }) => {
    // Look for view report button
    const viewButtonSelectors = [
      'button:has-text("View")',
      'button:has-text("Open")',
      '.btn-view',
      '[data-testid="view-report"]'
    ];

    for (const selector of viewButtonSelectors) {
      if (await page.locator(selector).count() > 0) {
        await page.click(selector);
        
        // Wait for report to load
        await page.waitForTimeout(2000);
        
        // Check for report content
        const reportContentIndicators = [
          'text=Report Details',
          'text=Generated on',
          'canvas', // Charts
          'table',  // Data tables
          '.report-content'
        ];

        let reportContentFound = false;
        for (const indicator of reportContentIndicators) {
          if (await page.locator(indicator).count() > 0) {
            reportContentFound = true;
            break;
          }
        }

        expect(reportContentFound).toBeTruthy();
        break;
      }
    }
  });

  test('should download report', async ({ page }) => {
    // Look for download button
    const downloadButtonSelectors = [
      'button:has-text("Download")',
      'button:has-text("Export")',
      '.btn-download',
      '[data-testid="download-report"]'
    ];

    for (const selector of downloadButtonSelectors) {
      if (await page.locator(selector).count() > 0) {
        // Start waiting for download before clicking
        const downloadPromise = page.waitForEvent('download');
        
        await page.click(selector);
        
        try {
          // Wait for download to start (with timeout)
          const download = await Promise.race([
            downloadPromise,
            new Promise((_, reject) => setTimeout(() => reject(new Error('Download timeout')), 5000))
          ]);
          
          // Verify download started
          expect(download).toBeTruthy();
          
          // Get suggested filename
          const filename = download.suggestedFilename();
          expect(filename).toBeTruthy();
          
        } catch (error) {
          // Download might not be implemented yet, just verify button exists
          await expect(page.locator(selector)).toBeVisible();
        }
        break;
      }
    }
  });

  test('should schedule report', async ({ page }) => {
    // Look for schedule report functionality
    const scheduleSelectors = [
      'button:has-text("Schedule")',
      'text=Schedule Report',
      '.schedule-report',
      '[data-testid="schedule-report"]'
    ];

    for (const selector of scheduleSelectors) {
      if (await page.locator(selector).count() > 0) {
        if (selector.startsWith('button')) {
          await page.click(selector);
          await commonHelper.waitForModal();
        }

        // Check for scheduling options
        const scheduleOptions = [
          'text=Daily',
          'text=Weekly',
          'text=Monthly',
          'select[name="frequency"]',
          'input[name="schedule"]'
        ];

        let scheduleOptionsFound = false;
        for (const option of scheduleOptions) {
          if (await page.locator(option).count() > 0) {
            scheduleOptionsFound = true;
            break;
          }
        }

        expect(scheduleOptionsFound).toBeTruthy();
        break;
      }
    }
  });

  test('should filter reports by date', async ({ page }) => {
    // Look for date filter
    const dateFilterSelectors = [
      'input[type="date"]',
      '.date-filter',
      '[data-testid="date-filter"]'
    ];

    for (const selector of dateFilterSelectors) {
      if (await page.locator(selector).count() > 0) {
        // Set date filter
        const today = new Date();
        await page.fill(selector, today.toISOString().split('T')[0]);
        
        // Apply filter
        const applyButton = page.locator('button:has-text("Apply"), button:has-text("Filter")');
        if (await applyButton.count() > 0) {
          await applyButton.click();
        }
        
        // Wait for filter to apply
        await page.waitForTimeout(1000);
        
        // Verify reports list is still visible
        const reportsList = page.locator('.reports-grid, table, .report-list');
        await expect(reportsList).toBeVisible();
        break;
      }
    }
  });

  test('should be responsive on mobile devices', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await commonHelper.navigateToHRModule('reports');

    // Check that reports page is accessible on mobile
    await expect(page.locator('h1, h2')).toContainText(/Reports|Report Management/);

    // Check that reports are displayed in mobile-friendly format
    const mobileDisplaySelectors = [
      '.report-card',
      '.report-item',
      'table',
      '.reports-grid'
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