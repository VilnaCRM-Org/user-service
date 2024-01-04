import { Drawer, Box, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import UIButton from '@/components/ui/UIButton/UIButton';
import UITypography from '@/components/ui/UITypography/UITypography';

import AtSignImage from '../../../assets/svg/header-drawer/at-sign.svg';
import Bars from '../../../assets/svg/header-drawer/menu-04.svg';
import CloseImage from '../../../assets/svg/header-drawer/x-close.svg';
import Logo from '../../../assets/svg/Logo/Logo.svg';
import { DRAWER_SOCIAL_LINKS } from '../../../utils/constants/constants';

import DrawerList from './DrawerList/DrawerList';

function UiDrawer() {
  const [isDrawerOpen, setIsDrawerOpen] = React.useState(false);

  return (
    <Box>
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
          sx={{ px: '15px', py: '6px' }}
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
            <UIButton variant="outlined" size="small" fullWidth>
              Войти
            </UIButton>
            <UIButton variant="contained" size="small" fullWidth>
              Спробувати
            </UIButton>
          </Stack>
          <DrawerList />
          <Box
            sx={{
              border: '1px solid #E1E7EA',
              py: '18px',
              borderRadius: '8px',
              mt: '6px',
            }}
          >
            <Stack
              justifyContent="center"
              alignItems="center"
              gap="10px"
              flexDirection="row"
            >
              <Image src={AtSignImage} alt="ExitImage" width={24} height={24} />
              <UITypography variant="demi18">info@vilnacrm.com</UITypography>
            </Stack>
          </Box>
          <Stack
            pr="12px"
            justifyContent="center"
            gap="8px"
            flexDirection="row"
            mt="16px"
          >
            {DRAWER_SOCIAL_LINKS.map(({ icon, title, id }) => (
              <Box key={id} sx={{ margin: '12px' }}>
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
