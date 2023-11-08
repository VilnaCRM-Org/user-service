import { useTranslation } from 'react-i18next';
import { Grid } from '@mui/material';
import { Button } from '@/components/ui/Button/Button';
import * as React from 'react';

interface IHeaderDrawerActionButtonsProps {
  onSignInButtonClick: () => void;
  onTryItOutButtonClick: () => void;
}

export function HeaderDrawerActionButtons({
                                            onSignInButtonClick,
                                            onTryItOutButtonClick,
                                          }: IHeaderDrawerActionButtonsProps) {
  const { t } = useTranslation();

  return (
    <Grid container justifyContent={'space-between'} flexGrow={0}
          sx={{
            display: 'flex',
            width: '100%',
            maxWidth: '100%',
            md: { display: 'none' },
            marginBottom: '16px',
          }}>
      <Grid item xs={6} sm={6}>
        <Button customVariant={'transparent-white'} onClick={onSignInButtonClick}
                fullWidth style={{ marginRight: '4.5px' }}>
          {t('Увійти')}
        </Button>
      </Grid>
      <Grid item xs={6} sm={6}>
        <Button customVariant={'light-blue'} onClick={onTryItOutButtonClick}
                fullWidth style={{ marginLeft: '4.5px' }}>
          {t('Спробувати')}
        </Button>
      </Grid>
    </Grid>
  );
}
