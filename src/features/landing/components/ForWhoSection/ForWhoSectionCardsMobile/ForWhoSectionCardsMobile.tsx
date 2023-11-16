import React from 'react';
import { Box, Grid, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/Button/Button';
import { scrollToRegistrationSection } from '@/features/landing/utils/helpers/scrollToRegistrationSection';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

interface IForWhoSectionCardsMobileProps {
  cardItemsJSX: React.ReactNode;
}

const styles = {
  mainBox: {
    position: 'absolute',
    bottom: '-150px',
    zIndex: 900,
  },
  mainGrid: {
    borderTopLeftRadius: '24px',
    borderTopRightRadius: '24px',
    backgroundColor: '#fff',
    padding: '32px 24px 32px 24px',
  },
  heading: {
    maxWidth: '374px',
    width: '100%',
    color: '#1A1C1E',
    fontFamily: 'Stolz-Regular, sans-serif',
    fontSize: '28px',
    fontStyle: 'normal',
    fontWeight: 700,
    lineHeight: 'normal',
    marginBottom: '12px',
  },
};

export function ForWhoSectionCardsMobile({ cardItemsJSX }: IForWhoSectionCardsMobileProps) {
  const { t } = useTranslation();
  const { isSmallest } = useScreenSize();

  return (
    <Box
      sx={{
        ...styles.mainBox,
      }}
    >
      <Grid
        item
        sx={{
          ...styles.mainGrid,
        }}
      >
        <Typography
          variant={'h4'}
          component={'h4'}
          sx={{
            ...styles.heading,
            fontSize: isSmallest ? '22px' : styles.heading.fontSize,
          }}
        >
          {t('Our CRM is ideal if you:')}
        </Typography>

        <Box sx={{ marginBottom: '32px' }}>{cardItemsJSX}</Box>

        <Box sx={{ display: 'flex', justifyContent: 'flex-start' }}>
          <Button
            customVariant={'light-blue'}
            onClick={scrollToRegistrationSection}
            buttonSize={'medium'}
          >
            {t('Try it out')}
          </Button>
        </Box>
      </Grid>
    </Box>
  );
}
