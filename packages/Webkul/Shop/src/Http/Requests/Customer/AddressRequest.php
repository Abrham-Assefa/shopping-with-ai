<?php

namespace Webkul\Shop\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Core\Rules\PostCode;
use Webkul\Core\Rules\StateBelongsToCountry;

class AddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Apply hidden defaults used for Ethiopian individual checkout addresses.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_name' => 'Individual',
            'vat_id' => 'number',
            'postcode' => '1000',
            'state' => 'Addis Ababa',
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_name' => ['nullable'],
            'first_name' => ['required'],
            'last_name' => ['required'],
            'address' => ['required', 'array', 'min:1'],
            'country' => core()->isCountryRequired() ? ['required'] : ['nullable'],
            'state' => [
                core()->isStateRequired() ? 'required' : 'nullable',
                new StateBelongsToCountry($this->input('country')),
            ],
            'city' => ['required', 'string'],
            'postcode' => core()->isPostCodeRequired() ? ['required', new PostCode] : [new PostCode],
            'phone' => ['required', new PhoneNumber],
            'vat_id' => ['nullable'],
            'email' => ['required'],
        ];
    }

    /**
     * Attributes.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'address.*' => 'address',
        ];
    }
}
