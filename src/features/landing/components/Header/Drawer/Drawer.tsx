import { Drawer, Box, Stack, Button, Link } from '@mui/material';
import Image from 'next-export-optimize-images/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiButton } from '@/components';

import Bars from '../../../assets/svg/header-drawer/menu-04.svg';
import CloseImage from '../../../assets/svg/header-drawer/x-close.svg';
import Logo from '../../../assets/svg/logo/Logo.svg';
import { SocialMediaList } from '../../SocialMedia';
import { drawerNavList, socialMedia } from '../constants';
import NavList from '../NavList/NavList';

import styles from './styles';
import { VilnaCRMEmail } from './VilnaCRMEmail';

function CustomDrawer(): React.ReactElement {
  const { t } = useTranslation();
  const [isDrawerOpen, setIsDrawerOpen] = React.useState(false);

  const handleCloseDrawer: () => void = () => setIsDrawerOpen(false);
  const handleOpenDrawer: () => void = () => setIsDrawerOpen(true);

  return (
    <Box sx={styles.wrapper}>
      <Button
        aria-label={t('header.drawer.button_aria_labels.bars')}
        sx={styles.button}
        onClick={handleOpenDrawer}
      >
        <Image src={Bars} alt={t('header.drawer.image_alt.bars')} width={24} height={24} />
      </Button>
      <Drawer
        sx={styles.drawer}
        anchor="right"
        open={isDrawerOpen}
        onClose={handleCloseDrawer}
        role="menu"
      >
        <Box width="23.4375rem" textAlign="center" role="presentation" sx={styles.drawerContent}>
          <Stack direction="row" justifyContent="space-between" alignItems="center">
            <Link href="/">
              <Image src={Logo} alt={t('header.logo_alt')} width={131} height={44} />
            </Link>
            <Button
              aria-label={t('header.drawer.button_aria_labels.exit') as string}
              sx={styles.button}
              onClick={handleCloseDrawer}
            >
              <Image
                src={CloseImage}
                alt={t('header.drawer.image_alt.exit')}
                width={24}
                height={24}
              />
            </Button>
          </Stack>
          <Stack
            direction="row"
            alignItems="center"
            justifyContent="center"
            gap="0.563rem"
            mt="0.75rem"
          >
            <Link href="#signUp" sx={styles.link}>
              <UiButton
                fullWidth
                variant="outlined"
                size="small"
                disabled
                onClick={handleCloseDrawer}
              >
                {t('header.actions.log_in')}
              </UiButton>
            </Link>
            <Link href="#signUp" sx={styles.link}>
              <UiButton fullWidth onClick={handleCloseDrawer} variant="contained" size="small">
                {t('header.actions.try_it_out')}
              </UiButton>
            </Link>
          </Stack>
          <NavList navItems={drawerNavList} handleClick={handleCloseDrawer} />
          <VilnaCRMEmail />
          <SocialMediaList socialLinks={socialMedia} />
        </Box>
      </Drawer>
    </Box>
  );
}

export default CustomDrawer;
