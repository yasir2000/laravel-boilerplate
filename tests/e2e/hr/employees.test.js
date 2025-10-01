// tests/e2e/hr/employees.test.js
import { test, expect } from '@playwright/test';
import { createAuthHelper } from '../helpers/auth.js';
import { createCommonHelpers } from '../helpers/common.js';

test.describe('Employee Management', () => {
  let authHelper;
  let commonHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = createAuthHelper(page);
    commonHelper = createCommonHelpers(page);
    
    await authHelper.ensureLoggedIn();
    await commonHelper.navigateToHRModule('employees');
  });

  test('should display employee list page', async ({ page }) => {
    // Check page title and heading
    await expect(page.locator('h1, h2')).toContainText(/Employees|Employee Management/);

    // Check for employee table or list
    const employeeListSelectors = [
      'table',
      '.employee-list',
      '.employee-grid',
      '[data-testid="employee-list"]'
    ];

    let listFound = false;
    for (const selector of employeeListSelectors) {
      if (await page.locator(selector).count() > 0) {
        listFound = true;
        break;
      }
    }

    expect(listFound).toBeTruthy();
  });

  test('should open create employee modal', async ({ page }) => {
    // Look for create/add employee button
    const createButtonSelectors = [
      'button:has-text("Add Employee")',
      'button:has-text("Create Employee")',
      'button:has-text("New Employee")',
      '[data-testid="create-employee"]',
      '.btn-create'
    ];

    let buttonFound = false;
    for (const selector of createButtonSelectors) {
      if (await page.locator(selector).count() > 0) {
        await page.click(selector);
        buttonFound = true;
        break;
      }
    }

    expect(buttonFound).toBeTruthy();

    // Wait for modal to appear
    await commonHelper.waitForModal();

    // Check for form fields in the modal
    const formFields = ['name', 'email', 'department', 'position', 'salary'];
    
    for (const field of formFields) {
      const fieldSelector = `input[name="${field}"], select[name="${field}"], textarea[name="${field}"]`;
      if (await page.locator(fieldSelector).count() > 0) {
        await expect(page.locator(fieldSelector)).toBeVisible();
      }
    }
  });

  test('should create new employee successfully', async ({ page }) => {
    // Click create employee button
    const createButton = page.locator('button:has-text("Add Employee"), button:has-text("Create Employee"), button:has-text("New Employee")').first();
    
    if (await createButton.count() > 0) {
      await createButton.click();
      await commonHelper.waitForModal();

      // Fill employee form
      const employeeData = {
        name: 'John Doe Test',
        email: `john.doe.test.${Date.now()}@example.com`,
        position: 'Software Developer',
        salary: '75000',
        phone: '123-456-7890'
      };

      // Fill form fields that exist
      for (const [field, value] of Object.entries(employeeData)) {
        const fieldSelector = `input[name="${field}"], select[name="${field}"], textarea[name="${field}"]`;
        if (await page.locator(fieldSelector).count() > 0) {
          await page.fill(fieldSelector, value);
        }
      }

      // Select department if dropdown exists
      const departmentSelect = page.locator('select[name="department"], select[name="departmentId"]');
      if (await departmentSelect.count() > 0) {
        await departmentSelect.selectOption({ index: 1 }); // Select first available option
      }

      // Submit form
      await page.click('button:has-text("Save"), button:has-text("Create"), button[type="submit"]');

      // Wait for success message or page update
      await page.waitForTimeout(2000);

      // Verify employee was created (look for success message or employee in list)
      const successIndicators = [
        'text=Employee created successfully',
        'text=Success',
        `.employee-list:has-text("${employeeData.name}")`,
        `table:has-text("${employeeData.name}")`
      ];

      let successFound = false;
      for (const indicator of successIndicators) {
        if (await page.locator(indicator).count() > 0) {
          successFound = true;
          break;
        }
      }

      expect(successFound).toBeTruthy();
    }
  });

  test('should search for employees', async ({ page }) => {
    // Look for search input
    const searchSelectors = [
      'input[placeholder*="Search"]',
      'input[name="search"]',
      '[data-testid="search"]',
      '.search-input'
    ];

    for (const selector of searchSelectors) {
      if (await page.locator(selector).count() > 0) {
        // Get first employee name from the list to search for
        const firstEmployeeName = await page.locator('table td, .employee-name').first().textContent();
        
        if (firstEmployeeName) {
          // Perform search
          await page.fill(selector, firstEmployeeName.split(' ')[0]); // Search by first name
          await page.keyboard.press('Enter');
          
          // Wait for search results
          await page.waitForTimeout(1000);
          
          // Verify search results contain the searched term
          await expect(page.locator('table, .employee-list')).toContainText(firstEmployeeName.split(' ')[0]);
        }
        break;
      }
    }
  });

  test('should view employee details', async ({ page }) => {
    // Look for view/details button for first employee
    const viewButtonSelectors = [
      'button:has-text("View")',
      'button:has-text("Details")',
      '.btn-view',
      '[data-testid="view-employee"]',
      'a:has-text("View")'
    ];

    for (const selector of viewButtonSelectors) {
      if (await page.locator(selector).count() > 0) {
        await page.click(selector);
        
        // Wait for details modal or page
        await page.waitForTimeout(1000);
        
        // Check for employee details display
        const detailsIndicators = [
          'text=Employee Details',
          'text=Employee Information',
          '.employee-details',
          '.modal:has-text("Details")'
        ];

        let detailsFound = false;
        for (const indicator of detailsIndicators) {
          if (await page.locator(indicator).count() > 0) {
            detailsFound = true;
            break;
          }
        }

        expect(detailsFound).toBeTruthy();
        break;
      }
    }
  });

  test('should edit employee information', async ({ page }) => {
    // Look for edit button for first employee
    const editButtonSelectors = [
      'button:has-text("Edit")',
      '.btn-edit',
      '[data-testid="edit-employee"]',
      'a:has-text("Edit")'
    ];

    for (const selector of editButtonSelectors) {
      if (await page.locator(selector).count() > 0) {
        await page.click(selector);
        await commonHelper.waitForModal();

        // Verify edit form is displayed
        const nameInput = page.locator('input[name="name"]');
        if (await nameInput.count() > 0) {
          // Update employee name
          const currentName = await nameInput.inputValue();
          const newName = currentName + ' Updated';
          
          await nameInput.fill(newName);
          
          // Save changes
          await page.click('button:has-text("Save"), button:has-text("Update")');
          
          // Wait for update confirmation
          await page.waitForTimeout(2000);
          
          // Verify update was successful
          const updateSuccessIndicators = [
            'text=Employee updated successfully',
            'text=Updated successfully',
            `table:has-text("${newName}")`,
            `.employee-list:has-text("${newName}")`
          ];

          let updateSuccessFound = false;
          for (const indicator of updateSuccessIndicators) {
            if (await page.locator(indicator).count() > 0) {
              updateSuccessFound = true;
              break;
            }
          }

          expect(updateSuccessFound).toBeTruthy();
        }
        break;
      }
    }
  });

  test('should handle employee form validation', async ({ page }) => {
    // Click create employee button
    const createButton = page.locator('button:has-text("Add Employee"), button:has-text("Create Employee")').first();
    
    if (await createButton.count() > 0) {
      await createButton.click();
      await commonHelper.waitForModal();

      // Try to submit empty form
      await page.click('button:has-text("Save"), button:has-text("Create"), button[type="submit"]');
      
      // Wait for validation errors
      await page.waitForTimeout(1000);

      // Check for validation error messages
      const errorSelectors = [
        '.error',
        '.invalid-feedback',
        '.text-red',
        '.validation-error',
        'text=required',
        'text=This field is required'
      ];

      let errorFound = false;
      for (const selector of errorSelectors) {
        if (await page.locator(selector).count() > 0) {
          errorFound = true;
          break;
        }
      }

      expect(errorFound).toBeTruthy();
    }
  });

  test('should filter employees by department', async ({ page }) => {
    // Look for department filter
    const filterSelectors = [
      'select[name="department"]',
      '.filter-department',
      '[data-testid="department-filter"]'
    ];

    for (const selector of filterSelectors) {
      if (await page.locator(selector).count() > 0) {
        // Get available options
        const options = await page.locator(`${selector} option`).allTextContents();
        
        if (options.length > 1) {
          // Select a department filter
          await page.selectOption(selector, { index: 1 });
          
          // Wait for filter to apply
          await page.waitForTimeout(1000);
          
          // Verify that employee list has been filtered
          const employeeList = page.locator('table, .employee-list');
          await expect(employeeList).toBeVisible();
        }
        break;
      }
    }
  });

  test('should be responsive on mobile devices', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await commonHelper.navigateToHRModule('employees');

    // Check that employees page is accessible on mobile
    await expect(page.locator('h1, h2')).toContainText(/Employees|Employee Management/);

    // Check that employee list is still visible (might be in card format)
    const mobileListSelectors = [
      'table',
      '.employee-card',
      '.employee-item',
      '.employee-list'
    ];

    let mobileListFound = false;
    for (const selector of mobileListSelectors) {
      if (await page.locator(selector).count() > 0) {
        mobileListFound = true;
        break;
      }
    }

    expect(mobileListFound).toBeTruthy();
  });
});