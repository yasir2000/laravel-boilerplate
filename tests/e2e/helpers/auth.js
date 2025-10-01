// tests/e2e/helpers/auth.js
import { expect } from '@playwright/test';

export class AuthHelper {
  constructor(page) {
    this.page = page;
  }

  async login(email = 'test@example.com', password = 'password') {
    // Navigate to login page
    await this.page.goto('/login');
    
    // Wait for the login form to be visible
    await this.page.waitForSelector('form');
    
    // Fill in login credentials
    await this.page.fill('input[name="email"]', email);
    await this.page.fill('input[name="password"]', password);
    
    // Submit the login form
    await this.page.click('button[type="submit"]');
    
    // Wait for redirect to dashboard or home page
    await this.page.waitForURL(/\/(dashboard|home|hr)/);
    
    // Verify we're logged in
    await expect(this.page.locator('text=Dashboard')).toBeVisible();
  }

  async logout() {
    // Look for logout button in the user menu
    await this.page.click('[data-testid="user-menu"], .user-menu, .dropdown-toggle');
    
    // Click logout
    await this.page.click('text=Logout');
    
    // Wait for redirect to login page
    await this.page.waitForURL(/\/login/);
  }

  async register(userData = {}) {
    const defaultData = {
      name: 'Test User',
      email: 'test@example.com',
      password: 'password',
      password_confirmation: 'password'
    };
    
    const data = { ...defaultData, ...userData };
    
    // Navigate to register page
    await this.page.goto('/register');
    
    // Wait for the register form
    await this.page.waitForSelector('form');
    
    // Fill in registration data
    await this.page.fill('input[name="name"]', data.name);
    await this.page.fill('input[name="email"]', data.email);
    await this.page.fill('input[name="password"]', data.password);
    await this.page.fill('input[name="password_confirmation"]', data.password_confirmation);
    
    // Submit the form
    await this.page.click('button[type="submit"]');
    
    // Wait for successful registration
    await this.page.waitForURL(/\/(dashboard|home|hr)/);
  }

  async ensureLoggedIn(email = 'test@example.com', password = 'password') {
    // Check if already logged in
    try {
      await this.page.goto('/hr/dashboard');
      await this.page.waitForSelector('text=HR Dashboard', { timeout: 5000 });
      return; // Already logged in
    } catch (error) {
      // Not logged in, proceed with login
      await this.login(email, password);
    }
  }
}

export const createAuthHelper = (page) => new AuthHelper(page);