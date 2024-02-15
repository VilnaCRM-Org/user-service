import { TypographyProps, ThemeProvider } from '@mui/material';

import theme from './theme';

export default function defaultTypography(
  Component: React.ComponentType<TypographyProps>
) {
  return function DefaultTypography(
    props: TypographyProps
  ): React.ReactElement {
    const { sx, children, component, variant, id } = props;
    return (
      <ThemeProvider theme={theme}>
        <Component sx={sx} component={component} variant={variant} id={id}>
          {children}
        </Component>
      </ThemeProvider>
    );
  };
}
