import { Controller, FieldValues } from 'react-hook-form';

import UiInput from '../UiInput';
import UiTypography from '../UiTypography';

import styles from './styles';
import { CustomTextField } from './types';

function UiTextFieldForm<T extends FieldValues>({
  control,
  rules,
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
      render={({ field, fieldState: { error } }) => (
        <>
          <UiInput
            type={type}
            placeholder={placeholder}
            onChange={e => field.onChange(e)}
            onBlur={field.onBlur}
            value={field.value}
            error={!!error}
            fullWidth={fullWidth}
          />
          {error && (
            <UiTypography variant="medium14" sx={styles.errorText}>
              {error.message}
            </UiTypography>
          )}
        </>
      )}
    />
  );
}

export default UiTextFieldForm;
