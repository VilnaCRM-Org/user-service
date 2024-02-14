/* eslint-disable react/jsx-props-no-spreading */
import { Button, ButtonProps } from '@mui/material';

import withThemeProvider from './withThemeProvider';

function UiButton(props: ButtonProps): React.ReactElement {
  return <Button {...props} />;
}

export default withThemeProvider(UiButton);
