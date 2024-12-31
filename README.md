# Volunteer Portal Event Role Automator Extension (vpschedulesautomator)

This extension improves the experience when creating/updating Volunteer Event Role / Volunteer Training Schedule activities by automatically calculating and creating registration start and end and cancellation date custom fields.

## Getting Started

1. Create a new activity of Volunteer Event Role / Volunteer Training Schedule activity type

2. Fill in the activity date time field, Registration Start Days Before, Registration End Days Before, and Cancellation Days Before custom fields.
   **Note:** Based on the activity date, count how many days before that date you would want the registration and cancellation to start and end. If no registration date required, input 0 for the respective days field.

3. This extension uses a hook to find the activity created. It then retrieves the Activity Date Time, Registration Start Days Before, Registration End Days Before, and Cancellation Days Before fields.

4. Next, it performs a calculation for the registration start & end dates:

- **Registration Start Date** = Activity Date Time - Registration Start Days Before
- **Registration End Date** = Activity Date Time - Registration End Days Before
- **Cancellation Date** = Activity Date Time - Cancellation Days Before

5. After calculation, the extension will make an update activity API4 request for the newly created activity to populate the date custom fields.

This is an [extension for CiviCRM](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/), licensed under [AGPL-3.0](LICENSE.txt).
