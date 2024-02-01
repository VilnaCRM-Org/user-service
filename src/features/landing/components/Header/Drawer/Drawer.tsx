import { Drawer, Box, Stack, Button } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiButton } from '@/components';

import Bars from '../../../assets/svg/header-drawer/menu-04.svg';
import CloseImage from '../../../assets/svg/header-drawer/x-close.svg';
import Logo from '../../../assets/svg/logo/Logo.svg';
import { navList, socialMedia } from '../dataArray';

import { NavList } from './NavList';
import { SocialMedia } from './SocialMedia';
import styles from './styles';
import { VilnaGmail } from './VilnaGmail';

function CustomDrawer(): React.ReactElement {
  const { t } = useTranslation();
  const [isDrawerOpen, setIsDrawerOpen] = React.useState(false);

  function handleClick(): void {
    setIsDrawerOpen(false);
  }

  return (
    <Box sx={styles.wrapper}>
      <Button
        aria-label={t('header.drawer.button_aria_labels.bars') as string}
        sx={styles.button}
        onClick={() => setIsDrawerOpen(!isDrawerOpen)}
      >
        <Image src={Bars} alt="Bars Image" width={24} height={24} />
      </Button>
      <Drawer
        anchor="right"
        open={isDrawerOpen}
        onClose={() => setIsDrawerOpen(!isDrawerOpen)}
      >
        <Box
          width="23.4375rem"
          textAlign="center"
          role="presentation"
          sx={styles.drawerContent}
        >
          <Stack
            direction="row"
            justifyContent="space-between"
            alignItems="center"
          >
            <Image src={Logo} alt="Header Image" width={131} height={44} />
            <Button
              aria-label={t('header.drawer.button_aria_labels.exit') as string}
              sx={styles.button}
              onClick={() => setIsDrawerOpen(!isDrawerOpen)}
            >
              <Image src={CloseImage} alt="Exit Image" width={24} height={24} />
            </Button>
          </Stack>
          <Stack
            direction="row"
            alignItems="center"
            justifyContent="center"
            gap="0.563rem"
            mt="0.75rem"
          >
            <UiButton
              variant="outlined"
              size="small"
              fullWidth
              href="#signUp"
              onClick={() => handleClick()}
            >
              {t('header.actions.log_in')}
            </UiButton>
            <UiButton
              variant="contained"
              size="small"
              fullWidth
              href="#signUp"
              onClick={() => handleClick()}
            >
              {t('header.actions.try_it_out')}
            </UiButton>
          </Stack>
          <NavList navList={navList} handleClick={() => handleClick()} />
          <VilnaGmail />
          <SocialMedia socialMedia={socialMedia} />
        </Box>
      </Drawer>
    </Box>
  );
}

export default CustomDrawer;
