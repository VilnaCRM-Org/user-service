import { Box, Container, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import Logo from '../../../assets/svg/logo/Logo.svg';
import { ISocialMedia } from '../../../types/social-media/index';
import { PrivacyPolicy } from '../PrivacyPolicy';
import { SocialMediaList } from '../SocialMediaList';

import styles from './styles';

function Adaptive({ socialLinks }: { socialLinks: ISocialMedia[] }) {
  const { t } = useTranslation();

  return (
    <Container sx={styles.wrapper}>
      <Stack
        direction="row"
        justifyContent="space-between"
        mt="1.125rem"
        pb="1rem"
      >
        <Image src={Logo} alt="Logo" width={131} height={44} />
        <SocialMediaList socialLinks={socialLinks} />
      </Stack>
      <Box sx={styles.gmailWrapper}>
        <UiTypography variant="medium15" sx={styles.gmailText}>
          info@vilnacrm.com
        </UiTypography>
      </Box>
      <PrivacyPolicy />
      <UiTypography variant="medium15" sx={styles.copyright}>
        {t('footer.copyright')}
      </UiTypography>
    </Container>
  );
}

export default Adaptive;
