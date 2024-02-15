import { Controller, FieldValues } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import { DefaultInput } from '../UiInput';
import { DefaultTypography } from '../UiTypography';

import styles from './styles';
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
  const { t } = useTranslation();
  return (
    <>
      <Controller
        control={control}
        name={name}
        rules={rules}
        render={({ field }) => (
          <DefaultInput
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
      {errors && (
        <DefaultTypography variant="medium14" sx={styles.errorText}>
          {t('sign_up.form.error_text')}
        </DefaultTypography>
      )}
    </>
  );
}

export default UiTextFieldForm;
