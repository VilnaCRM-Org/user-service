import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import UITypography from '@/components/ui/UITypography/UITypography';

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
        <UITypography variant="medium16">{t('footer.privacy')}</UITypography>
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
        <UITypography variant="medium16">
          {t('footer.usagePolicy')}
        </UITypography>
      </Stack>
    </>
  );
}

export default PrivacyPolicy;
