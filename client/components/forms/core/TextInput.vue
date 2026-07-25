<template>
  <input-wrapper v-bind="inputWrapperProps">
    <template #label>
      <slot name="label" />
    </template>

    <input
      :id="id ? id : name"
      v-model="compVal"
      :disabled="disabled ? true : null"
      :type="nativeType"
      :autocomplete="resolvedAutocomplete"
      :data-lpignore="nativeType === 'password' ? 'true' : null"
      :data-1p-ignore="nativeType === 'password' ? 'true' : null"
      :data-form-type="nativeType === 'password' ? 'other' : null"
      :pattern="pattern"
      :style="inputStyle"
      :class="ui.input({ class: props.ui?.slots?.input })"
      :name="name"
      :accept="accept"
      :placeholder="placeholder"
      :min="min"
      :max="max"
      :maxlength="maxCharLimit"
      @change="onChange"
      @keydown.enter="onEnterPress"
      @focus="onInputFocus"
      @blur="onBlur"
    >

    <template
      v-if="$slots.help"
      #help
    >
      <slot name="help" />
    </template>

    <template
      v-if="maxCharLimit && showCharLimit"
      #bottom_after_help
    >
      <small :class="ui.help({ class: props.ui?.slots?.help })">
        {{ charCount }}/{{ maxCharLimit }}
      </small>
    </template>

    <template
      v-if="$slots.error"
      #error
    >
      <slot name="error" />
    </template>
  </input-wrapper>
</template>

<script>
import {inputProps, useFormInput} from "../useFormInput.js"
import { textInputTheme } from "~/lib/forms/themes/text-input.theme.js"

export default {
  name: "TextInput",
  components: {},

  props: {
    ...inputProps,
    nativeType: {type: String, default: "text"},
    accept: {type: String, default: null},
    min: {type: Number, required: false, default: null},
    max: {type: Number, required: false, default: null},
    autocomplete: {type: [Boolean, String, Object], default: null},
    maxCharLimit: {type: Number, required: false, default: null},
    pattern: {type: String, default: null},
    preventEnter: {type: Boolean, default: true},
  },

  setup(props, context) {
    const formInput = useFormInput(
      props,
      context,
      {
        formPrefixKey: props.nativeType === "file" ? "file-" : null,
        variants: textInputTheme
      },
    )

    const onChange = (event) => {
      if (props.nativeType !== "file") return

      const file = event.target.files[0]
       
      props.form[props.name] = file
    }

    const onEnterPress = (event) => {
      if (props.preventEnter) {
        event.preventDefault()
      }
      context.emit('input-filled')
      return false
    }

    const onInputFocus = (event) => {
      // Password managers sometimes treat masked tax identifiers as login
      // credentials. If they changed only the DOM value, restore the actual
      // form value before the user starts typing.
      if (props.nativeType === 'password') {
        const formValue = formInput.compVal.value ?? ''
        if (event.target.value !== formValue) {
          event.target.value = formValue
        }
      }
      formInput.onFocus(event)
    }

    return {
      ...formInput,
      onEnterPress,
      onInputFocus,
      onChange,
      props
    }
  },
  computed: {
    resolvedAutocomplete() {
      if (this.nativeType === 'password' && !this.autocomplete) {
        return 'new-password'
      }
      return this.autocomplete
    },
    charCount() {
      return this.compVal ? this.compVal.length : 0
    },
  },
}
</script>
