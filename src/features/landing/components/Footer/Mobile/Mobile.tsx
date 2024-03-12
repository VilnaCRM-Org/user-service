import { Box, Container, Link, Stack } from '@mui/material';
import Image from 'next/image';
import React, { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/';

import Logo from '../../../assets/svg/logo/Logo.svg';
import { SocialMedia } from '../../../types/social-media/index';
import SocialMediaList from '../../SocialMedia/SocialMediaList/SocialMediaList';
import { PrivacyPolicy } from '../PrivacyPolicy';
import { VilnaCRMGmail } from '../VilnaCRMGmail';

import styles from './styles';

function Mobile({
  socialLinks,
}: {
  socialLinks: SocialMedia[];
}): React.ReactElement {
  const { t } = useTranslation();
  const currentDate: Date = useMemo(() => new Date(), []);
  const currentYear: number = useMemo(
    () => currentDate.getFullYear(),
    [currentDate]
  );
  return (
    <Container sx={styles.wrapper}>
      <Stack
        direction="row"
        justifyContent="space-between"
        marginTop="1.125rem"
        paddingBottom="1rem"
      >
        <Link href="/#">
          <Image
            src={Logo}
            alt={t('footer.logo_alt')}
            width={131}
            height={44}
          />
        </Link>
        <SocialMediaList socialLinks={socialLinks} />
      </Stack>
      <VilnaCRMGmail />
      <PrivacyPolicy />
      <UiTypography variant="medium15" sx={styles.copyright}>
        {t('footer.copyright')} <Box component="span">{currentYear}</Box>
      </UiTypography>
    </Container>
  );
}

export default Mobile;
