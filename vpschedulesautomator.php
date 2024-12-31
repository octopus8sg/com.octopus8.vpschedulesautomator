<?php

require_once 'vpschedulesautomator.civix.php';
require_once 'CRM/Vpschedulesautomator/Utils.php';


use CRM_Vpschedulesautomator_ExtensionUtil as E;
use CRM\Vpschedulesautomator\Utils as U;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function vpschedulesautomator_civicrm_config(&$config): void
{
    _vpschedulesautomator_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function vpschedulesautomator_civicrm_install(): void
{
    _vpschedulesautomator_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function vpschedulesautomator_civicrm_enable(): void
{
    _vpschedulesautomator_civix_civicrm_enable();
}

/**
 * Handle both event roles and training schedules in a refactored way
 */
function handleActivity($activityType, $op, $objectId, $customFieldGroup)
{
    Civi::log()->debug("Handling {$activityType} with operation: {$op}");

    // Fetch details based on operation type
    $details = U::getSchedulesDetails($objectId, $customFieldGroup);
    if (!$details) {
        return;
    }

    // Calculate registration and cancellation dates
    $regCanDates = U::calculateRegCanDates(
        $details['activity_date_time'],
        $details[$customFieldGroup . '.Registration_Start_Days_Before'],
        $details[$customFieldGroup . '.Registration_End_Days_Before'],
        $details[$customFieldGroup . '.Cancellation_Days_Before'] ?? 0
    );
    Civi::log()->debug("Calculated Dates: ", $regCanDates);

    // Check for "create" operation
    if ($op === "create") {
        Civi::log()->info("Creating {$activityType} entry");
        U::populateRegistrationDates($objectId, $regCanDates, $customFieldGroup);
        return;
    }

    // For "edit" operation, check original values before updating
    if ($op === "edit") {
        $original = U::getOriginalSchedule($objectId, $customFieldGroup);
        if (!$original) {
            return;
        }

        // Check if calculated dates differ from the original dates
        if (
            $original[$customFieldGroup . '.Registration_Start_Date'] !== $regCanDates['Registration_Start_Date'] ||
            $original[$customFieldGroup . '.Registration_End_Date'] !== $regCanDates['Registration_End_Date'] ||
            $original[$customFieldGroup . '.Cancellation_Date'] !== $regCanDates['Cancellation_Date']
        ) {
            U::populateRegistrationDates($objectId, $regCanDates, $customFieldGroup);
        } else {
            Civi::log()->debug("No changes detected in {$activityType} registration or cancellation dates.");
        }
    }
}

function vpschedulesautomator_civicrm_postCommit($op, $objectName, $objectId, &$objectRef)
{
    if ($objectName !== "Activity") {
        return;
    }

    $activityType = U::getActivityType($objectRef->activity_type_id);
    Civi::log()->debug("Activity Type: {$activityType}");

    // Handle Volunteer Event Role
    if ($activityType === "Volunteer Event Role") {
        handleActivity($activityType, $op, $objectId, U::EVENT_CUSTOMFIELDGROUP);
        return;
    }

    // Handle Volunteer Training Schedule
    if ($activityType === "Volunteer Training Schedule") {
        handleActivity($activityType, $op, $objectId, U::TRAINING_CUSTOMFIELDGROUP);
        return;
    }
}