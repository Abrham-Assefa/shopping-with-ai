<?php

namespace Webkul\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Checkout\Facades\Cart;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Core\Rules\PostCode;
use Webkul\Core\Rules\StateBelongsToCountry;

class CartAddressRequest extends FormRequest
{
    /**
     * Rules.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Apply hidden Ethiopian checkout defaults before validation and saving.
     */
    protected function prepareForValidation(): void
    {
        foreach (['billing', 'shipping'] as $addressType) {
            if (! $this->has($addressType)) {
                continue;
            }

            $address = $this->input($addressType, []);

            $this->merge([
                $addressType => array_merge($address, [
                    'company_name' => 'Individual',
                    'vat_id' => 'number',
                    'postcode' => '1000',
                    'state' => 'Addis Ababa',
                ]),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if ($this->has('billing')) {
            $this->mergeAddressRules('billing');
        }

        if (
            ! $this->input('billing.use_for_shipping')
            && Cart::getCart()?->haveStockableItems()
        ) {
            $this->mergeAddressRules('shipping');
        }

        return $this->rules;
    }

    /**
     * Merge new address rules.
     */
    private function mergeAddressRules(string $addressType): void
    {
        $this->mergeWithRules([
            "{$addressType}.company_name" => ['nullable'],
            "{$addressType}.first_name" => ['required'],
            "{$addressType}.last_name" => ['required'],
            "{$addressType}.email" => ['required'],
            "{$addressType}.address" => ['required', 'array', 'min:1'],
            "{$addressType}.city" => ['required'],
            "{$addressType}.country" => core()->isCountryRequired() ? ['required'] : ['nullable'],
            "{$addressType}.state" => [
                core()->isStateRequired() ? 'required' : 'nullable',
                new StateBelongsToCountry($this->input("{$addressType}.country")),
            ],
            "{$addressType}.postcode" => core()->isPostCodeRequired() ? ['required', new PostCode] : [new PostCode],
            "{$addressType}.phone" => ['required', new PhoneNumber],
        ]);

        if ($addressType == 'billing') {
            $this->mergeWithRules([
                "{$addressType}.vat_id" => ['nullable'],
            ]);
        }
    }

    /**
     * Merge additional rules.
     */
    private function mergeWithRules(array $rules): void
    {
        $this->rules = array_merge($this->rules, $rules);
    }
}
