/* eslint-disable react/jsx-props-no-spreading */
import { FormControlLabel, Checkbox, ThemeProvider } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import CheckedCheckBox from '../../features/landing/assets/svg/auth-section/checkedCheckbox.svg';
import RestCheckBox from '../../features/landing/assets/svg/auth-section/restCheckbox.svg';

import { theme } from './theme';
import { UiCheckboxProps } from './types';

function UiCheckbox({ label, sx, onChange, props }: UiCheckboxProps) {
  return (
    <ThemeProvider theme={theme}>
      <FormControlLabel
        sx={sx}
        control={
          <Checkbox
            {...props}
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
