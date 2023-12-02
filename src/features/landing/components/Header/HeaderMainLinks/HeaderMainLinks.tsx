import { Grid } from '@mui/material';
import * as React from 'react';
import { useTranslation } from 'react-i18next';

import CustomLink from '@/components/ui/CustomLink/CustomLink';

import useScreenSize from '../../../hooks/useScreenSize/useScreenSize';
import { TRANSLATION_NAMESPACE } from '../../../utils/constants/constants';

const styles = {
  mainGrid: {
    display: 'flex',
    justifyContent: 'flex-end',
    alignItems: 'center',
    gap: '32px',
    marginRight: '9.96875rem', // 159px
    color: 'black',
  },
  mainGridLaptop: {
    marginRight: '8.125rem', // 136px
  },
  link: {
    textDecoration: 'none',
    color: 'black',
    fontSize: '15px',
  },
};

export default function HeaderMainLinks() {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const { isMobile, isSmallest, isSmallTablet, isLaptop, isBigTablet } = useScreenSize();

  if (isMobile || isSmallest || isSmallTablet || isBigTablet) {
    return null;
  }

  return (
    <Grid
      container
      justifyContent="center"
      sx={{
        ...styles.mainGrid,
        ...(isLaptop ? styles.mainGridLaptop : {}),
      }}
    >
      <Grid item>
        <CustomLink href="/" style={{ ...styles.link }}>
          {t('header.advantages')}
        </CustomLink>
      </Grid>

      <Grid item>
        <CustomLink href="/" style={{ ...styles.link }}>
          {t('header.for_who')}
        </CustomLink>
      </Grid>

      <Grid item>
        <CustomLink href="/" style={{ ...styles.link }}>
          {t('header.integration')}
        </CustomLink>
      </Grid>

      <Grid item>
        <CustomLink href="/" style={{ ...styles.link }}>
          {t('header.contacts')}
        </CustomLink>
      </Grid>
    </Grid>
  );
}
