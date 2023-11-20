import { Grid, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button/Button';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

const styles = {
  mainGridContainer: {
    height: '100%',
    width: '100%',
    maxWidth: '343px',
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    marginBottom: '38px',
  },
  mainHeading: {
    width: '100%',
    maxWidth: '343px',
    color: '#1A1C1E',
    textAlign: 'inherit',
    fontFamily: 'GolosText-Bold, sans-serif',
    fontSize: '46px',
    fontStyle: 'normal',
    fontWeight: '700',
    lineHeight: 'normal',
  },
  mainText: {
    marginTop: '16px',
    width: '100%',
    maxWidth: '343px',
    color: '#1A1C1E',
    fontFamily: 'GolosText-Regular, sans-seriv',
    fontSize: '18px',
    fontStyle: 'normal',
    fontWeight: '400',
    lineHeight: '30px',
  },
};

export function ForWhoMainTextsContent({
                                         onTryItOutButtonClick,
                                       }: {
  onTryItOutButtonClick: () => void;
}) {
  const { t } = useTranslation();
  const { isSmallest, isMobile } = useScreenSize();

  return (
    <Grid item container sx={{ ...styles.mainGridContainer }}>
      {/* Top Content */}
      <Grid item>
        <Typography
          variant='h2'
          component='h2'
          style={{
            ...styles.mainHeading,
            fontSize: isSmallest ? '28px' : styles.mainHeading.fontSize,
          }}
        >
          {t('For who')}
        </Typography>

        <Typography
          variant='body1'
          component='p'
          style={{
            ...styles.mainText,
            fontSize: isSmallest ? '15px' : '18px',
          }}
        >
          {t(
            'We created Vilna, focusing on the specifics of the service business,\n' +
            'which is not suitable for ordinary e-commerce templates',
          )}
        </Typography>
        {isMobile || isSmallest ? null : (
          <Button
            customVariant='light-blue'
            buttonSize='big'
            style={{ marginTop: '24px' }}
            onClick={onTryItOutButtonClick}
          >
            {t('Try it out')}
          </Button>
        )}
      </Grid>
    </Grid>
  );
}
