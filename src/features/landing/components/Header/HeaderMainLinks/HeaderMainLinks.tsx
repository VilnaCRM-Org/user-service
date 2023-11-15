import * as React from 'react';
import { Grid } from '@mui/material';
import { CustomLink } from '@/components/ui/CustomLink/CustomLink';
import { useTranslation } from 'react-i18next';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

enum GapForLinksEnum {
  Tablet = '10px',
  Laptop = '16px',
  Desktop = '32px'
}

export function HeaderMainLinks() {
  const { t } = useTranslation();
  const {
    isDesktop,
    isLaptop,
    isTablet,
    isMobile,
    isSmallest,
  } = useScreenSize();

  if (isMobile || isSmallest) {
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
    <Grid container justifyContent={'center'} gap={'32px'}
          sx={{
            display: 'flex',
            flexGrow: 1,
            justifyContent: 'center',
            gap: gapForLinks,
          }}>
      <Grid item>
        <CustomLink href={'/'} style={{ textDecoration: 'none', color: 'black' }}>
          {t('Advantages')}
        </CustomLink>
      </Grid>

      <Grid item>
        <CustomLink href={'/'} style={{ textDecoration: 'none', color: 'black' }}>
          {t('For who')}
        </CustomLink>
      </Grid>

      <Grid item>
        <CustomLink href={'/'} style={{ textDecoration: 'none', color: 'black' }}>
          {t('Integration')}
        </CustomLink>
      </Grid>

      <Grid item>
        <CustomLink href={'/'} style={{ textDecoration: 'none', color: 'black' }}>
          {t('Contacts')}
        </CustomLink>
      </Grid>
    </Grid>
  );
}
