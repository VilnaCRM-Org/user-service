import { Box, FormControlLabel } from '@mui/material';
import React from 'react';

import styles from './styles';
import { UiCheckboxProps } from './types';

function UiCheckbox({ label, sx, onChange, error, disabled }: UiCheckboxProps): React.ReactElement {
  return (
    <FormControlLabel
      sx={sx}
      control={
        <Box
          component="span"
          onChange={onChange}
          sx={error ? styles.checkboxWrapperError : styles.checkboxWrapper}
        >
          <input type="checkbox" className="PrivateSwitchBase-input" disabled={disabled} />
        </Box>
      }
      label={label}
    />
  );
}

export default UiCheckbox;
