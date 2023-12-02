import { Box, Grid, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';

const styles = {
  mainBox: {
    position: 'relative',
    top: '-13.4375rem', // -215px
    padding: '0 0 0 0',
  },
  secondaryHeading: {
    maxWidth: '374px',
    width: '100%',
    color: '#1A1C1E',
    fontFamily: 'Stolzl-Regular, sans-serif',
    fontSize: '28px',
    fontStyle: 'normal',
    fontWeight: 700,
    lineHeight: 'normal',
  },
};

export default function ForWhoSectionCards({ cardItemsJSX }: { cardItemsJSX: React.ReactNode }) {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const { isSmallest } = useScreenSize();

  return (
    <Box sx={{ ...styles.mainBox }}>
      <Grid item>
        <Typography
          style={{
            ...styles.secondaryHeading,
            fontSize: isSmallest ? '22px' : styles.secondaryHeading.fontSize,
          }}
        >
          {t('for_who.heading_secondary')}
        </Typography>
      </Grid>
      <Grid
        container
        alignItems="stretch"
        spacing={3}
        sx={{
          position: 'absolute',
          bottom: '-150px',
          zIndex: 900,
          padding: '0',
        }}
      >
        {cardItemsJSX}
      </Grid>
    </Box>
  );
}
