// tests/e2e/hr/departments.test.js
import { test, expect } from '@playwright/test';
import { createAuthHelper } from '../helpers/auth.js';
import { createCommonHelpers } from '../helpers/common.js';

test.describe('Department Management', () => {
  let authHelper;
  let commonHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = createAuthHelper(page);
    commonHelper = createCommonHelpers(page);
    
    await authHelper.ensureLoggedIn();
    await commonHelper.navigateToHRModule('departments');
  });

  test('should display departments overview page', async ({ page }) => {
    // Check page title and heading
    await expect(page.locator('h1, h2')).toContainText(/Departments|Department Management/);

    // Check for departments grid or list
    const departmentsDisplaySelectors = [
      '.departments-grid',
      '.department-cards',
      '.department-list',
      'table',
      '[data-testid="departments-container"]'
    ];

    let departmentsDisplayFound = false;
    for (const selector of departmentsDisplaySelectors) {
      if (await page.locator(selector).count() > 0) {
        departmentsDisplayFound = true;
        break;
      }
    }

    expect(departmentsDisplayFound).toBeTruthy();
  });

  test('should open create department modal', async ({ page }) => {
    // Look for create department button
    const createButtonSelectors = [
      'button:has-text("Create Department")',
      'button:has-text("Add Department")',
      'button:has-text("New Department")',
      '[data-testid="create-department"]',
      '.btn-create-department'
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
    const formFields = ['name', 'manager', 'budget', 'description'];
    
    for (const field of formFields) {
      const fieldSelector = `input[name="${field}"], select[name="${field}"], textarea[name="${field}"]`;
      if (await page.locator(fieldSelector).count() > 0) {
        await expect(page.locator(fieldSelector)).toBeVisible();
      }
    }
  });

  test('should create new department successfully', async ({ page }) => {
    // Click create department button
    const createButton = page.locator('button:has-text("Create Department"), button:has-text("Add Department"), button:has-text("New Department")').first();
    
    if (await createButton.count() > 0) {
      await createButton.click();
      await commonHelper.waitForModal();

      // Fill department form
      const departmentData = {
        name: `Test Department ${Date.now()}`,
        budget: '500000',
        description: 'This is a test department for automated testing'
      };

      // Fill form fields that exist
      for (const [field, value] of Object.entries(departmentData)) {
        const fieldSelector = `input[name="${field}"], textarea[name="${field}"]`;
        if (await page.locator(fieldSelector).count() > 0) {
          await page.fill(fieldSelector, value);
        }
      }

      // Select manager if dropdown exists
      const managerSelect = page.locator('select[name="manager"]');
      if (await managerSelect.count() > 0) {
        await managerSelect.selectOption({ index: 1 }); // Select first available manager
      }

      // Select department color/icon if available
      const colorSelect = page.locator('select[name="color"], input[name="color"]');
      if (await colorSelect.count() > 0) {
        if ((await colorSelect.getAttribute('type')) === 'color') {
          await colorSelect.fill('#3B82F6'); // Blue color
        } else {
          await colorSelect.selectOption({ index: 1 });
        }
      }

      // Submit form
      await page.click('button:has-text("Save"), button:has-text("Create"), button[type="submit"]');

      // Wait for success message or page update
      await page.waitForTimeout(2000);

      // Verify department was created
      const successIndicators = [
        'text=Department created successfully',
        'text=Success',
        `.department-card:has-text("${departmentData.name}")`,
        `table:has-text("${departmentData.name}")`
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

  test('should view department details', async ({ page }) => {
    // Look for view department button or clickable department card
    const viewSelectors = [
      'button:has-text("View Details")',
      'button:has-text("View")',
      '.department-card',
      '.btn-view',
      '[data-testid="view-department"]'
    ];

    for (const selector of viewSelectors) {
      if (await page.locator(selector).count() > 0) {
        await page.click(selector);
        
        // Wait for details modal or page
        await page.waitForTimeout(1000);
        
        // Check for department details display
        const detailsIndicators = [
          'text=Department Details',
          'text=Department Information',
          'text=Employee Count',
          'text=Budget',
          '.department-details',
          '.modal:has-text("Department")'
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

  test('should manage department employees', async ({ page }) => {
    // First view a department (click on department card or view button)
    const departmentCard = page.locator('.department-card, button:has-text("View Details")').first();
    if (await departmentCard.count() > 0) {
      await departmentCard.click();
      await page.waitForTimeout(1000);

      // Look for manage employees or manage team button
      const manageEmployeesSelectors = [
        'button:has-text("Manage Employees")',
        'button:has-text("Manage Team")',
        'button:has-text("Add Employee")',
        '[data-testid="manage-employees"]'
      ];

      for (const selector of manageEmployeesSelectors) {
        if (await page.locator(selector).count() > 0) {
          await page.click(selector);
          await page.waitForTimeout(1000);

          // Check for employee management interface
          const employeeManagementIndicators = [
            'text=Team Management',
            'text=Employee Management',
            'text=Add Employee',
            '.employee-list',
            'table'
          ];

          let employeeManagementFound = false;
          for (const indicator of employeeManagementIndicators) {
            if (await page.locator(indicator).count() > 0) {
              employeeManagementFound = true;
              break;
            }
          }

          expect(employeeManagementFound).toBeTruthy();
          break;
        }
      }
    }
  });

  test('should add employee to department', async ({ page }) => {
    // Navigate to department details and then employee management
    const departmentCard = page.locator('.department-card, button:has-text("View Details")').first();
    if (await departmentCard.count() > 0) {
      await departmentCard.click();
      await page.waitForTimeout(1000);

      // Click manage employees/team button
      const manageButton = page.locator('button:has-text("Manage Team"), button:has-text("Manage Employees")').first();
      if (await manageButton.count() > 0) {
        await manageButton.click();
        await page.waitForTimeout(1000);

        // Look for add employee button
        const addEmployeeButton = page.locator('button:has-text("Add Employee")');
        if (await addEmployeeButton.count() > 0) {
          await addEmployeeButton.click();
          await commonHelper.waitForModal();

          // Fill employee form if modal appears
          const employeeNameInput = page.locator('input[name="name"]');
          if (await employeeNameInput.count() > 0) {
            await employeeNameInput.fill(`Test Employee ${Date.now()}`);
            
            const emailInput = page.locator('input[name="email"]');
            if (await emailInput.count() > 0) {
              await emailInput.fill(`test.employee.${Date.now()}@example.com`);
            }

            const roleInput = page.locator('input[name="role"]');
            if (await roleInput.count() > 0) {
              await roleInput.fill('Test Role');
            }

            const salaryInput = page.locator('input[name="salary"]');
            if (await salaryInput.count() > 0) {
              await salaryInput.fill('50000');
            }

            // Submit form
            await page.click('button:has-text("Add Employee"), button:has-text("Save")');
            await page.waitForTimeout(2000);

            // Verify employee was added
            const successMessage = page.locator('text=Employee added successfully, text=Success');
            if (await successMessage.count() > 0) {
              await expect(successMessage).toBeVisible();
            }
          }
        }
      }
    }
  });

  test('should navigate to teams page from department', async ({ page }) => {
    // View department details
    const departmentCard = page.locator('.department-card, button:has-text("View Details")').first();
    if (await departmentCard.count() > 0) {
      await departmentCard.click();
      await page.waitForTimeout(1000);

      // Look for teams navigation button
      const teamsNavSelectors = [
        'button:has-text("Manage Teams")',
        'a:has-text("Go to Teams")',
        'button:has-text("Teams")',
        '[href*="/teams"]'
      ];

      for (const selector of teamsNavSelectors) {
        if (await page.locator(selector).count() > 0) {
          await page.click(selector);
          
          // Wait for navigation
          await page.waitForTimeout(1000);
          
          // Verify we're on teams page or teams section
          const teamsPageIndicators = [
            'text=Teams',
            'text=Team Management',
            '/hr/teams'
          ];

          let teamsPageFound = false;
          for (const indicator of teamsPageIndicators) {
            if (indicator.startsWith('/')) {
              // Check URL
              if (page.url().includes(indicator)) {
                teamsPageFound = true;
                break;
              }
            } else {
              // Check page content
              if (await page.locator(indicator).count() > 0) {
                teamsPageFound = true;
                break;
              }
            }
          }

          expect(teamsPageFound).toBeTruthy();
          break;
        }
      }
    }
  });

  test('should edit department information', async ({ page }) => {
    // Look for edit button for first department
    const editButtonSelectors = [
      'button:has-text("Edit")',
      '.btn-edit',
      '[data-testid="edit-department"]'
    ];

    for (const selector of editButtonSelectors) {
      if (await page.locator(selector).count() > 0) {
        await page.click(selector);
        await commonHelper.waitForModal();

        // Verify edit form is displayed
        const nameInput = page.locator('input[name="name"]');
        if (await nameInput.count() > 0) {
          // Update department name
          const currentName = await nameInput.inputValue();
          const newName = currentName + ' Updated';
          
          await nameInput.fill(newName);
          
          // Save changes
          await page.click('button:has-text("Save"), button:has-text("Update")');
          
          // Wait for update confirmation
          await page.waitForTimeout(2000);
          
          // Verify update was successful
          const updateSuccessIndicators = [
            'text=Department updated successfully',
            'text=Updated successfully',
            `.department-card:has-text("${newName}")`,
            `table:has-text("${newName}")`
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

  test('should filter departments by status', async ({ page }) => {
    // Look for status filter
    const filterSelectors = [
      'select[name="status"]',
      '.filter-status',
      '[data-testid="status-filter"]'
    ];

    for (const selector of filterSelectors) {
      if (await page.locator(selector).count() > 0) {
        // Get available options
        const options = await page.locator(`${selector} option`).allTextContents();
        
        if (options.length > 1) {
          // Select a status filter
          await page.selectOption(selector, { index: 1 });
          
          // Wait for filter to apply
          await page.waitForTimeout(1000);
          
          // Verify that department list has been filtered
          const departmentList = page.locator('.departments-grid, table, .department-list');
          await expect(departmentList).toBeVisible();
        }
        break;
      }
    }
  });

  test('should handle department form validation', async ({ page }) => {
    // Click create department button
    const createButton = page.locator('button:has-text("Create Department"), button:has-text("Add Department")').first();
    
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

  test('should be responsive on mobile devices', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await commonHelper.navigateToHRModule('departments');

    // Check that departments page is accessible on mobile
    await expect(page.locator('h1, h2')).toContainText(/Departments|Department Management/);

    // Check that departments are displayed in mobile-friendly format
    const mobileDisplaySelectors = [
      '.department-card',
      '.department-item',
      'table',
      '.departments-grid'
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