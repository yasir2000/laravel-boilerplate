// tests/e2e/global-teardown.js
async function globalTeardown() {
  console.log('🧹 Starting Playwright Global Teardown...');
  
  // You can add cleanup tasks here like:
  // - Clear test data
  // - Reset database
  // - Clean up files
  
  console.log('✅ Global teardown completed');
}

module.exports = globalTeardown;