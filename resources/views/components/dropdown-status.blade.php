@props(['name', 'options', 'selected' => null])

<select name="{{ $name }}" id="{{ $name }}" 
        {{ $attributes->merge(['class' => 'form-select rounded-md shadow-sm']) }}>
    @foreach($options as $option)
        <option value="{{ $option->id }}" 
            {{ $selected == $option->id ? 'selected' : '' }}>
            {{ ucfirst($option->name) }}
        </option>
    @endforeach
</select>
