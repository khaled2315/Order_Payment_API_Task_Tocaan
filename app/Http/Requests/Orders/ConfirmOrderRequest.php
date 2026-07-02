<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Access\AuthorizationException;

class ConfirmOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order && $order->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route('order');
            if ($order && ! $order->isPending()) {
                $validator->errors()->add(
                    'status',
                    'Only pending orders can be confirmed'
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
        throw new AuthorizationException('You do not have permission to confirm this order');
    }
}
