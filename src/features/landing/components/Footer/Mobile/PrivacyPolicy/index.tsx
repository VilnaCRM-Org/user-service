import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/ui';

function PrivacyPolicy() {
  const { t } = useTranslation();

  return (
    <>
      <Stack
        alignItems="center"
        sx={{
          width: '100%',
          padding: '17px 16px',
          borderRadius: '8px',
          background: '#F4F5F6',
        }}
      >
        <UiTypography variant="medium16">{t('footer.privacy')}</UiTypography>
      </Stack>
      <Stack
        alignItems="center"
        sx={{
          width: '100%',
          padding: '17px 16px',
          borderRadius: '8px',
          background: '#F4F5F6',
        }}
      >
        <UiTypography variant="medium16">
          {t('footer.usagePolicy')}
        </UiTypography>
      </Stack>
    </>
  );
}

export default PrivacyPolicy;
