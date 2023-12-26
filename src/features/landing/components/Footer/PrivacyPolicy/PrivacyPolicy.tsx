'use client';

import { Box, Stack, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

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
        <Typography>{t('footer.privacy')}</Typography>
      </Box>
      <Box
        sx={{
          padding: '8px 16px',
          borderRadius: '8px',
          background: '#F4F5F6',
        }}
      >
        <Typography>{t('footer.usagePolicy')}</Typography>
      </Box>
    </Stack>
  );
}

export default PrivacyPolicy;
