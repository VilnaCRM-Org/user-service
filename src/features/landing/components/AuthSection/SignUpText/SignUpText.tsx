'use client';

import { Box, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import SocialList from '../SocialList/SocialList';

function SignUpText() {
  const { t } = useTranslation();
  return (
    <Box sx={{ width: '50%' }}>
      <Typography variant="h2" sx={{ fontWeight: 'bold', fontSize: '46px' }}>
        {t('sign_up.main_heading')}
      </Typography>
      <Typography
        variant="h2"
        color="  #1EAEFF"
        sx={{ fontWeight: 'bold', mb: '40px', fontSize: '46px' }}
      >
        VilnaCRM
      </Typography>
      <Box maxWidth="390px">
        <Typography
          sx={{
            fontFamily: 'Golos',
            fontSize: '22px',
            fontStyle: 'normal',
            fontWeight: '700',
            lineHeight: 'normal',
            mb: '24px',
          }}
        >
          {t('sign_up.socials_main_heading')}
        </Typography>
        <SocialList />
      </Box>
    </Box>
  );
}

export default SignUpText;
