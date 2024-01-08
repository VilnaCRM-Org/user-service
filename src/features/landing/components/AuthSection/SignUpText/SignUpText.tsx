'use client';

import { Box } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/ui';

import { SocialList } from '../SocialList';

import { signUpTextStyles } from './styles';

function SignUpText() {
  const { t } = useTranslation();
  return (
    <Box maxWidth="561px" sx={signUpTextStyles.textWrapper}>
      <UiTypography variant="h2" sx={{ whiteSpace: 'pre-line', pb: '40px' }}>
        {t('sign_up.main_heading')}
        <UiTypography variant="h2" component="span" sx={{ color: '#1EAEFF' }}>
          {' VilnaCRM'}
        </UiTypography>
      </UiTypography>
      <Box maxWidth="390px">
        <UiTypography variant="bold22" sx={{ mb: '24px' }}>
          {t('sign_up.socials_main_heading')}
        </UiTypography>
        <SocialList />
      </Box>
    </Box>
  );
}

export default SignUpText;
