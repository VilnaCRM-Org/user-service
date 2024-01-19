import { Drawer, Box, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiButton, UiTypography } from '@/components/ui';

import AtSignImage from '../../../assets/svg/header-drawer/at-sign.svg';
import Bars from '../../../assets/svg/header-drawer/menu-04.svg';
import CloseImage from '../../../assets/svg/header-drawer/x-close.svg';
import Logo from '../../../assets/svg/Logo/Logo.svg';
import { DRAWER_SOCIAL_LINKS } from '../../../utils/constants/constants';

import DrawerList from './DrawerList/DrawerList';
import { drawerStyles } from './styles';

function UiDrawer() {
  const { t } = useTranslation();
  const [isDrawerOpen, setIsDrawerOpen] = React.useState(false);

  return (
    <Box sx={drawerStyles.wrapper}>
      <Image
        src={Bars}
        alt="Header Image"
        width={24}
        height={24}
        onClick={() => setIsDrawerOpen(!isDrawerOpen)}
      />
      <Drawer
        anchor="right"
        open={isDrawerOpen}
        onClose={() => setIsDrawerOpen(!isDrawerOpen)}
      >
        <Box
          width="23.4375rem"
          textAlign="center"
          role="presentation"
          sx={drawerStyles.drawerContent}
        >
          <Stack
            direction="row"
            justifyContent="space-between"
            alignItems="center"
          >
            <Image src={Logo} alt="Header Image" width={131} height={44} />
            <Image
              src={CloseImage}
              alt="ExitImage"
              width={24}
              height={24}
              onClick={() => setIsDrawerOpen(!isDrawerOpen)}
            />
          </Stack>
          <Stack
            direction="row"
            alignItems="center"
            justifyContent="center"
            gap="9px"
            mt="12px"
          >
            <UiButton variant="outlined" size="small" fullWidth>
              {t('header.actions.log_in')}
            </UiButton>
            <UiButton variant="contained" size="small" fullWidth>
              {t('header.actions.try_it_out')}
            </UiButton>
          </Stack>
          <DrawerList />
          <Box sx={drawerStyles.gmailWrapper}>
            <Stack
              justifyContent="center"
              alignItems="center"
              gap="10px"
              flexDirection="row"
            >
              <Image src={AtSignImage} alt="ExitImage" width={24} height={24} />
              <UiTypography variant="demi18">info@vilnacrm.com</UiTypography>
            </Stack>
          </Box>
          <Stack
            justifyContent="center"
            gap="8px"
            flexDirection="row"
            sx={drawerStyles.linkWrapper}
          >
            {DRAWER_SOCIAL_LINKS.map(({ icon, title, id }) => (
              <Box key={id} m="12px">
                <Image src={icon} alt={title} width={25} height={25} key={id} />
              </Box>
            ))}
          </Stack>
        </Box>
      </Drawer>
    </Box>
  );
}

export default UiDrawer;
