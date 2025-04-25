<?php
namespace CRM\Vpschedulesautomator;

use DateTime;
use Exception;
use Civi;

class Utils
{
    public const EVENT_CUSTOMFIELDGROUP = "Volunteer_Event_Schedule";
    public const TRAINING_CUSTOMFIELDGROUP = "Volunteer_Training_Schedule";

    public static function getActivityType($activityTypeId)
    {
        Civi::log()->debug("TypeId: {$activityTypeId}");
        $activityTypeResult = civicrm_api4('OptionValue', 'get', [
            'select' => ['label'],
            'where' => [['value', '=', $activityTypeId]],
            'checkPermissions' => FALSE,
        ]);

        $activityType = $activityTypeResult[0]['label'];

        return $activityType;
    }

    public static function getSchedulesDetails($activityId, $customFieldGroup)
    {
        try {
            $details = civicrm_api4('Activity', 'get', [
                'select' => [
                    'activity_date_time',
                    $customFieldGroup . '.Registration_Start_Days_Before',
                    $customFieldGroup . '.Registration_End_Days_Before',
                    $customFieldGroup . '.Cancellation_Days_Before',
                ],
                'where' => [
                    ['id', '=', $activityId],
                ],
                'checkPermissions' => FALSE,
            ]);

            if (empty($details)) {
                throw new Exception("No schedule details found for activity ID {$activityId}");
            }

            return $details[0];
        } catch (Exception $e) {
            Civi::log()->debug("Error fetching {$customFieldGroup} schedule details for activity ID {$activityId}: " . $e->getMessage());
            throw $e;
        }
    }

    public static function getOriginalSchedule($activityId, $customFieldGroup)
    {
        try {
            $result = civicrm_api4('Activity', 'get', [
                'select' => [
                    $customFieldGroup . '.Registration_Start_Date',
                    $customFieldGroup . '.Registration_End_Date',
                    $customFieldGroup . '.Cancellation_Date',
                ],
                'where' => [
                    ['id', '=', $activityId]
                ],
                'checkPermissions' => FALSE,
            ]);

            if (empty($result)) {
                throw new Exception("No original schedule details found for activity ID {$activityId}");
            }

            return $result[0];
        } catch (Exception $e) {
            Civi::log()->debug("Error fetching {$customFieldGroup} original schedule details for activity ID {$activityId}: " . $e->getMessage());
            throw $e;
        }
    }

    public static function calculateRegCanDates($activityDateTime, $startDaysBefore, $endDaysBefore, $cancelDaysBefore) // Calculates registration & cancellation dates
    {
        $eventDateTime = new DateTime($activityDateTime);

        $registration_start_date = null;
        $registration_end_date = null;
        $cancellation_date = null;

        if ($startDaysBefore != 0) {
            $registration_start_date = (clone $eventDateTime)->modify("-$startDaysBefore days")->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        }

        if ($endDaysBefore != 0) {
            $registration_end_date = (clone $eventDateTime)->modify("-$endDaysBefore days")->setTime(23, 59, 0)->format('Y-m-d H:i:s');
        }

        if ($cancelDaysBefore != 0) {
            $cancellation_date = (clone $eventDateTime)->modify("-$cancelDaysBefore days")->setTime(23, 59, 0)->format('Y-m-d H:i:s');
        }

        return [
            'Registration_Start_Date' => $registration_start_date,
            'Registration_End_Date' => $registration_end_date,
            'Cancellation_Date' => $cancellation_date,
        ];
    }
    public static function populateRegistrationDates($activityId, $regCanDates, $customFieldGroup)
    {
        try {
            $values = [
                $customFieldGroup . '.Registration_Start_Date' => $regCanDates['Registration_Start_Date'] ?? null,
                $customFieldGroup . '.Registration_End_Date' => $regCanDates['Registration_End_Date'] ?? null,
                $customFieldGroup . '.Cancellation_Date' => $regCanDates['Cancellation_Date'] ?? null,
            ];

            Civi::log()->debug("Populating values: " . json_encode($values, JSON_PRETTY_PRINT));

            $result = civicrm_api4('Activity', 'update', [
                'values' => $values,
                'where' => [['id', '=', $activityId]],
                'checkPermissions' => FALSE,
            ]);

            Civi::log()->info("{$customFieldGroup} dates populated successfully for activity ID {$activityId}");
        } catch (Exception $e) {
            Civi::log()->debug("Error populating {$customFieldGroup} dates for activity ID {$activityId}: " . $e->getMessage());
            throw $e;
        }
    }
}
