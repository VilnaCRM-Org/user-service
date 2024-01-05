import { Box, Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/ui';

function PrivacyPolicy() {
  const { t } = useTranslation();

  return (
    <Stack
      direction="row"
      gap="8px"
      alignItems="center"
      sx={{
        marginRight: '-3px',
        marginBottom: '5px',
        flexDirection: { xs: 'column', md: 'row' },
      }}
    >
      <Box
        sx={{
          width: '244px',
          padding: '8px 16px',
          borderRadius: '8px',
          background: '#F4F5F6',
        }}
      >
        <UiTypography variant="medium16">{t('footer.privacy')}</UiTypography>
      </Box>
      <Box
        sx={{
          width: '255px',
          padding: '8px 16px',
          borderRadius: '8px',
          background: '#F4F5F6',
        }}
      >
        <UiTypography variant="medium16">
          {t('footer.usagePolicy')}
        </UiTypography>
      </Box>
    </Stack>
  );
}

export default PrivacyPolicy;
