import { useTranslation } from 'react-i18next';
import { Grid, Typography } from '@mui/material';

import { Button } from '@/components/ui/Button/Button';

const styles = {
  mainGridContainer: {
    height: '100%',
    minHeight: '498px',
    width: '100%',
    maxWidth: '343px',
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
  },
  mainHeading: {
    width: '100%',
    maxWidth: '343px',
  },
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

export function ForWhoMainTextsContent({ onTryItOutButtonClick }: {
  onTryItOutButtonClick: () => void;
}) {
  const { t } = useTranslation();

  return (
    <Grid item container md={6} sx={{ ...styles.mainGridContainer }}>

      {/* Top Content */}
      <Grid item>
        <Typography variant={'h2'} component={'h2'} sx={{ ...styles.mainHeading }} >
          { t('For who')}
          </Typography>

          <Typography variant={'body1'} component={'p'}
                    sx={{ marginTop: '16px', width: '100%', maxWidth: '343px' }}>
          {t('We created Vilna, focusing on the specifics of the service business,\n' +
            'which is not suitable for ordinary e-commerce templates')}
        </Typography>
        <Button customVariant={'light-blue'}
                buttonSize={'big'}
                style={{ marginTop: '24px' }}
                onClick={onTryItOutButtonClick}>{t('Try it out')}</Button>
      </Grid>

      {/* Bottom content */}
      <Grid item>
        <Typography variant={'h4'} component={'h4'}
                    sx={{
                      ...styles.secondaryHeading,
                    }}>
          {t('Our CRM is ideal if you:')}
        </Typography>
      </Grid>
    </Grid>
  );
}
