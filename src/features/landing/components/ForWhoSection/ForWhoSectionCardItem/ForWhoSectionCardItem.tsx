import { useTranslation } from 'react-i18next';
import { Card, Grid, Paper, Typography } from '@mui/material';

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
  },
};

export function ForWhoSectionCardItem({
                                        imageSrc,
                                        imageAltText,
                                        text,
                                      }: IForWhoSectionCardItemProps) {
  const { t } = useTranslation();

  return (
    <Grid item md={6} xs={12} sx={{ alignSelf: 'stretch' }}>
      <Card sx={{ ...style.card }}>
        <Paper elevation={0}>
          <img src={imageSrc} alt={imageAltText} style={{ width: '100%', height: '100%' }} />
        </Paper>
        <Typography variant={'body1'} component={'p'} sx={{ ...style.text }}>
          {t(text)}
        </Typography>
      </Card>
    </Grid>
  );
}
