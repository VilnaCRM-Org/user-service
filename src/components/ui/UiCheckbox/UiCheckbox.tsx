/* eslint-disable react/require-default-props */
import {
  FormControlLabel,
  Checkbox,
  ThemeProvider,
  createTheme,
} from '@mui/material';
import Image from 'next/image';
import React from 'react';

import CheckedCheckBox from '../../../features/landing/assets/svg/auth-section/checkedCheckbox.svg';
import RestCheckBox from '../../../features/landing/assets/svg/auth-section/restCheckbox.svg';

const theme = createTheme({
  components: {
    MuiCheckbox: {
      defaultProps: {},
      styleOverrides: {
        root: {},
      },
    },
  },
});

function UiCheckbox({
  label,
  sx,
  onChange,
}: {
  onChange: (event: React.ChangeEvent<HTMLInputElement>) => void;
  label: string | React.ReactNode;
  sx?: object;
}) {
  return (
    <ThemeProvider theme={theme}>
      <FormControlLabel
        sx={sx}
        control={
          <Checkbox
            onChange={onChange}
            sx={{
              padding: 0,
              marginRight: '13px',
            }}
            icon={
              <Image src={RestCheckBox} alt="Checkbox" width={24} height={24} />
            }
            checkedIcon={
              <Image
                src={CheckedCheckBox}
                alt="Checkbox"
                width={24}
                height={24}
              />
            }
            inputProps={{ 'aria-label': 'Checkbox A' }}
          />
        }
        label={label}
      />
    </ThemeProvider>
  );
}

export default UiCheckbox;
