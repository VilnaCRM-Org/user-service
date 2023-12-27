'use client';

import { Box, Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import UITypography from '@/components/ui/UITypography/UITypography';

function PrivacyPolicy() {
  const { t } = useTranslation();

  return (
    <Stack direction="row" gap="8px" alignItems="center">
      <Box
        sx={{
          padding: '8px 16px',
          borderRadius: '8px',
          background: '#F4F5F6',
        }}
      >
        <UITypography variant="medium16">{t('footer.privacy')}</UITypography>
      </Box>
      <Box
        sx={{
          padding: '8px 16px',
          borderRadius: '8px',
          background: '#F4F5F6',
        }}
      >
        <UITypography variant="medium16">
          {t('footer.usagePolicy')}
        </UITypography>
      </Box>
    </Stack>
  );
}

export default PrivacyPolicy;
