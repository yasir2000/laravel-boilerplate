// tests/e2e/hr/dashboard.test.js
import { test, expect } from '@playwright/test';
import { createAuthHelper } from '../helpers/auth.js';
import { createCommonHelpers } from '../helpers/common.js';

test.describe('HR Dashboard', () => {
  let authHelper;
  let commonHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = createAuthHelper(page);
    commonHelper = createCommonHelpers(page);
    
    // Ensure user is logged in before each test
    await authHelper.ensureLoggedIn();
  });

  test('should display HR dashboard with KPIs', async ({ page }) => {
    await commonHelper.navigateToHRModule('dashboard');

    // Check page title
    await expect(page).toHaveTitle(/HR Dashboard/);

    // Check main dashboard heading
    await expect(page.locator('h1, h2')).toContainText('HR Dashboard');

    // Check KPI cards are present
    const kpiCards = [
      'Total Employees',
      'Active Teams',
      'Departments',
      'Present Today',
      'On Leave',
      'New Hires'
    ];

    for (const kpi of kpiCards) {
      await expect(page.locator(`text=${kpi}`)).toBeVisible();
    }

    // Check that KPI values are displayed (should be numbers)
    const kpiValues = page.locator('.kpi-value, .text-2xl, .text-3xl, .text-4xl');
    const count = await kpiValues.count();
    expect(count).toBeGreaterThan(0);
  });

  test('should display recent activities section', async ({ page }) => {
    await commonHelper.navigateToHRModule('dashboard');

    // Check for recent activities section
    await expect(page.locator('text=Recent Activities')).toBeVisible();

    // Check that activities list exists
    const activitiesList = page.locator('.activity-item, .recent-activity, .activity-list li');
    const count = await activitiesList.count();
    
    if (count > 0) {
      // Verify activity items have timestamps and descriptions
      await expect(activitiesList.first()).toBeVisible();
    }
  });

  test('should display charts and analytics', async ({ page }) => {
    await commonHelper.navigateToHRModule('dashboard');

    // Check for charts section
    const chartSelectors = [
      'canvas',
      '.chart-container',
      '[data-chart]',
      '.apex-charts',
      '.echarts'
    ];

    let chartFound = false;
    for (const selector of chartSelectors) {
      if (await page.locator(selector).count() > 0) {
        chartFound = true;
        break;
      }
    }

    // If no charts found, check for chart placeholders or data tables
    if (!chartFound) {
      const alternativeSelectors = [
        'text=Employee Growth',
        'text=Department Distribution',
        'text=Attendance Trends',
        '.statistics',
        '.analytics'
      ];

      for (const selector of alternativeSelectors) {
        if (await page.locator(selector).count() > 0) {
          chartFound = true;
          break;
        }
      }
    }

    expect(chartFound).toBeTruthy();
  });

  test('should navigate to HR modules from dashboard', async ({ page }) => {
    await commonHelper.navigateToHRModule('dashboard');

    // Test navigation to different HR modules
    const moduleLinks = [
      { text: 'Employees', url: '/hr/employees' },
      { text: 'Teams', url: '/hr/teams' },
      { text: 'Departments', url: '/hr/departments' },
      { text: 'Attendance', url: '/hr/attendance' },
      { text: 'Reports', url: '/hr/reports' }
    ];

    for (const module of moduleLinks) {
      // Navigate back to dashboard
      await commonHelper.navigateToHRModule('dashboard');

      // Look for the module link and click it
      const linkSelector = `a:has-text("${module.text}"), [href="${module.url}"]`;
      
      if (await page.locator(linkSelector).count() > 0) {
        await page.click(linkSelector);
        await page.waitForURL(`**${module.url}**`);
        await expect(page).toHaveURL(new RegExp(module.url));
      }
    }
  });

  test('should display quick actions', async ({ page }) => {
    await commonHelper.navigateToHRModule('dashboard');

    // Check for quick action buttons
    const quickActions = [
      'Add Employee',
      'Create Team',
      'New Department',
      'Generate Report'
    ];

    let quickActionsFound = 0;
    for (const action of quickActions) {
      if (await page.locator(`button:has-text("${action}"), a:has-text("${action}")`).count() > 0) {
        quickActionsFound++;
      }
    }

    // At least some quick actions should be available
    expect(quickActionsFound).toBeGreaterThan(0);
  });

  test('should be responsive on mobile', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await commonHelper.navigateToHRModule('dashboard');

    // Check that dashboard is still accessible on mobile
    await expect(page.locator('h1, h2')).toContainText('HR Dashboard');

    // Check that KPIs are still visible (might be stacked)
    await expect(page.locator('text=Total Employees')).toBeVisible();

    // Check for mobile menu if present
    const mobileMenuSelectors = [
      '.mobile-menu',
      '.hamburger',
      '[data-testid="mobile-menu"]',
      'button[aria-label="Menu"]'
    ];

    for (const selector of mobileMenuSelectors) {
      if (await page.locator(selector).count() > 0) {
        await expect(page.locator(selector)).toBeVisible();
        break;
      }
    }
  });

  test('should handle dashboard data refresh', async ({ page }) => {
    await commonHelper.navigateToHRModule('dashboard');

    // Look for refresh button
    const refreshSelectors = [
      'button:has-text("Refresh")',
      '[data-testid="refresh"]',
      '.refresh-btn',
      '[aria-label="Refresh"]'
    ];

    for (const selector of refreshSelectors) {
      if (await page.locator(selector).count() > 0) {
        await page.click(selector);
        await commonHelper.waitForPageLoad();
        
        // Verify page is still functional after refresh
        await expect(page.locator('h1, h2')).toContainText('HR Dashboard');
        break;
      }
    }

    // If no refresh button, test manual page refresh
    await page.reload();
    await commonHelper.waitForPageLoad();
    await expect(page.locator('h1, h2')).toContainText('HR Dashboard');
  });

  test('should display user information and profile access', async ({ page }) => {
    await commonHelper.navigateToHRModule('dashboard');

    // Check for user information display
    const userInfoSelectors = [
      '.user-info',
      '.profile',
      '[data-testid="user-profile"]',
      '.user-name',
      '.avatar'
    ];

    let userInfoFound = false;
    for (const selector of userInfoSelectors) {
      if (await page.locator(selector).count() > 0) {
        userInfoFound = true;
        break;
      }
    }

    expect(userInfoFound).toBeTruthy();
  });
});