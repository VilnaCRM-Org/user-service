import { Grid, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button/Button';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';

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
    marginTop: '76px',
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

export default function ForWhoMainTextsContent({
  onTryItOutButtonClick,
}: {
  onTryItOutButtonClick: () => void;
}) {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const { isSmallest, isMobile } = useScreenSize();

  return (
    <Grid item container sx={{ ...styles.mainGridContainer }}>
      {/* Top Content */}
      <Grid item>
        <Typography
          variant="h2"
          component="h2"
          style={{
            ...styles.mainHeading,
            fontSize: isSmallest || isMobile ? '28px' : styles.mainHeading.fontSize,
            textAlign: 'inherit',
          }}
        >
          {t('for_who.heading_main')}
        </Typography>

        <Typography
          variant="body1"
          component="p"
          style={{
            ...styles.mainText,
            fontSize: isSmallest || isMobile ? '15px' : '18px',
          }}
        >
          {t('for_who.text_main')}
        </Typography>
        {isMobile || isSmallest ? null : (
          <Button
            customVariant="light-blue"
            buttonSize="big"
            style={{ marginTop: '24px' }}
            onClick={onTryItOutButtonClick}
          >
            {t('for_who.button_text')}
          </Button>
        )}
      </Grid>
    </Grid>
  );
}
