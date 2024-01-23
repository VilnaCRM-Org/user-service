import { Box, Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';
import { colorTheme } from '@/components/UiColorTheme';

import { privacyPolicyStyles } from './styles';

function PrivacyPolicy() {
  const { t } = useTranslation();

  return (
    <Stack direction="row" alignItems="center" sx={privacyPolicyStyles.wrapper}>
      <Box sx={privacyPolicyStyles.privacy}>
        <UiTypography
          variant="medium16"
          color={colorTheme.palette.grey300.main}
        >
          {t('footer.privacy')}
        </UiTypography>
      </Box>
      <Box sx={privacyPolicyStyles.usagePolicy}>
        <UiTypography
          variant="medium16"
          color={colorTheme.palette.grey300.main}
        >
          {t('footer.usagePolicy')}
        </UiTypography>
      </Box>
    </Stack>
  );
}

export default PrivacyPolicy;
