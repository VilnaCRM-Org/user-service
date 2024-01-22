import { Drawer, Box, Stack, Button } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiButton } from '@/components';

import Bars from '../../../assets/svg/header-drawer/menu-04.svg';
import FacebookDrawerIcon from '../../../assets/svg/header-drawer/socials/facebook.svg';
import GitHubDrawerIcon from '../../../assets/svg/header-drawer/socials/github.svg';
import InstagramDrawerIcon from '../../../assets/svg/header-drawer/socials/instagram.svg';
import LinkedinDrawerIcon from '../../../assets/svg/header-drawer/socials/linked-in.svg';
import CloseImage from '../../../assets/svg/header-drawer/x-close.svg';
import Logo from '../../../assets/svg/logo/Logo.svg';

import { NavList } from './NavList';
import { SocialMedia } from './SocialMedia';
import { drawerStyles } from './styles';
import { VilnaGmail } from './VilnaGmail';

function UiDrawer() {
  const navList = [
    {
      id: 'advantages',
      title: 'header.advantages',
    },
    {
      id: 'for-who',
      title: 'header.for_who',
    },
    {
      id: 'integration',
      title: 'header.integration',
    },
    {
      id: 'contacts',
      title: 'header.contacts',
    },
  ];

  const socialMedia = [
    {
      id: 'instagram-link',
      icon: InstagramDrawerIcon,
      alt: 'header.drawer.alt_social_images.instagram',
      ariaLabel: 'header.drawer.aria_labels_social_images.instagram',
      linkHref: '/',
    },
    {
      id: 'gitHub-link',
      icon: GitHubDrawerIcon,
      alt: 'header.drawer.alt_social_images.github',
      ariaLabel: 'header.drawer.aria_labels_social_images.github',
      linkHref: '/',
    },
    {
      id: 'facebook-link',
      icon: FacebookDrawerIcon,
      alt: 'header.drawer.alt_social_images.facebook',
      ariaLabel: 'header.drawer.aria_labels_social_images.facebook',
      linkHref: '/',
    },
    {
      id: 'linkedin-link',
      icon: LinkedinDrawerIcon,
      alt: 'header.drawer.alt_social_images.linkedin',
      ariaLabel: 'header.drawer.aria_labels_social_images.linkedin',
      linkHref: '/',
    },
  ];

  const { t } = useTranslation();
  const [isDrawerOpen, setIsDrawerOpen] = React.useState(false);

  return (
    <Box sx={drawerStyles.wrapper}>
      <Button
        aria-label={t('header.drawer.button_aria_labels.bars') as string}
        sx={drawerStyles.button}
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
          sx={drawerStyles.drawerContent}
        >
          <Stack
            direction="row"
            justifyContent="space-between"
            alignItems="center"
          >
            <Image src={Logo} alt="Header Image" width={131} height={44} />
            <Button
              aria-label={t('header.drawer.button_aria_labels.exit') as string}
              sx={drawerStyles.button}
              onClick={() => setIsDrawerOpen(!isDrawerOpen)}
            >
              <Image src={CloseImage} alt="Exit Image" width={24} height={24} />
            </Button>
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
          <NavList navList={navList} />
          <VilnaGmail />
          <SocialMedia socialMedia={socialMedia} />
        </Box>
      </Drawer>
    </Box>
  );
}

export default UiDrawer;
