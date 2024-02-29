import { Controller, FieldValues } from 'react-hook-form';

import UiInput from '../UiInput';

import { CustomTextField } from './types';

function UiTextFieldForm<T extends FieldValues>({
  control,
  rules,
  errors,
  placeholder,
  type,
  name,
  fullWidth,
}: CustomTextField<T>): React.ReactElement {
  return (
    <Controller
      control={control}
      name={name}
      rules={rules}
      render={({ field }) => (
        <UiInput
          type={type}
          placeholder={placeholder}
          onChange={e => field.onChange(e)}
          onBlur={field.onBlur}
          value={field.value}
          error={errors}
          fullWidth={fullWidth}
        />
      )}
    />
  );
}

export default UiTextFieldForm;
