import { Box, Container, Stack } from '@mui/material';
import Image from 'next/image';
import React, { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import UiTypography from '@/components/UiTypography';

import Logo from '../../../features/landing/assets/svg/logo/Logo.svg';
import { SocialMediaList } from '../../../features/landing/components/SocialMedia';
import { SocialMedia } from '../../../features/landing/types/social-media/index';
import { PrivacyPolicy } from '../PrivacyPolicy';
import { VilnaCRMEmail } from '../VilnaCRMEmail';

import styles from './styles';

function Mobile({ socialLinks }: { socialLinks: SocialMedia[] }): React.ReactElement {
  const { t } = useTranslation();
  const currentDate: Date = useMemo(() => new Date(), []);
  const currentYear: number = useMemo(() => currentDate.getFullYear(), [currentDate]);
  return (
    <Container sx={styles.wrapper}>
      <Stack sx={styles.content}>
        <Image src={Logo} alt={t('footer.logo_alt')} width={131} height={44} />
        <SocialMediaList socialLinks={socialLinks} />
      </Stack>
      <VilnaCRMEmail />
      <PrivacyPolicy />
      <UiTypography variant="medium15" sx={styles.copyright}>
        {t('footer.copyright')}, <Box component="span">{currentYear}</Box>
      </UiTypography>
    </Container>
  );
}

export default Mobile;
