import { Grid } from '@mui/material';
import React from 'react';

const styles = {
  mainGridWithBackground: {
    width: '100%',
    background: `url('/assets/img/Registration/Background.png')`,
    backgroundSize: 'contain',
    backgroundRepeat: 'no-repeat',
    backgroundPosition: 'center',
    height: '100%',
    maxWidth: '100%',
    maxHeight: '548px',
    alignSelf: 'stretch',
    position: 'relative',
  },
};

export default function SignUpWrapperWithBackground({ children }: {
  children: React.ReactNode;
}) {
  return (
    <Grid item lg={6} md={12} sx={{ ...styles.mainGridWithBackground }}>{children}</Grid>
  );
}
