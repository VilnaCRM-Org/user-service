import { Stack } from '@mui/material';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/';

import styles from './styles';

function PasswordTip(): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Stack direction="column" gap="0.25rem">
      <UiTypography variant="medium14" sx={styles.line}>
        {t('sign_up.form.password_tip.title')}
      </UiTypography>
      <UiTypography variant="medium14" sx={styles.recommendationText}>
        {t('sign_up.form.password_tip.recommendation_text')}
      </UiTypography>
      <Stack gap="0.25rem">
        <UiTypography variant="medium14" sx={styles.optionText}>
          {t('sign_up.form.password_tip.options.option_1')}
        </UiTypography>
        <UiTypography variant="medium14" sx={styles.optionText}>
          {t('sign_up.form.password_tip.options.option_2')}
        </UiTypography>
        <UiTypography variant="medium14" sx={styles.optionText}>
          {t('sign_up.form.password_tip.options.option_3')}
        </UiTypography>
      </Stack>
    </Stack>
  );
}

export default PasswordTip;
