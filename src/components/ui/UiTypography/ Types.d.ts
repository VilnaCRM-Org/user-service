import {} from '@mui/material/Typography';

declare module '@mui/material/Typography' {
  interface TypographyPropsVariantOverrides {
    medium16: true;
    medium15: true;
    medium14: true;
    regular16: true;
    bodyText18: true;
    bodyText16: true;
    bold22: true;
    demi18: true;
    button: true;
    bodyMobile: true;
  }
}
