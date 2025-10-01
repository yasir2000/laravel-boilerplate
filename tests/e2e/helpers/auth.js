// tests/e2e/helpers/auth.js
import { expect } from '@playwright/test';

export class AuthHelper {
  constructor(page) {
    this.page = page;
  }

  async login(email = 'test@example.com', password = 'password') {
    // Navigate to login page
    await this.page.goto('/login');
    
    // Wait for Inertia.js to load and render the form
    await this.page.waitForLoadState('networkidle');
    await this.page.waitForTimeout(2000);
    
    // Try multiple selectors for email field
    const emailSelectors = [
      'input[name="email"]',
      'input[type="email"]',
      'input[placeholder*="email" i]',
      '#email'
    ];
    
    const passwordSelectors = [
      'input[name="password"]',
      'input[type="password"]',
      'input[placeholder*="password" i]',
      '#password'
    ];
    
    const submitSelectors = [
      'button[type="submit"]',
      'button:has-text("Log in")',
      'button:has-text("Login")',
      'button:has-text("Sign in")',
      '.btn-primary'
    ];
    
    // Fill email field
    let emailFilled = false;
    for (const selector of emailSelectors) {
      if (await this.page.locator(selector).count() > 0) {
        await this.page.fill(selector, email);
        emailFilled = true;
        break;
      }
    }
    
    if (!emailFilled) {
      throw new Error('Could not find email input field');
    }
    
    // Fill password field
    let passwordFilled = false;
    for (const selector of passwordSelectors) {
      if (await this.page.locator(selector).count() > 0) {
        await this.page.fill(selector, password);
        passwordFilled = true;
        break;
      }
    }
    
    if (!passwordFilled) {
      throw new Error('Could not find password input field');
    }
    
    // Submit the login form
    let submitClicked = false;
    for (const selector of submitSelectors) {
      if (await this.page.locator(selector).count() > 0) {
        await this.page.click(selector);
        submitClicked = true;
        break;
      }
    }
    
    if (!submitClicked) {
      throw new Error('Could not find submit button');
    }
    
    // Wait for potential redirect or error
    await this.page.waitForTimeout(3000);
    
    // Check if we're redirected (successful login) or still on login page (failed)
    const currentUrl = this.page.url();
    if (currentUrl.includes('/login')) {
      // Still on login page - could be validation errors or wrong credentials
      console.log('Login attempt completed but still on login page');
    } else {
      // Successfully redirected
      console.log('Login successful, redirected to:', currentUrl);
    }
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
    // Check if already logged in by trying to access a protected route
    try {
      await this.page.goto('/hr/dashboard');
      await this.page.waitForTimeout(2000);
      
      // If we're redirected to login, we're not logged in
      if (this.page.url().includes('/login')) {
        await this.login(email, password);
      } else {
        // Check if page has some content that indicates we're logged in
        const loggedInIndicators = [
          'text=HR Dashboard',
          'text=Dashboard',
          'text=Welcome',
          '[data-testid="user-menu"]',
          '.user-menu',
          'text=Logout'
        ];
        
        let isLoggedIn = false;
        for (const indicator of loggedInIndicators) {
          if (await this.page.locator(indicator).count() > 0) {
            isLoggedIn = true;
            break;
          }
        }
        
        if (!isLoggedIn) {
          await this.login(email, password);
        }
      }
    } catch (error) {
      // If there's any error, try to login
      await this.login(email, password);
    }
  }
}

export const createAuthHelper = (page) => new AuthHelper(page);