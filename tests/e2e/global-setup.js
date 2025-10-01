// tests/e2e/global-setup.js
const { chromium } = require('@playwright/test');

async function globalSetup() {
  console.log('🚀 Starting Playwright Global Setup...');
  
  // You can add global setup tasks here like:
  // - Database seeding
  // - Environment variable setup
  // - Cache clearing
  
  // Create test user if needed
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  try {
    // Check if application is running
    await page.goto('http://localhost:8000');
    console.log('✅ Laravel application is running');
  } catch (error) {
    console.error('❌ Laravel application is not running. Please start it with: php artisan serve');
    throw error;
  } finally {
    await browser.close();
  }
  
  console.log('✅ Global setup completed');
}

module.exports = globalSetup;