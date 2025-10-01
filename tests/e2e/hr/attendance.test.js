// tests/e2e/hr/attendance.test.js
import { test, expect } from '@playwright/test';
import { createAuthHelper } from '../helpers/auth.js';
import { createCommonHelpers } from '../helpers/common.js';

test.describe('Attendance Management', () => {
  let authHelper;
  let commonHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = createAuthHelper(page);
    commonHelper = createCommonHelpers(page);
    
    await authHelper.ensureLoggedIn();
    await commonHelper.navigateToHRModule('attendance');
  });

  test('should display attendance overview page', async ({ page }) => {
    // Check page title and heading
    await expect(page.locator('h1, h2')).toContainText(/Attendance|Time Tracking/);

    // Check for attendance data display
    const attendanceDisplaySelectors = [
      '.attendance-grid',
      '.attendance-table',
      'table',
      '.attendance-records',
      '[data-testid="attendance-container"]'
    ];

    let attendanceDisplayFound = false;
    for (const selector of attendanceDisplaySelectors) {
      if (await page.locator(selector).count() > 0) {
        attendanceDisplayFound = true;
        break;
      }
    }

    expect(attendanceDisplayFound).toBeTruthy();
  });

  test('should display today\'s attendance status', async ({ page }) => {
    // Check for today's attendance indicators
    const todayAttendanceSelectors = [
      'text=Today',
      'text=Present Today',
      'text=Check In',
      'text=Check Out',
      '.today-attendance',
      '.attendance-status'
    ];

    let todayAttendanceFound = false;
    for (const selector of todayAttendanceSelectors) {
      if (await page.locator(selector).count() > 0) {
        todayAttendanceFound = true;
        break;
      }
    }

    expect(todayAttendanceFound).toBeTruthy();
  });

  test('should record check-in time', async ({ page }) => {
    // Look for check-in button
    const checkInSelectors = [
      'button:has-text("Check In")',
      'button:has-text("Clock In")',
      '[data-testid="check-in"]',
      '.btn-check-in'
    ];

    for (const selector of checkInSelectors) {
      if (await page.locator(selector).count() > 0) {
        await page.click(selector);
        
        // Wait for check-in to be processed
        await page.waitForTimeout(2000);
        
        // Verify check-in was recorded
        const checkInSuccessIndicators = [
          'text=Checked in successfully',
          'text=Check-in recorded',
          'button:has-text("Check Out")',
          '.checked-in-status'
        ];

        let checkInSuccessFound = false;
        for (const indicator of checkInSuccessIndicators) {
          if (await page.locator(indicator).count() > 0) {
            checkInSuccessFound = true;
            break;
          }
        }

        expect(checkInSuccessFound).toBeTruthy();
        break;
      }
    }
  });

  test('should view attendance history', async ({ page }) => {
    // Look for attendance history or records section
    const historySelectors = [
      'text=Attendance History',
      'text=Records',
      'text=Past Attendance',
      '.attendance-history',
      '.attendance-records'
    ];

    for (const selector of historySelectors) {
      if (await page.locator(selector).count() > 0) {
        await expect(page.locator(selector)).toBeVisible();
        
        // Check for attendance record entries
        const recordSelectors = [
          'table tr',
          '.attendance-record',
          '.attendance-entry'
        ];

        let recordsFound = false;
        for (const recordSelector of recordSelectors) {
          if (await page.locator(recordSelector).count() > 1) { // More than header
            recordsFound = true;
            break;
          }
        }

        expect(recordsFound).toBeTruthy();
        break;
      }
    }
  });

  test('should filter attendance by date range', async ({ page }) => {
    // Look for date filter inputs
    const dateFilterSelectors = [
      'input[type="date"]',
      '.date-picker',
      '[data-testid="date-filter"]'
    ];

    for (const selector of dateFilterSelectors) {
      if (await page.locator(selector).count() > 0) {
        // Set date filter
        const today = new Date();
        const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
        
        await page.fill(selector, weekAgo.toISOString().split('T')[0]);
        
        // Apply filter if there's a button
        const applyButton = page.locator('button:has-text("Apply"), button:has-text("Filter")');
        if (await applyButton.count() > 0) {
          await applyButton.click();
        }
        
        // Wait for filter to apply
        await page.waitForTimeout(1000);
        
        // Verify filter was applied
        const attendanceTable = page.locator('table, .attendance-records');
        await expect(attendanceTable).toBeVisible();
        break;
      }
    }
  });

  test('should display attendance statistics', async ({ page }) => {
    // Look for statistics section
    const statsSelectors = [
      'text=Statistics',
      'text=Summary',
      'text=Present Days',
      'text=Absent Days',
      'text=Hours Worked',
      '.stats',
      '.attendance-stats'
    ];

    let statsFound = false;
    for (const selector of statsSelectors) {
      if (await page.locator(selector).count() > 0) {
        statsFound = true;
        await expect(page.locator(selector)).toBeVisible();
        break;
      }
    }

    expect(statsFound).toBeTruthy();
  });
});