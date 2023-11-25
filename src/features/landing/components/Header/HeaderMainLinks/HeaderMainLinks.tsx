import { Grid } from '@mui/material';
import * as React from 'react';
import { useTranslation } from 'react-i18next';

import CustomLink from '@/components/ui/CustomLink/CustomLink';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';

export default function HeaderMainLinks() {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const { isMobile, isSmallest, isSmallTablet } = useScreenSize();

  if (isMobile || isSmallest || isSmallTablet) {
    return null;
  }

  return (
    <Grid
      container
      justifyContent='center'
      sx={{
        display: 'flex',
        justifyContent: 'flex-end',
        gap: '32px',
        marginRight: '9.96875rem',
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
