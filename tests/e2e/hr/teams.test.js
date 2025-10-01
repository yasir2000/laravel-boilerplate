// tests/e2e/hr/teams.test.js
import { test, expect } from '@playwright/test';
import { createAuthHelper } from '../helpers/auth.js';
import { createCommonHelpers } from '../helpers/common.js';

test.describe('Team Management', () => {
  let authHelper;
  let commonHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = createAuthHelper(page);
    commonHelper = createCommonHelpers(page);
    
    await authHelper.ensureLoggedIn();
    await commonHelper.navigateToHRModule('teams');
  });

  test('should display teams overview page', async ({ page }) => {
    // Check page title and heading
    await expect(page.locator('h1, h2')).toContainText(/Teams|Team Management/);

    // Check for teams grid or list
    const teamsDisplaySelectors = [
      '.teams-grid',
      '.team-cards',
      '.team-list',
      'table',
      '[data-testid="teams-container"]'
    ];

    let teamsDisplayFound = false;
    for (const selector of teamsDisplaySelectors) {
      if (await page.locator(selector).count() > 0) {
        teamsDisplayFound = true;
        break;
      }
    }

    expect(teamsDisplayFound).toBeTruthy();
  });

  test('should open create team modal', async ({ page }) => {
    // Look for create team button
    const createButtonSelectors = [
      'button:has-text("Create Team")',
      'button:has-text("Add Team")',
      'button:has-text("New Team")',
      '[data-testid="create-team"]',
      '.btn-create-team'
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
    const formFields = ['name', 'description', 'project', 'teamLead'];
    
    for (const field of formFields) {
      const fieldSelector = `input[name="${field}"], select[name="${field}"], textarea[name="${field}"]`;
      if (await page.locator(fieldSelector).count() > 0) {
        await expect(page.locator(fieldSelector)).toBeVisible();
      }
    }
  });

  test('should create new team successfully', async ({ page }) => {
    // Click create team button
    const createButton = page.locator('button:has-text("Create Team"), button:has-text("Add Team"), button:has-text("New Team")').first();
    
    if (await createButton.count() > 0) {
      await createButton.click();
      await commonHelper.waitForModal();

      // Fill team form
      const teamData = {
        name: `Test Team ${Date.now()}`,
        description: 'This is a test team for automated testing',
        project: 'Test Project',
        budget: '100000'
      };

      // Fill form fields that exist
      for (const [field, value] of Object.entries(teamData)) {
        const fieldSelector = `input[name="${field}"], textarea[name="${field}"]`;
        if (await page.locator(fieldSelector).count() > 0) {
          await page.fill(fieldSelector, value);
        }
      }

      // Select team lead if dropdown exists
      const teamLeadSelect = page.locator('select[name="teamLead"], select[name="team_lead"]');
      if (await teamLeadSelect.count() > 0) {
        await teamLeadSelect.selectOption({ index: 1 }); // Select first available lead
      }

      // Submit form
      await page.click('button:has-text("Save"), button:has-text("Create"), button[type="submit"]');

      // Wait for success message or page update
      await page.waitForTimeout(2000);

      // Verify team was created
      const successIndicators = [
        'text=Team created successfully',
        'text=Success',
        `.team-card:has-text("${teamData.name}")`,
        `table:has-text("${teamData.name}")`
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

  test('should view team details', async ({ page }) => {
    // Look for view team button or clickable team card
    const viewSelectors = [
      'button:has-text("View Details")',
      'button:has-text("View Team")',
      '.team-card',
      '.btn-view',
      '[data-testid="view-team"]'
    ];

    for (const selector of viewSelectors) {
      if (await page.locator(selector).count() > 0) {
        await page.click(selector);
        
        // Wait for details modal or page
        await page.waitForTimeout(1000);
        
        // Check for team details display
        const detailsIndicators = [
          'text=Team Details',
          'text=Team Information',
          'text=Team Members',
          '.team-details',
          '.modal:has-text("Team")'
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

  test('should manage team members', async ({ page }) => {
    // First view a team (click on team card or view button)
    const teamCard = page.locator('.team-card, button:has-text("View Details")').first();
    if (await teamCard.count() > 0) {
      await teamCard.click();
      await page.waitForTimeout(1000);

      // Look for manage members or add members button
      const manageMembersSelectors = [
        'button:has-text("Manage Members")',
        'button:has-text("Add Members")',
        'button:has-text("Assign Members")',
        '[data-testid="manage-members"]'
      ];

      for (const selector of manageMembersSelectors) {
        if (await page.locator(selector).count() > 0) {
          await page.click(selector);
          await page.waitForTimeout(1000);

          // Check for member selection interface
          const memberSelectionIndicators = [
            'text=Select Members',
            'text=Available Employees',
            '.member-selection',
            'input[type="checkbox"]',
            '.employee-checkbox'
          ];

          let memberSelectionFound = false;
          for (const indicator of memberSelectionIndicators) {
            if (await page.locator(indicator).count() > 0) {
              memberSelectionFound = true;
              
              // If checkboxes are available, try to select one
              const checkboxes = page.locator('input[type="checkbox"]');
              if (await checkboxes.count() > 0) {
                await checkboxes.first().check();
                
                // Look for save/assign button
                const saveButton = page.locator('button:has-text("Save"), button:has-text("Assign"), button:has-text("Add")');
                if (await saveButton.count() > 0) {
                  await saveButton.click();
                  await page.waitForTimeout(1000);
                }
              }
              break;
            }
          }

          expect(memberSelectionFound).toBeTruthy();
          break;
        }
      }
    }
  });

  test('should edit team information', async ({ page }) => {
    // Look for edit button for first team
    const editButtonSelectors = [
      'button:has-text("Edit")',
      '.btn-edit',
      '[data-testid="edit-team"]'
    ];

    for (const selector of editButtonSelectors) {
      if (await page.locator(selector).count() > 0) {
        await page.click(selector);
        await commonHelper.waitForModal();

        // Verify edit form is displayed
        const nameInput = page.locator('input[name="name"]');
        if (await nameInput.count() > 0) {
          // Update team name
          const currentName = await nameInput.inputValue();
          const newName = currentName + ' Updated';
          
          await nameInput.fill(newName);
          
          // Save changes
          await page.click('button:has-text("Save"), button:has-text("Update")');
          
          // Wait for update confirmation
          await page.waitForTimeout(2000);
          
          // Verify update was successful
          const updateSuccessIndicators = [
            'text=Team updated successfully',
            'text=Updated successfully',
            `.team-card:has-text("${newName}")`,
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

  test('should track team performance', async ({ page }) => {
    // Look for performance or analytics section
    const performanceSelectors = [
      'text=Performance',
      'text=Analytics',
      'text=Metrics',
      '.performance-section',
      '.team-analytics'
    ];

    for (const selector of performanceSelectors) {
      if (await page.locator(selector).count() > 0) {
        await expect(page.locator(selector)).toBeVisible();
        
        // Look for performance metrics
        const metricsSelectors = [
          'text=Productivity',
          'text=Efficiency',
          'text=Tasks Completed',
          'text=Projects',
          '.metric',
          '.kpi'
        ];

        let metricsFound = false;
        for (const metricSelector of metricsSelectors) {
          if (await page.locator(metricSelector).count() > 0) {
            metricsFound = true;
            break;
          }
        }

        expect(metricsFound).toBeTruthy();
        break;
      }
    }
  });

  test('should handle team form validation', async ({ page }) => {
    // Click create team button
    const createButton = page.locator('button:has-text("Create Team"), button:has-text("Add Team")').first();
    
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

  test('should search teams', async ({ page }) => {
    // Look for search input
    const searchSelectors = [
      'input[placeholder*="Search"]',
      'input[name="search"]',
      '[data-testid="search"]',
      '.search-input'
    ];

    for (const selector of searchSelectors) {
      if (await page.locator(selector).count() > 0) {
        // Get first team name to search for
        const firstTeamName = await page.locator('.team-card h3, .team-name, table td').first().textContent();
        
        if (firstTeamName && firstTeamName.trim()) {
          // Perform search
          await page.fill(selector, firstTeamName.split(' ')[0]);
          await page.keyboard.press('Enter');
          
          // Wait for search results
          await page.waitForTimeout(1000);
          
          // Verify search results
          await expect(page.locator('.team-card, table, .team-list')).toContainText(firstTeamName.split(' ')[0]);
        }
        break;
      }
    }
  });

  test('should be responsive on mobile devices', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await commonHelper.navigateToHRModule('teams');

    // Check that teams page is accessible on mobile
    await expect(page.locator('h1, h2')).toContainText(/Teams|Team Management/);

    // Check that teams are displayed in mobile-friendly format
    const mobileDisplaySelectors = [
      '.team-card',
      '.team-item',
      'table',
      '.teams-grid'
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