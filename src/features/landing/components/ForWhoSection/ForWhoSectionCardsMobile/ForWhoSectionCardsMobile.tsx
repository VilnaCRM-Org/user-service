import { Box, Grid, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import Button from '@/components/ui/Button/Button';

import useScreenSize from '../../../hooks/useScreenSize/useScreenSize';
import { TRANSLATION_NAMESPACE } from '../../../utils/constants/constants';
import scrollToRegistrationSection from '../../../utils/helpers/scrollToRegistrationSection';

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
    fontFamily: 'Stolzl-Regular, sans-serif',
    fontSize: '28px',
    fontStyle: 'normal',
    fontWeight: 700,
    lineHeight: 'normal',
    marginBottom: '12px',
  },
};

export default function ForWhoSectionCardsMobile({ cardItemsJSX }: IForWhoSectionCardsMobileProps) {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
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
          style={{
            ...styles.heading,
            fontSize: isSmallest ? '22px' : styles.heading.fontSize,
          }}
        >
          {t('for_who.heading_secondary')}
        </Typography>

        <Box sx={{ marginBottom: '32px' }}>{cardItemsJSX}</Box>

        <Box sx={{ display: 'flex', justifyContent: 'flex-start' }}>
          <Button
            customVariant="light-blue"
            onClick={scrollToRegistrationSection}
            buttonSize="medium"
          >
            {t('for_who.button_text')}
          </Button>
        </Box>
      </Grid>
    </Box>
  );
}
