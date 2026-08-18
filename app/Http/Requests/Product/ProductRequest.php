<?php
declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Enums\ProductType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProductRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required','integer','exists:categories,id',],
            'unit_id' => ['required','integer','exists:units,id',],
            'brand_id' => ['nullable','integer','exists:brands,id',],
            'sku' => ['nullable','string','max:100',],
            'name' => ['required','string','max:255',],
            'description' => ['nullable','string',],
            'product_type' => ['required',Rule::enum(ProductType::class),],
            'track_stock' => ['boolean',],
            'track_serial' => ['boolean',],
            'track_batch' => ['boolean',],
        ];
    }
}
