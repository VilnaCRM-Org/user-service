import { Stack } from '@mui/material';
import { useTranslation } from 'react-i18next';

import { DefaultTypography } from '@/components/UiTypography';

import styles from './styles';

function PasswordTip(): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Stack direction="column" gap="0.25rem">
      <DefaultTypography variant="medium14" sx={styles.line}>
        {t('sign_up.form.password_tip.title')}
      </DefaultTypography>
      <DefaultTypography variant="medium14" sx={styles.recommendationText}>
        {t('sign_up.form.password_tip.recommendation_text')}
      </DefaultTypography>
      <Stack gap="0.25rem">
        <DefaultTypography variant="medium14" sx={styles.optionText}>
          {t('sign_up.form.password_tip.options.option_1')}
        </DefaultTypography>
        <DefaultTypography variant="medium14" sx={styles.optionText}>
          {t('sign_up.form.password_tip.options.option_2')}
        </DefaultTypography>
        <DefaultTypography variant="medium14" sx={styles.optionText}>
          {t('sign_up.form.password_tip.options.option_3')}
        </DefaultTypography>
      </Stack>
    </Stack>
  );
}

export default PasswordTip;
