<x-app>
    <form
    method="POST"
    action="{{ route('supplier.address.store', $supplier) }}"
>
    @csrf

    <select name="type">
        <option value="billing">Billing</option>
        <option value="shipping">Shipping</option>
        <option value="other">Other</option>
    </select>

    <input
        type="text"
        name="line_1"
        placeholder="Address line 1"
        value="{{ old('line_1') }}"
        required
    >

    <input
        type="text"
        name="line_2"
        placeholder="Address line 2"
        value="{{ old('line_2') }}"
    >

    <input
        type="text"
        name="city"
        placeholder="City"
        value="{{ old('city') }}"
    >

    <input
        type="text"
        name="postal_code"
        placeholder="Postal code"
        value="{{ old('postal_code') }}"
    >

    <input
        type="text"
        name="country"
        placeholder="Country"
        value="{{ old('country') }}"
    >

    <label>
        <input
            type="checkbox"
            name="is_default"
            value="1"
        >

        Default address
    </label>

    <button type="submit">
        Save Address
    </button>
</form>
</x-app>