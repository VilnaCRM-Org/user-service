import { Box } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { DefaultTypography } from '@/components/UiTypography';

import { SocialLink } from '../../../types/authentication/social';
import { SocialList } from '../SocialList';

import styles from './styles';

function SignUpText({
  socialLinks,
}: {
  socialLinks: SocialLink[];
}): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Box sx={styles.textWrapper}>
      <DefaultTypography variant="h2" sx={styles.title} id="signUp">
        <Trans i18nKey="sign_up.main_heading" />
        <DefaultTypography
          variant="h2"
          component="span"
          sx={styles.titleVilnaCRM}
        >
          &nbsp;
          {t('sign_up.vilna_text')}
        </DefaultTypography>
      </DefaultTypography>
      <DefaultTypography variant="bold22" sx={styles.signInText}>
        {t('sign_up.socials_main_heading')}
      </DefaultTypography>
      <SocialList socialLinks={socialLinks} />
    </Box>
  );
}

export default SignUpText;
