@php
    $extraAttributes = $getExtraAttributes();
    $id = $getId();
    $isLabelHidden = $isLabelHidden();
    $label = $getLabel();
    $isContained = $isContained();
@endphp

<x-webkernel-platform::fieldset
    :contained="$isContained"
    :label="$label"
    :label-hidden="$isLabelHidden"
    :required="isset($isMarkedAsRequired) ? $isMarkedAsRequired() : false"
    :attributes="
        \Webkernel\Platform\Support\prepare_inherited_attributes($attributes)
            ->merge([
                'id' => $id,
            ], escape: false)
            ->merge($extraAttributes, escape: false)
            ->class(['fi-sc-fieldset'])
    "
>
    {{ $getChildSchema() }}
</x-webkernel-platform::fieldset>
