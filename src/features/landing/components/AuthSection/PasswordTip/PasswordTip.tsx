import { Stack } from '@mui/material';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';
import { colorTheme } from '@/components/UiColorTheme';

function PasswordTip(): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Stack direction="column" gap="0.25rem">
      <UiTypography
        variant="medium14"
        sx={{
          pb: '2px',
          borderBottom: `2px solid ${colorTheme.palette.grey400.main}`,
        }}
      >
        {t('sign_up.form.password_tip.title')}
      </UiTypography>
      <UiTypography variant="medium14" maxWidth="10rem">
        {t('sign_up.form.password_tip.recommendationText')}
      </UiTypography>
      <ul
        style={{
          display: 'flex',
          flexDirection: 'column',
          gap: '0.25rem',
          marginLeft: '1rem',
        }}
      >
        <li
          style={{
            fontSize: '0.8rem',
            color: colorTheme.palette.darkPrimary.main,
            fontWeight: 400,
          }}
        >
          {t('sign_up.form.password_tip.options.option_1')}
        </li>
        <li
          style={{
            fontSize: '0.8rem',
            fontWeight: 400,
            color: colorTheme.palette.darkPrimary.main,
          }}
        >
          {t('sign_up.form.password_tip.options.option_2')}
        </li>
        <li
          style={{
            fontSize: '0.8rem',
            fontWeight: 400,
            color: colorTheme.palette.darkPrimary.main,
          }}
        >
          {t('sign_up.form.password_tip.options.option_3')}
        </li>
      </ul>
    </Stack>
  );
}

export default PasswordTip;
