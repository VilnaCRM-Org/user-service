import React from 'react';
import { Box, Grid, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

const styles = {
  secondaryHeading: {
    maxWidth: '374px',
    width: '100%',
    color: '#1A1C1E',
    fontFamily: 'Stolz-Regular, sans-serif',
    fontSize: '28px',
    fontStyle: 'normal',
    fontWeight: 700,
    lineHeight: 'normal',
  },
};

export function ForWhoSectionCards({ cardItemsJSX }: {
  cardItemsJSX: React.ReactNode;
}) {
  const { t } = useTranslation();
  const { isSmallest } = useScreenSize();

  return (
    <>
      <Box sx={{ padding: '0 34px 0 34px' }}>
        <Grid item>
          <Typography variant={'h4'} component={'h4'}
                      sx={{
                        ...styles.secondaryHeading,
                        fontSize: (isSmallest) ? '22px' : styles.secondaryHeading.fontSize,
                      }}>
            {t('Our CRM is ideal if you:')}
          </Typography>
        </Grid>
      </Box>
      <Grid container
            alignItems={'stretch'}
            spacing={3}
            sx={{
              position: 'absolute',
              bottom: '-150px',
              zIndex: 900,
              padding: '0 34px',
            }}>
        {cardItemsJSX}
      </Grid>
    </>
  );
}
