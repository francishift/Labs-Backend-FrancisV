<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if (!$this->filled('end_date')) {
            $this->merge([
                'end_date' => $this->input('start_date')
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_all_day' => 'boolean',
            'reminders' => 'nullable|array',
            'reminders.*.minutes' => 'required|integer|min:0',
            'is_recurring' => 'nullable|boolean',
            'recurrence' => 'nullable|string|in:DAILY,WEEKLY,MONTHLY,YEARLY',
        ];
    }
}
