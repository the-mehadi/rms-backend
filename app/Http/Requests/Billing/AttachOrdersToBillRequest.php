<?php

namespace App\Http\Requests\Billing;

use App\Models\Bill;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class AttachOrdersToBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'order_ids.required' => 'At least one order ID is required.',
            'order_ids.array' => 'Order IDs must be an array.',
            'order_ids.min' => 'At least one order must be provided.',
            'order_ids.*.integer' => 'Each order ID must be an integer.',
            'order_ids.*.exists' => 'One or more order IDs do not exist.',
        ];
    }

    /**
     * Additional validation after basic rules pass.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Get the bill from the route parameter
            $billId = $this->route('id');
            $bill = Bill::find($billId);

            if (!$bill) {
                $validator->errors()->add('bill_id', 'Bill not found.');
                return;
            }

            if ($bill->status !== 'unpaid') {
                $validator->errors()->add('status', 'Can only attach orders to unpaid bills.');
                return;
            }

            // Validate that all orders belong to the same table as the bill
            $orderIds = $this->input('order_ids');
            $ordersWithDifferentTable = Order::whereIn('id', $orderIds)
                ->where('table_id', '!=', $bill->table_id)
                ->exists();

            if ($ordersWithDifferentTable) {
                $validator->errors()->add('order_ids', 'All orders must belong to the same table as the bill.');
            }

            // Validate that orders are not already in another bill
            $ordersInOtherBill = Order::whereIn('id', $orderIds)
                ->whereHas('bills', function ($query) use ($bill) {
                    $query->where('bill_id', '!=', $bill->id)
                        ->where('status', 'unpaid');
                })
                ->exists();

            if ($ordersInOtherBill) {
                $validator->errors()->add('order_ids', 'Some orders are already attached to another unpaid bill.');
            }
        });
    }
}
