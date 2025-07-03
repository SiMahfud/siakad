<?php

namespace App\Validation;

class CustomRules
{
    /**
     * Validates that if both start_date and end_date are set,
     * end_date is not earlier than start_date.
     *
     * @param string|null $endDate
     * @param string $params Format: "start_date_field,end_date_field"
     * @param array $data Full data array
     * @param string|null $error Error message
     * @return bool
     */
    public function valid_date_range_if_set(?string $endDate, string $params, array $data, ?string &$error = null): bool
    {
        // Extract field names from params
        [$startDateField, $endDateField] = explode(',', $params);

        $startDate = $data[$startDateField] ?? null;
        // The $endDate parameter is already the value of the end_date field

        // If either date is not set, the validation passes (handled by 'permit_empty' or 'required' rules)
        if (empty($startDate) || empty($endDate)) {
            return true;
        }

        // Convert dates to timestamps for comparison
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);

        if ($endTimestamp < $startTimestamp) {
            $error = lang('Validation.endDateLessorStartDate'); // Assuming you'll add this to your language file
            // Or a default message:
            // $error = 'The end date cannot be earlier than the start date.';
            return false;
        }

        return true;
    }
}
