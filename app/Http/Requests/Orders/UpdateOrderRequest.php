<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order && $order->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route('order');
            if ($order && ! $order->isPending()) {
                $validator->errors()->add(
                    'status',
                    'Only pending orders can be updated'
                );
            }
        });
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('You do not have permission to update this order');
    }
}
