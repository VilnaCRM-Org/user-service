import { Box, Container, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/ui';

import Logo from '../../../assets/svg/Logo/Logo.svg';
import { NavList } from '../NavList';
import { PrivacyPolicy } from '../PrivacyPolicy';

import { adaptiveStyles } from './styles';

function Adaptive() {
  const { t } = useTranslation();

  return (
    <Container sx={adaptiveStyles.wrapper}>
      <Stack direction="row" justifyContent="space-between" mt="18px" pb="16px">
        <Image src={Logo} alt="Logo" width={131} height={44} />
        <NavList />
      </Stack>
      <Box sx={adaptiveStyles.gmailWrapper}>
        <UiTypography variant="medium15" sx={adaptiveStyles.gmailText}>
          info@vilnacrm.com
        </UiTypography>
      </Box>
      <PrivacyPolicy />
      <UiTypography variant="medium15" sx={adaptiveStyles.copyright}>
        {t('footer.copyright')}
      </UiTypography>
    </Container>
  );
}

export default Adaptive;
