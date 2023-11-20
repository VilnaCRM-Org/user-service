import { Card, Grid, Paper, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';

import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

interface IForWhoSectionCardItemProps {
  imageSrc: string;
  imageAltText: string;
  text: string;
}

const style = {
  text: {
    color: '#1A1C1E',
    fontFamily: 'GolosText-Regular, sans-serif',
    fontSize: '15px',
    fontStyle: 'normal',
    fontWeight: '400',
    lineHeight: '25px',
  },
  card: {
    padding: '27px 32px 28px 32px',
    display: 'flex',
    alignItems: 'center',
    gap: '12px',
    minHeight: '115px',
    boxShadow: '0px 8px 27px 0px rgba(49, 59, 67, 0.14)',
  },
};

export function ForWhoSectionCardItem({
  imageSrc,
  imageAltText,
  text,
}: IForWhoSectionCardItemProps) {
  const { isMobile, isSmallest } = useScreenSize();
  const { t } = useTranslation();

  return (
    <Grid item md={6} xs={12} sx={{ alignSelf: 'stretch', flexGrow: '1' }}>
      <Card
        sx={{
          ...style.card,
          boxShadow: isMobile || isSmallest ? 'none' : '0px 8px 27px 0px rgba(49, 59, 67, 0.14)',
          padding: isMobile || isSmallest ? '0' : '27px 32px 28px 32px',
          minHeight: isMobile || isSmallest ? 'max-content' : '115px',
          height: '100%',
        }}
      >
        <Paper elevation={0}>
          <img
            src={imageSrc}
            alt={imageAltText}
            style={{ width: '100%', height: '100%', minWidth: '20px' }}
          />
        </Paper>
        <Typography variant="body1" component="p" style={{ ...style.text }}>
          {t(text)}
        </Typography>
      </Card>
    </Grid>
  );
}
