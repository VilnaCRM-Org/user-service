/* eslint-disable max-len */
import { Box, FormControlLabel } from '@mui/material';
import React from 'react';

import styles from './styles';
import { UiCheckboxProps } from './types';

function UiCheckbox({
  label,
  sx,
  onChange,
  error,
}: UiCheckboxProps): React.ReactElement {
  return (
    <FormControlLabel
      sx={sx}
      control={
        <Box
          className="MuiButtonBase-root MuiCheckbox-root MuiCheckbox-colorPrimary MuiCheckbox-sizeMedium PrivateSwitchBase-root MuiCheckbox-root MuiCheckbox-colorPrimary MuiCheckbox-sizeMedium MuiCheckbox-root MuiCheckbox-colorPrimary MuiCheckbox-sizeMedium css-12wnr2w-MuiButtonBase-root-MuiCheckbox-root"
          component="span"
          onChange={onChange}
          sx={error ? styles.checkboxWrapper : styles.checkboxWrapperError}
        >
          <input type="checkbox" className="PrivateSwitchBase-input" />
        </Box>
      }
      label={label}
    />
  );
}

export default UiCheckbox;
