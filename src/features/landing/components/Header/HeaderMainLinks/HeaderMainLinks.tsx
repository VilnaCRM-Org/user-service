import { Grid } from '@mui/material';
import * as React from 'react';
import { useTranslation } from 'react-i18next';

import CustomLink from '@/components/ui/CustomLink/CustomLink';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';

enum GapForLinksEnum {
  Tablet = '10px',
  Laptop = '16px',
  Desktop = '32px',
}

export default function HeaderMainLinks() {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const { isDesktop, isLaptop, isTablet, isMobile, isSmallest, isSmallTablet } = useScreenSize();

  if (isMobile || isSmallest || isSmallTablet) {
    return null;
  }

  let gapForLinks = '32px';

  if (isTablet) {
    gapForLinks = GapForLinksEnum.Tablet;
  } else if (isLaptop) {
    gapForLinks = GapForLinksEnum.Laptop;
  } else if (isDesktop) {
    gapForLinks = GapForLinksEnum.Desktop;
  }

  return (
    <Grid
      container
      justifyContent='center'
      gap='32px'
      sx={{
        display: 'flex',
        flexGrow: 1,
        justifyContent: 'center',
        gap: gapForLinks,
      }}
    >
      <Grid item>
        <CustomLink href='/' style={{ textDecoration: 'none', color: 'black', fontSize: '15px' }}>
          {t('header.advantages')}
        </CustomLink>
      </Grid>

      <Grid item>
        <CustomLink href='/' style={{ textDecoration: 'none', color: 'black', fontSize: '15px' }}>
          {t('header.for_who')}
        </CustomLink>
      </Grid>

      <Grid item>
        <CustomLink href='/' style={{ textDecoration: 'none', color: 'black', fontSize: '15px' }}>
          {t('header.integration')}
        </CustomLink>
      </Grid>

      <Grid item>
        <CustomLink href='/' style={{ textDecoration: 'none', color: 'black', fontSize: '15px' }}>
          {t('header.contacts')}
        </CustomLink>
      </Grid>
    </Grid>
  );
}
