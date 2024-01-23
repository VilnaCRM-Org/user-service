import { Box, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import { UiTypography } from '@/components';
import { colorTheme } from '@/components/UiColorTheme';

import AtSignImage from '../../../../assets/svg/header-drawer/at-sign.svg';

import { vilnaGmailStyles } from './styles';

function VilnaGmail() {
  return (
    <Box sx={vilnaGmailStyles.gmailWrapper}>
      <Stack
        justifyContent="center"
        alignItems="center"
        gap="10px"
        flexDirection="row"
      >
        <Image src={AtSignImage} alt="ExitImage" width={24} height={24} />
        <UiTypography
          variant="demi18"
          color={colorTheme.palette.darkSecondary.main}
        >
          info@vilnacrm.com
        </UiTypography>
      </Stack>
    </Box>
  );
}

export default VilnaGmail;
