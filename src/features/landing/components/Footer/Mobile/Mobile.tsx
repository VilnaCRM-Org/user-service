import { Container, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
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

  return (
    <Container sx={styles.wrapper}>
      <Stack
        direction="row"
        justifyContent="space-between"
        marginTop="1.125rem"
        paddingBottom="1rem"
      >
        <Image src={Logo} alt={t('footer.logo_alt')} width={131} height={44} />
        <SocialMediaList socialLinks={socialLinks} />
      </Stack>
      <VilnaCRMGmail />
      <PrivacyPolicy />
      <UiTypography variant="medium15" sx={styles.copyright}>
        {t('footer.copyright')}
      </UiTypography>
    </Container>
  );
}

export default Mobile;
