/* eslint-disable react/jsx-props-no-spreading */
import { TextField } from '@mui/material';
import { TextFieldProps } from '@mui/material/TextField';
import React from 'react';

import defaultInput from './DefaultInput';

const UiInput: React.ForwardRefExoticComponent<
  TextFieldProps & React.RefAttributes<HTMLInputElement>
> = React.forwardRef<HTMLInputElement, TextFieldProps>((props, ref) => (
  <TextField ref={ref} {...props} />
));

UiInput.displayName = 'UiInput';

export const DefaultInput: React.FC<TextFieldProps> = defaultInput(UiInput);

export default UiInput;
