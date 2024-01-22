import { Box } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import { ISocialLink } from '../../../types/authentication/social';
import { SocialList } from '../SocialList';

import { signUpTextStyles } from './styles';

function SignUpText({ socialLinks }: { socialLinks: ISocialLink[] }) {
  const { t } = useTranslation();
  return (
    <Box sx={signUpTextStyles.textWrapper}>
      <UiTypography variant="h2" sx={signUpTextStyles.title}>
        <Trans i18nKey="sign_up.main_heading" />
        <UiTypography
          variant="h2"
          component="span"
          sx={signUpTextStyles.titleVilnaCRM}
        >
          {' VilnaCRM'}
        </UiTypography>
      </UiTypography>
      <UiTypography variant="bold22" sx={signUpTextStyles.signInText}>
        {t('sign_up.socials_main_heading')}
      </UiTypography>
      <SocialList socialLinks={socialLinks} />
    </Box>
  );
}

export default SignUpText;
