import { Drawer, Box, Stack, Button } from '@mui/material';
import Image from 'next/image';
import Link from 'next/link';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiButton } from '@/components';

import Bars from '../../../assets/svg/header-drawer/menu-04.svg';
import CloseImage from '../../../assets/svg/header-drawer/x-close.svg';
import Logo from '../../../assets/svg/logo/Logo.svg';
import SocialMediaList from '../../SocialMedia/SocialMediaList/SocialMediaList';
import { drawerNavList, socialMedia } from '../constants';
import NavList from '../NavList/NavList';

import styles from './styles';
import VilnaCRMGmail from './VilnaCRMGmail/VilnaCRMGmail';

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
        <Image
          src={Bars}
          alt={t('header.drawer.image_alt.bars')}
          width={24}
          height={24}
        />
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
            <Image
              src={Logo}
              alt={t('header.logo_alt')}
              width={131}
              height={44}
            />
            <Button
              aria-label={t('header.drawer.button_aria_labels.exit') as string}
              sx={styles.button}
              onClick={() => setIsDrawerOpen(!isDrawerOpen)}
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
            <UiButton
              fullWidth
              variant="outlined"
              size="small"
              onClick={() => handleClick()}
            >
              <Link href="#signUp"> {t('header.actions.log_in')}</Link>
            </UiButton>
            <UiButton
              fullWidth
              onClick={() => handleClick()}
              variant="contained"
              size="small"
            >
              <Link href="#signUp">{t('header.actions.try_it_out')}</Link>
            </UiButton>
          </Stack>
          <NavList navItems={drawerNavList} handleClick={() => handleClick()} />
          <VilnaCRMGmail />
          <SocialMediaList socialLinks={socialMedia} />
        </Box>
      </Drawer>
    </Box>
  );
}

export default CustomDrawer;
