'use client';

import { Box } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import UITypography from '@/components/ui/UITypography/UITypography';

import SocialList from '../SocialList/SocialList';

function SignUpText() {
  const { t } = useTranslation();
  return (
    <Box maxWidth="561px" sx={{ mt: '136px' }}>
      <UITypography variant="h2" sx={{ whiteSpace: 'pre-line', pb: '40px' }}>
        {t('sign_up.main_heading')}
        <UITypography variant="h2" component="span" sx={{ color: '#1EAEFF' }}>
          {' VilnaCRM'}
        </UITypography>
      </UITypography>
      <Box maxWidth="390px">
        <UITypography variant="bold22" sx={{ mb: '24px' }}>
          {t('sign_up.socials_main_heading')}
        </UITypography>
        <SocialList />
      </Box>
    </Box>
  );
}

export default SignUpText;
