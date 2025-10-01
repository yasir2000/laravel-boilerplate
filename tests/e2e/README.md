# Playwright E2E Tests for HR Management System

This directory contains comprehensive end-to-end tests for the Laravel HR Management System using Playwright.

## 📁 Test Structure

```
tests/e2e/
├── helpers/
│   ├── auth.js          # Authentication helper functions
│   └── common.js        # Common test utilities
├── hr/
│   ├── dashboard.test.js    # HR Dashboard tests
│   ├── employees.test.js    # Employee Management tests
│   ├── teams.test.js        # Team Management tests
│   ├── departments.test.js  # Department Management tests
│   ├── attendance.test.js   # Attendance Management tests
│   ├── reports.test.js      # Reports Management tests
│   └── settings.test.js     # Settings Management tests
├── auth.test.js             # Authentication tests
├── global-setup.js          # Global test setup
└── global-teardown.js       # Global test cleanup
```

## 🚀 Running Tests

### Prerequisites

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Install Playwright browsers:**
   ```bash
   npx playwright install
   ```

3. **Start Laravel application:**
   ```bash
   php artisan serve
   ```

### Test Commands

```bash
# Run all tests
npm run test

# Run tests with browser UI visible
npm run test:headed

# Open Playwright Test UI for interactive testing
npm run test:ui

# Run tests in debug mode
npm run test:debug

# Show test report
npm run test:report

# Run specific test file
npx playwright test tests/e2e/hr/dashboard.test.js

# Run tests for specific browser
npx playwright test --project=chromium

# Run tests in parallel
npx playwright test --workers=4
```

## 🧪 Test Coverage

### ✅ Authentication Tests
- Login page functionality
- Form validation
- Successful authentication flow
- User session management

### ✅ HR Dashboard Tests
- Dashboard KPI display
- Navigation functionality
- Recent activities section
- Charts and analytics
- Responsive design
- Quick actions

### ✅ Employee Management Tests
- Employee list display
- Create new employee
- Edit employee information
- Employee search and filtering
- Form validation
- Employee details view
- Mobile responsiveness

### ✅ Team Management Tests
- Teams overview display
- Create new team
- Team member assignment
- Performance tracking
- Team details view
- Form validation
- Team search functionality

### ✅ Department Management Tests
- Departments overview
- Create new department
- Department employee management
- Add employees to departments
- Navigation to teams
- Form validation
- Department filtering

### ✅ Attendance Management Tests
- Attendance overview
- Check-in/check-out functionality
- Attendance history
- Date range filtering
- Attendance statistics

### ✅ Reports Management Tests
- Reports overview
- Generate new reports
- View existing reports
- Download reports
- Schedule reports
- Report filtering

### ✅ Settings Management Tests
- General settings
- Language configuration
- Notification preferences
- Security settings
- User permissions
- Theme settings
- Settings persistence

## 🔧 Configuration

### Playwright Configuration

The tests are configured in `playwright.config.js` with:

- **Multiple browsers:** Chromium, Firefox, WebKit
- **Mobile testing:** Mobile Chrome, Mobile Safari
- **Screenshots:** On failure
- **Video recording:** On failure
- **Trace collection:** On retry
- **Parallel execution:** Enabled
- **Automatic server startup:** Laravel dev server

### Test Environment

- **Base URL:** `http://localhost:8000`
- **Test user:** `test@example.com` / `password`
- **Database:** Test database with seeded data
- **Timeout:** 30 seconds per test
- **Retries:** 2 retries on CI

## 📊 Test Reports

Test results are automatically generated in multiple formats:

- **HTML Report:** `playwright-report/index.html`
- **JSON Report:** `playwright-report/results.json`
- **Screenshots:** `test-results/` (on failure)
- **Videos:** `test-results/` (on failure)
- **Traces:** Available for debugging

## 🔄 CI/CD Integration

Tests run automatically on:

- **Push to main/develop branches**
- **Pull requests to main/develop**
- **GitHub Actions workflow:** `.github/workflows/playwright.yml`

### CI Environment

- **OS:** Ubuntu Latest
- **PHP:** 8.2
- **Node.js:** 18
- **Database:** PostgreSQL 13
- **Browsers:** All Playwright browsers with dependencies

## 🐛 Debugging Tests

### Local Debugging

1. **Debug mode:**
   ```bash
   npm run test:debug
   ```

2. **Headed mode:**
   ```bash
   npm run test:headed
   ```

3. **Test UI:**
   ```bash
   npm run test:ui
   ```

### VS Code Integration

Install the Playwright VS Code extension for:
- Test discovery and running
- Debugging breakpoints
- Test generation
- Trace viewing

## 📝 Writing New Tests

### Test Structure

```javascript
import { test, expect } from '@playwright/test';
import { createAuthHelper } from '../helpers/auth.js';
import { createCommonHelpers } from '../helpers/common.js';

test.describe('Feature Name', () => {
  let authHelper;
  let commonHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = createAuthHelper(page);
    commonHelper = createCommonHelpers(page);
    
    await authHelper.ensureLoggedIn();
  });

  test('should perform specific action', async ({ page }) => {
    // Test implementation
  });
});
```

### Best Practices

1. **Use helper functions** for common actions
2. **Wait for elements** before interacting
3. **Use descriptive test names** that explain the behavior
4. **Test both happy path and edge cases**
5. **Include mobile responsiveness tests**
6. **Handle asynchronous operations properly**
7. **Clean up test data** when necessary

## 🔍 Helper Functions

### AuthHelper
- `login(email, password)` - Login user
- `logout()` - Logout user
- `register(userData)` - Register new user
- `ensureLoggedIn()` - Ensure user is authenticated

### CommonHelper
- `navigateToHRModule(module)` - Navigate to HR module
- `waitForModal()` - Wait for modal to appear
- `fillForm(formData)` - Fill form with data
- `waitForSuccessMessage()` - Wait for success notification
- `takeScreenshot(name)` - Take screenshot for debugging

## 📈 Performance Considerations

- Tests run in parallel by default
- Use `test.describe.serial()` for dependent tests
- Minimize page reloads between tests
- Use efficient selectors
- Implement proper wait strategies

## 🛠️ Maintenance

### Regular Tasks

1. **Update test data** when application changes
2. **Review failed tests** in CI
3. **Update selectors** if UI changes
4. **Add tests** for new features
5. **Remove obsolete tests** for removed features

### Monitoring

- **Test execution time** trends
- **Flaky test** identification
- **Coverage gaps** analysis
- **Browser compatibility** issues

---

## 📞 Support

For test-related issues:

1. Check the **test report** for detailed error information
2. Review **screenshots and videos** for visual debugging
3. Use **trace viewer** for step-by-step analysis
4. Run tests **locally** with debug mode
5. Consult **Playwright documentation** for advanced features

**Happy Testing! 🎭**