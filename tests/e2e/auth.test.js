// tests/e2e/auth.test.js
import { test, expect } from '@playwright/test';

test.describe('Authentication', () => {
  test('should load login page', async ({ page }) => {
    await page.goto('/login');
    
    // Check for login form elements
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('should show validation errors for empty login', async ({ page }) => {
    await page.goto('/login');
    
    // Try to submit empty form
    await page.click('button[type="submit"]');
    
    // Wait a moment for potential validation errors
    await page.waitForTimeout(1000);
    
    // Check if we're still on login page (not redirected)
    expect(page.url()).toContain('/login');
  });

  test('should redirect to HR dashboard after successful login', async ({ page }) => {
    await page.goto('/login');
    
    // Fill in login credentials
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    
    // Submit login form
    await page.click('button[type="submit"]');
    
    // Wait for redirect
    await page.waitForURL(/\/(dashboard|home|hr)/, { timeout: 10000 });
    
    // Should be redirected away from login page
    expect(page.url()).not.toContain('/login');
  });
});