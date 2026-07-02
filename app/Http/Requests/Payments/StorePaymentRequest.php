<?php

namespace App\Http\Requests\Payments;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:orders,id'],
            'payment_method' => ['required', 'in:credit_card,paypal,stripe'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = Order::find($this->order_id);

            if ($order) {
                if ($order->user_id !== $this->user()->id) {
                    $validator->errors()->add(
                        'order_id',
                        'You do not have permission to pay for this order'
                    );
                }

                if (! $order->isConfirmed()) {
                    $validator->errors()->add(
                        'order_id',
                        'Payments can only be processed for confirmed orders'
                    );
                }

                if (abs($order->total_amount - $this->amount) > 0.01) {
                    $validator->errors()->add(
                        'amount',
                        'Payment amount must match order total'
                    );
                }
            }
        });
    }
}
